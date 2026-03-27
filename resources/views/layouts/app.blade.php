<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Sistem Toko Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        html {
            font-family: "Poppins", system-ui, sans-serif;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .nav-active {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white !important;
        }
    </style>
</head>

<body class="bg-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden bg-slate-900">

        <div x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden">
        </div>

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0 no-scrollbar overflow-y-auto">
            <div class="p-8 mb-4 flex items-center justify-between shrink-0">
                <h1 class="text-3xl font-bold italic ml-6">NATOPC</h1>
                <button @click="sidebarOpen = false" class="lg:hidden text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-2 px-4 no-scrollbar">
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-sm transition-all rounded-xl {{ request()->routeIs('dashboard') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="7" height="7" x="3" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="14" rx="1" />
                        <rect width="7" height="7" x="3" y="14" rx="1" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('dashboard') ? 'font-semibold' : 'font-normal' }}">Dashboard</span>
                </a>

                <a href="{{ route('transactions.index') }}" class="flex items-center px-6 py-3 text-sm transition-all rounded-xl {{ request()->routeIs('transactions.*') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1" />
                        <circle cx="19" cy="21" r="1" />
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('transactions.*') ? 'font-semibold' : 'font-normal' }} text-sm">Transaksi</span>
                </a>



                <a href="{{ route('products.index') }}" class="flex items-center text-sm px-6 py-3 transition-all rounded-xl {{ request()->routeIs('products.*') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v6" />
                        <path d="M16.76 3a2 2 0 0 1 1.8 1.1l2.23 4.479a2 2 0 0 1 .21.891V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9.472a2 2 0 0 1 .211-.894L5.45 4.1A2 2 0 0 1 7.24 3z" />
                        <path d="M3.054 9.013h17.893" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('products.*') ? 'font-semibold' : 'font-normal' }} text-sm">Inventori</span>
                </a>

                <a href="{{ route('customers.index') }}" class="flex text-sm items-center px-6 py-3 transition-all rounded-xl {{ request()->routeIs('customers.*') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 2v2" />
                        <path d="M7 22v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                        <path d="M8 2v2" />
                        <circle cx="12" cy="11" r="3" />
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('customers.*') ? 'font-semibold' : 'font-normal' }}">Pelanggan</span>
                </a>

                @if(auth()->user()->role === 'owner')
                <a href="{{ route('supplier.index') }}" class="flex items-center text-sm px-6 py-3 transition-all rounded-xl {{ request()->routeIs('supplier.*') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck-icon lucide-truck">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                        <path d="M15 18H9" />
                        <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                        <circle cx="17" cy="18" r="2" />
                        <circle cx="7" cy="18" r="2" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('supplier.*') ? 'font-semibold' : 'font-normal' }}">Supplier</span>
                </a>
                <a href="{{ route('report') }}" class="flex items-center px-6 py-3 text-sm transition-all rounded-xl {{ request()->routeIs('report') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                        <path d="M12 11h4" />
                        <path d="M12 16h4" />
                        <path d="M8 11h.01" />
                        <path d="M8 16h.01" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('report') ? 'font-semibold' : 'font-normal' }} text-sm">Laporan Transaksi</span>
                </a>
                <a href="{{ route('report.product') }}"
                    class="flex items-center px-6 py-3 text-sm transition-all rounded-xl
   {{ request()->routeIs('report.product') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-pen-line-icon lucide-clipboard-pen-line">
                        <rect width="8" height="4" x="8" y="2" rx="1" />
                        <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-.5" />
                        <path d="M16 4h2a2 2 0 0 1 1.73 1" />
                        <path d="M8 18h1" />
                        <path d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" />
                    </svg>

                    <span class="ml-3 {{ request()->routeIs('report.product') ? 'font-semibold' : 'font-normal' }}">
                        Laporan Stok
                    </span>
                </a>

                <a href="{{ route('users.index') }}" class="flex text-sm items-center px-6 py-3 transition-all rounded-xl {{ request()->routeIs('users.index') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('users.index') ? 'font-semibold' : 'font-normal' }}">User</span>
                </a>

                <a href="{{ route('pc-builder.index') }}" class="flex items-center text-sm px-6 py-3 transition-all rounded-xl {{ request()->routeIs('pc-builder.index') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor-cog-icon lucide-monitor-cog">
                        <path d="M12 17v4" />
                        <path d="m14.305 7.53.923-.382" />
                        <path d="m15.228 4.852-.923-.383" />
                        <path d="m16.852 3.228-.383-.924" />
                        <path d="m16.852 8.772-.383.923" />
                        <path d="m19.148 3.228.383-.924" />
                        <path d="m19.53 9.696-.382-.924" />
                        <path d="m20.772 4.852.924-.383" />
                        <path d="m20.772 7.148.924.383" />
                        <path d="M22 13v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7" />
                        <path d="M8 21h8" />
                        <circle cx="18" cy="6" r="3" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('pc-builder.index') ? 'font-semibold' : 'font-normal' }}">Simulasi PC</span>
                </a>

                <a href="{{ route('settings') }}" class="flex items-center text-sm px-6 py-3 transition-all rounded-xl {{ request()->routeIs('settings') ? 'nav-active' : 'hover:bg-white/10 text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-icon lucide-settings">
                        <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <span class="ml-3 {{ request()->routeIs('settings') ? 'font-semibold' : 'font-normal' }}">Pengaturan</span>
                </a>
                @endif
            </nav>

            <div class="p-6">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    {{-- NAV ITEM LOGOUT --}}
                    <button
                        type="button"
                        onclick="openModal('modal-logout')"
                        class="flex items-center text-sm px-6 py-3 transition-all rounded-xl hover:bg-white/10 text-white w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg>
                        <span class="ml-3 font-normal text-sm">Logout</span>
                    </button>


                </form>


            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-white">
            <header class="h-20 lg:h-10 flex items-center justify-between lg:justify-end px-6 lg:px-12 bg-white">
                <button @click="sidebarOpen = true" class="lg:hidden text-blue-950 p-2 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </header>

            <section class="flex-1 overflow-y-auto">
                <div class="bg-white lg:rounded-[40px] min-h-full px-4 lg:px-10 py-0">
                    @if(session('success'))
                    <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                    @endif
                    @yield('content')

                    {{-- LOGOUT MODAL --}}
                    <x-modal id="modal-logout" title="Konfirmasi Logout" size="sm">
                        <div class="flex flex-col items-center text-center">
                            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                                <i class="fas fa-exclamation text-red-600"></i>
                            </div>

                            <h2 class="text-lg font-semibold mb-1">Logout</h2>
                            <p class="text-sm text-slate-500 mb-6">
                                Yakin ingin keluar dari akun Anda?
                            </p>

                            <div class="flex w-full gap-3">
                                <button
                                    type="button"
                                    onclick="closeModal('modal-logout')"
                                    class="flex-1 py-2 px-4 rounded-xl border text-sm hover:bg-slate-50 transition">
                                    Batal
                                </button>

                                <form action="{{ route('logout') }}" method="POST" class="flex-1">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-full py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700 transition">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </x-modal>
                    {{-- ApexCharts dulu --}}
                    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

                    {{-- Baru script dari component --}}
                    @stack('scripts')
                </div>
            </section>
        </main>
    </div>
</body>

<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

</html>
