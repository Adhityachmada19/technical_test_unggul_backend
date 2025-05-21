<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_penjualans', function (Blueprint $table) {
            $table->string('nota', 20);
            $table->string('kode_barang', 20);
            $table->integer('qty');
            $table->timestamps();

            $table->primary(['nota', 'kode_barang']); // Composite primary key

            $table->foreign('nota')->references('id_nota')->on('penjualans')->onDelete('cascade');
            $table->foreign('kode_barang')->references('kode')->on('barangs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_penjualans');
    }
};
