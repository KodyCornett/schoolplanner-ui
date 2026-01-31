/**
 * API client for preview endpoints
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': csrfToken,
};

export async function fetchPreviewData() {
    const response = await fetch('/plan/preview/data', {
        method: 'GET',
        headers,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch preview data: ${response.status}`);
    }

    return response.json();
}

export async function updateBlock(blockId, data) {
    const response = await fetch(`/plan/preview/blocks/${blockId}`, {
        method: 'PUT',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });

    if (!response.ok) {
        throw new Error(`Failed to update block: ${response.status}`);
    }

    return response.json();
}

export async function deleteBlock(blockId) {
    const response = await fetch(`/plan/preview/blocks/${blockId}`, {
        method: 'DELETE',
        headers,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to delete block: ${response.status}`);
    }

    return response.json();
}

export async function updateAssignmentSettings(assignmentId, settings) {
    const response = await fetch(`/plan/preview/assignments/${assignmentId}/settings`, {
        method: 'PUT',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(settings),
    });

    if (!response.ok) {
        throw new Error(`Failed to update assignment: ${response.status}`);
    }

    return response.json();
}

export async function createBlock(assignmentId, data) {
    const response = await fetch(`/plan/preview/assignments/${assignmentId}/blocks`, {
        method: 'POST',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });

    if (!response.ok) {
        throw new Error(`Failed to create block: ${response.status}`);
    }

    return response.json();
}

export async function regenerate() {
    const response = await fetch('/plan/preview/regenerate', {
        method: 'POST',
        headers,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to regenerate: ${response.status}`);
    }

    return response.json();
}

export async function finalize() {
    const response = await fetch('/plan/preview/finalize', {
        method: 'POST',
        headers,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to finalize: ${response.status}`);
    }

    return response.json();
}
