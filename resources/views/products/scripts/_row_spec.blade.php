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
                        description: row.description ?? '',
                        image_url: row.image_url ?? '',
                        _imageName: '',
                        specs: this.normalizeSpecs(row.specs || {}, row.category_id),
                        additional_specs: Array.isArray(row.additional_specs) ? row.additional_specs.map((spec) => ({
                            key: spec.key || '',
                            value: spec.value || '',
                        })) : [],
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
                        warranty_detail: supplier.warranty_detail ?? '',
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
