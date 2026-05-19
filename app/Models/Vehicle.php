<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'model',
        'engine_type',
        'variant',
        'unit_price',
        'vat_amount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public $timestamps = false; // master data

    // One vehicle can have many enquiries
    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}
