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

                get dirtyCount() {
                    return this.rows.filter((row) => row.is_dirty || row.is_new || row.marked_for_delete).length;
                },

                get hasChanges() {
                    return this.dirtyCount > 0;
                },
