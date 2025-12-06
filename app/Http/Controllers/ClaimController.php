<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Insurer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ClaimController extends Controller
{
    /**
     * Store a new claim submission.
     * Validates input, creates claim and items, batches the claim, and sends notification.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'insurer_code' => 'required|string|exists:insurers,code', // Must be a valid insurer code
            'provider_name' => 'required|string', // Name of the healthcare provider
            'encounter_date' => 'required|date', // Date of the medical encounter
            'specialty' => 'required|string', // Medical specialty (e.g., cardiology)
            'priority' => 'required|integer|min:1|max:5', // Priority level (1-5)
            'items' => 'required|array|min:1', // Array of claim items
            'items.*.name' => 'required|string', // Name of each item
            'items.*.unit_price' => 'required|numeric|min:0', // Price per unit
            'items.*.quantity' => 'required|integer|min:1', // Quantity of units
        ]);

        // Find the insurer by code
        $insurer = Insurer::where('code', $request->insurer_code)->first();

        // Calculate total value and prepare item data
        $totalValue = 0;
        $itemsData = [];
        foreach ($request->items as $item) {
            $subtotal = $item['unit_price'] * $item['quantity'];
            $totalValue += $subtotal;
            $itemsData[] = [
                'name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
            ];
        }

        try {
            // Use database transaction to ensure data integrity
            DB::transaction(function () use ($request, $insurer, $totalValue, $itemsData) {
                // Create the claim record
                $claim = Claim::create([
                    'insurer_id' => $insurer->id,
                    'provider_name' => $request->provider_name,
                    'encounter_date' => $request->encounter_date,
                    'submission_date' => now(),
                    'specialty' => $request->specialty,
                    'priority' => $request->priority,
                    'total_value' => $totalValue,
                ]);

                // Create claim items
                foreach ($itemsData as $itemData) {
                    $claim->items()->create($itemData);
                }

                // Batch the claim for optimal processing
                $this->batchClaim($claim, $insurer);
            });

            // Return success response
            return response()->json(['message' => 'Claim submitted successfully'], 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Claim submission failed: ' . $e->getMessage());

            // Return error response
            return response()->json(['message' => 'An error occurred while submitting the claim. Please try again.'], 500);
        }
    }

    /**
     * Batch the claim to minimize processing costs.
     *
     * Algorithm Approach:
     * 1. Determine batch date based on insurer's date_preference (encounter or submission).
     * 2. Find existing batch for provider_name + batch_date + insurer.
     * 3. If batch exists and has space (claims count < max_batch_size), add claim and update total_cost.
     * 4. Else, create new batch.
     * 5. Calculate claim cost using: base_cost * (1 + time_cost) * specialty_efficiency * priority_multiplier + total_value * value_multiplier
     *    Where time_cost = min + (max - min) * (day_of_month - 1) / 29
     * 6. Send email notification to insurer.
     *
     * This simple greedy approach adds to existing batches when possible to avoid base costs, but could be optimized further with more complex logic.
     */
    /**
     * Batch the claim for optimal processing.
     * Finds or creates a batch and assigns the claim to it.
     */
    private function batchClaim(Claim $claim, Insurer $insurer)
    {
        // Determine batch date based on insurer's preference
        $batchDate = $insurer->date_preference === 'encounter' ? $claim->encounter_date : $claim->submission_date;

        // Find existing batch for this provider, date, and insurer
        $batch = Batch::where('provider_name', $claim->provider_name)
            ->where('date', $batchDate)
            ->where('insurer_id', $insurer->id)
            ->first();

        if (!$batch) {
            // Create new batch if none exists
            $batch = Batch::create([
                'provider_name' => $claim->provider_name,
                'date' => $batchDate,
                'insurer_id' => $insurer->id,
                'total_cost' => $this->calculateClaimCost($claim, $insurer, $batchDate),
            ]);
        } elseif ($batch->claims()->count() < $insurer->max_batch_size) {
            // Add to existing batch if not full
            $batch->total_cost += $this->calculateClaimCost($claim, $insurer, $batchDate);
            $batch->save();
        } else {
            // Create new batch if existing is full
            $batch = Batch::create([
                'provider_name' => $claim->provider_name,
                'date' => $batchDate,
                'insurer_id' => $insurer->id,
                'total_cost' => $this->calculateClaimCost($claim, $insurer, $batchDate),
            ]);
        }

        // Assign batch to claim
        $claim->batch_id = $batch->id;
        $claim->save();

        // Send notification email to insurer
        Mail::to($insurer->email)->send(new \App\Mail\BatchNotification($batch));
    }

    /**
     * Calculate the processing cost for a claim within a batch.
     * Considers time of month, specialty efficiency, priority, and value.
     */
    private function calculateClaimCost(Claim $claim, Insurer $insurer, $batchDate)
    {
        // Calculate day of month (1-31)
        $day = (int) $batchDate->format('j');

        // Linear interpolation for time-based cost (20% to 50% over the month)
        $timeCost = $insurer->time_cost_min + ($insurer->time_cost_max - $insurer->time_cost_min) * ($day - 1) / 29;

        // Get specialty efficiency multiplier
        $specialtyEff = $insurer->specialty_efficiency[$claim->specialty] ?? 1.0;

        // Calculate total cost: base * (1 + time) * specialty * priority^exponent + value * multiplier
        $cost = $insurer->base_cost * (1 + $timeCost) * $specialtyEff * $insurer->priority_cost_multiplier ** ($claim->priority - 1) + $claim->total_value * $insurer->value_cost_multiplier;

        return $cost;
    }
}
