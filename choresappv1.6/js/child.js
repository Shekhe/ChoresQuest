// This file contains all logic for the Child's Zone.

import { apiRequest } from './api.js';
import * as dom from './dom.js';
import * as ui from './ui.js';
import { state, COMPLETED_TASKS_PER_PAGE } from './config.js';
import * as utils from './utils.js'; 

let onSwitchUser; // Callback to switch back to profile selection
let onLogout; // Callback for full logout
let loadParentContextData; // Callback to refresh data

let refreshIntervalId = null;


function createChildTaskCardHTML(task, isCompleted = false, completionDate = null) {
    let taskCardClasses = "card flex flex-row justify-between items-center";
    let titleClasses = "font-semibold";
    let taskStatusText = ''; 
    
    const noteHTML = task.notes 
        ? `<p class="text-xs text-gray-600 mt-1 pl-2 border-l-2 border-gray-300 italic">${utils.escapeHTML(task.notes)}</p>` 
        : '';

    let actionButtonHTML;
    if (isCompleted) {
        taskCardClasses += " border-l-4 border-app-blue opacity-70 bg-app-blue-light";
        titleClasses += " text-sky-600";
        taskStatusText = `Points: ${task.points} | Completed on ${utils.formatDateTime(completionDate)}`;
        actionButtonHTML = `
            <div class="text-app-blue text-2xl flex items-center justify-center rounded-full w-10 h-10" title="Completed">
                <i class="fas fa-check-circle"></i>
            </div>`;
    } else {
        const today = utils.getTodayDateString();
        if (task.repeat_type === 'none') {
            taskStatusText = `Due: ${utils.formatDate(task.due_date)} | Points: ${task.points}`;
            if (utils.isTaskOverdue(task)) {
                taskCardClasses += " border-l-4 border-red-500 highlight-red-bg";
                titleClasses += " highlight-red";
                taskStatusText = `<span class="highlight-red"><i class="fas fa-exclamation-triangle mr-1"></i>OVERDUE</span> | ${taskStatusText}`;
            }
        } else { 
            taskStatusText = `Points: ${task.points} | Repeats: ${task.repeat_type.charAt(0).toUpperCase() + task.repeat_type.slice(1)}`;
            if (task.repeat_on_days) {
                taskStatusText += ` (${utils.formatDays(task.repeat_on_days)})`; 
            }
            if (utils.isTaskOverdue(task)) {
                taskCardClasses += " border-l-4 border-red-500 highlight-red-bg";
                titleClasses += " highlight-red";
                taskStatusText = `<span class="highlight-red"><i class="fas fa-exclamation-triangle mr-1"></i>OVERDUE</span> | ${taskStatusText}`;
            } else if (task.due_date === today) {
                taskCardClasses += " border-l-4 border-app-blue bg-app-blue-light";
                titleClasses += " text-app-blue"; 
                taskStatusText = `<span class="text-app-blue font-semibold"><i class="fas fa-calendar-day mr-1"></i>DUE TODAY</span> | ${taskStatusText}`;
            }
        }

        actionButtonHTML = `
            <button class="mark-task-done text-2xl p-1 rounded-full w-10 h-10 flex items-center justify-center focus:outline-none
                             ${utils.isTaskOverdue(task) ? 'text-orange-400 hover:text-orange-600 focus:ring-2 focus:ring-orange-300' : 'text-gray-400 hover:text-app-blue focus:ring-2 focus:ring-sky-300'}"
                     data-task-id="${task.id}"
                     data-points="${task.points}"
                     title="Mark as Done">
                <i class="far fa-circle"></i>
            </button>`;
    }

    const familyTaskVisual = task.is_family_task ? `<span class="text-xs highlight-purple ml-2">(<i class="fas fa-users"></i> Family)</span>` : '';

    return `
        <div class="${taskCardClasses}" data-task-id="${task.id}">
            <div class="flex items-center flex-grow mr-3"> 
                <img src="${task.image_url || `https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(task.title.substring(0,1).toUpperCase())}`}" alt="${utils.escapeHTML(task.title)}" class="w-10 h-10 rounded-md mr-3 object-cover flex-shrink-0" onerror="this.src='https://placehold.co/40x40/cccccc/969696?text=${utils.escapeHTML(task.title.substring(0,1).toUpperCase())}'">
                <div class="flex-grow min-w-0"> 
                    <h4 class="${titleClasses} truncate">${utils.escapeHTML(task.title)}${familyTaskVisual}</h4> 
                    ${noteHTML}
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">${taskStatusText}</p>
                </div>
            </div>
            <div class="flex-shrink-0 flex items-center justify-center">
               ${actionButtonHTML}
            </div>
        </div>
    `;
}


function renderChildCompletedPagination(totalCompletedTasks) {
    dom.childCompletedPaginationControls.innerHTML = '';
    const totalPages = Math.ceil(totalCompletedTasks / COMPLETED_TASKS_PER_PAGE);
    if (totalPages <= 1) return;

    const prevButton = document.createElement('button');
    prevButton.innerHTML = '&laquo; Prev';
    prevButton.className = 'btn btn-neutral btn-sm';
    prevButton.disabled = state.currentCompletedTaskPage === 1;
    prevButton.addEventListener('click', () => {
        if (state.currentCompletedTaskPage > 1) {
            state.currentCompletedTaskPage--;
            renderChildDashboard(state.activeKidProfile);
        }
    });
    dom.childCompletedPaginationControls.appendChild(prevButton);

    const pageInfo = document.createElement('span');
    pageInfo.className = 'text-sm text-gray-600 px-4';
    pageInfo.textContent = `Page ${state.currentCompletedTaskPage} of ${totalPages}`;
    dom.childCompletedPaginationControls.appendChild(pageInfo);

    const nextButton = document.createElement('button');
    nextButton.innerHTML = 'Next &raquo;';
    nextButton.className = 'btn btn-neutral btn-sm';
    nextButton.disabled = state.currentCompletedTaskPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (state.currentCompletedTaskPage < totalPages) {
            state.currentCompletedTaskPage++;
            renderChildDashboard(state.activeKidProfile);
        }
    });
    dom.childCompletedPaginationControls.appendChild(nextButton);
}

function renderChildCompletedTasks(completedTasksWithDates) {
    dom.childCompletedTasksList.innerHTML = '';
    if (completedTasksWithDates.length === 0) {
        dom.childCompletedTasksList.innerHTML = '<p class="text-gray-500 p-4 text-center">You haven\'t completed any tasks yet.</p>';
        dom.childCompletedPaginationControls.innerHTML = '';
        return;
    }
    const startIndex = (state.currentCompletedTaskPage - 1) * COMPLETED_TASKS_PER_PAGE;
    const endIndex = startIndex + COMPLETED_TASKS_PER_PAGE;
    const paginatedItems = completedTasksWithDates.slice(startIndex, endIndex);

    paginatedItems.forEach(item => {
        dom.childCompletedTasksList.insertAdjacentHTML('beforeend', createChildTaskCardHTML(item.task, true, item.completed_at));
    });
    renderChildCompletedPagination(completedTasksWithDates.length);
}

async function markTaskAsDoneByKid(taskId, pointsAwarded) {
    if (!state.activeKidProfile) return;
    try {
        const data = await apiRequest('tasks.php?action=mark_done_by_kid', 'POST', {
            task_id: taskId,
            kid_id: state.activeKidProfile.id
        });
        if (data.success) {
            state.currentCompletedTaskPage = 1; 
            await loadParentContextData(); 
            const refreshedKidProfileData = state.childrenData.find(c => c.id.toString() === state.activeKidProfile.id.toString());
            if (refreshedKidProfileData) {
                state.activeKidProfile.points = refreshedKidProfileData.points;
            }
            renderChildDashboard(state.activeKidProfile);
            ui.showMessage("Task Completed!", `You earned ${pointsAwarded} points!`, "success");
        }
    } catch (error) { /* Handled by apiRequest which shows a modal */ }
}


// MODIFIED FUNCTION: renderChildTaskLists
async function renderChildTaskLists(kid, childCompletions) {
    if (!kid || !kid.parent_user_id) { return; }

    dom.childMyTasksList.innerHTML = '<p class="text-gray-500 p-4 text-center">Loading your tasks...</p>';
    dom.childFamilyTasksList.innerHTML = '<p class="text-gray-500 p-4 text-center">Loading family tasks...</p>';

    const allTasksForParent = state.tasksData.filter(task => task.parent_user_id.toString() === kid.parent_user_id.toString());
    const today = utils.getTodayDateString();

    const activePersonalTasks = [];
    const activeFamilyTasks = [];
    const allCompletedByMe = []; 

    allTasksForParent.forEach(task => {
        const isFamily = !!task.is_family_task;

        // Determine if this specific child has completed THIS task instance (for the current period for recurring)
        const myCompletionsForThisTask = childCompletions.filter(c => c.task_id === task.id);
        const hasBeenCompletedByMeForCurrentPeriod = myCompletionsForThisTask.some(c => {
            if (task.repeat_type !== 'none') {
                 // For recurring, completion date should be >= task's current due_date for it to count for this period
                return c.completed_at.substring(0, 10) >= task.due_date;
            } else {
                // For one-time tasks, any completion by this child means it's done for them
                return true; 
            }
        });

        // Add to allCompletedByMe array for rendering in the 'Completed Tasks' section
        myCompletionsForThisTask.forEach(completion => {
            allCompletedByMe.push({ task, completed_at: completion.completed_at });
        });

        // --- NEW/MODIFIED LOGIC FOR ACTIVE TASK LISTS ---
        // Conditions for a task to be displayed in 'My Tasks' or 'Family Tasks' section:
        // 1. Task status must be 'active'.
        // 2. This specific child must NOT have completed it for the current period.
        // 3. For Family Tasks: No family member must have completed it for the current period (checked via task.completion_count).
        // 4. For recurring tasks (both personal and family): It must be due today or overdue.
        //    (One-time tasks show if active, regardless of due date relative to today, if not overdue and not completed)

        if (task.status === 'active' && !hasBeenCompletedByMeForCurrentPeriod) {
            if (isFamily) {
                // For Family Tasks, check if ANY family member has completed it for this period.
                // task.completion_count is provided by the backend from `completion_count_for_period`
                const hasAnyFamilyMemberCompletedItForCurrentPeriod = task.completion_count > 0;

                if (!hasAnyFamilyMemberCompletedItForCurrentPeriod) {
                     // Only add if no one has completed it for this cycle
                    if (task.repeat_type === 'none') {
                        // One-time family tasks that are active
                        activeFamilyTasks.push(task);
                    } else if (task.due_date <= today) {
                        // Recurring family tasks that are active and due today or overdue
                        activeFamilyTasks.push(task);
                    }
                }
            } else {
                // Personal Tasks
                const isAssignedToMe = task.assigned_children_ids.includes(kid.id);
                if (isAssignedToMe) {
                    if (task.repeat_type === 'none') {
                        // One-time personal tasks that are active
                        activePersonalTasks.push(task);
                    } else if (task.due_date <= today) {
                        // Recurring personal tasks that are active and due today or overdue
                        activePersonalTasks.push(task);
                    }
                }
            }
        }
    });

    dom.childMyTasksList.innerHTML = activePersonalTasks.length === 0 
        ? '<p class="text-gray-500 p-4 text-center">No tasks for you to do right now.</p>'
        : activePersonalTasks.map(task => createChildTaskCardHTML(task, false)).join('');

    dom.childFamilyTasksList.innerHTML = activeFamilyTasks.length === 0
        ? '<p class="text-gray-500 p-4 text-center">No family tasks available right now.</p>'
        : activeFamilyTasks.map(task => createChildTaskCardHTML(task, false)).join('');
    
    // Render the list of all tasks this child has ever completed (paginated)
    // Sort by most recent completion
    const sortedCompletedByMe = allCompletedByMe
        .sort((a,b) => new Date(b.completed_at) - new Date(a.completed_at)); 
    
    renderChildCompletedTasks(sortedCompletedByMe); 
    
    // Add event listeners to the "mark done" buttons
    // IMPORTANT: Clear previous listeners to prevent duplicates
    dom.childMyTasksList.querySelectorAll('.mark-task-done').forEach(button => {
        button.removeEventListener('click', handleMarkTaskDone); 
        button.addEventListener('click', handleMarkTaskDone); 
    });
    dom.childFamilyTasksList.querySelectorAll('.mark-task-done').forEach(button => {
        button.removeEventListener('click', handleMarkTaskDone); 
        button.addEventListener('click', handleMarkTaskDone); 
    });
}

// Centralized handler for mark task done to prevent duplicates
function handleMarkTaskDone(e) {
    const taskId = e.currentTarget.dataset.taskId;
    const points = e.currentTarget.dataset.points;
    // Disable the button immediately to prevent double clicks
    e.currentTarget.disabled = true; 
    markTaskAsDoneByKid(taskId, points);
}


export async function renderChildDashboard(kid) {
    if (refreshIntervalId) {
        clearInterval(refreshIntervalId);
    }

    if (!kid) return;

    await loadParentContextData(); 

    state.activeKidProfile = kid;
    dom.childWelcomeName.textContent = `${kid.name}!`;

    const childDataFromGlobal = state.childrenData.find(c => c.id.toString() === kid.id.toString());
    if (childDataFromGlobal) {
        kid.points = childDataFromGlobal.points;
        state.activeKidProfile.points = kid.points;
    }
    dom.childPointsDisplay.innerHTML = `${kid.points} <i class="fas fa-star"></i>`;

    if (dom.childProfilePicHeader) {
        dom.childProfilePicHeader.src = kid.profile_pic_url || `https://placehold.co/40x40/ffffff/333333?text=${utils.escapeHTML(kid.name.substring(0,1).toUpperCase())}`;
        dom.childProfilePicHeader.alt = utils.escapeHTML(kid.name);
        dom.childProfilePicHeader.onerror = function() {
            this.src = `https://placehold.co/40x40/ffffff/333333?text=${utils.escapeHTML(kid.name.substring(0,1).toUpperCase())}`;
        };
    }
    
    try {
        const completionData = await apiRequest(`tasks.php?action=get_completions_for_child&child_id=${kid.id}`);
        const completions = completionData.success ? completionData.completions : [];
        renderChildTaskLists(kid, completions);
    } catch(error) {
        console.error("Could not fetch child's task completions", error);
        renderChildTaskLists(kid, []);
    }

    // Set a new interval, but only if the child dashboard is visible
    refreshIntervalId = setInterval(async () => {
        if (dom.childDashboard.offsetParent !== null) { 
            console.log("Auto-refreshing child dashboard data...");
            await loadParentContextData();
            
            const currentKidInState = state.childrenData.find(c => c.id.toString() === state.activeKidProfile.id.toString());
            if (currentKidInState) {
                state.activeKidProfile.points = currentKidInState.points;
                dom.childPointsDisplay.innerHTML = `${currentKidInState.points} <i class="fas fa-star"></i>`;
            }
            
            // Fetch completions again for the most up-to-date view
            const completionData = await apiRequest(`tasks.php?action=get_completions_for_child&child_id=${kid.id}`, 'GET', null, { suppressModalForErrorMessages: ["Notification not found or already read."] });
            if (completionData && completionData.success) {
                renderChildTaskLists(kid, completionData.completions);
            }
        } else {
            // If child dashboard is no longer visible, clear the interval
            clearInterval(refreshIntervalId);
            refreshIntervalId = null;
        }
    }, 30000); 
}


export function renderKidProfileSelection() {
    dom.kidProfilesContainer.innerHTML = '';
    if (state.childrenData.length === 0) {
        dom.kidProfilesContainer.innerHTML = '<p class="text-gray-500 col-span-full p-4 text-center">No child profiles found.</p>';
        return;
    }

    state.childrenData.forEach(kid => {
        const profileCard = `
            <div class="kid-profile-card card items-center cursor-pointer hover:shadow-xl transition-shadow p-4 text-center" data-kid-id="${kid.id}">
                <img src="${kid.profile_pic_url || `https://placehold.co/100x100/cccccc/969696?text=${utils.escapeHTML(kid.name.substring(0,1).toUpperCase())}`}" alt="${utils.escapeHTML(kid.name)}" class="w-24 h-24 rounded-full mb-3 mx-auto object-cover" onerror="this.src='https://placehold.co/100x100/cccccc/969696?text=${utils.escapeHTML(kid.name.substring(0,1).toUpperCase())}'">
                <h3 class="text-xl font-semibold text-gray-700">${utils.escapeHTML(kid.name)}</h3>
            </div>`;
        dom.kidProfilesContainer.insertAdjacentHTML('beforeend', profileCard);
    });

    document.querySelectorAll('.kid-profile-card').forEach(card => {
        card.addEventListener('click', (e) => {
            const selectedKidId = e.currentTarget.dataset.kidId;
            const selectedKidObject = state.childrenData.find(c => c.id.toString() === selectedKidId);
            if (selectedKidObject) {
                state.activeKidProfile = { ...selectedKidObject, type: 'kid' };
                localStorage.setItem('lastActiveChildId', selectedKidId);
                localStorage.setItem('lastActiveParentIdForChild', selectedKidObject.parent_user_id);
                state.currentCompletedTaskPage = 1; 
                dom.kidProfileSelection.classList.add('hidden');
                dom.childDashboard.classList.remove('hidden');
                renderChildDashboard(state.activeKidProfile);
            }
        });
    });
}

async function claimRewardByKid(rewardId) {
    if (!state.activeKidProfile) return;
    try {
        const data = await apiRequest('rewards.php?action=claim_by_kid', 'POST', {
            reward_id: rewardId,
            kid_id: state.activeKidProfile.id
        });
        if (data.success) {
            const rewardToClaim = state.rewardsData.find(r => r.id.toString() === rewardId.toString());
            await loadParentContextData();
            const refreshedKidProfileData = state.childrenData.find(c => c.id.toString() === state.activeKidProfile.id.toString());
            if (refreshedKidProfileData) {
                state.activeKidProfile.points = refreshedKidProfileData.points;
            }
            renderChildDashboard(state.activeKidProfile);
            ui.showModal(dom.kidRewardsModal);
            dom.kidModalPoints.innerHTML = `${state.activeKidProfile.points} <i class="fas fa-star"></i>`;
            ui.showMessage("Reward Claimed!", `You spent ${rewardToClaim.required_points} points. Enjoy!`, "success");
        }
    } catch (error) { /* Handled */ }
}

export function initializeChildDashboard(callbacks) {
    onSwitchUser = callbacks.onSwitchUser;
    onLogout = callbacks.onLogout;
    loadParentContextData = callbacks.loadParentContextData;

    dom.childLogoutBtn.addEventListener('click', () => {
        clearInterval(refreshIntervalId);
        onSwitchUser();
    });
    
    dom.childDashboardLogoutBtn.addEventListener('click', () => {
        clearInterval(refreshIntervalId);
        onLogout();
    });

    dom.backToLandingBtn.addEventListener('click', () => {
        clearInterval(refreshIntervalId);
        state.activeKidProfile = null;
        localStorage.removeItem('lastActiveChildId');
        localStorage.removeItem('lastActiveParentIdForChild');
        ui.showPage(dom.landingPage);
    });


    dom.viewRewardsBtn.addEventListener('click', async () => {
        if (!state.activeKidProfile) return;
        dom.kidModalPoints.innerHTML = `${state.activeKidProfile.points} <i class="fas fa-star"></i>`;
        dom.kidRewardsList.innerHTML = '';
        state.rewardsData
            .filter(r => r.parent_user_id.toString() === state.activeKidProfile.parent_user_id.toString() && r.is_active) // MODIFIED: Filter by is_active
            .sort((a,b) => a.required_points - b.required_points)
            .forEach(reward => {
                const canAfford = state.activeKidProfile.points >= reward.required_points;
                const rewardElementHTML = `
                    <div class="card ${!canAfford ? 'opacity-60' : ''}">
                        <img src="${reward.image_url || `https://placehold.co/300x200/cccccc/969696?text=R`}" alt="${utils.escapeHTML(reward.title)}" class="w-full h-32 object-cover rounded-md mb-3" onerror="this.src='https://placehold.co/300x200/cccccc/969696?text=R'">
                        <h4 class="font-semibold">${utils.escapeHTML(reward.title)}</h4>
                        <p class="text-sm text-yellow-600 font-semibold"><i class="fas fa-star mr-1"></i>${reward.required_points} Points</p>
                        <button class="btn btn-secondary w-full mt-2 claim-reward-btn" data-reward-id="${reward.id}" ${!canAfford ? 'disabled' : ''}>
                            ${canAfford ? 'Claim Reward' : 'Not enough points'}
                        </button>
                    </div>`; 
                dom.kidRewardsList.insertAdjacentHTML('beforeend', rewardElementHTML);
            });
        dom.kidRewardsList.querySelectorAll('.claim-reward-btn').forEach(button => {
            button.addEventListener('click', (e) => claimRewardByKid(e.currentTarget.dataset.rewardId));
        });
        ui.showModal(dom.kidRewardsModal);
    });

    dom.closeKidRewardsModal.addEventListener('click', () => ui.hideAllModals());
}