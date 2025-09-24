<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chores Quest</title>

    <link rel="manifest" href="/manifest.json">
    
    <meta name="theme-color" content="#0ea5e9">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="/imgs/favicon.png">
</head>
<body class="text-gray-800">

    <div id="app" class="min-h-screen flex flex-col items-center justify-center p-4">

        <?php 
            include 'templates/landing_page.php';
            include 'templates/auth_modals.php';
            include 'templates/policy_modal.php';
            include 'templates/recovery_modals.php';
            include 'templates/parent_dashboard.php';
            include 'templates/kids_zone.php';
            include 'templates/message_modals.php';
        ?>

    </div> 

    <script>
        // PWA Installation Logic
        let deferredPrompt;
        const installAppBtn = document.getElementById('installAppBtn');

        if (installAppBtn) {
            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevent the browser's default install prompt
                e.preventDefault();
                // Stash the event so it can be triggered later.
                deferredPrompt = e;
                // Show the custom install button
                installAppBtn.style.display = 'block';
            });

            installAppBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    // Show the browser's install prompt
                    deferredPrompt.prompt();
                    // Wait for the user to respond to the prompt
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User response to the install prompt: ${outcome}`);
                    // We can only use the prompt once, so clear it.
                    deferredPrompt = null;
                    // Hide the install button
                    installAppBtn.style.display = 'none';
                }
            });
        }

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }

        // --- NEW: iOS PWA Installation Instructions Logic ---
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isInStandaloneMode = ('standalone'in window.navigator) && (window.navigator.standalone);

        // If the user is on an iOS device and the app is not already installed...
        if (isIOS && !isInStandaloneMode) {
            const iosInstallInstructions = document.getElementById('iosInstallInstructions');
            if(iosInstallInstructions) {
                iosInstallInstructions.style.display = 'block';
            }
        }
    </script>
    
    <script src="js/main.js" type="module" defer></script>
</body>
</html>