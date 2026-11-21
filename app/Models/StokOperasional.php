<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sisa stok operasional milik satu kasir untuk satu bahan.
 * Kolom `bahan` berisi kode dari BahanOperasional.
 */
class StokOperasional extends Model
{
    protected $table = 'stok_operasionals';

    protected $fillable = ['user_id', 'bahan', 'stok'];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function master()
    {
        return $this->belongsTo(BahanOperasional::class, 'bahan', 'kode');
    }

    public function getLabelAttribute(): string
    {
        return $this->master->nama ?? $this->bahan;
    }

    public function getSatuanAttribute(): string
    {
        return $this->master->satuan ?? '';
    }

    public function getStatusStokAttribute(): string
    {
        if ($this->stok <= 0) {
            return 'habis';
        }

        return $this->stok <= (int) ($this->master->stok_menipis ?? 0) ? 'menipis' : 'aman';
    }

    /** Persentase isi bar (stok sekarang dibanding stok awal) */
    public function getPersenAttribute(): int
    {
        $awal = max((int) ($this->master->stok_awal ?? 1), 1);

        return (int) min(100, round($this->stok / $awal * 100));
    }
}
