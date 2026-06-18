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

        if ($parentRole) {
            $managerOptions = User::query()
                ->where('role', $parentRole)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'manager_id']);
        }

        return view('auth.register', [
            'role' => $role,
            'roleSlug' => $roleSlug,
            'roleLabel' => User::ROLE_LABELS[$role],
            'parentRole' => $parentRole,
            'parentRoleLabel' => $parentRole ? User::ROLE_LABELS[$parentRole] : null,
            'managerOptions' => $managerOptions,
        ]);
    }

    public function register(Request $request, string $roleSlug): RedirectResponse
    {
        $role = $this->resolveRoleFromSlug($roleSlug);
        $parentRole = User::parentRoleFor($role);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_number')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($parentRole) {
            $rules['manager_id'] = ['required', 'integer', Rule::exists('users', 'id')];
        } else {
            $rules['manager_id'] = ['nullable', 'integer'];
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

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_number' => $validated['employee_number'],
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'manager_id' => $managerId,
            'password' => $validated['password'],
            'permitted_districts' => null,
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
