<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
          'kode_menu',
    'kategori_menu_id',
    'nama_menu',
    'deskripsi',
    'harga',
    'harga_modal',
    'saus_bawaan_id',
    'bubuk_bawaan_id',
    'gambar',
    'status',
    'total_terjual',
    'stok',
    'stok_minimum',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriMenu::class, 'kategori_menu_id');
    }

    public function sausBawaan()
    {
        return $this->belongsTo(Bumbu::class, 'saus_bawaan_id');
    }

    public function bubukBawaan()
    {
        return $this->belongsTo(Bumbu::class, 'bubuk_bawaan_id');
    }

    public function transaksiItems()
    {
        return $this->hasMany(TransaksiItem::class);
    }

    /** Laba kotor per porsi (harga jual - modal) */
    public function getLabaPerPorsiAttribute(): int
    {
        return (int) $this->harga - (int) $this->harga_modal;
    }

    public function pergerakanStoks()
{
    return $this->hasMany(PergerakanStok::class);
}

public function getStatusStokAttribute(): string
{
    if ($this->stok <= 0) {
        return 'habis';
    }
    if ($this->stok <= $this->stok_minimum) {
        return 'menipis';
    }
    return 'aman';
}

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
