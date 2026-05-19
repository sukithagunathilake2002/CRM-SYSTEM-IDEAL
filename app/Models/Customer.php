<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'title',
        'name',
        'mobile_numbers',
        'district',
        'location',
        'state',
        'address1',
        'address2'
    ];

    protected $casts = [
        'mobile_numbers' => 'array'
    ];

    // One customer can have many enquiries
    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}
