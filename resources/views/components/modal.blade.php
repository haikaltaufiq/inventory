@props([
    'id',
    'title' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-3xl',
        'xl' => 'max-w-5xl',
    ];
@endphp

<div
    id="{{ $id }}"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/55 p-4"
>
    <div class="max-h-[90vh] w-full overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl {{ $sizes[$size] ?? $sizes['md'] }}">
        @if ($title)
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <button
                    type="button"
                    onclick="closeModal('{{ $id }}')"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900"
                >
                    <span class="text-lg leading-none">&times;</span>
                </button>
            </div>
        @endif

        <div class="max-h-[calc(90vh-84px)] overflow-y-auto p-6">
            {{ $slot }}
        </div>
    </div>
</div>
