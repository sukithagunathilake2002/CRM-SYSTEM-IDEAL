<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesConsultantReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'pending_registration_count',
        'pending_followup_count',
        'pending_booking_count',
        'pending_delivery_count',
        'message',
        'read_at',
    ];

    protected $casts = [
        'sender_id' => 'integer',
        'recipient_id' => 'integer',
        'pending_registration_count' => 'integer',
        'pending_followup_count' => 'integer',
        'pending_booking_count' => 'integer',
        'pending_delivery_count' => 'integer',
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
