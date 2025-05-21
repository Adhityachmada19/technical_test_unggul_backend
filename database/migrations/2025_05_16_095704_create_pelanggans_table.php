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
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->string('id_pelanggan', 20)->primary();
            $table->string('nama');
            $table->string('domisili');
            $table->enum('jenis_kelamin', ['PRIA', 'WANITA']);
            $table->timestamps();
            $table->softDeletes(); // otomatis membuat kolom deleted_at nullable timestamp

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
