@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    {{-- Hero Section --}}
    <div class="text-center mb-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">
            Turn your Canvas calendar into a study plan
        </h1>
        <p class="text-lg text-gray-500">
            Import your assignments, generate a schedule, then edit and export
        </p>
    </div>

    {{-- Error Messages --}}
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
            <ul class="list-disc pl-5 text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('plan.import.handle') }}" enctype="multipart/form-data" id="import-form">
        @csrf

        {{-- Canvas ICS Drop Zone --}}
        <div class="mb-6">
            <div id="canvas-drop-zone"
                 class="border-2 border-dashed border-gray-300 rounded-xl p-8 sm:p-10 text-center cursor-pointer transition-colors hover:border-gray-400 hover:bg-gray-50"
                 onclick="document.getElementById('canvas-file-input').click()">
                <div class="flex flex-col items-center gap-3">
                    <div id="canvas-icon" class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <p id="canvas-text" class="text-gray-700 font-medium">
                            Drag & drop your Canvas .ics file
                        </p>
                        <p class="text-sm text-gray-500 mt-1">or click to browse</p>
                    </div>
                </div>
            </div>
            <input type="file" name="canvas_ics" id="canvas-file-input" accept=".ics" class="hidden">
        </div>

        {{-- URL Alternative --}}
        <div class="relative mb-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-3 bg-gray-50 text-gray-500">or paste URL</span>
            </div>
        </div>

        <div class="mb-8">
            <input type="url"
                   name="canvas_url"
                   id="canvas-url-input"
                   class="block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                   placeholder="https://canvas.instructure.com/feeds/calendars/...">
        </div>

        {{-- Planning Horizon Section --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <label for="horizon" class="text-sm font-medium text-gray-700">Planning Horizon</label>
                @if(!$isPro)
                    <a href="{{ route('billing.pricing') }}" class="text-xs text-blue-600 hover:text-blue-700">
                        Upgrade for 30 days
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <select name="horizon" id="horizon"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="7" {{ old('horizon', $maxHorizon) == 7 ? 'selected' : '' }}>7 days</option>
                    <option value="14" {{ old('horizon', $maxHorizon) == 14 ? 'selected' : '' }}>14 days</option>
                    @if($isPro)
                        <option value="21" {{ old('horizon', $maxHorizon) == 21 ? 'selected' : '' }}>21 days</option>
                        <option value="30" {{ old('horizon', $maxHorizon) == 30 ? 'selected' : '' }}>30 days</option>
                    @endif
                </select>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                @if($isPro)
                    Pro plan: Plan up to 30 days ahead
                @else
                    Free plan: Plan up to 14 days ahead.
                    <a href="{{ route('billing.pricing') }}" class="text-blue-600 hover:underline">Upgrade to Pro</a> for 30-day planning.
                @endif
            </p>
        </div>

        {{-- Busy Calendar Section --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <h2 class="text-sm font-medium text-gray-700">Add Busy Times</h2>
                <span class="text-xs text-gray-400">(optional)</span>
            </div>
            <div id="busy-drop-zone"
                 class="border-2 border-dashed border-gray-200 rounded-lg p-5 text-center cursor-pointer transition-colors hover:border-gray-300 hover:bg-gray-50"
                 onclick="document.getElementById('busy-file-input').click()">
                <div class="flex items-center justify-center gap-2">
                    <svg id="busy-icon" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span id="busy-text" class="text-sm text-gray-500">Drop busy calendar .ics or click to browse</span>
                </div>
            </div>
            <input type="file" name="busy_ics" id="busy-file-input" accept=".ics" class="hidden">
        </div>

        {{-- Generate Button --}}
        <button type="submit"
                id="generate-btn"
                class="w-full py-3 px-6 rounded-lg bg-blue-600 text-white font-medium text-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
            Generate Study Plan
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Canvas drop zone
    const canvasDropZone = document.getElementById('canvas-drop-zone');
    const canvasFileInput = document.getElementById('canvas-file-input');
    const canvasUrlInput = document.getElementById('canvas-url-input');
    const canvasIcon = document.getElementById('canvas-icon');
    const canvasText = document.getElementById('canvas-text');

    // Busy drop zone
    const busyDropZone = document.getElementById('busy-drop-zone');
    const busyFileInput = document.getElementById('busy-file-input');
    const busyIcon = document.getElementById('busy-icon');
    const busyText = document.getElementById('busy-text');

    function setupDropZone(dropZone, fileInput, iconEl, textEl, label) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight on drag
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
                dropZone.classList.remove('border-gray-300', 'border-gray-200');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                dropZone.classList.add(label === 'Canvas' ? 'border-gray-300' : 'border-gray-200');
            }, false);
        });

        // Handle drop
        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.ics')) {
                fileInput.files = files;
                showFileSelected(iconEl, textEl, files[0].name, label);
            }
        }, false);

        // Handle file input change
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                showFileSelected(iconEl, textEl, fileInput.files[0].name, label);
            }
        });
    }

    function showFileSelected(iconEl, textEl, filename, label) {
        if (label === 'Canvas') {
            iconEl.innerHTML = '<svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
            iconEl.classList.remove('bg-gray-100');
            iconEl.classList.add('bg-green-100');
        } else {
            iconEl.outerHTML = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
        }
        textEl.textContent = filename;
        textEl.classList.add('text-gray-900');
    }

    setupDropZone(canvasDropZone, canvasFileInput, canvasIcon, canvasText, 'Canvas');
    setupDropZone(busyDropZone, busyFileInput, busyIcon, busyText, 'Busy');

    // Clear file when URL is entered
    canvasUrlInput.addEventListener('input', () => {
        if (canvasUrlInput.value.trim()) {
            canvasFileInput.value = '';
            canvasIcon.innerHTML = '<svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>';
            canvasIcon.classList.remove('bg-green-100');
            canvasIcon.classList.add('bg-gray-100');
            canvasText.textContent = 'Drag & drop your Canvas .ics file';
            canvasText.classList.remove('text-gray-900');
        }
    });
});
</script>
@endsection
