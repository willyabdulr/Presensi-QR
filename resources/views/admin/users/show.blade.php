@extends('layouts.app')

@section('title', 'Detail Akun')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.index', ['role' => $user->role]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900"><i class="fa-solid fa-arrow-left"></i>Kembali ke data {{ $user->role }}</a>
</div>

<section class="max-w-3xl overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="flex flex-col gap-4 border-b border-slate-200 bg-[#f7faff] p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl text-blue-800"><i class="fa-solid {{ $user->role === 'dosen' ? 'fa-chalkboard-user' : 'fa-user-graduate' }}"></i></span>
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500">Detail akun {{ $user->role }}</p>
                <h1 class="mt-1 text-xl font-bold text-[#17385f]">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Terdaftar {{ $user->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
        @if($user->is_approved)
            <span class="inline-flex w-fit items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800"><i class="fa-solid fa-circle-check"></i>Disetujui</span>
        @else
            <span class="inline-flex w-fit items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800"><i class="fa-solid fa-clock"></i>Menunggu approval</span>
        @endif
    </div>

    <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Nama lengkap</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Role</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ ucfirst($user->role) }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Email</dt><dd class="mt-1 break-all text-sm font-medium text-slate-900">{{ $user->email }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">{{ $user->role === 'dosen' ? 'NIP' : 'NIM' }}</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->nomor_induk ?: '-' }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Email terverifikasi</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->email_verified_at?->format('d M Y, H:i') ?? 'Belum' }}</dd></div>
        @if($user->role === 'mahasiswa')
            <div><dt class="text-xs font-semibold uppercase text-slate-500">Kelas</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->kelas?->kode_kelas ?? 'Belum ditetapkan' }}</dd></div>
        @endif
    </dl>

    <div id="edit-account" class="border-t border-slate-200 p-5 sm:p-6">
        <h2 class="mb-4 text-base font-bold text-slate-900">Edit data akun</h2>
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                    @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                    @error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nomor_induk" class="mb-1 block text-sm font-medium text-slate-700">{{ $user->role === 'dosen' ? 'NIP' : 'NIM' }}</label>
                    <input id="nomor_induk" name="nomor_induk" value="{{ old('nomor_induk', $user->nomor_induk) }}" maxlength="32" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                    @error('nomor_induk')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                @if($user->role === 'mahasiswa')
                    <div>
                        <label for="kelas_id" class="mb-1 block text-sm font-medium text-slate-700">Kelas</label>
                        <select id="kelas_id" name="kelas_id" class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm">
                            <option value="">Belum ditetapkan</option>
                            @foreach($kelasList as $kelas)
                                <option value="{{ $kelas->id }}" @selected(old('kelas_id', $user->kelas_id) == $kelas->id)>{{ $kelas->kode_kelas }}</option>
                            @endforeach
                        </select>
                        @error('kelas_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa-solid fa-floppy-disk"></i>Simpan perubahan</button>
        </form>
    </div>

    @unless($user->is_approved)
        <div class="border-t border-slate-200 bg-slate-50 p-5">
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa-solid fa-check"></i>Setujui akun</button>
            </form>
        </div>
    @endunless

    <div class="border-t border-rose-200 bg-rose-50/60 p-5 sm:p-6">
        <h2 class="text-sm font-bold text-rose-900">Hapus akun</h2>
        <p class="mt-1 text-sm text-rose-800">Akun dan semua data terkait akan dihapus, termasuk riwayat presensi, jadwal mengajar, atau pertemuan yang terhubung.</p>
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-3" onsubmit="return confirm('Hapus akun ini beserta seluruh data terkait? Tindakan ini tidak dapat dibatalkan.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800"><i class="fa-solid fa-trash"></i>Hapus akun {{ $user->role }}</button>
        </form>
    </div>
</section>
@endsection
