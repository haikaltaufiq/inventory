<x-modal id="modal-unsaved-navigation" title="Perubahan Belum Disimpan" size="sm">
    <div class="space-y-5">
        <div class="flex items-start gap-3">
            <div
                class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                <i class="fas fa-exclamation-triangle text-base"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900">Simpan perubahan dulu?</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Ada perubahan data produk yang masih tersimpan sementara. Simpan dulu untuk tetap di halaman
                    ini, atau abaikan untuk lanjut pindah halaman.
                </p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" @click="closeUnsavedNavigationModal()"
                class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </button>
            <button type="button" @click="saveAndStay()"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Save
            </button>
            <button type="button" @click="ignoreUnsavedAndNavigate()"
                class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-100">
                Abaikan
            </button>
        </div>
    </div>
</x-modal>
