<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    // Guarded attributes (none, all are mass assignable)
    protected $guarded = [];

    // Cast attributes to appropriate types
    protected function casts(): array
    {
        return [
            'date' => 'date', // Date of the batch (used for processing)
            'total_cost' => 'decimal:2', // Total processing cost for the batch
        ];
    }

    /**
     * Relationship: A batch belongs to an insurer.
     * The insurer processes this batch of claims.
     */
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    /**
     * Relationship: A batch has many claims.
     * Claims are grouped into batches for efficient processing.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
