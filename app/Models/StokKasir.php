<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sisa stok satu menu atau bumbu milik satu kasir. tipe = 'menu' | 'bumbu', item_id = id menu/bumbu. */
class StokKasir extends Model
{
    protected $table = 'stok_kasirs';

    protected $fillable = ['user_id', 'tipe', 'item_id', 'stok'];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
