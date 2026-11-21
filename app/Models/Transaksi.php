<?php

namespace App\Models;

use App\Http\Controllers\Kasir\TransaksiController;
use App\Services\StokOperasionalService;
use App\Services\StokService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_transaksi',
        'jenis',
        'pre_order_id',
        'user_id',
        'metode_bayar',
        'total',
        'uang_diterima',
        'kembalian',
        'status',
        'alasan_batal',
        'dibatalkan_oleh',
        'dibatalkan_pada',
    ];

    protected $casts = [
        'dibatalkan_pada' => 'datetime',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembatal()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function items()
    {
        return $this->hasMany(TransaksiItem::class);
    }

    /** Pre-order yang diselesaikan lewat transaksi ini (transaksi pelunasan) */
    public function preOrder()
    {
        return $this->hasOne(PreOrder::class);
    }

    /** Pre-order asal pembayaran ini (untuk catatan DP maupun pelunasan) */
    public function preOrderAsal()
    {
        return $this->belongsTo(PreOrder::class, 'pre_order_id');
    }

    public function scopeHariIni($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeMilikKasir($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getDibatalkanAttribute(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /**
     * Hanya KASIR yang boleh membatalkan, dengan alasan yang jelas.
     * Itu pun hanya transaksinya sendiri dan hanya di hari yang sama. Admin hanya melihat.
     */
    public function bisaDibatalkanOleh(User $user): bool
    {
        if ($this->status !== 'selesai') {
            return false;
        }

        // DP & pelunasan pre-order dikelola lewat halaman Pre-order (batalkan PO), bukan dari struk
        if (($this->jenis ?? 'penjualan') !== 'penjualan') {
            return false;
        }

        return $user->role === 'kasir'
            && $this->user_id === $user->id
            && $this->created_at->isToday();
    }

    /**
     * Batalkan transaksi: status jadi "dibatalkan", stok menu + saus + bubuk dikembalikan,
     * dan pergerakan stok "masuk" dicatat. Semuanya dalam satu transaksi database.
     */
    public function batalkan(User $oleh, string $alasan): void
    {
        DB::transaction(function () use ($oleh, $alasan) {
            $trx = static::lockForUpdate()->findOrFail($this->id);

            if ($trx->status !== 'selesai') {
                throw ValidationException::withMessages(['alasan' => 'Transaksi ini sudah dibatalkan sebelumnya.']);
            }

            foreach ($trx->items()->get() as $item) {
                $menu = Menu::lockForUpdate()->find($item->menu_id);
                if ($menu) {
                    StokService::kembali($menu, (int) $item->qty);
                    $menu->decrement('total_terjual', min((int) $menu->total_terjual, (int) $item->qty));

                    PergerakanStok::create([
                        'menu_id' => $menu->id,
                        'user_id' => $oleh->id,
                        'jenis' => 'masuk',
                        'jumlah' => $item->qty,
                        'keterangan' => 'Batal ' . $trx->kode_transaksi,
                        'tanggal' => today(),
                    ]);
                }

                // Kembalikan stok saus & bubuk tabur yang tadi terpakai
                foreach ([
                    ['saus', $item->saus, TransaksiController::TANPA_SAUS],
                    ['tabur', $item->bubuk_tabur, TransaksiController::TANPA_BUBUK],
                ] as [$jenis, $nama, $tanpa]) {
                    if ($nama && $nama !== $tanpa) {
                        $bumbu = Bumbu::where('jenis', $jenis)->where('nama', $nama)->lockForUpdate()->first();
                        if ($bumbu) {
                            StokService::kembali($bumbu, (int) $item->qty);
                        }
                    }
                }
            }

            // Kembalikan stok operasional kasir (gas, minyak, dus, cup, es batu) yang tadi terpakai
            StokOperasionalService::kembalikanTransaksi($trx, $oleh);

            $trx->update([
                'status' => 'dibatalkan',
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $oleh->id,
                'dibatalkan_pada' => now(),
            ]);
        });

        $this->refresh();
    }
}
