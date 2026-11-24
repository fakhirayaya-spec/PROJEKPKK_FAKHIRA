<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengajuan stok dari kasir ke admin (bahan operasional, menu, atau bumbu).
 * Stok baru bertambah setelah admin menyetujui (lihat Admin\PermintaanStokController).
 */
class PermintaanStok extends Model
{
    protected $table = 'permintaan_stoks';

    public const JENIS = [
        'operasional' => 'Bahan operasional',
        'menu' => 'Menu / produk',
        'bumbu' => 'Bumbu (saus & tabur)',
    ];

    protected $fillable = [
        'user_id', 'jenis', 'item_id', 'bahan_kode', 'nama_item', 'satuan', 'jumlah', 'catatan',
        'status', 'jumlah_disetujui', 'ditangani_oleh', 'ditangani_pada', 'catatan_admin',
    ];

    protected $casts = [
        'ditangani_pada' => 'datetime',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penangan()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    public function scopeMenunggu($q)
    {
        return $q->where('status', 'menunggu');
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }
}
