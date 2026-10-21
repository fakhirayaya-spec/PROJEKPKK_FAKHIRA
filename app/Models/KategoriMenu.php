<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriMenu extends Model
{
    use HasFactory;

    protected $fillable = ['nama_kategori'];

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
