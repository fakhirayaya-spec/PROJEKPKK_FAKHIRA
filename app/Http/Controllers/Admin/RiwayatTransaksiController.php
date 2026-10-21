<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;

class RiwayatTransaksiController extends Controller
{
    private function terapkanFilter($query, Request $request)
    {
        if ($request->filled('q')) {
            $query->where('kode_transaksi', 'like', '%' . $request->q . '%');
        }
        if ($request->filled('kasir_id')) {
            $query->where('user_id', $request->kasir_id);
        }
        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'metode_bayar' => ['nullable', 'in:cash,qris,paylater'],
            'status' => ['nullable', 'in:selesai,dibatalkan'],
        ]);

        $transaksis = $this->terapkanFilter(Transaksi::with('kasir')->withCount('items'), $request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $selesai = $this->terapkanFilter(Transaksi::query(), $request)->where('status', 'selesai');
        $totalNominal = (clone $selesai)->sum('total');
        $totalTransaksi = (clone $selesai)->count();

        $kasirs = User::kasir()->orderBy('name')->get();

        return view('admin.riwayat-transaksi.index', compact('transaksis', 'kasirs', 'totalNominal', 'totalTransaksi'));
    }

    public function show(Transaksi $transaksi)
    {
        $transaksi->load('items.menu', 'kasir', 'pembatal');

        return view('admin.riwayat-transaksi.show', compact('transaksi'));
    }
}
