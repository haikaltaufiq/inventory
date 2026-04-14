@extends('layouts.app')

@section('title', 'Manajemen Nilai Spesifikasi')

@section('content')
    <div class="px-4 pb-10 lg:px-5" x-data="specPresets()">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-[29px] font-semibold tracking-tight text-slate-800">Manajemen Nilai Spesifikasi</h1>
            <p class="mt-1 text-[13px] text-slate-500">
                Kelola daftar nilai yang muncul di dropdown saat input spesifikasi produk.
                Nilai di sini dipakai oleh semua kategori yang punya field relevan.
            </p>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <i class="fas fa-check-circle text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="fas fa-exclamation-circle text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Search / Filter --}}
        <div class="mb-5 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-sm">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Cari nama field atau nilai..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-[13px] outline-none transition focus:border-slate-400"
                >
            </div>
        </div>

        {{-- Legend --}}
        <div class="mb-5 flex flex-wrap items-center gap-4 text-[12px] text-slate-500">
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-slate-900"></span>
                <span>Required (wajib untuk simulasi rakit PC)</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                <span>Optional</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded border border-dashed border-amber-400 bg-amber-50"></span>
                <span>Nilai dari produk (belum jadi preset)</span>
            </div>
        </div>

        {{-- Grid sections --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($sections as $section)
                <div
                    x-show="sectionVisible(@js($section))"
                    class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden"
                >
                    {{-- Section header --}}
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-[13px] font-semibold text-slate-800">{{ $section['label'] }}</h3>
                                @if ($section['required'])
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] text-white">
                                        Required
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-medium uppercase tracking-[0.14em] text-slate-500">
                                        Optional
                                    </span>
                                @endif
                            </div>
                            <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-400">
                                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">{{ $section['key'] }}</code>
                                <span>·</span>
                                <span>{{ $section['category'] }}</span>
                            </div>
                        </div>
                        {{-- Import dari produk (kalau ada) --}}
                        @if (!empty($section['product_values']))
                            <form action="{{ route('spec-presets.import') }}" method="POST">
                                @csrf
                                <input type="hidden" name="spec_key" value="{{ $section['key'] }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] font-medium text-amber-700 transition hover:bg-amber-100"
                                    title="Import {{ count($section['product_values']) }} nilai dari produk yang sudah ada">
                                    <i class="fas fa-download text-[10px]"></i>
                                    Import {{ count($section['product_values']) }}
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Hint --}}
                    @if ($section['hint'])
                        <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-2 text-[11px] leading-4 text-slate-500">
                            {{ $section['hint'] }}
                        </div>
                    @endif

                    {{-- Daftar preset values --}}
                    <div class="px-4 py-3">
                        {{-- Preset values (tersimpan) --}}
                        @if (!empty($section['presets']))
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($section['presets'] as $preset)
                                    <div class="group flex items-center gap-1 rounded-full bg-slate-100 pl-2.5 pr-1 py-1">
                                        <span class="text-[12px] font-medium text-slate-700">{{ $preset['value'] }}</span>
                                        <form action="{{ route('spec-presets.destroy', $preset['id']) }}" method="POST"
                                            onsubmit="return confirm('Hapus nilai \'{{ $preset['value'] }}\' dari preset?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex h-5 w-5 items-center justify-center rounded-full text-slate-400 opacity-0 transition hover:bg-red-100 hover:text-red-500 group-hover:opacity-100">
                                                <i class="fas fa-times text-[9px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-[12px] italic text-slate-400">Belum ada nilai tersimpan.</p>
                        @endif

                        {{-- Product values (belum jadi preset) --}}
                        @if (!empty($section['product_values']))
                            <div class="mt-3 border-t border-dashed border-slate-200 pt-3">
                                <p class="mb-2 text-[10px] uppercase tracking-[0.14em] text-amber-600">Dari produk (belum preset)</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($section['product_values'] as $value)
                                        <span class="rounded-full border border-dashed border-amber-300 bg-amber-50 px-2.5 py-1 text-[12px] text-amber-700">
                                            {{ $value }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Form tambah nilai baru --}}
                        <div class="mt-3 border-t border-slate-100 pt-3"
                            x-data="{ open: false, value: '' }">
                            <button
                                type="button"
                                @click="open = !open"
                                class="inline-flex items-center gap-1.5 text-[12px] font-medium text-slate-500 transition hover:text-slate-800"
                            >
                                <i class="fas fa-plus text-[10px]"></i>
                                Tambah nilai
                            </button>
                            <div x-show="open" x-cloak class="mt-2 flex gap-2">
                                <form action="{{ route('spec-presets.store') }}" method="POST" class="flex flex-1 gap-2">
                                    @csrf
                                    <input type="hidden" name="spec_key" value="{{ $section['key'] }}">
                                    <input
                                        type="text"
                                        name="spec_value"
                                        x-model="value"
                                        placeholder="{{ $section['label'] }} baru..."
                                        class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[13px] outline-none transition focus:border-slate-400"
                                        required
                                    >
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-slate-900 px-3 py-1.5 text-[12px] font-medium text-white transition hover:bg-slate-700"
                                    >
                                        Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Empty state --}}
        <div x-show="noResults()" x-cloak class="mt-10 text-center text-sm text-slate-400">
            <i class="fas fa-search mb-2 text-2xl opacity-30"></i>
            <p>Tidak ada field yang cocok dengan pencarian.</p>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function specPresets() {
            return {
                search: '',

                sectionVisible(section) {
                    if (!this.search.trim()) return true;
                    const q = this.search.trim().toLowerCase();
                    const inKey   = section.key.toLowerCase().includes(q);
                    const inLabel = section.label.toLowerCase().includes(q);
                    const inCategory = section.category.toLowerCase().includes(q);
                    const inValues = [
                        ...section.presets.map(p => p.value.toLowerCase()),
                        ...section.product_values.map(v => v.toLowerCase()),
                    ].some(v => v.includes(q));

                    return inKey || inLabel || inCategory || inValues;
                },

                noResults() {
                    if (!this.search.trim()) return false;
                    const sections = @json($sections);
                    return !sections.some(s => this.sectionVisible(s));
                },
            };
        }
    </script>
@endpush
