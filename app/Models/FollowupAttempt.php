<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowupAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'user_id',
        'follow_type',
        'followup_status',
        'attempted_at',
    ];

    protected $casts = [
        'enquiry_id' => 'integer',
        'user_id' => 'integer',
        'attempted_at' => 'datetime',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
