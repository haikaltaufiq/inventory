<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

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
            'midtrans_server_key' => $request->midtrans_server_key,
            'midtrans_client_key' => $request->midtrans_client_key,
            'midtrans_env'        => $request->midtrans_env,
        ]);

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
            \Midtrans\Config::$serverKey    = $config['server_key'];
            \Midtrans\Config::$isProduction = $config['is_production'];

            // Cek dengan ambil status order dummy (akan 404, tapi autentikasi valid)
            \Midtrans\Transaction::status('test-connection-check');

        } catch (\Midtrans\Exceptions\MidtransApiException $e) {
            // 404 = key valid, order tidak ada → connection OK
            if ($e->getCode() === 404) {
                return response()->json(['status' => 'success', 'message' => 'Koneksi berhasil! API Key valid.']);
            }

            return response()->json(['status' => 'error', 'message' => 'API Key tidak valid: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung: ' . $e->getMessage()]);
        }

        return response()->json(['status' => 'success', 'message' => 'Koneksi berhasil!']);
    }
}
