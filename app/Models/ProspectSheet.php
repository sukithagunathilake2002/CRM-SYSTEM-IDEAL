<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspectSheet extends Model
{
    use HasFactory;

    protected $table = 'prospect_sheets';

    protected $fillable = [
        'enquiry_id',
        'customer_type',
        'corporate_name',
        'profession',
        'date_of_birth',
        'interested_vehicle_color',
        'source_of_information',
        'quote_taken',
        'quote_date',
        'test_drive_given',
        'test_drive_date',
        'test_drive_vehicle_model',
        'test_drive_to_whom',
        'test_drive_not_given_reason',
        'purchase_mode',
        'interested_in_exchange',
        'exchange_vehicle_brand',
        'exchange_vehicle_model',
        'exchange_manufacture_year',
        'exchange_color',
        'exchange_mileage_km',
        'exchange_registration_no',
        'exchange_expected_price',
        'exchange_quoted_price',
        'exchange_price_difference',
        'blue_book_image',
        'lot_no_image',
        'car_pic_1_image',
        'car_pic_2_image',
        'exchange_extra_images',
        'offer_unit_price',
        'offer_unit_price_discount',
        'offer_unit_price_free',
        'offer_vat_amount',
        'offer_vat_discount',
        'offer_vat_free',
        'offer_total_cost',
        'offer_total_discount',
        'offer_final_price',
        'interested_in_competition',
        'competition_brand',
        'competition_model',
        'first_time_buyer',
        'existing_vehicle_brand',
        'existing_vehicle_model',
        'existing_vehicle_year',
        'current_step',
        'reschedule_followup',
        'lead_status',
        'customer_remark',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    protected $casts = [
        'exchange_extra_images' => 'array',
        'offer_unit_price' => 'decimal:2',
        'offer_unit_price_discount' => 'decimal:2',
        'offer_unit_price_free' => 'boolean',
        'offer_vat_amount' => 'decimal:2',
        'offer_vat_discount' => 'decimal:2',
        'offer_vat_free' => 'boolean',
        'offer_total_cost' => 'decimal:2',
        'offer_total_discount' => 'decimal:2',
        'offer_final_price' => 'decimal:2',
        'reschedule_followup' => 'boolean',
    ];
}

