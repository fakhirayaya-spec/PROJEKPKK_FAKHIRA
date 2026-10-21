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
