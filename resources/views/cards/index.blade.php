@extends('layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
                Cards
            </h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Manage card catalog (search, filter, edit, soft delete).
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('cards.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow">
                + New Card
            </a>

            @if (Route::has('cards.import.create'))
                <a href="{{ route('cards.import.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 hover:bg-black text-white text-sm font-medium rounded-lg shadow">
                    Import (Yugipedia)
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    {{-- Force base text color inside page (prevents washed out UI) --}}
    <div class="text-gray-900 dark:text-gray-100">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4 mb-6">
            <form method="GET" action="{{ route('cards.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-4">
                    <label class="block text-sm font-semibold !text-gray-900 dark:!text-gray-100">Search</label>
                    <input type="text" name="q" value="{{ $q ?? request('q') }}"
                           placeholder="Name / Code / Barcode"
                           class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 !text-gray-900 dark:!text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold !text-gray-900 dark:!text-gray-100">Game</label>
                    <input type="text" name="game" value="{{ $game ?? request('game') }}"
                           placeholder="Yu-Gi-Oh!"
                           class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 !text-gray-900 dark:!text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold !text-gray-900 dark:!text-gray-100">Active</label>
                    <select name="active"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 !text-gray-900 dark:!text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" @selected(request('active') === null || request('active') === '')>All</option>
                        <option value="1" @selected((string)request('active') === '1')>Active</option>
                        <option value="0" @selected((string)request('active') === '0')>Inactive</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold !text-gray-900 dark:!text-gray-100">Sealed</label>
                    <select name="sealed"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 !text-gray-900 dark:!text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" @selected(request('sealed') === null || request('sealed') === '')>All</option>
                        <option value="1" @selected((string)request('sealed') === '1')>Sealed Product</option>
                        <option value="0" @selected((string)request('sealed') === '0')>Singles</option>
                    </select>
                </div>

                <div class="md:col-span-2 flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold !text-gray-900 dark:!text-gray-100">
                        <input type="checkbox" name="trashed" value="1"
                               class="rounded border-gray-300 dark:border-gray-700"
                               @checked((bool)($trashed ?? request()->boolean('trashed'))) />
                        Show deleted
                    </label>

                    <div class="flex gap-2 ml-auto">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                            Filter
                        </button>

                        <a href="{{ route('cards.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm font-medium !text-gray-900 dark:!text-gray-100">
                    Showing <span class="font-semibold">{{ $cards->firstItem() ?? 0 }}</span>–<span class="font-semibold">{{ $cards->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold">{{ $cards->total() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Card</th>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Game</th>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Lang</th>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold !text-gray-900 dark:!text-gray-100 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($cards as $card)
                            <tr class="{{ $card->deleted_at ? 'opacity-60' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                                            @if($card->front_image_url)
                                                <img src="{{ $card->front_image_url }}" alt="{{ $card->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">No Img</span>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <div class="font-semibold !text-gray-900 dark:!text-gray-100 truncate">
                                                {{ $card->name }}
                                            </div>
                                            <div class="text-xs text-gray-700 dark:text-gray-300">
                                                {{ $card->brand ?? '-' }}
                                                @if($card->rarity) • {{ $card->rarity }} @endif
                                                @if($card->finish) • {{ $card->finish }} @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm !text-gray-900 dark:!text-gray-100">
                                    <div class="font-mono">{{ $card->card_code }}</div>
                                    @if($card->collector_number)
                                        <div class="text-xs text-gray-700 dark:text-gray-300">
                                            {{ $card->collector_number }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm !text-gray-900 dark:!text-gray-100">
                                    {{ $card->game ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-sm !text-gray-900 dark:!text-gray-100">
                                    {{ $card->language ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-sm !text-gray-900 dark:!text-gray-100">
                                    {{ $card->is_sealed_product ? 'Sealed' : 'Single' }}
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    @if($card->is_active)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Inactive
                                        </span>
                                    @endif

                                    @if($card->deleted_at)
                                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                                            Deleted
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('cards.show', $card) }}"
                                           class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">
                                            View
                                        </a>

                                        <a href="{{ route('cards.edit', $card) }}"
                                           class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">
                                            Edit
                                        </a>

                                        @if($card->deleted_at)
                                            <form method="POST" action="{{ route('cards.restore', $card->id) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white">
                                                    Restore
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('cards.destroy', $card) }}" class="inline"
                                                  onsubmit="return confirm('Delete this card? (soft delete)')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-700 dark:text-gray-300">
                                    No cards found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $cards->links() }}
            </div>
        </div>
    </div>
@endsection
