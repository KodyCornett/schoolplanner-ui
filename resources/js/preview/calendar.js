/**
 * Calendar grid component for preview
 */

import { fetchPreviewData, updateBlock, deleteBlock, finalize } from './api.js';

// Color palette for assignments (consistent colors per assignment)
const COLORS = [
    { bg: 'bg-blue-100', border: 'border-blue-300', text: 'text-blue-800' },
    { bg: 'bg-green-100', border: 'border-green-300', text: 'text-green-800' },
    { bg: 'bg-purple-100', border: 'border-purple-300', text: 'text-purple-800' },
    { bg: 'bg-orange-100', border: 'border-orange-300', text: 'text-orange-800' },
    { bg: 'bg-pink-100', border: 'border-pink-300', text: 'text-pink-800' },
    { bg: 'bg-teal-100', border: 'border-teal-300', text: 'text-teal-800' },
    { bg: 'bg-indigo-100', border: 'border-indigo-300', text: 'text-indigo-800' },
    { bg: 'bg-amber-100', border: 'border-amber-300', text: 'text-amber-800' },
];

class PreviewCalendar {
    constructor(containerEl) {
        this.container = containerEl;
        this.data = null;
        this.weekOffset = 0; // 0 = current week, 1 = next week, etc.
        this.assignmentColors = new Map();
        this.selectedBlock = null;
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

    getWeekDates() {
        const today = new Date();
        const dayOfWeek = today.getDay(); // 0 = Sunday
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - dayOfWeek + (this.weekOffset * 7));

        const dates = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            dates.push(date);
        }
        return dates;
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    formatDisplayDate(date) {
        return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }

    getBlocksForDate(dateStr) {
        return (this.data?.work_blocks || []).filter(block => block.date === dateStr);
    }

    getAssignment(assignmentId) {
        return (this.data?.assignments || []).find(a => a.id === assignmentId);
    }

    showLoading() {
        this.container.innerHTML = `
            <div class="flex items-center justify-center h-64">
                <div class="text-gray-500">Loading preview...</div>
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

    render() {
        const weekDates = this.getWeekDates();
        const skipWeekends = this.data?.settings?.skip_weekends ?? false;
        const displayDates = skipWeekends
            ? weekDates.filter(d => d.getDay() !== 0 && d.getDay() !== 6)
            : weekDates;

        const weekStart = this.formatDisplayDate(weekDates[0]);
        const weekEnd = this.formatDisplayDate(weekDates[6]);

        this.container.innerHTML = `
            <div class="mb-6">
                <!-- Week Navigation -->
                <div class="flex items-center justify-between mb-4">
                    <button id="prev-week" class="px-3 py-1 rounded border hover:bg-gray-100">
                        &larr; Previous
                    </button>
                    <h2 class="text-lg font-semibold">${weekStart} - ${weekEnd}</h2>
                    <button id="next-week" class="px-3 py-1 rounded border hover:bg-gray-100">
                        Next &rarr;
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-${displayDates.length} gap-2">
                    ${displayDates.map(date => this.renderDayColumn(date)).join('')}
                </div>

                <!-- Assignment Legend -->
                <div class="mt-6 p-4 bg-gray-50 rounded">
                    <h3 class="font-semibold mb-2">Assignments</h3>
                    <div class="flex flex-wrap gap-2">
                        ${this.renderAssignmentLegend()}
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex gap-3">
                    <button id="finalize-btn" class="px-4 py-2 rounded bg-black text-white hover:bg-gray-800">
                        Generate Calendar
                    </button>
                    <a href="/plan/import" class="px-4 py-2 rounded border hover:bg-gray-100">
                        Back to Import
                    </a>
                </div>
            </div>

            <!-- Block Editor Modal (hidden by default) -->
            <div id="block-editor-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
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
                            <input type="date" id="edit-date" class="w-full border rounded p-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="edit-time">Start Time</label>
                            <input type="time" id="edit-time" class="w-full border rounded p-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="edit-duration">
                                Duration: <span id="duration-display">60</span> minutes
                            </label>
                            <input type="range" id="edit-duration" min="15" max="240" step="15" value="60"
                                class="w-full">
                        </div>

                        <div class="flex gap-2 mt-6">
                            <button type="submit" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                                Save
                            </button>
                            <button type="button" id="delete-block-btn" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                                Delete
                            </button>
                            <button type="button" id="cancel-edit-btn" class="px-4 py-2 rounded border hover:bg-gray-100">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        this.attachEventListeners();
    }

    renderDayColumn(date) {
        const dateStr = this.formatDate(date);
        const blocks = this.getBlocksForDate(dateStr);
        const isToday = this.formatDate(new Date()) === dateStr;
        const isWeekend = date.getDay() === 0 || date.getDay() === 6;

        return `
            <div class="border rounded ${isToday ? 'border-blue-500 border-2' : ''} ${isWeekend ? 'bg-gray-50' : ''}">
                <div class="p-2 border-b bg-gray-100 text-center">
                    <div class="font-medium">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                    <div class="text-sm text-gray-600">${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</div>
                </div>
                <div class="p-2 min-h-32 space-y-2">
                    ${blocks.length === 0 ? '<div class="text-gray-400 text-sm text-center">No tasks</div>' : ''}
                    ${blocks.map(block => this.renderBlock(block)).join('')}
                </div>
            </div>
        `;
    }

    renderBlock(block) {
        const assignment = this.getAssignment(block.assignment_id);
        const colors = this.assignmentColors.get(block.assignment_id) || COLORS[0];
        const anchoredIcon = block.is_anchored ? '<span class="ml-1" title="Edited">&#128274;</span>' : '';

        return `
            <div class="block-card p-2 rounded border cursor-pointer hover:shadow ${colors.bg} ${colors.border}"
                 data-block-id="${block.id}">
                <div class="text-xs font-medium ${colors.text} truncate">
                    ${assignment?.title || 'Unknown'}${anchoredIcon}
                </div>
                <div class="text-xs text-gray-600 truncate">${block.label}</div>
                <div class="text-xs text-gray-500 mt-1">
                    ${block.start_time} (${block.duration_minutes}m)
                </div>
            </div>
        `;
    }

    renderAssignmentLegend() {
        return (this.data?.assignments || []).map(assignment => {
            const colors = this.assignmentColors.get(assignment.id) || COLORS[0];
            const blockCount = (this.data?.work_blocks || []).filter(b => b.assignment_id === assignment.id).length;

            return `
                <div class="flex items-center gap-2 px-2 py-1 rounded ${colors.bg} ${colors.border} border">
                    <span class="text-sm ${colors.text}">${assignment.title}</span>
                    <span class="text-xs text-gray-500">(${blockCount} blocks)</span>
                </div>
            `;
        }).join('');
    }

    attachEventListeners() {
        // Week navigation
        document.getElementById('prev-week')?.addEventListener('click', () => {
            this.weekOffset--;
            this.render();
        });

        document.getElementById('next-week')?.addEventListener('click', () => {
            this.weekOffset++;
            this.render();
        });

        // Block clicks
        this.container.querySelectorAll('.block-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const blockId = e.currentTarget.dataset.blockId;
                this.openBlockEditor(blockId);
            });
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

        // Close modal on backdrop click
        document.getElementById('block-editor-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'block-editor-modal') {
                this.closeBlockEditor();
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
        document.getElementById('block-editor-modal').classList.add('hidden');
        document.getElementById('block-editor-modal').classList.remove('flex');
        this.selectedBlock = null;
    }

    async handleBlockSave(e) {
        e.preventDefault();

        const blockId = document.getElementById('edit-block-id').value;
        const data = {
            date: document.getElementById('edit-date').value,
            start_time: document.getElementById('edit-time').value,
            duration_minutes: parseInt(document.getElementById('edit-duration').value, 10),
        };

        try {
            this.data = await updateBlock(blockId, data);
            this.closeBlockEditor();
            this.render();
        } catch (error) {
            alert('Failed to save: ' + error.message);
        }
    }

    async handleBlockDelete() {
        if (!this.selectedBlock) return;

        if (!confirm('Delete this work block? Its effort will be redistributed to other blocks.')) {
            return;
        }

        try {
            this.data = await deleteBlock(this.selectedBlock.id);
            this.closeBlockEditor();
            this.render();
        } catch (error) {
            alert('Failed to delete: ' + error.message);
        }
    }

    async handleFinalize() {
        try {
            const result = await finalize();
            if (result.download_url) {
                window.location.href = result.download_url;
            }
        } catch (error) {
            alert('Failed to generate calendar: ' + error.message);
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
