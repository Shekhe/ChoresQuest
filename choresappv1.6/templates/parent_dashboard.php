<?php // templates/parent_dashboard.php ?>

<div id="parentDashboardPage" class="page w-full max-w-6xl mx-auto p-2 sm:p-4"> 
    <header class="bg-app-blue text-white p-3 sm:p-4 h-16 rounded-t-lg shadow-md flex justify-between items-center">
        <div class="flex items-center">
            <button id="parentMenuToggleBtn" class="p-2 mr-2 md:hidden text-white focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl sm:text-2xl font-semibold">Parent Dashboard</h1>
        </div>
        <div class="flex space-x-2 sm:space-x-4 text-sm sm:text-base">
            <button id="parentHomeBtn" class="text-white hover:text-sky-100"><i class="fas fa-home sm:mr-2"></i> <span class="hidden sm:inline">Home</span></button>
            <button id="parentSettingsBtn" class="text-white hover:text-sky-100"><i class="fas fa-cog sm:mr-2"></i> <span class="hidden sm:inline">Settings</span></button>
            <button id="parentLogoutBtn" class="text-white hover:text-sky-100"><i class="fas fa-sign-out-alt sm:mr-2"></i> <span class="hidden sm:inline">Logout</span></button>
        </div>
    </header>
    <div class="flex flex-col md:flex-row relative"> 
        <nav id="parentSidebarNav" 
             class="hidden absolute top-0 left-0 right-0 w-full bg-white shadow-xl z-30 p-4 
                    md:block md:relative md:w-64 md:top-auto md:left-auto md:right-auto md:shadow-md md:z-auto 
                    md:rounded-bl-lg md:border-r md:border-gray-200">
            <ul class="space-y-2">
                <li><a href="#" data-target="overview" class="nav-link parent-nav active-nav-link"><i class="fas fa-home mr-2"></i>Overview</a></li>
                <li><a href="#" data-target="notifications" class="nav-link parent-nav"><i class="fas fa-bell mr-2"></i>Notifications <span id="unreadNotificationCountBadge" class="ml-1 bg-red-500 text-white text-xs font-semibold px-1.5 py-0.5 rounded-full hidden">0</span></a></li> 
                <li><a href="#" data-target="manageTasks" class="nav-link parent-nav"><i class="fas fa-tasks mr-2"></i>Manage Tasks</a></li>
                <li><a href="#" data-target="manageRewards" class="nav-link parent-nav"><i class="fas fa-gift mr-2"></i>Manage Rewards</a></li>
                <li><a href="#" data-target="manageChildren" class="nav-link parent-nav"><i class="fas fa-users mr-2"></i>Manage Children</a></li>
            </ul>
            <div class="mt-6 p-3 bg-yellow-100 border border-yellow-300 rounded-md">
                <h4 class="font-semibold text-yellow-700 pb-[5px]"><i class="fas fa-bell mr-1"></i> Alerts</h4>
                <p class="text-sm text-yellow-600" id="parentNotificationArea">No new alerts.</p>
            </div>
        </nav>

        <main id="parentMainContent" class="flex-1 p-3 sm:p-6 bg-gray-50 rounded-br-lg md:rounded-tr-none md:rounded-br-lg">
            <section id="overviewSection" class="parent-section">                        
                <h3 class="text-xl font-semibold mb-2 text-gray-700">Recent Activity</h3>
                <ul id="recentActivityList" class="space-y-1 text-gray-600"></ul>
                <h3 class="text-xl font-semibold mb-2 text-gray-700 pt-[25px]">Family Overview</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="card">
                        <h3 class="font-semibold text-sky-600">Total Children</h3> 
                        <p class="text-3xl" id="totalChildrenCount">0</p>
                    </div>
                    <div class="card">
                        <h3 class="font-semibold text-lime-600">Active Tasks</h3> 
                        <p class="text-3xl" id="activeTasksCount">0</p>
                    </div>
                    <div class="card">
                        <h3 class="font-semibold text-red-600">Overdue Tasks</h3>
                        <p class="text-3xl" id="overdueTasksCount">0</p>
                    </div>
                </div>
            </section>

            <section id="manageTasksSection" class="parent-section hidden">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Manage Tasks</h2>
                    <button id="addNewTaskBtn" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add New Task</button>
                </div>
                <div class="mb-4 flex flex-wrap space-x-0 sm:space-x-2 gap-2 sm:gap-0">
                    <button id="filterBtnActive" class="btn btn-neutral filter-task-btn active flex-grow sm:flex-grow-0" data-filter="active">Active</button>
                    <button id="filterBtnOverdue" class="btn btn-neutral filter-task-btn flex-grow sm:flex-grow-0" data-filter="overdue">Overdue</button>
                    <button id="filterBtnArchived" class="btn btn-neutral filter-task-btn flex-grow sm:flex-grow-0" data-filter="archived">Archived</button>
                </div>
                <div id="taskList" class="space-y-3"></div>
            </section>

            <div id="taskModal" class="modal hidden">
                <div class="modal-content">
                    <h2 id="taskModalTitle" class="text-2xl font-semibold mb-6 text-center text-sky-600">Add New Task</h2> 
                    <form id="taskForm" class="space-y-4">
                        <input type="hidden" id="taskId">
                        <input type="hidden" id="taskImageURL">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Task Image</label>
                            <div class="flex items-center space-x-4">
                                <img id="taskImagePreview" src="https://placehold.co/80x80/e5e7eb/a0aec0?text=Icon" class="w-20 h-20 rounded-lg object-cover bg-gray-100">
                                <div class="flex-grow space-y-2">
                                    <label for="taskImage" class="btn btn-neutral w-full cursor-pointer mr-5">
                                        <i class="fas fa-upload mr-2"></i> Upload Image
                                    </label>
                                    <input type="file" id="taskImage" name="taskImage" class="hidden" accept="image/*">
                                    <button type="button" id="chooseFromLibraryBtn" class="btn-2 btn-neutral">
                                        <i class="fas fa-swatchbook mr-2"></i> Choose from Library
                                    </button>
                                </div>
                            </div>


                                <div id="iconLibraryModal" class="modal hidden">
                                    <div class="modal-content">
                                        <h2 class="text-2xl font-semibold mb-4 text-sky-600">Choose an Icon</h2>
                                        <div id="iconLibraryGrid" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-4">
                                            </div>
                                        <button id="closeIconLibraryModal" class="btn btn-neutral w-full mt-6">Cancel</button>
                                    </div>
                                </div>



                        </div>

                        <div>
                            <label for="taskTitle" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" id="taskTitle" name="taskTitle" class="input-field" required placeholder="e.g., Make your bed (3 words max)">
                        </div>
                        <div>
                            <label for="taskDueDate" class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" id="taskDueDate" name="taskDueDate" class="input-field" required>
                        </div>
                        <div>
                            <label for="taskPoints" class="block text-sm font-medium text-gray-700">Points</label>
                            <input type="number" id="taskPoints" name="taskPoints" class="input-field" min="1" required>
                        </div>
                        
                        <div>
                            <label for="taskNotes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                            <textarea id="taskNotes" name="taskNotes" class="input-field" rows="3" placeholder="Add extra instructions for your child..."></textarea>
                        </div>
                        
                        <div>
                            <div class="flex items-center mt-1 mb-1">
                                <input type="checkbox" id="taskIsFamily" name="taskIsFamily" class="form-checkbox h-5 w-5 text-app-blue rounded mr-2">
                                <label for="taskIsFamily" class="text-sm font-medium text-gray-700 whitespace-nowrap">Family Task <span class="text-xs text-gray-500">(all children)</span></label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assign To</label>
                            <div id="taskAssignToContainer" class="child-selector-container">
                                </div>
                        </div>

                        <div>
                            <label for="taskRepeat" class="block text-sm font-medium text-gray-700">Repeating</label>
                            <select id="taskRepeat" name="taskRepeat" class="input-field">
                                <option value="none">None</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div id="taskRepeatDaysContainer" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Repeat on these days:</label>
                            <div class="week-day-selector">
                                <input type="checkbox" id="weekday-1" class="weekday-checkbox" value="1"><label for="weekday-1">Mon</label>
                                <input type="checkbox" id="weekday-2" class="weekday-checkbox" value="2"><label for="weekday-2">Tue</label>
                                <input type="checkbox" id="weekday-3" class="weekday-checkbox" value="3"><label for="weekday-3">Wed</label>
                                <input type="checkbox" id="weekday-4" class="weekday-checkbox" value="4"><label for="weekday-4">Thu</label>
                                <input type="checkbox" id="weekday-5" class="weekday-checkbox" value="5"><label for="weekday-5">Fri</label>
                                <input type="checkbox" id="weekday-6" class="weekday-checkbox" value="6"><label for="weekday-6">Sat</label>
                                <input type="checkbox" id="weekday-7" class="weekday-checkbox" value="7"><label for="weekday-7">Sun</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Save Task</button>
                    </form>
                    <button id="closeTaskModal" class="btn btn-neutral w-full mt-2">Cancel</button>
                </div>
            </div>
            
            <section id="manageRewardsSection" class="parent-section hidden">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Manage Rewards</h2>
                    <button id="addNewRewardBtn" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add New Reward</button>
                </div>
                <div id="rewardList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </section>

            <div id="rewardModal" class="modal hidden">
                <div class="modal-content"> 
                    <h2 id="rewardModalTitle" class="text-2xl font-semibold mb-6 text-center text-sky-600">Add New Reward</h2> 
                    <form id="rewardForm" class="space-y-4">
                        <input type="hidden" id="rewardId">
                        <input type="hidden" id="rewardImageURL">
                        <div>
                            <label for="rewardTitle" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" id="rewardTitle" class="input-field" required>
                        </div>
                        <div>
                            <label for="rewardPoints" class="block text-sm font-medium text-gray-700">Required Points</label>
                            <input type="number" id="rewardPoints" class="input-field" min="1" required>
                        </div>
                        <div>
                            <label for="rewardImage" class="block text-sm font-medium text-gray-700">Image (Optional)</label>
                            <input type="file" id="rewardImage" class="input-field" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Save Reward</button>
                    </form>
                    <button id="closeRewardModal" class="btn btn-neutral w-full mt-2">Cancel</button>
                </div>
            </div>

            <section id="manageChildrenSection" class="parent-section hidden">
                 <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Manage Children</h2>
                    <button id="addNewChildBtn" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add Child</button>
                </div>
                <div id="childrenList" class="space-y-3">
                    </div>
            </section>

            <div id="childModal" class="modal hidden">
                <div class="modal-content"> 
                    <h2 id="childModalTitle" class="text-2xl font-semibold mb-6 text-center text-sky-600">Add New Child</h2> 
                    <form id="childForm" class="space-y-4">
                        <input type="hidden" id="childId">
                        <input type="hidden" id="childImageURL">
                        <div>
                            <label for="childName" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" id="childName" class="input-field" required>
                        </div>
                        <div>
                            <label for="childProfilePic" class="block text-sm font-medium text-gray-700">Profile Picture (Optional)</label>
                            <input type="file" id="childProfilePic" class="input-field" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Save Child</button>
                    </form>
                    <button id="closeChildModal" class="btn btn-neutral w-full mt-2">Cancel</button>
                </div>
            </div>

            <section id="settingsSection" class="parent-section hidden">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Settings</h2>
                <div class="card space-y-6">
                    <div>
                        <label for="parentPinInput" class="block text-sm font-medium text-gray-700 label-w-50">Parent PIN Protection</label> 
                        <div class="flex items-center space-x-2 mt-1">
                            <div class="w-1/2"> 
                                <input type="password" id="parentPinInput" class="input-field" placeholder="Enter 4-digit PIN" maxlength="4" pattern="\d{4}">
                            </div>
                            <button id="setParentPinBtn" class="btn btn-primary">Set PIN</button>
                        </div>
                         <button id="clearParentPinBtn" class="btn btn-neutral btn-sm mt-2 hidden">Clear PIN</button>
                        <p id="parentPinMessage" class="text-xs text-gray-500 mt-1">Secure parent dashboard access.</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="autoDeleteCompletedTasks" class="block text-sm font-medium text-gray-700">Auto-delete completed tasks older than 30 days</label>
                        </div>
                        <input type="checkbox" id="autoDeleteCompletedTasks" class="form-checkbox h-5 w-5 text-app-blue rounded"> 
                    </div>
                     <div class="flex items-center justify-between">
                        <div>
                            <label for="autoDeleteNotifications" class="block text-sm font-medium text-gray-700">Auto-delete notifications older than 30 days</label>
                        </div>
                        <input type="checkbox" id="autoDeleteNotifications" class="form-checkbox h-5 w-5 text-app-blue rounded"> 
                    </div>
                     <div class="flex items-center justify-between">
                        <label for="notifyOverdue" class="block text-sm font-medium text-gray-700">Enable notifications for overdue tasks</label>
                        <input type="checkbox" id="notifyOverdue" class="form-checkbox h-5 w-5 text-app-blue rounded" checked> 
                    </div>
                    <div>
                        <button id="syncDateTimeBtn" class="btn btn-neutral"><i class="fas fa-sync-alt mr-2"></i>Sync Date and Time</button>
                        <p class="text-xs text-gray-500 mt-1">Current system time: <span id="currentTimeDisplay"></span></p>
                    </div>
                </div>
            </section>

            <section id="notificationsSection" class="parent-section hidden">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Notifications</h2>
                    <button id="markAllNotificationsReadBtn" class="btn btn-primary">Mark All as Read</button> </div>
                <div id="fullNotificationList" class="space-y-2 bg-white rounded-lg shadow p-[15px]"> 
                    <p class="text-gray-500 p-4 text-center">Loading notifications...</p>
                </div>
                <div id="notificationPaginationControls" class="mt-4 flex justify-center items-center space-x-2">
                    </div>
            </section>
        </main>
    </div>
</div>