<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $table = 'claims';

    // Guarded attributes (none, all are mass assignable)
    protected $guarded = [];

    // Cast attributes to appropriate types
    protected function casts(): array
    {
        return [
            'encounter_date' => 'date', // Date when the medical encounter occurred
            'submission_date' => 'date', // Date when the claim was submitted
            'total_value' => 'decimal:2', // Total monetary value of the claim
        ];
    }

    /**
     * Relationship: A claim belongs to an insurer.
     * This links the claim to the insurance company processing it.
     */
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    /**
     * Relationship: A claim belongs to a batch.
     * Batches group claims for processing to minimize costs.
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Relationship: A claim has many items.
     * Items represent individual services or products in the claim.
     */
    public function items()
    {
        return $this->hasMany(ClaimItem::class);
    }
}
