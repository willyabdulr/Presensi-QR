@extends('layouts.app')

@section('title', $role ? 'Daftar '.ucfirst($role) : 'Pilih Jenis Akun')

@section('content')
@php
    $accent = $role === 'dosen' ? 'violet' : 'blue';
@endphp
<section class="mx-auto my-6 w-full max-w-md overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="border-b border-slate-100 px-6 pb-5 pt-6 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-md bg-[#17385f] text-xl text-white"><i class="fa-solid fa-graduation-cap"></i></span>
        <h1 class="mt-3 text-xl font-bold text-[#17385f]">Buat Akun EduAttend</h1>
        <p class="mt-1 text-sm text-slate-500">Sistem Absensi Perkuliahan</p>
    </div>

    <div class="p-5 sm:p-6">
        @if($errors->any())
            <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $errors->first() }}</div>
        @endif

        <div class="mb-5 grid grid-cols-2 gap-2 rounded-md bg-slate-100 p-1">
            <a href="{{ route('register.role', 'mahasiswa') }}" @class(['rounded px-3 py-2 text-center text-sm font-semibold', 'bg-blue-600 text-white' => $role === 'mahasiswa', 'text-slate-600 hover:bg-white' => $role !== 'mahasiswa'])><i class="fa-solid fa-user-graduate mr-1.5"></i>Mahasiswa</a>
            <a href="{{ route('register.role', 'dosen') }}" @class(['rounded px-3 py-2 text-center text-sm font-semibold', 'bg-violet-600 text-white' => $role === 'dosen', 'text-slate-600 hover:bg-white' => $role !== 'dosen'])><i class="fa-solid fa-chalkboard-user mr-1.5"></i>Dosen</a>
        </div>

        @if($role)
            <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"><i class="fa-solid fa-clock mr-1.5"></i>Akun {{ $role }} perlu persetujuan admin sebelum dapat digunakan.</div>
            <form method="POST" action="{{ route('register.store', $role) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-xs font-semibold text-slate-600">Nama lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-{{ $accent }}-500 focus:outline-none focus:ring-2 focus:ring-{{ $accent }}-100">
                </div>
                <div>
                    <label for="email" class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@kampus.ac.id" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-{{ $accent }}-500 focus:outline-none focus:ring-2 focus:ring-{{ $accent }}-100">
                </div>
                <div>
                    <label for="nomor_induk" class="mb-1 block text-xs font-semibold text-slate-600">{{ $role === 'dosen' ? 'NID / NIP' : 'NIM' }}</label>
                    <input type="text" id="nomor_induk" name="nomor_induk" value="{{ old('nomor_induk') }}" required autocomplete="off" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-{{ $accent }}-500 focus:outline-none focus:ring-2 focus:ring-{{ $accent }}-100">
                </div>
                <div>
                    <label for="password" class="mb-1 block text-xs font-semibold text-slate-600">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-{{ $accent }}-500 focus:outline-none focus:ring-2 focus:ring-{{ $accent }}-100">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-semibold text-slate-600">Konfirmasi password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-{{ $accent }}-500 focus:outline-none focus:ring-2 focus:ring-{{ $accent }}-100">
                </div>
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Daftar sebagai {{ ucfirst($role) }}</button>
            </form>
        @else
            <p class="rounded-md border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-600">Pilih jenis akun untuk melanjutkan pendaftaran.</p>
        @endif

        <p class="mt-5 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ $role ? route('login.role', $role) : route('login') }}" class="font-semibold text-blue-700 hover:text-blue-900">Masuk</a></p>
    </div>
</section>
@endsection
