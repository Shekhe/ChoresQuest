// This file contains all logic related to user authentication.

import { apiRequest } from './api.js';
import * as dom from './dom.js';
import * as ui from './ui.js';
import { state } from './config.js';

// This function will be called from main.js to set up all the event listeners.
export function initializeAuth(callbacks) {

    dom.signUpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = dom.signUpForm.signUpName.value;
        const username = dom.signUpForm.signUpUsername.value;
        const password = dom.signUpForm.signUpPassword.value;
        const confirmPassword = dom.signUpForm.signUpConfirmPassword.value;

        if (password !== confirmPassword) {
            ui.showMessage("Sign Up Error", "Passwords do not match.", "error");
            return;
        }

        try {
            const data = await apiRequest('auth.php?action=register', 'POST', { name, username, password, confirmPassword });
            if (data.success) {
                state.loggedInParentUser = data.user;
                if (data.recovery_code) {
                    dom.recoveryCodeDisplay.textContent = data.recovery_code;
                    ui.showModal(dom.recoveryCodeModal);
                } else {
                    // Fallback if recovery code isn't returned for some reason
                    callbacks.onLoginSuccess();
                }
            }
        } catch (error) { /* Handled by apiRequest */ }
    });

    dom.loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = dom.loginForm.loginUsername.value;
        const password = dom.loginForm.loginPassword.value;
        try {
            const data = await apiRequest('auth.php?action=login', 'POST', { username, password });
            if (data.success) {
                state.loggedInParentUser = data.user;
                ui.hideAllAuthModals();
                callbacks.onLoginSuccess(); // Use callback to proceed
            }
        } catch (error) { /* Handled by apiRequest */ }
    });

    dom.parentLogoutBtn.addEventListener('click', async () => {
        console.log("Parent logout button clicked.");
        try {
            await apiRequest('auth.php?action=logout', 'POST');
        } catch (error) {
            console.warn("Logout API call failed, but logging out frontend anyway.", error);
        } finally {
            callbacks.onLogoutSuccess(); // Use callback to reset app state
        }
    });

    dom.childDashboardLogoutBtn.addEventListener('click', async () => {
        console.log("Child Dashboard Logout button clicked (full logout).");
        try {
            await apiRequest('auth.php?action=logout', 'POST');
        } catch (error) {
            console.warn("Logout API call failed, but logging out frontend anyway.", error);
        } finally {
            callbacks.onLogoutSuccess();
        }
    });

    // --- Listeners for Modals and Links ---
    dom.getStartedLink.addEventListener('click', (e) => { e.preventDefault(); ui.showModal(dom.signUpModal); });
    dom.loginLink.addEventListener('click', (e) => { e.preventDefault(); ui.showModal(dom.loginModal); });
    dom.closeSignUpModal.addEventListener('click', () => ui.hideAllAuthModals());
    dom.closeLoginModal.addEventListener('click', () => ui.hideAllAuthModals());
    dom.switchToLoginLink.addEventListener('click', (e) => { e.preventDefault(); ui.showModal(dom.loginModal); });
    dom.switchToSignUpLink.addEventListener('click', (e) => { e.preventDefault(); ui.showModal(dom.signUpModal); });
    
    // --- Listeners for Recovery Flow ---
    dom.copyRecoveryCodeBtn.addEventListener('click', () => {
        const code = dom.recoveryCodeDisplay.textContent;
        navigator.clipboard.writeText(code).then(() => {
            ui.showMessage("Copied!", "Recovery code copied to clipboard.", "success");
        }).catch(err => {
            ui.showMessage("Oops", "Could not copy the code automatically.", "error");
        });
    });

    dom.finishRegistrationBtn.addEventListener('click', () => {
        ui.hideAllAuthModals();
        callbacks.onLoginSuccess();
    });
    
    dom.recoverAccountLink.addEventListener('click', (e) => {
        e.preventDefault();
        dom.recoveryStep1.classList.remove('hidden');
        dom.recoveryStep2.classList.add('hidden');
        dom.recoveryCodeForm.reset();
        dom.resetPasswordForm.reset();
        ui.showModal(dom.accountRecoveryModal);
    });

    dom.closeRecoveryModal.addEventListener('click', () => {
        ui.hideAllAuthModals();
    });

    dom.recoveryCodeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const recoveryCode = dom.recoveryCodeForm.recoveryCodeInput.value;
        try {
            const data = await apiRequest('auth.php?action=verify_recovery_code', 'POST', { recovery_code: recoveryCode });
            if (data.success) {
                dom.recoveryUsernameDisplay.textContent = data.username;
                dom.resetPasswordUserId.value = data.userId;
                dom.recoveryStep1.classList.add('hidden');
                dom.recoveryStep2.classList.remove('hidden');
            }
        } catch (error) { /* Handled by apiRequest */ }
    });

    dom.resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = dom.resetPasswordUserId.value;
        const newPassword = dom.resetPasswordForm.resetPasswordInput.value;
        const confirmPassword = dom.resetPasswordForm.resetConfirmPasswordInput.value;

        if (newPassword !== confirmPassword) {
            ui.showMessage("Error", "Passwords do not match.", "error");
            return;
        }

        try {
            const data = await apiRequest('auth.php?action=reset_password', 'POST', { userId, newPassword, confirmPassword });
            if (data.success) {
                ui.hideAllAuthModals();
                ui.showMessage("Success!", "Your password has been reset. Please log in with your new password.", "success");
                ui.showModal(dom.loginModal);
            }
        } catch (error) { /* Handled by apiRequest */ }
    });
}
