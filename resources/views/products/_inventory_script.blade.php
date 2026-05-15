@push('scripts')
    <script>
        function productInventory() {
            return {
                rows: @json($productRows),
                categories: @json($categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name])->values()),
                suppliers: @json($suppliers->map(fn($supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->nama_supplier,
                ])->values()),
                users: @json($users->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()),
                specTemplates: @json($specTemplates),
                oldInput: @json(old()),
                indexAction: @json(route('products.index')),
                storeAction: @json(route('products.store')),
                updateActionTemplate: @json(route('products.update', ['product' => '__ID__'])),
                deleteActionTemplate: @json(route('products.destroy', ['product' => '__ID__'])),
                noImageUrl: @json(asset('assets/no-image.svg')),
                conditionOptions: ['New', 'Used', 'Refurbished'],
                formMode: 'create',
                formRow: null,
                formReady: false,
                formErrors: [],
                isSaving: false,
                previewModalKey: null,

                boot() {
                    this.rows = this.rows.map((row) => this.prepareRow(row));

                    if (this.oldInput && Object.keys(this.oldInput).length > 0 && this.oldInput.name !== undefined) {
                        this.formMode = this.oldInput._form_mode === 'edit' ? 'edit' : 'create';
                        this.formRow = this.prepareRow(this.rowFromPayload(this.oldInput));
                        this.formReady = true;
                        this.openProductFormModal();
                    }
                },

                get allKnownSpecKeys() {
                    const seen = new Set();
                    const result = [];
                    Object.values(this.specTemplates).forEach((template) => {
                        (template.fields || []).forEach((field) => {
                            if (!seen.has(field.key)) {
                                seen.add(field.key);
                                result.push({ key: field.key, label: field.label });
                            }
                        });
                    });
                    return result.sort((a, b) => a.label.localeCompare(b.label));
                },

                productFormAction() {
                    if (this.formMode !== 'edit' || !this.formRow?.id) {
                        return this.storeAction;
                    }

                    return this.updateActionTemplate.replace('__ID__', this.formRow.id);
                },

                deleteAction(row) {
                    return this.deleteActionTemplate.replace('__ID__', row.id);
                },

                openCreateProductModal() {
                    this.formMode = 'create';
                    this.formErrors = [];
                    this.isSaving = false;
                    this.formReady = false;
                    this.formRow = null;
                    this.resetProductFileInput();
                    this.$nextTick(() => {
                        this.formRow = this.newRow();
                        this.formReady = true;
                        this.openProductFormModal();
                    });
                },

                openEditProductModal(row) {
                    if (!row) return;
                    const preparedRow = this.prepareRow(JSON.parse(JSON.stringify(row)));

                    this.formErrors = [];
                    this.isSaving = false;
                    this.formMode = 'edit';
                    this.formReady = false;
                    this.formRow = null;
                    this.resetProductFileInput();
                    this.$nextTick(() => {
                        this.formRow = preparedRow;
                        this.formReady = true;
                        this.openProductFormModal();
                    });
                },

                openProductFormModal() {
                    this.$nextTick(() => {
                        openModal('modal-product-form');
                        this.syncProductFormControls(4);
                    });
                },

                closeProductFormModal() {
                    this.formRow = null;
                    this.formReady = false;
                    this.formErrors = [];
                    this.isSaving = false;
                    this.resetProductFileInput();
                    closeModal('modal-product-form');
                },

                async submitProductForm(event) {
                    const form = event.target;
                    this.formErrors = [];
                    this.isSaving = true;

                    try {
                        this.syncProductFormControls();

                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const contentType = response.headers.get('content-type') || '';
                        const payload = contentType.includes('application/json')
                            ? await response.json()
                            : {};

                        if (response.status === 422) {
                            this.formErrors = Object.values(payload.errors || {}).flat();
                            if (this.formErrors.length === 0) {
                                this.formErrors = ['Data belum valid. Periksa kembali input produk.'];
                            }
                            this.scrollModalToTop();
                            return;
                        }

                        if (!response.ok) {
                            this.formErrors = [payload.message || 'Produk gagal disimpan. Coba lagi.'];
                            this.scrollModalToTop();
                            return;
                        }

                        window.location.href = payload.redirect || this.indexAction;
                    } catch (error) {
                        this.formErrors = ['Koneksi gagal saat menyimpan produk. Coba lagi.'];
                        this.scrollModalToTop();
                    } finally {
                        this.isSaving = false;
                    }
                },

                scrollModalToTop() {
                    this.$nextTick(() => {
                        document.querySelector('#modal-product-form .overflow-y-auto')?.scrollTo({
                            top: 0,
                            behavior: 'smooth',
                        });
                    });
                },

                resetProductFileInput() {
                    this.$nextTick(() => {
                        const input = document.querySelector('#product-form input[name="image"]');
                        if (input) input.value = '';
                    });
                },

                syncProductFormControls(retries = 0) {
                    if (!this.formRow) return;

                    this.$nextTick(() => {
                        const form = document.getElementById('product-form');
                        if (!form || !this.formRow) return;

                        const setValue = (name, value) => {
                            const field = form.elements.namedItem(name);
                            if (!field || field.type === 'file') return;
                            field.value = value ?? '';
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        };

                        setValue('name', this.formRow.name);
                        setValue('serial_number', this.formRow.serial_number);
                        setValue('brand', this.formRow.brand);
                        setValue('category_id', this.formRow.category_id);
                        setValue('letak_barang', this.formRow.letak_barang);
                        setValue('description', this.formRow.description);

                        (this.formRow.suppliers || []).forEach((supplier, index) => {
                            setValue(`suppliers[${index}][supplier_id]`, supplier.supplier_id);
                            setValue(`suppliers[${index}][pemodal_user_id]`, supplier.pemodal_user_id);
                            setValue(`suppliers[${index}][condition]`, supplier.condition);
                            setValue(`suppliers[${index}][stock]`, supplier.stock);
                            setValue(`suppliers[${index}][harga_beli]`, supplier.harga_beli);
                            setValue(`suppliers[${index}][harga_jual]`, supplier.harga_jual);
                            setValue(`suppliers[${index}][warranty_detail]`, supplier.warranty_detail);
                            setValue(`suppliers[${index}][new_supplier_name]`, supplier.new_supplier_name);
                            setValue(`suppliers[${index}][new_supplier_address]`, supplier.new_supplier_address);
                        });

                        Object.entries(this.formRow.specs || {}).forEach(([key, spec]) => {
                            setValue(`specs[${key}][value]`, spec?.value || '');
                        });

                        if (retries > 0) {
                            window.setTimeout(() => this.syncProductFormControls(retries - 1), 30);
                        }
                    });
                },

                rowFromPayload(payload = {}) {
                    return {
                        id: payload._product_id || null,
                        name: payload.name || '',
                        serial_number: payload.serial_number || '',
                        brand: payload.brand || '',
                        category_id: payload.category_id || '',
                        category_name: '',
                        letak_barang: payload.letak_barang || '',
                        description: payload.description || '',
                        image_url: '',
                        specs: payload.specs || {},
                        additional_specs: payload.extra_specs || [],
                        suppliers: payload.suppliers || [this.newSupplier()],
                    };
                },

                prepareRow(row = {}) {
                    const preparedSuppliers = Array.isArray(row.suppliers) && row.suppliers.length
                        ? row.suppliers.map((supplier) => this.prepareSupplier(supplier))
                        : [this.newSupplier()];

                    const prepared = {
                        client_key: row.client_key || this.uid(),
                        id: row.id || null,
                        name: row.name || '',
                        serial_number: row.serial_number || '',
                        brand: row.brand || '',
                        category_id: row.category_id ? String(row.category_id) : '',
                        category_name: row.category_name || '',
                        letak_barang: row.letak_barang || '',
                        description: row.description || '',
                        image_url: row.image_url || '',
                        _imageName: '',
                        specs: this.normalizeSpecs(row.specs || {}, row.category_id),
                        additional_specs: Array.isArray(row.additional_specs) ? row.additional_specs.map((spec) => {
                            const key = spec.key || '';
                            const isKnown = this.allKnownSpecKeys.some((knownKey) => knownKey.key === key);
                            return {
                                key,
                                value: spec.value || '',
                                _selectedKey: key === '' ? '' : (isKnown ? key : '__custom__'),
                            };
                        }) : [],
                        suppliers: preparedSuppliers,
                    };

                    if (prepared.category_id) {
                        prepared.category_name = this.categoryName(prepared);
                        this.ensureSpecs(prepared);
                    }

                    return prepared;
                },

                prepareSupplier(supplier = {}) {
                    const hasManualSupplier = !!`${supplier.new_supplier_name || ''}${supplier.new_supplier_address || ''}`.trim();

                    return {
                        mode: supplier.mode || (hasManualSupplier ? 'new' : 'existing'),
                        supplier_id: supplier.supplier_id ? String(supplier.supplier_id) : '',
                        pemodal_user_id: supplier.pemodal_user_id ? String(supplier.pemodal_user_id) : '',
                        new_supplier_name: supplier.new_supplier_name || '',
                        new_supplier_address: supplier.new_supplier_address || '',
                        condition: supplier.condition || this.defaultCondition(),
                        stock: supplier.stock ?? '0',
                        harga_beli: supplier.harga_beli ?? '',
                        harga_jual: supplier.harga_jual ?? '',
                        warranty_detail: supplier.warranty_detail ?? '',
                    };
                },

                defaultCondition() {
                    return this.conditionOptions[0] || 'New';
                },

                uid() {
                    return `row_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
                },

                newSupplier() {
                    return this.prepareSupplier({
                        mode: 'existing',
                        supplier_id: '',
                        pemodal_user_id: '',
                        condition: this.defaultCondition(),
                        stock: '0',
                    });
                },

                newRow() {
                    return this.prepareRow({
                        client_key: this.uid(),
                        suppliers: [this.newSupplier()],
                    });
                },

                currentTemplate(row) {
                    return this.specTemplates[String(row?.category_id || '')] || { fields: [], options: {} };
                },

                templateFields(row) {
                    return this.currentTemplate(row).fields || [];
                },

                specOptions(row, key) {
                    return this.currentTemplate(row).options?.[key] || [];
                },

                specSelectOptions(row, key) {
                    const options = [...this.specOptions(row, key)];
                    const currentValue = `${row?.specs?.[key]?.value || ''}`.trim();

                    if (!currentValue) return options;

                    const matched = this.findMatchingSpecOption(options, currentValue);
                    return matched ? options : [currentValue, ...options];
                },

                resolveSpecMode(categoryId, key, value, fallbackMode = null) {
                    if (fallbackMode === 'new' || fallbackMode === 'existing') {
                        return fallbackMode;
                    }

                    if (!`${value || ''}`.trim()) {
                        return 'existing';
                    }

                    const options = (this.specTemplates[String(categoryId || '')]?.options?.[key] || []);
                    return this.findMatchingSpecOption(options, value) ? 'existing' : 'new';
                },

                normalizeSpecs(specs = {}, categoryId = '') {
                    const template = this.specTemplates[String(categoryId || '')] || { fields: [] };
                    const next = {};

                    (template.fields || []).forEach((field) => {
                        const current = specs[field.key] || {};
                        const resolvedMode = this.resolveSpecMode(categoryId, field.key, current.value || '', current.mode || null);
                        const matched = resolvedMode === 'existing'
                            ? this.findMatchingSpecOption(this.specTemplates[String(categoryId || '')]?.options?.[field.key] || [], current.value || '')
                            : null;

                        next[field.key] = {
                            key: field.key,
                            value: matched || current.value || '',
                            mode: resolvedMode,
                        };
                    });

                    return next;
                },

                ensureSpecs(row) {
                    if (!row) return;
                    const previous = row.specs || {};
                    const next = {};

                    this.templateFields(row).forEach((field) => {
                        const current = previous[field.key] || {};
                        const resolvedMode = this.resolveSpecMode(row.category_id, field.key, current.value || '', current.mode || null);
                        const matched = resolvedMode === 'existing'
                            ? this.findMatchingSpecOption(this.specOptions(row, field.key), current.value || '')
                            : null;

                        next[field.key] = {
                            key: field.key,
                            value: matched || current.value || '',
                            mode: resolvedMode,
                        };
                    });

                    row.specs = next;
                },

                comparableSpecValue(value) {
                    return `${value || ''}`.trim().toLowerCase().replace(/[^a-z0-9]+/g, '');
                },

                findMatchingSpecOption(options, value) {
                    const comparable = this.comparableSpecValue(value);
                    if (!comparable) return null;
                    return (options || []).find((option) => this.comparableSpecValue(option) === comparable) || null;
                },

                updateSpec(row, key, value) {
                    if (!row) return;
                    if (!row.specs[key]) row.specs[key] = { key, value: '', mode: 'existing' };
                    row.specs[key].value = value;
                },

                setSpecMode(row, key, mode) {
                    if (!row) return;
                    if (!row.specs[key]) row.specs[key] = { key, value: '', mode: 'existing' };
                    row.specs[key].mode = mode;

                    if (mode === 'existing') {
                        const matched = this.findMatchingSpecOption(this.specOptions(row, key), row.specs[key].value);
                        if (matched) row.specs[key].value = matched;
                    }
                },

                normalizeSpecEntry(row, key) {
                    if (!row?.specs?.[key]) return;
                    const matched = this.findMatchingSpecOption(this.specOptions(row, key), row.specs[key].value);
                    if (matched) {
                        row.specs[key].value = matched;
                        row.specs[key].mode = 'existing';
                    }
                },

                specFieldLabel(row, key) {
                    const field = this.templateFields(row).find((item) => item.key === key);
                    return field?.label || key;
                },

                filledMainSpecs(row) {
                    return Object.entries(row?.specs || {})
                        .map(([key, spec]) => ({
                            key,
                            label: this.specFieldLabel(row, key),
                            value: `${spec?.value || ''}`.trim(),
                        }))
                        .filter((spec) => spec.value !== '');
                },

                filledExtraSpecs(row) {
                    return (row?.additional_specs || [])
                        .map((spec) => ({
                            key: spec.key || '',
                            label: spec.key || 'Spesifikasi tambahan',
                            value: `${spec.value || ''}`.trim(),
                        }))
                        .filter((spec) => spec.key !== '' && spec.value !== '');
                },

                filledSpecs(row) {
                    return [
                        ...this.filledMainSpecs(row),
                        ...this.filledExtraSpecs(row),
                    ];
                },

                specSummary(row) {
                    const specs = this.filledSpecs(row);

                    if (specs.length === 0) {
                        return '-';
                    }

                    const firstValue = specs[0].value;
                    const remaining = specs.length - 1;

                    return remaining > 0 ? `${firstValue} +${remaining}` : firstValue;
                },

                categoryName(row) {
                    const found = this.categories.find((category) => String(category.id) === String(row?.category_id));
                    return found ? found.name : (row?.category_name || 'No Category');
                },

                changeCategory(row) {
                    if (!row) return;
                    row.category_name = this.categoryName(row);
                    this.ensureSpecs(row);
                },

                addSupplier(row) {
                    if (!row) return;
                    row.suppliers.push(this.newSupplier());
                },

                removeSupplier(row, index) {
                    if (!row) return;
                    if (row.suppliers.length === 1) {
                        row.suppliers[0] = this.newSupplier();
                    } else {
                        row.suppliers.splice(index, 1);
                    }
                },

                setSupplierMode(row, supplier, mode) {
                    if (!supplier) return;
                    supplier.mode = mode;

                    if (mode === 'new') {
                        supplier.supplier_id = '';
                    } else {
                        supplier.new_supplier_name = '';
                        supplier.new_supplier_address = '';
                    }
                },

                onSupplierSelectChange(row, supplier) {
                    if (!supplier) return;
                    if (supplier.supplier_id === '__new__') {
                        supplier.supplier_id = '';
                        this.setSupplierMode(row, supplier, 'new');
                        return;
                    }

                    supplier.mode = 'existing';
                },

                supplierName(id) {
                    const found = this.suppliers.find((supplier) => String(supplier.id) === String(id));
                    return found ? found.name : '';
                },

                userName(id) {
                    const found = this.users.find((user) => String(user.id) === String(id));
                    return found ? found.name : '';
                },

                supplierModeLabel(supplier) {
                    return supplier?.mode === 'new' ? 'Supplier Baru' : 'Supplier Lama';
                },

                supplierDisplayName(supplier, index = 0) {
                    if ((supplier?.mode || 'existing') === 'new') {
                        return supplier?.new_supplier_name || `Supplier baru #${index + 1}`;
                    }

                    return this.supplierName(supplier?.supplier_id) || `Supplier #${index + 1}`;
                },

                supplierPemodalName(supplier) {
                    return this.userName(supplier?.pemodal_user_id) || '';
                },

                isSupplierReady(supplier) {
                    if (!supplier) return false;
                    if ((supplier.mode || 'existing') === 'new') {
                        return !!`${supplier.new_supplier_name || ''}`.trim()
                            && !!`${supplier.new_supplier_address || ''}`.trim()
                            && !!`${supplier.pemodal_user_id || ''}`.trim();
                    }

                    return !!supplier.supplier_id && !!`${supplier.pemodal_user_id || ''}`.trim();
                },

                supplierEntries(row) {
                    return (row?.suppliers || []).map((supplier, index) => ({
                        ...supplier,
                        index,
                        name: this.supplierDisplayName(supplier, index),
                        pemodalName: this.supplierPemodalName(supplier),
                        modeLabel: this.supplierModeLabel(supplier),
                        condition: supplier.condition || this.defaultCondition(),
                    }));
                },

                activeSupplierEntries(row) {
                    return this.supplierEntries(row).filter((supplier) => this.isSupplierReady(supplier));
                },

                supplierPreview(row, limit = 1) {
                    return this.activeSupplierEntries(row).slice(0, limit);
                },

                remainingSupplierCount(row, limit = 1) {
                    return Math.max(this.activeSupplierEntries(row).length - limit, 0);
                },

                investorSummary(row) {
                    const investors = [...new Set(
                        this.activeSupplierEntries(row)
                            .map((supplier) => supplier.pemodalName)
                            .filter((name) => `${name || ''}`.trim() !== '')
                    )];

                    if (investors.length === 0) return '-';
                    if (investors.length === 1) return investors[0];
                    return `${investors[0]} +${investors.length - 1}`;
                },

                supplierCardTitle(supplierRow, supplierIndex) {
                    return this.supplierDisplayName(supplierRow, supplierIndex);
                },

                addExtraSpec(row) {
                    if (!row) return;
                    row.additional_specs.push({ key: '', value: '', _selectedKey: '' });
                },

                onExtraSpecKeySelect(row, extraSpec, selectedValue) {
                    extraSpec.key = selectedValue !== '__custom__' ? selectedValue : '';
                },

                removeExtraSpec(row, index) {
                    if (!row) return;
                    row.additional_specs.splice(index, 1);
                },

                rowStock(row) {
                    return (row?.suppliers || []).reduce((sum, supplier) => sum + (parseInt(supplier.stock || 0, 10) || 0), 0);
                },

                statusMeta(row) {
                    const stock = this.rowStock(row);
                    if (stock > 10) return { label: 'Aman', className: 'bg-emerald-100 text-emerald-600' };
                    if (stock > 0) return { label: 'Menipis', className: 'bg-amber-100 text-amber-600' };
                    return { label: 'Kosong', className: 'bg-rose-100 text-rose-600' };
                },

                conditionMeta(condition) {
                    if (condition === 'New') return 'bg-sky-50 text-sky-600';
                    if (condition === 'Used') return 'bg-amber-50 text-amber-600';
                    if (condition === 'Refurbished') return 'bg-violet-50 text-violet-600';
                    return 'bg-slate-100 text-slate-600';
                },

                supplierPreviewBadgeClass(condition) {
                    if (condition === 'Used') return 'bg-amber-50 text-amber-700 ring-amber-200';
                    if (condition === 'Refurbished') return 'bg-violet-50 text-violet-700 ring-violet-200';
                    return 'bg-sky-50 text-sky-700 ring-sky-200';
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
                },

                onImageChange(event) {
                    if (!this.formRow) return;
                    const file = event.target.files?.[0];
                    this.formRow._imageName = file ? file.name : '';
                },

                productImageUrl(row) {
                    return row?.image_url || this.noImageUrl;
                },

                rowByKey(key) {
                    return this.rows.find((row) => row.client_key === key) || null;
                },

                openPreviewModal(row) {
                    if (!row) return;
                    this.previewModalKey = row.client_key;
                    openModal('modal-product-preview');
                },

                activePreviewRow() {
                    return this.rowByKey(this.previewModalKey);
                },

                activePreviewImage() {
                    return this.productImageUrl(this.activePreviewRow());
                },
            };
        }
    </script>
@endpush
