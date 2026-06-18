<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;

    protected $table = 'enquiries';

    protected $fillable = [
        'user_id',
        'customer_id',
        'vehicle_id',
        'lead_source',
        'source_of_information',
        'follow_type',
        'follow_date',
        'follow_time',
        'followup_status',
        'followup_marked_at',
        'followup_visit_date',
        'followup_met_whom',
        'followup_picture_1',
        'followup_picture_2',
        'followup_result',
        'followup_customer_comment',
        'followup_conversion_year',
        'followup_conversion_month',
        'followup_test_drive_given',
        'followup_test_drive_not_given_reason',
        'followup_test_drive_when',
        'followup_test_drive_vehicle_used',
        'followup_test_drive_to_whom',
        'followup_first_time_buyer',
        'followup_first_time_buyer_reason',
        'followup_lead_temperature',
        'followup_next_type',
        'followup_next_date',
        'followup_next_time',
        'followup_lost_to',
        'followup_lost_competition_brand',
        'followup_lost_competition_model',
        'followup_lost_codealer_name',
        'followup_lost_reject_reasons',
        'followup_lost_reject_other_text',
        'exchange',
        'finance',
        'status'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'followup_marked_at' => 'datetime',
        'followup_visit_date' => 'date',
        'followup_conversion_year' => 'integer',
        'followup_conversion_month' => 'integer',
        'followup_test_drive_when' => 'date',
        'followup_next_date' => 'date',
        'followup_lost_reject_reasons' => 'array',
    ];

    // Enable timestamps
    public $timestamps = true;

    public function scopeRegisteredLead($query)
    {
        return $query->whereHas('prospectSheet', function ($query): void {
            $query->where('current_step', '>=', 5)
                ->whereRaw("LOWER(COALESCE(lead_status, '')) IN ('hot', 'warm', 'cold')");
        });
    }

    public function scopePendingRegistration($query)
    {
        return $query->whereDoesntHave('prospectSheet', function ($query): void {
            $query->where('current_step', '>=', 5)
                ->whereRaw("LOWER(COALESCE(lead_status, '')) IN ('hot', 'warm', 'cold')");
        });
    }

    // Each enquiry belongs to one customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each enquiry belongs to one vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function prospectSheet()
    {
        return $this->hasOne(ProspectSheet::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }
}
