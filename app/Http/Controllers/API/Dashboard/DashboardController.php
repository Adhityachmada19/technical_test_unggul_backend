<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {

        //Total Pelanggan

        $total_pelanggan = Pelanggan::count();

        $total_barang = Barang::count();

        $today = Carbon::today();
        $now = Carbon::now();

        $total_pembelian_hari_ini = Penjualan::whereDate('tgl', $today)->sum('subtotal');
        $total_pembelian_bulan_ini = Penjualan::whereMonth('tgl', $now->month)->whereYear('tgl', $now->year)->sum('subtotal');
        $total_pembelian_keseluruhan = Penjualan::sum('subtotal');


        $top5Terlaris = DB::table('item_penjualans')
            ->join('barangs', 'item_penjualans.kode_barang', '=', 'barangs.kode')
            ->select(
                'item_penjualans.kode_barang',
                'barangs.nama',
                DB::raw('SUM(item_penjualans.qty) as total_terjual')
            )
            ->groupBy('item_penjualans.kode_barang', 'barangs.nama')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();


        $top5Pelanggan = DB::table('penjualans')
            ->join('pelanggans', 'penjualans.kode_pelanggan', '=', 'pelanggans.id_pelanggan')
            ->select('penjualans.kode_pelanggan', 'pelanggans.nama', DB::raw('SUM(penjualans.subtotal) as total_pembelian'))
            ->groupBy('penjualans.kode_pelanggan', 'pelanggans.nama')
            ->orderByDesc('total_pembelian')
            ->limit(5)
            ->get();



        return response()->json([
            'success' => true,
            'message' => 'Data Dashboard',
            'data' => [
                'total_pelanggan' => $total_pelanggan,
                'total_barang' => $total_barang,
                'pemasukan_hari_ini' => intval($total_pembelian_hari_ini),
                'pemasukan_bulan_ini' => intval($total_pembelian_bulan_ini),
                'pemasukan_keseluruhan' => intval($total_pembelian_keseluruhan),
                'barang_terlaris' => $top5Terlaris,
                'top_pelanggan' => $top5Pelanggan
            ],
        ]);
    }
}
