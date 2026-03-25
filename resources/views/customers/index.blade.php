@extends('layouts.app')

@section('title', 'Manajemen Customer')

@section('content')
<div class="px-5">
    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">
            Manajemen Customer
        </h1>

        <button
            onclick="openModal('modal-tambah-customer')"
            class="inline-flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-slate-800 transition">
            <i class="fas fa-plus text-xs"></i>
            Tambah Customer
        </button>

    </div>

    {{-- SEARCH --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
        <form action="{{ route('customers.index') }}" method="GET">
            <div class="flex gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama, email, atau telepon..."
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
                    <th class="px-6 py-4 text-left font-medium">Nama</th>
                    <th class="px-6 py-4 text-left font-medium">Email</th>
                    <th class="px-6 py-4 text-left font-medium">Telepon</th>
                    <th class="px-6 py-4 text-left font-medium">Alamat</th>
                    <th class="px-6 py-4 text-right font-medium">Aksi</th>
                </tr>
            </thead>

            <tbody class="">
                @foreach($customers as $customer)
                <tr class="hover:bg-slate-50 transition border-b border-gray-200">
                    <td class="px-6 py-4 font-medium text-slate-800">
                        {{ $customer->name }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $customer->email }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $customer->phone }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $customer->address }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-3">
                            <button
                                onclick="openModal('modal-edit-customer-{{ $customer->id }}')"
                                class="text-slate-500 hover:text-slate-900 transition"
                                title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>


                            <form
                                action="{{ route('customers.destroy', $customer) }}"
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus customer ini?')">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="text-red-500 hover:text-red-700 transition"
                                    title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- Edit Customer -->
                <x-modal id="modal-edit-customer-{{ $customer->id }}" title="Edit Customer" size="md">
                    <form action="{{ route('customers.update', $customer) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama Customer</label>
                            <input type="text" name="name" value="{{ old('name', $customer->name) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('name') @enderror"
                                required>
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('email') @enderror"
                                required>
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">No. Telepon</label>
                            <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('phone') @enderror"
                                required>
                            @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Alamat</label>
                            <textarea name="address" rows="4"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('address') @enderror"
                                required>{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                                Simpan
                            </button>
                            <button type="button" onclick="closeModal('modal-edit-customer-{{ $customer->id }}')"
                                class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">
                                Batal
                            </button>
                        </div>
                    </form>
                </x-modal>

                <!-- Tambah Customer -->
                <x-modal id="modal-tambah-customer" title="Tambah Customer" size="md">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama Customer</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('name') @enderror"
                                required>
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('email') @enderror"
                                required>
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-600 mb-1">No. Telepon</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('phone') @enderror"
                                required>
                            @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Alamat</label>
                            <textarea name="address" rows="4"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('address') @enderror"
                                required>{{ old('address') }}</textarea>
                            @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                                Simpan
                            </button>
                            <button type="button" onclick="closeModal('modal-tambah-customer')"
                                class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">
                                Batal
                            </button>
                        </div>
                    </form>
                </x-modal>
                @endforeach
            </tbody>
        </table>
    </div>




    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $customers->links() }}
    </div>
</div>
@endsection