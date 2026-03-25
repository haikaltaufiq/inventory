<div>
    <div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 shadow overflow-hidden']) }}>
        <div class="overflow-x-auto">
            <table class="w-full">
                {{ $slot }}
            </table>
        </div>
    </div>
</div>