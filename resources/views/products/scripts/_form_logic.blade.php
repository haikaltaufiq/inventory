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
                        fields.push({ name: `products[${row.client_key}][suppliers][${index}][warranty_detail]`, value: supplier.warranty_detail || '' });
                    });

                    return fields;
                },

                submitForm() {
                    if (!this.hasChanges) return;
                    this.isSubmittingForm = true;
                    document.getElementById('product-grid-form').submit();
                },