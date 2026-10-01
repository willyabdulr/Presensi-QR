@extends('layouts.app')

@php
    $activeRole = $role ?? 'mahasiswa';
    $roleColors = [
        'mahasiswa' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-200',
        'dosen' => 'bg-violet-600 hover:bg-violet-700 focus:ring-violet-200',
        'admin' => 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-200',
    ];
    $identityField = $activeRole === 'admin' ? 'email' : 'nomor_induk';
    $identityLabel = match ($activeRole) {
        'dosen' => 'NID / NIP',
        'mahasiswa' => 'NIM',
        default => 'Email Admin',
    };
    $identityPlaceholder = match ($activeRole) {
        'dosen' => 'Masukkan NID atau NIP',
        'mahasiswa' => 'Masukkan NIM',
        default => 'admin@kampus.ac.id',
    };
@endphp

@section('title', 'Masuk '.ucfirst($activeRole))

@section('content')
<section class="mx-auto my-4 w-full max-w-md rounded-md border border-blue-100 bg-white p-6 shadow-sm sm:my-8 sm:p-8">
    <div class="mb-6 text-center">
        <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-md bg-[#17385f] text-2xl text-white">
            <i class="fa-solid fa-graduation-cap"></i>
        </span>
        <h1 class="text-2xl font-extrabold text-[#17385f]">EduAttend</h1>
        <p class="mt-1 text-sm text-slate-500">Sistem Absensi Perkuliahan</p>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-1 rounded-md bg-slate-100 p-1" aria-label="Pilih role">
        @foreach(['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin' => 'Admin'] as $value => $label)
            <a href="{{ route('login.role', $value) }}" @class([
                'flex min-h-10 items-center justify-center gap-1.5 rounded px-2 text-xs font-semibold sm:text-sm',
                'bg-white text-[#17385f] shadow-sm' => $activeRole === $value,
                'text-slate-500 hover:text-slate-800' => $activeRole !== $value,
            ]) @if($activeRole === $value) aria-current="page" @endif>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mb-5">
        <h2 class="text-lg font-bold text-slate-900">Masuk {{ ucfirst($activeRole) }}</h2>
        <p class="mt-1 text-xs text-slate-500">Masuk dengan {{ $identityLabel }} yang terdaftar.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" role="alert">
            <i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post', $activeRole) }}" class="space-y-4">
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

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md px-4 py-3 text-sm font-bold text-white focus:outline-none focus:ring-4 {{ $roleColors[$activeRole] }}">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
        </button>
    </form>

    @if($activeRole === 'admin')
        <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">Akun admin dibuat oleh pengelola sistem.</p>
    @else
        <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">
            Belum punya akun?
            <a href="{{ route('register.role', $activeRole) }}" class="font-semibold text-blue-700 hover:text-blue-900">Daftar {{ $activeRole }}</a>
        </p>
    @endif
</section>
@endsection
