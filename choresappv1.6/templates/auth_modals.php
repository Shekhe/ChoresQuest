<?php // templates/auth_modals.php ?>

<div id="authSection" class="hidden">
    <!-- Sign Up Modal -->
    <div id="signUpModal" class="modal hidden">
        <div class="modal-content max-w-md"> 
            <h2 class="text-2xl font-semibold mb-6 text-center text-sky-600">Create Your Account</h2> 
            <form id="signUpForm" class="space-y-4">
                <div>
                    <label for="signUpName" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="signUpName" class="input-field" required>
                </div>
                <div>
                    <label for="signUpUsername" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="signUpUsername" class="input-field" required>
                </div>
                <div>
                    <label for="signUpPassword" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="signUpPassword" class="input-field" required>
                </div>
                <div>
                    <label for="signUpConfirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" id="signUpConfirmPassword" class="input-field" required>
                </div>
                <p class="mt-4 text-xs text-center text-gray-500">
                    By signing up, you agree to our 
                    <a href="#" id="signUpTermsLink" class="text-sky-600 hover:underline">Terms of Use</a> and 
                    <a href="#" id="signUpPrivacyLink" class="text-sky-600 hover:underline">Privacy Policy</a>.
                </p>
                <button type="submit" class="btn btn-primary w-full">Sign Up</button>
            </form>

            <p class="mt-4 text-sm text-center">Already have an account? <a href="#" id="switchToLoginLink" class="text-sky-600 hover:underline">Login</a></p>
            <button id="closeSignUpModal" class="btn btn-neutral w-full mt-2">Cancel</button>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal hidden">
        <div class="modal-content max-w-md"> 
            <h2 class="text-2xl font-semibold mb-6 text-center text-sky-600">Login to Chores Quest</h2> 
            <form id="loginForm" class="space-y-4">
                <div>
                    <label for="loginUsername" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="loginUsername" class="input-field" required>
                </div>
                <div>
                    <label for="loginPassword" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="loginPassword" class="input-field" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Login</button>
            </form>
            <div class="mt-4 text-sm text-center space-y-2">
                <p>New here? <a href="#" id="switchToSignUpLink" class="text-sky-600 hover:underline">Sign Up</a></p>
                <p>Forgot Password? <a href="#" id="recoverAccountLink" class="text-red-500 hover:underline">Recover Account</a></p>
            </div>
            <button id="closeLoginModal" class="btn btn-neutral w-full mt-2">Cancel</button>
        </div>
    </div>
</div>
