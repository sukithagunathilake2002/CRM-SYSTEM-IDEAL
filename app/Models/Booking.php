<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'enquiry_id',
        'booking_same_as_customer',
        'title',
        'name',
        'contact_type',
        'mobile_numbers',
        'district',
        'location',
        'state',
        'address1',
        'address2',
        'customer_type',
        'profession',
        'date_of_birth',
        'interested_model',
        'interested_engine',
        'interested_variant',
        'interested_vehicle_color',
        'quote_taken',
        'quote_date',
        'test_drive_given',
        'test_drive_date',
        'test_drive_vehicle_model',
        'test_drive_to_whom',
        'test_drive_not_given_reason',
        'purchase_mode',
        'finance_form',
        'interested_in_competition',
        'competition_brand',
        'competition_model',
        'first_time_buyer',
        'existing_vehicle_brand',
        'existing_vehicle_model',
        'existing_vehicle_year',
        'interested_in_exchange',
        'exchange_type',
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
        'purchase_order_image',
    ];

    protected $casts = [
        'booking_same_as_customer' => 'boolean',
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
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }
}
