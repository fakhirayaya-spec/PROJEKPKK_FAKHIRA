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

    public function preOrderItems()
    {
        return $this->hasMany(PreOrderItem::class);
    }

    /** Alasan menu tidak boleh dihapus (null = boleh): sudah pernah dipesan lewat transaksi atau pre-order. */
    public function alasanTidakBisaDihapus(): ?string
    {
        $trx = $this->jml_transaksi ?? $this->transaksiItems()->count();
        $po = $this->jml_po ?? $this->preOrderItems()->count();

        if ($trx > 0 || $po > 0) {
            return 'Menu ini sudah pernah dipesan (' . collect([$trx ? "{$trx} transaksi" : null, $po ? "{$po} pre-order" : null])->filter()->implode(' dan ') . '), jadi tidak bisa dihapus. Ubah statusnya jadi Nonaktif saja.';
        }

        return null;
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

    /**
     * Foto menu. Kalau belum diunggah, tampilkan gambar placeholder polos (inisial menu) —
     * bukan gambar bawaan template.
     */
    public function getFotoUrlAttribute(): string
    {
        if ($this->gambar) {
            return asset('storage/' . $this->gambar);
        }

        $huruf = htmlspecialchars(mb_strtoupper(mb_substr(trim((string) $this->nama_menu), 0, 1)) ?: '?', ENT_QUOTES);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">'
            . '<rect width="120" height="120" fill="#fdf1d8"/>'
            . '<circle cx="60" cy="60" r="38" fill="#fcae1e"/>'
            . '<text x="60" y="60" text-anchor="middle" dominant-baseline="central" font-family="Arial,sans-serif" font-size="40" font-weight="700" fill="#3d2314">' . $huruf . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
