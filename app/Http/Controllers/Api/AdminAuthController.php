<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $registrationKey = config('auth.admin_registration_key');

        abort_if(! is_string($registrationKey) || $registrationKey === '', 503, 'Registrasi admin belum dikonfigurasi.');
        abort_unless(hash_equals($registrationKey, (string) $request->header('X-Admin-Registration-Key')), 403, 'Kunci registrasi admin tidak valid.');

        if (User::query()->where('role', 'admin')->exists()) {
            return response()->json([
                'message' => 'Admin pertama sudah terdaftar. Gunakan akun admin yang ada atau perintah CLI untuk membuat admin tambahan.',
            ], 409);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'nomor_induk' => ['nullable', 'string', 'max:32', Rule::unique('users', 'nomor_induk')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => $validated['nomor_induk'] ?? null,
            'password' => $validated['password'],
            'role' => 'admin',
        ]);
        $user->is_approved = true;
        $user->save();

        return response()->json([
            'message' => 'Admin pertama berhasil didaftarkan. Silakan login untuk mendapatkan token API.',
            'data' => RegistrationUserResource::make($user)->resolve($request),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->where('is_approved', true)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password admin tidak valid.'], 401);
        }

        $expiresAt = now()->addHours(12);
        $accessToken = $user->createToken(
            $validated['device_name'] ?? 'postman-admin',
            ['admin:access'],
            $expiresAt,
        );

        return response()->json([
            'message' => 'Login admin berhasil.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $accessToken->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'user' => RegistrationUserResource::make($user)->resolve($request),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json([
            'data' => RegistrationUserResource::make($user)->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Token admin berhasil dicabut.']);
    }
}
