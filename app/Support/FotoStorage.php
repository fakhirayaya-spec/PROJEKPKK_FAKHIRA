<?php

namespace App\Support;

/**
 * Supaya foto (menu, kasir, admin, bahan operasional) SELALU tampil, di komputer mana pun,
 * tanpa harus menjalankan `php artisan storage:link`.
 *
 * Masalah sebelumnya: upload disimpan di storage/app/public, tapi browser membaca dari public/storage.
 * Kalau public/storage berubah jadi folder biasa (biasanya karena proyek di-ZIP lalu di-extract),
 * foto baru tidak pernah muncul.
 *
 * Solusi: disk 'public' diarahkan langsung ke public/storage (lihat AppServiceProvider),
 * dan kelas ini memastikan foldernya ada serta memindahkan foto lama dari storage/app/public (sekali saja).
 * Kalau public/storage ternyata sudah berupa symlink yang benar, semuanya tetap jalan.
 */
class FotoStorage
{
    /**
     * @param string $publik  folder tujuan (public/storage)
     * @param string $lama    folder lama tempat upload sebelumnya tersimpan (storage/app/public)
     * @param string $tanda   file penanda bahwa pemindahan sudah selesai
     */
    public static function siapkan(string $publik, string $lama, string $tanda): void
    {
        if (! file_exists($publik) && ! is_link($publik)) {
            @mkdir($publik, 0775, true);
        }

        if (! is_dir($publik) || is_file($tanda) || ! is_dir($lama)) {
            return;
        }

        // Kalau public/storage adalah symlink ke folder yang sama, tidak ada yang perlu dipindah
        $a = realpath($publik);
        $b = realpath($lama);
        if ($a !== false && $b !== false && $a === $b) {
            @file_put_contents($tanda, date('c'));

            return;
        }

        $semuaBerhasil = self::salinYangBelumAda($lama, $publik);

        if ($semuaBerhasil) {
            @file_put_contents($tanda, date('c'));
        }
    }

    /**
     * Pastikan file yang baru diupload benar-benar ada di public/storage (tempat browser membacanya).
     * Kalau ternyata tersimpan di storage/app/public saja, disalin ke public/storage.
     */
    public static function pastikanTersaji(string $relatif, string $publik, string $lama): bool
    {
        $tujuan = rtrim($publik, '/\\') . DIRECTORY_SEPARATOR . $relatif;
        if (is_file($tujuan)) {
            return true;
        }

        $sumber = rtrim($lama, '/\\') . DIRECTORY_SEPARATOR . $relatif;
        if (! is_file($sumber)) {
            return false;
        }

        if (! is_dir(dirname($tujuan))) {
            @mkdir(dirname($tujuan), 0775, true);
        }

        return @copy($sumber, $tujuan);
    }

    /** Salin file yang belum ada di tujuan (file yang sudah ada tidak pernah ditimpa). */
    private static function salinYangBelumAda(string $dari, string $ke): bool
    {
        $ok = true;
        $dari = rtrim($dari, '/\\');
        $ke = rtrim($ke, '/\\');

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dari, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $item) {
            $rel = substr($item->getPathname(), strlen($dari) + 1);

            if ($item->getFilename() === '.gitignore') {
                continue;
            }

            $tujuan = $ke . DIRECTORY_SEPARATOR . $rel;

            if ($item->isDir()) {
                if (! is_dir($tujuan) && ! @mkdir($tujuan, 0775, true) && ! is_dir($tujuan)) {
                    $ok = false;
                }
                continue;
            }

            if (is_file($tujuan)) {
                continue;
            }

            if (! is_dir(dirname($tujuan))) {
                @mkdir(dirname($tujuan), 0775, true);
            }

            if (! @copy($item->getPathname(), $tujuan)) {
                $ok = false;
            }
        }

        return $ok;
    }
}
