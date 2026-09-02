<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'model',
        'engine_type',
        'variant',
        'colors',
        'unit_price',
        'vat_amount',
    ];

    protected $casts = [
        'colors' => 'array',
        'unit_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public $timestamps = false; // master data

    // One vehicle can have many enquiries
    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }

    public function permittedHeadsOfSales()
    {
        return $this->belongsToMany(User::class, 'head_of_sales_vehicle', 'vehicle_id', 'head_of_sales_id');
    }

    public static function visibleTo(?User $user): Builder
    {
        $query = static::query();

        if (!$user || $user->role === User::ROLE_SUPER_ADMIN) {
            return $query;
        }

        $headOfSales = $user->headOfSalesForVehiclePermissions();
        if (!$headOfSales) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('permittedHeadsOfSales', function (Builder $headsQuery) use ($headOfSales): void {
            $headsQuery->where('users.id', $headOfSales->id);
        });
    }

    public function colorOptions(?string $selectedColor = null): array
    {
        $colors = collect(is_array($this->colors) ? $this->colors : [])
            ->map(fn($color): string => trim((string) $color))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedColor = trim((string) $selectedColor);
        if ($selectedColor !== '' && !in_array($selectedColor, $colors, true)) {
            $colors[] = $selectedColor;
        }

        return $colors;
    }
}
