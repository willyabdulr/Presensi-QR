@extends('layouts.app')

@php
    $activeRole = $role;
    $roleColors = [
        'mahasiswa' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-200',
        'dosen' => 'bg-violet-600 hover:bg-violet-700 focus:ring-violet-200',
        'admin' => 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-200',
    ];
    $identityField = $activeRole === null ? 'identifier' : ($activeRole === 'admin' ? 'email' : 'nomor_induk');
    $identityLabel = match ($activeRole) {
        'dosen' => 'NID / NIP',
        'mahasiswa' => 'NIM',
        'admin' => 'Email Admin',
        default => 'Email / NIM / NID / NIP',
    };
    $identityPlaceholder = match ($activeRole) {
        'dosen' => 'Masukkan NID atau NIP',
        'mahasiswa' => 'Masukkan NIM',
        'admin' => 'admin@kampus.ac.id',
        default => 'Masukkan email, NIM, atau NID/NIP',
    };
@endphp

@section('title', $activeRole ? 'Masuk '.ucfirst($activeRole) : 'Masuk')

@section('content')
<section class="mx-auto my-5 w-full max-w-md rounded-md border border-blue-100 bg-white p-6 shadow-sm sm:my-8 sm:p-8">
    <div class="mb-6 text-center">
        <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-md bg-[#17385f] text-2xl text-white">
            <i class="fa-solid fa-graduation-cap"></i>
        </span>
        <h1 class="text-2xl font-extrabold text-[#17385f]">EduAttend</h1>
        <p class="mt-1 text-sm text-slate-500">Sistem Presensi Perkuliahan</p>
    </div>

    <div class="mb-5">
        <h2 class="text-lg font-bold text-slate-900">{{ $activeRole ? 'Masuk '.ucfirst($activeRole) : 'Masuk ke akun Anda' }}</h2>
        <p class="mt-1 text-xs text-slate-500">
            {{ $activeRole ? 'Masuk dengan '.$identityLabel.' yang terdaftar.' : 'Gunakan email, NIM, atau NID/NIP yang terdaftar.' }}
        </p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" role="alert">
            <i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $activeRole ? route('login.post', $activeRole) : route('login.authenticate') }}" class="space-y-4">
        @csrf
        <div>
            <label for="{{ $identityField }}" class="mb-1.5 block text-xs font-semibold text-slate-700">{{ $identityLabel }}</label>
            <input type="{{ $identityField === 'email' ? 'email' : 'text' }}" id="{{ $identityField }}" name="{{ $identityField }}" value="{{ old($identityField) }}" required autofocus autocomplete="username"
                class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="{{ $identityPlaceholder }}">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="Masukkan password">
        </div>

        <label class="flex items-center gap-2 text-xs text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Ingat saya
        </label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md px-4 py-3 text-sm font-bold text-white focus:outline-none focus:ring-4 {{ $roleColors[$activeRole ?? 'mahasiswa'] }}">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
        </button>
    </form>

    @if($activeRole === 'admin')
        <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">Akun admin dibuat oleh pengelola sistem.</p>
    @else
        <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:text-blue-900">Daftar</a>
        </p>
    @endif
</section>
@endsection
