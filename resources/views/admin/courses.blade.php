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
            <div class="grid gap-4 sm:grid-cols-2">
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
                    <div class="grid gap-3 sm:grid-cols-2">
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
                    </div>
                </div>
                <div>
                    <label for="latitude_kelas" class="mb-1 block text-sm font-medium text-slate-700">Latitude lokasi kelas</label>
                    <input id="latitude_kelas" name="latitude_kelas" type="number" step="any" min="-90" max="90" value="{{ old('latitude_kelas') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                    @error('latitude_kelas')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="longitude_kelas" class="mb-1 block text-sm font-medium text-slate-700">Longitude lokasi kelas</label>
                            <input id="longitude_kelas" name="longitude_kelas" type="number" step="any" min="-180" max="180" value="{{ old('longitude_kelas') }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                            @error('longitude_kelas')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="radius_meter" class="mb-1 block text-sm font-medium text-slate-700">Radius presensi (meter)</label>
                            <input id="radius_meter" name="radius_meter" type="number" min="1" max="1000" value="{{ old('radius_meter', 50) }}" required class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">
                            @error('radius_meter')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button id="use-current-location" type="button" class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-location-crosshairs"></i> Gunakan Lokasi Saat Ini
                </button>
                <span id="location-status" class="text-xs text-slate-500" role="status" aria-live="polite"></span>
            </div>
            <div class="space-y-4 border-t border-slate-200 pt-4">
                <div class="flex items-center gap-2">
                    <input id="is_substitute" name="is_substitute" type="checkbox" value="1" @checked(old('is_substitute')) class="rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                    <label for="is_substitute" class="text-sm font-medium text-slate-700">Dosen pengganti</label>
                </div>
                <div id="substitute-note-field" @class(['hidden' => !old('is_substitute')])>
                    <label for="substitute_note" class="mb-1 block text-sm font-medium text-slate-700">Catatan dosen pengganti</label>
                    <textarea id="substitute_note" name="substitute_note" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100">{{ old('substitute_note') }}</textarea>
                    @error('substitute_note')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" @disabled($mataKuliahList->isEmpty() || $dosenList->isEmpty()) class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="fa-solid fa-calendar-plus"></i> Simpan Jadwal
            </button>
        </form>
    </section>
</div>

<section class="mt-5 overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
        <h2 class="font-bold text-slate-900">Jadwal Mengajar</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">Mata kuliah</th>
                    <th class="px-4 py-3 font-semibold">Dosen pengampu</th>
                    <th class="px-4 py-3 font-semibold">Hari & waktu</th>
                    <th class="px-4 py-3 font-semibold">Lokasi kelas</th>
                    <th class="px-4 py-3 text-right font-semibold">Radius</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($jadwalList as $jadwal)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="block font-semibold text-slate-800">{{ $jadwal->mataKuliah->nama_mk }}</span>
                            <span class="font-mono text-xs text-slate-500">{{ $jadwal->mataKuliah->kode_mk }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block text-slate-800">{{ $jadwal->dosen->name }}</span>
                            <span class="text-xs text-slate-500">{{ $jadwal->dosen->nomor_induk }}</span>
                            @if($jadwal->is_substitute)
                                <span class="mt-1 inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">(Pengganti)</span>
                                @if($jadwal->substitute_note)
                                    <p class="mt-1 text-xs text-slate-600">{{ $jadwal->substitute_note }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $jadwal->latitude_kelas }}, {{ $jadwal->longitude_kelas }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $jadwal->radius_meter }} m</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada jadwal kuliah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($jadwalList->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $jadwalList->links() }}</div>
    @endif
</section>

<section class="mt-5 overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
        <h2 class="font-bold text-slate-900">Daftar Mata Kuliah</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[540px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">Kode</th>
                    <th class="px-4 py-3 font-semibold">Nama mata kuliah</th>
                    <th class="px-4 py-3 text-right font-semibold">Jumlah jadwal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mataKuliahList as $mataKuliah)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-teal-800">{{ $mataKuliah->kode_mk }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $mataKuliah->nama_mk }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $mataKuliah->jadwal_kuliah_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data mata kuliah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
    <script>
        const substituteCheckbox = document.getElementById('is_substitute');
        const substituteNoteField = document.getElementById('substitute-note-field');

        const toggleSubstituteNote = () => {
            substituteNoteField?.classList.toggle('hidden', !substituteCheckbox?.checked);
        };

        substituteCheckbox?.addEventListener('change', toggleSubstituteNote);
        toggleSubstituteNote();

        const locationButton = document.getElementById('use-current-location');
        const locationStatus = document.getElementById('location-status');

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
