@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="px-5">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Manajemen Pengguna</h1>

        <button
            onclick="openModal('modal-tambah-user')"
            class="inline-flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-slate-800 transition">
            <i class="fas fa-plus text-xs"></i>
            Tambah Pengguna
        </button>
    </div>

    {{-- SEARCH --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-6">
        <form action="{{ route('users.index') }}" method="GET">
            <div class="flex gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama atau email pengguna..."
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
                    <th class="px-6 py-4 text-left font-medium">Peran</th>
                    <th class="px-6 py-4 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="hover:bg-slate-50 transition border-b border-gray-200">
                    <td class="px-6 py-4 font-medium text-slate-800">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs bg-slate-100 text-slate-600">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-3">
                            <button type="button" onclick="openModal('modal-edit-user-{{ $user->id }}')"
                                class="text-slate-500 hover:text-slate-900 transition" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>

                            <button type="button" onclick="openModal('delete-{{ $user->id }}')"
                                class="text-red-500 hover:text-red-700 transition" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- DELETE MODAL --}}
                <x-modal id="delete-{{ $user->id }}" size="sm">
                    <div class="flex flex-col items-center text-center">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-exclamation text-red-600"></i>
                        </div>
                        <h2 class="text-lg font-semibold mb-1">Hapus Pengguna</h2>
                        <p class="text-sm text-slate-500 mb-6">
                            Yakin ingin menghapus <span class="font-medium text-slate-800">{{ $user->name }}</span>?<br>
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <div class="flex w-full gap-3">
                            <button type="button" onclick="closeModal('delete-{{ $user->id }}')"
                                class="flex-1 py-2 px-4 rounded-xl border text-sm hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button class="w-full py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700 transition">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </x-modal>

                {{-- EDIT USER MODAL --}}
                <x-modal id="modal-edit-user-{{ $user->id }}" title="Edit Pengguna" size="md">
                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Nama --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Nama</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('name') @enderror"
                                required>
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('email') @enderror"
                                required>
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Peran</label>
                            <select name="role"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('role') @enderror"
                                required>
                                <option value="">Pilih Peran</option>
                                <option value="staff" @selected(old('role', $user->role) === 'staff')>Staff</option>
                                <option value="owner" @selected(old('role', $user->role) === 'owner')>Owner</option>
                            </select>
                            @error('role')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-6 relative">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Password</label>
                            <input type="password" name="password" id="password-{{ $user->id }}" placeholder="Minimal 6 karakter"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 pr-10">
                            <button type="button" onclick="togglePassword('{{ $user->id }}')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-700">
                                <i id="password-icon-{{ $user->id }}" class="fas fa-eye"></i>
                            </button>
                            <p id="password-error-{{ $user->id }}" class="text-red-500 text-xs mt-1 hidden">Password minimal 6 karakter</p>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex gap-3">
                            <button type="submit" onclick="return validatePassword('{{ $user->id }}')"
                                class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">
                                Simpan
                            </button>
                            <button type="button" onclick="closeModal('modal-edit-user-{{ $user->id }}')"
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
        {{ $users->links() }}
    </div>

</div>

{{-- TAMBAH USER MODAL --}}
<x-modal id="modal-tambah-user" title="Tambah Pengguna" size="md">
    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        {{-- Nama --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-600 mb-1">Nama</label>
            <input type="text" name="name" value="{{ old('name') }}"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('name') @enderror"
                required>
            @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('email') @enderror"
                required>
            @error('email')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Role --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-600 mb-1">Peran</label>
            <select name="role"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 @error('role') @enderror"
                required>
                <option value="">Pilih Peran</option>
                <option value="staff">Staff</option>
                <option value="owner">Owner</option>
            </select>
            @error('role')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-6 relative">
            <label class="block text-sm font-medium text-slate-600 mb-1">Password</label>
            <input type="password" name="password" id="password" placeholder="Minimal 6 karakter"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 pr-10">
            <button type="button" onclick="togglePassword('')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-700">
                <i id="password-icon" class="fas fa-eye"></i>
            </button>
            <p id="password-error" class="text-red-500 text-xs mt-1 hidden">Password minimal 6 karakter</p>
        </div>

        {{-- Buttons --}}
        <div class="flex gap-3">
            <button type="submit" onclick="return validatePassword('')" class="flex-1 py-3 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">Simpan</button>
            <button type="button" onclick="closeModal('modal-tambah-user')" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200">Batal</button>
        </div>
    </form>
</x-modal>

<script>
    function validatePassword(id = '') {
        const password = document.getElementById('password' + (id ? '-' + id : ''));
        const error = document.getElementById('password-error' + (id ? '-' + id : ''));
        if (password.value.length < 6) {
            error.classList.remove('hidden');
            password.focus();
            return false;
        } else {
            error.classList.add('hidden');
            return true;
        }
    }

    function togglePassword(id = '') {
        const password = document.getElementById('password' + (id ? '-' + id : ''));
        const icon = document.getElementById('password-icon' + (id ? '-' + id : ''));
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection