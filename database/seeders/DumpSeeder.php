<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dasar semua seeder data. Kolom timestamp di MySQL disimpan dalam UTC, dan isi db_potato.sql
 * juga dalam UTC, jadi saat meng-seed zona waktu sesi dipaksa +00:00 supaya jam-nya tidak bergeser 7 jam.
 */
abstract class DumpSeeder extends Seeder
{
    protected function upsertDump(string $table, array $rows, array $updateColumns): void
    {
        if (DB::getDriverName() !== 'mysql') {
            DB::table($table)->upsert($rows, ['id'], $updateColumns);

            return;
        }

        $lama = DB::selectOne('select @@session.time_zone as tz')->tz;
        DB::statement("SET time_zone = '+00:00'");
        try {
            DB::table($table)->upsert($rows, ['id'], $updateColumns);
        } finally {
            DB::statement('SET time_zone = ?', [$lama]);
        }
    }
}
