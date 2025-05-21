<?php

namespace App\Http\Controllers\API\Barang;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->perPage ? request()->perPage : 5;
            $barang = Barang::when(request()->search, function ($barang) {
                $barang = $barang->where('nama', 'LIKE', '%' . request()->search . '%')->orWhere('kategori', 'LIKE', '%' . request()->search . '%');
            })->paginate($perPage);


            return response()->json([
                'success' => true,
                'message' => "List barang",
                'data' => $barang,
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
            'nama' => 'required',
            'kategori' => 'required',
            'harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Variable untuk menampung response dari dalam closure
            $response = DB::transaction(function () use ($request) {

                $last = barang::withTrashed()
                    ->selectRaw("CAST(SUBSTRING_INDEX(kode, '_', -1) AS UNSIGNED) as nomor")
                    ->orderByDesc('nomor')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = ($last?->nomor ?? 0) + 1; // Null-safe operator untuk kasus pertama kali
                $id_barang = "BRG_" . $nextNumber;

                $barang = barang::create([
                    'kode' => $id_barang,
                    'nama' => $request->nama,
                    'kategori' => $request->kategori,
                    'harga' => $request->harga
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Created Data Successfully',
                    'data' => $barang
                ], 201);
            });

            return $response;
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => "Internal Server Error",
                'error' => $err->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $barang = barang::where('kode', $id)->first();

            if (!$barang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Barang',
                'data' => $barang
            ]);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
                'error' => $err->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $barang = barang::where('kode', $id)->first();

            if (!$barang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required',
                'kategori' => 'required',
                'harga' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $barang->update([
                'nama' => $request->nama,
                'kategori' => $request->kategori,
                'harga' => $request->harga
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
                'data' => $barang
            ]);
        } catch (\Throwable $err) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Eror',
                'error' => $err->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $barang = barang::where('kode', $id)->first();

            if (!$barang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            $barang->delete();

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
