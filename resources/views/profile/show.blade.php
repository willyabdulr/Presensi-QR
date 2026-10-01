@extends('layouts.app')

@section('title', 'Profil')

@section('content')
@php
    $profileIcon = match ($user->role) {
        'admin' => 'fa-shield-halved',
        'dosen' => 'fa-chalkboard-user',
        default => 'fa-graduation-cap',
    };
@endphp
<div class="mb-5">
    <p class="text-sm font-semibold text-blue-700">Akun Anda</p>
    <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Profil {{ ucfirst($user->role) }}</h1>
</div>

<section class="max-w-3xl overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-blue-50 to-white px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:text-left">
            @if($user->profile_photo_path)
                <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Foto profil {{ $user->name }}" class="h-16 w-16 rounded-full border border-slate-200 object-cover">
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-[#17385f] text-2xl text-white">
                    <i class="fa-solid {{ $profileIcon }}"></i>
                </span>
            @endif
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ ucfirst($user->role) }} - {{ $user->nomor_induk ?: 'Nomor induk belum diisi' }}</p>
            </div>
        </div>
        <a href="{{ route($user->role.'.profile.edit') }}" class="inline-flex h-10 items-center justify-center gap-2 self-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 sm:self-auto">
            <i class="fa-solid fa-pen"></i> Edit Profil
        </a>
    </div>

    <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Nama lengkap</dt>
            <dd class="mt-1.5 break-words text-sm font-semibold text-slate-800">{{ $user->name }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
            <dd class="mt-1.5 break-all text-sm font-semibold text-slate-800">{{ $user->email }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ $user->role === 'dosen' ? 'NID / NIP' : ($user->role === 'mahasiswa' ? 'NIM' : 'Nomor identitas') }}</dt>
            <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->nomor_induk ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Status akun</dt>
            <dd class="mt-1.5 text-sm font-semibold {{ $user->is_approved ? 'text-emerald-700' : 'text-amber-700' }}">
                <i class="fa-solid {{ $user->is_approved ? 'fa-circle-check' : 'fa-clock' }} mr-1"></i>{{ $user->is_approved ? 'Aktif' : 'Menunggu persetujuan' }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Bergabung</dt>
            <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->created_at->format('d M Y') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Email terverifikasi</dt>
            <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Belum diverifikasi' }}</dd>
        </div>
    </dl>
</section>
@endsection
