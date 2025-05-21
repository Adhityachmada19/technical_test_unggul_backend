<?php

namespace App\Http\Controllers\API\Penjualan;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\ItemPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenjualanController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->perPage ? request()->perPage : 5;
            $penjualan = Penjualan::with('pelanggan', 'items.barang')->when(request()->search, function ($penjualan) {
                $penjualan = $penjualan->where('id_nota', 'LIKE', '%' . request()->search . '%')->orWhere('tgl', 'LIKE', '%' . request()->search . '%');
            })->paginate($perPage);


            return response()->json([
                'success' => true,
                'message' => "List Penjualan",
                'data' => $penjualan,
            ]);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => "Internal Server Error",
                'error' => $err->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'tgl' => 'required|date',
            'kode_pelanggan' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.kode_barang' => 'required|string|exists:barangs,kode',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $data = $validator->validated();

        DB::beginTransaction();
        try {

            /**Check Pelanggan Dahulu */

            $pelanggan = Pelanggan::where('id_pelanggan', $request['kode_pelanggan'])->first();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan Not Found'
                ], 404);
            }
            // Generate id_nota otomatis: NOTA_1, NOTA_2, ...

            $lastNota = Penjualan::withTrashed()
                ->selectRaw("CAST(SUBSTRING_INDEX(id_nota, '_', -1) AS UNSIGNED) as nomor")
                ->orderByDesc('nomor')
                ->lockForUpdate()
                ->first();

            $nextNotaNumber = ($lastNota?->nomor ?? 0) + 1;
            $id_nota = "NOTA_" . $nextNotaNumber;



            // Hitung subtotal berdasarkan harga * qty

            $subTotal = 0;
            foreach ($data['items'] as $item) {
                $barang = Barang::where('kode', $item['kode_barang'])->first();

                if (!$barang) {
                    return response()->json([
                        'success' => false,
                        'message' => ' Barang Not Found'
                    ], 404);
                }
                $subTotal += $barang->harga * $item['qty'];
            }

            // Simpan penjualan


            $penjualan = Penjualan::create([
                'id_nota' => $id_nota,
                'tgl' => Carbon::parse($data['tgl'])->format('Y-m-d'),
                'kode_pelanggan' => $data['kode_pelanggan'],
                'subtotal' => $subTotal,
            ]);

            //Simpan Item Penjualan

            foreach ($data['items'] as $item) {
                $penjualan->items()->create([
                    'kode_barang' => $item['kode_barang'],
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penjualan berhasil disimpan',
                'data' => [
                    'id_nota' => $id_nota,
                    'subtotal' => $subTotal,
                    'items' => $penjualan->items()->with('barang')->get()
                ]
            ], 201);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'error' => $err->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $penjualan = Penjualan::with('pelanggan', 'items.barang')->where('id_nota', $id)->first();

            if (!$penjualan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Penjualan',
                'data' => $penjualan
            ]);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'error' => $err->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tgl' => 'required|date',
            'kode_pelanggan' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.kode_barang' => 'required|string|exists:barangs,kode',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try {
            // Cari penjualan beserta items-nya
            $penjualan = Penjualan::with('items')->where('id_nota', $id)->firstOrFail();

            // Update data utama penjualan
            $penjualan->update([
                'tgl' => $data['tgl'],
                'kode_pelanggan' => $data['kode_pelanggan'],
            ]);

            $items = collect($data['items']);
            $kodeBarangBaru = $items->pluck('kode_barang')->toArray();

            // Hapus item yang tidak ada di request
            $penjualan->items()
                ->whereNotIn('kode_barang', $kodeBarangBaru)
                ->delete();

            $subtotal = 0;

            foreach ($items as $item) {
                // Cari data barang untuk harga
                $barang = Barang::findOrFail($item['kode_barang']);
                $totalPerItem = $barang->harga * $item['qty'];
                $subtotal += $totalPerItem;

                // Cek apakah item sudah ada
                $itemPenjualan = ItemPenjualan::where('nota', $id)
                    ->where('kode_barang', $item['kode_barang'])
                    ->first();

                if ($itemPenjualan) {
                    ItemPenjualan::where('nota', $id)
                        ->where('kode_barang', $item['kode_barang'])
                        ->update(['qty' => $item['qty']]);
                } else {
                    // Buat baru jika belum ada
                    $penjualan->items()->create([
                        'nota' => $id,
                        'kode_barang' => $item['kode_barang'],
                        'qty' => $item['qty'],
                    ]);
                }
            }

            // Update subtotal
            $penjualan->update(['subtotal' => $subtotal]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
                'data' => $penjualan->load('items.barang')
            ]);
        } catch (\Throwable $err) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'error' => $err->getMessage()
            ], 500);
        }
    }



    public function destroy($id)
    {
        try {
            $penjualan = Penjualan::where('id_nota', $id)->first();

            if (!$penjualan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            $penjualan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully'
            ]);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'error' => $err->getMessage()
            ], 500);
        }
    }
}
