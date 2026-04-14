@push('scripts')
    <script>
        function productGrid() {
            return {
                @include('products.scripts._state')
                @include('products.scripts._row_spec')
                @include('products.scripts._supplier_ui')
                @include('products.scripts._form_logic')
            };
        }
    </script>
@endpush
