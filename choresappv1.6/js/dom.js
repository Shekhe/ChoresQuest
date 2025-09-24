// This file contains all references to DOM elements.

export const landingPage = document.getElementById('landingPage');
export const parentBtn = document.getElementById('parentBtn');
export const kidsBtn = document.getElementById('kidsBtn');
export const getStartedLink = document.getElementById('getStartedLink');
export const loginLink = document.getElementById('loginLink');

export const authSection = document.getElementById('authSection');
export const signUpModal = document.getElementById('signUpModal');
export const loginModal = document.getElementById('loginModal');
export const closeSignUpModal = document.getElementById('closeSignUpModal');
export const closeLoginModal = document.getElementById('closeLoginModal');
export const switchToLoginLink = document.getElementById('switchToLoginLink');
export const switchToSignUpLink = document.getElementById('switchToSignUpLink');
export const signUpForm = document.getElementById('signUpForm');
export const loginForm = document.getElementById('loginForm');

export const signUpUsername = document.getElementById('signUpUsername');
export const loginUsername = document.getElementById('loginUsername');

// Recovery Modal Elements
export const recoverAccountLink = document.getElementById('recoverAccountLink');
export const recoveryCodeModal = document.getElementById('recoveryCodeModal');
export const recoveryCodeDisplay = document.getElementById('recoveryCodeDisplay');
export const copyRecoveryCodeBtn = document.getElementById('copyRecoveryCodeBtn');
export const finishRegistrationBtn = document.getElementById('finishRegistrationBtn');
export const accountRecoveryModal = document.getElementById('accountRecoveryModal');
export const closeRecoveryModal = document.getElementById('closeRecoveryModal');
export const recoveryCodeForm = document.getElementById('recoveryCodeForm');
export const resetPasswordForm = document.getElementById('resetPasswordForm');
export const recoveryStep1 = document.getElementById('recoveryStep1');
export const recoveryStep2 = document.getElementById('recoveryStep2');
export const recoveryUsernameDisplay = document.getElementById('recoveryUsernameDisplay');
export const resetPasswordUserId = document.getElementById('resetPasswordUserId');

// Policy Modal Elements
export const policyModal = document.getElementById('policyModal');
export const policyModalTitle = document.getElementById('policyModalTitle');
export const policyModalContentArea = document.getElementById('policyModalContentArea');
export const closePolicyModalBtn = document.getElementById('closePolicyModalBtn');
export const landingPrivacyLink = document.getElementById('landingPrivacyLink');
export const landingTermsLink = document.getElementById('landingTermsLink'); // FIX: Corrected typo here
export const signUpPrivacyLink = document.getElementById('signUpPrivacyLink');
export const signUpTermsLink = document.getElementById('signUpTermsLink');

// Parent Dashboard Elements
export const parentDashboardPage = document.getElementById('parentDashboardPage');
export const parentLogoutBtn = document.getElementById('parentLogoutBtn');
export const parentHomeBtn = document.getElementById('parentHomeBtn');
export const parentMenuToggleBtn = document.getElementById('parentMenuToggleBtn');
export const parentSidebarNav = document.getElementById('parentSidebarNav');
export const parentNavLinks = document.querySelectorAll('.parent-nav');
export const parentSections = document.querySelectorAll('.parent-section');
export const parentSettingsBtn = document.getElementById('parentSettingsBtn');
export const parentNotificationArea = document.getElementById('parentNotificationArea');
export const unreadNotificationCountBadge = document.getElementById('unreadNotificationCountBadge');
export const recentActivityList = document.getElementById('recentActivityList');
export const totalChildrenCount = document.getElementById('totalChildrenCount');
export const activeTasksCount = document.getElementById('activeTasksCount');
export const overdueTasksCount = document.getElementById('overdueTasksCount');

// Task Elements
export const addNewTaskBtn = document.getElementById('addNewTaskBtn');
export const taskModal = document.getElementById('taskModal');
export const closeTaskModal = document.getElementById('closeTaskModal');
export const taskForm = document.getElementById('taskForm');
export const taskModalTitle = document.getElementById('taskModalTitle');
export const taskListContainer = document.getElementById('taskList');
export const taskFilterButtons = document.querySelectorAll('.filter-task-btn');
export const filterBtnActive = document.getElementById('filterBtnActive');
export const filterBtnOverdue = document.getElementById('filterBtnOverdue');
export const filterBtnArchived = document.getElementById('filterBtnArchived');
export const taskIsFamilyCheckbox = document.getElementById('taskIsFamily');
export const taskAssignToContainer = document.getElementById('taskAssignToContainer');
export const taskNotes = document.getElementById('taskNotes');

// Reward Elements
export const addNewRewardBtn = document.getElementById('addNewRewardBtn');
export const rewardModal = document.getElementById('rewardModal');
export const closeRewardModal = document.getElementById('closeRewardModal');
export const rewardForm = document.getElementById('rewardForm');
export const rewardModalTitle = document.getElementById('rewardModalTitle');
export const rewardListContainer = document.getElementById('rewardList');

// Child Management Elements
export const addNewChildBtn = document.getElementById('addNewChildBtn');
export const childModal = document.getElementById('childModal');
export const closeChildModal = document.getElementById('closeChildModal');
export const childForm = document.getElementById('childForm');
export const childModalTitle = document.getElementById('childModalTitle');
export const childrenListContainer = document.getElementById('childrenList');
export const adjustPointsInput = document.getElementById('adjustPointsInput');
export const addPointsBtn = document.getElementById('addPointsBtn');
export const subtractPointsBtn = document.getElementById('subtractPointsBtn');


// Settings Page Elements
export const parentPinInput = document.getElementById('parentPinInput');
export const setParentPinBtn = document.getElementById('setParentPinBtn');
export const clearParentPinBtn = document.getElementById('clearParentPinBtn');
export const parentPinMessage = document.getElementById('parentPinMessage');
export const autoDeleteCompletedTasksCheckbox = document.getElementById('autoDeleteCompletedTasks');
export const autoDeleteNotificationsCheckbox = document.getElementById('autoDeleteNotifications');
export const notifyOverdueCheckbox = document.getElementById('notifyOverdue');
export const syncDateTimeBtn = document.getElementById('syncDateTimeBtn');
export const currentTimeDisplay = document.getElementById('currentTimeDisplay');

// Notifications Page Elements
export const fullNotificationList = document.getElementById('fullNotificationList');
export const markAllNotificationsReadBtn = document.getElementById('markAllNotificationsReadBtn');
export const notificationPaginationControls = document.getElementById('notificationPaginationControls');

// PIN Entry Modal Elements
export const pinEntryModal = document.getElementById('pinEntryModal');
export const pinEntryForm = document.getElementById('pinEntryForm');
export const pinInput = document.getElementById('pinInput');
export const pinErrorMessage = document.getElementById('pinErrorMessage');
export const cancelPinEntryBtn = document.getElementById('cancelPinEntryBtn');

// Kids Zone Elements
export const kidsZonePage = document.getElementById('kidsZonePage');
export const kidProfileSelection = document.getElementById('kidProfileSelection');
export const childDashboard = document.getElementById('childDashboard');
export const childLogoutBtn = document.getElementById('childLogoutBtn');
export const childDashboardLogoutBtn = document.getElementById('childDashboardLogoutBtn');
export const kidProfilesContainer = document.getElementById('kidProfilesContainer');
export const childWelcomeName = document.getElementById('childWelcomeName');
export const childPointsDisplay = document.getElementById('childPoints');
export const childMyTasksList = document.getElementById('childMyTasksList');
export const childFamilyTasksList = document.getElementById('childFamilyTasksList');
export const childProfilePicHeader = document.getElementById('childProfilePicHeader');
export const viewRewardsBtn = document.getElementById('viewRewardsBtn');
export const kidRewardsModal = document.getElementById('kidRewardsModal');
export const closeKidRewardsModal = document.getElementById('closeKidRewardsModal');
export const kidRewardsList = document.getElementById('kidRewardsList');
export const kidModalPoints = document.getElementById('kidModalPoints');
export const childCompletedTasksList = document.getElementById('childCompletedTasksList');
export const childCompletedPaginationControls = document.getElementById('childCompletedPaginationControls');

// Generic Message Modal
export const messageModal = document.getElementById('messageModal');
export const messageModalTitle = document.getElementById('messageModalTitle');
export const messageModalText = document.getElementById('messageModalText');
export const messageModalCloseBtn = document.getElementById('messageModalCloseBtn');

// New Button for Child Dashboard
export const backToLandingBtn = document.getElementById('backToLandingBtn');