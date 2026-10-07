<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const ROLES = ['admin', 'dosen', 'mahasiswa'];

    public function showLoginForm(?string $role = null): View|RedirectResponse
    {
        abort_unless($role === null || in_array($role, self::ROLES, true), 404);

        foreach (self::ROLES as $authenticatedRole) {
            if (Auth::guard($authenticatedRole)->check()) {
                return redirect()->route($this->dashboardRoute($authenticatedRole));
            }
        }

        return view('auth.login', compact('role'));
    }

    public function login(Request $request, ?string $role = null): RedirectResponse
    {
        abort_unless($role === null || in_array($role, self::ROLES, true), 404);

        if ($role === null) {
            $credentials = $request->validate([
                'identifier' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ]);
            $inputField = 'identifier';
            $identity = $credentials[$inputField];
            $identityField = str_contains($identity, '@') ? 'email' : 'nomor_induk';
            $user = User::query()->where($identityField, $identity)->first();
            $role = $user?->role;

            if (! in_array($role, self::ROLES, true)) {
                return back()->withErrors([
                    $inputField => 'Email, NIM, NID/NIP atau password yang Anda masukkan salah.',
                ])->onlyInput($inputField);
            }
        } else {
            $identityField = $role === 'admin' ? 'email' : 'nomor_induk';
            $credentials = $request->validate([
                $identityField => $identityField === 'email' ? 'required|email' : 'required|string|max:32',
                'password' => 'required|string',
            ]);
            $inputField = $identityField;
            $identity = $credentials[$identityField];
            $user = null;
        }

        $authCredentials = [
            $identityField => $identity,
            'password' => $credentials['password'],
            'role' => $role,
            'is_approved' => true,
        ];

        if (Auth::guard($role)->attempt($authCredentials, $request->boolean('remember'))) {
            foreach (['web', ...self::ROLES] as $guard) {
                if ($guard !== $role) {
                    Auth::guard($guard)->logout();
                }
            }
            $request->session()->regenerate();

            return redirect()->intended(route($this->dashboardRoute($role)));
        }

        $pendingUser = $user ?? User::query()
            ->where($identityField, $identity)
            ->where('role', $role)
            ->where('is_approved', false)
            ->first();

        if ($pendingUser && ! $pendingUser->is_approved && Hash::check($credentials['password'], $pendingUser->password)) {
            return back()->withErrors([
                $inputField => 'Akun '.ucfirst($role).' Anda masih menunggu persetujuan admin.',
            ])->onlyInput($inputField);
        }

        return back()->withErrors([
            $inputField => match ($role) {
                'dosen' => 'NID/NIP atau password yang Anda masukkan salah.',
                'mahasiswa' => 'NIM atau password yang Anda masukkan salah.',
                default => 'Email, NIM, NID/NIP atau password yang Anda masukkan salah.',
            },
        ])->onlyInput($inputField);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        Auth::guard('dosen')->logout();
        Auth::guard('mahasiswa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    private function dashboardRoute(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'dosen' => 'dosen.dashboard',
            'mahasiswa' => 'mahasiswa.dashboard',
        };
    }
}
