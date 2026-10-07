@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-blue-700">Pengaturan akun</p>
        <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Edit Profil {{ ucfirst($user->role) }}</h1>
    </div>
    <a href="{{ route($user->role.'.profile') }}" class="inline-flex h-10 items-center gap-2 self-start rounded-md border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Profil
    </a>
</div>

<section class="max-w-3xl rounded-md border border-slate-200 bg-white p-5 sm:p-6">
    <form method="POST" action="{{ route($user->role.'.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PATCH')

        <div class="flex flex-col items-center gap-4 border-b border-slate-100 pb-5 sm:flex-row">
            @if($user->profile_photo_path)
                <img id="profile-photo-preview" src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Foto profil saat ini" class="h-20 w-20 rounded-full border border-slate-200 object-cover">
            @else
                <div id="profile-photo-fallback" class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-500">
                    <i class="fa-solid fa-user"></i>
                </div>
                <img id="profile-photo-preview" src="" alt="Pratinjau foto profil" class="hidden h-20 w-20 rounded-full border border-slate-200 object-cover">
            @endif
            <div class="w-full">
                <label for="profile_photo" class="mb-1 block text-sm font-semibold text-slate-700">Foto profil</label>
                <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:h-10 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                @error('profile_photo')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            @if(in_array($user->role, ['dosen', 'mahasiswa'], true))
                <div>
                    <label for="nomor_induk" class="mb-1 block text-sm font-medium text-slate-700">{{ $user->role === 'dosen' ? 'NID / NIP' : 'NIM' }}</label>
                    <input id="nomor_induk" name="nomor_induk" value="{{ old('nomor_induk', $user->nomor_induk) }}" required maxlength="32" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('nomor_induk')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
            </button>
            <a href="{{ route($user->role.'.profile') }}" class="inline-flex h-10 items-center rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
    <script>
        const profilePhotoInput = document.getElementById('profile_photo');
        const profilePhotoPreview = document.getElementById('profile-photo-preview');
        let profilePhotoPreviewUrl;

        profilePhotoInput?.addEventListener('change', () => {
            const file = profilePhotoInput.files?.[0];

            if (!file) {
                return;
            }

            if (profilePhotoPreviewUrl) {
                URL.revokeObjectURL(profilePhotoPreviewUrl);
            }

            profilePhotoPreviewUrl = URL.createObjectURL(file);
            profilePhotoPreview.src = profilePhotoPreviewUrl;
            profilePhotoPreview.classList.remove('hidden');
            document.getElementById('profile-photo-fallback')?.classList.add('hidden');
        });
    </script>
@endpush
