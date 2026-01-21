@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Preview (MVP)</h1>

    @if(session('status'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-900">
            {{ session('status') }}
        </div>
    @endif

    <p class="text-gray-700">TODO: Render agenda from plan_events.json.</p>

    <div class="mt-6 flex gap-3">
        <a class="px-4 py-2 rounded bg-black text-white" href="{{ route('plan.download') }}">
            Download StudyPlan.ics (stub)
        </a>
        <a class="px-4 py-2 rounded border" href="{{ route('plan.import') }}">
            Back to Import
        </a>
    </div>
</div>
@endsection
