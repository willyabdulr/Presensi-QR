@extends('layouts.app')

@section('title', $role ? 'Daftar '.ucfirst($role) : 'Pilih Jenis Akun')

@section('content')
<section class="mx-auto my-5 w-full max-w-md rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:my-8 sm:p-8">
    <div class="mb-6 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-md bg-[#17385f] text-2xl text-white"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1 class="mt-3 text-2xl font-extrabold text-[#17385f]">{{ $role ? 'Daftar '.ucfirst($role) : 'Buat Akun EduAttend' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Sistem Presensi Perkuliahan</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $errors->first() }}</div>
    @endif

    @if($role)
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <i class="fa-solid fa-clock mr-1.5"></i>Akun {{ $role }} perlu persetujuan admin sebelum dapat digunakan.
        </div>
        <form method="POST" action="{{ route('register.store', $role) }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-xs font-semibold text-slate-600">Nama lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="email" class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@kampus.ac.id"
                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="nomor_induk" class="mb-1 block text-xs font-semibold text-slate-600">{{ $role === 'dosen' ? 'NID / NIP' : 'NIM' }}</label>
                <input type="text" id="nomor_induk" name="nomor_induk" value="{{ old('nomor_induk') }}" required autocomplete="off"
                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="password" class="mb-1 block text-xs font-semibold text-slate-600">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-xs font-semibold text-slate-600">Konfirmasi password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit" @class([
                'w-full rounded-md px-4 py-2.5 text-sm font-bold text-white',
                'bg-violet-600 hover:bg-violet-700' => $role === 'dosen',
                'bg-blue-600 hover:bg-blue-700' => $role === 'mahasiswa',
            ])>Daftar sebagai {{ ucfirst($role) }}</button>
        </form>

        <div class="mt-5 flex flex-col items-center gap-2 border-t border-slate-100 pt-4 text-center text-sm">
            <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:text-blue-900">Kembali ke pilihan jenis akun</a>
            <p class="text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:text-blue-900">Masuk</a></p>
        </div>
    @else
        <p class="mb-4 text-center text-sm text-slate-600">Pilih jenis akun yang ingin Anda daftarkan.</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('register.role', 'mahasiswa') }}" class="flex min-h-32 flex-col items-center justify-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-4 py-5 text-center text-blue-900 transition hover:border-blue-400 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100">
                <i class="fa-solid fa-user-graduate text-2xl"></i>
                <span class="font-bold">Mahasiswa</span>
                <span class="text-xs text-blue-700">Daftar dengan NIM</span>
            </a>
            <a href="{{ route('register.role', 'dosen') }}" class="flex min-h-32 flex-col items-center justify-center gap-2 rounded-md border border-violet-200 bg-violet-50 px-4 py-5 text-center text-violet-900 transition hover:border-violet-400 hover:bg-violet-100 focus:outline-none focus:ring-4 focus:ring-violet-100">
                <i class="fa-solid fa-chalkboard-user text-2xl"></i>
                <span class="font-bold">Dosen</span>
                <span class="text-xs text-violet-700">Daftar dengan NID / NIP</span>
            </a>
        </div>
        <p class="mt-5 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:text-blue-900">Masuk</a>
        </p>
    @endif
</section>
@endsection
