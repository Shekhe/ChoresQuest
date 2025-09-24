// js/rewards.js - Handles Rewards Section in Parent Dashboard

import { apiRequest } from './api.js';
import * as dom from './dom.js';
import * as ui from './ui.js';
import { state } from './config.js';
import * as utils from './utils.js';

let loadParentContextDataCallback; // Callback to refresh data in main.js

/**
 * Initializes the rewards module, setting up necessary callbacks and event listeners.
 * @param {object} callbacks - Object containing callback functions, e.g., { loadParentContextData: func }
 */
export function initializeRewards(callbacks) {
    loadParentContextDataCallback = callbacks.loadParentContextData;

    // Add event listener for "Add New Reward" button
    dom.addNewRewardBtn.removeEventListener('click', handleAddNewRewardClick); // Prevent duplicates
    dom.addNewRewardBtn.addEventListener('click', handleAddNewRewardClick);

    // Add event listener for reward form submission
    dom.rewardForm.removeEventListener('submit', handleRewardFormSubmit); // Prevent duplicates
    dom.rewardForm.addEventListener('submit', handleRewardFormSubmit);

    // Add event listener for closing the reward modal
    dom.closeRewardModal.removeEventListener('click', handleCloseRewardModalClick); // Prevent duplicates
    dom.closeRewardModal.addEventListener('click', handleCloseRewardModalClick);
}

/**
 * Renders the list of rewards in the parent dashboard.
 */
export function renderParentRewards() {
    dom.rewardListContainer.innerHTML = '';
    if (state.rewardsData.length === 0) {
        dom.rewardListContainer.innerHTML = `<p class="text-gray-500 p-4 text-center col-span-full">No rewards configured yet.</p>`;
        return;
    }
    state.rewardsData.forEach(reward => {
        const rewardElementHTML = `
            <div class="card" data-reward-id="${reward.id}">
                <img src="${reward.image_url || `https://placehold.co/300x200/cccccc/969696?text=Reward`}" alt="${utils.escapeHTML(reward.title)}" class="w-full h-32 object-cover rounded-md mb-3" onerror="this.src='https://placehold.co/300x200/cccccc/969696?text=Reward'">
                <h4 class="font-semibold text-lg">${utils.escapeHTML(reward.title)}</h4>
                <p class="text-yellow-600 font-semibold"><i class="fas fa-star mr-1"></i>${reward.required_points} Points</p>
                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center">
                        <label for="reward-active-${reward.id}" class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" id="reward-active-${reward.id}" class="sr-only toggle-reward-active" data-reward-id="${reward.id}" ${reward.is_active ? 'checked' : ''}>
                                <div class="block bg-gray-300 w-10 h-6 rounded-full"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                            </div>
                            <div class="ml-2 text-gray-700 text-sm">Visible to Children</div>
                        </label>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-app-blue hover:text-app-blue-darkest edit-reward-btn p-1" title="Edit Reward"><i class="fas fa-edit"></i> Edit</button>
                        <button class="text-red-500 hover:text-red-700 delete-reward-btn p-1" title="Delete Reward"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
        `;
        dom.rewardListContainer.insertAdjacentHTML('beforeend', rewardElementHTML);
    });

    // Attach event listeners for the newly rendered elements
    attachRewardEventListeners();
}

/**
 * Attaches event listeners for reward actions.
 * This should be called after `renderParentRewards` or any dynamic update.
 */
function attachRewardEventListeners() {
    dom.rewardListContainer.querySelectorAll('.edit-reward-btn').forEach(btn => {
        btn.removeEventListener('click', handleEditRewardClick); // Prevent duplicates
        btn.addEventListener('click', handleEditRewardClick);
    });
    dom.rewardListContainer.querySelectorAll('.delete-reward-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteRewardClick); // Prevent duplicates
        btn.addEventListener('click', handleDeleteRewardClick);
    });
    dom.rewardListContainer.querySelectorAll('.toggle-reward-active').forEach(toggle => {
        toggle.removeEventListener('change', handleToggleRewardActiveChange); // Prevent duplicates
        toggle.addEventListener('change', handleToggleRewardActiveChange);
    });
}

/**
 * Handler for the "Add New Reward" button click.
 */
function handleAddNewRewardClick() {
    dom.rewardForm.reset();
    dom.rewardModalTitle.textContent = "Add New Reward";
    dom.rewardForm.rewardId.value = '';
    dom.rewardForm.rewardImageURL.value = '';
    // Optionally clear image preview if you add one for rewards
    // document.getElementById('rewardImagePreview').src = 'default_reward_placeholder.png';
    ui.showModal(dom.rewardModal);
}

/**
 * Handler for the reward form submission (Create/Update).
 * @param {Event} e - The submit event.
 */
async function handleRewardFormSubmit(e) {
    e.preventDefault();
    const fileInput = dom.rewardForm.rewardImage;
    let imageUrl = dom.rewardForm.rewardImageURL.value; // Prefer existing URL if available

    if (fileInput.files.length > 0) {
        const uploadedUrl = await utils.uploadImage(fileInput);
        if (uploadedUrl === null) return; // Exit if upload failed
        imageUrl = uploadedUrl; // Use the newly uploaded URL
    }

    const rewardId = dom.rewardForm.rewardId.value;
    const payload = {
        title: dom.rewardForm.rewardTitle.value,
        requiredPoints: parseInt(dom.rewardForm.rewardPoints.value),
        image: imageUrl,
    };
    if (rewardId) payload.id = rewardId;

    try {
        const action = rewardId ? 'update' : 'create';
        const data = await apiRequest(`rewards.php?action=${action}`, 'POST', payload);
        if (data.success) {
            ui.showMessage("Success", data.message, "success");
            await loadParentContextDataCallback(); // Refresh all rewards data
            renderParentRewards(); // Re-render the rewards list
            ui.hideAllModals(); // Hide the reward modal
        }
    } catch (error) {
        // apiRequest already handles showing an error modal
        console.error("Reward form submission failed:", error);
    }
}

/**
 * Handler for the "Edit Reward" button click.
 * @param {Event} e - The click event.
 */
function handleEditRewardClick(e) {
    const rewardId = e.currentTarget.closest('.card').dataset.rewardId;
    const reward = state.rewardsData.find(r => r.id.toString() === rewardId.toString());
    if (reward) {
        dom.rewardForm.reset();
        dom.rewardModalTitle.textContent = "Edit Reward";
        dom.rewardForm.rewardId.value = reward.id;
        dom.rewardForm.rewardImageURL.value = reward.image_url || '';
        dom.rewardForm.rewardTitle.value = reward.title;
        dom.rewardForm.rewardPoints.value = reward.required_points;
        // If you add an image preview to reward modal:
        // document.getElementById('rewardImagePreview').src = reward.image_url || 'default_reward_placeholder.png';
        ui.showModal(dom.rewardModal);
    }
}

/**
 * Handler for the "Delete Reward" button click.
 * @param {Event} e - The click event.
 */
async function handleDeleteRewardClick(e) {
    const rewardId = e.currentTarget.closest('.card').dataset.rewardId;
    const rewardToDelete = state.rewardsData.find(r => r.id.toString() === rewardId.toString());
    if (!rewardToDelete) return;

    if (confirm(`Are you sure you want to delete the reward "${utils.escapeHTML(rewardToDelete.title)}"?`)) {
        try {
            const data = await apiRequest(`rewards.php?action=delete`, 'POST', { id: rewardId });
            if (data.success) {
                ui.showMessage("Success", data.message, "success");
                await loadParentContextDataCallback(); // Refresh all rewards data
                renderParentRewards(); // Re-render the rewards list
            }
        } catch (error) {
            // apiRequest already handles showing an error modal
            console.error("Failed to delete reward:", error);
        }
    }
}

/**
 * Handler for toggling the active status of a reward.
 * @param {Event} e - The change event from the checkbox.
 */
async function handleToggleRewardActiveChange(e) {
    const rewardId = e.currentTarget.dataset.rewardId;
    const isActive = e.currentTarget.checked;
    
    try {
        const data = await apiRequest('rewards.php?action=toggle_active_status', 'POST', {
            id: rewardId,
            is_active: isActive ? 1 : 0
        });
        if (data.success) {
            ui.showMessage("Success", data.message, "success");
            // Update the state immediately for responsiveness
            const rewardToUpdate = state.rewardsData.find(r => r.id.toString() === rewardId.toString());
            if (rewardToUpdate) {
                rewardToUpdate.is_active = isActive;
            }
            // No need to re-render the whole list, just ensure state is in sync
        } else {
            // If API call fails, revert the toggle in UI
            e.currentTarget.checked = !isActive;
        }
    } catch (error) {
        // If API call fails, revert the toggle in UI
        e.currentTarget.checked = !isActive;
        console.error("Failed to toggle reward active status:", error);
    }
}

/**
 * Handler for closing the reward modal.
 */
function handleCloseRewardModalClick() {
    ui.hideAllModals();
}