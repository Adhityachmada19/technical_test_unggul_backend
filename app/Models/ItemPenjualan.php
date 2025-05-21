<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPenjualan extends Model
{
    protected $table = 'item_penjualans';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['nota', 'kode_barang', 'qty'];

    /**
     * Relasi ke Penjualan (nota)
     */
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'nota', 'id_nota');
    }

    /**
     * Relasi ke Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode');
    }
}
