<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreOrderItem extends Model
{
    protected $fillable = [
        'pre_order_id', 'menu_id', 'nama_menu', 'ukuran', 'saus', 'bubuk_tabur',
        'qty', 'harga_satuan', 'modal_satuan', 'subtotal',
    ];

    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
