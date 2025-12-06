<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Insurer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClaimSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_submission_creates_claim_and_batches()
    {
        // Seed insurers
        $this->seed(\Database\Seeders\InsurerSeeder::class);

        $insurer = Insurer::first();

        $data = [
            'insurer_code' => $insurer->code,
            'provider_name' => 'Provider A',
            'encounter_date' => '2023-10-01',
            'specialty' => 'cardiology',
            'priority' => 3,
            'items' => [
                [
                    'name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                ],
                [
                    'name' => 'Test',
                    'unit_price' => 50.00,
                    'quantity' => 2,
                ],
            ],
        ];

        Mail::shouldReceive('to->send')->once();

        $response = $this->postJson('/api/claims', $data);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Claim submitted successfully']);

        $this->assertDatabaseHas('claims', [
            'insurer_id' => $insurer->id,
            'provider_name' => 'Provider A',
            'encounter_date' => '2023-10-01 00:00:00',
            'specialty' => 'cardiology',
            'priority' => 3,
            'total_value' => 200,
        ]);

        $this->assertDatabaseHas('claim_items', [
            'name' => 'Consultation',
            'unit_price' => 100.00,
            'quantity' => 1,
            'subtotal' => 100.00,
        ]);

        $this->assertDatabaseHas('batches', [
            'provider_name' => 'Provider A',
            'date' => '2023-10-01 00:00:00',
            'insurer_id' => $insurer->id,
        ]);

        $claim = Claim::first();
        $this->assertNotNull($claim->batch_id);
    }

    public function test_invalid_data_returns_validation_errors()
    {
        $data = [
            'insurer_code' => 'INVALID',
            'provider_name' => '',
        ];

        $response = $this->postJson('/api/claims', $data);

        $response->assertStatus(422);
    }
}
