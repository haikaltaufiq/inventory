@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="px-5 max-w-5xl space-y-6">

    {{-- PAGE HEADER --}}
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">Pengaturan</h1>
        <p class="text-sm text-slate-500 mt-1">Konfigurasi sistem & integrasi pihak ketiga</p>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
    <div class="flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
        <i class="fas fa-check-circle text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- MIDTRANS SETTINGS --}}
    <form action="{{ route('settings.midtrans.save') }}" method="POST">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200">

            {{-- CARD HEADER --}}
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Midtrans API Configuration</h2>
                        <p class="text-sm text-slate-500">Digunakan untuk proses pembayaran transaksi</p>
                    </div>

                    {{-- STATUS BADGE --}}
                    @if(!empty($midtrans['server_key']))
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <i class="fas fa-circle text-[8px] mr-1"></i> Connected
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                            <i class="fas fa-circle text-[8px] mr-1"></i> Belum Dikonfigurasi
                        </span>
                    @endif
                </div>
            </div>

            {{-- CARD BODY --}}
            <div class="p-6 space-y-6">

                {{-- MODE --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Environment Mode</label>
                    <div class="flex gap-3" x-data="{ mode: '{{ $midtrans['env'] ?? 'sandbox' }}' }">
                        <button type="button"
                            @click="mode = 'production'"
                            :class="mode === 'production' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition">
                            Production
                        </button>
                        <button type="button"
                            @click="mode = 'sandbox'"
                            :class="mode === 'sandbox' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition">
                            Sandbox
                        </button>
                        <input type="hidden" name="midtrans_env" :value="mode">
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        Gunakan <b>Sandbox</b> untuk testing, <b>Production</b> untuk live transaksi.
                    </p>
                </div>

                {{-- SERVER KEY --}}
                <div x-data="{ show: false }">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Server Key</label>
                    <div class="relative">
                        <input
                            :type="show ? 'text' : 'password'"
                            name="midtrans_server_key"
                            value="{{ $midtrans['server_key'] ?? '' }}"
                            placeholder="SB-Mid-server-xxxxxxxxxxxx"
                            class="w-full px-4 py-2.5 pr-16 rounded-xl bg-slate-50 border border-slate-200
                                   text-sm font-mono text-slate-600 focus:outline-none focus:border-slate-400
                                   @error('midtrans_server_key') border-red-400 @enderror">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-3 text-slate-400 hover:text-slate-600 text-xs font-medium px-2">
                            <span x-text="show ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                    @error('midtrans_server_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- CLIENT KEY --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Client Key</label>
                    <input
                        type="text"
                        name="midtrans_client_key"
                        value="{{ $midtrans['client_key'] ?? '' }}"
                        placeholder="SB-Mid-client-xxxxxxxxxxxx"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200
                               text-sm font-mono text-slate-600 focus:outline-none focus:border-slate-400
                               @error('midtrans_client_key') border-red-400 @enderror">
                    @error('midtrans_client_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- WEBHOOK URL (read-only, info saja) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Callback / Webhook URL
                        <span class="ml-2 text-xs text-slate-400 font-normal">(Daftarkan URL ini di dashboard Midtrans)</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            value="{{ url('/api/midtrans/webhook') }}"
                            readonly
                            id="webhookUrl"
                            class="w-full px-4 py-2.5 pr-24 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-600">
                        <button type="button"
                            onclick="copyWebhook()"
                            class="absolute inset-y-0 right-3 text-slate-400 hover:text-slate-600 text-xs font-medium px-2">
                            Salin
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        Masuk ke <a href="https://dashboard.midtrans.com" target="_blank" class="text-blue-500 hover:underline">dashboard.midtrans.com</a>
                        → Settings → Configuration → Payment Notification URL
                    </p>
                </div>

            </div>

            {{-- CARD FOOTER --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-between items-center">
                <p class="text-xs text-slate-500">
                    @if(!empty($midtrans['server_key']))
                        Konfigurasi tersimpan di database
                    @else
                        Belum ada konfigurasi tersimpan
                    @endif
                </p>

                <div class="flex gap-3">
                    <button type="button" id="btnTestConnection"
                        class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        <i class="fas fa-plug mr-1.5 text-xs"></i> Test Connection
                    </button>
                    <button type="submit"
                        class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 transition">
                        <i class="fas fa-save mr-1.5 text-xs"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

{{-- TOAST NOTIFICATION --}}
<div id="toast"
    class="fixed bottom-6 right-6 z-50 hidden items-center gap-3 rounded-2xl border px-5 py-4 shadow-lg text-sm max-w-sm">
</div>

@push('scripts')
<script>
    // ── Copy webhook URL ──────────────────────────────────────
    function copyWebhook() {
        const url = document.getElementById('webhookUrl').value;
        navigator.clipboard.writeText(url).then(() => showToast('URL berhasil disalin!', 'success'));
    }

    // ── Test Connection ───────────────────────────────────────
    document.getElementById('btnTestConnection').addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5 text-xs"></i> Testing...';

        try {
            const res  = await fetch('{{ route("settings.midtrans.test") }}');
            const data = await res.json();

            showToast(data.message, data.status === 'success' ? 'success' : 'error');
        } catch (e) {
            showToast('Gagal melakukan test connection.', 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-plug mr-1.5 text-xs"></i> Test Connection';
        }
    });

    // ── Toast helper ──────────────────────────────────────────
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const isSuccess = type === 'success';

        toast.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border px-5 py-4 shadow-lg text-sm max-w-sm ${
            isSuccess
                ? 'bg-green-50 border-green-200 text-green-700'
                : 'bg-red-50 border-red-200 text-red-700'
        }`;

        toast.innerHTML = `
            <i class="fas fa-${isSuccess ? 'check-circle text-green-500' : 'times-circle text-red-500'}"></i>
            <span>${message}</span>
        `;

        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    }
</script>
@endpush

@endsection
