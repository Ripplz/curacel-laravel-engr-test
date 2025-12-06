<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    use HasFactory;

    protected $table = 'insurers';

    // Guarded attributes (none, all are mass assignable)
    protected $guarded = [];

    // Cast attributes to appropriate types
    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2', // Base processing cost per batch
            'time_cost_min' => 'decimal:4', // Minimum time-based cost percentage (e.g., 0.2 for 20%)
            'time_cost_max' => 'decimal:4', // Maximum time-based cost percentage (e.g., 0.5 for 50%)
            'specialty_efficiency' => 'array', // JSON array of specialty efficiencies (e.g., {"cardiology": 0.8})
            'priority_cost_multiplier' => 'decimal:4', // Multiplier for claim priority (higher priority costs more)
            'value_cost_multiplier' => 'decimal:4', // Multiplier for claim monetary value
        ];
    }

    /**
     * Relationship: An insurer has many claims.
     * Claims are submitted to this insurer for processing.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    /**
     * Relationship: An insurer has many batches.
     * Batches are groups of claims processed by this insurer.
     */
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
