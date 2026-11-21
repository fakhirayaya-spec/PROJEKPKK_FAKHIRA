<?php

namespace App\Http\Middleware;

use App\Services\StokService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tanpa perlu scheduler/cron: begitu ada yang membuka aplikasi di hari yang baru,
 * stok yang sudah lewat tanggal exp otomatis dibuang (cukup sekali per hari).
 */
class BuangStokKadaluarsa
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $kunci = 'stok_kadaluarsa_' . today()->toDateString();

            if (! Cache::has($kunci) && Schema::hasTable('stok_batches')) {
                StokService::buangKadaluarsa();
                Cache::put($kunci, true, now()->addDay());
            }
        } catch (\Throwable $e) {
            report($e); // jangan sampai pengecekan exp menghalangi halaman lain
        }

        return $next($request);
    }
}
