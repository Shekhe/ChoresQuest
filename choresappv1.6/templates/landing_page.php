<?php // templates/landing_page.php ?>

<div id="landingPage" class="page active text-center space-y-8 w-full max-w-md">
    <header class="mb-8 sm:mb-12">
        <img src="imgs/logo.png" alt="Chores Quest Logo" class="mx-auto w-44 md:w-60">
    </header>
    <div class="space-y-4">
        <button id="parentBtn" class="btn btn-parent btn-landing w-full text-lg sm:text-xl"> 
            <i class="fas fa-user-shield mr-3"></i> <span>I'm a Parent</span>
        </button>
        <button id="kidsBtn" class="btn btn-kid btn-landing w-full text-lg sm:text-xl">
            <i class="fas fa-child mr-3"></i> <span>I'm a Child</span>
        </button>
        <!-- PWA Install Button -->

        <button id="installAppBtn" class="btn w-full text-lg sm:text-xl" style="display: none;">
            <i class="fas fa-download mr-3"></i> <span>Add Shortcut to Home screen</span>
        </button>

        <div id="iosInstallInstructions" class="p-3 mt-4 bg-sky-100 border border-sky-300 rounded-lg text-sm text-gray-700 text-left" style="display: none;">
            <p class="font-semibold text-center pb-2">Add Shortcut to Home screen</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Tap the <img src="https/upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Apple_iOS_7_share_icon.svg/1024px-Apple_iOS_7_share_icon.svg.png" class="inline h-4 w-4 mx-1 text-gray-700" alt="Share Icon"> button below.</li>
                <li>Scroll down and tap 'Add to Home Screen'.</li>
                <li>Tap 'Add' in the top-right corner.</li>
            </ol>
        </div>

    </div>
    <div class="mt-10 text-sm text-gray-500">
        <p>New here? <a href="#" id="getStartedLink" class="text-sky-600 hover:underline">Get Started</a></p>
        <p>Already have an account? <a href="#" id="loginLink" class="text-sky-600 hover:underline">Login</a></p>
    </div>
    <footer class="mt-12 text-xs text-gray-400">
        <a href="#" id="landingPrivacyLink" class="hover:underline">Privacy Policy</a> &bull; 
        <a href="#" id="landingTermsLink" class="hover:underline">Terms of Use</a>
        <p>If you have any questions or concerns, please contact us at <a href="mailto:support@choresquest.com">support@choresquest.com</a>.</p>
        <a href="description.html" id="landingTermsLink" class="hover:underline">App Overview</a>
    </footer>
</div>
