@push('scripts')
<script>
    function posSystem() {
        return {
            // === FILTER STATE ===
            searchQuery: '',
            activeCat: 'Semua',
            filterCompatible: false,
            // === CHECKOUT STATE ===
            additionalFees: {
                installation: 0,
                service_labor: 0,
                shipping: 0,
                marketing: 0,
            },
            conflictMessage: '',
            draftBuildName: '',
            // === MODAL STATE ===
            detailOpen: false,
            detailZoom: false,
            imageViewerOpen: false,
            detailProduct: null,
            // === DATA SOURCES ===
            categories: @json($categories),
            products: @json($products).map(p => ({
                id: p.id,
                name: p.name,
                category_name: p.category ? p.category.name : 'Uncategorized',
                base_price: Number(p.base_price ?? 0),
                socket: p.socket ?? null,
                ram_type: p.ram_type ?? null,
                image: p.image_url ?? @json(asset('assets/no-image.svg')),
                description: p.description ?? null,
                specs: p.specs ?? [],
                suppliers: (p.suppliers || []).map(s => {
                    const sourceKey = s.pivot && s.pivot.id ? `pivot-${s.pivot.id}` :
                        `${s.supplier_id ?? s.id}-${s.pivot && s.pivot.condition ? s.pivot.condition : 'default'}`;

                    return {
                        id: s.id,
                        supplier_id: s.supplier_id ?? s.id,
                        source_key: sourceKey,
                        product_supplier_id: s.pivot ? s.pivot.id : null,
                        name: s.nama_supplier,
                        condition: s.pivot ? s.pivot.condition : null,
                        pivot_stock: Number(s.pivot ? s.pivot.stock : 0),
                        pivot_price: Number(s.pivot ? s.pivot.harga_jual_manual : 0)
                    };
                })
            })),
            // === CART STATE ===
            cart: [],
            selectedProduct: {
                suppliers: []
            },
            tempSupplier: {},
            // === TRANSACTION FORM ===
            transactionData: {
                sales: @js($salesUsers->first()?->name ?? ''),
                customerName: '',
                customerPhone: '',
                customerAddress: '',
                type: 'Invoice',
                transactionMode: 'sparepart',
                buildName: '',
            },

            // === COMPUTED: FILTERED PRODUCTS ===
            get filteredProducts() {
                return this.products.filter(p => {
                    const matchSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchCat = this.activeCat === 'Semua' || p.category_name === this.activeCat;
                    if (this.filterCompatible) {
                        return matchSearch && matchCat && !this.isProductIncompatible(p);
                    }
                    return matchSearch && matchCat;
                });
            },

            // === COMPUTED: SUBTOTAL ===
            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            },

            // === COMPUTED: FINAL TOTAL ===
            get serviceFee() {
                return (Number(this.additionalFees.installation) || 0) +
                    (Number(this.additionalFees.service_labor) || 0) +
                    (Number(this.additionalFees.shipping) || 0) +
                    (Number(this.additionalFees.marketing) || 0);
            },

            get finalTotal() {
                return this.subtotal + (Number(this.serviceFee) || 0);
            },

            // === HELPER: NUMBER FORMAT ===
            formatNumber(n) {
                return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
            },

            // === SPEC NORMALIZER ===
            getSpecs(p) {
                if (!p) return [];
                const specs = Array.isArray(p.specs) ? p.specs.map(s => ({
                    key: s.key,
                    value: s.value
                })) : [];

                if (specs.length === 0) {
                    if (p.socket) specs.push({
                        key: 'Socket',
                        value: p.socket
                    });
                    if (p.ram_type) specs.push({
                        key: 'RAM',
                        value: p.ram_type
                    });
                }
                return specs;
            },

            // === DETAIL MODAL ===
            openDetail(product) {
                this.detailProduct = product;
                this.detailZoom = false;
                this.imageViewerOpen = false;
                this.detailOpen = true;
            },

            closeDetail() {
                this.detailOpen = false;
                this.detailZoom = false;
                this.imageViewerOpen = false;
                this.detailProduct = null;
            },

            // === IMAGE VIEWER ===
            openImageViewer() {
                if (!this.detailProduct) return;
                this.imageViewerOpen = true;
            },

            closeImageViewer() {
                this.imageViewerOpen = false;
            },

            // === COMPATIBILITY CHECK ===
            toggleCompatibility() {
                if (this.transactionData.transactionMode === 'rakit_pc') {
                    this.filterCompatible = true;
                    return;
                }

                this.filterCompatible = !this.filterCompatible;
            },

            setTransactionMode(mode) {
                this.transactionData.transactionMode = mode;
                if (mode === 'rakit_pc') {
                    this.filterCompatible = true;
                    this.draftBuildName = this.transactionData.buildName || '';
                    openModal('modalBuildName');
                    return;
                }

                this.transactionData.buildName = '';
            },

            applyBuildName() {
                const buildName = (this.draftBuildName || '').trim();
                if (!buildName) {
                    alert('Nama barang rakit PC wajib diisi.');
                    return;
                }

                this.transactionData.buildName = buildName;
                closeModal('modalBuildName');
            },

            isProductIncompatible(p) {
                const cartProcie = this.cart.find(i => i.category_name === 'Processor' || i.category_name === 'CPU');
                const cartMobo = this.cart.find(i => i.category_name === 'Motherboard');
                const cartRam = this.cart.find(i => i.category_name === 'RAM' || i.category_name === 'Memory');

                if ((p.category_name === 'Motherboard' && cartProcie && p.socket !== cartProcie.socket) ||
                    (p.category_name === 'Processor' && cartMobo && p.socket !== cartMobo.socket)) return true;

                if ((p.category_name === 'RAM' && cartMobo && p.ram_type !== cartMobo.ram_type) ||
                    (p.category_name === 'Motherboard' && cartRam && p.ram_type !== cartRam.ram_type)) return true;

                return false;
            },

            // === CART: ADD ===
            handleAddToCart(product) {
                this.selectedProduct = product;
                if (!product.suppliers || product.suppliers.length === 0) {
                    alert('Stok dari supplier tidak tersedia.');
                    return;
                }
                if (product.suppliers.length === 1) {
                    this.checkCompatibilityBeforeAdd(product, product.suppliers[0]);
                    return;
                }
                openModal('modalSupplier');
            },

            // === CART: PRECHECK CONFLICT ===
            checkCompatibilityBeforeAdd(p, s) {
                const cartProcie = this.cart.find(i => i.category_name === 'Processor' || i.category_name === 'CPU');
                const cartMobo = this.cart.find(i => i.category_name === 'Motherboard');

                let conflict = false;
                if ((p.category_name === 'Motherboard' && cartProcie && p.socket !== cartProcie.socket) ||
                    (p.category_name === 'Processor' && cartMobo && p.socket !== cartMobo.socket)) {
                    this.conflictMessage = `Socket ${p.socket} tidak cocok dengan build saat ini.`;
                    conflict = true;
                }

                if (conflict) {
                    this.tempSupplier = s;
                    openModal('modalConflict');
                } else {
                    this.confirmAddToCart(p, s, false);
                }
            },

            // === CART: FORCE ADD ===
            forceAddToCart() {
                this.confirmAddToCart(this.selectedProduct, this.tempSupplier, true);
                closeModal('modalConflict');
            },

            // === CART: CONFIRM ADD ===
            confirmAddToCart(p, s, isConflict) {
                const cartId = `${p.id}-${s.source_key}`;
                const found = this.cart.find(i => i.cartId === cartId);

                if (found) {
                    if (found.qty + 1 > s.pivot_stock) return alert('Stok habis.');
                    found.qty++;
                } else {
                    this.cart.push({
                        ...p,
                        cartId,
                        source_key: s.source_key,
                        supplier_id: s.supplier_id,
                        product_supplier_id: s.product_supplier_id,
                        supplierName: s.condition ? `${s.name} (${s.condition})` : s.name,
                        price: s.pivot_price,
                        qty: 1,
                        isConflict: isConflict
                    });
                }
                closeModal('modalSupplier');
            },

            // === CART: UPDATE QTY ===
            updateQty(cartId, delta) {
                const item = this.cart.find(i => i.cartId === cartId);
                if (!item) return;

                const newQty = item.qty + delta;
                const productSource = this.products.find(p => p.id === item.id);
                if (!productSource) return;

                const supplierSource = productSource.suppliers.find(s => s.source_key === item.source_key);
                if (!supplierSource) return;

                if (newQty > supplierSource.pivot_stock) return alert('Stok supplier tidak cukup.');
                if (newQty <= 0) return this.removeFromCart(cartId);

                item.qty = newQty;
            },

            // === CART: REMOVE ===
            removeFromCart(cartId) {
                this.cart = this.cart.filter(i => i.cartId !== cartId);
            },

            // === SUBMIT ORDER ===
            async submitOrder() {
                if (!this.transactionData.sales) return alert('Sales wajib dipilih.');
                if (!this.transactionData.customerName) return alert('Nama customer wajib diisi.');
                if (this.transactionData.transactionMode === 'rakit_pc' && !this.transactionData.buildName) {
                    this.draftBuildName = '';
                    openModal('modalBuildName');
                    return alert('Isi nama barang untuk transaksi Rakit PC.');
                }

                const payload = {
                    transaction_data: this.transactionData,
                    service_fee: this.serviceFee,
                    additional_fees: this.additionalFees,
                    cart: this.cart.map(i => ({
                        product_id: i.id,
                        name: i.name,
                        supplier_id: i.supplier_id,
                        product_supplier_id: i.product_supplier_id,
                        qty: i.qty,
                        price: i.price,
                        is_conflict: i.isConflict
                    }))
                };

                try {
                    const response = await fetch("{{ route('transactions.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();
                    if (response.ok && result.status === 'success') {
                        if (result.document_url) {
                            const iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = result.document_url;
                            document.body.appendChild(iframe);
                        }

                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 800);
                    } else {
                        alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                    }
                } catch (error) {
                    alert('Tidak dapat terhubung ke server.');
                }
            }
        }
    }
</script>
@endpush
