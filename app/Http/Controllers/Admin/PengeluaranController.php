<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Models\User;
use App\Support\KategoriPengeluaran;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    private const KATEGORI = [
        'bahan_baku' => 'Bahan Baku',
        'operasional' => 'Operasional',
        'lainnya' => 'Lainnya',
    ];

    public function index(Request $request)
    {
        $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date'], 'kasir' => ['nullable', 'string', 'max:20']]);

        // Filter kasir: id kasir, atau "umum" (pengeluaran yang bukan untuk kasir tertentu)
        $daftarKasir = User::kasir()->orderBy('name')->get(['id', 'name']);
        $kasirDipilih = $request->filled('kasir') ? (string) $request->kasir : null;
        $kasirModel = ($kasirDipilih && $kasirDipilih !== 'umum') ? $daftarKasir->firstWhere('id', (int) $kasirDipilih) : null;
        $terapkanKasir = function ($q) use ($kasirDipilih, $kasirModel) {
            if ($kasirDipilih === 'umum') {
                $q->umum();
            } elseif ($kasirModel) {
                $q->untukKasir($kasirModel->name);
            }
        };

        // Tanpa filter tanggal = tampilkan SEMUA periode (dulu default-nya hanya bulan berjalan,
        // jadi pengeluaran bulan lain terlihat "tidak masuk").
        $dari = $request->filled('dari') ? Carbon::parse($request->dari) : null;
        $sampai = $request->filled('sampai') ? Carbon::parse($request->sampai) : null;
        if ($dari && $sampai && $dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai->copy(), $dari->copy()];
        }

        $query = Pengeluaran::with('pencatat');
        $terapkanKasir($query);
        if ($dari) {
            $query->where('tanggal', '>=', $dari->toDateString());
        }
        if ($sampai) {
            $query->where('tanggal', '<=', $sampai->toDateString());
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Ringkasan per kategori untuk rentang tanggal yang dipilih (tidak ikut filter kategori)
        $rentang = Pengeluaran::query();
        $terapkanKasir($rentang);
        if ($dari) {
            $rentang->where('tanggal', '>=', $dari->toDateString());
        }
        if ($sampai) {
            $rentang->where('tanggal', '<=', $sampai->toDateString());
        }
        $perKategori = $rentang->selectRaw('kategori, SUM(jumlah) as total')->groupBy('kategori')->pluck('total', 'kategori');

        // Total dihitung dari query yang sama SEBELUM paginate, supaya tidak ikut ter-offset di halaman 2 dst.
        $totalPengeluaran = (int) (clone $query)->sum('jumlah');

        $pengeluarans = (clone $query)->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        return view('admin.pengeluaran.index', [
            'pengeluarans' => $pengeluarans,
            'totalPengeluaran' => $totalPengeluaran,
            'perKategori' => $perKategori,
            'dari' => $dari,
            'sampai' => $sampai,
            'labelKategori' => self::KATEGORI,
            'daftarKasir' => $daftarKasir,
            'kasirDipilih' => $kasirModel ? (string) $kasirModel->id : ($kasirDipilih === 'umum' ? 'umum' : ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'deskripsi' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        // Kategori tidak dipilih manual: ditentukan otomatis dari keterangan (barang/bahan -> Bahan Baku, selain itu Operasional)
        $kategori = KategoriPengeluaran::tentukan($data['deskripsi']);

        $pengeluaran = Pengeluaran::create($data + ['kategori' => $kategori, 'user_id' => auth()->id()]);

        // Arahkan ke bulan dari tanggal yang diisi, supaya data barunya langsung kelihatan di daftar
        $tgl = Carbon::parse($data['tanggal']);

        return redirect()->route('admin.pengeluaran.index', [
            'dari' => $tgl->copy()->startOfMonth()->toDateString(),
            'sampai' => $tgl->copy()->endOfMonth()->toDateString(),
        ])->with('sukses', 'Pengeluaran berhasil dicatat (' . $pengeluaran->tanggal->format('d M Y') . ') sebagai ' . self::KATEGORI[$kategori] . '.');
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        $pengeluaran->delete();

        return back()->with('sukses', 'Data pengeluaran berhasil dihapus.');
    }
}
