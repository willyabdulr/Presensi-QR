<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $this->profileUser($request);

        return view('profile.show', compact('user'));
    }

    public function edit(Request $request): View
    {
        $user = $this->profileUser($request);

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->profileUser($request);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if (in_array($user->role, ['dosen', 'mahasiswa'], true)) {
            $rules['nomor_induk'] = [
                'required',
                'string',
                'max:32',
                Rule::unique('users', 'nomor_induk')->ignore($user->id),
            ];
        }

        $validated = $request->validate($rules);
        $uploadedPhoto = $request->file('profile_photo');
        $newPhotoPath = $uploadedPhoto?->store('profile-photos', 'public');

        if ($uploadedPhoto !== null && ! is_string($newPhotoPath)) {
            return back()->withErrors(['profile_photo' => 'Foto gagal disimpan. Silakan coba kembali.'])->withInput();
        }

        $oldPhotoPath = $user->profile_photo_path;

        try {
            DB::transaction(function () use ($user, $validated, $newPhotoPath): void {
                $emailChanged = $user->email !== $validated['email'];
                $user->name = $validated['name'];
                $user->email = $validated['email'];

                if ($emailChanged) {
                    $user->email_verified_at = null;
                }

                if (isset($validated['nomor_induk'])) {
                    $user->nomor_induk = $validated['nomor_induk'];
                }

                if (is_string($newPhotoPath)) {
                    $user->profile_photo_path = $newPhotoPath;
                }

                $user->save();
            });
        } catch (\Throwable $exception) {
            if (is_string($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if (is_string($newPhotoPath) && is_string($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route($user->role.'.profile')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    private function profileUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
