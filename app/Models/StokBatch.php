<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu kali stok masuk (menu atau bumbu) beserta tanggal kadaluarsanya. */
class StokBatch extends Model
{
    protected $fillable = [
        'tipe', 'item_id', 'jumlah_awal', 'sisa', 'dibuang',
        'tanggal_masuk', 'tanggal_exp', 'dibuang_pada', 'keterangan',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_exp' => 'date',
        'dibuang_pada' => 'datetime',
    ];

    /** Sisa hari sampai exp (0 = exp hari ini, negatif = sudah lewat). */
    public function getSisaHariAttribute(): int
    {
        return (int) round(($this->tanggal_exp->copy()->startOfDay()->timestamp - today()->timestamp) / 86400);
    }
}
