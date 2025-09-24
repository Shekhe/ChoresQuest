<?php // templates/policy_modal.php ?>

<div id="policyModal" class="modal hidden">
    <div class="modal-content max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 id="policyModalTitle" class="text-2xl font-semibold text-sky-600"></h2>
            <button id="closePolicyModalBtn" class="text-gray-500 hover:text-gray-800 text-3xl">&times;</button>
        </div>
        <div id="policyModalContentArea" class="prose max-w-none text-gray-700">
            <!-- Content will be loaded here via JavaScript fetch -->
        </div>
    </div>
</div>
