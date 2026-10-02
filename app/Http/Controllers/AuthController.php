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

    public function login(Request $request, string $role): RedirectResponse
    {
        $identityField = $request->filled('identity')
            ? 'identity'
            : ($request->filled('email') ? 'email' : 'nomor_induk');
        $request->merge(['identity' => $request->input($identityField)]);
        $credentials = $request->validate([
            'identity' => 'required|string|max:255',
            'password' => 'required|string',
        ]);
        $user = User::query()
            ->where('email', $credentials['identity'])
            ->orWhere('nomor_induk', $credentials['identity'])
            ->first();

        if (! $user || ! in_array($user->role, self::ROLES, true) || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                $identityField => 'Email, NIM/NIDN, atau password yang Anda masukkan salah.',
            ])->onlyInput('identity');
        }

        if (! $user->is_approved) {
            return back()->withErrors([
                $identityField => 'Akun Anda belum dikonfirmasi oleh Admin. Silakan hubungi pengelola sistem.',
            ])->onlyInput('identity');
        }

        foreach (['web', ...self::ROLES] as $guard) {
            if ($guard !== $user->role) {
                Auth::guard($guard)->logout();
            }
        }
        Auth::guard($user->role)->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route($this->dashboardRoute($user->role));
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
