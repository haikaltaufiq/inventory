@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="px-5 max-w-5xl space-y-6">

    {{-- PAGE HEADER --}}
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
            Pengaturan
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Konfigurasi sistem & integrasi pihak ketiga
        </p>
    </div>

    {{-- MIDTRANS SETTINGS --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200">

        {{-- CARD HEADER --}}
        <div class="p-6 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">
                        Midtrans API Configuration
                    </h2>
                    <p class="text-sm text-slate-500">
                        Digunakan untuk proses pembayaran transaksi
                    </p>
                </div>

                {{-- STATUS BADGE (DUMMY) --}}
                <span class="px-3 py-1 rounded-full text-xs font-medium
                             bg-green-100 text-green-700">
                    Connected
                </span>
            </div>
        </div>

        {{-- CARD BODY --}}
        <div class="p-6 space-y-6">

            {{-- MODE --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Environment Mode
                </label>

                <div class="flex gap-3">
                    <button
                        class="px-4 py-2 rounded-xl text-sm font-medium
                               bg-slate-900 text-white">
                        Production
                    </button>

                    <button
                        class="px-4 py-2 rounded-xl text-sm font-medium
                               bg-slate-100 text-slate-600 hover:bg-slate-200">
                        Sandbox
                    </button>
                </div>

                <p class="text-xs text-slate-500 mt-2">
                    Gunakan <b>Sandbox</b> untuk testing, <b>Production</b> untuk live transaksi.
                </p>
            </div>

            {{-- SERVER KEY --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Server Key
                </label>

                <div class="relative">
                    <input
                        type="password"
                        value="SB-Mid-server-xxxxxxxxxxxx"
                        disabled
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50
                               border border-slate-200 text-sm font-mono
                               text-slate-600">

                    <button
                        type="button"
                        class="absolute inset-y-0 right-3 text-slate-400 hover:text-slate-600 text-sm">
                        Show
                    </button>
                </div>
            </div>

            {{-- CLIENT KEY --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Client Key
                </label>

                <input
                    type="text"
                    value="SB-Mid-client-xxxxxxxxxxxx"
                    disabled
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50
                           border border-slate-200 text-sm font-mono
                           text-slate-600">
            </div>

            {{-- WEBHOOK URL --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Callback / Webhook URL
                </label>

                <input
                    type="text"
                    value="https://yourdomain.com/api/midtrans/webhook"
                    disabled
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50
                           border border-slate-200 text-sm
                           text-slate-600">
            </div>

        </div>

        {{-- CARD FOOTER --}}
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-between items-center">
            <p class="text-xs text-slate-500">
                Terakhir diperbarui: 27 Jan 2026
            </p>

            <div class="flex gap-3">
                <button
                    class="px-4 py-2 rounded-xl text-sm font-medium
                           bg-slate-100 text-slate-600 hover:bg-slate-200">
                    Test Connection
                </button>

                <button
                    class="px-4 py-2 rounded-xl text-sm font-medium
                           bg-slate-900 text-white hover:bg-slate-800">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>



</div>
@endsection