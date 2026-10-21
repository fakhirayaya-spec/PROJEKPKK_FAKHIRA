<?php

namespace App\Models;

use App\Http\Controllers\Kasir\TransaksiController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreOrder extends Model
{
    public const STATUS_AKTIF = ['baru', 'diproses', 'siap'];

    public const LABEL_STATUS = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'siap' => 'Siap Diambil',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'kode_preorder', 'user_id', 'nama_pelanggan', 'no_whatsapp', 'tanggal_ambil', 'jam_ambil',
        'catatan', 'status', 'total', 'dp', 'transaksi_id', 'alasan_batal',
    ];

    protected $casts = [
        'tanggal_ambil' => 'date',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(PreOrderItem::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function scopeAktif($query)
    {
        return $query->whereIn('status', self::STATUS_AKTIF);
    }

    public function getAktifAttribute(): bool
    {
        return in_array($this->status, self::STATUS_AKTIF, true);
    }

    public function getLabelStatusAttribute(): string
    {
        return self::LABEL_STATUS[$this->status] ?? ucfirst($this->status);
    }

    /** Sisa yang harus dibayar pelanggan saat mengambil pesanan */
    public function getSisaAttribute(): int
    {
        return max(0, (int) $this->total - (int) $this->dp);
    }

    public function getJumlahPorsiAttribute(): int
    {
        return (int) $this->items->sum('qty');
    }

    /** Nomor WhatsApp dalam format internasional (62...) untuk link wa.me */
    public function getNomorWaAttribute(): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $this->no_whatsapp);
        if ($angka === '') {
            return null;
        }
        if (str_starts_with($angka, '0')) {
            $angka = '62' . substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62' . $angka;
        }

        return $angka;
    }

    public function getPesanWaAttribute(): string
    {
        $jam = $this->jam_ambil ? ' pukul ' . $this->jam_ambil : '';

        return "Halo kak {$this->nama_pelanggan}, ini Potato Curly Crunch. "
            . "Pre-order {$this->kode_preorder} untuk diambil " . $this->tanggal_ambil->translatedFormat('d F Y') . "{$jam} "
            . "statusnya: {$this->label_status}. Total Rp " . number_format($this->total, 0, ',', '.')
            . ($this->dp > 0 ? ', DP Rp ' . number_format($this->dp, 0, ',', '.') . ', sisa Rp ' . number_format($this->sisa, 0, ',', '.') : '')
            . '. Terima kasih!';
    }

    /**
     * Selesaikan pre-order: pesanan diserahkan & dilunasi, lalu otomatis dicatat sebagai transaksi
     * (masuk riwayat & laporan), stok menu + saus + bubuk berkurang, dan pergerakan stok dicatat.
     * Stok baru dipotong di sini (bukan saat pesanan dicatat) karena pesanan dibuat untuk hari lain.
     */
    public function selesaikan(User $oleh, string $metode, ?int $uangSisa): Transaksi
    {
        return DB::transaction(function () use ($oleh, $metode, $uangSisa) {
            $po = static::lockForUpdate()->findOrFail($this->id);

            if (! $po->aktif) {
                throw ValidationException::withMessages(['status' => 'Pre-order ini sudah ' . strtolower($po->label_status) . '.']);
            }

            $items = $po->items()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['status' => 'Pre-order tidak punya item.']);
            }

            // --- Pembayaran pelunasan ---
            $sisa = $po->sisa;
            $uangDiterima = null;
            $kembalian = null;
            if ($metode === 'cash') {
                $uangSisa = (int) $uangSisa;
                if ($uangSisa < $sisa) {
                    throw ValidationException::withMessages([
                        'uang_diterima' => 'Uang diterima kurang Rp ' . number_format($sisa - $uangSisa, 0, ',', '.') . ' dari sisa pembayaran Rp ' . number_format($sisa, 0, ',', '.') . '.',
                    ]);
                }
                $uangDiterima = (int) $po->dp + $uangSisa;   // total uang masuk: DP + pelunasan
                $kembalian = $uangSisa - $sisa;
            }

            // --- Cek stok dulu (semua item) sebelum ada yang dipotong ---
            $pakaiMenu = [];
            $pakaiBumbu = [];
            $menus = [];
            $bumbus = [];
            foreach ($items as $item) {
                $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
                if (! $menu) {
                    throw ValidationException::withMessages(['status' => "Menu \"{$item->nama_menu}\" sudah dihapus dari Master Menu, pre-order tidak bisa diselesaikan."]);
                }
                $menus[$item->id] = $menu;
                $pakaiMenu[$menu->id] = ($pakaiMenu[$menu->id] ?? 0) + $item->qty;
                if ($menu->stok < $pakaiMenu[$menu->id]) {
                    throw ValidationException::withMessages(['status' => "Stok {$menu->nama_menu} kurang (tersedia {$menu->stok}, dibutuhkan {$pakaiMenu[$menu->id]}). Tambah stok dulu di Manajemen Stok."]);
                }

                foreach ([
                    'saus' => ['saus', $item->saus, TransaksiController::TANPA_SAUS],
                    'bubuk' => ['tabur', $item->bubuk_tabur, TransaksiController::TANPA_BUBUK],
                ] as $kunci => [$jenis, $nama, $tanpa]) {
                    if (! $nama || $nama === $tanpa) {
                        continue;
                    }
                    $bumbu = Bumbu::lockForUpdate()->where('jenis', $jenis)->where('nama', $nama)->first();
                    if (! $bumbu) {
                        throw ValidationException::withMessages(['status' => "\"{$nama}\" sudah tidak ada di Manajemen Bumbu."]);
                    }
                    $bumbus[$item->id][$kunci] = $bumbu;
                    $pakaiBumbu[$bumbu->id] = ($pakaiBumbu[$bumbu->id] ?? 0) + $item->qty;
                    if ($bumbu->stok < $pakaiBumbu[$bumbu->id]) {
                        throw ValidationException::withMessages(['status' => "Stok {$bumbu->nama} kurang (tersedia {$bumbu->stok}, dibutuhkan {$pakaiBumbu[$bumbu->id]}). Tambah stok dulu di Manajemen Bumbu."]);
                    }
                }
            }

            // --- Catat transaksi ---
            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX-' . now()->format('ymd') . '-' . str_pad((Transaksi::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => $oleh->id,
                'metode_bayar' => $metode,
                'total' => $po->total,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
                'status' => 'selesai',
            ]);

            foreach ($items as $item) {
                TransaksiItem::create([
                    'transaksi_id' => $transaksi->id,
                    'menu_id' => $item->menu_id,
                    'ukuran' => $item->ukuran,
                    'saus' => $item->saus,
                    'bubuk_tabur' => $item->bubuk_tabur,
                    'qty' => $item->qty,
                    'harga_satuan' => $item->harga_satuan,
                    'modal_satuan' => $item->modal_satuan,
                    'subtotal' => $item->subtotal,
                ]);

                $menu = $menus[$item->id];
                $menu->decrement('stok', $item->qty);
                $menu->increment('total_terjual', $item->qty);

                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => $oleh->id,
                    'jenis' => 'keluar',
                    'jumlah' => $item->qty,
                    'keterangan' => 'Pre-order ' . $po->kode_preorder . ' (' . $transaksi->kode_transaksi . ')',
                    'tanggal' => today(),
                ]);

                foreach ($bumbus[$item->id] ?? [] as $bumbu) {
                    $bumbu->decrement('stok', $item->qty);
                }
            }

            $po->update(['status' => 'selesai', 'transaksi_id' => $transaksi->id]);

            return $transaksi;
        });
    }
}
