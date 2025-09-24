// This file contains the global configuration and state variables for the app.

// Configuration
export const API_BASE_URL = 'backend_chores_quest/api/';
export const NOTIFICATIONS_PER_PAGE = 10;
export const COMPLETED_TASKS_PER_PAGE = 10;

// Global State (we use an object to make it mutable across modules)
export const state = {
    loggedInParentUser: null,
    activeKidProfile: null,
    childrenData: [],
    tasksData: [],
    rewardsData: [],
    notificationsData: [],
    loginRedirectTarget: null,
    isParentPinVerifiedInSession: false,
    currentNotificationPage: 1,
    currentCompletedTaskPage: 1,
};

// App Settings (defaults)
export const appSettings = {
    parentPin: null,
    notifyForOverdue: true,
    autoDeleteCompletedTasks: false,
    autoDeleteCompletedTasksDays: 30,
    autoDeleteNotifications: false,
    autoDeleteNotificationsDays: 30,
};

// Enum for task repeat types (for consistency across frontend)
export const TASK_REPEAT_TYPES = {
    NONE: 'none',
    DAILY: 'daily',
    WEEKLY: 'weekly',
    MONTHLY: 'monthly',
    CUSTOM_DAYS: 'custom_days' // NEW: Added custom_days option
};