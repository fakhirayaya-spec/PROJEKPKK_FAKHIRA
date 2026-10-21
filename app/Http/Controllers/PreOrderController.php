<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Kasir\TransaksiController;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\PreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pre-order (pesanan jauh hari / jumlah besar yang biasanya masuk lewat WhatsApp).
 * Dipakai bersama oleh admin dan kasir: nama rute menyesuaikan role (admin.preorder.* / kasir.preorder.*).
 */
class PreOrderController extends Controller
{
    private function rp(): string
    {
        return auth()->user()->role === 'admin' ? 'admin.preorder.' : 'kasir.preorder.';
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'aktif');
        $q = trim((string) $request->query('q', ''));

        $query = PreOrder::with(['items', 'pembuat'])->withCount('items');

        if ($status === 'aktif') {
            $query->aktif();
        } elseif (isset(PreOrder::LABEL_STATUS[$status])) {
            $query->where('status', $status);
        } else {
            $status = 'semua';
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nama_pelanggan', 'like', "%{$q}%")
                    ->orWhere('kode_preorder', 'like', "%{$q}%")
                    ->orWhere('no_whatsapp', 'like', "%{$q}%");
            });
        }

        // Yang paling dekat tanggal ambilnya tampil duluan (riwayat selesai/batal: terbaru dulu)
        if (in_array($status, ['selesai', 'dibatalkan', 'semua'], true)) {
            $query->orderByDesc('tanggal_ambil')->orderByDesc('id');
        } else {
            $query->orderBy('tanggal_ambil')->orderByRaw("jam_ambil IS NULL, jam_ambil")->orderBy('id');
        }

        $preorders = $query->paginate(12)->withQueryString();

        $hitung = PreOrder::selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $ambilHariIni = PreOrder::aktif()->whereDate('tanggal_ambil', today())->count();
        $terlambat = PreOrder::aktif()->whereDate('tanggal_ambil', '<', today())->count();

        return view('preorder.index', [
            'rp' => $this->rp(),
            'preorders' => $preorders,
            'status' => $status,
            'q' => $q,
            'hitung' => $hitung,
            'ambilHariIni' => $ambilHariIni,
            'terlambat' => $terlambat,
        ]);
    }

    public function create()
    {
        return view('preorder.create', $this->dataForm());
    }

    private function dataForm(): array
    {
        return [
            'rp' => $this->rp(),
            'menus' => Menu::aktif()->with(['sausBawaan', 'bubukBawaan'])->orderBy('nama_menu')->get(),
            'sausList' => Bumbu::aktif()->jenis('saus')->orderBy('nama')->pluck('nama'),
            'bubukList' => Bumbu::aktif()->jenis('tabur')->orderBy('nama')->pluck('nama'),
            'ukuranOptions' => TransaksiController::UKURAN_EXTRA,
            'hargaSausTambahan' => TransaksiController::HARGA_SAUS_TAMBAHAN,
            'tanpaSaus' => TransaksiController::TANPA_SAUS,
            'tanpaBubuk' => TransaksiController::TANPA_BUBUK,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'tanggal_ambil' => ['required', 'date', 'after_or_equal:today'],
            'jam_ambil' => ['nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'dp' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer'],
            'items.*.ukuran' => ['required', 'in:' . implode(',', array_keys(TransaksiController::UKURAN_EXTRA))],
            'items.*.saus' => ['nullable', 'string', 'max:100'],
            'items.*.bubuk_tabur' => ['nullable', 'string', 'max:100'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'items.required' => 'Tambahkan minimal satu menu ke pesanan.',
            'items.min' => 'Tambahkan minimal satu menu ke pesanan.',
            'no_whatsapp.regex' => 'Nomor WhatsApp hanya boleh angka (boleh diawali + atau 0), minimal 8 digit.',
            'tanggal_ambil.after_or_equal' => 'Tanggal ambil tidak boleh sebelum hari ini.',
        ]);

        $preorder = DB::transaction(function () use ($data) {
            $total = 0;
            $itemsSiap = [];

            foreach ($data['items'] as $item) {
                $siap = $this->olahItem($item);
                $total += $siap['subtotal'];
                $itemsSiap[] = $siap;
            }

            $dp = (int) ($data['dp'] ?? 0);
            if ($dp > $total) {
                throw ValidationException::withMessages([
                    'dp' => 'DP (Rp ' . number_format($dp, 0, ',', '.') . ') tidak boleh lebih besar dari total pesanan (Rp ' . number_format($total, 0, ',', '.') . ').',
                ]);
            }

            $preorder = PreOrder::create([
                'kode_preorder' => 'PO-' . now()->format('ymd') . '-' . str_pad((PreOrder::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'nama_pelanggan' => $data['nama_pelanggan'],
                'no_whatsapp' => $data['no_whatsapp'] ?? null,
                'tanggal_ambil' => $data['tanggal_ambil'],
                'jam_ambil' => $data['jam_ambil'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'status' => 'baru',
                'total' => $total,
                'dp' => $dp,
            ]);

            $preorder->items()->createMany($itemsSiap);

            return $preorder;
        });

        return redirect()->route($this->rp() . 'show', $preorder)->with('sukses', 'Pre-order ' . $preorder->kode_preorder . ' berhasil dicatat.');
    }

    /**
     * Tentukan saus/bubuk/harga tiap item di server (aturan sama dengan kasir):
     *  - menu bersaus  : saus terkunci, bubuk tabur opsional & gratis
     *  - menu berbubuk : bubuk terkunci, saus tambahan opsional (+Rp 3.000)
     *  - lainnya       : tanpa pilihan bumbu
     * Stok belum dipotong di sini; baru dipotong saat pre-order diselesaikan.
     */
    private function olahItem(array $item): array
    {
        $menu = Menu::aktif()->with(['sausBawaan', 'bubukBawaan'])->find($item['menu_id']);
        if (! $menu) {
            throw ValidationException::withMessages(['items' => 'Ada menu yang sudah tidak tersedia/nonaktif. Muat ulang halaman lalu coba lagi.']);
        }

        $sausTerkunci = (bool) $menu->saus_bawaan_id;
        $bubukTerkunci = (bool) $menu->bubuk_bawaan_id;
        $tambahan = 0;

        $labelSaus = null;
        if ($sausTerkunci) {
            $labelSaus = $menu->sausBawaan->nama ?? null;
        } elseif ($bubukTerkunci) {
            $pilih = trim((string) ($item['saus'] ?? ''));
            if ($pilih === '' || $pilih === TransaksiController::TANPA_SAUS) {
                $labelSaus = TransaksiController::TANPA_SAUS;
            } else {
                $this->pastikanBumbuAda('saus', $pilih);
                $labelSaus = $pilih;
                $tambahan = TransaksiController::HARGA_SAUS_TAMBAHAN;
            }
        }

        $labelBubuk = null;
        if ($bubukTerkunci) {
            $labelBubuk = $menu->bubukBawaan->nama ?? null;
        } elseif ($sausTerkunci) {
            $pilih = trim((string) ($item['bubuk_tabur'] ?? ''));
            if ($pilih === '' || $pilih === TransaksiController::TANPA_BUBUK) {
                $labelBubuk = TransaksiController::TANPA_BUBUK;
            } else {
                $this->pastikanBumbuAda('tabur', $pilih);
                $labelBubuk = $pilih;
            }
        }

        $hargaSatuan = $menu->harga + TransaksiController::UKURAN_EXTRA[$item['ukuran']] + $tambahan;

        return [
            'menu_id' => $menu->id,
            'nama_menu' => $menu->nama_menu,
            'ukuran' => $item['ukuran'],
            'saus' => $labelSaus,
            'bubuk_tabur' => $labelBubuk,
            'qty' => (int) $item['qty'],
            'harga_satuan' => $hargaSatuan,
            'modal_satuan' => (int) ($menu->harga_modal ?? 0),
            'subtotal' => $hargaSatuan * (int) $item['qty'],
        ];
    }

    private function pastikanBumbuAda(string $jenis, string $nama): void
    {
        if (! Bumbu::aktif()->jenis($jenis)->where('nama', $nama)->exists()) {
            throw ValidationException::withMessages(['items' => "\"{$nama}\" tidak tersedia. Muat ulang halaman lalu coba lagi."]);
        }
    }

    public function show(PreOrder $preorder)
    {
        $preorder->load(['items.menu', 'pembuat', 'transaksi']);

        return view('preorder.show', ['rp' => $this->rp(), 'po' => $preorder]);
    }

    /** Ubah data pelanggan / jadwal ambil / catatan / DP (isi pesanan tidak diubah di sini) */
    public function update(Request $request, PreOrder $preorder)
    {
        abort_unless($preorder->aktif, 403, 'Pre-order yang sudah selesai atau dibatalkan tidak bisa diubah.');

        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'tanggal_ambil' => ['required', 'date'],
            'jam_ambil' => ['nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'dp' => ['nullable', 'integer', 'min:0'],
        ], [
            'no_whatsapp.regex' => 'Nomor WhatsApp hanya boleh angka (boleh diawali + atau 0), minimal 8 digit.',
        ]);

        $data['dp'] = (int) ($data['dp'] ?? 0);
        if ($data['dp'] > $preorder->total) {
            throw ValidationException::withMessages([
                'dp' => 'DP tidak boleh lebih besar dari total pesanan (Rp ' . number_format($preorder->total, 0, ',', '.') . ').',
            ]);
        }

        $preorder->update($data);

        return redirect()->route($this->rp() . 'show', $preorder)->with('sukses', 'Data pre-order diperbarui.');
    }

    /** Maju satu tahap: baru -> diproses -> siap */
    public function status(Request $request, PreOrder $preorder)
    {
        $data = $request->validate(['ke' => ['required', 'in:diproses,siap']]);

        $urutan = ['baru' => 0, 'diproses' => 1, 'siap' => 2];
        abort_unless($preorder->aktif && $urutan[$data['ke']] > $urutan[$preorder->status], 403, 'Status tidak bisa diubah ke tahap itu.');

        $preorder->update(['status' => $data['ke']]);

        return redirect()->route($this->rp() . 'show', $preorder)
            ->with('sukses', 'Status diubah menjadi "' . PreOrder::LABEL_STATUS[$data['ke']] . '".');
    }

    /** Pesanan diambil & dilunasi -> otomatis jadi transaksi (masuk riwayat, laporan, dan stok berkurang) */
    public function selesai(Request $request, PreOrder $preorder)
    {
        abort_unless($preorder->aktif, 403, 'Pre-order ini sudah selesai atau dibatalkan.');

        $data = $request->validate([
            'metode_bayar' => ['required', 'in:cash,qris'],
            'uang_diterima' => ['nullable', 'integer', 'min:0'],
        ]);

        $transaksi = $preorder->selesaikan(auth()->user(), $data['metode_bayar'], $data['uang_diterima'] ?? null);

        $tujuan = auth()->user()->role === 'admin'
            ? route('admin.riwayat.show', $transaksi)
            : route('kasir.transaksi.struk', $transaksi);

        return redirect($tujuan)->with('sukses', 'Pre-order ' . $preorder->kode_preorder . ' selesai dan tercatat sebagai ' . $transaksi->kode_transaksi . '.');
    }

    public function batal(Request $request, PreOrder $preorder)
    {
        abort_unless($preorder->aktif, 403, 'Pre-order ini sudah selesai atau dibatalkan.');

        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:150'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan terlalu pendek.',
        ]);

        $preorder->update(['status' => 'dibatalkan', 'alasan_batal' => $data['alasan']]);

        return redirect()->route($this->rp() . 'show', $preorder)->with('sukses', 'Pre-order dibatalkan.');
    }
}
