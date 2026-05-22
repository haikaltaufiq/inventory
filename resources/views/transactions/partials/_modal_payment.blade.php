{{--
    ════════════════════════════════════════════════════════════════
    MODAL PEMBAYARAN MIDTRANS SNAP
    ════════════════════════════════════════════════════════════════
--}}

<div id="modalPayment"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 md:p-6">
    <div class="w-full max-w-md overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-2xl">

        {{-- HEADER --}}
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <p class="text-xs text-slate-500">Pembayaran transaksi</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">Pilih Metode Bayar</h2>
            </div>
            <button onclick="closePaymentModal()"
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- BODY --}}
        <div class="p-6 space-y-4">

            {{-- INFO TRANSAKSI --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">ID Transaksi</span>
                    <span class="font-semibold text-slate-800" id="paymentTrxId">–</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                    <span class="text-slate-500">Total Tagihan</span>
                    <span class="text-xl font-bold text-slate-900" id="paymentTotal">–</span>
                </div>
            </div>

            {{-- STATUS BADGE --}}
            <div id="paymentStatusBadge" class="hidden">
                <div class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm"
                    id="paymentStatusContent"></div>
            </div>

            {{-- LOADING STATE --}}
            <div id="paymentLoading"
                class="hidden flex-col items-center justify-center py-6 text-center">
                <div
                    class="w-10 h-10 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin mx-auto">
                </div>
                <p class="text-sm text-slate-500 mt-3">Menyiapkan halaman pembayaran...</p>
            </div>

            {{-- ACTION BUTTONS --}}
            <div id="paymentActions" class="space-y-3">
                <button onclick="launchSnap()"
                    id="btnLaunchSnap"
                    class="w-full py-3.5 bg-slate-900 text-white rounded-xl text-sm font-medium
                           hover:bg-slate-800 transition flex items-center justify-center gap-2">
                    <i class="fas fa-credit-card text-xs"></i>
                    Bayar Sekarang
                </button>

                <p class="text-center text-xs text-slate-400">
                    Didukung oleh <span class="font-semibold text-slate-600">Midtrans</span>
                    — QRIS, Transfer Bank, E-Wallet, dll.
                </p>
            </div>

        </div>
    </div>
</div>

{{-- Snap JS: otomatis pilih URL sandbox/production dari DB setting --}}
<script
    src="{{ \App\Models\Setting::get('midtrans_env', 'sandbox') === 'production'
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ $midtransClientKey ?? '' }}"
    id="snapScript">
</script>

<script>
    let _snapToken       = null;
    let _paymentTrxId    = null;
    let _documentUrl     = null;
    let _pollingInterval = null;

    // ─────────────────────────────────────────────────────────────────
    // Buka modal + ambil snap token
    // Tambah parameter ke-3: documentUrl dari result.document_url
    // ─────────────────────────────────────────────────────────────────
    async function openPaymentModal(transactionId, totalFormatted, documentUrl = null) {
        _paymentTrxId = transactionId;
        _documentUrl  = documentUrl;
        _snapToken    = null;

        const modal = document.getElementById('modalPayment');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('paymentTrxId').textContent = '#' + transactionId;
        document.getElementById('paymentTotal').textContent  = totalFormatted || '–';
        document.getElementById('paymentStatusBadge').classList.add('hidden');
        document.getElementById('btnLaunchSnap').disabled = false;

        setPaymentLoading(true);

        try {
            const res = await fetch(`/transactions/${transactionId}/snap-token`);

            // Debug: kalau 404 berarti route belum terdaftar
            if (!res.ok) {
                const text = await res.text();
                console.error('snap-token HTTP error:', res.status, text);
                throw new Error(
                    res.status === 404
                        ? 'Route snap-token belum terdaftar di web.php'
                        : `Server error ${res.status}. Cek log Laravel.`
                );
            }

            const data = await res.json();

            if (data.status !== 'success') {
                throw new Error(data.message || 'Gagal mendapatkan token pembayaran.');
            }

            _snapToken = data.snap_token;

            if (data.client_key) {
                document.getElementById('snapScript')
                    .setAttribute('data-client-key', data.client_key);
            }

            setPaymentLoading(false);

        } catch (err) {
            console.error('openPaymentModal error:', err);
            setPaymentLoading(false);
            showPaymentStatus('error', `<i class="fas fa-times-circle mr-2"></i>${err.message}`);
            document.getElementById('btnLaunchSnap').disabled = true;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Launch Snap popup
    // ─────────────────────────────────────────────────────────────────
    function launchSnap() {
        if (!_snapToken) {
            alert('Token belum siap. Tutup modal lalu coba lagi.');
            return;
        }

        window.snap.pay(_snapToken, {
            onSuccess: function (result) {
                console.log('Snap success:', result);
                markTransactionPaid(_paymentTrxId);
                showPaymentStatus('success',
                    '<i class="fas fa-check-circle mr-2"></i> Pembayaran berhasil!');
                stopPolling();

                setTimeout(() => {
                    closePaymentModal();

                    // ✅ Download invoice pakai document_url dari store()
                    if (_documentUrl) {
                        const a = document.createElement('a');
                        a.href     = _documentUrl;
                        a.target   = '_blank';
                        a.download = '';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }

                    setTimeout(() => location.reload(), 2000);
                }, 1500);
            },

            onPending: function (result) {
                console.log('Snap pending:', result);
                showPaymentStatus('pending',
                    '<i class="fas fa-clock mr-2"></i> Menunggu konfirmasi pembayaran...');
                startPolling(_paymentTrxId);
            },

            onError: function (result) {
                console.error('Snap error:', result);
                showPaymentStatus('error',
                    '<i class="fas fa-times-circle mr-2"></i> Pembayaran gagal. Silakan coba lagi.');
            },

            onClose: function () {
                checkPaymentStatus(_paymentTrxId);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Polling status pembayaran (untuk pending)
    // ─────────────────────────────────────────────────────────────────
    function startPolling(transactionId) {
        stopPolling();
        _pollingInterval = setInterval(() => checkPaymentStatus(transactionId), 5000);
    }

    function stopPolling() {
        if (_pollingInterval) clearInterval(_pollingInterval);
        _pollingInterval = null;
    }

    async function checkPaymentStatus(transactionId) {
        try {
            const res  = await fetch(`/transactions/${transactionId}/payment-status`);
            const data = await res.json();
            if (data.payment_status === 'paid') {
                stopPolling();
                showPaymentStatus('success',
                    '<i class="fas fa-check-circle mr-2"></i> Pembayaran berhasil!');
                setTimeout(() => { closePaymentModal(); location.reload(); }, 2000);
            }
        } catch (_) {}
    }

    // ─────────────────────────────────────────────────────────────────
    // Tutup modal
    // ─────────────────────────────────────────────────────────────────
    async function markTransactionPaid(transactionId) {
        if (!transactionId) return;

        try {
            const res = await fetch(`/transactions/${transactionId}/mark-paid`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const text = await res.text();
                console.error('mark-paid HTTP error:', res.status, text);
            }
        } catch (err) {
            console.error('mark-paid error:', err);
        }
    }

    function closePaymentModal() {
        stopPolling();
        const modal = document.getElementById('modalPayment');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.getElementById('btnLaunchSnap').disabled = false;
        _snapToken = null;
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers UI
    // ─────────────────────────────────────────────────────────────────
    function setPaymentLoading(loading) {
        document.getElementById('paymentLoading').classList.toggle('hidden', !loading);
        document.getElementById('paymentLoading').classList.toggle('flex', loading);
        document.getElementById('paymentActions').classList.toggle('hidden', loading);
    }

    function showPaymentStatus(type, html) {
        const badge   = document.getElementById('paymentStatusBadge');
        const content = document.getElementById('paymentStatusContent');
        const classes = {
            success: 'bg-green-50 border-green-200 text-green-700',
            error:   'bg-red-50 border-red-200 text-red-700',
            pending: 'bg-amber-50 border-amber-200 text-amber-700',
        };
        content.className = `flex items-center gap-2 rounded-xl border px-4 py-3 text-sm ${classes[type] ?? classes.pending}`;
        content.innerHTML = html;
        badge.classList.remove('hidden');
    }
</script>
