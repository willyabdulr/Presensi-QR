<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'role' => ['nullable', Rule::in(['dosen', 'mahasiswa'])],
            'status' => ['nullable', Rule::in(['pending', 'approved'])],
            'class_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = User::query()
            ->whereIn('role', ['dosen', 'mahasiswa'])
            ->when(($filters['role'] ?? null) === 'mahasiswa', fn ($query) => $query->with('kelas:id,kode_kelas'))
            ->when(isset($filters['role']), fn ($query) => $query->where('role', $filters['role']));

        $queryFilters = $filters;
        if (($filters['role'] ?? null) !== 'mahasiswa') {
            unset($queryFilters['class_id']);
        }

        $users = $this->applyFilters($query, $queryFilters)
            ->orderBy('is_approved')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $classes = ($filters['role'] ?? null) === 'mahasiswa'
            ? Kelas::query()->orderBy('kode_kelas')->get(['id', 'kode_kelas'])
            : collect();
        $exportFilters = array_intersect_key($filters, array_flip(['class_id', 'search', 'status']));

        $counts = [
            'dosen' => User::query()->where('role', 'dosen')->count(),
            'mahasiswa' => User::query()->where('role', 'mahasiswa')->count(),
            'pending' => User::query()->whereIn('role', ['dosen', 'mahasiswa'])->where('is_approved', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'filters', 'counts', 'classes', 'exportFilters'));
    }

    public function exportStudents(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')],
            'status' => ['nullable', Rule::in(['pending', 'approved'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $students = $this->applyFilters(
            User::query()->where('role', 'mahasiswa')->with('kelas:id,kode_kelas'),
            $filters
        )->orderBy('nomor_induk')->lazy(500);

        return response()->streamDownload(function () use ($students): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new RuntimeException('Tidak dapat membuat file CSV mahasiswa.');
            }

            fputcsv($stream, ['NIM', 'Nama', 'Email', 'Kelas', 'Status'], ',', '"', '\\', "\r\n");

            foreach ($students as $student) {
                fputcsv($stream, [
                    $this->safeCsvValue($student->nomor_induk),
                    $this->safeCsvValue($student->name),
                    $this->safeCsvValue($student->email),
                    $this->safeCsvValue($student->kelas?->kode_kelas),
                    $student->is_approved ? 'Disetujui' : 'Menunggu approval',
                ], ',', '"', '\\', "\r\n");
            }

            fclose($stream);
        }, 'data-mahasiswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(User $user): View
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        return view('admin.users.show', [
            'user' => $user->load('kelas'),
            'kelasList' => Kelas::query()->orderBy('kode_kelas')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_induk' => ['required', 'string', 'max:32', Rule::unique('users', 'nomor_induk')->ignore($user->id)],
        ];

        if ($user->role === 'mahasiswa') {
            $rules['kelas_id'] = ['nullable', 'integer', Rule::exists('kelas', 'id')];
        }

        $validated = $request->validate($rules);
        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }
        $user->fill($validated);
        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Data akun '.ucfirst($user->role).' berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        $role = $user->role;
        $profilePhotoPath = $user->profile_photo_path;

        DB::transaction(function () use ($user): void {
            $user->delete();
        });

        if (is_string($profilePhotoPath)) {
            Storage::disk('public')->delete($profilePhotoPath);
        }

        return redirect()->route('admin.users.index', ['role' => $role])
            ->with('success', 'Akun '.($role === 'dosen' ? 'dosen' : 'mahasiswa').' dan data terkait berhasil dihapus.');
    }

    public function updateClass(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'mahasiswa', 404);

        $validated = $request->validate([
            'kelas_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')],
        ]);

        $user->update(['kelas_id' => $validated['kelas_id'] ?? null]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Relasi kelas mahasiswa berhasil diperbarui.');
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['dosen', 'mahasiswa'], true), 404);

        if ($user->is_approved) {
            return back()->with('success', 'Akun ini sudah disetujui sebelumnya.');
        }

        DB::transaction(function () use ($user): void {
            $user->is_approved = true;
            $user->save();
            AdminActivity::record(
                'account_approved',
                'Akun '.ucfirst($user->role).' disetujui: '.$user->name
            );
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Akun '.ucfirst($user->role).' berhasil disetujui dan sekarang dapat login.');
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function applyFilters(Builder $users, array $filters): Builder
    {
        return $users
            ->when(isset($filters['class_id']), fn (Builder $query) => $query->where('kelas_id', $filters['class_id']))
            ->when(isset($filters['status']), function (Builder $query) use ($filters): void {
                $query->where('is_approved', $filters['status'] === 'approved');
            })
            ->when(isset($filters['search']), function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('nomor_induk', "%{$search}%");
                });
            });
    }

    private function safeCsvValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }
}
