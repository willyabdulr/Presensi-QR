<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::in(['dosen', 'mahasiswa'])],
            'status' => ['nullable', Rule::in(['pending', 'approved'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()
            ->whereIn('role', ['dosen', 'mahasiswa'])
            ->when(isset($filters['role']), fn ($query) => $query->where('role', $filters['role']))
            ->when(isset($filters['status']), function ($query) use ($filters): void {
                $query->where('is_approved', $filters['status'] === 'approved');
            })
            ->when(isset($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('nomor_induk', "%{$search}%");
                });
            })
            ->orderBy('is_approved')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'dosen' => User::query()->where('role', 'dosen')->count(),
            'mahasiswa' => User::query()->where('role', 'mahasiswa')->count(),
            'pending' => User::query()->whereIn('role', ['dosen', 'mahasiswa'])->where('is_approved', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'filters', 'counts'));
    }

    public function show(User $user): View
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        return view('admin.users.show', compact('user'));
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        if ($user->is_approved) {
            return back()->with('success', 'Akun ini sudah disetujui sebelumnya.');
        }

        $user->is_approved = true;
        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Akun '.ucfirst($user->role).' berhasil disetujui dan sekarang dapat login.');
    }
}
