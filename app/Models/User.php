<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_HEAD_OF_SALES = 'head_of_sales';
    public const ROLE_REGIONAL_MANAGER = 'regional_manager';
    public const ROLE_AREA_MANAGER = 'area_manager';
    public const ROLE_SALES_CONSULTANT = 'sales_consultant';

    public const ROLE_HIERARCHY = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_HEAD_OF_SALES,
        self::ROLE_REGIONAL_MANAGER,
        self::ROLE_AREA_MANAGER,
        self::ROLE_SALES_CONSULTANT,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_HEAD_OF_SALES => 'Head Of Sales',
        self::ROLE_REGIONAL_MANAGER => 'Regional Manager',
        self::ROLE_AREA_MANAGER => 'Area Manager',
        self::ROLE_SALES_CONSULTANT => 'Sales Consultant',
    ];

    public const ROLE_SLUGS = [
        self::ROLE_SUPER_ADMIN => 'super-admin',
        self::ROLE_HEAD_OF_SALES => 'head-of-sales',
        self::ROLE_REGIONAL_MANAGER => 'regional-manager',
        self::ROLE_AREA_MANAGER => 'area-manager',
        self::ROLE_SALES_CONSULTANT => 'sales-consultant',
    ];

    public const DISTRICT_OPTIONS = [
        'Ampara',
        'Anuradhapura',
        'Badulla',
        'Batticaloa',
        'Colombo',
        'Galle',
        'Gampaha',
        'Hambantota',
        'Jaffna',
        'Kalutara',
        'Kandy',
        'Kegalle',
        'Kilinochchi',
        'Kurunegala',
        'Mannar',
        'Matale',
        'Matara',
        'Monaragala',
        'Mullaitivu',
        'Nuwara Eliya',
        'Polonnaruwa',
        'Puttalam',
        'Ratnapura',
        'Trincomalee',
        'Vavuniya',
    ];

    public const PROVINCE_DISTRICT_MAP = [
        'Western' => ['Colombo', 'Gampaha', 'Kalutara'],
        'Central' => ['Kandy', 'Matale', 'Nuwara Eliya'],
        'Southern' => ['Galle', 'Matara', 'Hambantota'],
        'Northern' => ['Jaffna', 'Kilinochchi', 'Mannar', 'Mullaitivu', 'Vavuniya'],
        'Eastern' => ['Trincomalee', 'Batticaloa', 'Ampara'],
        'North Western' => ['Kurunegala', 'Puttalam'],
        'North Central' => ['Anuradhapura', 'Polonnaruwa'],
        'Uva' => ['Badulla', 'Monaragala'],
        'Sabaragamuwa' => ['Ratnapura', 'Kegalle'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'manager_id',
        'permitted_districts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'manager_id' => 'integer',
            'permitted_districts' => 'array',
        ];
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst(str_replace('_', ' ', (string) $this->role));
    }

    public static function roleFromSlug(string $slug): ?string
    {
        return array_search($slug, self::ROLE_SLUGS, true) ?: null;
    }

    public static function slugFromRole(string $role): ?string
    {
        return self::ROLE_SLUGS[$role] ?? null;
    }

    public static function parentRoleFor(string $role): ?string
    {
        return match ($role) {
            self::ROLE_REGIONAL_MANAGER => self::ROLE_HEAD_OF_SALES,
            self::ROLE_AREA_MANAGER => self::ROLE_REGIONAL_MANAGER,
            self::ROLE_SALES_CONSULTANT => self::ROLE_AREA_MANAGER,
            default => null,
        };
    }

    public static function normalizeDistrictName(?string $district): ?string
    {
        $raw = trim((string) $district);
        if ($raw === '') {
            return null;
        }

        $key = preg_replace('/[^a-z]/', '', strtolower($raw));
        if ($key === '') {
            return null;
        }

        static $lookup = null;
        if (!is_array($lookup)) {
            $lookup = [];
            foreach (self::DISTRICT_OPTIONS as $option) {
                $normalizedKey = preg_replace('/[^a-z]/', '', strtolower($option));
                if ($normalizedKey !== '') {
                    $lookup[$normalizedKey] = $option;
                }
            }

            // Common spelling aliases
            $lookup['moneragala'] = 'Monaragala';
            $lookup['mullativu'] = 'Mullaitivu';
            $lookup['kilinochi'] = 'Kilinochchi';
        }

        return $lookup[$key] ?? null;
    }

    public static function provinceForDistrict(?string $district): ?string
    {
        $normalizedDistrict = self::normalizeDistrictName($district);
        if ($normalizedDistrict === null) {
            return null;
        }

        foreach (self::PROVINCE_DISTRICT_MAP as $province => $districts) {
            if (in_array($normalizedDistrict, $districts, true)) {
                return $province;
            }
        }

        return null;
    }

    public function resolvePermittedDistricts(): array
    {
        if (in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_HEAD_OF_SALES], true)) {
            return self::DISTRICT_OPTIONS;
        }

        $ownDistricts = collect($this->permitted_districts ?? [])
            ->map(fn($district): ?string => self::normalizeDistrictName((string) $district))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $managerDistricts = self::DISTRICT_OPTIONS;
        if ($this->manager_id !== null) {
            $this->loadMissing('manager.manager.manager');
            if ($this->manager instanceof self) {
                $managerDistricts = $this->manager->resolvePermittedDistricts();
            }
        }

        if (empty($ownDistricts)) {
            return $managerDistricts;
        }

        $allowedLookup = array_fill_keys($managerDistricts, true);
        $resolved = array_values(array_filter(
            $ownDistricts,
            fn(string $district): bool => isset($allowedLookup[$district])
        ));

        return !empty($resolved) ? $resolved : $managerDistricts;
    }
}
