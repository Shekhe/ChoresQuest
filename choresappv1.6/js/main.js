// This is the main entry point for the application.
// It initializes all the different modules.

import { state } from './config.js';
import * as dom from './dom.js';
import * as ui from './ui.js';
import { apiRequest } from './api.js';
import { initializeAuth } from './auth.js';
import { initializeParentDashboard } from './parent.js';
import { initializeChildDashboard, renderKidProfileSelection, renderChildDashboard } from './child.js';
import { initializeRewards, renderParentRewards } from './rewards.js'; // NEW: Import rewards.js

// NEW: Flag to track if the parent dashboard has been initialized for the current session
let hasParentDashboardBeenInitialized = false;

// --- Core App Logic ---

async function loadParentContextData() {
    if (!state.loggedInParentUser) {
        console.warn("loadParentContextData called without a parent user context.");
        state.childrenData = [];
        state.tasksData = [];
        state.rewardsData = [];
        state.notificationsData = [];
        return false;
    }
    try {
        const [childrenResult, tasksResult, rewardsResult, notificationsResult] = await Promise.all([
            apiRequest('children.php?action=list'),
            apiRequest('tasks.php?action=list'),
            apiRequest('rewards.php?action=list'),
            apiRequest('notifications.php?action=list')
        ]);

        state.childrenData = childrenResult.success ? childrenResult.children : [];
        state.tasksData = tasksResult.success ? tasksResult.tasks : [];
        state.rewardsData = rewardsResult.success ? rewardsResult.rewards : [];
        state.notificationsData = notificationsResult.success ? notificationsResult.notifications : [];
        
        return true;
    } catch (error) {
        console.error("Error in loadParentContextData:", error);
        return false;
    }
}

function showParentDashboard() {
    // NEW: Only initialize the parent dashboard listeners once per session.
    // Subsequent calls to showParentDashboard will just display the page.
    if (!hasParentDashboardBeenInitialized) {
        initializeParentDashboard({ loadParentContextData }); // Pass loadParentContextData to parent.js
        initializeRewards({ loadParentContextData }); // NEW: Initialize rewards.js
        hasParentDashboardBeenInitialized = true;
    }
    ui.showPage(dom.parentDashboardPage);
}

async function checkLoginStatus() {
    try {
        const statusData = await apiRequest('auth.php?action=status');
        if (!statusData.success || !statusData.loggedIn) {
            ui.showPage(dom.landingPage);
            return;
        }

        state.loggedInParentUser = statusData.user;
        await loadParentContextData();

        const lastActiveChildId = localStorage.getItem('lastActiveChildId');
        const lastActiveParentId = localStorage.getItem('lastActiveParentIdForChild');

        if (lastActiveChildId && lastActiveParentId && lastActiveParentId === state.loggedInParentUser.id.toString()) {
            const selectedKid = state.childrenData.find(c => c.id.toString() === lastActiveChildId);
            if (selectedKid) {
                state.activeKidProfile = { ...selectedKid, type: 'kid' };
                ui.showPage(dom.kidsZonePage);
                dom.kidProfileSelection.classList.add('hidden');
                dom.childDashboard.classList.remove('hidden');
                renderChildDashboard(state.activeKidProfile);
                return;
            }
        }

        if (state.loggedInParentUser.has_pin_set) {
            ui.showModal(dom.pinEntryModal);
        } else {
            showParentDashboard();
        }
    } catch (error) {
        console.error("Error checking login status:", error);
        ui.showPage(dom.landingPage);
    }
}

function initializeApp() {
    console.log("Initializing App...");

    const callbacks = {
        onLoginSuccess: async () => {
            await loadParentContextData();
            if (state.loginRedirectTarget === 'kidsZone') {
                ui.showPage(dom.kidsZonePage);
                renderKidProfileSelection();
            } else { 
                if (state.loggedInParentUser.has_pin_set) {
                    ui.showModal(dom.pinEntryModal);
                } else {
                    showParentDashboard(); // This will now ensure initializeParentDashboard is called only once
                }
            }
            state.loginRedirectTarget = null;
        },
        onLogoutSuccess: () => {
            if (dom.childDashboard && dom.childDashboard.refreshIntervalId) { 
                clearInterval(dom.childDashboard.refreshIntervalId);
                dom.childDashboard.refreshIntervalId = null;
            }
            // Clear the parent dashboard initialization flag on logout
            hasParentDashboardBeenInitialized = false; 

            state.loggedInParentUser = null;
            state.activeKidProfile = null;
            Object.keys(state).forEach(key => {
                if (Array.isArray(state[key])) state[key] = [];
            });
            localStorage.clear();
            sessionStorage.clear();
            ui.showPage(dom.landingPage);
        },
        onSwitchUser: async () => {
            if (dom.childDashboard && dom.childDashboard.refreshIntervalId) {
                clearInterval(dom.childDashboard.refreshIntervalId);
                dom.childDashboard.refreshIntervalId = null;
            }
            state.activeKidProfile = null;
            dom.childDashboard.classList.add('hidden');
            dom.kidProfileSelection.classList.remove('hidden');
            await renderKidProfileSelection();
        },
        loadParentContextData: loadParentContextData,
    };

    initializeAuth(callbacks);
    initializeChildDashboard(callbacks);

    async function showPolicyModal(type) {
        const url = type === 'privacy' ? './legal/privacy.html' : './legal/terms.html';
        const title = type === 'privacy' ? 'Privacy Policy' : 'Terms of Use';
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Could not load policy document.');
            const content = await response.text();
            dom.policyModalTitle.textContent = title;
            dom.policyModalContentArea.innerHTML = content;
            ui.showModal(dom.policyModal);
        } catch (error) {
            ui.showMessage('Error', 'Could not load the document.');
        }
    }
    
    dom.landingPrivacyLink.addEventListener('click', (e) => { e.preventDefault(); showPolicyModal('privacy'); });
    dom.landingTermsLink.addEventListener('click', (e) => { e.preventDefault(); showPolicyModal('terms'); });
    dom.signUpPrivacyLink.addEventListener('click', (e) => { e.preventDefault(); showPolicyModal('privacy'); });
    dom.signUpTermsLink.addEventListener('click', (e) => { e.preventDefault(); showPolicyModal('terms'); });
    dom.closePolicyModalBtn.addEventListener('click', () => ui.hideAllAuthModals());

    dom.messageModalCloseBtn.addEventListener('click', () => dom.messageModal.classList.add('hidden'));
    
    dom.parentBtn.addEventListener('click', () => {
        state.loginRedirectTarget = 'parentDashboard';
        if (state.loggedInParentUser) {
            if (state.loggedInParentUser.has_pin_set) {
                ui.showModal(dom.pinEntryModal);
            } else {
                showParentDashboard(); // Ensures single initialization
            }
        } else {
            ui.showModal(dom.loginModal);
        }
    });

    dom.kidsBtn.addEventListener('click', async () => {
        state.loginRedirectTarget = 'kidsZone';
        if (state.loggedInParentUser) {
            ui.showPage(dom.kidsZonePage);
            dom.childDashboard.classList.add('hidden');
            dom.kidProfileSelection.classList.remove('hidden');
            await renderKidProfileSelection();
        } else {
             ui.showMessage("Parent Login Required", "A parent needs to log in first to access the Child Zone.", "info");
             ui.showModal(dom.loginModal);
        }
    });

    dom.pinEntryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const data = await apiRequest('parent_settings.php?action=verify_pin', 'POST', { pin: dom.pinInput.value });
            if (data.success) {
                sessionStorage.setItem('parentPinVerified-' + state.loggedInParentUser.id, 'true');
                dom.pinEntryModal.classList.add('hidden');
                showParentDashboard(); // This will now trigger the interval in parent.js, ensuring initialization only once.
            } else {
                 dom.pinErrorMessage.textContent = data.message || 'Invalid PIN.';
                 dom.pinErrorMessage.classList.remove('hidden');
            }
        } catch (error) {
             dom.pinErrorMessage.textContent = error.message || 'Error verifying PIN.';
             dom.pinErrorMessage.classList.remove('hidden');
        }
    });

    dom.cancelPinEntryBtn.addEventListener('click', () => {
        ui.hideAllAuthModals();
    });

    checkLoginStatus();
}

initializeApp();