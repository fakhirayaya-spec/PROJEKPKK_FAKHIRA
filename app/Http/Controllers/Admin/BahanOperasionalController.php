<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kelola daftar bahan operasional: tambah bahan baru, ubah aturan/gambar, nonaktifkan, hapus.
 */
class BahanOperasionalController extends Controller
{
    public function index()
    {
        $bahans = BahanOperasional::orderBy('urutan')->orderBy('id')->get();

        // jumlah catatan riwayat per bahan, untuk peringatan sebelum menghapus
        $punyaRiwayat = DB::table('riwayat_stok_operasionals')
            ->select('bahan', DB::raw('count(*) as jumlah'))
            ->groupBy('bahan')
            ->pluck('jumlah', 'bahan');

        return view('admin.stok-operasional.bahan', [
            'bahans' => $bahans,
            'punyaRiwayat' => $punyaRiwayat,
            'dasarOptions' => BahanOperasional::DASAR,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $data['kode'] = $this->buatKode($data['nama']);
        $data['ikon'] = 'bi-box-seam';
        $data['urutan'] = (int) BahanOperasional::max('urutan') + 1;
        $data['gambar'] = $request->hasFile('gambar') ? $request->file('gambar')->store('stok-operasional', 'public') : null;

        BahanOperasional::create($data);

        return back()->with('sukses', "Bahan \"{$data['nama']}\" ditambahkan. Stok tiap kasir mulai dari 0, isi lewat tombol Isi Ulang.");
    }

    public function update(Request $request, BahanOperasional $bahan)
    {
        $data = $this->validasi($request, $bahan);

        if ($request->hasFile('gambar')) {
            if ($bahan->gambar) {
                Storage::disk('public')->delete($bahan->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('stok-operasional', 'public');
        } else {
            unset($data['gambar']);
        }

        if ($request->boolean('hapus_gambar') && $bahan->gambar && ! $request->hasFile('gambar')) {
            Storage::disk('public')->delete($bahan->gambar);
            $data['gambar'] = null;
        }

        $bahan->update($data);

        return back()->with('sukses', "Bahan \"{$bahan->nama}\" diperbarui.");
    }

    public function toggle(BahanOperasional $bahan)
    {
        $bahan->update(['status' => $bahan->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('sukses', $bahan->status === 'aktif'
            ? "\"{$bahan->nama}\" diaktifkan lagi."
            : "\"{$bahan->nama}\" dinonaktifkan: tidak lagi berkurang otomatis dan tidak mengunci transaksi.");
    }

    /**
     * Tampilkan gambar bahan lewat aplikasi (tidak bergantung pada `php artisan storage:link`).
     * Mencari di storage/app/public dulu, lalu di folder public.
     */
    public function gambar(BahanOperasional $bahan)
    {
        $path = trim((string) $bahan->gambar);
        abort_if($path === '' || str_contains($path, '..'), 404);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return redirect()->away($path);
        }

        $cache = ['Cache-Control' => 'public, max-age=86400'];
        $rel = ltrim($path, '/');
        if (Str::startsWith($rel, 'storage/')) {
            $rel = Str::after($rel, 'storage/');
        }

        $disk = Storage::disk('public');
        if ($disk->exists($rel)) {
            return response()->file($disk->path($rel), $cache);
        }

        $publik = public_path(ltrim($path, '/'));
        if (is_file($publik)) {
            return response()->file($publik, $cache);
        }

        abort(404);
    }

    public function destroy(BahanOperasional $bahan)
    {
        DB::transaction(function () use ($bahan) {
            // Hapus permanen: stok semua kasir dan riwayat pemakaian bahan ini ikut dihapus
            $bahan->riwayat()->delete();
            $bahan->stokKasir()->delete();
            if ($bahan->gambar) {
                Storage::disk('public')->delete($bahan->gambar);
            }
            $bahan->delete();
        });

        return back()->with('sukses', "Bahan \"{$bahan->nama}\" dihapus.");
    }

    private function validasi(Request $request, ?BahanOperasional $bahan = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60', Rule::unique('bahan_operasionals', 'nama')->ignore($bahan?->id)],
            'satuan' => ['required', 'string', 'max:20'],
            'stok_awal' => ['required', 'integer', 'min:0', 'max:100000'],
            'stok_menipis' => ['required', 'integer', 'min:0', 'max:100000'],
            'dasar_pakai' => ['required', Rule::in(array_keys(BahanOperasional::DASAR))],
            'ukuran' => ['nullable', Rule::in(array_keys(BahanOperasional::UKURAN))],
            'per_kelompok' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'unit_isi' => ['required', 'string', 'max:20'],
            'isi_per_unit' => ['required', 'integer', 'min:1', 'max:100000'],
            'harga_per_unit' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'kategori_pengeluaran' => ['nullable', Rule::in(array_keys(BahanOperasional::KATEGORI_PENGELUARAN))],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'nama.required' => 'Nama bahan wajib diisi.',
            'nama.unique' => 'Nama bahan itu sudah ada.',
            'satuan.required' => 'Satuan stok wajib diisi (contoh: pcs, set, order).',
            'unit_isi.required' => 'Satuan isi ulang wajib diisi (contoh: tabung, dus, pcs).',
            'gambar.image' => 'File gambar harus berupa foto (jpg, png, atau webp).',
            'gambar.max' => 'Ukuran gambar maksimal 5 MB.',
            'gambar.uploaded' => 'Foto gagal diunggah. Ukuran maksimal 5 MB (dan batas upload di php.ini minimal 5M).',
        ]);

        $data['ukuran'] = $data['ukuran'] ?? null;
        if (! Schema::hasColumn('bahan_operasionals', 'ukuran')) {
            unset($data['ukuran']); // belum php artisan migrate
        }
        $data['per_kelompok'] = $data['dasar_pakai'] === 'transaksi' ? 1 : max((int) ($data['per_kelompok'] ?? 1), 1);
        $data['harga_per_unit'] = (int) ($data['harga_per_unit'] ?? 0);
        $data['kategori_pengeluaran'] = $data['kategori_pengeluaran'] ?? ($bahan?->kategori_pengeluaran ?: 'operasional');
        unset($data['hapus_gambar']);

        return $data;
    }

    /** Kode unik (maks 20 karakter) dari nama, dipakai sebagai kunci di tabel stok & riwayat */
    private function buatKode(string $nama): string
    {
        $dasar = Str::limit(Str::slug($nama, '_'), 14, '') ?: 'bahan';
        $kode = $dasar;
        $i = 2;

        while (BahanOperasional::where('kode', $kode)->exists()) {
            $kode = $dasar . '_' . $i++;
        }

        return $kode;
    }
}
