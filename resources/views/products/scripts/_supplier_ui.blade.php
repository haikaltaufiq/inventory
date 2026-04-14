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
                    row.additional_specs.push({ key: '', value: '' });
                    this.markDirty(row, true);
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
