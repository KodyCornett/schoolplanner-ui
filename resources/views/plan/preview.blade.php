@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 sm:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Preview Your Study Plan</h1>
        <p class="text-gray-600 mt-1">
            Click on any work block to edit its date, time, or duration. Changes will automatically redistribute effort across other blocks for the same assignment.
        </p>
    </div>

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

    <div id="preview-calendar">
        <!-- Calendar will be rendered here by JavaScript -->
        <div class="flex items-center justify-center h-64">
            <div class="flex flex-col items-center gap-3">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                <div class="text-gray-500">Loading preview...</div>
            </div>
        </div>
    </div>
</div>

@vite(['resources/js/preview/calendar.js'])
@endsection
