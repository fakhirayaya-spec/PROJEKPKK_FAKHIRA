<?php

namespace App\Providers;

use App\Models\Bumbu;
use App\Models\Menu;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

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
                Menu::aktif()->whereColumn('stok', '<=', 'stok_minimum')->orderBy('stok')->get()
                    ->each(fn ($m) => $alert->push([
                        'nama' => $m->nama_menu,
                        'jenis' => 'Menu',
                        'stok' => $m->stok,
                        'status' => $m->stok <= 0 ? 'habis' : 'menipis',
                    ]));

                Bumbu::aktif()->whereColumn('stok', '<=', 'stok_minimum')->orderBy('stok')->get()
                    ->each(fn ($b) => $alert->push([
                        'nama' => $b->nama,
                        'jenis' => $b->jenis === 'saus' ? 'Saus' : 'Bumbu tabur',
                        'stok' => $b->stok,
                        'status' => $b->stok <= 0 ? 'habis' : 'menipis',
                    ]));
            } catch (\Throwable $e) {
                // tabel bumbu belum dimigrasi: lonceng tetap tampil tanpa data bumbu
            }

            $view->with('alertStok', $alert->sortBy(fn ($a) => $a['status'] === 'habis' ? 0 : 1)->values());
        });
    }
}
