@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Import (MVP)</h1>

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
