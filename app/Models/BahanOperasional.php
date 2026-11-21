<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master bahan operasional (gas, minyak, dus, cup, es batu, dan tambahan lain dari admin).
 */
class BahanOperasional extends Model
{
    protected $table = 'bahan_operasionals';

    /** Pilihan "berkurang berdasarkan apa" */
    public const DASAR = [
        'kentang' => 'Porsi kentang',
        'minuman' => 'Minuman',
        'semua_item' => 'Semua item (kentang + minuman)',
        'transaksi' => 'Tiap transaksi',
    ];

    /** Kategori yang dipakai saat biaya bahan ini dicatat di Pengeluaran */
    public const KATEGORI_PENGELUARAN = [
        'operasional' => 'Operasional',
        'bahan_baku' => 'Bahan Baku',
    ];

    protected $fillable = [
        'kode', 'nama', 'satuan', 'stok_awal', 'stok_menipis',
        'dasar_pakai', 'per_kelompok', 'unit_isi', 'isi_per_unit',
        'harga_per_unit', 'kategori_pengeluaran', 'gambar', 'ikon', 'status', 'urutan',
    ];

    public function stokKasir()
    {
        return $this->hasMany(StokOperasional::class, 'bahan', 'kode');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatStokOperasional::class, 'bahan', 'kode');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function getGambarUrlAttribute(): ?string
    {
        if (! $this->gambar) {
            return null;
        }

        // lewat route aplikasi (tidak perlu storage:link); ?v= supaya gambar baru langsung tampil
        return route('bahan-operasional.gambar', $this) . '?v=' . optional($this->updated_at)->timestamp;
    }

    /** Penjelasan singkat cara bahan ini berkurang, untuk ditampilkan di kartu */
    public function getAturanAttribute(): string
    {
        $n = max((int) $this->per_kelompok, 1);

        if ($this->dasar_pakai === 'transaksi') {
            return "Berkurang 1 {$this->satuan} tiap transaksi";
        }

        $objek = match ($this->dasar_pakai) {
            'kentang' => 'porsi kentang',
            'minuman' => 'minuman',
            default => 'item',
        };

        return $n === 1
            ? "Berkurang 1 {$this->satuan} tiap 1 {$objek}"
            : "Berkurang 1 {$this->satuan} tiap {$n} {$objek} (per transaksi)";
    }

    /** Angka awal yang muncul di kolom isi ulang: 1 kalau 1 unit = banyak stok, selain itu stok awal */
    public function getDefaultIsiAttribute(): int
    {
        return $this->isi_per_unit > 1 ? 1 : max((int) $this->stok_awal, 1);
    }

    /** Teks hasil isi ulang, mis. "1 tabung = 50 order" */
    public function getInfoIsiAttribute(): string
    {
        return $this->isi_per_unit > 1
            ? "1 {$this->unit_isi} = {$this->isi_per_unit} {$this->satuan}"
            : "dalam {$this->satuan}";
    }
}
