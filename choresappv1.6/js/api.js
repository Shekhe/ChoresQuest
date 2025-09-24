// This file contains the logic for making API requests to the backend.

import { API_BASE_URL } from './config.js';
import { showMessage } from './ui.js';

/**
 * A reusable function for making API requests. Handles both JSON and FormData.
 * @param {string} endpoint The API endpoint to call (e.g., 'auth.php?action=login').
 * @param {string} method The HTTP method (e.g., 'GET', 'POST').
 * @param {object|FormData} body The data to send in the request body.
 * @param {object} options Additional options for the request.
 * @returns {Promise<any>} A promise that resolves with the JSON response from the API.
 */
export async function apiRequest(endpoint, method = 'GET', body = null, options = {}) {
    const url = API_BASE_URL + endpoint;
    console.log(`API Request: ${method} ${url}`, body);

    const isFormData = body instanceof FormData;

    const fetchOptions = {
        method: method,
        headers: {}
    };

    if (isFormData) {
        fetchOptions.body = body;
    } else if (body) {
        fetchOptions.headers['Content-Type'] = 'application/json';
        fetchOptions.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(url, fetchOptions);
        const responseText = await response.text();
        console.log(`API Response Text (${response.status} for ${url}):`, responseText);

        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { message: responseText || `HTTP error! Status: ${response.status}` };
            }
            const errorMessage = errorData?.message || response.statusText || `HTTP error! Status: ${response.status}`;
            console.error('Error:', errorMessage, errorData);

            let showErrorModal = true;
            if (options.suppressModalForErrorMessages && options.suppressModalForErrorMessages.includes(errorMessage)) {
                showErrorModal = false;
            }
            if (showErrorModal) {
                showMessage("Error", errorMessage, "error");
            }
            throw new Error(errorMessage);
        }

        try {
            const jsonData = JSON.parse(responseText);
            console.log('API Response JSON:', jsonData);
            return jsonData;
        } catch (e) {
            console.error('API JSON Parse Error:', e, responseText);
            throw new Error('Failed to parse server response as JSON.');
        }

    } catch (error) {
        console.error('API Fetch/Network Error:', url, error);
        let showErrorModalInCatch = true;
        if (options.suppressModalForErrorMessages && options.suppressModalForErrorMessages.includes(error.message)) {
            showErrorModalInCatch = false;
        }
        if (showErrorModalInCatch && (!options.suppressModalForErrorMessages || !options.suppressModalForErrorMessages.some(msg => error.message.includes(msg)))) {
            showMessage("Error", error.message || "A network error occurred. Check console.", "error");
        }
        throw error;
    }
}
