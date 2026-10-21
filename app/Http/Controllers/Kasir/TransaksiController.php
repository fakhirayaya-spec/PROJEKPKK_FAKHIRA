<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    // Tambahan harga per pilihan ukuran (dihitung di server biar tidak bisa dimanipulasi dari luar)
    public const UKURAN_EXTRA = [
        'Reguler' => 0,
        'Jumbo' => 5000,
    ];

    // Biaya kalau pembeli menambah saus di menu yang bumbu tabur-nya sudah bawaan
    public const HARGA_SAUS_TAMBAHAN = 3000;

    public const TANPA_SAUS = 'Tanpa Saus Tambahan';
    public const TANPA_BUBUK = 'Tanpa Bubuk Tabur';

    public function index()
    {
        $menus = Menu::aktif()->with(['kategori', 'sausBawaan', 'bubukBawaan'])->where('stok', '>', 0)->orderBy('nama_menu')->get();

        // Pilihan saus & bubuk tabur diambil dari Manajemen Bumbu (hanya yang aktif dan masih ada stok)
        $sausList = Bumbu::tersedia()->jenis('saus')->orderBy('nama')->get(['nama', 'stok', 'satuan']);
        $bubukList = Bumbu::tersedia()->jenis('tabur')->orderBy('nama')->get(['nama', 'stok', 'satuan']);

        // Stok semua bumbu aktif (termasuk bumbu bawaan menu) untuk pengecekan di keranjang
        $stokBumbu = Bumbu::aktif()->pluck('stok', 'nama');

        return view('kasir.transaksi.index', [
            'stokBumbu' => $stokBumbu,
            'hargaSausTambahan' => self::HARGA_SAUS_TAMBAHAN,
            'menus' => $menus,
            'ukuranOptions' => self::UKURAN_EXTRA,
            'sausList' => $sausList,
            'bubukList' => $bubukList,
            'tanpaSaus' => self::TANPA_SAUS,
            'tanpaBubuk' => self::TANPA_BUBUK,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'metode_bayar' => ['required', 'in:cash,qris,paylater'],
            'uang_diterima' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'exists:menus,id'],
            'items.*.ukuran' => ['required', 'in:' . implode(',', array_keys(self::UKURAN_EXTRA))],
            'items.*.saus' => ['nullable', 'string', 'max:100'],
            'items.*.bubuk_tabur' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $transaksi = DB::transaction(function () use ($validated) {
            $total = 0;
            $itemsSiap = [];
            $pakaiMenu = [];   // total qty per menu di keranjang ini
            $pakaiBumbu = [];  // total pemakaian per bumbu di keranjang ini (1 porsi tiap qty)

            foreach ($validated['items'] as $item) {
                $menu = Menu::lockForUpdate()->findOrFail($item['menu_id']);

                $pakaiMenu[$menu->id] = ($pakaiMenu[$menu->id] ?? 0) + $item['qty'];
                if ($menu->stok < $pakaiMenu[$menu->id]) {
                    abort(422, "Stok {$menu->nama_menu} tidak cukup.");
                }

                // Aturan bumbu per menu (server yang menentukan, bukan kiriman dari browser):
                //  - menu bersaus  : saus terkunci, bubuk tabur boleh dipilih (gratis)
                //  - menu berbubuk : bubuk terkunci, saus tambahan boleh dipilih (+Rp 3.000)
                //  - menu tanpa bumbu bawaan (mis. minuman): tidak ada pilihan
                $sausTerkunci = (bool) $menu->saus_bawaan_id;
                $bubukTerkunci = (bool) $menu->bubuk_bawaan_id;
                $bisaPilihBubuk = $sausTerkunci && ! $bubukTerkunci;
                $bisaTambahSaus = $bubukTerkunci && ! $sausTerkunci;

                $tambahanSaus = 0;

                if ($sausTerkunci) {
                    $saus = $this->pakaiBawaan($menu->sausBawaan, $item['qty'], $pakaiBumbu);
                    $labelSaus = $saus->nama;
                } elseif ($bisaTambahSaus) {
                    $labelSaus = $item['saus'] ?: self::TANPA_SAUS;
                    $saus = $this->ambilBumbu('saus', $labelSaus, self::TANPA_SAUS, $item['qty'], $pakaiBumbu);
                    $tambahanSaus = $saus ? self::HARGA_SAUS_TAMBAHAN : 0;
                } else {
                    $saus = null;
                    $labelSaus = null;
                }

                if ($bubukTerkunci) {
                    $bubuk = $this->pakaiBawaan($menu->bubukBawaan, $item['qty'], $pakaiBumbu);
                    $labelBubuk = $bubuk->nama;
                } elseif ($bisaPilihBubuk) {
                    $labelBubuk = $item['bubuk_tabur'] ?: self::TANPA_BUBUK;
                    $bubuk = $this->ambilBumbu('tabur', $labelBubuk, self::TANPA_BUBUK, $item['qty'], $pakaiBumbu);
                } else {
                    $bubuk = null;
                    $labelBubuk = null;
                }

                $hargaSatuan = $menu->harga + self::UKURAN_EXTRA[$item['ukuran']] + $tambahanSaus;
                $subtotal = $hargaSatuan * $item['qty'];
                $total += $subtotal;

                $itemsSiap[] = [
                    'menu' => $menu,
                    'ukuran' => $item['ukuran'],
                    'saus' => $labelSaus,
                    'bubuk_tabur' => $labelBubuk,
                    'saus_model' => $saus,
                    'bubuk_model' => $bubuk,
                    'qty' => $item['qty'],
                    'harga_satuan' => $hargaSatuan,
                    'modal_satuan' => (int) ($menu->harga_modal ?? 0),
                    'subtotal' => $subtotal,
                ];
            }

            // Pembayaran cash: uang diterima wajib & harus cukup. Total dihitung server, bukan dari browser.
            $uangDiterima = null;
            $kembalian = null;
            if ($validated['metode_bayar'] === 'cash') {
                $uangDiterima = (int) ($validated['uang_diterima'] ?? 0);
                if ($uangDiterima < $total) {
                    throw ValidationException::withMessages([
                        'uang_diterima' => 'Uang diterima kurang Rp ' . number_format($total - $uangDiterima, 0, ',', '.') . ' dari total Rp ' . number_format($total, 0, ',', '.') . '.',
                    ]);
                }
                $kembalian = $uangDiterima - $total;
            }

            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX-' . now()->format('ymd') . '-' . str_pad((Transaksi::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'metode_bayar' => $validated['metode_bayar'],
                'total' => $total,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
                'status' => 'selesai',
            ]);

            foreach ($itemsSiap as $item) {
                TransaksiItem::create([
                    'transaksi_id' => $transaksi->id,
                    'menu_id' => $item['menu']->id,
                    'ukuran' => $item['ukuran'],
                    'saus' => $item['saus'],
                    'bubuk_tabur' => $item['bubuk_tabur'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'modal_satuan' => $item['modal_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Kurangi stok & catat pergerakan stok keluar
                $item['menu']->decrement('stok', $item['qty']);
                $item['menu']->increment('total_terjual', $item['qty']);

                PergerakanStok::create([
                    'menu_id' => $item['menu']->id,
                    'user_id' => auth()->id(),
                    'jenis' => 'keluar',
                    'jumlah' => $item['qty'],
                    'keterangan' => 'Transaksi ' . $transaksi->kode_transaksi,
                    'tanggal' => today(),
                ]);

                // Kurangi stok saus & bumbu tabur yang dipilih (1 porsi per qty)
                foreach (['saus_model', 'bubuk_model'] as $kunci) {
                    if ($item[$kunci]) {
                        $item[$kunci]->decrement('stok', $item['qty']);
                    }
                }
            }

            return $transaksi;
        });

        return redirect()->route('kasir.transaksi.struk', $transaksi)->with('sukses', 'Transaksi berhasil disimpan.');
    }

    /**
     * Pakai bumbu bawaan menu (saus/bubuk yang terkunci) & pastikan aktif dan stoknya cukup.
     */
    private function pakaiBawaan(?Bumbu $bawaan, int $qty, array &$pakai): Bumbu
    {
        abort_if(! $bawaan, 422, 'Bumbu bawaan menu tidak ditemukan. Hubungi admin.');

        $bumbu = Bumbu::lockForUpdate()->find($bawaan->id);
        abort_if(! $bumbu || $bumbu->status !== 'aktif', 422, "\"{$bawaan->nama}\" sedang nonaktif, menu ini belum bisa dijual.");

        $pakai[$bumbu->id] = ($pakai[$bumbu->id] ?? 0) + $qty;
        abort_if($bumbu->stok < $pakai[$bumbu->id], 422, "Stok {$bumbu->nama} tidak cukup.");

        return $bumbu;
    }

    /**
     * Cari bumbu yang dipilih kasir & pastikan stoknya cukup.
     * Mengembalikan null kalau kasir memilih "Tanpa ...".
     */
    private function ambilBumbu(string $jenis, string $nama, string $labelTanpa, int $qty, array &$pakai): ?Bumbu
    {
        if ($nama === $labelTanpa) {
            return null;
        }

        $bumbu = Bumbu::aktif()->jenis($jenis)->where('nama', $nama)->lockForUpdate()->first();
        abort_if(! $bumbu, 422, "\"{$nama}\" tidak tersedia. Muat ulang halaman transaksi.");

        $pakai[$bumbu->id] = ($pakai[$bumbu->id] ?? 0) + $qty;
        abort_if($bumbu->stok < $pakai[$bumbu->id], 422, "Stok {$bumbu->nama} tidak cukup.");

        return $bumbu;
    }

    public function struk(Transaksi $transaksi)
    {
        // Kasir hanya boleh lihat struk transaksi miliknya sendiri
        abort_unless($transaksi->user_id === auth()->id(), 403);

        $transaksi->load('items.menu', 'kasir', 'pembatal');
        $bisaBatal = $transaksi->bisaDibatalkanOleh(auth()->user());

        return view('kasir.transaksi.struk', compact('transaksi', 'bisaBatal'));
    }

    public function batal(Request $request, Transaksi $transaksi)
    {
        abort_unless($transaksi->user_id === auth()->id(), 403);
        abort_unless($transaksi->bisaDibatalkanOleh(auth()->user()), 403, 'Transaksi ini tidak bisa dibatalkan (hanya transaksi milikmu di hari yang sama).');

        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:10', 'max:150'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan harus jelas (minimal 10 karakter), misalnya: salah pilih menu, pelanggan tidak jadi membeli.',
        ]);

        $transaksi->batalkan(auth()->user(), $data['alasan']);

        return redirect()->route('kasir.transaksi.struk', $transaksi)
            ->with('sukses', 'Transaksi dibatalkan. Stok sudah dikembalikan.');
    }
}
