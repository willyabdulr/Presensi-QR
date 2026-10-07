@extends('layouts.app')

@section('title', 'Mata Kuliah & Jadwal')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-teal-700">Data Akademik</p>
    <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Mata Kuliah & Jadwal</h1>
    <p class="mt-1 text-sm text-slate-500">Kelola mata kuliah dan penugasan dosen pengampu.</p>
</div>

<div class="grid gap-4 xl:grid-cols-[minmax(17rem,0.8fr)_minmax(0,1.6fr)]">
    <section class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
        <h2 class="text-base font-bold text-slate-900">Tambah Mata Kuliah</h2>
        <form method="POST" action="{{ route('admin.courses.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="kode_mk" class="mb-1 block text-sm font-medium text-slate-700">Kode mata kuliah</label>
                <input id="kode_mk" name="kode_mk" value="{{ old('kode_mk') }}" maxlength="32" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                @error('kode_mk')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="nama_mk" class="mb-1 block text-sm font-medium text-slate-700">Nama mata kuliah</label>
                <input id="nama_mk" name="nama_mk" value="{{ old('nama_mk') }}" maxlength="255" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                @error('nama_mk')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-md bg-teal-700 px-4 text-sm font-semibold text-white hover:bg-teal-800">
                <i class="fa-solid fa-plus"></i> Tambah Mata Kuliah
            </button>
        </form>
    </section>

    <section class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
        <div class="mb-4">
            <h2 class="text-base font-bold text-slate-900">Buat Jadwal & Tugaskan Dosen</h2>
        </div>

        @if($mataKuliahList->isEmpty())
            <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">Tambahkan mata kuliah sebelum membuat jadwal.</p>
        @endif
        @if($dosenList->isEmpty())
            <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">Belum ada akun dosen yang disetujui untuk ditugaskan.</p>
        @endif

        <form method="POST" action="{{ route('admin.schedules.store') }}" class="space-y-4">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="mata_kuliah_id" class="mb-1 block text-sm font-medium text-slate-700">Mata kuliah</label>
                    <select id="mata_kuliah_id" name="mata_kuliah_id" required class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih mata kuliah</option>
                        @foreach($mataKuliahList as $mataKuliah)
                            <option value="{{ $mataKuliah->id }}" @selected(old('mata_kuliah_id') == $mataKuliah->id)>{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</option>
                        @endforeach
                    </select>
                    @error('mata_kuliah_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dosen_id" class="mb-1 block text-sm font-medium text-slate-700">Dosen pengampu</label>
                    <select id="dosen_id" name="dosen_id" required class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih dosen</option>
                        @foreach($dosenList as $dosen)
                            <option value="{{ $dosen->id }}" @selected(old('dosen_id') == $dosen->id)>{{ $dosen->name }} - {{ $dosen->nomor_induk }}</option>
                        @endforeach
                    </select>
                    @error('dosen_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="hari" class="mb-1 block text-sm font-medium text-slate-700">Hari</label>
                    <select id="hari" name="hari" required class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih hari</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)
                            <option value="{{ $hari }}" @selected(old('hari') === $hari)>{{ $hari }}</option>
                        @endforeach
                    </select>
                    @error('hari')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="kelas" class="mb-1 block text-sm font-medium text-slate-700">Kelas</label>
                    <input id="kelas" name="kelas" value="{{ old('kelas') }}" maxlength="20" placeholder="Contoh: 04SIFE001" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('kelas')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jam_mulai" class="mb-1 block text-sm font-medium text-slate-700">Jam mulai</label>
                    <input id="jam_mulai" name="jam_mulai" type="time" value="{{ old('jam_mulai') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('jam_mulai')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jam_selesai" class="mb-1 block text-sm font-medium text-slate-700">Jam selesai</label>
                    <input id="jam_selesai" name="jam_selesai" type="time" value="{{ old('jam_selesai') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('jam_selesai')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="latitude_kelas" class="mb-1 block text-sm font-medium text-slate-700">Latitude lokasi kelas</label>
                    <input id="latitude_kelas" name="latitude_kelas" type="number" step="any" min="-90" max="90" value="{{ old('latitude_kelas') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('latitude_kelas')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="longitude_kelas" class="mb-1 block text-sm font-medium text-slate-700">Longitude lokasi kelas</label>
                    <input id="longitude_kelas" name="longitude_kelas" type="number" step="any" min="-180" max="180" value="{{ old('longitude_kelas') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('longitude_kelas')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button id="use-current-location" type="button" class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-location-crosshairs"></i> Gunakan Lokasi Saat Ini
                </button>
                <span id="location-status" class="text-xs text-slate-500" role="status" aria-live="polite"></span>
            </div>
            <button type="submit" @disabled($mataKuliahList->isEmpty() || $dosenList->isEmpty()) class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="fa-solid fa-calendar-plus"></i> Simpan Jadwal
            </button>
        </form>
    </section>
</div>

<section class="mt-5 rounded-md border border-slate-200 bg-white p-4 sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="font-bold text-slate-900">Data Kelas</h2>
            <p class="mt-1 text-sm text-slate-500">Kelas yang dapat dihubungkan ke jadwal mata kuliah dan mahasiswa.</p>
        </div>
        <form method="POST" action="{{ route('admin.classes.store') }}" class="flex w-full gap-2 lg:max-w-md">
            @csrf
            <label for="kode_kelas" class="sr-only">Kode kelas</label>
            <input id="kode_kelas" name="kode_kelas" value="{{ old('kode_kelas') }}" maxlength="20" required placeholder="Contoh: 04SIFE001" class="h-10 min-w-0 flex-1 rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
            <button type="submit" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-md bg-teal-700 px-3 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa-solid fa-plus"></i>Tambah kelas</button>
        </form>
    </div>
    @error('kode_kelas')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
    <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
        @forelse($kelasList as $kelas)
            <form method="POST" action="{{ route('admin.classes.update', $kelas) }}" class="w-64 shrink-0 rounded-md border border-slate-200 bg-slate-50 p-3">
                @csrf
                @method('PATCH')
                <label for="kode-kelas-{{ $kelas->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Kode kelas</label>
                <div class="flex gap-2">
                    <input id="kode-kelas-{{ $kelas->id }}" name="kode_kelas" value="{{ $kelas->kode_kelas }}" maxlength="20" required class="h-9 min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-2 text-sm">
                    <button type="submit" class="inline-flex h-9 items-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Simpan</button>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $kelas->mahasiswa_count }} mahasiswa · {{ $kelas->jadwal_kuliah_count }} jadwal</p>
            </form>
        @empty
            <p class="text-sm text-slate-500">Belum ada data kelas.</p>
        @endforelse
    </div>
</section>

<section class="mt-5 overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
        <h2 class="font-bold text-slate-900">Mata Kuliah · Kelas · Jadwal</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola dosen utama dan data pertemuan pada setiap kelas. Dosen pengganti berlaku untuk pertemuan yang dipilih saja.</p>
        <div class="mt-4 max-w-md">
            <label for="course-list-search" class="mb-1 block text-sm font-medium text-slate-700">Cari mata kuliah</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input id="course-list-search" type="search" autocomplete="off" placeholder="Cari nama atau kode mata kuliah" class="h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
            </div>
            <p class="mt-1 text-xs text-slate-500">Pencarian hanya mencakup nama dan kode mata kuliah.</p>
        </div>
    </div>
    <div class="divide-y divide-slate-200">
        @forelse($mataKuliahList as $mataKuliah)
            @php
                $jadwalPerKelas = $mataKuliah->jadwalKuliah->groupBy(
                    fn ($jadwal) => $jadwal->kelas ?: $jadwal->kelasData?->kode_kelas ?: 'Kelas belum ditentukan'
                );
            @endphp
            <details class="group course-list-item" data-course-search="{{ \Illuminate\Support\Str::lower($mataKuliah->nama_mk.' '.$mataKuliah->kode_mk) }}">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 hover:bg-slate-50 sm:px-5">
                    <span>
                        <span class="block font-semibold text-slate-900">{{ $mataKuliah->nama_mk }}</span>
                        <span class="mt-1 block font-mono text-xs text-teal-800">{{ $mataKuliah->kode_mk }} · {{ $mataKuliah->jadwal_kuliah_count }} jadwal</span>
                    </span>
                    <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition group-open:rotate-180"></i>
                </summary>
                <div class="space-y-4 bg-slate-50/70 p-4 sm:p-5">
                    @forelse($jadwalPerKelas as $namaKelas => $jadwalGroup)
                        <section class="rounded-md border border-slate-200 bg-white p-4">
                            <h3 class="font-bold text-slate-900">Kelas · {{ $namaKelas }}</h3>
                            <div class="mt-3 space-y-3">
                                @foreach($jadwalGroup as $jadwal)
                                    <article class="rounded-md border border-slate-200 p-4">
                                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-slate-500">Mata kuliah</p>
                                                <p class="mt-1 font-semibold text-slate-800">{{ $mataKuliah->nama_mk }}</p>
                                                <p class="font-mono text-xs text-slate-500">{{ $mataKuliah->kode_mk }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-slate-500">Kelas</p>
                                                <p class="mt-1 font-semibold text-slate-800">{{ $jadwal->kelas ?: $jadwal->kelasData?->kode_kelas ?: 'Kelas belum ditentukan' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-slate-500">Dosen utama</p>
                                                <p class="mt-1 font-semibold text-slate-800">{{ $jadwal->dosen->name ?? 'Belum ditentukan' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-slate-500">Hari & jam</p>
                                                <p class="mt-1 font-semibold text-slate-800">{{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }}–{{ substr($jadwal->jam_selesai, 0, 5) }}</p>
                                            </div>
                                        </div>

                                        <details class="mt-4 border-t border-slate-100 pt-3">
                                            <summary class="cursor-pointer text-sm font-semibold text-teal-800">Atur dosen utama dan pertemuan ({{ $jadwal->pertemuan->count() }})</summary>
                                            <form method="POST" action="{{ route('admin.schedules.update-time', $jadwal) }}" class="mt-3 grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label for="jam-mulai-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Jam mulai</label>
                                                    <input id="jam-mulai-{{ $jadwal->id }}" name="jam_mulai" type="time" value="{{ substr($jadwal->jam_mulai, 0, 5) }}" required class="h-9 w-full rounded-md border border-slate-300 bg-white px-3 text-sm">
                                                </div>
                                                <div>
                                                    <label for="jam-selesai-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Jam selesai</label>
                                                    <input id="jam-selesai-{{ $jadwal->id }}" name="jam_selesai" type="time" value="{{ substr($jadwal->jam_selesai, 0, 5) }}" required class="h-9 w-full rounded-md border border-slate-300 bg-white px-3 text-sm">
                                                </div>
                                                <button type="submit" class="h-9 rounded-md border border-teal-200 bg-white px-3 text-sm font-semibold text-teal-800 hover:bg-teal-50">Simpan jam</button>
                                                @error('jam_mulai')<p class="text-xs text-rose-700 sm:col-span-3">{{ $message }}</p>@enderror
                                                @error('jam_selesai')<p class="text-xs text-rose-700 sm:col-span-3">{{ $message }}</p>@enderror
                                            </form>
                                            <form method="POST" action="{{ route('admin.schedules.update-lecturer', $jadwal) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                                @csrf
                                                @method('PATCH')
                                                <div class="min-w-0 flex-1">
                                                    <label for="main-lecturer-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Dosen utama jadwal</label>
                                                    <select id="main-lecturer-{{ $jadwal->id }}" name="dosen_id" required class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm">
                                                        @foreach($dosenList as $dosen)
                                                            <option value="{{ $dosen->id }}" @selected($jadwal->dosen_id === $dosen->id)>{{ $dosen->name }} · {{ $dosen->nomor_induk }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan dosen utama</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.schedules.update-class', $jadwal) }}" class="mt-3 flex items-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label for="kelas-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Kode kelas</label>
                                                    <input id="kelas-{{ $jadwal->id }}" name="kelas" value="{{ $jadwal->kelas ?: $jadwal->kelasData?->kode_kelas }}" maxlength="20" placeholder="Belum diisi" class="h-10 w-40 rounded-md border border-slate-300 px-3 text-sm">
                                                </div>
                                                <button type="submit" class="inline-flex h-10 items-center rounded-md border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan kelas</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.meetings.store') }}" class="mt-4 grid gap-2 rounded-md bg-slate-50 p-3 sm:grid-cols-4">
                                                @csrf
                                                <input type="hidden" name="jadwal_kuliah_id" value="{{ $jadwal->id }}">
                                                <div>
                                                    <label for="new-meeting-number-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Nomor pertemuan</label>
                                                    <input id="new-meeting-number-{{ $jadwal->id }}" name="pertemuan_ke" type="number" min="1" required value="{{ ($jadwal->pertemuan->max('pertemuan_ke') ?? 0) + 1 }}" class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                </div>
                                                <div>
                                                    <label for="new-meeting-topic-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Topik</label>
                                                    <input id="new-meeting-topic-{{ $jadwal->id }}" name="topik" required maxlength="255" class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                </div>
                                                <div>
                                                    <label for="new-meeting-date-{{ $jadwal->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Tanggal</label>
                                                    <input id="new-meeting-date-{{ $jadwal->id }}" name="tanggal_pertemuan" type="date" required class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                </div>
                                                <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 self-end rounded-md bg-teal-700 px-3 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa-solid fa-plus"></i>Tambah pertemuan</button>
                                            </form>

                                            <div class="mt-3 space-y-3">
                                                @forelse($jadwal->pertemuan as $pertemuan)
                                                    <div class="grid gap-3 rounded-md border border-slate-200 p-3 lg:grid-cols-2">
                                                        <div class="flex flex-wrap items-center justify-between gap-2 lg:col-span-2">
                                                            <span class="text-sm font-semibold text-slate-800">
                                                                Pertemuan {{ $pertemuan->pertemuan_ke }} · {{ $pertemuan->status_pertemuan }}
                                                            </span>
                                                            <span class="text-xs text-slate-600">
                                                                Dosen: {{ $pertemuan->dosenPengganti->name ?? $jadwal->dosen->name }}
                                                                @if($pertemuan->dosenPengganti)
                                                                    <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-800">Dosen Pengganti</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.meetings.update', $pertemuan) }}" class="grid gap-2 sm:grid-cols-3">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div>
                                                                <label for="meeting-topic-{{ $pertemuan->id }}" class="mb-1 block text-xs font-semibold text-slate-600">P{{ $pertemuan->pertemuan_ke }} · Topik</label>
                                                                <input name="topik" id="meeting-topic-{{ $pertemuan->id }}" value="{{ $pertemuan->topik }}" maxlength="255" required class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                            </div>
                                                            <div>
                                                                <label for="meeting-number-{{ $pertemuan->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Nomor</label>
                                                                <input name="pertemuan_ke" id="meeting-number-{{ $pertemuan->id }}" type="number" min="1" value="{{ $pertemuan->pertemuan_ke }}" required class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                            </div>
                                                            <div>
                                                                <label for="meeting-date-{{ $pertemuan->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Tanggal</label>
                                                                <input name="tanggal_pertemuan" id="meeting-date-{{ $pertemuan->id }}" type="date" value="{{ $pertemuan->tanggal_pertemuan?->format('Y-m-d') }}" required class="h-9 w-full rounded-md border border-slate-300 px-2 text-sm">
                                                            </div>
                                                            <button type="submit" class="h-9 justify-self-start rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:col-span-3">Simpan detail pertemuan</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.meetings.update-substitute', $pertemuan) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="min-w-0 flex-1">
                                                                <label for="substitute-{{ $pertemuan->id }}" class="mb-1 block text-xs font-semibold text-slate-600">P{{ $pertemuan->pertemuan_ke }} · Dosen pengganti</label>
                                                                <select id="substitute-{{ $pertemuan->id }}" name="dosen_pengganti_id" class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm">
                                                                    <option value="">Gunakan dosen utama</option>
                                                                    @foreach($dosenList as $dosen)
                                                                        @if($dosen->id !== $jadwal->dosen_id)
                                                                            <option value="{{ $dosen->id }}" @selected($pertemuan->dosen_pengganti_id === $dosen->id)>{{ $dosen->name }} · {{ $dosen->nomor_induk }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="h-9 rounded-md border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-800 hover:bg-amber-100">Simpan pengganti</button>
                                                        </form>
                                                    </div>
                                                @empty
                                                    <p class="rounded-md border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500">Belum ada pertemuan.</p>
                                                @endforelse
                                            </div>
                                        </details>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <p class="rounded-md border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Belum ada jadwal untuk mata kuliah ini.</p>
                    @endforelse
                </div>
            </details>
                @empty
            <p class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data mata kuliah.</p>
        @endforelse
        <p id="course-list-empty" class="hidden px-4 py-8 text-center text-sm text-slate-500" aria-live="polite">Mata kuliah tidak ditemukan.</p>
    </div>
</section>
@endsection

@push('scripts')
    <script>
        const locationButton = document.getElementById('use-current-location');
        const locationStatus = document.getElementById('location-status');
        const courseSearch = document.getElementById('course-list-search');
        const courseItems = [...document.querySelectorAll('.course-list-item')];
        const courseListEmpty = document.getElementById('course-list-empty');

        courseSearch?.addEventListener('input', () => {
            const searchTerm = courseSearch.value.trim().toLowerCase();
            let visibleCourses = 0;

            courseItems.forEach((course) => {
                const matches = course.dataset.courseSearch.includes(searchTerm);
                course.hidden = !matches;
                visibleCourses += Number(matches);
            });

            courseListEmpty?.classList.toggle('hidden', visibleCourses > 0 || searchTerm === '' || courseItems.length === 0);
        });

        locationButton?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Browser tidak mendukung lokasi.';
                return;
            }

            locationButton.disabled = true;
            locationStatus.textContent = 'Mengambil lokasi...';

            navigator.geolocation.getCurrentPosition(
                ({ coords }) => {
                    document.getElementById('latitude_kelas').value = coords.latitude.toFixed(8);
                    document.getElementById('longitude_kelas').value = coords.longitude.toFixed(8);
                    locationStatus.textContent = 'Koordinat berhasil diisi.';
                    locationButton.disabled = false;
                },
                () => {
                    locationStatus.textContent = 'Lokasi gagal diambil. Periksa izin browser.';
                    locationButton.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    </script>
@endpush
