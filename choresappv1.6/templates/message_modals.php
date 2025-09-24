<?php // templates/message_modals.php ?>

<div id="messageModal" class="modal hidden">
    <div class="modal-content max-w-md text-center"> 
        <h2 id="messageModalTitle" class="text-2xl font-semibold mb-4">Notification</h2>
        <p id="messageModalText" class="text-gray-700 mb-6"></p>
        <button id="messageModalCloseBtn" class="btn btn-primary mx-auto">OK</button> 
    </div>
</div>

<div id="pinEntryModal" class="modal hidden">
    <div class="modal-content max-w-xs">
        <h2 class="text-xl font-semibold mb-4 text-center text-sky-600">Enter Parent PIN</h2> 
        <form id="pinEntryForm">
            <input type="password" id="pinInput" class="input-field text-center text-2xl tracking-widest" maxlength="4" pattern="\d{4}" required placeholder="----">
            <p id="pinErrorMessage" class="text-red-500 text-sm text-center my-2 hidden"></p>
            <button type="submit" class="btn btn-primary w-full mt-4">Unlock</button>
        </form>
        <button id="cancelPinEntryBtn" class="btn btn-neutral w-full mt-2">Cancel</button>
    </div>
</div>
