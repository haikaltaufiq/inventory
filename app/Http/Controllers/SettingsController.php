<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $midtrans = Setting::midtransConfig();

        return view('settings.index', compact('midtrans'));
    }

    // ─────────────────────────────────────────────
    // Simpan konfigurasi Midtrans
    // ─────────────────────────────────────────────
    public function saveMidtrans(Request $request)
    {
        $request->validate([
            'midtrans_server_key' => 'required|string',
            'midtrans_client_key' => 'required|string',
            'midtrans_env'        => 'required|in:sandbox,production',
        ]);

        Setting::setMany([
            'midtrans_server_key' => encrypt($request->midtrans_server_key),
            'midtrans_client_key' => encrypt($request->midtrans_client_key),
            'midtrans_env'        => $request->midtrans_env,
        ]);

        // Bust the MidtransService config cache so next request picks up new keys.
        Cache::forget('midtrans:config');

        return redirect()->route('settings')->with('success', 'Konfigurasi Midtrans berhasil disimpan.');
    }

    // ─────────────────────────────────────────────
    // Test koneksi ke Midtrans (opsional)
    // ─────────────────────────────────────────────
    public function testConnection()
    {
        $config = Setting::midtransConfig();

        if (empty($config['server_key'])) {
            return response()->json(['status' => 'error', 'message' => 'Server Key belum diisi.']);
        }

        try {
            \Midtrans\Config::$serverKey = $config['server_key'];
            \Midtrans\Config::$isProduction = $config['is_production'];

            // Cek status transaksi dummy
            \Midtrans\Transaction::status('test-' . time());

            return response()->json(['status' => 'success', 'message' => 'Koneksi Berhasil!']);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            $statusCode = $e->getCode();

            if ($statusCode == 404 || str_contains($message, '404')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Koneksi Berhasil! API Key valid dan terhubung.'
                ]);
            }

            if ($statusCode == 401 || str_contains($message, '401')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal: Server Key salah atau salah mode (Sandbox/Production).'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $message
            ]);
        }
    }
}
