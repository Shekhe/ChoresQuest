<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chores Quest Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css"> 
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div id="adminApp" class="min-h-screen flex flex-col items-center justify-center p-4">

        <section id="adminLoginSection" class="admin-page active w-full max-w-md bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold text-center text-blue-600 mb-6">Admin Login</h1>
            <form id="adminLoginForm" class="space-y-4">
                <div>
                    <label for="adminUsername" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="adminUsername" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <div>
                    <label for="adminPassword" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="adminPassword" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <p id="adminLoginError" class="text-red-600 text-sm hidden">Invalid credentials.</p>
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Login</button>
            </form>
        </section>

        <section id="adminDashboardSection" class="admin-page hidden w-full max-w-6xl bg-white p-8 rounded-lg shadow-lg">
            <header class="flex justify-between items-center mb-6 border-b pb-4">
                <h1 class="text-3xl font-bold text-blue-600">Admin Dashboard</h1>
                <button id="adminLogoutBtn" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </header>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Total Parents</h2>
                    <p id="totalParentsCount" class="text-4xl font-bold text-blue-500">0</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Total Children</h2>
                    <p id="totalChildrenCount" class="text-4xl font-bold text-green-500">0</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Total Tasks</h2>
                    <p id="totalTasksCount" class="text-4xl font-bold text-orange-500">0</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow md:col-span-2 lg:col-span-3">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Manage Users</h2>
                    <div id="adminUsersList" class="space-y-4">
                        <p class="text-gray-500">Loading users...</p>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <div id="adminModalOverlay" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center p-4 hidden">
        
        <div id="changeUsernameModal" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm hidden">
            <h2 class="text-2xl font-semibold text-center text-blue-600 mb-6">Change Username</h2>
            <p class="text-sm text-gray-600 mb-4">Changing username for: <strong id="currentUsernameDisplay"></strong></p>
            <form id="changeUsernameForm" class="space-y-4">
                <input type="hidden" id="changeUsernameUserId">
                <div>
                    <label for="newUsername" class="block text-sm font-medium text-gray-700">New Username</label>
                    <input type="text" id="newUsername" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                </div>
                <p id="changeUsernameError" class="text-red-600 text-sm hidden"></p>
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700">Update Username</button>
                <button type="button" id="closeChangeUsernameModal" class="w-full bg-gray-300 text-gray-800 p-2 rounded-md hover:bg-gray-400">Cancel</button>
            </form>
        </div>

        <div id="resetPasswordModal" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm hidden">
            <h2 class="text-2xl font-semibold text-center text-blue-600 mb-6">Reset Password</h2>
            <p class="text-sm text-gray-600 mb-4">Resetting password for: <strong id="resetPasswordUsernameDisplay"></strong></p>
            <form id="resetPasswordForm" class="space-y-4">
                <input type="hidden" id="resetPasswordUserIdHidden">
                <div>
                    <label for="newPassword" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" id="newPassword" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required minlength="6">
                </div>
                <div>
                    <label for="confirmNewPassword" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" id="confirmNewPassword" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required minlength="6">
                </div>
                <p id="resetPasswordError" class="text-red-600 text-sm hidden"></p>
                <button type="submit" class="w-full bg-orange-600 text-white p-2 rounded-md hover:bg-orange-700">Reset Password</button>
                <button type="button" id="closeResetPasswordModal" class="w-full bg-gray-300 text-gray-800 p-2 rounded-md hover:bg-gray-400">Cancel</button>
            </form>
        </div>

        <div id="generateRecoveryCodeModal" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md hidden">
            <h2 class="text-2xl font-semibold text-center text-blue-600 mb-6">Generate New Recovery Code</h2>
            <p class="text-sm text-gray-600 mb-4">Generating a new code for: <strong id="recoveryCodeUsernameDisplay"></strong></p>
            <p class="text-sm text-red-600 font-semibold mb-4">WARNING: This will invalidate any old recovery codes for this user. Ensure the user saves this new code securely.</p>
            <input type="hidden" id="generateRecoveryCodeUserId">
            <div class="bg-gray-100 p-4 rounded-lg mb-6 text-center">
                <p class="text-2xl font-mono tracking-widest text-gray-800" id="generatedRecoveryCodeDisplay">Generating...</p>
                <button type="button" id="copyGeneratedRecoveryCodeBtn" class="bg-blue-200 text-blue-800 text-sm py-1 px-3 rounded-full mt-2 hover:bg-blue-300"><i class="fas fa-copy mr-1"></i> Copy</button>
            </div>
            <p id="generateRecoveryCodeError" class="text-red-600 text-sm hidden"></p>
            <button type="button" id="confirmGenerateRecoveryCodeBtn" class="w-full bg-green-600 text-white p-2 rounded-md hover:bg-green-700">Confirm & Copy (Parent View)</button>
            <button type="button" id="closeGenerateRecoveryCodeModal" class="w-full bg-gray-300 text-gray-800 p-2 rounded-md hover:bg-gray-400 mt-2">Close</button>
        </div>

        <div id="setPinModal" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm hidden">
            <h2 class="text-2xl font-semibold text-center text-blue-600 mb-6">Set/Clear Parent PIN</h2>
            <p class="text-sm text-gray-600 mb-4">Setting PIN for: <strong id="setPinUsernameDisplay"></strong></p>
            <form id="setPinForm" class="space-y-4">
                <input type="hidden" id="setPinUserId">
                <div>
                    <label for="newPin" class="block text-sm font-medium text-gray-700">New 4-Digit PIN (Leave blank to clear)</label>
                    <input type="password" id="newPin" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-center text-xl tracking-widest" maxlength="4" pattern="\d{4}">
                </div>
                <p id="setPinError" class="text-red-600 text-sm hidden"></p>
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700">Set PIN</button>
                <button type="button" id="closeSetPinModal" class="w-full bg-gray-300 text-gray-800 p-2 rounded-md hover:bg-gray-400">Cancel</button>
            </form>
        </div>

        <div id="deleteUserConfirmModal" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm hidden">
            <h2 class="text-2xl font-semibold text-center text-red-600 mb-6">Confirm User Deletion</h2>
            <p class="text-base text-gray-700 mb-4">Are you absolutely sure you want to delete <strong id="deleteConfirmUsernameDisplay"></strong>?</p>
            <p class="text-sm text-red-600 font-semibold mb-6">WARNING: This action is irreversible. All associated children, tasks, rewards, and notifications for this user will be permanently deleted.</p>
            <input type="hidden" id="deleteConfirmUserId">
            <button type="button" id="confirmDeleteUserBtn" class="w-full bg-red-600 text-white p-2 rounded-md hover:bg-red-700">Yes, Delete This User</button>
            <button type="button" id="cancelDeleteUserBtn" class="w-full bg-gray-300 text-gray-800 p-2 rounded-md hover:bg-gray-400 mt-2">Cancel</button>
        </div>

    </div>
    
    <script src="js/admin_main.js" type="module" defer></script>
</body>
</html>