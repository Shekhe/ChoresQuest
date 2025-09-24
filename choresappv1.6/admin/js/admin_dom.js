// admin/js/admin_dom.js - DOM element references for the Admin Panel

export const adminLoginSection = document.getElementById('adminLoginSection');
export const adminLoginForm = document.getElementById('adminLoginForm');
export const adminUsername = document.getElementById('adminUsername');
export const adminPassword = document.getElementById('adminPassword');
export const adminLoginError = document.getElementById('adminLoginError');

export const adminDashboardSection = document.getElementById('adminDashboardSection');
export const adminLogoutBtn = document.getElementById('adminLogoutBtn');

export const totalParentsCount = document.getElementById('totalParentsCount');
export const totalChildrenCount = document.getElementById('totalChildrenCount');
export const totalTasksCount = document.getElementById('totalTasksCount');
export const adminUsersList = document.getElementById('adminUsersList');

// Generic page/modal handling (simple for admin for now)
export const adminPages = document.querySelectorAll('.admin-page');

// NEW: Admin User Management Modals and their elements
export const adminModalOverlay = document.getElementById('adminModalOverlay');

export const changeUsernameModal = document.getElementById('changeUsernameModal');
export const currentUsernameDisplay = document.getElementById('currentUsernameDisplay');
export const changeUsernameForm = document.getElementById('changeUsernameForm');
export const changeUsernameUserId = document.getElementById('changeUsernameUserId');
export const newUsername = document.getElementById('newUsername');
export const changeUsernameError = document.getElementById('changeUsernameError');
export const closeChangeUsernameModal = document.getElementById('closeChangeUsernameModal');

export const resetPasswordModal = document.getElementById('resetPasswordModal');
export const resetPasswordUsernameDisplay = document.getElementById('resetPasswordUsernameDisplay');
export const resetPasswordForm = document.getElementById('resetPasswordForm');
export const resetPasswordUserIdHidden = document.getElementById('resetPasswordUserIdHidden');
export const newPassword = document.getElementById('newPassword');
export const confirmNewPassword = document.getElementById('confirmNewPassword');
export const resetPasswordError = document.getElementById('resetPasswordError');
export const closeResetPasswordModal = document.getElementById('closeResetPasswordModal');

export const generateRecoveryCodeModal = document.getElementById('generateRecoveryCodeModal');
export const recoveryCodeUsernameDisplay = document.getElementById('recoveryCodeUsernameDisplay');
export const generateRecoveryCodeUserId = document.getElementById('generateRecoveryCodeUserId');
export const generatedRecoveryCodeDisplay = document.getElementById('generatedRecoveryCodeDisplay');
export const copyGeneratedRecoveryCodeBtn = document.getElementById('copyGeneratedRecoveryCodeBtn');
export const generateRecoveryCodeError = document.getElementById('generateRecoveryCodeError');
export const confirmGenerateRecoveryCodeBtn = document.getElementById('confirmGenerateRecoveryCodeBtn');
export const closeGenerateRecoveryCodeModal = document.getElementById('closeGenerateRecoveryCodeModal');

// NEW: Set PIN Modal elements
export const setPinModal = document.getElementById('setPinModal');
export const setPinUsernameDisplay = document.getElementById('setPinUsernameDisplay');
export const setPinForm = document.getElementById('setPinForm');
export const setPinUserId = document.getElementById('setPinUserId');
export const newPin = document.getElementById('newPin');
export const setPinError = document.getElementById('setPinError');
export const closeSetPinModal = document.getElementById('closeSetPinModal');

// NEW: Delete User Confirmation Modal elements
export const deleteUserConfirmModal = document.getElementById('deleteUserConfirmModal');
export const deleteConfirmUsernameDisplay = document.getElementById('deleteConfirmUsernameDisplay');
export const deleteConfirmUserId = document.getElementById('deleteConfirmUserId');
export const confirmDeleteUserBtn = document.getElementById('confirmDeleteUserBtn');
export const cancelDeleteUserBtn = document.getElementById('cancelDeleteUserBtn');