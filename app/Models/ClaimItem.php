<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimItem extends Model
{
    use HasFactory;

    // Guarded attributes (none, all are mass assignable)
    protected $guarded = [];

    // Cast attributes to appropriate types
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2', // Price per unit of the item
            'subtotal' => 'decimal:2', // Total price for this item (unit_price * quantity)
        ];
    }

    /**
     * Relationship: A claim item belongs to a claim.
     * Each item is part of a larger claim submission.
     */
    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
