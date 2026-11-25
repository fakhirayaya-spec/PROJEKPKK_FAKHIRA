<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PergerakanBumbu extends Model
{
    protected $fillable = ['bumbu_id', 'nama', 'user_id', 'kasir_id', 'jenis', 'jumlah', 'keterangan', 'tanggal'];

    protected $casts = ['tanggal' => 'date'];

    public function bumbu()
    {
        return $this->belongsTo(Bumbu::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
