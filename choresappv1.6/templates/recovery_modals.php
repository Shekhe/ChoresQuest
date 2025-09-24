<?php // templates/recovery_modals.php ?>

<!-- Recovery Code Display Modal (after signup) -->
<div id="recoveryCodeModal" class="modal hidden">
    <div class="modal-content max-w-md text-center">
        <h2 class="text-2xl font-semibold mb-4 text-sky-600">Save Your Recovery Code!</h2>
        <p class="text-gray-700 mb-4">This is the <strong class="text-red-600">ONLY</strong> time you will see this code. Please write it down and keep it in a safe place. You will need it to recover your account if you forget your password.</p>
        <div class="bg-gray-100 p-4 rounded-lg mb-6">
            <p class="text-2xl font-mono tracking-widest text-gray-800" id="recoveryCodeDisplay"></p>
        </div>
        <button id="copyRecoveryCodeBtn" class="btn btn-secondary w-full mb-2"><i class="fas fa-copy mr-2"></i>Copy Code</button>
        <button id="finishRegistrationBtn" class="btn btn-primary w-full">I have saved my code. Continue.</button>
    </div>
</div>

<!-- Account Recovery Flow Modal (for forgotten password) -->
<div id="accountRecoveryModal" class="modal hidden">
    <div class="modal-content max-w-md">
        <h2 class="text-2xl font-semibold mb-6 text-center text-sky-600">Account Recovery</h2>
        <div id="recoveryStep1">
            <form id="recoveryCodeForm" class="space-y-4">
                <p class="text-sm text-gray-600">Enter your recovery code to begin the password reset process.</p>
                <div>
                    <label for="recoveryCodeInput" class="block text-sm font-medium text-gray-700">Recovery Code</label>
                    <input type="text" id="recoveryCodeInput" class="input-field font-mono" placeholder="XXXX-XXXX-XXXX" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Verify Code</button>
            </form>
        </div>
        <div id="recoveryStep2" class="hidden">
            <form id="resetPasswordForm" class="space-y-4">
                <p class="text-sm text-gray-600">Your recovery code has been verified. You can now set a new password for your account: <strong id="recoveryUsernameDisplay"></strong></p>
                <input type="hidden" id="resetPasswordUserId">
                <div>
                    <label for="resetPasswordInput" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" id="resetPasswordInput" class="input-field" required>
                </div>
                <div>
                    <label for="resetConfirmPasswordInput" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" id="resetConfirmPasswordInput" class="input-field" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Reset Password</button>
            </form>
        </div>
        <button id="closeRecoveryModal" class="btn btn-neutral w-full mt-4">Cancel</button>
    </div>
</div>
