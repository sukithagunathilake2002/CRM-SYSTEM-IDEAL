<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
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
        'corporate_name',
        'profession',
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
        'purchase_order_image',
        'insurance_copy_1_image',
        'insurance_copy_2_image',
        'pan_certificate_image',
        'tin_certificate_image',
        'company_registration_certificate_1_image',
        'company_registration_certificate_2_image',
        'share_certificate_copy_1_image',
        'share_certificate_copy_2_image',
        'citizenship_certificate_1_image',
        'citizenship_certificate_2_image',
        'extra_images',
        'payment_receipt_amount_booking',
        'payment_pre_delivery_amount',
        'payment_delivery_amount',
        'payment_finance_provider',
        'payment_pending_reason',
        'payment_pending_amount',
        'payment_agent_name',
        'payment_agent_number',
        'payment_expected_date',
        'payment_credit_given_to_customer',
        'payment_credit_amount_pending',
        'payment_credit_permitted_by',
        'payment_credit_expected_date',
    ];

    protected $casts = [
        'extra_images' => 'array',
        'quote_date' => 'date',
        'test_drive_date' => 'date',
        'existing_vehicle_year' => 'integer',
        'exchange_manufacture_year' => 'integer',
        'exchange_mileage_km' => 'integer',
        'exchange_expected_price' => 'decimal:2',
        'exchange_quoted_price' => 'decimal:2',
        'exchange_price_difference' => 'decimal:2',
        'exchange_extra_images' => 'array',
        'payment_receipt_amount_booking' => 'decimal:2',
        'payment_pre_delivery_amount' => 'decimal:2',
        'payment_delivery_amount' => 'decimal:2',
        'payment_pending_amount' => 'decimal:2',
        'payment_expected_date' => 'date',
        'payment_credit_amount_pending' => 'decimal:2',
        'payment_credit_expected_date' => 'date',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }
}
