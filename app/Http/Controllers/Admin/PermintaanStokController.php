<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\Pengeluaran;
use App\Models\PergerakanStok;
use App\Models\PermintaanStok;
use App\Services\StokOperasionalService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Admin menyetujui / menolak pengajuan stok dari kasir. Stok baru bertambah saat disetujui. */
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

        $pesan = DB::transaction(function () use ($permintaan, $data) {
            $p = PermintaanStok::lockForUpdate()->findOrFail($permintaan->id);

            if ($p->status !== 'menunggu') {
                throw ValidationException::withMessages(['jumlah' => 'Pengajuan ini sudah ditangani sebelumnya.']);
            }

            $jumlah = (int) $data['jumlah'];
            $biaya = (int) ($data['biaya'] ?? 0);
            $kasir = $p->kasir;
            $admin = auth()->user();

            if ($p->jenis === 'operasional') {
                // Sama seperti isi ulang manual: biaya wajib supaya tercatat di Pengeluaran
                if ($biaya < 1) {
                    throw ValidationException::withMessages(['biaya' => 'Biaya pembelian wajib diisi untuk bahan operasional supaya tercatat di Pengeluaran.']);
                }

                $bahan = BahanOperasional::aktif()->where('kode', $p->bahan_kode)->first();
                if (! $bahan) {
                    throw ValidationException::withMessages(['jumlah' => "Bahan {$p->nama_item} sudah nonaktif atau dihapus, pengajuan tidak bisa disetujui."]);
                }

                $tambah = StokOperasionalService::isiUlang($kasir, $bahan, $jumlah, $admin, 'Pengajuan kasir disetujui');

                Pengeluaran::create([
                    'tanggal' => today(),
                    'kategori' => $bahan->kategori_pengeluaran ?: 'operasional',
                    'deskripsi' => Str::limit("Isi ulang {$bahan->nama}: {$jumlah} {$bahan->unit_isi} untuk kasir {$kasir->name}", 150, ''),
                    'jumlah' => $biaya,
                    'user_id' => $admin->id,
                ]);

                $hasil = "{$bahan->nama} milik {$kasir->name} ditambah {$tambah} {$bahan->satuan}";
            } elseif ($p->jenis === 'menu') {
                $menu = Menu::lockForUpdate()->find($p->item_id);
                if (! $menu) {
                    throw ValidationException::withMessages(['jumlah' => "Menu {$p->nama_item} sudah dihapus, pengajuan tidak bisa disetujui."]);
                }

                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => $admin->id,
                    'kasir_id' => $kasir->id,
                    'jenis' => 'masuk',
                    'jumlah' => $jumlah,
                    'keterangan' => 'Pengajuan kasir ' . $kasir->name,
                    'tanggal' => today(),
                ]);
                // stok masuk ke kasir yang mengajukan (bukan stok pusat)
                StokService::tambah($menu, $jumlah, (int) $kasir->id, null, 'Pengajuan kasir ' . $kasir->name);

                $this->catatBiayaBahanBaku($biaya, "Restock {$menu->nama_menu}: {$jumlah} porsi (pengajuan kasir {$kasir->name})", $admin->id);
                $hasil = "Stok {$menu->nama_menu} bertambah {$jumlah} porsi";
            } else {
                $bumbu = Bumbu::lockForUpdate()->find($p->item_id);
                if (! $bumbu) {
                    throw ValidationException::withMessages(['jumlah' => "Bumbu {$p->nama_item} sudah dihapus, pengajuan tidak bisa disetujui."]);
                }

                StokService::tambah($bumbu, $jumlah, (int) $kasir->id, null, 'Pengajuan kasir ' . $kasir->name);

                $this->catatBiayaBahanBaku($biaya, "Restock {$bumbu->nama}: {$jumlah} {$bumbu->satuan} (pengajuan kasir {$kasir->name})", $admin->id);
                $hasil = "Stok {$bumbu->nama} bertambah {$jumlah} {$bumbu->satuan}";
            }

            $p->update([
                'status' => 'disetujui',
                'jumlah_disetujui' => $jumlah,
                'ditangani_oleh' => $admin->id,
                'ditangani_pada' => now(),
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);

            return $hasil;
        });

        return back()->with('sukses', 'Pengajuan disetujui. ' . $pesan . '.');
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
