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

        // 1. Ambil nilai dari field name="identity" di login.blade.php
        $identity = $request->input('identity') 
            ?? $request->input('identifier') 
            ?? $request->input('email') 
            ?? $request->input('nomor_induk');

        if (!$identity) {
            return back()->withErrors([
                'identity' => 'Email, NIM, NID/NIP wajib diisi.',
            ])->onlyInput('identity');
        }

        $password = $request->input('password');
        if (!$password) {
            return back()->withErrors([
                'password' => 'Password wajib diisi.',
            ])->onlyInput('identity');
        }

        // 2. Deteksi apakah input berupa email (@) atau nomor_induk
        $identityField = str_contains($identity, '@') ? 'email' : 'nomor_induk';

        // 3. Jika login umum (/login), cari role user dari DB
        if ($role === null) {
            $user = User::query()->where($identityField, $identity)->first();
            $role = $user?->role;

            if (!$role || !in_array($role, self::ROLES, true)) {
                return back()->withErrors([
                    'identity' => 'Email, NIM, NID/NIP atau password yang Anda masukkan salah.',
                ])->onlyInput('identity');
            }
        }

        // 4. Susun kredensial login
        $authCredentials = [
            $identityField => $identity,
            'password' => $password,
            'role' => $role,
            'is_approved' => true,
        ];

        // 5. Eksekusi Login
        if (Auth::guard($role)->attempt($authCredentials, $request->boolean('remember'))) {
            foreach (['web', ...self::ROLES] as $guard) {
                if ($guard !== $role) {
                    Auth::guard($guard)->logout();
                }
            }
            $request->session()->regenerate();

            return redirect()->intended(route($this->dashboardRoute($role)));
        }

        // 6. Cek jika akun masih pending approval
        $pendingUser = User::query()
            ->where($identityField, $identity)
            ->where('role', $role)
            ->where('is_approved', false)
            ->first();

        if ($pendingUser && Hash::check($password, $pendingUser->password)) {
            return back()->withErrors([
                'identity' => 'Akun ' . ucfirst($role) . ' Anda masih menunggu persetujuan admin.',
            ])->onlyInput('identity');
        }

        // 7. Respon jika kredensial/password salah
        return back()->withErrors([
            'identity' => match ($role) {
                'dosen' => 'NID/NIP atau password yang Anda masukkan salah.',
                'mahasiswa' => 'NIM atau password yang Anda masukkan salah.',
                default => 'Email, NIM, NID/NIP atau password yang Anda masukkan salah.',
            },
        ])->onlyInput('identity');
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
