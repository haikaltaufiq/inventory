@if (!empty($gridErrorGroups) || !empty($generalErrors))
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
        <p class="text-sm font-semibold text-red-700">Masih ada data yang perlu diperbaiki sebelum disimpan.</p>

        @if (!empty($gridErrorGroups))
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach ($gridErrorGroups as $group)
                    <div class="rounded-2xl border border-red-200 bg-white/80 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-red-700">{{ $group['title'] }}</p>
                                <p class="mt-1 text-xs text-red-500">{{ $group['subtitle'] }} •
                                    {{ count($group['messages']) }} hal perlu dicek</p>
                            </div>
                            <span
                                class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700">
                                {{ count($group['messages']) }} issue
                            </span>
                        </div>
                        <ul class="mt-3 space-y-1 text-sm text-red-600">
                            @foreach ($group['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        @if (!empty($generalErrors))
            <ul class="mt-4 space-y-1 text-sm text-red-600">
                @foreach ($generalErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
