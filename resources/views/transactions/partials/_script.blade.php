@push('scripts')
    <script>
        function posSystem() {
            return {
                init() {
                    this.loadProducts(true);

                    this.$watch('searchQuery', () => {
                        clearTimeout(this.productSearchTimer);
                        this.productSearchTimer = setTimeout(() => {
                            if (this.transactionData.transactionMode === 'sparepart') {
                                this.loadProducts(true);
                            }
                        }, 250);
                    });

                    this.$watch('activeCat', () => {
                        if (this.transactionData.transactionMode === 'sparepart') {
                            this.loadProducts(true);
                        }
                    });

                    this.$nextTick(() => {
                        const scroller = this.$root.closest('section');
                        if (!scroller) return;

                        scroller.addEventListener('scroll', () => {
                            if (this.transactionData.transactionMode !== 'sparepart') return;
                            if (this.productLoading || !this.productHasMore) return;

                            const nearBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 500;
                            if (nearBottom) this.loadProducts(false);
                        }, { passive: true });
                    });
                },
                // === FILTER STATE ===
                searchQuery: '',
                activeCat: 'Semua',
                // filterCompatible: false,
                // === CHECKOUT STATE ===
                additionalFees: {
                    installation: 0,
                    service_labor: 0,
                    discount: 0,
                },
                conflictMessage: '',
                draftBuildName: '',
                selectedCustomerId: '',
                // === MODAL STATE ===
                detailOpen: false,
                detailZoom: false,
                imageViewerOpen: false,
                detailProduct: null,
                buildDetailOpen: false,
                detailBuild: null,
                modeSwitchWarningOpen: false,
                pendingTransactionMode: null,
                // === DATA SOURCES ===
                customers: @json($customers),
                categories: @json($categories),
                products: [],
                productPage: 1,
                productHasMore: true,
                productLoading: false,
                productsLoaded: false,
                productSearchTimer: null,
                // === CART STATE ===
                cart: [],
                selectedProduct: {
                    suppliers: []
                },
                tempSupplier: {},
                // === TRANSACTION FORM ===
                transactionData: {
                    sales: @js($salesUsers->first()?->name ?? ''),
                    customer_id: null,
                    customerName: '',
                    customerPhone: '',
                    customerAddress: '',
                    type: 'Invoice',
                    transactionMode: 'sparepart',
                    buildName: '',
                    paymentMethod: 'midtrans',
                },
                // === BUILD MODE STATE ===
                activeBuild: null,
                buildMarginPct: 0,

                // === COMPUTED: FILTERED PRODUCTS ===
                get filteredProducts() {
                    return this.products;
                },

                get filteredBuilds() {
                    const searchLower = String(this.searchQuery || '').trim().toLowerCase();

                    return this.savedBuilds.filter(build => {
                        if (!searchLower) return true;

                        const components = this.buildComponents(build)
                            .map(c => String(c.name || '').toLowerCase())
                            .join(' ');

                        return String(build.name || '').toLowerCase().includes(searchLower) ||
                            String(build.notes || '').toLowerCase().includes(searchLower) ||
                            components.includes(searchLower);
                    });
                },

                // === COMPUTED: SUBTOTAL ===
                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                // === COMPUTED: MARGIN AMOUNT (hanya di build mode) ===
                get buildMarginAmount() {
                    if (!this.activeBuild || !this.buildMarginPct) return 0;
                    return Math.round(this.subtotal * this.buildMarginPct / 100);
                },

                // === COMPUTED: FINAL TOTAL ===
                get serviceFee() {
                    return (Number(this.additionalFees.installation) || 0) +
                        (Number(this.additionalFees.service_labor) || 0);
                },

                get totalChargeAmount() {
                    return this.buildMarginAmount + this.serviceFee;
                },

            get discountAmount() {
                const discountPct = Math.min(100, Math.max(0, Number(this.additionalFees.discount) || 0));
                return Math.round((this.subtotal + this.totalChargeAmount) * discountPct / 100);
            },

                // === COMPUTED: FINAL TOTAL (modal + margin + biaya tambahan - diskon) ===
                get finalTotal() {
                    return Math.max(0, this.subtotal + this.totalChargeAmount - this.discountAmount);
                },

                get documentActionLabel() {
                    if (this.transactionData.type === 'Quotation') return 'Export Quotation';
                    if (this.transactionData.type === 'DO') return 'Export Delivery Order';
                    return 'Simpan Transaksi';
                },

                // === HELPER: NUMBER FORMAT ===
                formatNumber(n) {
                    return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
                },

                normalizeProduct(p) {
                    return {
                        id: p.id,
                        name: p.name,
                        serial_number: p.serial_number ?? null,
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
                    };
                },

                mergeProducts(rawProducts) {
                    const byId = new Map(this.products.map(product => [Number(product.id), product]));
                    rawProducts.map(product => this.normalizeProduct(product)).forEach(product => {
                        byId.set(Number(product.id), product);
                    });
                    this.products = Array.from(byId.values());
                },

                async loadProducts(reset = false, ids = []) {
                    if (this.productLoading) return;

                    if (reset) {
                        this.productPage = 1;
                        this.productHasMore = true;
                        this.products = [];
                    }

                    if (!this.productHasMore && ids.length === 0) return;

                    this.productLoading = true;

                    const params = new URLSearchParams({
                        page: String(ids.length ? 1 : this.productPage),
                        per_page: ids.length ? String(Math.max(ids.length, 24)) : '72',
                    });

                    if (ids.length) {
                        ids.forEach(id => params.append('ids[]', id));
                    } else {
                        const search = String(this.searchQuery || '').trim();
                        if (search) params.set('search', search);
                        if (this.activeCat && this.activeCat !== 'Semua') params.set('category', this.activeCat);
                    }

                    try {
                        const response = await fetch(`{{ route('transactions.products') }}?${params}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!response.ok) throw new Error(`Server error ${response.status}`);

                        const result = await response.json();
                        this.mergeProducts(result.data || []);
                        this.productHasMore = Boolean(result.meta?.has_more);
                        this.productPage = (Number(result.meta?.current_page) || this.productPage) + 1;
                        this.productsLoaded = true;
                    } catch (error) {
                        console.error('loadProducts error:', error);
                        this.productsLoaded = true;
                    } finally {
                        this.productLoading = false;
                    }
                },

                async ensureProductsLoaded(productIds) {
                    while (this.productLoading) {
                        await new Promise(resolve => setTimeout(resolve, 50));
                    }

                    const missingIds = [...new Set(productIds.map(Number))]
                        .filter(id => id && !this.products.some(product => Number(product.id) === id));

                    if (missingIds.length) {
                        await this.loadProducts(false, missingIds);
                    }
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

                // === BUILD DETAIL MODAL ===
                openBuildDetail(build) {
                    this.detailBuild = build;
                    this.buildDetailOpen = true;
                },

                closeBuildDetail() {
                    this.buildDetailOpen = false;
                    this.detailBuild = null;
                },

                buildComponents(build) {
                    if (!build || !build.components) return [];
                    return Object.values(build.components).filter(Boolean);
                },

                buildComponentCount(build) {
                    return this.buildComponents(build).length;
                },

                buildCoverImage(build) {
                    const firstComponent = this.buildComponents(build).find(c => c.id);
                    const product = firstComponent ? this.products.find(p => p.id === firstComponent.id) : null;
                    return product?.image || @json(asset('assets/no-image.svg'));
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
                // toggleCompatibility() {
                //     if (this.transactionData.transactionMode === 'rakit_pc') {
                //         this.filterCompatible = true;
                //         return;
                //     }

                //     this.filterCompatible = !this.filterCompatible;
                // },

                async setTransactionMode(mode) {
                    if (mode === 'sparepart' && this.activeBuild) {
                        this.pendingTransactionMode = mode;
                        this.modeSwitchWarningOpen = true;
                        return;
                    }

                    if (mode === 'rakit_pc' && !this.activeBuild && this.cart.length > 0) {
                        this.pendingTransactionMode = mode;
                        this.modeSwitchWarningOpen = true;
                        return;
                    }

                    this.transactionData.transactionMode = mode;

                    if (mode === 'sparepart') {
                        this.transactionData.buildName = '';
                        this.closeBuildDetail();
                        return;
                    }

                    if (mode === 'rakit_pc') {
                        this.activeCat = 'Semua';
                        await this.loadSavedBuilds();
                    }
                },

                cancelModeSwitch() {
                    this.pendingTransactionMode = null;
                    this.modeSwitchWarningOpen = false;
                },

                confirmCancelCurrentOrder() {
                    this.cart = [];
                    this.activeBuild = null;
                    this.buildMarginPct = 0;
                    this.transactionData.transactionMode = this.pendingTransactionMode || 'sparepart';
                    this.transactionData.buildName = '';
                    this.additionalFees = {
                        installation: 0,
                        service_labor: 0,
                        discount: 0,
                    };
                    this.closeBuildDetail();
                    this.cancelModeSwitch();

                    if (this.transactionData.transactionMode === 'sparepart' && this.products.length === 0) {
                        this.loadProducts(true);
                    }

                    if (this.transactionData.transactionMode === 'rakit_pc') {
                        this.activeCat = 'Semua';
                        this.loadSavedBuilds();
                    }
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

                selectCustomer() {
                    if (!this.selectedCustomerId) return;

                    const cust = this.customers.find(c => c.id == this.selectedCustomerId);

                    if (!cust) return;

                    this.transactionData.customer_id = cust.id;
                    this.transactionData.customerName = cust.name;
                    this.transactionData.customerPhone = cust.phone;
                    this.transactionData.customerAddress = cust.address;
                },

                resetCustomer() {
                    this.selectedCustomerId = '';

                    this.transactionData.customer_id = null;
                    this.transactionData.customerName = '';
                    this.transactionData.customerPhone = '';
                    this.transactionData.customerAddress = '';
                },

                // === SUBMIT ORDER ===
                async submitOrder() {
                    if (!this.transactionData.sales) return alert('Sales wajib dipilih.');
                    if (!this.transactionData.customerName) return alert('Nama customer wajib diisi.');
                if ((Number(this.additionalFees.discount) || 0) > 100) {
                    return alert('Diskon persentase tidak boleh lebih dari 100%.');
                }

                    const payload = {
                        transaction_data: this.transactionData,
                        service_fee: this.totalChargeAmount,
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

                    if (this.transactionData.type !== 'Invoice') {
                        await this.exportDraftDocument(payload);
                        return;
                    }

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
                            if (result.payment_method === 'cash') {
                                alert('Transaksi Cash berhasil disimpan!');
                                if (result.document_url) {
                                    const a = document.createElement('a');
                                    a.href     = result.document_url;
                                    a.target   = '_blank';
                                    a.download = '';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                }
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                // ✅ Kirim document_url sebagai parameter ke-3
                                openPaymentModal(
                                    result.transaction_id,
                                    'Rp ' + this.formatNumber(this.finalTotal),
                                    result.document_url // ← ini yang baru, untuk download invoice setelah bayar
                                );
                            }
                        } else {
                            alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                        }

                    } catch (error) {
                        console.error('submitOrder error:', error);
                        alert('Tidak dapat terhubung ke server.');
                    }
                },

                async exportDraftDocument(payload) {
                    try {
                        const response = await fetch("{{ route('transactions.export-document') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json, application/pdf'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (!response.ok) {
                            const result = await response.json().catch(() => ({}));
                            alert('Gagal: ' + (result.message || 'Terjadi kesalahan.'));
                            return;
                        }

                        const blob = await response.blob();
                        const disposition = response.headers.get('Content-Disposition') || '';
                        const fileNameMatch = disposition.match(/filename="?([^"]+)"?/);
                        const fileName = fileNameMatch?.[1] || `${this.transactionData.type.toLowerCase()}-${Date.now()}.pdf`;
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        closeModal('modalCheckout');
                    } catch (error) {
                        console.error('exportDraftDocument error:', error);
                        alert('Tidak dapat terhubung ke server.');
                    }
                },

                // === PC BUILDER: SAVED BUILDS ===
                savedBuilds: [],
                savedBuildsLoaded: false,
                // === PC BUILDER: LOAD SAVED BUILDS ===
                async loadSavedBuilds() {
                    const res = await fetch('/pc-builder/builds/list');
                    this.savedBuilds = await res.json();
                    this.savedBuildsLoaded = true;
                },
                async applyBuild(buildData) {
                    await this.ensureProductsLoaded(Object.values(buildData.components || {})
                        .filter(Boolean)
                        .map(component => component.id));

                    this.cart = [];
                    this.activeBuild = buildData;
                    this.buildMarginPct = Number(buildData.margin_pct) || 0;

                    const skipped = [];

                    Object.values(buildData.components).forEach(comp => {
                        if (!comp) return;

                        const product = this.products.find(p => p.id === comp.id);
                        if (!product) {
                            skipped.push(comp.name ?? 'Unknown');
                            return;
                        }

                        const supplier = product.suppliers.find(s => s.pivot_stock > 0);
                        if (!supplier) {
                            skipped.push(product.name + ' (stok habis)');
                            return;
                        }

                        const supplierWithModalPrice = {
                            ...supplier,
                            pivot_price: comp.price || 0, // ← harga modal
                        };

                        this.confirmAddToCart(product, supplierWithModalPrice, false);
                    });

                    this.transactionData.transactionMode = 'rakit_pc';
                    this.transactionData.buildName = buildData.name;

                    this.closeBuildDetail();

                    if (skipped.length > 0) {
                        alert('Beberapa komponen tidak bisa dimuat:\n- ' + skipped.join('\n- '));
                    }
                },

                async deleteBuild(buildId) {
                    if (!buildId) return;
                    if (!confirm('Apakah Anda yakin ingin menghapus build ini? Tindakan ini tidak dapat dibatalkan.')) return;

                    try {
                        const response = await fetch(`/pc-builder/builds/${buildId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            this.closeBuildDetail();
                            await this.loadSavedBuilds();
                            alert('Build berhasil dihapus.');
                        } else {
                            const err = await response.json().catch(() => ({}));
                            alert('Gagal menghapus build: ' + (err.message || 'Kesalahan server'));
                        }
                    } catch (error) {
                        console.error('Delete build error:', error);
                        alert('Tidak dapat terhubung ke server.');
                    }
                },

              
            }
        }
    </script>
@endpush
