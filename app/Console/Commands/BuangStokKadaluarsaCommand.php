<?php

namespace App\Console\Commands;

use App\Services\StokService;
use Illuminate\Console\Command;

class BuangStokKadaluarsaCommand extends Command
{
    protected $signature = 'stok:buang-kadaluarsa';

    protected $description = 'Buang sisa stok menu & bumbu yang sudah lewat tanggal exp (opsional, normalnya jalan otomatis)';

    public function handle(): int
    {
        $jumlah = StokService::buangKadaluarsa();
        $this->info("Selesai. {$jumlah} batch kadaluarsa dibuang dari stok.");

        return self::SUCCESS;
    }
}
