<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen overflow-hidden">

    <div class="flex flex-col md:flex-row h-full">

        <!-- LEFT SIDE -->
        <div class="hidden md:flex md:w-1/2 flex-col p-6 lg:p-5 bg-slate-900">
            <h1 class="text-white text-5xl font-bold italic pt-10 pl-10">
                NATOPC
            </h1>

            <div class="flex flex-1 items-center justify-center">|

                <div class="flex flex-col items-center text-center gap-6">


                    <img
                        src="/assets/hero-illustration.svg"
                        alt="Main content"
                        class="w-120 h-auto" />
                </div>
            </div>
            <p class="text-gray-400 tracking-widest font-extralight mb-7  text-sm pt-2 pl-10">
                Website internal untuk mengelola inventaris dan transaksi perusahaan.
            </p>
        </div>



        <!-- RIGHT SIDE -->
        <div class="flex w-full md:w-1/2 items-center justify-center p-6 lg:p-10">
            <div class="w-full max-w-md">

                <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 text-center mb-8 font-poppins">
                    Login
                </h1>

                <!-- ERROR -->
                @if ($errors->has('email'))
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                    {{ $errors->first('email') }}
                </div>
                @endif

                <form method="POST" action="{{ url('auth/login') }}" class="space-y-5 px-10">
                    @csrf

                    <!-- EMAIL -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700 font-poppins">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="Masukkan email"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-xs text-gray-700 focus:outline-none focus:border-gray-400">
                    </div>

                    <!-- PASSWORD -->
                    <div class="relative">
                        <label class="block mb-1 text-sm font-medium text-gray-700 font-poppins">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            placeholder="Masukkan password"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 pr-11 text-xs text-gray-700 focus:outline-none focus:border-gray-400">

                        <!-- EYE TOGGLE -->
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute right-3 top-8.5 text-gray-400 hover:text-gray-600 transition">
                            <!-- EYE CLOSED -->
                            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12
                                C3.226 16.338 7.244 19.5 12 19.5
                                c.993 0 1.953-.138 2.863-.395
                                M6.228 6.228A10.45 10.45 0 0112 4.5
                                c4.756 0 8.773 3.162 10.065 7.498
                                a10.523 10.523 0 01-4.293 5.774
                                M6.228 6.228L3 3m3.228 3.228
                                l3.65 3.65m7.894 7.894L21 21
                                m-3.228-3.228l-3.65-3.65
                                m0 0a3 3 0 10-4.243-4.243
                                m4.242 4.242L9.88 9.88" />
                            </svg>

                            <!-- EYE OPEN -->
                            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 hidden"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 010-.639
                                C3.423 7.51 7.36 4.5 12 4.5
                                c4.638 0 8.573 3.007 9.963 7.178
                                .07.207.07.431 0 .639
                                C20.577 16.49 16.64 19.5 12 19.5
                                c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>

                    <!-- SUBMIT -->
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-slate-900 py-3 text-sm font-medium text-white hover:bg-slate-800 transition font-poppins">
                        Login
                    </button>
                </form>

            </div>
        </div>

    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        togglePassword.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            eyeOpen.classList.toggle('hidden', !isHidden);
            eyeClosed.classList.toggle('hidden', isHidden);
        });
    </script>

</body>

</html>