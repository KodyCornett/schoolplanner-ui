/**
 * Calendar grid component for preview
 * Supports week and month views with interactive block editing
 */

import { fetchPreviewData, updateBlock, deleteBlock, updateAssignmentSettings, createBlock, regenerate, finalize } from './api.js';

// Grid column classes - explicit for Tailwind JIT compilation
const GRID_COLS = {
    1: 'sm:grid-cols-1',
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-3',
    4: 'sm:grid-cols-4',
    5: 'sm:grid-cols-5',
    6: 'sm:grid-cols-6',
    7: 'sm:grid-cols-7',
};

// Color palette for assignments (consistent colors per assignment)
const COLORS = [
    { bg: 'bg-blue-100', border: 'border-blue-300', text: 'text-blue-800', hex: '#3b82f6' },
    { bg: 'bg-green-100', border: 'border-green-300', text: 'text-green-800', hex: '#22c55e' },
    { bg: 'bg-purple-100', border: 'border-purple-300', text: 'text-purple-800', hex: '#a855f7' },
    { bg: 'bg-orange-100', border: 'border-orange-300', text: 'text-orange-800', hex: '#f97316' },
    { bg: 'bg-pink-100', border: 'border-pink-300', text: 'text-pink-800', hex: '#ec4899' },
    { bg: 'bg-teal-100', border: 'border-teal-300', text: 'text-teal-800', hex: '#14b8a6' },
    { bg: 'bg-indigo-100', border: 'border-indigo-300', text: 'text-indigo-800', hex: '#6366f1' },
    { bg: 'bg-amber-100', border: 'border-amber-300', text: 'text-amber-800', hex: '#f59e0b' },
];

class PreviewCalendar {
    constructor(containerEl) {
        this.container = containerEl;
        this.data = null;
        this.weekOffset = 0; // 0 = current week, 1 = next week, etc.
        this.viewMode = 'week'; // 'week' or 'month'
        this.assignmentColors = new Map();
        this.selectedBlock = null;
        this.selectedAssignmentForNewBlock = null; // For creating new blocks
        this.isLoading = false;
    }

    async init() {
        this.showLoading();
        try {
            this.data = await fetchPreviewData();
            this.assignColors();
            this.render();
        } catch (error) {
            this.showError(error.message);
        }
    }

    assignColors() {
        const assignments = this.data?.assignments || [];
        assignments.forEach((assignment, index) => {
            this.assignmentColors.set(assignment.id, COLORS[index % COLORS.length]);
        });
    }

    /**
     * Get dates for a single week starting from the current offset.
     */
    getWeekDates(additionalOffset = 0) {
        const today = new Date();
        const dayOfWeek = today.getDay(); // 0 = Sunday
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - dayOfWeek + ((this.weekOffset + additionalOffset) * 7));

        const dates = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            dates.push(date);
        }
        return dates;
    }

    /**
     * Get dates for 4 weeks (month view).
     * Returns array of 4 week arrays.
     */
    getMonthWeeks() {
        const weeks = [];
        for (let w = 0; w < 4; w++) {
            weeks.push(this.getWeekDates(w));
        }
        return weeks;
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    formatDisplayDate(date) {
        return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }

    formatMonthYear(date) {
        return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    getBlocksForDate(dateStr) {
        return (this.data?.work_blocks || []).filter(block => block.date === dateStr);
    }

    getBusyTimesForDate(dateStr) {
        return (this.data?.busy_times || []).filter(busy => busy.date === dateStr);
    }

    getDueDatesForDate(dateStr) {
        return (this.data?.assignments || []).filter(a => a.due_date === dateStr);
    }

    getAssignment(assignmentId) {
        return (this.data?.assignments || []).find(a => a.id === assignmentId);
    }

    getBlock(blockId) {
        return (this.data?.work_blocks || []).find(b => b.id === blockId);
    }

    showLoading() {
        this.container.innerHTML = `
            <div class="flex items-center justify-center h-64">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                    <div class="text-gray-500">Loading preview...</div>
                </div>
            </div>
        `;
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="bg-red-100 text-red-800 p-4 rounded">
                <strong>Error:</strong> ${message}
            </div>
        `;
    }

    setLoadingState(loading) {
        this.isLoading = loading;
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.toggle('hidden', !loading);
        }
    }

    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-red-600' : 'bg-green-600';
        const icon = type === 'error' ? '✕' : '✓';

        toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-72 max-w-sm animate-slide-in`;
        toast.innerHTML = `
            <span class="text-lg">${icon}</span>
            <span class="flex-1">${message}</span>
            <button class="toast-close text-white/80 hover:text-white text-xl leading-none">&times;</button>
        `;

        container.appendChild(toast);

        // Auto-dismiss after 4 seconds
        const timeoutId = setTimeout(() => toast.remove(), 4000);

        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            clearTimeout(timeoutId);
            toast.remove();
        });
    }

    render() {
        const weekDates = this.getWeekDates();
        const skipWeekends = this.data?.settings?.skip_weekends ?? false;

        // For display, filter weekends if setting is enabled
        const filterWeekends = (dates) => skipWeekends
            ? dates.filter(d => d.getDay() !== 0 && d.getDay() !== 6)
            : dates;

        const weekStart = this.formatDisplayDate(weekDates[0]);
        const weekEnd = this.formatDisplayDate(weekDates[6]);

        // Calculate date range display based on view mode
        let dateRangeDisplay;
        if (this.viewMode === 'month') {
            const monthWeeks = this.getMonthWeeks();
            const firstDate = monthWeeks[0][0];
            const lastDate = monthWeeks[3][6];
            dateRangeDisplay = `${this.formatDisplayDate(firstDate)} - ${this.formatDisplayDate(lastDate)}`;
        } else {
            dateRangeDisplay = `${weekStart} - ${weekEnd}`;
        }

        const displayDates = filterWeekends(weekDates);
        const colCount = displayDates.length;

        this.container.innerHTML = `
            <!-- Toast Container -->
            <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

            <!-- Toast Animation Styles -->
            <style>
                @keyframes slide-in {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                .animate-slide-in { animation: slide-in 0.2s ease-out; }
            </style>

            <!-- Loading Overlay -->
            <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-75 z-40 hidden items-center justify-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                    <div class="text-gray-600">Updating...</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Main Calendar Area -->
                <div class="lg:col-span-8">
                    <!-- Navigation Bar -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <button id="prev-period" class="px-3 py-1.5 rounded border hover:bg-gray-100 text-sm">
                                &larr; Previous
                            </button>
                            <button id="next-period" class="px-3 py-1.5 rounded border hover:bg-gray-100 text-sm">
                                Next &rarr;
                            </button>
                            <button id="today-btn" class="px-3 py-1.5 rounded border hover:bg-gray-100 text-sm">
                                Today
                            </button>
                        </div>

                        <h2 class="text-lg font-semibold order-first sm:order-none">${dateRangeDisplay}</h2>

                        <!-- View Toggle -->
                        <div class="flex rounded border overflow-hidden">
                            <button id="view-week" class="px-3 py-1.5 text-sm ${this.viewMode === 'week' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">
                                Week
                            </button>
                            <button id="view-month" class="px-3 py-1.5 text-sm border-l ${this.viewMode === 'month' ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">
                                Month
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    ${this.viewMode === 'week' ? this.renderWeekView(displayDates) : this.renderMonthView(filterWeekends)}

                    <!-- Actions -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button id="finalize-btn" class="px-4 py-2 rounded bg-black text-white hover:bg-gray-800">
                            Generate Calendar
                        </button>
                        <button id="regenerate-btn" class="px-4 py-2 rounded border hover:bg-gray-100">
                            Reset Changes
                        </button>
                        <a href="/plan/import" class="px-4 py-2 rounded border hover:bg-gray-100">
                            Back to Import
                        </a>
                    </div>
                </div>

                <!-- Assignment Sidebar -->
                <div class="lg:col-span-4">
                    <div class="lg:sticky lg:top-6">
                        ${this.renderAssignmentPanel()}
                    </div>
                </div>
            </div>

            <!-- Block Editor Modal -->
            ${this.renderBlockEditorModal()}

            <!-- Create Block Modal -->
            ${this.renderCreateBlockModal()}
        `;

        this.attachEventListeners();
    }

    renderWeekView(displayDates) {
        const gridColsClass = GRID_COLS[displayDates.length] || 'sm:grid-cols-7';
        return `
            <div class="grid grid-cols-1 ${gridColsClass} gap-2">
                ${displayDates.map(date => this.renderDayColumn(date)).join('')}
            </div>
        `;
    }

    renderMonthView(filterWeekends) {
        const monthWeeks = this.getMonthWeeks();

        return `
            <div class="space-y-4">
                ${monthWeeks.map((weekDates, weekIndex) => {
                    const filteredDates = filterWeekends(weekDates);
                    const weekLabel = this.formatDisplayDate(weekDates[0]);
                    const gridColsClass = GRID_COLS[filteredDates.length] || 'sm:grid-cols-7';
                    return `
                        <div>
                            <div class="text-sm font-medium text-gray-500 mb-2">Week of ${weekLabel}</div>
                            <div class="grid grid-cols-1 ${gridColsClass} gap-2">
                                ${filteredDates.map(date => this.renderDayColumn(date, true)).join('')}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    renderDayColumn(date, compact = false) {
        const dateStr = this.formatDate(date);
        const blocks = this.getBlocksForDate(dateStr);
        const busyTimes = this.getBusyTimesForDate(dateStr);
        const dueDates = this.getDueDatesForDate(dateStr);
        const isToday = this.formatDate(new Date()) === dateStr;
        const isWeekend = date.getDay() === 0 || date.getDay() === 6;
        const minHeight = compact ? 'min-h-24' : 'min-h-32';
        const hasContent = dueDates.length > 0 || busyTimes.length > 0 || blocks.length > 0;

        return `
            <div class="day-column border rounded ${isToday ? 'border-blue-500 border-2 shadow-sm' : ''} ${isWeekend ? 'bg-gray-50' : ''}"
                 data-date="${dateStr}">
                <div class="p-2 border-b ${isToday ? 'bg-blue-50' : 'bg-gray-100'} text-center">
                    <div class="font-medium text-sm">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                    <div class="text-xs text-gray-600">${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</div>
                </div>
                <div class="p-2 ${minHeight} space-y-1.5">
                    ${dueDates.map(a => this.renderDueDate(a)).join('')}
                    ${busyTimes.map(busy => this.renderBusyTime(busy)).join('')}
                    ${!hasContent ? '<div class="text-gray-400 text-xs text-center py-2">No tasks</div>' : ''}
                    ${blocks.map(block => this.renderBlock(block, compact)).join('')}
                </div>
            </div>
        `;
    }

    renderBlock(block, compact = false) {
        const assignment = this.getAssignment(block.assignment_id);
        const colors = this.assignmentColors.get(block.assignment_id) || COLORS[0];
        const anchoredIcon = block.is_anchored ? '<span class="ml-1" title="Edited (anchored)">&#128274;</span>' : '';
        const padding = compact ? 'p-1.5' : 'p-2';
        const textSize = compact ? 'text-xs' : 'text-xs';

        return `
            <div class="block-card ${padding} rounded border cursor-pointer hover:shadow transition-shadow ${colors.bg} ${colors.border}"
                 data-block-id="${block.id}"
                 draggable="true">
                <div class="${textSize} font-medium ${colors.text} truncate">
                    ${assignment?.title || 'Unknown'}${anchoredIcon}
                </div>
                <div class="${textSize} text-gray-600 truncate">${block.label}</div>
                <div class="${textSize} text-gray-500 mt-0.5">
                    ${block.start_time} (${block.duration_minutes}m)
                </div>
            </div>
        `;
    }

    renderBusyTime(busy) {
        return `
            <div class="p-1.5 rounded border border-gray-300 bg-gray-200 text-xs text-gray-600">
                <div class="font-medium truncate">${busy.title || 'Busy'}</div>
                ${busy.start_time ? `<div class="text-gray-500">${busy.start_time}${busy.end_time ? ' - ' + busy.end_time : ''}</div>` : ''}
            </div>
        `;
    }

    renderDueDate(assignment) {
        const colors = this.assignmentColors.get(assignment.id) || COLORS[0];
        return `
            <div class="p-1.5 rounded border-2 border-red-400 ${colors.bg} text-xs">
                <div class="flex items-center gap-1">
                    <span class="text-red-600 font-bold">DUE</span>
                    <span class="${colors.text} font-medium truncate">${assignment.title}</span>
                </div>
            </div>
        `;
    }

    renderAssignmentPanel() {
        const assignments = this.data?.assignments || [];
        const workBlocks = this.data?.work_blocks || [];

        return `
            <div class="bg-white border rounded-lg shadow-sm">
                <div class="p-4 border-b">
                    <h3 class="font-semibold text-lg">Assignments</h3>
                    <p class="text-sm text-gray-500 mt-1">Click + to add a work block</p>
                </div>
                <div class="divide-y max-h-96 lg:max-h-[calc(100vh-200px)] overflow-y-auto">
                    ${assignments.map(assignment => {
                        const colors = this.assignmentColors.get(assignment.id) || COLORS[0];
                        const blockCount = workBlocks.filter(b => b.assignment_id === assignment.id).length;
                        const totalEffort = assignment.total_effort_minutes || 0;
                        const hours = Math.floor(totalEffort / 60);
                        const minutes = totalEffort % 60;
                        const effortDisplay = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

                        return `
                            <div class="p-3 hover:bg-gray-50">
                                <div class="flex items-start gap-3">
                                    <div class="w-3 h-3 rounded-full mt-1.5 flex-shrink-0" style="background-color: ${colors.hex}"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="font-medium text-sm truncate">${assignment.title}</div>
                                            <button class="add-block-btn flex-shrink-0 w-6 h-6 rounded-full bg-blue-600 text-white hover:bg-blue-700 flex items-center justify-center text-sm font-bold transition-colors"
                                                    data-assignment-id="${assignment.id}"
                                                    title="Add work block">
                                                +
                                            </button>
                                        </div>
                                        <div class="text-xs text-gray-500">${assignment.course || 'No course'}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            ${blockCount} blocks &bull; ${effortDisplay}
                                            ${assignment.due_date ? `&bull; Due ${assignment.due_date}` : ''}
                                        </div>

                                        <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                            <input type="checkbox"
                                                   class="assignment-due-date-toggle rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                   data-assignment-id="${assignment.id}"
                                                   ${assignment.allow_work_on_due_date ? 'checked' : ''}>
                                            <span class="text-xs text-gray-600">Allow work on due date</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>

                ${assignments.length === 0 ? '<div class="p-4 text-gray-500 text-sm">No assignments found</div>' : ''}

                <!-- Summary -->
                <div class="p-4 border-t bg-gray-50">
                    <div class="text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total assignments:</span>
                            <span class="font-medium">${assignments.length}</span>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-gray-600">Total work blocks:</span>
                            <span class="font-medium">${workBlocks.length}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderBlockEditorModal() {
        return `
            <div id="block-editor-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
                    <h3 class="text-lg font-semibold mb-4">Edit Work Block</h3>
                    <form id="block-editor-form">
                        <input type="hidden" id="edit-block-id">

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Assignment</label>
                            <div id="edit-assignment-name" class="text-gray-700"></div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Phase</label>
                            <div id="edit-block-label" class="text-gray-600 text-sm"></div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="edit-date">Date</label>
                            <input type="date" id="edit-date" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="edit-time">Start Time</label>
                            <input type="time" id="edit-time" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="edit-duration">
                                Duration: <span id="duration-display" class="font-semibold">60</span> minutes
                            </label>
                            <input type="range" id="edit-duration" min="15" max="240" step="15" value="60"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>15m</span>
                                <span>1h</span>
                                <span>2h</span>
                                <span>3h</span>
                                <span>4h</span>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-6">
                            <button type="submit" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                Save
                            </button>
                            <button type="button" id="delete-block-btn" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                                Delete
                            </button>
                            <button type="button" id="cancel-edit-btn" class="px-4 py-2 rounded border hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }

    renderCreateBlockModal() {
        // Default to today's date
        const today = new Date().toISOString().split('T')[0];

        return `
            <div id="create-block-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
                    <h3 class="text-lg font-semibold mb-4">Add Work Block</h3>
                    <form id="create-block-form">
                        <input type="hidden" id="create-assignment-id">

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Assignment</label>
                            <div id="create-assignment-name" class="text-gray-700 font-medium"></div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="create-date">Date</label>
                            <input type="date" id="create-date" value="${today}" required
                                class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="create-time">Start Time</label>
                            <input type="time" id="create-time" value="09:00" required
                                class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="create-duration">
                                Duration: <span id="create-duration-display" class="font-semibold">60</span> minutes
                            </label>
                            <input type="range" id="create-duration" min="15" max="240" step="15" value="60"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>15m</span>
                                <span>1h</span>
                                <span>2h</span>
                                <span>3h</span>
                                <span>4h</span>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-6">
                            <button type="submit" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                Add Block
                            </button>
                            <button type="button" id="cancel-create-btn" class="px-4 py-2 rounded border hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        // Period navigation
        document.getElementById('prev-period')?.addEventListener('click', () => {
            this.weekOffset -= this.viewMode === 'month' ? 4 : 1;
            this.render();
        });

        document.getElementById('next-period')?.addEventListener('click', () => {
            this.weekOffset += this.viewMode === 'month' ? 4 : 1;
            this.render();
        });

        document.getElementById('today-btn')?.addEventListener('click', () => {
            this.weekOffset = 0;
            this.render();
        });

        // View toggle
        document.getElementById('view-week')?.addEventListener('click', () => {
            if (this.viewMode !== 'week') {
                this.viewMode = 'week';
                this.render();
            }
        });

        document.getElementById('view-month')?.addEventListener('click', () => {
            if (this.viewMode !== 'month') {
                this.viewMode = 'month';
                this.render();
            }
        });

        // Block clicks
        this.container.querySelectorAll('.block-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const blockId = e.currentTarget.dataset.blockId;
                this.openBlockEditor(blockId);
            });
        });

        // Drag and drop for moving blocks between days
        this.container.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('block-card')) {
                e.dataTransfer.setData('text/plain', e.target.dataset.blockId);
                e.dataTransfer.effectAllowed = 'move';
                e.target.classList.add('opacity-50');
            }
        });

        this.container.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('block-card')) {
                e.target.classList.remove('opacity-50');
            }
        });

        this.container.addEventListener('dragover', (e) => {
            const dayColumn = e.target.closest('[data-date]');
            if (dayColumn) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            }
        });

        this.container.addEventListener('dragenter', (e) => {
            const dayColumn = e.target.closest('[data-date]');
            if (dayColumn) {
                dayColumn.classList.add('bg-blue-50');
            }
        });

        this.container.addEventListener('dragleave', (e) => {
            const dayColumn = e.target.closest('[data-date]');
            if (dayColumn && !dayColumn.contains(e.relatedTarget)) {
                dayColumn.classList.remove('bg-blue-50');
            }
        });

        this.container.addEventListener('drop', async (e) => {
            e.preventDefault();
            const dayColumn = e.target.closest('[data-date]');
            if (!dayColumn) return;

            dayColumn.classList.remove('bg-blue-50');
            const blockId = e.dataTransfer.getData('text/plain');
            const newDate = dayColumn.dataset.date;
            const block = this.getBlock(blockId);

            if (block && block.date !== newDate) {
                await this.moveBlockToDate(blockId, newDate);
            }
        });

        // Modal controls
        document.getElementById('cancel-edit-btn')?.addEventListener('click', () => this.closeBlockEditor());
        document.getElementById('block-editor-form')?.addEventListener('submit', (e) => this.handleBlockSave(e));
        document.getElementById('delete-block-btn')?.addEventListener('click', () => this.handleBlockDelete());

        // Duration slider
        document.getElementById('edit-duration')?.addEventListener('input', (e) => {
            document.getElementById('duration-display').textContent = e.target.value;
        });

        // Finalize button
        document.getElementById('finalize-btn')?.addEventListener('click', () => this.handleFinalize());

        // Regenerate button
        document.getElementById('regenerate-btn')?.addEventListener('click', () => this.handleRegenerate());

        // Assignment due date toggles
        this.container.querySelectorAll('.assignment-due-date-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const assignmentId = e.target.dataset.assignmentId;
                const allowWorkOnDueDate = e.target.checked;
                this.handleAssignmentSettingChange(assignmentId, { allow_work_on_due_date: allowWorkOnDueDate });
            });
        });

        // Add block buttons
        this.container.querySelectorAll('.add-block-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const assignmentId = e.currentTarget.dataset.assignmentId;
                this.openCreateBlockModal(assignmentId);
            });
        });

        // Create block modal controls
        document.getElementById('cancel-create-btn')?.addEventListener('click', () => this.closeCreateBlockModal());
        document.getElementById('create-block-form')?.addEventListener('submit', (e) => this.handleCreateBlock(e));

        // Create duration slider
        document.getElementById('create-duration')?.addEventListener('input', (e) => {
            document.getElementById('create-duration-display').textContent = e.target.value;
        });

        // Close modal on backdrop click
        document.getElementById('block-editor-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'block-editor-modal') {
                this.closeBlockEditor();
            }
        });

        document.getElementById('create-block-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'create-block-modal') {
                this.closeCreateBlockModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeBlockEditor();
                this.closeCreateBlockModal();
            }
        });
    }

    openBlockEditor(blockId) {
        const block = (this.data?.work_blocks || []).find(b => b.id === blockId);
        if (!block) return;

        const assignment = this.getAssignment(block.assignment_id);
        this.selectedBlock = block;

        document.getElementById('edit-block-id').value = block.id;
        document.getElementById('edit-assignment-name').textContent = assignment?.title || 'Unknown';
        document.getElementById('edit-block-label').textContent = block.label;
        document.getElementById('edit-date').value = block.date;
        document.getElementById('edit-time').value = block.start_time;
        document.getElementById('edit-duration').value = block.duration_minutes;
        document.getElementById('duration-display').textContent = block.duration_minutes;

        document.getElementById('block-editor-modal').classList.remove('hidden');
        document.getElementById('block-editor-modal').classList.add('flex');
    }

    closeBlockEditor() {
        document.getElementById('block-editor-modal')?.classList.add('hidden');
        document.getElementById('block-editor-modal')?.classList.remove('flex');
        this.selectedBlock = null;
    }

    openCreateBlockModal(assignmentId) {
        const assignment = this.getAssignment(assignmentId);
        if (!assignment) return;

        this.selectedAssignmentForNewBlock = assignmentId;

        document.getElementById('create-assignment-id').value = assignmentId;
        document.getElementById('create-assignment-name').textContent = assignment.title || 'Unknown';

        // Reset form to defaults
        document.getElementById('create-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('create-time').value = '09:00';
        document.getElementById('create-duration').value = 60;
        document.getElementById('create-duration-display').textContent = '60';

        document.getElementById('create-block-modal').classList.remove('hidden');
        document.getElementById('create-block-modal').classList.add('flex');
    }

    closeCreateBlockModal() {
        document.getElementById('create-block-modal')?.classList.add('hidden');
        document.getElementById('create-block-modal')?.classList.remove('flex');
        this.selectedAssignmentForNewBlock = null;
    }

    async moveBlockToDate(blockId, newDate) {
        const block = this.getBlock(blockId);
        if (!block) return;

        this.setLoadingState(true);
        try {
            this.data = await updateBlock(blockId, {
                date: newDate,
                start_time: block.start_time,
                duration_minutes: block.duration_minutes,
            });
            this.render();
            this.showToast('Block moved');
        } catch (error) {
            console.error('Failed to move block:', error);
            this.showToast('Failed to move block. Please try again.', 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleCreateBlock(e) {
        e.preventDefault();

        const assignmentId = document.getElementById('create-assignment-id').value;
        const data = {
            date: document.getElementById('create-date').value,
            start_time: document.getElementById('create-time').value,
            duration_minutes: parseInt(document.getElementById('create-duration').value, 10),
        };

        this.setLoadingState(true);
        try {
            this.data = await createBlock(assignmentId, data);
            this.closeCreateBlockModal();
            this.render();
            this.showToast('Block created');
        } catch (error) {
            this.showToast('Failed to create block: ' + error.message, 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleBlockSave(e) {
        e.preventDefault();

        const blockId = document.getElementById('edit-block-id').value;
        const data = {
            date: document.getElementById('edit-date').value,
            start_time: document.getElementById('edit-time').value,
            duration_minutes: parseInt(document.getElementById('edit-duration').value, 10),
        };

        this.setLoadingState(true);
        try {
            this.data = await updateBlock(blockId, data);
            this.closeBlockEditor();
            this.render();
            this.showToast('Block saved');
        } catch (error) {
            this.showToast('Failed to save: ' + error.message, 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleBlockDelete() {
        if (!this.selectedBlock) return;

        if (!confirm('Delete this work block? Its effort will be redistributed to other blocks for the same assignment.')) {
            return;
        }

        this.setLoadingState(true);
        try {
            this.data = await deleteBlock(this.selectedBlock.id);
            this.closeBlockEditor();
            this.render();
            this.showToast('Block deleted');
        } catch (error) {
            this.showToast('Failed to delete: ' + error.message, 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleAssignmentSettingChange(assignmentId, settings) {
        this.setLoadingState(true);
        try {
            this.data = await updateAssignmentSettings(assignmentId, settings);
            // Don't re-render to avoid losing scroll position - just update local data
        } catch (error) {
            this.showToast('Failed to update assignment: ' + error.message, 'error');
            this.render(); // Re-render to reset checkbox state
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleRegenerate() {
        if (!confirm('Reset all changes and regenerate the preview from the original plan? This cannot be undone.')) {
            return;
        }

        this.setLoadingState(true);
        try {
            this.data = await regenerate();
            this.assignColors();
            this.render();
            this.showToast('Plan reset to original');
        } catch (error) {
            this.showToast('Failed to regenerate: ' + error.message, 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    async handleFinalize() {
        this.setLoadingState(true);
        try {
            const result = await finalize();
            if (result.download_url) {
                window.location.href = result.download_url;
            }
        } catch (error) {
            this.showToast('Failed to generate calendar: ' + error.message, 'error');
        } finally {
            this.setLoadingState(false);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('preview-calendar');
    if (container) {
        const calendar = new PreviewCalendar(container);
        calendar.init();
    }
});

export default PreviewCalendar;
