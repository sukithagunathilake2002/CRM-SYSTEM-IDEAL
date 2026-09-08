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
    private const LOGIN_CAPTCHA_ANSWER_KEY = 'login_captcha_answer';

    public function showCommonLoginForm(Request $request): View
    {
        return view('auth.login-common', [
            'captchaImage' => $this->generateLoginCaptcha($request),
        ]);
    }

    public function loginCommon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'captcha_answer' => ['required', 'string', 'max:10'],
        ], [], [
            'captcha_answer' => 'CAPTCHA',
        ]);

        if (!$this->isValidLoginCaptcha($request, $validated['captcha_answer'])) {
            return $this->invalidLoginCaptchaResponse($request);
        }

        $identifier = trim((string) $validated['email']);
        $matchedUsers = User::query()
            ->where('email', $identifier)
            ->orWhere('name', $identifier)
            ->limit(2)
            ->get(['id', 'email']);

        if ($matchedUsers->count() !== 1) {
            $this->generateLoginCaptcha($request);

            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->withInput($request->only('email', 'remember'));
        }

        $matchedUser = $matchedUsers->first();

        $attemptData = [
            'email' => $matchedUser->email,
            'password' => $validated['password'],
        ];

        $remember = (bool) ($validated['remember'] ?? false);

        if (!Auth::attempt($attemptData, $remember)) {
            $this->generateLoginCaptcha($request);

            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->withInput($request->only('email', 'remember'));
        }

        $this->clearLoginCaptcha($request);
        $request->session()->regenerate();

        return redirect()->route('dashboard.home');
    }

    public function roles(): View
    {
        return view('auth.roles', [
            'roles' => User::ROLE_HIERARCHY,
            'labels' => User::ROLE_LABELS,
            'slugs' => User::ROLE_SLUGS,
        ]);
    }

    public function showLoginForm(Request $request, string $roleSlug): View
    {
        $role = $this->resolveRoleFromSlug($roleSlug);

        return view('auth.login', [
            'role' => $role,
            'roleSlug' => $roleSlug,
            'roleLabel' => User::ROLE_LABELS[$role],
            'captchaImage' => $this->generateLoginCaptcha($request),
        ]);
    }

    public function login(Request $request, string $roleSlug): RedirectResponse
    {
        $role = $this->resolveRoleFromSlug($roleSlug);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'captcha_answer' => ['required', 'string', 'max:10'],
        ], [], [
            'captcha_answer' => 'CAPTCHA',
        ]);

        if (!$this->isValidLoginCaptcha($request, $credentials['captcha_answer'])) {
            return $this->invalidLoginCaptchaResponse($request);
        }

        $attemptData = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => $role,
        ];

        $remember = (bool) ($credentials['remember'] ?? false);

        if (!Auth::attempt($attemptData, $remember)) {
            $this->generateLoginCaptcha($request);

            return back()
                ->withErrors(['email' => 'Invalid credentials for this role.'])
                ->withInput($request->only('email', 'remember'));
        }

        $this->clearLoginCaptcha($request);
        $request->session()->regenerate();

        return redirect()->route('dashboard.home');
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
            'employee_number' => ['required', 'regex:/^M\d{5}$/', Rule::unique('users', 'employee_number')],
            'phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
        $messages = [
            'phone.regex' => 'Phone number must start with 0 and contain exactly 10 digits.',
            'employee_number.regex' => 'Employee number must start with M followed by exactly 5 digits.',
        ];

        if ($parentRole) {
            $rules['manager_id'] = ['required', 'integer', Rule::exists('users', 'id')];
        } else {
            $rules['manager_id'] = ['nullable', 'integer'];
        }

        $validated = $request->validate($rules, $messages);

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

        return redirect()->route('dashboard.home');
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

    private function generateLoginCaptcha(Request $request): string
    {
        $code = $this->randomLoginCaptchaCode();
        $svg = $this->buildLoginCaptchaSvg($code);

        $request->session()->put(self::LOGIN_CAPTCHA_ANSWER_KEY, $code);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function isValidLoginCaptcha(Request $request, string $answer): bool
    {
        $expected = $request->session()->get(self::LOGIN_CAPTCHA_ANSWER_KEY);

        if (!is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, strtoupper(trim($answer)));
    }

    private function invalidLoginCaptchaResponse(Request $request): RedirectResponse
    {
        $this->generateLoginCaptcha($request);

        return back()
            ->withErrors(['captcha_answer' => 'The CAPTCHA answer is incorrect. Please try again.'])
            ->withInput($request->only('email', 'remember'));
    }

    private function clearLoginCaptcha(Request $request): void
    {
        $request->session()->forget(self::LOGIN_CAPTCHA_ANSWER_KEY);
    }

    private function randomLoginCaptchaCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    private function buildLoginCaptchaSvg(string $code): string
    {
        $lines = '';

        for ($i = 0; $i < 32; $i++) {
            $stroke = $i % 2 === 0 ? '#111111' : '#4b5563';
            $opacity = random_int(20, 58) / 100;
            $width = random_int(1, 2);
            $x1 = random_int(-20, 170);
            $y1 = random_int(0, 48);
            $x2 = random_int(-20, 170);
            $y2 = random_int(0, 48);

            $lines .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $stroke . '" stroke-width="' . $width . '" opacity="' . $opacity . '"/>';
        }

        $letters = '';
        foreach (str_split($code) as $index => $character) {
            $x = 18 + ($index * 25) + random_int(-3, 3);
            $y = 33 + random_int(-4, 4);
            $rotation = random_int(-16, 16);
            $scaleY = random_int(88, 112) / 100;

            $letters .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ') scale(1 ' . $scaleY . ')" fill="#050505">' . e($character) . '</text>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="152" height="48" viewBox="0 0 152 48">'
            . '<rect width="152" height="48" fill="#f7f7f7"/>'
            . '<g stroke-linecap="round">' . $lines . '</g>'
            . '<g font-family="Arial Black, Impact, Arial, sans-serif" font-size="33" font-weight="900" letter-spacing="0">' . $letters . '</g>'
            . '<g stroke="#111111" stroke-width="1" opacity=".72">'
            . '<path d="M0 7 H152 M0 16 H152 M0 25 H152 M0 34 H152 M0 43 H152"/>'
            . '<path d="M8 0 V48 M24 0 V48 M40 0 V48 M56 0 V48 M72 0 V48 M88 0 V48 M104 0 V48 M120 0 V48 M136 0 V48"/>'
            . '</g>'
            . '</svg>';
    }
}
