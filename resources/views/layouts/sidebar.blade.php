@php
    // Helper for active state
    $isActive = function (array $routeNames) {
        foreach ($routeNames as $r) {
            if (request()->routeIs($r)) return true;
        }
        return false;
    };

    $linkClass = function (bool $active) {
        return $active
            ? 'flex items-center justify-between px-3 py-2 rounded-lg bg-indigo-600 text-white shadow'
            : 'flex items-center justify-between px-3 py-2 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700';
    };

    $subLinkClass = function (bool $active) {
        return $active
            ? 'block px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200'
            : 'block px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
    };

    $cardsOpen = $isActive(['cards.*', 'cards.import.*']);
@endphp

<div class="h-full flex flex-col">
    {{-- Brand --}}
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
            POS Card Shop
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Inventory & Sales
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-3">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" class="{{ $linkClass(request()->routeIs('dashboard')) }}">
            <span>Dashboard</span>
        </a>

        {{-- Cards Group --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <button type="button"
                class="w-full {{ $linkClass($cardsOpen) }}"
                onclick="document.getElementById('cardsMenu').classList.toggle('hidden')">
                <span>Cards</span>
                <span class="text-xs opacity-90">{{ $cardsOpen ? '−' : '+' }}</span>
            </button>

            <div id="cardsMenu" class="{{ $cardsOpen ? '' : 'hidden' }} p-2 bg-white dark:bg-gray-800">
                <div class="space-y-1">
                    <a href="{{ route('cards.index') }}"
                       class="{{ $subLinkClass(request()->routeIs('cards.index')) }}">
                        All Cards
                    </a>

                    <a href="{{ route('cards.create') }}"
                       class="{{ $subLinkClass(request()->routeIs('cards.create')) }}">
                        Add New Card
                    </a>

                    @if(Route::has('cards.import.create'))
                        <a href="{{ route('cards.import.create') }}"
                           class="{{ $subLinkClass(request()->routeIs('cards.import.*')) }}">
                            Import (Yugipedia)
                        </a>
                    @endif

                    {{-- Optional quick links --}}
                    <a href="{{ route('cards.index', ['trashed' => 1]) }}"
                       class="{{ $subLinkClass(request('trashed') == 1) }}">
                        Deleted Cards
                    </a>
                </div>
            </div>
        </div>

        {{-- Future modules placeholders --}}
        <div class="pt-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 px-2 mb-2">
                Coming Soon
            </div>

            <div class="space-y-1">
                <a href="#"
                   class="block px-3 py-2 rounded-lg text-gray-400 cursor-not-allowed bg-gray-50 dark:bg-gray-900/30">
                    Inventory Lots
                </a>
                <a href="#"
                   class="block px-3 py-2 rounded-lg text-gray-400 cursor-not-allowed bg-gray-50 dark:bg-gray-900/30">
                    Sales / POS
                </a>
                <a href="#"
                   class="block px-3 py-2 rounded-lg text-gray-400 cursor-not-allowed bg-gray-50 dark:bg-gray-900/30">
                    Customers
                </a>
            </div>
        </div>
    </nav>

    {{-- Footer --}}
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
        <div>Logged in: {{ auth()->user()->name ?? '—' }}</div>
        <div class="mt-1">v{{ config('app.version', '1.0') }}</div>
    </div>
</div>
