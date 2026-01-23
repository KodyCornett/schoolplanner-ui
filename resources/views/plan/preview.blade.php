@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Preview Your Study Plan</h1>

    @if(session('status'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-900">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-900">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <p class="text-gray-600 mb-6">
        Click on any work block to edit its date, time, or duration. Changes will automatically redistribute effort across other blocks for the same assignment.
    </p>

    <div id="preview-calendar">
        <!-- Calendar will be rendered here by JavaScript -->
        <div class="flex items-center justify-center h-64">
            <div class="text-gray-500">Loading preview...</div>
        </div>
    </div>
</div>

@vite(['resources/js/preview/calendar.js'])
@endsection
