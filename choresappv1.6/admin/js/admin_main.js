// admin/js/admin_main.js - Main logic for the Admin Panel

import * as dom from './admin_dom.js';
import { adminApiRequest } from './admin_api.js';

let loggedInAdminUser = null;

// Helper to show/hide sections
function showAdminPage(pageElement) {
    dom.adminPages.forEach(p => {
        p.classList.remove('active');
        p.classList.add('hidden');
    });
    pageElement.classList.remove('hidden');
    pageElement.classList.add('active');
}

// NEW: Helper to show/hide admin modals
function showAdminModal(modalElement) {
    dom.adminModalOverlay.classList.remove('hidden');
    modalElement.classList.remove('hidden');
}

function hideAdminModals() {
    dom.adminModalOverlay.classList.add('hidden');
    dom.changeUsernameModal.classList.add('hidden');
    dom.resetPasswordModal.classList.add('hidden');
    dom.generateRecoveryCodeModal.classList.add('hidden');
    dom.setPinModal.classList.add('hidden');
    dom.deleteUserConfirmModal.classList.add('hidden'); // NEW: Hide deleteUserConfirmModal
}

// Function to fetch and display dashboard data
async function loadDashboardData() {
    dom.totalParentsCount.textContent = 'Loading...';
    dom.totalChildrenCount.textContent = 'Loading...';
    dom.totalTasksCount.textContent = 'Loading...';
    dom.adminUsersList.innerHTML = '<p class="text-gray-500">Loading user data...</p>';

    try {
        // Fetch overall statistics
        const statsData = await adminApiRequest('users.php?action=get_stats', 'GET');
        if (statsData.success) {
            dom.totalParentsCount.textContent = statsData.stats.total_parents;
            dom.totalChildrenCount.textContent = statsData.stats.total_children;
            dom.totalTasksCount.textContent = statsData.stats.total_tasks;
        } else {
            console.error("Failed to load stats:", statsData.message);
            dom.totalParentsCount.textContent = 'Error';
            dom.totalChildrenCount.textContent = 'Error';
            dom.totalTasksCount.textContent = 'Error';
        }

        // Fetch and display all users
        const usersData = await adminApiRequest('users.php?action=list_all', 'GET');
        if (usersData.success) {
            dom.adminUsersList.innerHTML = '';
            if (usersData.users.length === 0) {
                dom.adminUsersList.innerHTML = '<p class="text-gray-500">No users found.</p>';
            } else {
                usersData.users.forEach(user => {
                    const userDiv = document.createElement('div');
                    userDiv.className = 'bg-gray-50 p-4 rounded-md shadow-sm border border-gray-200 flex justify-between items-center';
                    userDiv.innerHTML = `
                        <div>
                            <p><strong>${user.username}</strong> (<span class="font-semibold ${user.user_type === 'admin' ? 'text-blue-600' : 'text-green-600'}">${user.user_type}</span>)</p>
                            <p class="text-sm text-gray-600">Name: ${user.name || 'N/A'}</p>
                            <p class="text-xs text-gray-500">Joined: ${new Date(user.created_at).toLocaleDateString()}</p>
                            <p class="text-xs text-gray-500">PIN Set: <span class="${user.has_pin ? 'text-green-500' : 'text-red-500'}">${user.has_pin ? 'Yes' : 'No'}</span></p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="btn-change-username text-blue-500 hover:text-blue-700 font-semibold text-sm" data-user-id="${user.id}" data-username="${user.username}"><i class="fas fa-user-edit mr-1"></i>Username</button>
                            <button class="btn-reset-password text-orange-500 hover:text-orange-700 font-semibold text-sm" data-user-id="${user.id}" data-username="${user.username}"><i class="fas fa-key mr-1"></i>Password</button>
                            <button class="btn-generate-recovery-code text-green-500 hover:text-green-700 font-semibold text-sm" data-user-id="${user.id}" data-username="${user.username}"><i class="fas fa-redo-alt mr-1"></i>Recovery</button>
                            <button class="btn-set-pin text-purple-500 hover:text-purple-700 font-semibold text-sm" data-user-id="${user.id}" data-username="${user.username}" data-has-pin="${user.has_pin}"><i class="fas fa-thumbtack mr-1"></i>PIN</button>
                            <button class="btn-delete-user text-red-500 hover:text-red-700 font-semibold text-sm" data-user-id="${user.id}" data-username="${user.username}"><i class="fas fa-trash-alt mr-1"></i>Delete</button>
                        </div>
                    `;
                    dom.adminUsersList.appendChild(userDiv);
                });

                // Add event listeners to the new buttons
                document.querySelectorAll('.btn-change-username').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        const username = e.currentTarget.dataset.username;
                        dom.changeUsernameUserId.value = userId;
                        dom.currentUsernameDisplay.textContent = username;
                        dom.newUsername.value = username; // Pre-fill with current username
                        dom.changeUsernameError.classList.add('hidden');
                        showAdminModal(dom.changeUsernameModal);
                    });
                });

                document.querySelectorAll('.btn-reset-password').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        const username = e.currentTarget.dataset.username;
                        dom.resetPasswordUserIdHidden.value = userId;
                        dom.resetPasswordUsernameDisplay.textContent = username;
                        dom.newPassword.value = '';
                        dom.confirmNewPassword.value = '';
                        dom.resetPasswordError.classList.add('hidden');
                        showAdminModal(dom.resetPasswordModal);
                    });
                });

                document.querySelectorAll('.btn-generate-recovery-code').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        const username = e.currentTarget.dataset.username;
                        dom.generateRecoveryCodeUserId.value = userId;
                        dom.recoveryCodeUsernameDisplay.textContent = username;
                        dom.generatedRecoveryCodeDisplay.textContent = 'Generating...'; // Reset text
                        dom.generateRecoveryCodeError.classList.add('hidden');
                        showAdminModal(dom.generateRecoveryCodeModal);
                        // Trigger generation immediately after showing modal
                        generateNewRecoveryCode(userId);
                    });
                });

                document.querySelectorAll('.btn-set-pin').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        const username = e.currentTarget.dataset.username;
                        const hasPin = e.currentTarget.dataset.hasPin === 'true'; // Convert string to boolean
                        
                        dom.setPinUserId.value = userId;
                        dom.setPinUsernameDisplay.textContent = username;
                        dom.newPin.value = ''; // Always clear previous pin entry
                        dom.setPinError.classList.add('hidden');
                        
                        // Customize placeholder/message based on current PIN status
                        if (hasPin) {
                            dom.newPin.placeholder = 'Enter new PIN (or leave blank to clear)';
                        } else {
                            dom.newPin.placeholder = 'Enter new 4-digit PIN';
                        }
                        
                        showAdminModal(dom.setPinModal);
                    });
                });

                // NEW: Event listener for Delete User button
                document.querySelectorAll('.btn-delete-user').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const userId = e.currentTarget.dataset.userId;
                        const username = e.currentTarget.dataset.username;

                        // Prevent admin from deleting themselves
                        if (userId == loggedInAdminUser.id) {
                            alert('You cannot delete your own admin account.');
                            return;
                        }

                        dom.deleteConfirmUserId.value = userId;
                        dom.deleteConfirmUsernameDisplay.textContent = username;
                        showAdminModal(dom.deleteUserConfirmModal);
                    });
                });
            }
        } else {
            console.error("Failed to load users:", usersData.message);
            dom.adminUsersList.innerHTML = '<p class="text-red-500">Failed to load users.</p>';
        }

    } catch (error) {
        console.error("Failed to load admin dashboard data:", error);
        dom.adminLoginError.textContent = `Failed to load dashboard data: ${error.message}. Please try again.`;
        dom.adminLoginError.classList.remove('hidden');
        dom.totalParentsCount.textContent = 'Error';
        dom.totalChildrenCount.textContent = 'Error';
        dom.totalTasksCount.textContent = 'Error';
        dom.adminUsersList.innerHTML = '<p class="text-red-500">Failed to load user data.</p>';
    }
}


// NEW: Handle Delete User Confirmation
dom.confirmDeleteUserBtn.addEventListener('click', async () => {
    const userIdToDelete = dom.deleteConfirmUserId.value;
    const usernameToDelete = dom.deleteConfirmUsernameDisplay.textContent; // For alert message

    try {
        const data = await adminApiRequest('users.php?action=delete_user', 'POST', { user_id: userIdToDelete });
        if (data.success) {
            alert(data.message); // e.g., "User 'X' and all associated data deleted successfully."
            hideAdminModals();
            loadDashboardData(); // Reload data to reflect deletion
        } else {
            alert(`Failed to delete user: ${data.message}`);
        }
    } catch (error) {
        alert(`An error occurred during deletion: ${error.message}`);
    }
});

// NEW: Handle Cancel Delete User
dom.cancelDeleteUserBtn.addEventListener('click', hideAdminModals);


// NEW: Handle Set PIN Form Submission
dom.setPinForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    dom.setPinError.classList.add('hidden');

    const userId = dom.setPinUserId.value;
    const newPinValue = dom.newPin.value.trim(); // Get value, trim whitespace

    // Basic client-side validation for PIN
    if (newPinValue !== '' && !/^\d{4}$/.test(newPinValue)) {
        dom.setPinError.textContent = 'PIN must be exactly 4 digits or left empty to clear.';
        dom.setPinError.classList.remove('hidden');
        return;
    }

    try {
        const data = await adminApiRequest('users.php?action=set_pin', 'POST', { user_id: userId, pin: newPinValue === '' ? null : newPinValue });
        if (data.success) {
            alert(data.message); // Show success message (e.g., "PIN set successfully." or "PIN cleared successfully.")
            hideAdminModals();
            loadDashboardData(); // Reload data to show updated PIN status
        } else {
            dom.setPinError.textContent = data.message || 'Failed to update PIN.';
            dom.setPinError.classList.remove('hidden');
        }
    } catch (error) {
        dom.setPinError.textContent = error.message || 'An error occurred. Please try again.';
        dom.setPinError.classList.remove('hidden');
    }
});


// NEW: Handle Change Username Form Submission
dom.changeUsernameForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    dom.changeUsernameError.classList.add('hidden');

    const userId = dom.changeUsernameUserId.value;
    const newUsername = dom.newUsername.value;

    try {
        const data = await adminApiRequest('users.php?action=change_username', 'POST', { user_id: userId, new_username: newUsername });
        if (data.success) {
            alert('Username updated successfully!'); // Simple success message
            hideAdminModals();
            loadDashboardData(); // Reload data to show updated username
        } else {
            dom.changeUsernameError.textContent = data.message || 'Failed to update username.';
            dom.changeUsernameError.classList.remove('hidden');
        }
    } catch (error) {
        dom.changeUsernameError.textContent = error.message || 'An error occurred. Please try again.';
        dom.changeUsernameError.classList.remove('hidden');
    }
});

// NEW: Handle Reset Password Form Submission
dom.resetPasswordForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    dom.resetPasswordError.classList.add('hidden');

    const userId = dom.resetPasswordUserIdHidden.value;
    const newPassword = dom.newPassword.value;
    const confirmNewPassword = dom.confirmNewPassword.value;

    if (newPassword !== confirmNewPassword) {
        dom.resetPasswordError.textContent = 'Passwords do not match.';
        dom.resetPasswordError.classList.remove('hidden');
        return;
    }
    if (newPassword.length < 6) {
        dom.resetPasswordError.textContent = 'Password must be at least 6 characters long.';
        dom.resetPasswordError.classList.remove('hidden');
        return;
    }

    try {
        const data = await adminApiRequest('users.php?action=reset_password', 'POST', { user_id: userId, new_password: newPassword });
        if (data.success) {
            alert('Password reset successfully! Recovery code cleared.'); // Simple success message
            hideAdminModals();
            loadDashboardData(); // Reload data to ensure consistency
        } else {
            dom.resetPasswordError.textContent = data.message || 'Failed to reset password.';
            dom.resetPasswordError.classList.remove('hidden');
        }
    } catch (error) {
        dom.resetPasswordError.textContent = error.message || 'An error occurred. Please try again.';
        dom.resetPasswordError.classList.remove('hidden');
    }
});

// NEW: Handle Generate New Recovery Code
async function generateNewRecoveryCode(userId) {
    dom.generatedRecoveryCodeDisplay.textContent = 'Generating...';
    dom.generateRecoveryCodeError.classList.add('hidden');
    try {
        const data = await adminApiRequest('users.php?action=generate_new_recovery_code', 'POST', { user_id: userId });
        if (data.success) {
            dom.generatedRecoveryCodeDisplay.textContent = data.recovery_code;
            dom.copyGeneratedRecoveryCodeBtn.onclick = () => {
                navigator.clipboard.writeText(data.recovery_code);
                alert('Recovery code copied to clipboard!');
            };
        } else {
            dom.generatedRecoveryCodeDisplay.textContent = 'Error';
            dom.generateRecoveryCodeError.textContent = data.message || 'Failed to generate new recovery code.';
            dom.generateRecoveryCodeError.classList.remove('hidden');
        }
    } catch (error) {
        dom.generatedRecoveryCodeDisplay.textContent = 'Error';
        dom.generateRecoveryCodeError.textContent = error.message || 'An error occurred. Please try again.';
        dom.generateRecoveryCodeError.classList.remove('hidden');
    }
}

// NEW: Event Listeners for modal close buttons
dom.closeChangeUsernameModal.addEventListener('click', hideAdminModals);
dom.closeResetPasswordModal.addEventListener('click', hideAdminModals);
dom.closeGenerateRecoveryCodeModal.addEventListener('click', hideAdminModals);
dom.confirmGenerateRecoveryCodeBtn.addEventListener('click', hideAdminModals); // Also closes the modal
dom.closeSetPinModal.addEventListener('click', hideAdminModals);
dom.cancelDeleteUserBtn.addEventListener('click', hideAdminModals); // NEW: Close Delete User modal


// NEW: Event listener for copying generated recovery code directly
dom.copyGeneratedRecoveryCodeBtn.addEventListener('click', () => {
    // This is already set by generateNewRecoveryCode, but good to have a direct listener too.
    const code = dom.generatedRecoveryCodeDisplay.textContent;
    if (code && code !== 'Generating...' && code !== 'Error') {
        navigator.clipboard.writeText(code);
        alert('Recovery code copied to clipboard!');
    }
});


// Check admin login status on page load
async function checkAdminLoginStatus() {
    try {
        const data = await adminApiRequest('auth.php?action=status', 'GET');
        if (data.success && data.loggedIn) {
            loggedInAdminUser = data.user;
            showAdminPage(dom.adminDashboardSection);
            loadDashboardData();
        } else {
            loggedInAdminUser = null;
            showAdminPage(dom.adminLoginSection);
        }
    } catch (error) {
        console.error("Error checking admin login status:", error);
        loggedInAdminUser = null;
        showAdminPage(dom.adminLoginSection);
        dom.adminLoginError.textContent = `Failed to connect to authentication service: ${error.message}`;
        dom.adminLoginError.classList.remove('hidden');
    }
}

// Event Listeners

// Admin Login Form Submission
dom.adminLoginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    dom.adminLoginError.classList.add('hidden'); // Hide previous errors

    const username = dom.adminUsername.value;
    const password = dom.adminPassword.value;

    try {
        const data = await adminApiRequest('auth.php?action=login', 'POST', { username, password });
        if (data.success) {
            loggedInAdminUser = data.user;
            showAdminPage(dom.adminDashboardSection);
            loadDashboardData();
        } else {
            dom.adminLoginError.textContent = data.message || 'Invalid username or password.';
            dom.adminLoginError.classList.remove('hidden');
        }
    } catch (error) {
        dom.adminLoginError.textContent = error.message || 'An error occurred during login. Please try again.';
        dom.adminLoginError.classList.remove('hidden');
    }
});

// Admin Logout Button
dom.adminLogoutBtn.addEventListener('click', async () => {
    try {
        await adminApiRequest('auth.php?action=logout', 'POST');
    } catch (error) {
        console.warn("Admin logout API call failed, but logging out frontend anyway.", error);
    } finally {
        loggedInAdminUser = null;
        showAdminPage(dom.adminLoginSection);
        dom.adminLoginForm.reset(); // Clear form fields
        dom.adminLoginError.classList.add('hidden'); // Clear any lingering error messages
    }
});

// Initial check on page load
checkAdminLoginStatus();