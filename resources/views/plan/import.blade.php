@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Import (MVP)</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-900">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($run))
        <div class="mb-4 p-3 rounded bg-gray-100">
            <div><strong>Current Run:</strong> {{ $run['id'] }}</div>
            <div class="text-sm text-gray-600">
                Canvas: {{ $run['paths']['canvas'] }}
                @if(!empty($run['paths']['busy']))
                    • Busy: {{ $run['paths']['busy'] }}
                @endif
            </div>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-900">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('plan.import.handle') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block font-semibold">Canvas .ics (upload)</label>
            <input type="file" name="canvas_ics" class="block w-full">
        </div>

        <div>
            <label class="block font-semibold">Canvas .ics (URL)</label>
            <input type="url" name="canvas_url" class="block w-full border rounded p-2" placeholder="https://...">
        </div>

        <div>
            <label class="block font-semibold">Busy .ics (optional upload)</label>
            <input type="file" name="busy_ics" class="block w-full">
        </div>

        <hr class="my-6">

        <h2 class="text-xl font-bold">Settings</h2>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">Horizon (days)</label>
                <input type="number" name="horizon" value="{{ old('horizon', $run['settings']['horizon'] ?? 30) }}"
                       class="block w-full border rounded p-2">
            </div>

            <div>
                <label class="block font-semibold">Busy weight</label>
                <input type="number" step="0.1" name="busy_weight" value="{{ old('busy_weight', $run['settings']['busy_weight'] ?? 1) }}"
                       class="block w-full border rounded p-2">
            </div>

            <div>
                <label class="block font-semibold">Soft cap (events/day)</label>
                <input type="number" name="soft_cap" value="{{ old('soft_cap', $run['settings']['soft_cap'] ?? 4) }}"
                       class="block w-full border rounded p-2">
            </div>

            <div>
                <label class="block font-semibold">Hard cap (events/day)</label>
                <input type="number" name="hard_cap" value="{{ old('hard_cap', $run['settings']['hard_cap'] ?? 5) }}"
                       class="block w-full border rounded p-2">
            </div>
        </div>

        <div class="mt-4">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="skip_weekends" value="1"
                       @checked(old('skip_weekends', $run['settings']['skip_weekends'] ?? false))>
                <span class="font-semibold">Skip weekends</span>
            </label>
        </div>

        <button class="px-4 py-2 rounded bg-black text-white" type="submit">
            Save Import
        </button>
    </form>

    <form method="POST" action="{{ route('plan.generate') }}" class="mt-6">
        @csrf
        <button class="px-4 py-2 rounded bg-blue-600 text-white" type="submit">
            Generate Plan (stub)
        </button>
    </form>
</div>
@endsection
