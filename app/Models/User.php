<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'alamat',
        'jenis_kelamin',
        'tanggal_lahir',
        'no_telepon',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tanggal_lahir' => 'date',
        ];
    }

    public function scopeKasir($query)
    {
        return $query->where('role', 'kasir');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Alasan kasir ini TIDAK boleh dihapus (null = boleh dihapus).
     * Boleh dihapus hanya kalau kasir belum pernah punya transaksi / pre-order dan belum pernah
     * ada stok apa pun yang masuk (stok menu, bumbu, operasional, pengajuan stok, maupun riwayatnya).
     */
    public function alasanTidakBisaDihapus(): ?string
    {
        if ($this->role !== 'kasir') {
            return 'Hanya akun kasir yang bisa dihapus dari halaman ini.';
        }

        $id = $this->id;
        $ada = fn (string $tabel, string $kolom = 'user_id') => Schema::hasTable($tabel)
            && Schema::hasColumn($tabel, $kolom)
            && DB::table($tabel)->where($kolom, $id)->exists();

        if ($ada('transaksis') || $ada('pre_orders')) {
            return 'Kasir ini sudah punya transaksi/pre-order, jadi tidak bisa dihapus. Nonaktifkan saja akunnya.';
        }

        $punyaStok = $ada('stok_batches')
            || $ada('pergerakan_stoks', 'kasir_id')
            || $ada('riwayat_stok_operasionals')
            || $ada('permintaan_stoks')
            || (Schema::hasTable('stok_kasirs') && DB::table('stok_kasirs')->where('user_id', $id)->where('stok', '>', 0)->exists())
            || (Schema::hasTable('stok_operasionals') && DB::table('stok_operasionals')->where('user_id', $id)->where('stok', '>', 0)->exists());

        if ($punyaStok) {
            return 'Kasir ini sudah pernah menerima stok, jadi tidak bisa dihapus. Nonaktifkan saja akunnya.';
        }

        return null;
    }

    public function bisaDihapus(): bool
    {
        return $this->alasanTidakBisaDihapus() === null;
    }
}
