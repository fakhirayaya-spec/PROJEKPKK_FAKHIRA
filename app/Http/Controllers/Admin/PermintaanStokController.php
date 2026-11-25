<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\PermintaanStok;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Admin menyetujui / menolak pengajuan stok dari kasir. Stok baru bertambah setelah kasir mengonfirmasi barang diterima. */
class PermintaanStokController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'menunggu');

        $daftar = PermintaanStok::with(['kasir', 'penangan'])
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'menunggu' THEN 0 ELSE 1 END")
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Harga per unit bahan operasional dipakai untuk mengisi biaya otomatis di form setuju
        $hargaBahan = BahanOperasional::pluck('harga_per_unit', 'kode');

        return view('admin.permintaan-stok.index', [
            'hargaMenu' => Menu::pluck('harga_modal', 'id'),
            'hargaBumbu' => Bumbu::all()->mapWithKeys(fn ($b) => [$b->id => $b->harga_bungkus_efektif]),
            'isiBumbu' => Bumbu::all()->mapWithKeys(fn ($b) => [$b->id => $b->isi_bungkus_efektif]),
            'daftar' => $daftar,
            'status' => $status,
            'hitung' => PermintaanStok::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'hargaBahan' => $hargaBahan,
        ]);
    }

    public function setujui(Request $request, PermintaanStok $permintaan)
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'biaya' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'catatan_admin' => ['nullable', 'string', 'max:200'],
        ], [
            'jumlah.required' => 'Jumlah yang disetujui wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
        ]);

        [$pesan, $biayaCatat] = DB::transaction(function () use ($permintaan, $data) {
            $p = PermintaanStok::lockForUpdate()->findOrFail($permintaan->id);

            if ($p->status !== 'menunggu') {
                throw ValidationException::withMessages(['jumlah' => 'Pengajuan ini sudah ditangani sebelumnya.']);
            }

            $jumlah = (int) $data['jumlah'];
            $biaya = (int) ($data['biaya'] ?? 0);
            $kasir = $p->kasir;
            $admin = auth()->user();

            // Admin hanya menyetujui (dan mencatat biaya belanja). Stok BARU masuk setelah kasir
            // mengonfirmasi barangnya sudah diterima (Kasir\PermintaanStokController@konfirmasi).
            if ($p->jenis === 'operasional') {
                // Sama seperti isi ulang manual: biaya wajib supaya tercatat di Pengeluaran
                if ($biaya < 1) {
                    throw ValidationException::withMessages(['biaya' => 'Biaya pembelian wajib diisi untuk bahan operasional supaya tercatat di Pengeluaran.']);
                }

                $bahan = BahanOperasional::aktif()->where('kode', $p->bahan_kode)->first();
                if (! $bahan) {
                    throw ValidationException::withMessages(['jumlah' => "Bahan {$p->nama_item} sudah nonaktif atau dihapus, pengajuan tidak bisa disetujui."]);
                }

                Pengeluaran::create([
                    'tanggal' => today(),
                    'kategori' => $bahan->kategori_pengeluaran ?: 'operasional',
                    'deskripsi' => Str::limit("Isi ulang {$bahan->nama}: {$jumlah} {$bahan->unit_isi} untuk kasir {$kasir->name}", 150, ''),
                    'jumlah' => $biaya,
                    'user_id' => $admin->id,
                ]);

                $biayaTercatat = $biaya;
                $hasil = "{$bahan->nama} untuk {$kasir->name}";
            } elseif ($p->jenis === 'menu') {
                $menu = Menu::find($p->item_id);
                if (! $menu) {
                    throw ValidationException::withMessages(['jumlah' => "Menu {$p->nama_item} sudah dihapus, pengajuan tidak bisa disetujui."]);
                }

                // Biaya otomatis = jumlah x harga modal menu (kecuali admin mengisi angka lain)
                $biayaTercatat = $biaya > 0 ? $biaya : $jumlah * (int) $menu->harga_modal;
                if ($biaya > 0) {
                    $this->catatBiayaBahanBaku($biaya, "Restock {$menu->nama_menu}: {$jumlah} porsi (pengajuan kasir {$kasir->name})", $admin->id);
                } else {
                    StokService::catatBiayaMenu($menu, $jumlah, (int) $kasir->id, $admin->id);
                }
                $hasil = "{$menu->nama_menu} {$jumlah} porsi untuk {$kasir->name}";
            } else {
                $bumbu = Bumbu::find($p->item_id);
                if (! $bumbu) {
                    throw ValidationException::withMessages(['jumlah' => "Bumbu {$p->nama_item} sudah dihapus, pengajuan tidak bisa disetujui."]);
                }

                // Biaya otomatis: bungkus x harga bungkus (pengajuan lama dalam porsi: porsi x harga per pakai).
                // Admin boleh mengisi angka lain.
                $perBungkus = $p->satuan === 'bungkus';
                $biayaBumbu = $biaya > 0 ? $biaya : ($perBungkus ? $jumlah * $bumbu->harga_bungkus_efektif : $bumbu->biayaUntuk($jumlah));
                $satuanTeks = $perBungkus ? 'bungkus' : $bumbu->satuan;
                $biayaTercatat = $biayaBumbu;
                $this->catatBiayaBahanBaku($biayaBumbu, "Restock {$bumbu->nama}: {$jumlah} {$satuanTeks} (pengajuan kasir {$kasir->name})", $admin->id);
                $hasil = "{$bumbu->nama} {$jumlah} {$satuanTeks} untuk {$kasir->name}";
            }

            $p->update([
                'status' => 'disetujui',
                'jumlah_disetujui' => $jumlah,
                'ditangani_oleh' => $admin->id,
                'ditangani_pada' => now(),
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);

            return [$hasil, $biayaTercatat ?? 0];
        });

        $infoBiaya = $biayaCatat > 0
            ? ' Biaya Rp ' . number_format($biayaCatat, 0, ',', '.') . ' tercatat di Pengeluaran.'
            : ' Tidak ada biaya yang tercatat di Pengeluaran (biaya kosong / harga belum diisi).';

        return back()->with('sukses', 'Pengajuan disetujui: ' . $pesan . '.' . $infoBiaya . ' Stok baru masuk setelah kasir mengonfirmasi barangnya diterima.');
    }

    public function tolak(Request $request, PermintaanStok $permintaan)
    {
        $data = $request->validate([
            'catatan_admin' => ['required', 'string', 'min:3', 'max:200'],
        ], [
            'catatan_admin.required' => 'Tulis alasan penolakan supaya kasir tahu.',
            'catatan_admin.min' => 'Alasan penolakan terlalu pendek.',
        ]);

        DB::transaction(function () use ($permintaan, $data) {
            $p = PermintaanStok::lockForUpdate()->findOrFail($permintaan->id);

            if ($p->status !== 'menunggu') {
                throw ValidationException::withMessages(['catatan_admin' => 'Pengajuan ini sudah ditangani sebelumnya.']);
            }

            $p->update([
                'status' => 'ditolak',
                'ditangani_oleh' => auth()->id(),
                'ditangani_pada' => now(),
                'catatan_admin' => $data['catatan_admin'],
            ]);
        });

        return back()->with('sukses', 'Pengajuan ditolak.');
    }

    /** Biaya restock menu / bumbu bersifat opsional; kalau diisi masuk Pengeluaran kategori Bahan Baku */
    private function catatBiayaBahanBaku(int $biaya, string $deskripsi, int $adminId): void
    {
        if ($biaya < 1) {
            return;
        }

        Pengeluaran::create([
            'tanggal' => today(),
            'kategori' => 'bahan_baku',
            'deskripsi' => Str::limit($deskripsi, 150, ''),
            'jumlah' => $biaya,
            'user_id' => $adminId,
        ]);
    }
}
