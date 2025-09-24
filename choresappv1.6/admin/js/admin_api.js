// admin/js/admin_api.js - API request functions for the Admin Panel

// Assuming API_BASE_URL is relative to the admin/ directory,
// it needs to point to ../../backend_chores_quest/api/ for the main app APIs
// OR to ./api/ for admin-specific APIs.
// For admin APIs, it will be relative to admin/index.php.
// Let's define it here for clarity for admin specific API calls.
const ADMIN_API_BASE_URL = 'api/'; // This will point to admin/api/

/**
 * A reusable function for making API requests to the Admin backend.
 * @param {string} endpoint The API endpoint to call (e.g., 'auth.php?action=login').
 * @param {string} method The HTTP method (e.g., 'GET', 'POST').
 * @param {object|FormData} body The data to send in the request body.
 * @returns {Promise<any>} A promise that resolves with the JSON response from the API.
 */
export async function adminApiRequest(endpoint, method = 'GET', body = null) {
    const url = ADMIN_API_BASE_URL + endpoint;
    console.log(`Admin API Request: ${method} ${url}`, body);

    const fetchOptions = {
        method: method,
        headers: {}
    };

    if (body instanceof FormData) {
        fetchOptions.body = body;
    } else if (body) {
        fetchOptions.headers['Content-Type'] = 'application/json';
        fetchOptions.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(url, fetchOptions);
        const responseText = await response.text();
        console.log(`Admin API Response Text (${response.status} for ${url}):`, responseText);

        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { message: responseText || `HTTP error! Status: ${response.status}` };
            }
            const errorMessage = errorData?.message || response.statusText || `HTTP error! Status: ${response.status}`;
            console.error('Admin API Error:', errorMessage, errorData);
            throw new Error(errorMessage); // Propagate error for handling in calling function
        }

        try {
            const jsonData = JSON.parse(responseText);
            console.log('Admin API Response JSON:', jsonData);
            return jsonData;
        } catch (e) {
            console.error('Admin API JSON Parse Error:', e, responseText);
            throw new Error('Failed to parse server response as JSON.');
        }

    } catch (error) {
        console.error('Admin API Fetch/Network Error:', url, error);
        throw error; // Propagate network errors
    }
}