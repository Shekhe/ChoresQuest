// This file contains all logic for the Parent Dashboard.

import { apiRequest } from './api.js';
import * as dom from './dom.js';
import * as ui from './ui.js';
import { state, appSettings, NOTIFICATIONS_PER_PAGE } from './config.js';
import { ICON_LIBRARY } from './icon-library.js';
import * as utils from './utils.js'; 
import { renderParentRewards } from './rewards.js';

let loadParentContextData; // Will be set by a callback from main.js
let refreshIntervalId = null; // Variable to hold the refresh interval ID

// NEW: Flag to ensure form event listeners are attached only once
let areParentFormListenersInitialized = false;

// --- Helper Functions for Parent Dashboard ---
function updateTaskFilterButtonCounts() {
    const activeTasks = state.tasksData.filter(task => task.status === 'active' && !utils.isTaskOverdue(task));
    const overdueTasks = state.tasksData.filter(task => task.status === 'active' && utils.isTaskOverdue(task));
    // MODIFIED: Changed "completed" to "archived" and now only counts non-recurring tasks that are completed.
    const archivedTasks = state.tasksData.filter(task => task.repeat_type === 'none' && task.status === 'completed');

    dom.filterBtnActive.textContent = `Active (${activeTasks.length})`;
    dom.filterBtnOverdue.textContent = `Overdue (${overdueTasks.length})`;
    dom.filterBtnArchived.textContent = `Archived (${archivedTasks.length})`; // Renamed and re-filtered
}

function updateUnreadNotificationBadge() {
    if (!state.loggedInParentUser) {
        dom.unreadNotificationCountBadge.classList.add('hidden');
        return;
    }
    const unreadCount = state.notificationsData.filter(n => n.is_read == 0).length;
    if (unreadCount > 0) {
        dom.unreadNotificationCountBadge.textContent = unreadCount;
        dom.unreadNotificationCountBadge.classList.remove('hidden');
    } else {
        dom.unreadNotificationCountBadge.classList.add('hidden');
    }
}

function updateParentNotifications() {
    if (!state.loggedInParentUser) {
        dom.parentNotificationArea.textContent = "N/A";
        updateUnreadNotificationBadge();
        return;
    }

    const overdueTasksResult = state.tasksData.filter(task => appSettings.notifyForOverdue && task.status === 'active' && utils.isTaskOverdue(task));

    // Prioritize overdue tasks first
    if (overdueTasksResult.length > 0) {
        dom.parentNotificationArea.textContent = `${overdueTasksResult.length} task(s) are overdue!`;
        dom.parentNotificationArea.classList.add('text-red-700', 'font-bold');
        dom.parentNotificationArea.classList.remove('text-yellow-600', 'text-lime-600');
    } else {
        // If no overdue tasks, check for the most recent unread notification of other types
        const unreadOtherNotifications = state.notificationsData
            .filter(n => n.is_read == 0 && n.notification_type !== 'task_overdue') // Exclude overdue tasks from this list
            .sort((a, b) => new Date(b.created_at_iso) - new Date(a.created_at_iso));

        if (unreadOtherNotifications.length > 0) {
            const latestUnread = unreadOtherNotifications[0];
            if (latestUnread.notification_type === 'points_added_by_parent') {
                dom.parentNotificationArea.textContent = `Points added for ${utils.escapeHTML(latestUnread.child_name || 'a child')}.`;
                dom.parentNotificationArea.classList.add('text-lime-600');
                dom.parentNotificationArea.classList.remove('text-red-700', 'font-bold', 'text-yellow-600');
            } else if (latestUnread.notification_type === 'points_deducted_by_parent') {
                dom.parentNotificationArea.textContent = `Points deducted for ${utils.escapeHTML(latestUnread.child_name || 'a child')}.`;
                dom.parentNotificationArea.classList.add('text-red-600');
                dom.parentNotificationArea.classList.remove('text-yellow-600', 'text-lime-600', 'font-bold');
            } else if (latestUnread.notification_type === 'reward_claimed') {
                 dom.parentNotificationArea.textContent = `${utils.escapeHTML(latestUnread.child_name || 'A child')} claimed: ${utils.escapeHTML(latestUnread.reward_title || 'a reward')}`;
                 dom.parentNotificationArea.classList.add('text-lime-600');
                 dom.parentNotificationArea.classList.remove('text-red-700', 'font-bold', 'text-yellow-600');
            }
            else {
                // Generic message for other types of unread notifications
                dom.parentNotificationArea.textContent = "You have new activity!";
                dom.parentNotificationArea.classList.add('text-yellow-600');
                dom.parentNotificationArea.classList.remove('text-red-700', 'font-bold', 'text-lime-600');
            }
        } else {
            // No overdue tasks and no other unread notifications
            dom.parentNotificationArea.textContent = "No new alerts.";
            dom.parentNotificationArea.classList.remove('text-red-700', 'font-bold', 'text-lime-600');
            dom.parentNotificationArea.classList.add('text-yellow-600');
        }
    }
    updateUnreadNotificationBadge();
}

async function markNotificationAsRead(notificationId) {
    try {
        await apiRequest('notifications.php?action=mark_read', 'POST', { notification_id: notificationId }, { suppressModalForErrorMessages: ["Notification not found or already read."] });
        const notifToUpdate = state.notificationsData.find(n => n.id.toString() === notificationId.toString());
        if (notifToUpdate) notifToUpdate.is_read = 1;
        updateUnreadNotificationBadge();
        renderNotificationsPage();
        updateParentNotifications(); // Re-evaluate the sidebar alert after marking as read
    } catch (error) {
        console.log("markNotificationAsRead caught (possibly suppressed):", error.message);
    }
}

function updateCurrentTime() {
    dom.currentTimeDisplay.textContent = utils.formatDateTime(new Date());
}

// --- Rendering Functions ---

function renderRecentActivity() {
    dom.recentActivityList.innerHTML = '';
    if (!state.loggedInParentUser || state.notificationsData.length === 0) {
        dom.recentActivityList.innerHTML = '<li>No recent activity to display.</li>';
        return;
    }

    const sortedNotifications = [...state.notificationsData]
        .sort((a, b) => new Date(b.created_at_iso) - new Date(a.created_at_iso))
        .slice(0, 5);

    if (sortedNotifications.length === 0) {
        dom.recentActivityList.innerHTML = '<li>No recent activity to display.</li>';
        return;
    }

    sortedNotifications.forEach(notif => {
        const li = document.createElement('li');
        li.className = 'text-sm text-gray-600';
        
        let icon = 'fas fa-info-circle text-app-blue';
        if (notif.notification_type === 'reward_claimed') icon = 'fas fa-gift text-yellow-500';
        else if (notif.notification_type === 'task_completed_by_child') icon = 'fas fa-check-circle text-app-blue';
        else if (notif.notification_type === 'points_added_by_parent') icon = 'fas fa-plus-circle text-lime-600';
        else if (notif.notification_type === 'points_deducted_by_parent') icon = 'fas fa-minus-circle text-red-600';
        else if (notif.notification_type === 'task_overdue') icon = 'fas fa-exclamation-triangle text-red-500';

        li.innerHTML = `<i class="${icon} mr-2"></i> ${utils.escapeHTML(notif.message)} <span class="text-xs text-gray-400">- ${utils.formatDateTime(notif.created_at_iso)}</span>`;
        dom.recentActivityList.appendChild(li);
    });
}

function renderParentOverview() {
    if (!state.loggedInParentUser) return;
    dom.totalChildrenCount.textContent = state.childrenData.length;
    const activeTasks = state.tasksData.filter(t => t.status === 'active' && !utils.isTaskOverdue(t));
    const overdueTasksResult = state.tasksData.filter(t => t.status === 'active' && utils.isTaskOverdue(t));
    dom.activeTasksCount.textContent = activeTasks.length;
    dom.overdueTasksCount.textContent = overdueTasksResult.length;
    updateParentNotifications();
    updateTaskFilterButtonCounts();
    renderRecentActivity();
}

function renderNotificationPagination(totalNotifications) {
    dom.notificationPaginationControls.innerHTML = '';
    const totalPages = Math.ceil(totalNotifications / NOTIFICATIONS_PER_PAGE);

    if (totalPages <= 1) return;

    const prevButton = document.createElement('button');
    prevButton.innerHTML = '&laquo; Prev';
    prevButton.className = 'btn btn-neutral mx-1';
    prevButton.disabled = state.currentNotificationPage === 1;
    prevButton.addEventListener('click', () => {
        if (state.currentNotificationPage > 1) {
            state.currentNotificationPage--;
            renderNotificationsPage();
        }
    });
    dom.notificationPaginationControls.appendChild(prevButton);

    const nextButton = document.createElement('button');
    nextButton.innerHTML = 'Next &raquo;';
    nextButton.className = 'btn btn-neutral mx-1';
    nextButton.disabled = state.currentNotificationPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (state.currentNotificationPage < totalPages) {
            state.currentNotificationPage++;
            renderNotificationsPage();
        }
    });
    dom.notificationPaginationControls.appendChild(nextButton);
}

function renderNotificationsPage() {
    if (!state.loggedInParentUser) {
        dom.fullNotificationList.innerHTML = '<p class="text-gray-500">Please log in to see notifications.</p>';
        return;
    }
    
    dom.fullNotificationList.innerHTML = '<p class="text-gray-500">Loading notifications...</p>';
    if (state.notificationsData.length === 0) {
        dom.fullNotificationList.innerHTML = '<p class="text-gray-500">No notifications yet.</p>';
        dom.notificationPaginationControls.innerHTML = '';
        return;
    }

    const sortedNotifications = [...state.notificationsData].sort((a,b) => new Date(b.created_at_iso) - new Date(a.created_at_iso));
    const startIndex = (state.currentNotificationPage - 1) * NOTIFICATIONS_PER_PAGE;
    const endIndex = startIndex + NOTIFICATIONS_PER_PAGE;
    const paginatedNotifications = sortedNotifications.slice(startIndex, endIndex);

    dom.fullNotificationList.innerHTML = '';
    
    paginatedNotifications.forEach(notif => {
        const notifElement = document.createElement('div');
        notifElement.className = `notification-item ${notif.is_read == 1 ? 'is-read' : 'font-semibold'}`;
        notifElement.dataset.notificationId = notif.id;

        let iconClass = 'fas fa-info-circle text-app-blue';
        if (notif.notification_type === 'reward_claimed') iconClass = 'fas fa-gift text-yellow-500';
        else if (notif.notification_type === 'task_completed_by_child') iconClass = 'fas fa-check-circle text-app-blue';
        else if (notif.notification_type === 'points_added_by_parent') iconClass = 'fas fa-plus-circle text-lime-600';
        else if (notif.notification_type === 'points_deducted_by_parent') iconClass = 'fas fa-minus-circle text-red-600';
        else if (notif.notification_type === 'task_overdue') iconClass = 'fas fa-exclamation-triangle text-red-500';

        notifElement.innerHTML = `
            <div class="flex items-start space-x-3">
                <i class="${iconClass} mt-1"></i>
                <div>
                    <p class="text-gray-800">${utils.escapeHTML(notif.message)}</p>
                    <p class="text-xs text-gray-500">${utils.formatDateTime(notif.created_at_iso)}</p>
                </div>
            </div>
        `;
        if (notif.is_read == 0) {
            notifElement.style.cursor = 'pointer';
            // IMPORTANT: Remove previous event listener before adding a new one
            notifElement.removeEventListener('click', () => markNotificationAsRead(notif.id)); 
            notifElement.addEventListener('click', () => markNotificationAsRead(notif.id));
        }
        dom.fullNotificationList.appendChild(notifElement);
    });

    renderNotificationPagination(state.notificationsData.length);
}

function renderParentTasks(filter = 'active') {
    dom.taskListContainer.innerHTML = '<p class="text-gray-500 p-4 text-center">Loading tasks...</p>';
    
    let filteredTasksToDisplay = [];
    const today = utils.getTodayDateString(); 

    if (filter === 'active') {
        filteredTasksToDisplay = state.tasksData.filter(task => {
            // NEW LOGIC FOR ACTIVE TASKS ON PARENT DASHBOARD:
            // - Show if status is 'active' AND
            //   - It's a one-time task AND not overdue
            //   - OR It's a recurring task (regardless of due_date relative to today, as long as it's active and not overdue)
            return task.status === 'active' &&
                   ((task.repeat_type === 'none' && !utils.isTaskOverdue(task)) ||
                    (task.repeat_type !== 'none' && !utils.isTaskOverdue(task))); 
        });
    } else if (filter === 'overdue') {
        filteredTasksToDisplay = state.tasksData.filter(task => {
            // NEW LOGIC for overdue tasks:
            // Any active task where the due_date is in the past compared to today.
            return task.status === 'active' && utils.isTaskOverdue(task);
        });
    } else if (filter === 'archived') { 
        filteredTasksToDisplay = state.tasksData.filter(task => {
            // NEW LOGIC for archived tasks:
            // Only one-time tasks that have been completed (status === 'completed').
            return task.repeat_type === 'none' && task.status === 'completed';
        });
    }

    dom.taskListContainer.innerHTML = '';
    updateParentNotifications();
    updateTaskFilterButtonCounts();

    if (filteredTasksToDisplay.length === 0) {
        dom.taskListContainer.innerHTML = `<p class="text-gray-500 p-4 text-center">No tasks in this category.</p>`;
        return;
    }
    
    filteredTasksToDisplay.forEach(task => {
        const isFamily = !!task.is_family_task;
        const assignedName = isFamily ? 'All Children' : (utils.escapeHTML(task.assigned_children_names) || 'Unassigned');
        let taskCardClasses = "card flex flex-col sm:flex-row justify-between items-start sm:items-center";
        let titleClasses = "font-semibold";
        let statusTextForDisplay = '';
        let repeatIndicator = task.repeat_type && task.repeat_type !== 'none' 
            ? `<p class="text-xs text-app-blue"><i class="fas fa-redo mr-1"></i> Repeats: ${task.repeat_type.charAt(0).toUpperCase() + task.repeat_type.slice(1)}${task.repeat_on_days ? ` (${utils.formatDays(task.repeat_on_days)})` : ''}</p>` 
            : '';
        const familyTaskVisual = isFamily ? `<span class="text-xs highlight-purple ml-2">(<i class="fas fa-users"></i> Family)</span>` : '';
        const notesHTML = task.notes 
            ? `<p class="text-xs text-gray-600 mt-1 pl-2 border-l-2 border-gray-300 italic">${utils.escapeHTML(task.notes)}</p>` 
            : '';

        // MODIFIED: Adjusted status display logic
        if (filter === 'archived') { 
            taskCardClasses += " opacity-70 highlight-green-bg border-l-4 border-lime-500";
            titleClasses += " highlight-green";
            statusTextForDisplay = `<div class="text-xs text-lime-600 mt-1"><i class="fas fa-check-circle mr-1"></i> Archived on ${utils.formatDate(task.completed_date)}</div>`;
        } else if (task.status === 'active' && utils.isTaskOverdue(task)) { 
            taskCardClasses += " highlight-red-bg border-l-4 border-red-500";
            titleClasses += " highlight-red";
            statusTextForDisplay = `<div class="text-xs text-red-500 mt-1 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i> Overdue</div>`;
        } else if (task.status === 'active' && task.repeat_type !== 'none' && task.due_date > today) { 
             taskCardClasses += " border-l-4 border-gray-300"; 
             statusTextForDisplay = `<div class="text-xs text-gray-500 mt-1"><i class="fas fa-calendar-alt mr-1"></i> Due: ${utils.formatDate(task.due_date)}</div>`;
        } else if (task.status === 'active' && task.repeat_type !== 'none' && task.due_date === today) { 
             taskCardClasses += " border-l-4 border-app-blue bg-app-blue-light";
             statusTextForDisplay = `<div class="text-xs text-app-blue mt-1 font-semibold"><i class="fas fa-calendar-day mr-1"></i> Due Today</div>`;
        } else if (task.status === 'active' && task.repeat_type === 'none') { 
             taskCardClasses += " border-l-4 border-gray-300"; 
             statusTextForDisplay = `<div class="text-xs text-gray-500 mt-1"><i class="fas fa-hourglass-half mr-1"></i> Active</div>`;
        }
        
        const actionButtonsHTML = `
            <button class="text-app-blue hover:text-app-blue-darkest edit-task-btn p-2" title="Edit Task"><i class="fas fa-edit"></i></button>
            <button class="text-orange-500 hover:text-orange-700 duplicate-task-btn p-2" title="Duplicate Task"><i class="fas fa-copy"></i></button>
            <button class="text-red-500 hover:text-red-700 delete-task-btn p-2" title="Delete Task"><i class="fas fa-trash"></i></button>
        `;

        const taskElementHTML = `
            <div class="${taskCardClasses}" data-task-id="${task.id}">
                <div class="flex items-center mb-2 sm:mb-0 w-full sm:w-auto flex-grow">
                    <img src="${task.image_url || `https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(task.title.substring(0,1).toUpperCase())}`}" alt="${utils.escapeHTML(task.title)}" class="w-10 h-10 rounded-md mr-3 object-cover" onerror="this.src='https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(task.title.substring(0,1).toUpperCase())}'">
                    <div class="flex-grow">
                        <h4 class="${titleClasses}">${utils.escapeHTML(task.title)}${familyTaskVisual}</h4>
                        ${notesHTML} <p class="text-sm text-gray-500">Due: ${utils.formatDate(task.due_date)} | Points: ${task.points} | For: ${assignedName}</p>
                        ${repeatIndicator}
                        ${statusTextForDisplay} 
                    </div>
                </div>
                <div class="flex space-x-2 self-end sm:self-center mt-2 sm:mt-0 flex-shrink-0">
                   ${actionButtonsHTML} 
                </div>
            </div>
        `;
        dom.taskListContainer.insertAdjacentHTML('beforeend', taskElementHTML);
    });

    // IMPORTANT: Re-attach listeners to the new elements after rendering
    dom.taskListContainer.querySelectorAll('.edit-task-btn').forEach(btn => {
        btn.removeEventListener('click', handleEditTask); // Prevent duplicates
        btn.addEventListener('click', handleEditTask);
    });
    dom.taskListContainer.querySelectorAll('.duplicate-task-btn').forEach(btn => {
        btn.removeEventListener('click', handleDuplicateTask); // Prevent duplicates
        btn.addEventListener('click', handleDuplicateTask);
    });
    dom.taskListContainer.querySelectorAll('.delete-task-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteTask); // Prevent duplicates
        btn.addEventListener('click', handleDeleteTask);
    });
}

// NEW: Centralized handlers for task actions to ensure single listener attachment
function handleEditTask(e) {
    editTask(e.currentTarget.closest('.card').dataset.taskId);
}

function handleDuplicateTask(e) {
    duplicateTask(e.currentTarget.closest('.card').dataset.taskId);
}

function handleDeleteTask(e) {
    deleteTask(e.currentTarget.closest('.card').dataset.taskId);
}

// Function to adjust child points (moved here to be accessible)
async function adjustChildPoints(childId, type) {
    const inputElement = document.querySelector(`.child-points-input[data-child-id="${childId}"]`);
    if (!inputElement) return;

    let pointsChange = parseInt(inputElement.value);

    if (isNaN(pointsChange) || pointsChange <= 0) {
        ui.showMessage("Input Error", "Please enter a positive number for points adjustment.", "error");
        return;
    }

    if (type === 'subtract') {
        pointsChange = -pointsChange; // Make it negative for subtraction
    }

    try {
        const data = await apiRequest('children.php?action=update_points', 'POST', {
            child_id: childId,
            points_change: pointsChange
        });

        if (data.success) {
            ui.showMessage("Success", data.message, "success");
            inputElement.value = ''; // Clear input after successful adjustment
            // Find the child in state and update their points
            const childToUpdate = state.childrenData.find(c => c.id.toString() === childId.toString());
            if (childToUpdate) {
                childToUpdate.points = data.new_points;
                // Directly update the displayed points without re-rendering the whole list for a smoother UX
                const pointsDisplayElement = document.querySelector(`.current-child-points[data-child-id="${childId}"]`);
                if (pointsDisplayElement) {
                    pointsDisplayElement.textContent = data.new_points;
                }
            }
            // Also refresh notifications, as a new one might have been created
            await loadParentContextData(); 
            updateParentNotifications(); // Update the sidebar alert
            renderNotificationsPage(); // Update the full notifications page if open

        }
    } catch (error) {
        // apiRequest already handles showing a modal.
        console.error("Failed to adjust child points:", error);
    }
}


function renderParentChildren() {
    dom.childrenListContainer.innerHTML = '';
    if (state.childrenData.length === 0) {
        dom.childrenListContainer.innerHTML = `<p class="text-gray-500 p-4 text-center">No children added yet.</p>`;
        return;
    }
    state.childrenData.forEach(child => {
        const childElementHTML = `
            <div class="card flex flex-col sm:flex-row justify-between items-start sm:items-center" data-child-id="${child.id}">
                <div class="flex items-center mb-2 sm:mb-0 flex-grow">
                    <img src="${child.profile_pic_url || `https://placehold.co/50x50/cccccc/969696?text=${utils.escapeHTML(child.name.substring(0,1).toUpperCase())}`}" alt="${utils.escapeHTML(child.name)}" class="w-12 h-12 rounded-full mr-4 object-cover" onerror="this.src='https://placehold.co/50x50/cccccc/969696?text=P'">
                    <div class="flex-grow">
                        <h4 class="font-semibold text-lg">${utils.escapeHTML(child.name)}</h4>
                        <p class="text-sm text-gray-500">Points: <span class="current-child-points font-bold text-yellow-600" data-child-id="${child.id}">${child.points}</span> | Joined: ${utils.formatDate(child.joinedDate)}</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 self-end sm:self-center mt-2 sm:mt-0 flex-shrink-0">
                    <div class="flex items-center space-x-1">
                        <input type="number" placeholder="Amt" class="input-field-sm w-20 text-center child-points-input" data-child-id="${child.id}">
                        <button class="btn btn-secondary btn-sm add-points-btn" data-child-id="${child.id}" title="Add Points"><i class="fas fa-plus"></i></button>
                        <button class="btn btn-neutral btn-sm subtract-points-btn" data-child-id="${child.id}" title="Subtract Points"><i class="fas fa-minus"></i></button>
                    </div>
                    <button class="text-app-blue hover:text-app-blue-darkest edit-child-btn p-1" title="Edit Child"><i class="fas fa-edit"></i> Edit</button>
                    <button class="text-red-500 hover:text-red-700 delete-child-btn p-1" title="Delete Child"><i class="fas fa-user-times"></i> Delete</button>
                </div>
            </div>
        `;
        dom.childrenListContainer.insertAdjacentHTML('beforeend', childElementHTML);
    });
    // IMPORTANT: Remove previous event listeners before adding new ones
    dom.childrenListContainer.querySelectorAll('.edit-child-btn').forEach(btn => {
        btn.removeEventListener('click', handleEditChild);
        btn.addEventListener('click', handleEditChild);
    });
    dom.childrenListContainer.querySelectorAll('.delete-child-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteChild);
        btn.addEventListener('click', handleDeleteChild);
    });
    
    // Add event listeners for point adjustment buttons
    dom.childrenListContainer.querySelectorAll('.add-points-btn').forEach(btn => {
        btn.removeEventListener('click', handleAdjustChildPointsAdd); // Prevent duplicates
        btn.addEventListener('click', handleAdjustChildPointsAdd);
    });
    dom.childrenListContainer.querySelectorAll('.subtract-points-btn').forEach(btn => {
        btn.removeEventListener('click', handleAdjustChildPointsSubtract); // Prevent duplicates
        btn.addEventListener('click', handleAdjustChildPointsSubtract);
    });
}

// NEW: Centralized handlers for child actions
function handleEditChild(e) {
    editChild(e.currentTarget.closest('.card').dataset.childId);
}

function handleDeleteChild(e) {
    deleteChild(e.currentTarget.closest('.card').dataset.childId);
}

function handleAdjustChildPointsAdd(e) {
    adjustChildPoints(e.currentTarget.dataset.childId, 'add');
}

function handleAdjustChildPointsSubtract(e) {
    adjustChildPoints(e.currentTarget.dataset.childId, 'subtract');
}

function populateChildCheckboxes(container) { // Made this function local to parent.js, not exported
    container.innerHTML = '';
    if (state.childrenData.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500">No children have been added yet.</p>';
        return;
    }
    state.childrenData.forEach(child => {
        const label = document.createElement('label');
        label.innerHTML = `
            <input type="checkbox" name="assignedChildren" class="form-checkbox" value="${child.id}">
            <img src="${child.profile_pic_url || `https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(child.name.substring(0,1).toUpperCase())}`}" alt="${utils.escapeHTML(child.name)}" onerror="this.src='https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(child.name.substring(0,1).toUpperCase())}'">
            <span>${utils.escapeHTML(child.name)}</span>
        `;
        container.appendChild(label);
    });
}

function renderIconLibrary() {
    const grid = document.getElementById('iconLibraryGrid');
    if (!grid) return;
    grid.innerHTML = '';
    
    ICON_LIBRARY.forEach(icon => {
        const img = document.createElement('img');
        img.src = icon.url;
        img.alt = utils.escapeHTML(icon.name);
        img.dataset.url = icon.url;
        img.className = "w-full h-auto object-cover";
        
        // IMPORTANT: Remove previous event listener before adding a new one
        img.removeEventListener('click', handleIconSelect);
        img.addEventListener('click', handleIconSelect);
        grid.appendChild(img);
    });
}

// NEW: Centralized handler for icon selection
function handleIconSelect(e) {
    const icon = e.currentTarget;
    dom.taskForm.taskImageURL.value = icon.dataset.url;
    document.getElementById('taskImagePreview').src = icon.dataset.url;
    dom.taskForm.taskImage.value = ''; 
    document.querySelectorAll('#iconLibraryGrid img').forEach(i => i.classList.remove('selected'));
    icon.classList.add('selected');
    document.getElementById('iconLibraryModal').classList.add('hidden');
}


function editTask(taskId) {
    const task = state.tasksData.find(t => t.id.toString() === taskId.toString());
    if (task) {
        dom.taskForm.reset();
        dom.taskModalTitle.textContent = "Edit Task";
        dom.taskForm.taskId.value = task.id;
        dom.taskForm.taskImageURL.value = task.image_url || '';
        document.getElementById('taskImagePreview').src = task.image_url || 'https://placehold.co/80x80/e5e7eb/a0aec0?text=Icon';
        dom.taskForm.taskTitle.value = task.title;
        dom.taskForm.taskNotes.value = task.notes || '';
        dom.taskForm.taskDueDate.value = task.due_date;
        dom.taskForm.taskPoints.value = task.points;
        const isFamily = !!task.is_family_task;
        dom.taskIsFamilyCheckbox.checked = isFamily;

        const childCheckboxes = dom.taskAssignToContainer.querySelectorAll('input[type="checkbox"]');
        childCheckboxes.forEach(checkbox => {
            checkbox.checked = false; 
            if (task.assigned_children_ids && task.assigned_children_ids.includes(parseInt(checkbox.value))) {
                checkbox.checked = true;
            }
            checkbox.disabled = isFamily;
        });

        dom.taskForm.taskRepeat.value = task.repeat_type;

        const repeatDaysContainer = document.getElementById('taskRepeatDaysContainer');
        const dayCheckboxes = repeatDaysContainer.querySelectorAll('.weekday-checkbox');
        dayCheckboxes.forEach(cb => cb.checked = false);
        
        if (task.repeat_type === 'daily' || task.repeat_type === 'custom_days') { 
            repeatDaysContainer.classList.remove('hidden');
            if (task.repeat_on_days) {
                const days = task.repeat_on_days.split(',');
                days.forEach(day => {
                    const checkbox = document.getElementById(`weekday-${day}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } else {
            repeatDaysContainer.classList.add('hidden');
        }
        
        ui.showModal(dom.taskModal);
    }
}

function duplicateTask(taskId) {
    const taskToDuplicate = state.tasksData.find(t => t.id.toString() === taskId.toString());
    if (taskToDuplicate) {
        dom.taskForm.reset();
        dom.taskModalTitle.textContent = "Duplicate Task";
        dom.taskForm.taskId.value = '';
        dom.taskForm.taskImageURL.value = taskToDuplicate.image_url || '';
        document.getElementById('taskImagePreview').src = taskToDuplicate.image_url || 'https://placehold.co/80x80/e5e7eb/a0aec0?text=Icon';
        dom.taskForm.taskTitle.value = taskToDuplicate.title;
        dom.taskForm.taskNotes.value = taskToDuplicate.notes || '';
        dom.taskForm.taskDueDate.value = taskToDuplicate.due_date;
        dom.taskForm.taskPoints.value = taskToDuplicate.points;
        const isFamily = !!taskToDuplicate.is_family_task;
        dom.taskIsFamilyCheckbox.checked = isFamily;
        
        const childCheckboxes = dom.taskAssignToContainer.querySelectorAll('input[type="checkbox"]');
        childCheckboxes.forEach(checkbox => {
            checkbox.checked = false; 
            if (taskToDuplicate.assigned_children_ids && taskToDuplicate.assigned_children_ids.includes(parseInt(checkbox.value))) {
                checkbox.checked = true;
            }
            checkbox.disabled = isFamily;
        });
        
        dom.taskForm.taskRepeat.value = taskToDuplicate.repeat_type;
        // Ensure repeat days container visibility matches duplicated task's repeat type
        const repeatDaysContainer = document.getElementById('taskRepeatDaysContainer');
        const dayCheckboxes = repeatDaysContainer.querySelectorAll('.weekday-checkbox');
        dayCheckboxes.forEach(cb => cb.checked = false); // Clear all days first
        if (taskToDuplicate.repeat_type === 'daily' || taskToDuplicate.repeat_type === 'custom_days') {
            repeatDaysContainer.classList.remove('hidden');
            if (taskToDuplicate.repeat_on_days) {
                const days = taskToDuplicate.repeat_on_days.split(',');
                days.forEach(day => {
                    const checkbox = document.getElementById(`weekday-${day}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } else {
            repeatDaysContainer.classList.add('hidden');
        }
        ui.showModal(dom.taskModal);
    }
}

async function deleteTask(taskId) {
    const taskToDelete = state.tasksData.find(t => t.id.toString() === taskId.toString());
    if (!taskToDelete) return;

    if (confirm(`Are you sure you want to delete the task "${taskToDelete.title}"?`)) {
        try {
            const data = await apiRequest(`tasks.php?action=delete`, 'POST', { id: taskId });
            if (data.success) {
                await loadParentContextData();
                renderParentTasks(document.querySelector('.filter-task-btn.active')?.dataset.filter || 'active');
            }
        } catch (error) { /* Handled */ }
    }
}


async function loadSettings() {
    try {
        const data = await apiRequest('parent_settings.php?action=get_settings');
        if(data.success && data.settings) {
            appSettings.notifyForOverdue = data.settings.enable_overdue_task_notifications;
            appSettings.autoDeleteCompletedTasks = data.settings.auto_delete_completed_tasks;
            appSettings.autoDeleteNotifications = data.settings.auto_delete_notifications;
            dom.notifyOverdueCheckbox.checked = appSettings.notifyForOverdue;
            dom.autoDeleteCompletedTasksCheckbox.checked = appSettings.autoDeleteCompletedTasks;
            dom.autoDeleteNotificationsCheckbox.checked = appSettings.autoDeleteNotifications;
        }
        const hasPin = state.loggedInParentUser?.has_pin_set || false;
        if(hasPin) {
            dom.parentPinInput.disabled = true;
            dom.parentPinInput.value = "";
            dom.parentPinInput.placeholder = "****";
            dom.setParentPinBtn.classList.add('hidden');
            dom.clearParentPinBtn.classList.remove('hidden');
            dom.parentPinMessage.textContent = "A PIN is set. Clear it to set a new one.";
        } else {
            dom.parentPinInput.disabled = false;
            dom.parentPinInput.value = '';
            dom.parentPinInput.placeholder = "Enter 4-digit PIN";
            dom.setParentPinBtn.classList.remove('hidden');
            dom.clearParentPinBtn.classList.add('hidden');
            dom.parentPinMessage.textContent = "Secure parent dashboard access.";
        }
    } catch (error) {
        console.error("Could not load parent settings:", error);
        ui.showMessage("Error", "Could not load your settings from the server.", "error");
    }
}

async function updateSettingOnBackend(settingName, value) {
    try {
        const payload = { [settingName]: value };
        const data = await apiRequest('parent_settings.php?action=update_settings', 'POST', payload);
        if (data.success) {
            console.log(`Setting '${settingName}' updated successfully.`);
            if (appSettings.hasOwnProperty(settingName)) {
                 appSettings[settingName] = value;
            } else if (settingName === 'enable_overdue_task_notifications') {
                 appSettings.notifyForOverdue = value;
            }
        }
    } catch (error) {
        console.error(`Failed to update setting ${settingName}:`, error);
        loadSettings();
    }
}

export function initializeParentDashboard(callbacks) {
    loadParentContextData = callbacks.loadParentContextData;
    
    // Clear interval when initializing (e.g., if switching from child dashboard)
    if (refreshIntervalId) {
        clearInterval(refreshIntervalId);
        refreshIntervalId = null;
    }

    // Only attach these general event listeners once
    if (!areParentFormListenersInitialized) {
        dom.parentNavLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = e.currentTarget.dataset.target;
                ui.showParentSection(targetId);
                
                switch (targetId) {
                    case 'overview': renderParentOverview(); break;
                    case 'manageTasks': renderParentTasks(); break;
                    case 'manageRewards': renderParentRewards(); break; // MODIFIED: Call from rewards.js
                    case 'manageChildren': renderParentChildren(); break;
                    case 'settings': loadSettings(); break;
                    case 'notifications': state.currentNotificationPage = 1; renderNotificationsPage(); break;
                }
            });
        });

        dom.parentHomeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (refreshIntervalId) clearInterval(refreshIntervalId); 
            ui.showPage(dom.landingPage);
        });

        dom.parentLogoutBtn.addEventListener('click', async () => {
            console.log("Parent logout button clicked.");
            if (refreshIntervalId) clearInterval(refreshIntervalId); 
            try {
                await apiRequest('auth.php?action=logout', 'POST');
            }
            // No need for finally block here, as the callback will handle state reset
            catch (error) {
                console.warn("Logout API call failed, but logging out frontend anyway.", error);
            } finally {
                callbacks.onLogoutSuccess();
            }
        });

        dom.parentSettingsBtn.addEventListener('click', () => { ui.showParentSection('settings'); loadSettings(); });
        dom.parentMenuToggleBtn.addEventListener('click', () => { dom.parentSidebarNav.classList.toggle('hidden'); });
        dom.closeTaskModal.addEventListener('click', () => dom.taskModal.classList.add('hidden'));
        dom.closeRewardModal.addEventListener('click', () => dom.rewardModal.classList.add('hidden')); 
        dom.closeChildModal.addEventListener('click', () => dom.childModal.classList.add('hidden'));

        const taskRepeatDropdown = document.getElementById('taskRepeat');
        const taskRepeatDaysContainer = document.getElementById('taskRepeatDaysContainer');
        if(taskRepeatDropdown) {
            taskRepeatDropdown.addEventListener('change', (e) => {
                if (e.target.value === 'daily' || e.target.value === 'custom_days') {
                    taskRepeatDaysContainer.classList.remove('hidden');
                } else {
                    taskRepeatDaysContainer.classList.add('hidden');
                }
            });
        }

        document.getElementById('chooseFromLibraryBtn').addEventListener('click', () => {
            ui.showModal(document.getElementById('iconLibraryModal'), false); 
        });

        document.getElementById('closeIconLibraryModal').addEventListener('click', () => {
            document.getElementById('iconLibraryModal').classList.add('hidden');
        });

        dom.taskForm.taskImage.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('taskImagePreview').src = event.target.result;
                }
                reader.readAsDataURL(file);
                document.querySelectorAll('#iconLibraryGrid img').forEach(i => i.classList.remove('selected'));
                dom.taskForm.taskImageURL.value = '';
            }
        });

        dom.addNewTaskBtn.addEventListener('click', () => {
            dom.taskForm.reset();
            dom.taskModalTitle.textContent = "Add New Task";
            dom.taskForm.taskId.value = '';
            dom.taskForm.taskImageURL.value = '';
            document.getElementById('taskImagePreview').src = 'https://placehold.co/80x80/e5e7eb/a0aec0?text=Icon';
            dom.taskForm.taskDueDate.value = utils.getTodayDateString();
            dom.taskForm.taskRepeat.value = 'none';
            document.getElementById('taskRepeatDaysContainer').classList.add('hidden');
            document.querySelectorAll('.weekday-checkbox').forEach(cb => cb.checked = false);
            dom.taskIsFamilyCheckbox.checked = false;
            const childCheckboxes = dom.taskAssignToContainer.querySelectorAll('input[type="checkbox"]');
            childCheckboxes.forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            ui.showModal(dom.taskModal);
        });
        
        dom.taskIsFamilyCheckbox.addEventListener('change', (e) => {
            const isChecked = e.currentTarget.checked;
            const childCheckboxes = dom.taskAssignToContainer.querySelectorAll('input[type="checkbox"]');
            childCheckboxes.forEach(checkbox => {
                checkbox.disabled = isChecked;
                if(isChecked) {
                    checkbox.checked = false;
                }
            });
        });

        dom.taskFilterButtons.forEach(btn => {
            // IMPORTANT: Remove existing listener before adding to prevent duplicates
            btn.removeEventListener('click', handleTaskFilter); 
            btn.addEventListener('click', handleTaskFilter);
        });

        // NEW: Centralized handler for task filter buttons
        function handleTaskFilter(e) {
            dom.taskFilterButtons.forEach(b => b.classList.remove('active', 'btn-primary'));
            e.currentTarget.classList.add('active', 'btn-primary');
            renderParentTasks(e.currentTarget.dataset.filter);
        }

        // IMPORTANT: Remove existing listener before adding to prevent duplicates
        dom.taskForm.removeEventListener('submit', handleTaskFormSubmit);
        dom.taskForm.addEventListener('submit', handleTaskFormSubmit);
        
        dom.addNewChildBtn.addEventListener('click', () => {
            dom.childForm.reset();
            dom.childModalTitle.textContent = "Add New Child";
            dom.childForm.childId.value = '';
            dom.childForm.childImageURL.value = '';
            ui.showModal(dom.childModal);
        });
        
        // IMPORTANT: Remove existing listener before adding to prevent duplicates
        dom.childForm.removeEventListener('submit', handleChildFormSubmit);
        dom.childForm.addEventListener('submit', handleChildFormSubmit);

        // Settings Listeners
        dom.syncDateTimeBtn.removeEventListener('click', updateCurrentTime); // Prevent duplicates
        dom.syncDateTimeBtn.addEventListener('click', updateCurrentTime);

        // For PIN and auto-delete settings, re-add listeners carefully if they need to be dynamic
        // or ensure their handlers manage state properly. For now, assuming they are set up elsewhere
        // or their handlers are idempotent.
        // If these are also causing duplicates, apply the remove/add pattern.
        dom.setParentPinBtn.removeEventListener('click', handleSetParentPin);
        dom.setParentPinBtn.addEventListener('click', handleSetParentPin);
        dom.clearParentPinBtn.removeEventListener('click', handleClearParentPin);
        dom.clearParentPinBtn.addEventListener('click', handleClearParentPin);
        dom.autoDeleteCompletedTasksCheckbox.removeEventListener('change', handleAutoDeleteCompletedTasksChange);
        dom.autoDeleteCompletedTasksCheckbox.addEventListener('change', handleAutoDeleteCompletedTasksChange);
        dom.autoDeleteNotificationsCheckbox.removeEventListener('change', handleAutoDeleteNotificationsChange);
        dom.autoDeleteNotificationsCheckbox.addEventListener('change', handleAutoDeleteNotificationsChange);
        dom.notifyOverdueCheckbox.removeEventListener('change', handleNotifyOverdueChange);
        dom.notifyOverdueCheckbox.addEventListener('change', handleNotifyOverdueChange);
        
        dom.markAllNotificationsReadBtn.removeEventListener('click', handleMarkAllNotificationsRead);
        dom.markAllNotificationsReadBtn.addEventListener('click', handleMarkAllNotificationsRead);

        areParentFormListenersInitialized = true; // Set flag to true after initialization
    } // End of !areParentFormListenersInitialized block


    // Initial render call and setup auto-refresh
    if (state.loggedInParentUser) {
        ui.showParentSection('overview');
        renderParentOverview(); // Initial render
        populateChildCheckboxes(dom.taskAssignToContainer); // Call it here
        renderIconLibrary();

        // Set up auto-refresh for parent dashboard
        refreshIntervalId = setInterval(async () => {
            if (dom.parentDashboardPage.offsetParent !== null) { // Check if element is visible
                console.log("Auto-refreshing parent dashboard data...");
                await loadParentContextData();
                renderParentOverview(); // Re-render overview to update counts and notifications
            } else {
                clearInterval(refreshIntervalId);
                refreshIntervalId = null;
            }
        }, 30000); // 30 seconds
    }
}

// NEW: Centralized handler for task form submission
async function handleTaskFormSubmit(e) {
    e.preventDefault();

    const taskTitleValue = dom.taskForm.taskTitle.value.trim();
    const words = taskTitleValue.split(/\s+/).filter(word => word.length > 0);
    if (words.length > 3) {
        ui.showMessage("Input Error", "Task Title must be 3 words or less.", "error");
        return;
    }

    const fileInput = dom.taskForm.taskImage;
    let imageUrl = dom.taskForm.taskImageURL.value;
    if (fileInput.files.length > 0) {
        const uploadedUrl = await utils.uploadImage(fileInput);
        if (uploadedUrl === null) return;
        imageUrl = uploadedUrl;
    }
    const taskId = dom.taskForm.taskId.value;
    let repeatOnDays = null;
    if (dom.taskForm.taskRepeat.value === 'daily' || dom.taskForm.taskRepeat.value === 'custom_days') {
        const selectedDays = Array.from(document.querySelectorAll('.weekday-checkbox:checked')).map(cb => cb.value);
        if (selectedDays.length > 0) {
            repeatOnDays = selectedDays.join(',');
        }
    }
    const assignedChildren = Array.from(dom.taskAssignToContainer.querySelectorAll('input[name="assignedChildren"]:checked'))
        .map(checkbox => checkbox.value);
    
    const payload = {
        title: dom.taskForm.taskTitle.value,
        dueDate: dom.taskForm.taskDueDate.value,
        points: parseInt(dom.taskForm.taskPoints.value),
        image: imageUrl,
        notes: dom.taskForm.taskNotes.value,
        is_family_task: dom.taskIsFamilyCheckbox.checked,
        assigned_children_ids: assignedChildren,
        repeat: dom.taskForm.taskRepeat.value,
        repeat_on_days: repeatOnDays
    };
    if(taskId) payload.id = taskId;
    try {
        const action = taskId ? 'update' : 'create';
        await apiRequest(`tasks.php?action=${action}`, 'POST', payload);
        await loadParentContextData();
        renderParentTasks(document.querySelector('.filter-task-btn.active')?.dataset.filter || 'active');
        dom.taskModal.classList.add('hidden');
    } catch (error) { /* Handled */ }
}

// NEW: Centralized handler for child form submission
async function handleChildFormSubmit(e) {
    e.preventDefault();
    const fileInput = dom.childForm.childProfilePic;
    let imageUrl = dom.childForm.childImageURL.value;
    if (fileInput.files.length > 0) {
        const uploadedUrl = await utils.uploadImage(fileInput);
        if (uploadedUrl === null) return;
        imageUrl = uploadedUrl;
    }
    const childId = dom.childForm.childId.value;
    const payload = {
        name: dom.childForm.childName.value,
        profilePic: imageUrl,
    };
    if(childId) payload.id = childId;
    try {
        const action = childId ? 'update' : 'create';
        await apiRequest(`children.php?action=${action}`, 'POST', payload);
        await loadParentContextData();
        renderParentChildren();
        populateChildCheckboxes(dom.taskAssignToContainer);
        renderParentTasks();
    } catch (error) { /* Handled */ }
}

// NEW: Centralized handlers for settings actions
async function handleSetParentPin() {
    // Implement PIN setting logic here or call a separate function
    // For now, this is a placeholder to show where the listener is managed.
    const pin = dom.parentPinInput.value;
    if (!pin || pin.length !== 4 || isNaN(pin)) {
        ui.showMessage("Input Error", "PIN must be exactly 4 digits.", "error");
        return;
    }
    try {
        const data = await apiRequest('parent_settings.php?action=set_pin', 'POST', { pin: pin });
        if (data.success) {
            ui.showMessage("Success", "PIN set successfully!", "success");
            loadSettings(); // Reload settings to update UI
        }
    } catch (error) { /* Handled by apiRequest */ }
}

async function handleClearParentPin() {
    if (confirm("Are you sure you want to clear your parent PIN?")) {
        try {
            const data = await apiRequest('parent_settings.php?action=clear_pin', 'POST');
            if (data.success) {
                ui.showMessage("Success", "PIN cleared successfully!", "success");
                loadSettings(); // Reload settings to update UI
            }
        } catch (error) { /* Handled by apiRequest */ }
    }
}

function handleAutoDeleteCompletedTasksChange(e) {
    updateSettingOnBackend('auto_delete_completed_tasks', e.target.checked);
}

function handleAutoDeleteNotificationsChange(e) {
    updateSettingOnBackend('auto_delete_notifications', e.target.checked);
}

function handleNotifyOverdueChange(e) {
    updateSettingOnBackend('enable_overdue_task_notifications', e.target.checked);
}

async function handleMarkAllNotificationsRead() {
    try {
        const data = await apiRequest('notifications.php?action=mark_all_read', 'POST');
        if (data.success) {
            await loadParentContextData();
            renderNotificationsPage();
            updateUnreadNotificationBadge();
            updateParentNotifications();
            ui.showMessage("Success", "All notifications have been marked as read.", "success");
        }
    } catch (error) {
        console.error("Failed to mark all notifications as read:", error);
    }
}