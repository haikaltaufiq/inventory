@extends('layouts.app')

@section('title', 'Manajemen Supplier')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">
            Manajemen Supplier
        </h1>

        <button
            onclick="openModal('modal-tambah-supplier')"
            class="inline-flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-slate-800 transition">
            <i class="fas fa-plus text-xs"></i>
            Tambah Supplier
        </button>

    </div>

    {{-- SEARCH --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
        <form action="{{ route('supplier.index') }}" method="GET">
            <div class="flex gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama supplier..."
                    class="flex-1 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-200">
                <button
                    type="submit"
                    class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm hover:bg-slate-800 transition">
                    Cari
                </button>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-6 py-4 text-left font-medium">Nama Supplier</th>
                    <th class="px-6 py-4 text-left font-medium">Alamat</th>
                    <th class="px-6 py-4 text-right font-medium">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @foreach($suppliers as $supplier)
                <tr class="hover:bg-slate-50 transition border-b border-gray-200">

                    <td class="px-6 py-4 font-medium text-slate-800">
                        {{ $supplier->nama_supplier }}
                    </td>

                    <td class="px-6 py-4 text-slate-600">
                        {{ $supplier->alamat }}
                    </td>

                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-3">
                            <button
                                type="button"
                                onclick="openModal('edit-supplier-{{ $supplier->id }}')"
                                class="text-slate-500 hover:text-slate-900 transition"
                                title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>


                            <button
                                type="button"
                                onclick="openModal('delete-{{ $supplier->id }}')"
                                class="text-red-500 hover:text-red-700 transition"
                                title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- DELETE MODAL --}}
                <x-modal id="delete-{{ $supplier->id }}" size="sm">
                    <div class="flex flex-col items-center text-center">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-exclamation text-red-600"></i>
                        </div>

                        <h2 class="text-lg font-semibold mb-1">
                            Hapus Supplier
                        </h2>

                        <p class="text-sm text-slate-500 mb-6">
                            Yakin ingin menghapus
                            <span class="font-medium text-slate-800">
                                {{ $supplier->nama_supplier }}
                            </span>?
                            <br>
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        <div class="flex w-full gap-3">
                            <button
                                type="button"
                                onclick="closeModal('delete-{{ $supplier->id }}')"
                                class="flex-1 py-2 px-4 rounded-xl border text-sm hover:bg-slate-50 transition">
                                Batal
                            </button>

                            <form action="{{ route('supplier.destroy', $supplier) }}" method="POST" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="w-full py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700 transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </x-modal>
                @endforeach
            </tbody>
        </table>

        <!-- Tambah Supplier -->
        <x-modal id="modal-tambah-supplier" title="Tambah Supplier" size="md">
            <form action="{{ route('supplier.store') }}" method="POST">
                @csrf

                {{-- Nama Supplier --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nama Supplier</label>
                    <input type="text" name="nama_supplier" value="{{ old('nama_supplier') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('nama_supplier') @enderror"
                        required>
                    @error('nama_supplier')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Alamat</label>
                    <input type="text" name="alamat" value="{{ old('alamat') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('alamat') @enderror"
                        required>
                    @error('alamat')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                        Simpan
                    </button>
                    <button type="button" onclick="closeModal('modal-tambah-supplier')"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">
                        Batal
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Edit Supplier -->
        @foreach($suppliers as $supplier)
        <x-modal id="edit-supplier-{{ $supplier->id }}" title="Edit Supplier" size="md">
            <form action="{{ route('supplier.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nama Supplier</label>
                    <input type="text" name="nama_supplier" value="{{ old('nama_supplier', $supplier->nama_supplier) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        required>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Alamat</label>
                    <input type="text" name="alamat" value="{{ old('alamat', $supplier->alamat) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        required>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                        Simpan
                    </button>
                    <button type="button" onclick="closeModal('edit-supplier-{{ $supplier->id }}')"
                        class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">
                        Batal
                    </button>
                </div>
            </form>
        </x-modal>
        @endforeach

    </div>

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $suppliers->links() }}
    </div>

</div>
@endsection