<?php

use App\Http\Controllers\API\Barang\BarangController;
use App\Http\Controllers\API\Dashboard\DashboardController;
use App\Http\Controllers\API\Pelanggan\PelangganController;
use App\Http\Controllers\API\Penjualan\PenjualanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {

    Route::post('/login', App\Http\Controllers\API\Auth\LoginController::class, ['as' => 'auth']);
    Route::group(['middleware' => 'auth:api'], function () {

        Route::post('/logout', App\Http\Controllers\API\Auth\LogoutController::class, ['as' => 'auth']);
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
    });
    Route::prefix('pelanggan')->group(function () {
        Route::get('/list', [PelangganController::class, 'index']);           // List semua pelanggan
        Route::post('/store', [PelangganController::class, 'store']);          // Tambah pelanggan
        Route::get('/detail/{id}', [PelangganController::class, 'show']);        // Tampilkan pelanggan by id
        Route::put('/update/{id}', [PelangganController::class, 'update']);      // Update pelanggan
        Route::delete('/delete/{id}', [PelangganController::class, 'destroy']);  // Hapus pelanggan
    });

    Route::prefix('barang')->group(function () {
        Route::get('/list', [BarangController::class, 'index']);           // List semua barnag
        Route::post('/store', [BarangController::class, 'store']);          // Tambah barang
        Route::get('/detail/{id}', [BarangController::class, 'show']);        // Tampilkan barang by id
        Route::put('/update/{id}', [BarangController::class, 'update']);      // Update barang
        Route::delete('/delete/{id}', [BarangController::class, 'destroy']);  // Hapus barang
    });

    Route::prefix('penjualan')->group(function () {
        Route::get('/list', [PenjualanController::class, 'index']);           // List semua penjualan
        Route::post('/store', [PenjualanController::class, 'store']);          // Tambah penjualan
        Route::get('/detail/{id}', [PenjualanController::class, 'show']);        // Tampilkan penjualan by id
        Route::put('/update/{id}', [PenjualanController::class, 'update']);      // Update penjualan
        Route::delete('/delete/{id}', [PenjualanController::class, 'destroy']);  // Hapus penjualan
    });
    //route user logged in
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('user');
});
