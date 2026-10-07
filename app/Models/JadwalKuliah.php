<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalKuliah extends Model
{
    use HasFactory;

    public const MAX_RADIUS_METERS = 5;

    protected $table = 'jadwal_kuliah';

    protected $fillable = [
        'dosen_id',
        'mata_kuliah_id',
        'kelas_id',
        'kelas',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'latitude_kelas',
        'longitude_kelas',
        'radius_meter',
        'is_substitute',
        'substitute_note',
    ];

    protected $casts = [
        'latitude_kelas' => 'float',
        'longitude_kelas' => 'float',
        'radius_meter' => 'integer',
        'is_substitute' => 'boolean',
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function kelasData(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function pertemuan(): HasMany
    {
        return $this->hasMany(Pertemuan::class, 'jadwal_kuliah_id');
    }
}
