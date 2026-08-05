<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;

    protected $table = 'enquiries';

    public const TERMINAL_LEAD_RESULTS = ['lost', 'closed'];

    protected $fillable = [
        'user_id',
        'customer_id',
        'vehicle_id',
        'selected_vehicle_models',
        'lead_source',
        'source_of_information',
        'follow_type',
        'follow_date',
        'follow_time',
        'followup_status',
        'followup_marked_at',
        'followup_attempted_type',
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
        'followup_not_done_reason',
        'followup_not_done_reason_other',
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
        'selected_vehicle_models' => 'array',
    ];

    // Enable timestamps
    public $timestamps = true;

    public function scopeRegisteredLead($query)
    {
        return $query->nonTerminalLead()->whereHas('prospectSheet', function ($query): void {
            $query->where('current_step', '>=', 5)
                ->whereRaw("LOWER(COALESCE(lead_status, '')) IN ('hot', 'warm', 'cold')");
        });
    }

    public function scopePendingRegistration($query)
    {
        return $query->nonTerminalLead()->whereDoesntHave('prospectSheet', function ($query): void {
            $query->where('current_step', '>=', 5)
                ->whereRaw("LOWER(COALESCE(lead_status, '')) IN ('hot', 'warm', 'cold')");
        });
    }

    public function scopeNonTerminalLead($query)
    {
        return $query
            ->whereRaw("LOWER(COALESCE(followup_result, '')) NOT IN ('lost', 'closed')")
            ->whereRaw("LOWER(COALESCE(status, 'open')) NOT IN ('closed', 'cancelled', 'canceled', 'lost')");
    }

    public function scopeActiveInquiryStage($query)
    {
        return $query->nonTerminalLead();
    }

    public function scopeActiveBookingStage($query)
    {
        return $query
            ->registeredLead()
            ->has('booking')
            ->doesntHave('delivery');
    }

    public function scopeInactiveBookingStage($query)
    {
        return $query
            ->registeredLead()
            ->doesntHave('booking')
            ->doesntHave('delivery');
    }

    public function scopeActiveDeliveryStage($query)
    {
        return $query
            ->nonTerminalLead()
            ->whereHas('booking', function ($query): void {
                $query->whereNotNull('booking_completed_at');
            })
            ->whereHas('delivery');
    }

    public function hasCompletedProspectSheet(): bool
    {
        $prospect = $this->relationLoaded('prospectSheet')
            ? $this->getRelation('prospectSheet')
            : $this->prospectSheet;

        if (!$prospect) {
            return false;
        }

        $leadStatus = strtolower(trim((string) $prospect->lead_status));

        return (int) ($prospect->current_step ?? 0) >= 5
            && in_array($leadStatus, ['hot', 'warm', 'cold'], true);
    }

    public function canOpenBooking(): bool
    {
        return !$this->isTerminalLead() && $this->hasCompletedProspectSheet();
    }

    public function canOpenDelivery(): bool
    {
        return $this->canOpenBooking() && $this->hasCompletedBooking();
    }

    public function hasCompletedBooking(): bool
    {
        $booking = $this->relationLoaded('booking')
            ? $this->getRelation('booking')
            : $this->booking;

        return $booking !== null && $booking->booking_completed_at !== null;
    }

    public function terminalLeadResult(): ?string
    {
        $followupResult = strtolower(trim((string) $this->followup_result));
        if (in_array($followupResult, self::TERMINAL_LEAD_RESULTS, true)) {
            return $followupResult;
        }

        $status = strtolower(trim((string) $this->status));
        if (in_array($status, self::TERMINAL_LEAD_RESULTS, true)) {
            return $status;
        }

        return null;
    }

    public function isTerminalLead(): bool
    {
        return $this->terminalLeadResult() !== null;
    }

    public function terminalLeadLabel(): string
    {
        return ucfirst($this->terminalLeadResult() ?? 'terminal');
    }

    public function terminalLeadRouteParameters(): array
    {
        $result = $this->terminalLeadResult();

        return $result ? ['lead_result' => $result] : [];
    }

    public function selectedVehicleItems(): array
    {
        $items = is_array($this->selected_vehicle_models) ? $this->selected_vehicle_models : [];
        $items = array_values(array_filter($items, fn($item): bool => is_array($item)));

        if (!empty($items)) {
            return $items;
        }

        $vehicle = $this->vehicle;
        if (!$vehicle) {
            return [];
        }

        return [[
            'vehicle_id' => (int) $vehicle->id,
            'model' => $vehicle->model,
            'engine_type' => $vehicle->engine_type,
            'variant' => $vehicle->variant,
            'label' => trim((string) $vehicle->model . ' ' . (string) $vehicle->engine_type . ' ' . (string) $vehicle->variant),
        ]];
    }

    public function selectedVehicleDisplay(): string
    {
        return collect($this->selectedVehicleItems())
            ->map(fn(array $item): string => trim((string) ($item['label'] ?? '')))
            ->filter()
            ->implode(', ');
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

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function followupAttempts()
    {
        return $this->hasMany(FollowupAttempt::class);
    }
}