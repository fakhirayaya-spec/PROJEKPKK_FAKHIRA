<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $fillable = ['tanggal', 'kategori', 'deskripsi', 'jumlah', 'user_id'];

    protected $casts = ['tanggal' => 'date'];

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Nama kasir yang dituju catatan otomatis ("Stok awal / Isi stok / Isi ulang ... untuk kasir <nama>").
     * Null = pengeluaran umum (diketik admin sendiri / tidak untuk kasir tertentu).
     */
    public function getNamaKasirAttribute(): ?string
    {
        if (preg_match('/^(?:Stok awal|Isi stok|Isi ulang) .+ untuk kasir (.+?)(?: \(data lama\))?$/u', (string) $this->deskripsi, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /** Hanya catatan otomatis "... untuk kasir <nama>" milik satu kasir (nama harus persis, jadi "Kasir potato 2" tidak ikut). */
    public function scopeUntukKasir($query, string $nama)
    {
        $aman = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $nama);

        return $query
            ->where(fn ($q) => $q
                ->where('deskripsi', 'like', 'Stok awal %')
                ->orWhere('deskripsi', 'like', 'Isi stok %')
                ->orWhere('deskripsi', 'like', 'Isi ulang %'))
            ->where(fn ($q) => $q
                ->where('deskripsi', 'like', '% untuk kasir ' . $aman)
                ->orWhere('deskripsi', 'like', '% untuk kasir ' . $aman . ' (data lama)'));
    }

    /** Pengeluaran umum: bukan catatan otomatis untuk kasir tertentu (diketik admin sendiri). */
    public function scopeUmum($query)
    {
        return $query->where(fn ($q) => $q
            ->where('deskripsi', 'not like', '% untuk kasir %')
            ->orWhere(fn ($r) => $r
                ->where('deskripsi', 'not like', 'Stok awal %')
                ->where('deskripsi', 'not like', 'Isi stok %')
                ->where('deskripsi', 'not like', 'Isi ulang %')));
    }

    public function scopeRentang($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    /**
     * Pengeluaran yang BUKAN bahan baku (operasional + lainnya).
     * Dipakai khusus untuk hitung Keuntungan Bersih — supaya biaya bahan
     * baku tidak terpotong dua kali: sekali otomatis lewat harga_modal
     * (HPP) tiap item yang terjual di Laba Kotor, dan sekali lagi di sini
     * kalau ikut dijumlahkan. Kategori "Bahan Baku" tetap dicatat & tetap
     * tampil di daftar Pengeluaran (untuk pelacakan kas keluar), hanya
     * saja tidak ikut mengurangi laba supaya tidak dihitung dua kali.
     */
    public function scopeOperasional($query)
    {
        return $query->whereIn('kategori', ['operasional', 'lainnya']);
    }
}
