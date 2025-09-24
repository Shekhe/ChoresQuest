// This file contains shared utility and helper functions.

import { apiRequest } from './api.js';
import { showMessage } from './ui.js';

export function formatDate(dateString) {
    if (!dateString) return 'N/A';
    // Add T00:00:00 to ensure the date is parsed in the local timezone, not UTC
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

export function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    const date = new Date(dateTimeString);
    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function getTodayDateString() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function isTaskOverdue(task) {
    // A task is overdue if:
    // 1. Its status is 'active'
    // 2. Its due_date is in the past relative to today's date (local time)
    if (task.status === 'completed' || !task.due_date) return false;
    
    const today = getTodayDateString(); // Get today's date as a YYYY-MM-DD string
    return task.due_date < today;
}

export async function uploadImage(fileInput) {
    if (!fileInput.files || fileInput.files.length === 0) {
        return null; // No file selected
    }
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('imageFile', file);

    try {
        const uploadData = await apiRequest('upload.php', 'POST', formData);
        if (uploadData.success && uploadData.url) {
            return uploadData.url; // Return the URL of the uploaded image
        } else {
            showMessage("Upload Failed", uploadData.message || "Could not upload image.", "error");
            return null;
        }
    } catch (error) {
        // Error is shown by apiRequest
        return null;
    }
}

// --- NEW: Security function to prevent XSS ---
/**
 * Escapes HTML special characters in a string to prevent XSS.
 * @param {string} str The string to escape.
 * @returns {string} The escaped string.
 */
export function escapeHTML(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Converts a comma-separated string of day numbers (1=Mon, 7=Sun) into readable day abbreviations.
 * @param {string} dayNumbers A comma-separated string of day numbers.
 * @returns {string} Abbreviated day names (e.g., "Mon, Wed, Fri").
 */
export function formatDays(dayNumbers) {
    if (!dayNumbers) return '';
    const daysMap = {
        '1': 'Mon', '2': 'Tue', '3': 'Wed', '4': 'Thu', '5': 'Fri', '6': 'Sat', '7': 'Sun'
    };
    const daysArray = dayNumbers.split(',').map(dayNum => daysMap[dayNum.trim()]).filter(Boolean);
    return daysArray.join(', ');
}