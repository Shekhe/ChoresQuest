<?php // templates/kids_zone.php ?>

<div id="kidsZonePage" class="page w-full max-w-4xl mx-auto p-2 sm:p-4">
    <div id="kidProfileSelection" class="text-center py-10">
        <h1 class="text-3xl font-bold text-lime-600 mb-8">Who are you?</h1> 
        <div id="kidProfilesContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6"></div>
        <div class="mt-8 text-center border-t pt-6">
            <button id="backToLandingBtn" class="btn btn-neutral btn-sm"><i class="fas fa-home mr-2"></i>Back to Home</button>
        </div>
    </div>

    <div id="childDashboard" class="hidden">
         <header class="bg-lime-500 text-white p-3 sm:p-4 h-16 rounded-t-lg shadow-md flex justify-between items-center">
            <div class="flex items-center"> <img id="childProfilePicHeader" src="" alt="Profile" class="w-10 h-10 rounded-full mr-3 object-cover border-2 border-white">
                <h1 class="text-xl sm:text-2xl font-semibold" id="childWelcomeName">[Child Name]!</h1>
            </div>
            <div class="flex space-x-2 sm:space-x-4 text-sm sm:text-base"> 
                <button id="childLogoutBtn" class="text-white hover:text-lime-200"><i class="fas fa-user-friends sm:mr-2"></i> <span class="hidden sm:inline">Family Members</span></button>
                <button id="childDashboardLogoutBtn" class="text-white hover:text-lime-200"><i class="fas fa-sign-out-alt sm:mr-2 ml-3"></i> <span class="hidden sm:inline">Logout</span></button>
            </div>
        </header>
        <div class="bg-white p-4 sm:p-6 rounded-b-lg shadow-md">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-700">My Points: <span id="childPoints" class="text-yellow-500">0 <i class="fas fa-star"></i></span></h2>
                </div>
                <div>
                    <button id="viewRewardsBtn" class="btn btn-secondary w-full sm:w-auto"><i class="fas fa-gift mr-2"></i>View Rewards</button>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-700 mt-6"><i class="fas fa-users mr-2 text-lime-500"></i>Family Tasks</h3>
                <div id="childFamilyTasksList" class="space-y-3"> <p class="text-gray-500 p-4 text-center">No family tasks available right now.</p>
                </div>

                <h3 class="text-lg font-semibold mb-3 text-gray-700 mt-6"><i class="fas fa-user mr-2 text-lime-500"></i>My Tasks</h3>
                <div id="childMyTasksList" class="space-y-3 mb-8"> <p class="text-gray-500 p-4 text-center">No tasks assigned directly to you right now.</p>
                </div>
                
                <h3 class="text-lg font-semibold mb-3 text-gray-700 mt-6"><i class="fas fa-check-double mr-2 text-lime-500"></i>Completed Tasks</h3>
                <div id="childCompletedTasksList" class="space-y-3">
                </div>
                <div id="childCompletedPaginationControls" class="mt-4 flex justify-center items-center space-x-2">
                </div>
            </div>

        </div>
    </div>

    <div id="kidRewardsModal" class="modal hidden">
        <div class="modal-content w-full p-4 sm:p-6 md:p-8"> 
            <div class="relative mb-6"> 
                <h2 class="text-xl sm:text-2xl font-semibold text-sky-600 text-center py-2">Available Rewards</h2> 
                <button id="closeKidRewardsModal" class="absolute top-0 right-0 mt-1 mr-1 text-gray-600 hover:text-gray-800 text-3xl leading-none p-1">&times;</button>
            </div>
            <p class="mb-4 text-lg">Your Points: <span id="kidModalPoints" class="text-yellow-500 font-bold">0 <i class="fas fa-star"></i></span></p>
            <div id="kidRewardsList" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </div>
    </div>
</div>
