<?php

namespace App\Support;

use App\Models\Transaksi;

/** Status pembayaran yang ditampilkan pada riwayat transaksi. */
class StatusTransaksi
{
    /** @return array{label: string, warna: string} */
    public static function untuk(Transaksi $trx): array
    {
        if ($trx->status === 'dibatalkan') {
            return ['label' => 'Dibatalkan', 'warna' => 'red'];
        }

        $jenis = $trx->jenis ?? 'penjualan';

        // DP PO baru dianggap lunas jika seluruh total sudah dibayar,
        // atau PO sudah diselesaikan melalui proses pelunasan.
        if ($jenis === 'dp') {
            $po = $trx->preOrderAsal;

            if ($po?->status === 'dibatalkan') {
                return ['label' => 'DP Hangus', 'warna' => 'red'];
            }

            if ($po && ($po->status === 'selesai' || (int) $po->dp >= (int) $po->total)) {
                return ['label' => 'Lunas', 'warna' => 'green'];
            }

            return ['label' => 'Belum Lunas', 'warna' => 'amber'];
        }

        // Transaksi biasa dibayar penuh saat disimpan, sedangkan transaksi
        // pelunasan PO mencatat sisa pembayaran yang telah diterima.
        return ['label' => 'Lunas', 'warna' => 'green'];
    }
}
