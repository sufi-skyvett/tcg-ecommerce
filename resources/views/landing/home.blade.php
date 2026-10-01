@extends('layouts.landing')

@section('content')
    <!-- Hero Section -->
    <section class="bg-white">
        <div class="max-w-7xl mx-auto px-4 py-16 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 text-red-700 text-sm mb-4">
                <span class="font-semibold">MDS OCG</span>
                <span class="text-red-400">•</span>
                <span>Duel Masters Card Translation &amp; Gallery</span>
            </div>

            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 text-gray-900">
                Welcome to MDS OCG <span class="text-red-600">Duel Masters</span>
            </h1>

            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
                Instant English translation and database lookup for Japanese Duel Masters cards.
                Search directly by expansion box code, collector number, or card name.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="#gallery"
                    class="bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition font-medium shadow-sm">
                    Lookup Cards
                </a>
                <a href="#about"
                    class="bg-white text-gray-900 px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition font-medium">
                    Community &amp; Project Info
                </a>
            </div>
        </div>
    </section>

    <!-- Cards Section -->
    <section id="gallery" class="bg-gray-50 border-t">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Card Database &amp; Translations</h2>
                    <p class="text-gray-600 text-sm mt-1">
                        Filter by Box/Set code and collector number (e.g. <span class="font-mono text-red-600 bg-red-50 px-1 py-0.5 rounded">24EX1</span> + <span class="font-mono text-red-600 bg-red-50 px-1 py-0.5 rounded">77/89</span>) or card name.
                    </p>
                </div>

                <!-- Server-Side Search Form -->
                <form method="GET" action="{{ url()->current() }}#gallery" class="flex flex-wrap sm:flex-nowrap items-end gap-3 w-full lg:w-auto">
                    <!-- Set / Box Search -->
                    <div class="w-full sm:w-36">
                        <label for="setSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Box / Set Code
                        </label>
                        <input id="setSearch" name="set" value="{{ request('set') }}" type="text" placeholder="e.g. 24EX1"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-mono" />
                    </div>

                    <!-- Card Number Search -->
                    <div class="w-full sm:w-32">
                        <label for="numberSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Card No.
                        </label>
                        <input id="numberSearch" name="number" value="{{ request('number') }}" type="text" placeholder="e.g. 77/89"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-mono" />
                    </div>

                    <!-- Card Name Search -->
                    <div class="w-full sm:w-48">
                        <label for="nameSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Card Name (Optional)
                        </label>
                        <input id="nameSearch" name="name" value="{{ request('name') }}" type="text" placeholder="e.g. Jenny's..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btnSearchAction"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm transition">
                        <span id="btnSearchText">Search</span>
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Status Banner (for errors/messages) -->
            <div id="statusAlert" class="hidden mb-4 p-3 rounded-xl text-xs font-medium border"></div>

            <!-- Gallery Wrapper -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-800">Available Cards</div>
                    <div id="resultsCount" class="text-xs text-gray-500 font-mono">
                        Showing {{ $cards->firstItem() ?? 0 }} to {{ $cards->lastItem() ?? 0 }} of {{$cards->total() }}
                    </div>
                </div>

                <div class="p-5">
                    @if($cards->count() > 0)
                        <!-- Grid container -->
                        <div id="cardsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($cards as $item)
                                @php
                                    $cardName =$item->name ?? '';
                                    $cardSet =$item->set_code ?? '';
                                    $cardNum = $item->collector_number ?? ($item->card_number ?? '');
                                    $cardType =$item->card_type ?? '';
                                    $cardCiv =$item->civilization ?? '';
                                    $cardEffect =$item->effect_text ?? '';
                                    $cardImg =$item->image_path ?? '';
                                @endphp
                                <div class="card-item group">
                                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition overflow-hidden flex flex-col h-full">
                                        <div class="h-64 sm:h-72 bg-gray-100 overflow-hidden relative flex items-center justify-center p-3">
                                            @if(!empty($cardImg))
                                                <img src="{{ str_starts_with($cardImg, 'http') ? $cardImg : asset('storage/' .$cardImg) }}"
                                                    alt="{{ $cardName ?: 'Card' }}"
                                                    loading="lazy"
                                                    class="max-w-full max-h-full object-contain group-hover:scale-[1.05] transition duration-200">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 text-xs p-2 text-center">
                                                    <span>No Card Art</span>
                                                </div>
                                            @endif

                                            @if(!empty($cardSet) || !empty($cardNum))
                                                <span class="absolute bottom-1.5 left-1.5 bg-black/75 backdrop-blur-sm text-white font-mono text-[10px] px-1.5 py-0.5 rounded z-10">
                                                    {{ $cardSet }} {{$cardNum }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="p-3 flex-1 flex flex-col justify-between">
                                            <div>
                                                <div class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2" title="{{ $cardName }}">
                                                    {{ $cardName ?: 'Unknown Card' }}
                                                </div>
                                                @if(!empty($cardType))
                                                    <div class="text-[11px] text-gray-500 mt-1 capitalize font-medium">
                                                        {{ $cardType }}
                                                        @if(!empty($cardCiv))
                                                            • {{ $cardCiv }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            @if(!empty($cardEffect))
                                                <div class="mt-2 pt-2 border-t border-gray-50 text-[11px] text-gray-600 line-clamp-3 leading-relaxed">
                                                    {{ $cardEffect }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Laravel Pagination Links -->
                        <div class="mt-8">
                            {{ $cards->links() }}
                        </div>
                    @else
                        <!-- Zero Results States -->
                        @if(request()->filled('set') && request()->filled('number'))
                            <!-- User searched specifically for Set+Number but it's not in DB -->
                            <div id="noResults" class="py-14 px-4 text-center">
                                <div class="max-w-md mx-auto">
                                    <h3 class="text-base font-bold text-gray-800 mb-1">Card not found in local database</h3>
                                    <p class="text-gray-500 text-xs mb-4">Would you like to fetch its details and artwork from the Fandom Wiki?</p>
                                    <button id="btnFetchWikiDirect" type="button"
                                        class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                                        <span>Fetch Card From Wiki</span>
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- Generic empty database state -->
                            <div id="initialEmpty" class="py-16 text-center text-gray-500">
                                <div class="text-3xl mb-2">🃏</div>
                                <p class="font-medium text-sm text-gray-700">No cards matched your search.</p>
                                <p class="text-xs text-gray-400 mt-1">Make sure you provide both <strong>Set Code</strong> and <strong>Card No</strong> if you want to pull a new card from the wiki.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- About / Footer Section -->
    <section id="about" class="bg-white py-12 border-t mt-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="text-xl font-bold mb-2 text-gray-900">MDS OCG</h3>
            <p class="text-gray-600 max-w-2xl mx-auto text-sm leading-relaxed">
                #DuelMasters #MalaysiaDuelistSociety #MDSOCG #TakaraTomy #TCGCommunity #SEACards #CardTranslations
            </p>
            <div class="mt-6 max-w-3xl mx-auto text-xs text-gray-400 leading-normal border-t border-gray-100 pt-6">
                Duel Masters is a registered trademark of Takara Tomy and Wizards of the Coast.
                This translation archive is an independent, non-commercial fan initiative managed by the MDS OCG community
                to support local play, casual deck testing, and tournament organization across Malaysia.
            </div>
            <p class="text-xs text-gray-400 mt-4 mb-6">&copy; {{ date('Y') }} MDS OCG. Built for the community.</p>

            <!-- Non-Profit Disclaimer Banner -->
            <div class="bg-amber-50 border border-amber-200 text-amber-900 text-xs py-3 px-4 rounded-xl text-center max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                    <span class="inline-block px-1.5 py-0.5 rounded bg-amber-200 text-amber-800 font-bold uppercase text-[10px] tracking-wider shrink-0">Disclaimer</span>
                    <span>
                        MDS OCG Duel Masters Translation Hub is a voluntary, non-profit community project built for Southeast Asian players.
                        No copyright infringement is intended.
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript Handler -->
    <script>
        (function() {
            const statusAlert = document.getElementById('statusAlert');
            const btnSearchAction = document.getElementById('btnSearchAction');
            const btnSearchText = document.getElementById('btnSearchText');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnFetchWikiDirect = document.getElementById('btnFetchWikiDirect');

            function showAlert(msg, isError = false) {
                if (!statusAlert) return;
                statusAlert.textContent = msg;
                statusAlert.className = isError
                    ? 'mb-4 p-3 rounded-xl text-xs font-medium border bg-red-50 text-red-700 border-red-200 block'
                    : 'mb-4 p-3 rounded-xl text-xs font-medium border bg-green-50 text-green-700 border-green-200 block';
            }

            function clearAlert() {
                if (statusAlert) statusAlert.className = 'hidden';
            }

            async function triggerWikiFetch() {
                const rawSet = document.getElementById('setSearch').value.trim();
                const rawNum = document.getElementById('numberSearch').value.trim();

                if (!rawSet || !rawNum) {
                    showAlert('Please provide both Box/Set Code and Card No to fetch from Wiki.', true);
                    return;
                }

                if (btnSearchAction) btnSearchAction.disabled = true;
                if (btnSearchText) btnSearchText.textContent = "Fetching...";
                if (btnSpinner) btnSpinner.classList.remove('hidden');

                // Disable wiki button to prevent double-click
                if(btnFetchWikiDirect) btnFetchWikiDirect.disabled = true;
                clearAlert();

                try {
                    const response = await fetch("{{ route('cards.fetch-wiki') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            set_code: rawSet,
                            collector_number: rawNum
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Card could not be found on Fandom Wiki.');
                    }

                    showAlert(`Successfully fetched: ${data.card.name}! Reloading page to display...`, false);

                    // Reload the page to display the newly added card via server-side rendering
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);

                } catch (err) {
                    showAlert(err.message, true);
                    if (btnSearchAction) btnSearchAction.disabled = false;
                    if (btnSearchText) btnSearchText.textContent = "Search";
                    if (btnSpinner) btnSpinner.classList.add('hidden');
                    if (btnFetchWikiDirect) btnFetchWikiDirect.disabled = false;
                }
            }

            // Bind wiki fetch button if it exists on the page (renders when 0 results)
            if (btnFetchWikiDirect) {
                btnFetchWikiDirect.addEventListener('click', triggerWikiFetch);
            }
        })();
    </script>
@endsection
