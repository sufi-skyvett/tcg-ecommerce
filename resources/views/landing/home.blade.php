@extends('layouts.landing')

@section('content')
    <!-- Non-Profit Disclaimer Banner -->
    <div class="bg-amber-50 border-b border-amber-200 text-amber-900 text-xs py-2.5 px-4 text-center">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2">
            <span class="inline-block px-1.5 py-0.5 rounded bg-amber-200 text-amber-800 font-bold uppercase text-[10px] tracking-wider">Disclaimer</span>
            <span>
                MDS OCG Duel Masters Translation Hub is a voluntary, non-profit community project built for Southeast Asian players.
                Duel Masters is a trademark of Takara Tomy and Wizards of the Coast. No copyright infringement is intended.
            </span>
        </div>
    </div>

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

                <!-- Dual Search Filters + Action Button -->
                <div class="flex flex-wrap sm:flex-nowrap items-end gap-3 w-full lg:w-auto">
                    <!-- Set / Box Search -->
                    <div class="w-full sm:w-36">
                        <label for="setSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Box / Set Code
                        </label>
                        <input id="setSearch" type="text" placeholder="e.g. 24EX1"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-mono" />
                    </div>

                    <!-- Card Number Search -->
                    <div class="w-full sm:w-32">
                        <label for="numberSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Card No.
                        </label>
                        <input id="numberSearch" type="text" placeholder="e.g. 77/89"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-mono" />
                    </div>

                    <!-- Card Name Search -->
                    <div class="w-full sm:w-48">
                        <label for="nameSearch" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">
                            Card Name (Optional)
                        </label>
                        <input id="nameSearch" type="text" placeholder="e.g. Jenny's..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
                    </div>

                    <!-- Explicit Search Button -->
                    <button id="btnSearchAction" type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm transition">
                        <span id="btnSearchText">Search</span>
                        <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Status Banner (for errors/messages) -->
            <div id="statusAlert" class="hidden mb-4 p-3 rounded-xl text-xs font-medium border"></div>

            <!-- Gallery Wrapper -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-800">Available Cards</div>
                    <div id="resultsCount" class="text-xs text-gray-500 font-mono">Showing results</div>
                </div>

                <!-- Fixed height scroll area -->
                <div class="max-h-[72vh] overflow-auto p-5">
                    <!-- Always render the grid container so JS can insert cards -->
                    <div id="cardsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @if(isset($cards) &&$cards->count() > 0)
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
                                <div class="card-item group"
                                    data-name="{{ strtolower($cardName) }}"
                                    data-set="{{ strtolower(str_replace('-', '', $cardSet)) }}"
                                    data-number="{{ strtolower($cardNum) }}">
                                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition overflow-hidden flex flex-col h-full">
                                        <div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
                                            @if(!empty($cardImg))
                                                <img src="{{ str_starts_with($cardImg, 'http') ? $cardImg : asset('storage/' .$cardImg) }}"
                                                    alt="{{ $cardName ?: 'Card' }}"
                                                    loading="lazy"
                                                    class="w-full h-full object-cover group-hover:scale-[1.03] transition duration-200">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 text-xs p-2 text-center">
                                                    <span>No Card Art</span>
                                                </div>
                                            @endif

                                            @if(!empty($cardSet) || !empty($cardNum))
                                                <span class="absolute bottom-1.5 left-1.5 bg-black/75 backdrop-blur-sm text-white font-mono text-[10px] px-1.5 py-0.5 rounded">
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
                        @endif
                    </div>

                    <!-- Initial Empty Placeholder when DB is fresh -->
                    <div id="initialEmpty" class="{{ isset($cards) &&$cards->count() > 0 ? 'hidden' : '' }} py-16 text-center text-gray-500">
                        <div class="text-3xl mb-2">🃏</div>
                        <p class="font-medium text-sm text-gray-700">No cards registered yet.</p>
                        <p class="text-xs text-gray-400 mt-1">Enter a set (e.g. <strong>24EX1</strong>) and card number (e.g. <strong>77/89</strong>) above and click Search to auto-fetch!</p>
                    </div>

                    <!-- 0 Results Box with Auto-Fetch Trigger Button -->
                    <div id="noResults" class="hidden py-14 px-4 text-center">
                        <div class="max-w-md mx-auto">
                            <h3 class="text-base font-bold text-gray-800 mb-1">Card not found in local database</h3>
                            <p class="text-gray-500 text-xs mb-4">Would you like to fetch its details and artwork from the Fandom Wiki?</p>
                            <button id="btnFetchWikiDirect" type="button"
                                class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition">
                                <span>Fetch Card From Wiki</span>
                            </button>
                        </div>
                    </div>
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
                to support local play, casual deck testing, and tournament organization across Malaysia and Southeast Asia.
        </div>
            <p class="text-xs text-gray-400 mt-4">&copy; {{ date('Y') }} MDS OCG. Built for the community.</p>
        </div>
    </section>

    <!-- JavaScript Handler -->
    <script>
        (function() {
            const setSearch = document.getElementById('setSearch');
            const numberSearch = document.getElementById('numberSearch');
            const nameSearch = document.getElementById('nameSearch');
            const btnSearchAction = document.getElementById('btnSearchAction');
            const btnSearchText = document.getElementById('btnSearchText');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnFetchWikiDirect = document.getElementById('btnFetchWikiDirect');

            const grid = document.getElementById('cardsGrid');
            const resultsCount = document.getElementById('resultsCount');
            const noResults = document.getElementById('noResults');
            const initialEmpty = document.getElementById('initialEmpty');
            const statusAlert = document.getElementById('statusAlert');

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

            function normalizeCode(str) {
                return (str || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            }

            function applyFilter() {
                clearAlert();
                const items = Array.from(document.querySelectorAll('.card-item'));
                const rawSet = (setSearch ? setSearch.value : '').trim();
                const setQ = normalizeCode(rawSet);

                const rawNum = (numberSearch ? numberSearch.value : '').trim().toLowerCase();
                const numQ = rawNum.replace(/\s+/g, '');

                const nameQ = (nameSearch ? nameSearch.value : '').trim().toLowerCase();

                let shown = 0;
                items.forEach(el => {
                    const cardSet = normalizeCode(el.dataset.set || '');
                    const cardNum = (el.dataset.number || '').toLowerCase();
                    const cardName = (el.dataset.name || '').toLowerCase();

                    const matchSet = !setQ || cardSet.includes(setQ) || setQ.includes(cardSet);
                    const matchNum = !numQ || cardNum.includes(numQ);
                    const matchName = !nameQ || cardName.includes(nameQ);

                    const isMatch = matchSet && matchNum && matchName;
                    el.classList.toggle('hidden', !isMatch);
                    if (isMatch) shown++;
                });

                if (resultsCount) resultsCount.textContent = `Showing ${shown} card(s)`;

                if (shown === 0) {
                    if (initialEmpty) initialEmpty.classList.add('hidden');
                    if (noResults) noResults.classList.remove('hidden');
                } else {
                    if (noResults) noResults.classList.add('hidden');
                    if (initialEmpty) initialEmpty.classList.add('hidden');
                }

                return shown;
            }

            async function triggerWikiFetch() {
                const rawSet = (setSearch ? setSearch.value : '').trim();
                const rawNum = (numberSearch ? numberSearch.value : '').trim();

                if (!rawSet || !rawNum) {
                    showAlert('Please provide both Box/Set Code and Card No to fetch from Wiki.', true);
                    return;
                }

                btnSearchAction.disabled = true;
                btnSearchText.textContent = "Fetching...";
                btnSpinner.classList.remove('hidden');
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

                    const card = data.card;
                    const imageSrc = card.image_path
                        ? (card.image_path.startsWith('http') ? card.image_path : '/storage/' + card.image_path)
                        : null;

                    const cardElement = document.createElement('div');
                    cardElement.className = 'card-item group';
                    cardElement.setAttribute('data-name', (card.name || '').toLowerCase());
                    cardElement.setAttribute('data-set', normalizeCode(card.set_code || ''));
                    cardElement.setAttribute('data-number', (card.collector_number || '').toLowerCase());

                    cardElement.innerHTML = `
                        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition overflow-hidden flex flex-col h-full ring-2 ring-red-500/20">
                            <div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
                                ${imageSrc ? `
                                    <img src="${imageSrc}" alt="${card.name}" class="w-full h-full object-cover group-hover:scale-[1.03] transition duration-200">
                                ` : `
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 text-xs p-2 text-center">
                                        <span>No Card Art</span>
                                    </div>
                                `}
                                <span class="absolute bottom-1.5 left-1.5 bg-black/75 backdrop-blur-sm text-white font-mono text-[10px] px-1.5 py-0.5 rounded">
                                    ${card.set_code || ''} ${card.collector_number || ''}
                                </span>
                            </div>
                            <div class="p-3 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2" title="${card.name}">
                                        ${card.name}
                                    </div>
                                    ${card.card_type ? `
                                        <div class="text-[11px] text-gray-500 mt-1 capitalize font-medium">
                                            ${card.card_type}${card.civilization ? '• ' + card.civilization : ''}
                                        </div>
                                    ` : ''}
                                </div>
                                ${card.effect_text ? `
                                    <div class="mt-2 pt-2 border-t border-gray-50 text-[11px] text-gray-600 line-clamp-3 leading-relaxed">
                                        ${card.effect_text}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;

                    grid.prepend(cardElement);
                    showAlert(`Successfully fetched and cached: ${card.name}!`, false);
                    applyFilter();

                } catch (err) {
                    showAlert(err.message, true);
                } finally {
                    btnSearchAction.disabled = false;
                    btnSearchText.textContent = "Search";
                    btnSpinner.classList.add('hidden');
                }
            }

            // Button click logic: Filter local first; if 0 results, auto-fetch
            if (btnSearchAction) {
                btnSearchAction.addEventListener('click', function() {
                    const localMatches = applyFilter();
                    if (localMatches === 0 && setSearch.value.trim() && numberSearch.value.trim()) {
                        triggerWikiFetch();
                    }
                });
            }

            if (btnFetchWikiDirect) {
                btnFetchWikiDirect.addEventListener('click', triggerWikiFetch);
            }

            // Allow pressing "Enter" on any search input
            [setSearch, numberSearch, nameSearch].forEach(input => {
                if (input) {
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            btnSearchAction.click();
                        }
                    });
                }
            });

            applyFilter();
        })();
    </script>
@endsection
