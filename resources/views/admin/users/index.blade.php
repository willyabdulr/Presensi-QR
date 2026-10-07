@extends('layouts.app')

@section('title', 'Data '.ucfirst($filters['role'] ?? 'Akun'))

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-teal-700">Pengelolaan akun</p>
            <h1 class="mt-1 text-2xl font-bold text-[#17385f]">{{ isset($filters['role']) ? 'Data '.ucfirst($filters['role']) : 'Data Dosen & Mahasiswa' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Data akun, identitas, dan status persetujuan.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
            <i class="fa-solid fa-hourglass-half"></i>{{ $counts['pending'] }} menunggu approval
        </span>
    </div>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="Jumlah akun">
        <div class="flex items-center justify-between rounded-md border border-slate-200 border-l-4 border-l-violet-500 bg-white p-4">
            <span class="text-sm text-slate-600">Dosen</span>
            <span class="text-2xl font-bold text-slate-900">{{ number_format($counts['dosen']) }}</span>
        </div>
        <div class="flex items-center justify-between rounded-md border border-slate-200 border-l-4 border-l-blue-500 bg-white p-4">
            <span class="text-sm text-slate-600">Mahasiswa</span>
            <span class="text-2xl font-bold text-slate-900">{{ number_format($counts['mahasiswa']) }}</span>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 rounded-md border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <div class="min-w-40 flex-1">
            <label for="search" class="mb-1 block text-xs font-semibold text-slate-600">Cari nama, email, NIP/NIM</label>
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <div>
            <label for="role" class="mb-1 block text-xs font-semibold text-slate-600">Role</label>
            <select id="role" name="role" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm sm:w-40">
                <option value="">Semua role</option>
                <option value="dosen" @selected(($filters['role'] ?? '') === 'dosen')>Dosen</option>
                <option value="mahasiswa" @selected(($filters['role'] ?? '') === 'mahasiswa')>Mahasiswa</option>
            </select>
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
            <select id="status" name="status" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm sm:w-44">
                <option value="">Semua status</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Menunggu approval</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Disetujui</option>
            </select>
        </div>
        @if(($filters['role'] ?? null) === 'mahasiswa')
            <div>
                <label for="class_id" class="mb-1 block text-xs font-semibold text-slate-600">Kelas</label>
                <select id="class_id" name="class_id" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm sm:w-44">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(($filters['class_id'] ?? '') == $class->id)>{{ $class->kode_kelas }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex gap-2">
            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i class="fa-solid fa-magnifying-glass"></i>Cari</button>
            <a href="{{ route('admin.users.index', isset($filters['role']) ? ['role' => $filters['role']] : []) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50" title="Reset filter" aria-label="Reset filter"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>

    <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
        @if(($filters['role'] ?? null) === 'mahasiswa')
            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">Ekspor mahasiswa dari kelas yang dipilih atau seluruh kelas.</p>
                <a href="{{ route('admin.users.export-csv', $exportFilters) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <i class="fa-solid fa-file-csv"></i>Export CSV
                </a>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama akun</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">NIP / NIM</th>
                        @if(($filters['role'] ?? null) === 'mahasiswa')
                            <th class="px-4 py-3 font-semibold">Kelas</th>
                        @endif
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-teal-50/30">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ ucfirst($user->role) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->nomor_induk ?: '-' }}</td>
                            @if(($filters['role'] ?? null) === 'mahasiswa')
                                <td class="px-4 py-3 text-slate-600">{{ $user->kelas?->kode_kelas ?? '-' }}</td>
                            @endif
                            <td class="px-4 py-3">
                                @if($user->is_approved)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check"></i>Disetujui</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700"><i class="fa-solid fa-clock"></i>Menunggu</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1.5 font-semibold text-blue-700 hover:text-blue-900">Lihat<i class="fa-solid fa-arrow-right text-xs"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ ($filters['role'] ?? null) === 'mahasiswa' ? 7 : 6 }}" class="px-4 py-12 text-center text-slate-500">Tidak ada akun yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </section>
</div>
@endsection
