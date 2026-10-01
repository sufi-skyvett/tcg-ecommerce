<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/mds_banner.jpg') }}" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $pageDescription ?? 'landing page MDS OCG' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-900 font-sans">

    <!-- Navbar -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/mds_banner.jpg') }}" alt="MDS OCG Logo" class="h-20">
                <span class="text-2xl font-bold text-blue-600">MDS OCG</span>
            </a>

            <div class="flex gap-4 items-center">
                @auth
                    <span class="text-sm">Hi, {{ Auth::user()->name }}</span>
                    <a href="{{ url('/cart') }}" class="text-sm text-blue-600 hover:underline">Cart</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-red-600 hover:underline">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:underline">Login</a>
                    <!-- <a href="{{ route('register') }}" class="text-sm text-blue-600 hover:underline">Register</a> -->
                @endauth
            </div>


        </div>
    </nav>

    <a href="https://wa.me/01117518933?text=Hi%20I%20have%20a%20question%20about%20your%20cards" target="_blank"
        class="fixed bottom-4 right-4 z-50">
        <img src="{{ asset('images/whatsapp_icon.png') }}" alt="Chat with us on WhatsApp"
            class="h-[40px] w-[40px] object-contain" style="height: 40px !important; width: 40px !important;">
    </a>


    <!-- Page Content -->
    <main class="py-8">
        @yield('content')
    </main>

</body>

</html>
