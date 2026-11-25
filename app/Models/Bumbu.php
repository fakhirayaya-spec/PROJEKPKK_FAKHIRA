<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Bumbu extends Model
{
    protected $table = 'bumbus';

    public const JENIS = [
        'tabur' => 'Bumbu Tabur',
        'saus' => 'Saus',
    ];

    /** Buat tabel bumbus otomatis bila belum ada (tanpa perlu php artisan migrate). */
    public static function pastikanTabel(): void
    {
        if (Schema::hasTable('bumbus')) {
            return;
        }

        Schema::create('bumbus', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['tabur', 'saus']);
            $table->string('nama');
            $table->unsignedInteger('stok')->default(0);
            $table->unsignedInteger('stok_minimum')->default(20);
            $table->string('satuan', 20)->default('porsi');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->unique(['jenis', 'nama']);
        });

        $now = now();
        $awal = [
            ['saus', 'Saus Keju'], ['saus', 'Saus Mayo'], ['saus', 'Saus Blackpaper'],
            ['saus', 'Saus Mentai'], ['saus', 'Saus Lava'],
            ['tabur', 'Bubuk Jagung'], ['tabur', 'Bubuk Pedas'], ['tabur', 'Bubuk Balado'], ['tabur', 'Bubuk Keju'],
        ];
        DB::table('bumbus')->insert(array_map(fn ($b) => [
            'jenis' => $b[0], 'nama' => $b[1], 'stok' => 100, 'stok_minimum' => 20,
            'satuan' => 'porsi', 'status' => 'aktif', 'created_at' => $now, 'updated_at' => $now,
        ], $awal));
    }

    protected $fillable = ['jenis', 'nama', 'stok', 'stok_minimum', 'satuan', 'status', 'harga_per_bungkus', 'isi_per_bungkus'];

    /** Harga & isi bungkus bawaan per jenis: [harga per bungkus, berapa kali pakai] */
    public const BUNGKUS_BAWAAN = [
        'tabur' => [7000, 50],
        'saus' => [14000, 25],
    ];

    public function getHargaBungkusEfektifAttribute(): int
    {
        $h = (int) ($this->attributes['harga_per_bungkus'] ?? 0);

        return $h > 0 ? $h : (self::BUNGKUS_BAWAAN[$this->jenis][0] ?? 0);
    }

    public function getIsiBungkusEfektifAttribute(): int
    {
        $i = (int) ($this->attributes['isi_per_bungkus'] ?? 0);
        if ((int) ($this->attributes['harga_per_bungkus'] ?? 0) > 0) {
            return max($i, 1);
        }

        return self::BUNGKUS_BAWAAN[$this->jenis][1] ?? max($i, 1);
    }

    /** Harga satu kali pakai (1 porsi), bisa berkoma, mis. 7000/50 = 140 */
    public function getHargaPerPakaiAttribute(): float
    {
        return $this->harga_bungkus_efektif / $this->isi_bungkus_efektif;
    }

    /** Biaya (Rp, dibulatkan) untuk sejumlah porsi/pakai */
    public function biayaUntuk(int $porsi): int
    {
        return (int) round($porsi * $this->harga_per_pakai);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeTersedia($query)
    {
        return $query->where('status', 'aktif')->where('stok', '>', 0);
    }

    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    public function getStatusStokAttribute(): string
    {
        if ($this->stok <= 0) {
            return 'habis';
        }

        return $this->stok <= $this->stok_minimum ? 'menipis' : 'aman';
    }

    /** Persentase isi bar stok (penuh = 3x stok minimum) */
    public function getPersenStokAttribute(): int
    {
        $penuh = max($this->stok_minimum * 3, 1);

        return (int) min(100, round($this->stok / $penuh * 100));
    }
}
