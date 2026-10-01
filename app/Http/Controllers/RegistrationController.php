<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegistrationUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    private const ROLES = ['dosen', 'mahasiswa'];

    public function create(?string $role = null): View|RedirectResponse
    {
        abort_unless($role === null || in_array($role, self::ROLES, true), 404);

        foreach (['admin', 'dosen', 'mahasiswa'] as $authenticatedRole) {
            if (Auth::guard($authenticatedRole)->check()) {
                $route = match ($authenticatedRole) {
                    'admin' => 'admin.dashboard',
                    'dosen' => 'dosen.dashboard',
                    default => 'mahasiswa.dashboard',
                };

                return redirect()->route($route);
            }
        }

        return view('auth.register', compact('role'));
    }

    public function store(Request $request, string $role): RedirectResponse
    {
        $validated = $this->validateRegistration($request, $role);
        $this->createUser($validated, $role);

        return redirect()->route('login.role', $role)
            ->with('success', 'Pendaftaran berhasil. Akun '.ucfirst($role).' menunggu persetujuan admin.');
    }

    public function storeApi(Request $request, string $role): JsonResponse
    {
        $validated = $this->validateRegistration($request, $role);
        $user = $this->createUser($validated, $role);

        return RegistrationUserResource::make($user)
            ->additional(['message' => 'Pendaftaran berhasil dan menunggu persetujuan admin.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @return array{name: string, email: string, nomor_induk: string, password: string}
     */
    private function validateRegistration(Request $request, string $role): array
    {
        abort_unless(in_array($role, self::ROLES, true), 404);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'nomor_induk' => ['required', 'string', 'max:32', Rule::unique('users', 'nomor_induk')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * @param  array{name: string, email: string, nomor_induk: string, password: string}  $validated
     */
    private function createUser(array $validated, string $role): User
    {
        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => $validated['nomor_induk'],
            'password' => $validated['password'],
            'role' => $role,
        ]);

        $user->is_approved = false;
        $user->save();

        return $user;
    }
}
