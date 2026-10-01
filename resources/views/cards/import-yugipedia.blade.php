@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Import Cards from Yugipedia (by Code Range)</h1>

    @if(session('status'))
        <pre>{{ print_r(session('status'), true) }}</pre>
    @endif

    <form method="POST" action="{{ route('cards.import.store') }}">
        @csrf

        <div>
            <label>Prefix (e.g. DBJH-JP or DBJH-AE)</label>
            <input name="prefix" value="DBJH-JP" required>
            @error('prefix') <div style="color:red">{{ $message }}</div> @enderror
        </div>

        <div>
            <label>Start number (e.g. 1 for 001)</label>
            <input type="number" name="start" value="1" min="1" max="999" required>
            @error('start') <div style="color:red">{{ $message }}</div> @enderror
        </div>

        <div>
            <label>End number (e.g. 45 for 045)</label>
            <input type="number" name="end" value="45" min="1" max="999" required>
            @error('end') <div style="color:red">{{ $message }}</div> @enderror
        </div>

        <hr>

        <div>
            <label>Defaults (optional)</label><br>
            <input name="game" placeholder="game" value="Yu-Gi-Oh!">
            <input name="brand" placeholder="brand" value="Konami">
            <input name="language" placeholder="language (optional override)">
        </div>

        <button type="submit">Import</button>
    </form>
</div>
@endsection
