<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Bumbu;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\PermintaanStok;
use App\Services\StokOperasionalService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Kasir mengajukan stok (operasional / menu / bumbu) ke admin, dan melihat status pengajuannya. */
class PermintaanStokController extends Controller
{
    public function index()
    {
        $riwayat = PermintaanStok::where('user_id', auth()->id())->latest()->latest('id')->paginate(10);

        $menuList = Menu::aktif()->orderBy('nama_menu')->get(['id', 'nama_menu', 'stok']);
        $bumbuList = Bumbu::aktif()->orderBy('jenis')->orderBy('nama')->get();

        // "sisa" yang tampil = stok milik kasir ini, bukan total semua kasir
        StokService::terapkanStokKasir($menuList, 'menu', (int) auth()->id());
        StokService::terapkanStokKasir($bumbuList, 'bumbu', (int) auth()->id());

        return view('kasir.permintaan-stok.index', [
            'riwayat' => $riwayat,
            'bahanList' => BahanOperasional::aktif()->orderBy('urutan')->orderBy('id')->get(),
            'menuList' => $menuList,
            'bumbuList' => $bumbuList,
            'jenisList' => PermintaanStok::JENIS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:operasional,menu,bumbu'],
            'bahan_kode' => ['required_if:jenis,operasional', 'nullable', 'string'],
            'menu_id' => ['required_if:jenis,menu', 'nullable', 'integer'],
            'bumbu_id' => ['required_if:jenis,bumbu', 'nullable', 'integer'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:200'],
        ], [
            'jenis.required' => 'Pilih jenis stok yang diajukan.',
            'bahan_kode.required_if' => 'Pilih bahan operasional yang diajukan.',
            'menu_id.required_if' => 'Pilih menu yang diajukan.',
            'bumbu_id.required_if' => 'Pilih bumbu yang diajukan.',
            'jumlah.required' => 'Jumlah yang diajukan wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => 'Jumlah terlalu besar (maksimal 1000).',
        ]);

        $itemId = null;
        $kode = null;

        if ($data['jenis'] === 'operasional') {
            $bahan = BahanOperasional::aktif()->where('kode', $data['bahan_kode'])->first();
            abort_if(! $bahan, 422, 'Bahan operasional tidak ditemukan atau sudah nonaktif.');
            $nama = $bahan->nama;
            $satuan = $bahan->unit_isi ?: $bahan->satuan;
            $kode = $bahan->kode;
        } elseif ($data['jenis'] === 'menu') {
            $menu = Menu::aktif()->find($data['menu_id']);
            abort_if(! $menu, 422, 'Menu tidak ditemukan atau sudah nonaktif.');
            $nama = $menu->nama_menu;
            $satuan = 'porsi';
            $itemId = $menu->id;
        } else {
            $bumbu = Bumbu::aktif()->find($data['bumbu_id']);
            abort_if(! $bumbu, 422, 'Bumbu tidak ditemukan atau sudah nonaktif.');
            $nama = $bumbu->nama;
            $satuan = 'bungkus'; // bumbu & saus diajukan per bungkus; isi per bungkus masuk ke stok saat dikonfirmasi
            $itemId = $bumbu->id;
        }

        // Satu barang cukup satu pengajuan yang masih menunggu, supaya admin tidak dibanjiri pengajuan ganda
        $sudahAda = PermintaanStok::menunggu()
            ->where('user_id', auth()->id())
            ->where('jenis', $data['jenis'])
            ->where('nama_item', $nama)
            ->exists();

        if ($sudahAda) {
            return back()->withInput()->withErrors(['jumlah' => "Pengajuan {$nama} milikmu masih menunggu admin. Batalkan dulu kalau mau mengajukan ulang."]);
        }

        PermintaanStok::create([
            'user_id' => auth()->id(),
            'jenis' => $data['jenis'],
            'item_id' => $itemId,
            'bahan_kode' => $kode,
            'nama_item' => $nama,
            'satuan' => $satuan,
            'jumlah' => (int) $data['jumlah'],
            'catatan' => $data['catatan'] ?? null,
            'status' => 'menunggu',
        ]);

        return redirect()->route('kasir.permintaan-stok.index')
            ->with('sukses', "Pengajuan {$nama} ({$data['jumlah']} {$satuan}) terkirim ke admin.");
    }

    /** Kasir membatalkan pengajuannya sendiri selama belum ditangani admin */
    public function batal(PermintaanStok $permintaan)
    {
        abort_unless($permintaan->user_id === auth()->id(), 403);
        abort_unless($permintaan->status === 'menunggu', 403, 'Pengajuan ini sudah ditangani admin.');

        $permintaan->update(['status' => 'dibatalkan']);

        return back()->with('sukses', 'Pengajuan dibatalkan.');
    }

    /**
     * Kasir mengonfirmasi barang yang disetujui admin sudah diterima.
     * Baru di sini stok bertambah dan pengajuan dinyatakan berhasil.
     */
    public function konfirmasi(PermintaanStok $permintaan)
    {
        abort_unless($permintaan->user_id === auth()->id(), 403);

        $hasil = DB::transaction(function () use ($permintaan) {
            $p = PermintaanStok::lockForUpdate()->findOrFail($permintaan->id);

            if ($p->status !== 'disetujui') {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini tidak sedang menunggu konfirmasi (mungkin sudah dikonfirmasi).']);
            }

            $kasir = auth()->user();
            $jumlah = (int) ($p->jumlah_disetujui ?: $p->jumlah);
            $admin = $p->penangan ?: $kasir;

            if ($p->jenis === 'operasional') {
                $bahan = BahanOperasional::where('kode', $p->bahan_kode)->first();
                if (! $bahan) {
                    throw ValidationException::withMessages(['status' => "Bahan {$p->nama_item} sudah dihapus, hubungi admin."]);
                }

                $tambah = StokOperasionalService::isiUlang($kasir, $bahan, $jumlah, $admin, 'Pengajuan kasir disetujui, dikonfirmasi kasir');
                $teks = "{$bahan->nama} bertambah {$tambah} {$bahan->satuan}";
            } elseif ($p->jenis === 'menu') {
                $menu = Menu::lockForUpdate()->find($p->item_id);
                if (! $menu) {
                    throw ValidationException::withMessages(['status' => "Menu {$p->nama_item} sudah dihapus, hubungi admin."]);
                }

                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => $kasir->id,
                    'kasir_id' => $kasir->id,
                    'jenis' => 'masuk',
                    'jumlah' => $jumlah,
                    'keterangan' => 'Pengajuan disetujui, dikonfirmasi kasir ' . $kasir->name,
                    'tanggal' => today(),
                ]);
                StokService::tambah($menu, $jumlah, (int) $kasir->id, null, 'Pengajuan kasir ' . $kasir->name);
                $teks = "{$menu->nama_menu} bertambah {$jumlah} porsi";
            } else {
                $bumbu = Bumbu::lockForUpdate()->find($p->item_id);
                if (! $bumbu) {
                    throw ValidationException::withMessages(['status' => "Bumbu {$p->nama_item} sudah dihapus, hubungi admin."]);
                }

                // Pengajuan baru dalam bungkus; pengajuan lama (satuan porsi) langsung dalam porsi
                $porsi = $p->satuan === 'bungkus' ? $jumlah * $bumbu->isi_bungkus_efektif : $jumlah;
                StokService::tambah($bumbu, $porsi, (int) $kasir->id, null, 'Pengajuan kasir ' . $kasir->name);
                $teks = $p->satuan === 'bungkus'
                    ? "{$bumbu->nama} bertambah {$jumlah} bungkus ({$porsi} {$bumbu->satuan})"
                    : "{$bumbu->nama} bertambah {$porsi} {$bumbu->satuan}";
            }

            $p->update(['status' => 'selesai', 'dikonfirmasi_pada' => now()]);

            return $teks;
        });

        return back()->with('sukses', "Berhasil! Stok sudah masuk: {$hasil}.");
    }
}
