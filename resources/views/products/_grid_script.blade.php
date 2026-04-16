@push('scripts')
    <script>
        function productGrid() {
            return {
                rows: @json($gridRows),
                categories: @json($categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name])->values()),
                suppliers: @json($suppliers->map(fn($supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->nama_supplier,
                ])->values()),
                users: @json($users->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()),
                specTemplates: @json($specTemplates),
                validationErrorsByRow: @json($gridErrorsByRow ?? []),
                conditionOptions: ['New', 'Used', 'Refurbished'],
                detailModalKey: null,
                supplierModalKey: null,
                previewModalKey: null,
                pendingNavigationUrl: null,
                isSubmittingForm: false,

                boot() {
                    this.rows = this.rows.map((row) => this.prepareRow(row));

                    if (this.rows.length === 0) {
                        this.rows = [this.newRow()];
                    }

                    this.registerUnsavedChangeGuard();
                },

                // untuk key
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

                get dirtyCount() {
                    return this.rows.filter((row) => row.is_dirty || row.is_new || row.marked_for_delete).length;
                },

                get hasChanges() {
                    return this.dirtyCount > 0;
                },

                prepareRow(row = {}) {
                    const preparedSuppliers = Array.isArray(row.suppliers) && row.suppliers.length
                        ? row.suppliers.map((supplier) => this.prepareSupplier(supplier))
                        : [this.newSupplier()];

                    const prepared = {
                        client_key: row.client_key || this.uid(),
                        id: row.id || null,
                        name: row.name || '',
                        brand: row.brand || '',
                        category_id: row.category_id ? String(row.category_id) : '',
                        category_name: row.category_name || '',
                        letak_barang: row.letak_barang || '',
                        warranty: row.warranty ?? '',
                        description: row.description ?? '',
                        image_url: row.image_url ?? '',
                        _imageName: '',
                        specs: this.normalizeSpecs(row.specs || {}, row.category_id),
                        additional_specs: Array.isArray(row.additional_specs) ? row.additional_specs.map((spec) => {
                            const key = spec.key || '';
                            const isKnown = this.allKnownSpecKeys.some((k) => k.key === key);
                            return {
                                key: key,
                                value: spec.value || '',
                                _selectedKey: key === '' ? '' : (isKnown ? key : '__custom__'),
                            };
                        }) : [],
                        suppliers: preparedSuppliers,
                        is_new: !!row.is_new,
                        is_dirty: !!row.is_dirty,
                        marked_for_delete: !!row.marked_for_delete,
                        editing_cell: row.editing_cell || (row.is_new ? 'name' : null),
                        server_errors: Array.isArray(row.server_errors)
                            ? row.server_errors
                            : (this.validationErrorsByRow[row.client_key] || []),
                    };

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
                    };
                },

                defaultCondition() {
                    return this.conditionOptions[0] || 'New';
                },

                uid() {
                    return `new_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
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
                        is_new: true,
                        is_dirty: true,
                        editing_cell: 'name',
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

                    if (!currentValue) {
                        return options;
                    }

                    const matched = this.findMatchingSpecOption(options, currentValue);

                    if (matched) {
                        return options;
                    }

                    return [currentValue, ...options];
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
                    const previous = row.specs || {};
                    const next = {};

                    this.templateFields(row).forEach((field) => {
                        const current = previous[field.key] || {};
                        const resolvedMode = this.resolveSpecMode(row?.category_id, field.key, current.value || '', current.mode || null);
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
                    this.markDirty(row);
                },

                setSpecMode(row, key, mode) {
                    if (!row) return;
                    if (!row.specs[key]) row.specs[key] = { key, value: '', mode: 'existing' };
                    row.specs[key].mode = mode;

                    if (mode === 'existing') {
                        const matched = this.findMatchingSpecOption(this.specOptions(row, key), row.specs[key].value);
                        if (matched) {
                            row.specs[key].value = matched;
                        }
                    }

                    this.markDirty(row, true);
                },

                normalizeSpecEntry(row, key) {
                    if (!row?.specs?.[key]) return;

                    const matched = this.findMatchingSpecOption(this.specOptions(row, key), row.specs[key].value);

                    if (matched) {
                        row.specs[key].value = matched;
                        row.specs[key].mode = 'existing';
                    }

                    this.markDirty(row, true);
                },

                specSummary(row) {
                    const filledMain = Object.values(row?.specs || {}).filter((spec) => `${spec?.value || ''}`.trim() !== '').length;
                    const extra = (row?.additional_specs || []).filter((spec) => `${spec?.key || ''}${spec?.value || ''}`.trim() !== '').length;
                    return `${filledMain} field utama + ${extra} tambahan`;
                },

                categoryName(row) {
                    const found = this.categories.find((category) => String(category.id) === String(row.category_id));
                    return found ? found.name : (row.category_name || 'No Category');
                },

                changeCategory(row) {
                    row.category_name = this.categoryName(row);
                    this.ensureSpecs(row);
                    this.markDirty(row, true);
                },

                markDirty(row, keepOpen = false) {
                    if (!row) return;
                    row.server_errors = [];
                    row.is_dirty = true;
                },

                rowErrors(row) {
                    return Array.isArray(row?.server_errors) ? row.server_errors : [];
                },

                rowErrorCount(row) {
                    return this.rowErrors(row).length;
                },

                rowHasErrors(row) {
                    return this.rowErrorCount(row) > 0;
                },

                clearEditingCells(exceptClientKey = null) {
                    this.rows.forEach((item) => {
                        if (!exceptClientKey || item.client_key !== exceptClientKey) {
                            item.editing_cell = null;
                        }
                    });
                },

                isCellEditing(row, cell) {
                    return !!row && !row.marked_for_delete && row.editing_cell === cell;
                },

                activateRow(row, cell = null) {
                    if (!row || row.marked_for_delete) return;
                    this.ensureSpecs(row);

                    if (!cell) {
                        return;
                    }

                    this.clearEditingCells(row.client_key);
                    row.editing_cell = cell;
                    this.$nextTick(() => this.focusCell(row.client_key, cell));
                },

                focusCell(clientKey, cell) {
                    const input = document.querySelector(`[data-row-key="${clientKey}"] [data-cell-input="${cell}"]`);
                    if (input) {
                        input.focus();
                        if (typeof input.select === 'function') {
                            input.select();
                        }
                    }
                },

                stopCellEdit(row, cell = null) {
                    if (!row) return;
                    if (cell && row.editing_cell !== cell) return;
                    row.editing_cell = null;
                },

                saveCell(row, cell, nextCell = null) {
                    if (!row) return;

                    if (cell === 'category') {
                        row.category_name = this.categoryName(row);
                        this.ensureSpecs(row);
                    }

                    if (nextCell) {
                        this.activateRow(row, nextCell);
                        return;
                    }

                    this.stopCellEdit(row, cell);
                },

                addRowTop() {
                    const row = this.newRow();
                    this.rows.unshift(row);
                    this.$nextTick(() => this.focusCell(row.client_key, 'name'));
                },

                addRowAfter(clientKey) {
                    const index = this.rows.findIndex((row) => row.client_key === clientKey);
                    const row = this.newRow();
                    if (index === -1) this.rows.push(row);
                    else this.rows.splice(index + 1, 0, row);
                    this.$nextTick(() => this.focusCell(row.client_key, 'name'));
                },

                toggleDelete(row) {
                    if (!row) return;

                    if (!row.id) {
                        this.rows = this.rows.filter((item) => item.client_key !== row.client_key);
                        return;
                    }

                    row.marked_for_delete = !row.marked_for_delete;
                    row.is_dirty = true;

                    if (row.marked_for_delete) {
                        row.editing_cell = null;
                    }
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

                supplierDisplayAddress(supplier) {
                    if ((supplier?.mode || 'existing') === 'new') {
                        return supplier?.new_supplier_address || '';
                    }

                    return '';
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

                supplierReferenceKey(supplier) {
                    if (!this.isSupplierReady(supplier)) return null;

                    if ((supplier.mode || 'existing') === 'new') {
                        const name = `${supplier.new_supplier_name || ''}`.trim().toLowerCase().replace(/\s+/g, ' ');
                        const address = `${supplier.new_supplier_address || ''}`.trim().toLowerCase().replace(/\s+/g, ' ');
                        return `new:${name}::${address}`;
                    }

                    return `existing:${supplier.supplier_id}`;
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

                    this.markDirty(row, true);
                },

                onSupplierSelectChange(row, supplier) {
                    if (!supplier) return;

                    if (supplier.supplier_id === '__new__') {
                        supplier.supplier_id = '';
                        this.setSupplierMode(row, supplier, 'new');
                        return;
                    }

                    supplier.mode = 'existing';
                    this.markDirty(row, true);
                },

                supplierEntries(row) {
                    return (row?.suppliers || []).map((supplier, index) => ({
                        ...supplier,
                        index,
                        name: this.supplierDisplayName(supplier, index),
                        address: this.supplierDisplayAddress(supplier),
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

                supplierSummary(row) {
                    const entries = this.activeSupplierEntries(row);

                    if (entries.length === 0) return 'Belum ada supplier';
                    if (entries.length === 1) return `${entries[0].name} | ${entries[0].condition}`;
                    return `${entries.length} supplier aktif`;
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

                hasDuplicateSupplierCondition(row) {
                    const seen = new Set();

                    return this.activeSupplierEntries(row)
                        .some((supplier) => {
                            const reference = this.supplierReferenceKey(supplier);

                            if (!reference) {
                                return false;
                            }

                            const key = `${reference}::${supplier.condition}::${supplier.pemodal_user_id || ''}`;

                            if (seen.has(key)) {
                                return true;
                            }

                            seen.add(key);
                            return false;
                        });
                },

                addSupplier(row) {
                    if (!row) return;
                    row.suppliers.push(this.newSupplier());
                    this.markDirty(row, true);
                },

                removeSupplier(row, index) {
                    if (!row) return;

                    if (row.suppliers.length === 1) {
                        row.suppliers[0] = this.newSupplier();
                    } else {
                        row.suppliers.splice(index, 1);
                    }

                    this.markDirty(row, true);
                },

                addExtraSpec(row) {
                    if (!row) return;
                    row.additional_specs.push({ key: '', value: '', _selectedKey: '' });
                    this.markDirty(row, true);
                },

                onExtraSpecKeySelect(row, extraSpec, selectedValue) {
                    if (selectedValue !== '__custom__') {
                        extraSpec.key = selectedValue;
                    } else {
                        extraSpec.key = '';
                    }
                    this.markDirty(row);
                },
                
                removeExtraSpec(row, index) {
                    if (!row) return;
                    row.additional_specs.splice(index, 1);
                    this.markDirty(row, true);
                },

                rowStock(row) {
                    return row.suppliers.reduce((sum, supplier) => sum + (parseInt(supplier.stock || 0, 10) || 0), 0);
                },

                minCost(row) {
                    const values = row.suppliers.map((supplier) => Number(supplier.harga_beli)).filter((value) => !Number.isNaN(value) && value > 0);
                    return values.length ? Math.min(...values) : 0;
                },

                minSell(row) {
                    const values = row.suppliers.map((supplier) => Number(supplier.harga_jual)).filter((value) => !Number.isNaN(value) && value > 0);
                    return values.length ? Math.min(...values) : 0;
                },

                conditionLabel(row) {
                    const conditions = [...new Set(this.activeSupplierEntries(row).map((supplier) => supplier.condition).filter(Boolean))];
                    return conditions.length <= 1 ? (conditions[0] || '-') : 'Mixed';
                },

                conditionBadges(row) {
                    return [...new Set(this.activeSupplierEntries(row).map((supplier) => supplier.condition).filter(Boolean))];
                },

                conditionMeta(condition) {
                    if (condition === 'New') {
                        return 'bg-sky-50 text-sky-600';
                    }

                    if (condition === 'Used') {
                        return 'bg-amber-50 text-amber-600';
                    }

                    if (condition === 'Refurbished') {
                        return 'bg-violet-50 text-violet-600';
                    }

                    return 'bg-slate-100 text-slate-600';
                },

                supplierPreviewBadgeClass(condition) {
                    if (condition === 'Used') {
                        return 'bg-amber-50 text-amber-700 ring-amber-200';
                    }

                    if (condition === 'Refurbished') {
                        return 'bg-violet-50 text-violet-700 ring-violet-200';
                    }

                    return 'bg-sky-50 text-sky-700 ring-sky-200';
                },

                supplierTone(index = 0) {
                    const tones = [
                        {
                            dot: 'bg-sky-500',
                            badge: 'bg-sky-50 text-sky-700 ring-sky-200',
                            soft: 'bg-sky-50/80 ring-sky-100',
                        },
                        {
                            dot: 'bg-emerald-500',
                            badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            soft: 'bg-emerald-50/80 ring-emerald-100',
                        },
                        {
                            dot: 'bg-amber-500',
                            badge: 'bg-amber-50 text-amber-700 ring-amber-200',
                            soft: 'bg-amber-50/80 ring-amber-100',
                        },
                        {
                            dot: 'bg-rose-500',
                            badge: 'bg-rose-50 text-rose-700 ring-rose-200',
                            soft: 'bg-rose-50/80 ring-rose-100',
                        },
                        {
                            dot: 'bg-violet-500',
                            badge: 'bg-violet-50 text-violet-700 ring-violet-200',
                            soft: 'bg-violet-50/80 ring-violet-100',
                        },
                        {
                            dot: 'bg-cyan-500',
                            badge: 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                            soft: 'bg-cyan-50/80 ring-cyan-100',
                        },
                    ];

                    return tones[index % tones.length];
                },

                supplierMetricBadges(row, supplier, index) {
                    const tone = this.supplierTone(index);

                    return [
                        {
                            key: 'stock',
                            label: `Stok ${supplier.stock || 0}`,
                            className: `${tone.badge} ring-1`,
                        },
                        {
                            key: 'cost',
                            label: `Modal Rp ${this.formatCurrency(supplier.harga_beli)}`,
                            className: 'bg-white text-slate-600 ring-1 ring-slate-200',
                        },
                        {
                            key: 'sell',
                            label: `Jual Rp ${this.formatCurrency(supplier.harga_jual)}`,
                            className: 'bg-white text-slate-900 ring-1 ring-slate-200',
                        },
                    ];
                },

                statusMeta(row) {
                    if (this.rowHasErrors(row)) {
                        return { label: 'Perlu Cek', className: 'bg-rose-100 text-rose-600' };
                    }

                    const stock = this.rowStock(row);
                    if (stock > 10) return { label: 'Aman', className: 'bg-emerald-100 text-emerald-600' };
                    if (stock > 0) return { label: 'Menipis', className: 'bg-amber-100 text-amber-600' };
                    return { label: 'Kosong', className: 'bg-rose-100 text-rose-600' };
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
                },

                onImageChange(event, row) {
                    if (!row) return;
                    const file = event.target.files?.[0];
                    row._imageName = file ? file.name : '';
                    this.markDirty(row, true);
                },

                pickImage(row) {
                    if (!row) return;
                    const input = document.querySelector(`[data-image-input="${row.client_key}"]`);
                    if (input) {
                        input.click();
                    }
                },

                rowByKey(key) {
                    return this.rows.find((row) => row.client_key === key) || null;
                },

                openDetailModal(row) {
                    if (!row) return;
                    this.activateRow(row);
                    this.detailModalKey = row.client_key;
                    openModal('modal-product-detail');
                },

                activeDetailRow() {
                    return this.rowByKey(this.detailModalKey);
                },

                openSupplierModal(row) {
                    if (!row) return;
                    this.activateRow(row);
                    this.supplierModalKey = row.client_key;
                    openModal('modal-product-suppliers');
                },

                activeSupplierRow() {
                    return this.rowByKey(this.supplierModalKey);
                },

                openPreviewModal(row) {
                    if (!row) return;
                    this.previewModalKey = row.client_key;
                    openModal('modal-product-preview');
                },

                activePreviewRow() {
                    return this.rowByKey(this.previewModalKey);
                },

                registerUnsavedChangeGuard() {
                    if (this._unsavedGuardRegistered) return;
                    this._unsavedGuardRegistered = true;

                    document.addEventListener('click', (event) => {
                        const link = event.target.closest('a[href]');
                        if (!link) return;
                        if (!this.hasChanges) return;
                        if (link.target === '_blank' || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                        const href = link.getAttribute('href') || '';
                        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

                        const destination = new URL(href, window.location.href);
                        const current = new URL(window.location.href);

                        if (destination.href === current.href) return;

                        event.preventDefault();
                        this.pendingNavigationUrl = destination.href;
                        openModal('modal-unsaved-navigation');
                    }, true);

                    window.addEventListener('beforeunload', (event) => {
                        if (this.isSubmittingForm) return;
                        if (!this.hasChanges) return;
                        event.preventDefault();
                        event.returnValue = '';
                    });
                },

                closeUnsavedNavigationModal() {
                    this.pendingNavigationUrl = null;
                    closeModal('modal-unsaved-navigation');
                },

                ignoreUnsavedAndNavigate() {
                    const destination = this.pendingNavigationUrl;
                    this.pendingNavigationUrl = null;
                    closeModal('modal-unsaved-navigation');
                    if (destination) {
                        window.location.href = destination;
                    }
                },

                saveAndStay() {
                    this.pendingNavigationUrl = null;
                    closeModal('modal-unsaved-navigation');
                    this.submitForm();
                },

                hiddenFields(row) {
                    const fields = [
                        { name: `products[${row.client_key}][id]`, value: row.id ?? '' },
                        { name: `products[${row.client_key}][_is_new]`, value: row.is_new ? '1' : '0' },
                        { name: `products[${row.client_key}][_dirty]`, value: row.is_dirty ? '1' : '0' },
                        { name: `products[${row.client_key}][_delete]`, value: row.marked_for_delete ? '1' : '0' },
                        { name: `products[${row.client_key}][name]`, value: row.name || '' },
                        { name: `products[${row.client_key}][brand]`, value: row.brand || '' },
                        { name: `products[${row.client_key}][category_id]`, value: row.category_id || '' },
                        { name: `products[${row.client_key}][letak_barang]`, value: row.letak_barang || '' },
                        { name: `products[${row.client_key}][warranty]`, value: row.warranty || '' },
                        { name: `products[${row.client_key}][description]`, value: row.description || '' },
                    ];

                    Object.entries(row.specs || {}).forEach(([key, spec]) => {
                        fields.push({ name: `products[${row.client_key}][specs][${key}][key]`, value: spec.key || key });
                        fields.push({ name: `products[${row.client_key}][specs][${key}][value]`, value: spec.value || '' });
                        fields.push({ name: `products[${row.client_key}][specs][${key}][mode]`, value: spec.mode || 'existing' });
                    });

                    row.additional_specs.forEach((spec, index) => {
                        fields.push({ name: `products[${row.client_key}][additional_specs][${index}][key]`, value: spec.key || '' });
                        fields.push({ name: `products[${row.client_key}][additional_specs][${index}][value]`, value: spec.value || '' });
                    });

                    row.suppliers.forEach((supplier, index) => {
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][mode]`, value: supplier.mode || 'existing' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][supplier_id]`, value: supplier.supplier_id || '' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][pemodal_user_id]`, value: supplier.pemodal_user_id || '' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][new_supplier_name]`, value: supplier.new_supplier_name || '' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][new_supplier_address]`, value: supplier.new_supplier_address || '' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][condition]`, value: supplier.condition || this.defaultCondition() });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][stock]`, value: supplier.stock || 0 });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][harga_beli]`, value: supplier.harga_beli || '' });
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][harga_jual]`, value: supplier.harga_jual || '' });
                    });

                    return fields;
                },

                submitForm() {
                    if (!this.hasChanges) return;
                    this.isSubmittingForm = true;
                    document.getElementById('product-grid-form').submit();
                },
            };
        }
    </script>
@endpush
