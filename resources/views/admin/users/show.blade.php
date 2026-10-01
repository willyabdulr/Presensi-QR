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
    </dl>

    @unless($user->is_approved)
        <div class="border-t border-slate-200 bg-slate-50 p-5">
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa-solid fa-check"></i>Setujui akun</button>
            </form>
        </div>
    @endunless
</section>
@endsection
