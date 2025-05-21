<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{

    use SoftDeletes;
    protected $table = 'penjualans';
    protected $primaryKey = 'id_nota';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id_nota', 'tgl', 'kode_pelanggan', 'subtotal'];

    public function items()
    {
        return $this->hasMany(ItemPenjualan::class, 'nota', 'id_nota');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'kode_pelanggan', 'id_pelanggan');
    }
}
