<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showCommonLoginForm(): View
    {
        return view('auth.login-common', [
            'roles' => User::ROLE_HIERARCHY,
            'labels' => User::ROLE_LABELS,
            'slugs' => User::ROLE_SLUGS,
        ]);
    }

    public function loginCommon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_values(User::ROLE_SLUGS))],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $role = $this->resolveRoleFromSlug($validated['role']);

        $attemptData = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
        ];

        $remember = (bool) ($validated['remember'] ?? false);

        if (!Auth::attempt($attemptData, $remember)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials for this user type.'])
                ->withInput($request->only('role', 'email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard.main');
    }

    public function roles(): View
    {
        return view('auth.roles', [
            'roles' => User::ROLE_HIERARCHY,
            'labels' => User::ROLE_LABELS,
            'slugs' => User::ROLE_SLUGS,
        ]);
    }

    public function showLoginForm(string $roleSlug): View
    {
        $role = $this->resolveRoleFromSlug($roleSlug);

        return view('auth.login', [
            'role' => $role,
            'roleSlug' => $roleSlug,
            'roleLabel' => User::ROLE_LABELS[$role],
        ]);
    }

    public function login(Request $request, string $roleSlug): RedirectResponse
    {
        $role = $this->resolveRoleFromSlug($roleSlug);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $attemptData = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => $role,
        ];

        $remember = (bool) ($credentials['remember'] ?? false);

        if (!Auth::attempt($attemptData, $remember)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials for this role.'])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard.main');
    }

    public function showRegistrationForm(string $roleSlug): View
    {
        $role = $this->resolveRoleFromSlug($roleSlug);
        $parentRole = User::parentRoleFor($role);
        $managerOptions = collect();
        $managerPermittedDistrictMap = [];
        $supportsDistrictPermissions = in_array($role, [
            User::ROLE_REGIONAL_MANAGER,
            User::ROLE_AREA_MANAGER,
            User::ROLE_SALES_CONSULTANT,
        ], true);

        if ($parentRole) {
            $managerOptions = User::query()
                ->where('role', $parentRole)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'manager_id', 'permitted_districts']);

            if ($supportsDistrictPermissions) {
                $managerPermittedDistrictMap = $managerOptions
                    ->mapWithKeys(fn(User $manager): array => [(string) $manager->id => $manager->resolvePermittedDistricts()])
                    ->all();
            }
        }

        return view('auth.register', [
            'role' => $role,
            'roleSlug' => $roleSlug,
            'roleLabel' => User::ROLE_LABELS[$role],
            'parentRole' => $parentRole,
            'parentRoleLabel' => $parentRole ? User::ROLE_LABELS[$parentRole] : null,
            'managerOptions' => $managerOptions,
            'supportsDistrictPermissions' => $supportsDistrictPermissions,
            'districtOptions' => User::DISTRICT_OPTIONS,
            'provinceDistrictMap' => User::PROVINCE_DISTRICT_MAP,
            'managerPermittedDistrictMap' => $managerPermittedDistrictMap,
        ]);
    }

    public function register(Request $request, string $roleSlug): RedirectResponse
    {
        $role = $this->resolveRoleFromSlug($roleSlug);
        $parentRole = User::parentRoleFor($role);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
        $supportsDistrictPermissions = in_array($role, [
            User::ROLE_REGIONAL_MANAGER,
            User::ROLE_AREA_MANAGER,
            User::ROLE_SALES_CONSULTANT,
        ], true);

        if ($parentRole) {
            $rules['manager_id'] = ['required', 'integer', Rule::exists('users', 'id')];
        } else {
            $rules['manager_id'] = ['nullable', 'integer'];
        }

        if ($supportsDistrictPermissions) {
            $rules['permitted_districts'] = ['nullable', 'array'];
            $rules['permitted_districts.*'] = ['string', Rule::in(User::DISTRICT_OPTIONS)];
        }

        $validated = $request->validate($rules);

        $managerId = $validated['manager_id'] ?? null;

        if ($parentRole && $managerId) {
            $manager = User::query()->find($managerId);

            if (!$manager || $manager->role !== $parentRole) {
                return back()
                    ->withErrors(['manager_id' => 'Please select a valid ' . User::ROLE_LABELS[$parentRole] . '.'])
                    ->withInput();
            }
        } else {
            $managerId = null;
        }

        $managerPermittedDistricts = User::DISTRICT_OPTIONS;
        if ($managerId !== null) {
            $manager = User::query()->find((int) $managerId);
            if ($manager instanceof User) {
                $managerPermittedDistricts = $manager->resolvePermittedDistricts();
            }
        }

        $permittedDistricts = null;
        if ($supportsDistrictPermissions) {
            $selectedDistricts = collect($validated['permitted_districts'] ?? [])
                ->map(fn($district): ?string => User::normalizeDistrictName((string) $district))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (!empty($selectedDistricts)) {
                $allowedLookup = array_fill_keys($managerPermittedDistricts, true);
                $hasInvalid = collect($selectedDistricts)->contains(
                    fn(string $district): bool => !isset($allowedLookup[$district])
                );

                if ($hasInvalid) {
                    return back()
                        ->withErrors(['permitted_districts' => 'Selected districts must be within manager access.'])
                        ->withInput();
                }
            }

            $permittedDistricts = !empty($selectedDistricts) ? $selectedDistricts : null;
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'manager_id' => $managerId,
            'password' => $validated['password'],
            'permitted_districts' => $permittedDistricts,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard.main');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Logged out successfully.');
    }

    private function resolveRoleFromSlug(string $roleSlug): string
    {
        $role = User::roleFromSlug($roleSlug);

        abort_if(!$role, 404, 'Role not found.');

        return $role;
    }
}
