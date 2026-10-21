<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
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
        $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date']]);

        // Tanpa filter tanggal = tampilkan SEMUA periode (dulu default-nya hanya bulan berjalan,
        // jadi pengeluaran bulan lain terlihat "tidak masuk").
        $dari = $request->filled('dari') ? Carbon::parse($request->dari) : null;
        $sampai = $request->filled('sampai') ? Carbon::parse($request->sampai) : null;
        if ($dari && $sampai && $dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai->copy(), $dari->copy()];
        }

        $query = Pengeluaran::with('pencatat');
        if ($dari) {
            $query->where('tanggal', '>=', $dari->toDateString());
        }
        if ($sampai) {
            $query->where('tanggal', '<=', $sampai->toDateString());
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Total dihitung dari query yang sama SEBELUM paginate, supaya tidak ikut ter-offset di halaman 2 dst.
        $totalPengeluaran = (int) (clone $query)->sum('jumlah');

        $pengeluarans = (clone $query)->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        return view('admin.pengeluaran.index', [
            'pengeluarans' => $pengeluarans,
            'totalPengeluaran' => $totalPengeluaran,
            'dari' => $dari,
            'sampai' => $sampai,
            'labelKategori' => self::KATEGORI,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', 'in:bahan_baku,operasional,lainnya'],
            'deskripsi' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $pengeluaran = Pengeluaran::create($data + ['user_id' => auth()->id()]);

        // Arahkan ke bulan dari tanggal yang diisi, supaya data barunya langsung kelihatan di daftar
        $tgl = Carbon::parse($data['tanggal']);

        return redirect()->route('admin.pengeluaran.index', [
            'dari' => $tgl->copy()->startOfMonth()->toDateString(),
            'sampai' => $tgl->copy()->endOfMonth()->toDateString(),
        ])->with('sukses', 'Pengeluaran berhasil dicatat (' . $pengeluaran->tanggal->format('d M Y') . ').');
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        $pengeluaran->delete();

        return back()->with('sukses', 'Data pengeluaran berhasil dihapus.');
    }
}
