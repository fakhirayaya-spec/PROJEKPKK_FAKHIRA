<?php

namespace App\Providers;

use App\Models\Bumbu;
use App\Models\Menu;
use App\Services\StokService;
use App\Support\FotoStorage;
use App\Services\StokOperasionalService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Foto upload (menu, kasir, admin, bahan) disimpan langsung ke public/storage, tempat browser membacanya.
        // Tanpa ini, kalau public/storage berupa folder biasa (bukan symlink), foto baru tersimpan di
        // storage/app/public dan tidak pernah tampil.
        config(['filesystems.disks.public.root' => public_path('storage')]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Pastikan public/storage ada, dan pindahkan foto lama dari storage/app/public (sekali saja)
        try {
            FotoStorage::siapkan(public_path('storage'), storage_path('app/public'), storage_path('app/.foto_dipindah'));
        } catch (\Throwable $e) {
            report($e);
        }

        // Tabel bumbu dibuat otomatis kalau belum ada, jadi tidak perlu migrate manual
        if (! $this->app->runningInConsole()) {
            try {
                Bumbu::pastikanTabel();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Data untuk lonceng "Stok perlu perhatian" di navbar (menu + bumbu yang menipis/habis)
        View::composer('layouts.base', function ($view) {
            $alert = collect();

            if (! auth()->check()) {
                return;
            }

            try {
                if (auth()->user()->role === 'kasir') {
                    // Kasir diingatkan soal stok miliknya sendiri
                    $kasirId = (int) auth()->id();
                    $menus = Menu::aktif()->get();
                    $bumbus = Bumbu::aktif()->get();
                    StokService::terapkanStokKasir($menus, 'menu', $kasirId);
                    StokService::terapkanStokKasir($bumbus, 'bumbu', $kasirId);

                    $menus->filter(fn ($m) => $m->stok <= $m->stok_minimum)->sortBy('stok')
                        ->each(fn ($m) => $alert->push([
                            'nama' => $m->nama_menu,
                            'jenis' => 'Menu',
                            'stok' => $m->stok,
                            'status' => $m->stok <= 0 ? 'habis' : 'menipis',
                        ]));

                    $bumbus->filter(fn ($b) => $b->stok <= $b->stok_minimum)->sortBy('stok')
                        ->each(fn ($b) => $alert->push([
                            'nama' => $b->nama,
                            'jenis' => $b->jenis === 'saus' ? 'Saus' : 'Bumbu tabur',
                            'stok' => $b->stok,
                            'status' => $b->stok <= 0 ? 'habis' : 'menipis',
                        ]));
                } else {
                    // Admin: stok menipis/habis di kasir MANA PUN, lengkap dengan nama kasirnya
                    StokService::barisStokSemuaKasir()
                        ->whereIn('status', ['habis', 'menipis'])
                        ->each(fn ($b) => $alert->push([
                            'nama' => $b->nama . ' · ' . $b->kasir,
                            'jenis' => $b->jenis,
                            'stok' => $b->stok,
                            'status' => $b->status,
                        ]));
                }
            } catch (\Throwable $e) {
                // tabel bumbu belum dimigrasi: lonceng tetap tampil tanpa data bumbu
            }

            // Stok operasional: kasir melihat miliknya, admin melihat semua kasir
            try {
                StokOperasionalService::alertUntuk(auth()->user())->each(fn ($a) => $alert->push($a));
            } catch (\Throwable $e) {
                // tabel stok operasional belum dimigrasi: lonceng tetap tampil tanpa data ini
            }

            $view->with('alertStok', $alert->sortBy(fn ($a) => $a['status'] === 'habis' ? 0 : 1)->values());
        });
    }
}
