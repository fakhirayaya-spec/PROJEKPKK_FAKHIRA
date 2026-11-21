<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStokOperasional extends Model
{
    protected $table = 'riwayat_stok_operasionals';

    protected $fillable = [
        'user_id', 'bahan', 'jenis', 'jumlah', 'saldo',
        'transaksi_id', 'dilakukan_oleh', 'keterangan',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pelaku()
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh');
    }

    public function master()
    {
        return $this->belongsTo(BahanOperasional::class, 'bahan', 'kode');
    }

    public function getLabelBahanAttribute(): string
    {
        return $this->master->nama ?? $this->bahan;
    }

    public function getSatuanAttribute(): string
    {
        return $this->master->satuan ?? '';
    }

    public function getLabelJenisAttribute(): string
    {
        return match ($this->jenis) {
            'pakai' => 'Terpakai',
            'kembali' => 'Dikembalikan (batal)',
            'isi_ulang' => 'Isi ulang',
            default => $this->jenis,
        };
    }

    /** true kalau kejadian ini mengurangi stok */
    public function getMengurangiAttribute(): bool
    {
        return $this->jenis === 'pakai';
    }
}
