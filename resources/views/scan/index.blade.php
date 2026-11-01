@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-slate-900">Scan Property Tag</h2>
            <p class="text-base text-slate-600 mt-1">
                Scan a tag using your USB barcode/QR scanner, or type an Asset No. below and press <strong>Enter</strong>.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-5 rounded-xl bg-amber-50 border-2 border-amber-400 text-amber-950" role="alert">
                <div class="flex items-start gap-3">
                    <svg class="w-7 h-7 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="font-bold text-lg">
                            {{ $errors->first('q') }}
                        </p>
                        @if (! empty($notFoundCode))
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <a href="{{ route('assets.create', ['asset_no' => $notFoundCode]) }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Add New Asset with "{{ $notFoundCode }}"</span>
                                </a>
                                <a href="{{ route('assets.index', ['q' => $notFoundCode]) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition">
                                    <span>Search All Fields Instead</span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <form method="GET" action="{{ route('scan.lookup') }}" class="p-8 bg-slate-50 border-2 border-slate-200 rounded-xl shadow-xs">
            <label for="scan-input" class="block text-lg font-bold text-slate-900 mb-3">
                Asset No. / Barcode Input
            </label>

            <div class="flex flex-col sm:flex-row gap-3">
                <input id="scan-input"
                       name="q"
                       type="text"
                       autofocus
                       autocomplete="off"
                       value="{{ old('q', $notFoundCode ?? '') }}"
                       placeholder="e.g. BBHIND-S300002315 or BBH-000001"
                       class="flex-1 px-5 py-4 text-2xl font-mono font-bold border-2 border-blue-600 rounded-xl bg-white text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-100">

                <button type="submit"
                        class="px-8 py-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                    Find Asset
                </button>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-200 flex items-center gap-3 text-slate-600 text-base">
                <svg class="w-6 h-6 text-blue-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>
                    <strong>Ready to scan:</strong> Keep the cursor in the box above. Your USB scanner will type the Asset No. and open the asset automatically.
                </span>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('scan-input');
            if (input) {
                input.focus();
                input.select();
            }
        });
    </script>
@endsection
