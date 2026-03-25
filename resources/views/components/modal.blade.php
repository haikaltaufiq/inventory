@props([
'id',
'title' => null,
'size' => 'md', // sm | md | lg | xl
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
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-lg shadow w-full {{ $sizes[$size] ?? $sizes['md'] }}">
        {{-- Header --}}
        @if($title)
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            <button onclick="closeModal('{{ $id }}')" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
        @endif

        {{-- Content --}}
        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>