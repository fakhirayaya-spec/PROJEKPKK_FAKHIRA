<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bumbu;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Manajemen bumbu (saus & bubuk tabur). Daftar bumbu dipakai bersama, tapi STOKNYA dimiliki per kasir:
 * admin memilih kasirnya dulu, lalu menambah / mengoreksi stok khusus untuk kasir itu.
 */
class BumbuController extends Controller
{
    public function index(Request $request)
    {
        $kasirs = User::kasir()->aktif()->orderBy('name')->get();
        $kasirTerpilih = $kasirs->firstWhere('id', (int) $request->kasir) ?? $kasirs->first();

        $semua = Bumbu::orderBy('nama')->get();

        if ($kasirTerpilih) {
            StokService::terapkanStokKasir($semua, 'bumbu', $kasirTerpilih->id);
        }

        return view('admin.manajemen-bumbu.index', [
            'kasirs' => $kasirs,
            'kasirTerpilih' => $kasirTerpilih,
            'tabur' => $semua->where('jenis', 'tabur')->values(),
            'saus' => $semua->where('jenis', 'saus')->values(),
            'total' => $semua->count(),
            'menipis' => $semua->filter(fn ($b) => $b->status_stok === 'menipis')->count(),
            'habis' => $semua->filter(fn ($b) => $b->status_stok === 'habis')->count(),
            'expTerdekat' => $kasirTerpilih ? StokService::expTerdekat('bumbu', $kasirTerpilih->id) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(Bumbu::JENIS))],
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('bumbus', 'nama')->where('jenis', $request->jenis),
            ],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_per_bungkus' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'isi_per_bungkus' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'nama.unique' => 'Nama itu sudah dipakai di kelompok yang sama.',
        ]);

        $data = $this->dataBungkus($data, $data['jenis']);

        // Stok awal selalu 0: stok diisi per kasir lewat tombol tambah stok
        Bumbu::create($data + ['stok' => 0]);

        return back()->with('sukses', '"' . $data['nama'] . '" berhasil ditambahkan. Isi stoknya per kasir lewat tombol tambah stok.');
    }

    public function update(Request $request, Bumbu $bumbu)
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('bumbus', 'nama')->where('jenis', $bumbu->jenis)->ignore($bumbu->id),
            ],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_per_bungkus' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'isi_per_bungkus' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'nama.unique' => 'Nama itu sudah dipakai di kelompok yang sama.',
        ]);

        $data = $this->dataBungkus($data, $bumbu->jenis);

        DB::transaction(function () use ($bumbu, $data) {
            $bumbu->update(Arr::only($data, ['nama', 'stok_minimum', 'satuan', 'harga_per_bungkus', 'isi_per_bungkus']));

            // angka stok di form = koreksi stok milik kasir yang dipilih (batch exp kasir itu ikut disesuaikan)
            StokService::koreksi($bumbu->refresh(), (int) $data['kasir_id'], (int) $data['stok'], 'Koreksi stok');
        });

        return back()->with('sukses', '"' . $bumbu->nama . '" berhasil diperbarui.');
    }

    public function tambahStok(Request $request, Bumbu $bumbu)
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal_exp' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $kasir = User::findOrFail($data['kasir_id']);

        // Stok masuk dicatat sebagai batch milik kasir itu dengan tanggal exp (default 30 hari dari hari ini)
        DB::transaction(fn () => StokService::tambah($bumbu, (int) $data['jumlah'], (int) $data['kasir_id'], $data['tanggal_exp'] ?? null, 'Restock manual'));

        return back()->with('sukses', 'Stok "' . $bumbu->nama . '" untuk ' . $kasir->name . ' bertambah ' . $data['jumlah'] . ' ' . $bumbu->satuan . '.');
    }

    /**
     * Isi stok SEMUA bumbu & saus untuk satu kasir sekaligus: tiap bumbu dibuat jadi 50 (sama dengan Master).
     * Bumbu yang sudah 50 atau lebih dilewati. Mau isi jumlah lain untuk satu bumbu, pakai tombol Stok.
     */
    public function isiSemua(Request $request)
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'tanggal_exp' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'tanggal_exp.after_or_equal' => 'Tanggal exp tidak boleh sebelum hari ini.',
        ]);

        $kasir = User::findOrFail($data['kasir_id']);
        $target = StokService::STOK_STANDAR;
        $diisi = 0;

        DB::transaction(function () use ($data, $target, &$diisi) {
            foreach (Bumbu::orderBy('id')->get() as $bumbu) {
                $kurang = $target - StokService::stokDi($bumbu, (int) $data['kasir_id']);
                if ($kurang <= 0) {
                    continue;
                }

                StokService::tambah($bumbu, $kurang, (int) $data['kasir_id'], $data['tanggal_exp'] ?? null, 'Isi semua stok');
                $diisi++;
            }
        });

        if ($diisi === 0) {
            return back()->with('sukses', 'Semua bumbu ' . $kasir->name . ' sudah ' . $target . ' atau lebih, tidak ada yang perlu diisi.');
        }

        return back()->with('sukses', 'Stok ' . $diisi . ' bumbu untuk ' . $kasir->name . ' sekarang ' . $target . '.');
    }

    public function toggle(Bumbu $bumbu)
    {
        $bumbu->update(['status' => $bumbu->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return back()->with('sukses', '"' . $bumbu->nama . '" sekarang ' . $bumbu->status . '.');
    }

    public function destroy(Bumbu $bumbu)
    {
        $nama = $bumbu->nama;

        // stok & batch per kasir milik bumbu ini ikut dihapus
        DB::transaction(function () use ($bumbu) {
            \App\Models\StokKasir::where('tipe', 'bumbu')->where('item_id', $bumbu->id)->delete();
            \App\Models\StokBatch::where('tipe', 'bumbu')->where('item_id', $bumbu->id)->delete();
            $bumbu->delete();
        });

        return back()->with('sukses', '"' . $nama . '" dihapus.');
    }

    /** Harga/isi bungkus kosong -> pakai bawaan jenisnya; dibuang kalau kolomnya belum ada (belum migrate) */
    private function dataBungkus(array $data, string $jenis): array
    {
        if (! Schema::hasColumn('bumbus', 'harga_per_bungkus')) {
            unset($data['harga_per_bungkus'], $data['isi_per_bungkus']);

            return $data;
        }

        [$hargaBawaan, $isiBawaan] = Bumbu::BUNGKUS_BAWAAN[$jenis] ?? [0, 1];
        $data['harga_per_bungkus'] = (int) ($data['harga_per_bungkus'] ?? 0) > 0 ? (int) $data['harga_per_bungkus'] : $hargaBawaan;
        $data['isi_per_bungkus'] = (int) ($data['isi_per_bungkus'] ?? 0) > 0 ? (int) $data['isi_per_bungkus'] : $isiBawaan;

        return $data;
    }
}
