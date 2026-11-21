<?php

namespace App\Models;

use App\Http\Controllers\Kasir\TransaksiController;
use App\Services\StokService;
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

    /** Minimal jumlah produk (pcs, semua menu dijumlah) dalam satu pre-order */
    public const MIN_PCS = 10;

    /** DP wajib minimal sekian persen dari total pembelian */
    public const DP_MIN_PERSEN = 50;

    protected $fillable = [
        'kode_preorder', 'user_id', 'nama_pelanggan', 'no_whatsapp', 'tanggal_ambil', 'jam_ambil',
        'catatan', 'status', 'stok_dipotong', 'total', 'dp', 'transaksi_id', 'alasan_batal',
    ];

    protected $casts = [
        'tanggal_ambil' => 'date',
        'stok_dipotong' => 'boolean',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(PreOrderItem::class);
    }

    /** Transaksi pelunasan (terisi saat pre-order diselesaikan) */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    /** Semua catatan pembayaran PO ini: DP (bisa lebih dari satu) dan pelunasan */
    public function pembayaran()
    {
        return $this->hasMany(Transaksi::class, 'pre_order_id');
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

    /** DP minimal (50% dari total, dibulatkan ke atas ke rupiah) */
    public static function dpMinimal(int $total): int
    {
        return (int) ceil($total * self::DP_MIN_PERSEN / 100);
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
     * Catat DP (uang muka) sebagai pemasukan: dibuat sebagai transaksi berjenis "dp" sebesar DP saja,
     * sehingga Dashboard & Laporan langsung bertambah sebesar DP di tanggal DP diterima.
     */
    public function catatDp(User $oleh, int $jumlah, string $metode): ?Transaksi
    {
        if ($jumlah <= 0) {
            return null;
        }

        $urut = Transaksi::where('pre_order_id', $this->id)->where('jenis', 'dp')->count() + 1;

        return Transaksi::create([
            'kode_transaksi' => 'DP-' . $this->kode_preorder . '-' . $urut,
            'jenis' => 'dp',
            'pre_order_id' => $this->id,
            'user_id' => $oleh->id,
            'metode_bayar' => $metode,
            'total' => $jumlah,
            'uang_diterima' => $metode === 'cash' ? $jumlah : null,
            'kembalian' => $metode === 'cash' ? 0 : null,
            'status' => 'selesai',
        ]);
    }

    /** Tandai sedang diproses. Stok sudah berkurang sejak pesanan dibuat; PO lama yang belum terpotong dipotong di sini. */
    public function proses(User $oleh): void
    {
        DB::transaction(function () use ($oleh) {
            $po = static::lockForUpdate()->findOrFail($this->id);

            if ($po->status !== 'baru') {
                throw ValidationException::withMessages(['status' => 'Pre-order ini sudah tidak berstatus Baru.']);
            }

            $po->potongStokJikaBelum($oleh);
            $po->update(['status' => 'diproses']);
        });

        $this->refresh();
    }

    /** Tandai siap diambil. */
    public function tandaiSiap(User $oleh): void
    {
        DB::transaction(function () use ($oleh) {
            $po = static::lockForUpdate()->findOrFail($this->id);

            if (! in_array($po->status, ['baru', 'diproses'], true)) {
                throw ValidationException::withMessages(['status' => 'Status pre-order tidak bisa diubah ke "Siap Diambil".']);
            }

            $po->potongStokJikaBelum($oleh);
            $po->update(['status' => 'siap']);
        });

        $this->refresh();
    }

    /**
     * Potong stok semua item (menu + saus + bubuk). Dicek dulu semuanya sebelum ada yang dipotong.
     * Aman dipanggil berulang: kalau stok sudah pernah dipotong, tidak melakukan apa-apa.
     * Harus dipanggil di dalam DB::transaction oleh pemanggilnya.
     */
    public function potongStokJikaBelum(User $oleh): void
    {
        if ($this->stok_dipotong) {
            return;
        }

        $items = $this->items()->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['status' => 'Pre-order tidak punya item.']);
        }

        $pakaiMenu = [];
        $pakaiBumbu = [];
        $menus = [];
        $bumbus = [];

        foreach ($items as $item) {
            $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
            if (! $menu) {
                throw ValidationException::withMessages(['status' => "Menu \"{$item->nama_menu}\" sudah dihapus dari Master Menu, pre-order tidak bisa diproses."]);
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

        foreach ($items as $item) {
            $menu = $menus[$item->id];
            StokService::keluar($menu, (int) $item->qty);

            PergerakanStok::create([
                'menu_id' => $menu->id,
                'user_id' => $oleh->id,
                'jenis' => 'keluar',
                'jumlah' => $item->qty,
                'keterangan' => 'Pre-order ' . $this->kode_preorder . ' diproses',
                'tanggal' => today(),
            ]);

            foreach ($bumbus[$item->id] ?? [] as $bumbu) {
                StokService::keluar($bumbu, (int) $item->qty);
            }
        }

        $this->update(['stok_dipotong' => true]);
    }

    /** Kembalikan stok yang sudah dipotong (dipakai saat pre-order dibatalkan). */
    private function kembalikanStok(User $oleh): void
    {
        foreach ($this->items()->get() as $item) {
            $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
            if ($menu) {
                StokService::kembali($menu, (int) $item->qty);

                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => $oleh->id,
                    'jenis' => 'masuk',
                    'jumlah' => $item->qty,
                    'keterangan' => 'Batal pre-order ' . $this->kode_preorder,
                    'tanggal' => today(),
                ]);
            }

            foreach ([
                ['saus', $item->saus, TransaksiController::TANPA_SAUS],
                ['tabur', $item->bubuk_tabur, TransaksiController::TANPA_BUBUK],
            ] as [$jenis, $nama, $tanpa]) {
                if ($nama && $nama !== $tanpa) {
                    $bumbu = Bumbu::lockForUpdate()->where('jenis', $jenis)->where('nama', $nama)->first();
                    if ($bumbu) {
                        StokService::kembali($bumbu, (int) $item->qty);
                    }
                }
            }
        }

        $this->update(['stok_dipotong' => false]);
    }

    /**
     * Batalkan pre-order. DP TIDAK dikembalikan ke pelanggan, jadi tetap tercatat sebagai pemasukan.
     * Stok dikembalikan hanya kalau pesanan masih "Baru" (bahan belum dipakai). Kalau sudah
     * diproses / siap, bahan dianggap sudah terpakai sehingga stok tidak dikembalikan.
     */
    public function batalkan(User $oleh, string $alasan): void
    {
        DB::transaction(function () use ($oleh, $alasan) {
            $po = static::lockForUpdate()->findOrFail($this->id);

            if (! $po->aktif) {
                throw ValidationException::withMessages(['status' => 'Pre-order ini sudah ' . strtolower($po->label_status) . '.']);
            }

            if ($po->stok_dipotong && $po->status === 'baru') {
                $po->kembalikanStok($oleh);
            }

            $po->update(['status' => 'dibatalkan', 'alasan_batal' => $alasan]);
        });

        $this->refresh();
    }

    /**
     * Pelunasan: pesanan diambil & sisa dibayar. Dicatat sebagai transaksi berjenis "pelunasan" sebesar
     * SISA saja (DP sudah masuk lebih dulu), lengkap dengan item-itemnya untuk laporan menu & laba.
     * Stok sudah berkurang saat "Proses"; kalau PO lama belum pernah dipotong, dipotong di sini.
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
                $uangDiterima = $uangSisa;
                $kembalian = $uangSisa - $sisa;
            }

            $menus = [];
            foreach ($items as $item) {
                $menu = $item->menu_id ? Menu::lockForUpdate()->find($item->menu_id) : null;
                if (! $menu) {
                    throw ValidationException::withMessages(['status' => "Menu \"{$item->nama_menu}\" sudah dihapus dari Master Menu, pre-order tidak bisa diselesaikan."]);
                }
                $menus[$item->id] = $menu;
            }

            // Pre-order lama yang belum pernah diproses lewat alur baru: potong stok sekarang
            $po->potongStokJikaBelum($oleh);

            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX-' . now()->format('ymd') . '-' . str_pad((Transaksi::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
                'jenis' => 'pelunasan',
                'pre_order_id' => $po->id,
                'user_id' => $oleh->id,
                'metode_bayar' => $metode,
                'total' => $sisa,
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

                $menus[$item->id]->increment('total_terjual', $item->qty);
            }

            $po->update(['status' => 'selesai', 'transaksi_id' => $transaksi->id]);

            return $transaksi;
        });
    }
}
