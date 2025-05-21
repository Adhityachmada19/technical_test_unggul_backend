<?php

namespace App\Http\Controllers\API\Pelanggan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PelangganController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->perPage ? request()->perPage : 5;
            $pelanggan = Pelanggan::when(request()->search, function ($pelanggan) {
                $pelanggan = $pelanggan->where('nama', 'LIKE', '%' . request()->search . '%');
            })->paginate($perPage);


            return response()->json([
                'success' => true,
                'message' => "List Pelanggan",
                'data' => $pelanggan,
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
            'domisili' => 'required',
            'jenis_kelamin' => 'required|in:PRIA,WANITA',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Variable untuk menampung response dari dalam closure
            $response = DB::transaction(function () use ($request) {

                $last = Pelanggan::withTrashed()
                    ->selectRaw("CAST(SUBSTRING_INDEX(id_pelanggan, '_', -1) AS UNSIGNED) as nomor")
                    ->orderByDesc('nomor')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = ($last?->nomor ?? 0) + 1; // Null-safe operator untuk kasus pertama kali
                $id_pelanggan = "PELANGGAN_" . $nextNumber;

                $pelanggan = Pelanggan::create([
                    'id_pelanggan' => $id_pelanggan,
                    'nama' => $request->nama,
                    'domisili' => $request->domisili,
                    'jenis_kelamin' => $request->jenis_kelamin
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Created Data Successfully',
                    'data' => $pelanggan
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
            $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Pelanggan',
                'data' => $pelanggan
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
            $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required',
                'domisili' => 'required',
                'jenis_kelamin' => 'required|in:PRIA,WANITA',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $pelanggan->update([
                'nama' => $request->nama,
                'domisili' => $request->domisili,
                'jenis_kelamin' => $request->jenis_kelamin
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
                'data' => $pelanggan
            ]);
        } catch (\Throwable $err) {
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
            $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

            if (!$pelanggan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

            $pelanggan->delete();

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
