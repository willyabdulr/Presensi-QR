@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<section class="mx-auto my-4 w-full max-w-md rounded-md border border-blue-100 bg-white p-6 shadow-sm sm:my-8 sm:p-8">
    <div class="mb-6 text-center">
        <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-md bg-[#17385f] text-2xl text-white">
            <i class="fa-solid fa-graduation-cap"></i>
        </span>
        <h1 class="text-2xl font-extrabold text-[#17385f]">EduAttend</h1>
        <p class="mt-1 text-sm text-slate-500">Sistem Absensi Perkuliahan</p>
    </div>

    <div class="mb-5">
        <h2 class="text-lg font-bold text-slate-900">Masuk ke akun Anda</h2>
        <p class="mt-1 text-xs text-slate-500">Gunakan email, NIM, atau NIDN/NIP yang terdaftar.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700" role="alert">
            <i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post', $role ?? 'mahasiswa') }}" class="space-y-4">
        @csrf
        <div>
            <label for="identity" class="mb-1.5 block text-xs font-semibold text-slate-700">Email / NIM / NID / NIP</label>
            <input type="text" id="identity" name="identity" value="{{ old('identity') }}" required autofocus autocomplete="username"
                class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                placeholder="Masukkan email, NIM, atau NIDN/NIP">
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

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
        </button>
    </form>

    <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs text-slate-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:text-blue-900">Daftar</a>
    </p>
</section>
@endsection
