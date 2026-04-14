<div class="px-4 pb-6 lg:px-5 lg:pb-8" x-data="productGrid()" x-init="boot()">
    @php
        $oldProducts = old('products', []);
        $productKeys = array_keys(is_array($oldProducts) ? $oldProducts : []);

        $gridErrorGroups = collect($errors->getMessages())->reduce(function (
            array $carry,
            array $messages,
            string $key,
        ) use ($oldProducts, $productKeys) {
            if (!\Illuminate\Support\Str::startsWith($key, 'products.')) {
                return $carry;
            }

            if (!preg_match('/^products\.([^.]+)\.(.+)$/', $key, $matches)) {
                return $carry;
            }

            $clientKey = $matches[1];
            $row = data_get($oldProducts, $clientKey, []);
            $rowIndex = array_search($clientKey, $productKeys, true);
            $rowNumber = $rowIndex === false ? null : $rowIndex + 1;
            $productId = (int) data_get($row, 'id', 0);
            $productName = trim((string) data_get($row, 'name', ''));
            $title =
                $productName !== ''
                    ? $productName
                    : ($productId > 0
                        ? '#PRD-' . str_pad((string) $productId, 4, '0', STR_PAD_LEFT)
                        : 'Produk baru');

            if (!isset($carry[$clientKey])) {
                $carry[$clientKey] = [
                    'client_key' => $clientKey,
                    'title' => $title,
                    'subtitle' => $rowNumber !== null ? 'Baris ' . $rowNumber : 'Produk draft',
                    'messages' => [],
                ];
            }

            foreach ($messages as $message) {
                if (!in_array($message, $carry[$clientKey]['messages'], true)) {
                    $carry[$clientKey]['messages'][] = $message;
                }
            }

            return $carry;
        }, []);

        $gridErrorGroups = array_values($gridErrorGroups);
        $gridErrorsByRow = collect($gridErrorGroups)
            ->mapWithKeys(fn(array $group) => [$group['client_key'] => $group['messages']])
            ->all();
        $generalErrors = collect($errors->getMessages())
            ->reject(fn(array $messages, string $key) => \Illuminate\Support\Str::startsWith($key, 'products.'))
            ->flatten()
            ->unique()
            ->values()
            ->all();
    @endphp

    @include('products.partials._grid_header_stats')

    @include('products.partials._grid_errors')

    @include('products.partials._grid_filters')

    @include('products.partials._grid_table')

    @include('products.partials._grid_modal_detail')

    @include('products.partials._grid_modal_suppliers')

    @include('products.partials._grid_modal_preview')

    @include('products.partials._grid_modal_unsaved')

    <div class="mt-6">{{ $products->links() }}</div>
</div>
