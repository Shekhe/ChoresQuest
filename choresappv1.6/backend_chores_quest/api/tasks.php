<?php // api/tasks.php - Handles Task Management with Multi-Child Assignments

// --- FIX: Set the default timezone for all PHP date/time functions ---
date_default_timezone_set('America/Edmonton'); // Ensure this matches your server's timezone or desired app timezone.

// CORS Headers and Session Start
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../db.php'; 
header('Content-Type: application/json'); 

// Helper functions
if (!function_exists('send_json_response')) {
    function send_json_response($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
function require_parent_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parent') {
        send_json_response(['success' => false, 'message' => 'Unauthorized. Parent login required.'], 401);
    }
    return $_SESSION['user_id']; 
}

/**
 * Calculates the next due date for a recurring task.
 * @param {string} $current_due_date The task's current due date (e.g., '2025-06-16')
 * @param {string} $repeat_type 'daily', 'weekly', 'monthly', or 'custom_days'
 * @param {string|null} $repeat_on_days_str A comma-separated string of weekday numbers (1=Mon, 7=Sun)
 * @return {string|null} The next due date in 'Y-m-d' format, or null if not recurring.
 */
function get_next_due_date($current_due_date, $repeat_type, $repeat_on_days_str) {
    if ($repeat_type === 'none' || empty($current_due_date)) {
        return null;
    }
    
    $today = new DateTime();
    $today->setTime(0, 0, 0); 
    $current_date_obj = new DateTime($current_due_date);

    // If the current due date is in the future, we don't need to advance it yet.
    if ($current_date_obj >= $today) {
        return $current_date_obj->format('Y-m-d');
    }

    // Loop until the calculated date is in the present or future
    // This loop ensures that even if a task was missed for multiple periods,
    // its due_date is correctly advanced to the next *current or future* occurrence.
    while ($current_date_obj < $today) {
        $start_date_for_calc = clone $current_date_obj;

        if ($repeat_type === 'daily' || $repeat_type === 'custom_days') { // MODIFIED: Added 'custom_days'
            $allowed_days = array_map('intval', explode(',', $repeat_on_days_str));
            sort($allowed_days);
            
            // If custom_days is selected but no days are specified, treat as daily
            if (empty($allowed_days) && $repeat_type === 'custom_days') {
                $current_date_obj->modify('+1 day');
                continue;
            }
            if (empty($allowed_days) && $repeat_type === 'daily') { // If daily, and no specific days, just move to next day
                 $current_date_obj->modify('+1 day');
                 continue;
            }

            $next_day_found = false;
            for ($i = 1; $i <= 7; $i++) { // Check up to 7 days in the future
                $start_date_for_calc->modify('+1 day');
                $day_of_week = (int)$start_date_for_calc->format('N'); // 1 (for Monday) through 7 (for Sunday)
                if (in_array($day_of_week, $allowed_days)) {
                    $current_date_obj = $start_date_for_calc;
                    $next_day_found = true;
                    break;
                }
            }
            if (!$next_day_found) {
                 // Should ideally not happen if loop runs for 7 days, but as a safeguard
                 $current_date_obj->modify('+1 day'); // Fallback to ensure progress
            }

        } else { // 'daily', 'weekly', 'monthly' where custom_days is not applicable or not set
            switch ($repeat_type) {
                case 'daily': $current_date_obj->modify('+1 day'); break;
                case 'weekly': $current_date_obj->modify('+1 week'); break;
                case 'monthly': $current_date_obj->modify('+1 month'); break;
                default: return $current_date_obj->format('Y-m-d'); // Should not happen with validation
            }
        }
    }
    return $current_date_obj->format('Y-m-d');
}


$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$conn = get_db_connection();
if (!$conn) { send_json_response(['success' => false, 'message' => 'Database connection failed.'], 500); }


// =============================================
// --- TASK API ACTIONS (with Multi-Child Logic)---
// =============================================

switch ($action) {
    case 'create':
    case 'update':
        $parent_user_id = require_parent_login();
        $task_id = ($action === 'update') ? ($input['id'] ?? null) : null;

        if ($action === 'update' && !$task_id) { send_json_response(['success' => false, 'message' => 'Task ID is required for update.'], 400); }
        $title = $input['title'] ?? null;
        $dueDate = $input['dueDate'] ?? null;
        $points = isset($input['points']) ? intval($input['points']) : 0;
        if (empty($title) || empty($dueDate) || $points <= 0) { send_json_response(['success' => false, 'message' => 'Title, due date, and positive points are required.'], 400); }
        
        $image_url = $input['image'] ?? null;
        $notes = $input['notes'] ?? null;
        $is_family_task = isset($input['is_family_task']) && $input['is_family_task'] ? 1 : 0;
        $assigned_children_ids = $input['assigned_children_ids'] ?? [];
        $repeat_type = $input['repeat'] ?? 'none';
        $repeat_on_days = $input['repeat_on_days'] ?? null;

        // Validation for repeat_type 'custom_days' requires repeat_on_days
        if (($repeat_type === 'daily' || $repeat_type === 'custom_days') && empty($repeat_on_days)) { // MODIFIED: Added 'custom_days'
             // If daily, and no days specified, it means all days.
             // If custom_days, and no days specified, it's an error or defaults to daily behavior.
             // For now, let's treat it as all days for 'daily' and require for 'custom_days'
             if ($repeat_type === 'custom_days') {
                 send_json_response(['success' => false, 'message' => 'For custom daily repeats, specific days must be selected.'], 400);
             }
        }
        
        $conn->begin_transaction();
        try {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO tasks (parent_user_id, title, due_date, points, image_url, notes, repeat_type, repeat_on_days, status, is_family_task) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                if (!$stmt) throw new Exception('DB error (create task prepare): ' . $conn->error);
                $stmt->bind_param("ississssi", $parent_user_id, $title, $dueDate, $points, $image_url, $notes, $repeat_type, $repeat_on_days, $is_family_task);
            } else {
                // When updating, if a task was completed and is now being updated, its status should revert to active
                $stmt = $conn->prepare("UPDATE tasks SET title=?, due_date=?, points=?, image_url=?, notes=?, repeat_type=?, repeat_on_days=?, is_family_task=?, status='active' WHERE id=? AND parent_user_id=?");
                if (!$stmt) throw new Exception('DB error (update task prepare): ' . $conn->error);
                $stmt->bind_param("ssissssiii", $title, $dueDate, $points, $image_url, $notes, $repeat_type, $repeat_on_days, $is_family_task, $task_id, $parent_user_id);
            }
            if (!$stmt->execute()) throw new Exception('DB error (task execute): ' . $stmt->error);
            
            if ($action === 'create') { $task_id = $stmt->insert_id; }
            $stmt->close();

            // Handle task assignments for non-family tasks
            $delete_stmt = $conn->prepare("DELETE FROM task_assignments WHERE task_id = ?");
            if (!$delete_stmt) throw new Exception('DB error (delete assignments prepare): ' . $conn->error);
            $delete_stmt->bind_param("i", $task_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            if (!$is_family_task && !empty($assigned_children_ids)) {
                $assign_stmt = $conn->prepare("INSERT INTO task_assignments (task_id, child_id) VALUES (?, ?)");
                if (!$assign_stmt) throw new Exception('DB error (assign task prepare): ' . $conn->error);
                foreach ($assigned_children_ids as $child_id) {
                    $assign_stmt->bind_param("ii", $task_id, $child_id);
                    if (!$assign_stmt->execute()) throw new Exception('DB error (assign task execute): ' . $assign_stmt->error);
                }
                $assign_stmt->close();
            }

            $conn->commit();
            send_json_response(['success' => true, 'message' => 'Task ' . $action . 'd successfully.', 'task_id' => $task_id]);

        } catch (Exception $e) {
            $conn->rollback();
            send_json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'list': 
        $parent_user_id = require_parent_login();
        
        $today_date = date('Y-m-d');

        // --- NEW LOGIC FOR RECURRING TASK ADVANCEMENT ---
        // Fetch all active recurring tasks for this parent that are "stuck" in the past
        $overdue_recurring_sql = "SELECT id, due_date, repeat_type, repeat_on_days FROM tasks 
                                  WHERE parent_user_id = ? 
                                  AND repeat_type != 'none' 
                                  AND status = 'active'
                                  AND due_date < ?"; // Check against today
        
        $stmt_overdue_recurring = $conn->prepare($overdue_recurring_sql);
        if (!$stmt_overdue_recurring) { send_json_response(['success' => false, 'message' => 'DB prepare error for selecting overdue recurring: ' . $conn->error], 500); }
        
        $stmt_overdue_recurring->bind_param("is", $parent_user_id, $today_date);
        $stmt_overdue_recurring->execute();
        $overdue_tasks_result = $stmt_overdue_recurring->get_result();

        $tasks_to_update_due_date = [];
        while($task = $overdue_tasks_result->fetch_assoc()) {
            $next_due = get_next_due_date($task['due_date'], $task['repeat_type'], $task['repeat_on_days']);
            if($next_due && $next_due !== $task['due_date']) { // Only update if it actually changes
                $tasks_to_update_due_date[] = ['id' => $task['id'], 'next_due' => $next_due];
            }
        }
        $stmt_overdue_recurring->close();

        // Perform updates in a single transaction for efficiency
        if (!empty($tasks_to_update_due_date)) {
            $conn->begin_transaction();
            try {
                $update_stmt = $conn->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
                if (!$update_stmt) throw new Exception('DB prepare error for update recurring: ' . $conn->error);
                
                foreach ($tasks_to_update_due_date as $task_update) {
                    $update_stmt->bind_param("si", $task_update['next_due'], $task_update['id']);
                    $update_stmt->execute();
                }
                $update_stmt->close();
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Failed to update recurring task due dates: " . $e->getMessage());
                // Don't send error to frontend here, just log, as it's a background update.
            }
        }
        // --- END NEW LOGIC FOR RECURRING TASK ADVANCEMENT ---

        $sql = "SELECT 
                    t.*,
                    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as assigned_children_names,
                    GROUP_CONCAT(DISTINCT ta.child_id SEPARATOR ',') as assigned_children_ids,
                    -- For family tasks, check for ANY completion for the current due date period
                    -- For non-family tasks, completion_count will be 0 if not completed by ANY assigned child
                    -- MODIFIED: Use MAX to get the most recent completion for the period if multiple children completed it (family tasks)
                    (SELECT COUNT(sub_tc.id)
                     FROM task_completions sub_tc
                     WHERE sub_tc.task_id = t.id AND DATE(sub_tc.completed_at) >= t.due_date) as completion_count_for_period
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.id = ta.task_id
                LEFT JOIN children c ON ta.child_id = c.id
                WHERE t.parent_user_id = ?
                GROUP BY t.id
                ORDER BY t.due_date ASC, t.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB prepare error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $row['is_family_task'] = (bool)$row['is_family_task'];
            $row['assigned_children_ids'] = $row['assigned_children_ids'] ? array_map('intval', explode(',', $row['assigned_children_ids'])) : [];
            
            $row['completion_count'] = (int)$row['completion_count_for_period']; 
            
            // NEW FIX: If a ONE-TIME task (family or personal) has been completed, explicitly set its status to 'completed'
            // This ensures it moves to the 'Archived' tab and out of active view for parents/kids.
            if ($row['repeat_type'] === 'none' && $row['completion_count'] > 0 && $row['status'] !== 'completed') {
                $update_one_time_status_stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_date = CURDATE() WHERE id = ?");
                if ($update_one_time_status_stmt) { // Check if prepare was successful
                    $update_one_time_status_stmt->bind_param("i", $row['id']);
                    $update_one_time_status_stmt->execute();
                    $update_one_time_status_stmt->close();
                } else {
                    error_log("Failed to prepare update one-time task status for task ID " . $row['id'] . ": " . $conn->error);
                }
                $row['status'] = 'completed'; // Update current row to reflect change
                $row['completed_date'] = $today_date; // Update current row
            }

            $tasks[] = $row;
        }
        send_json_response(['success' => true, 'tasks' => $tasks]);
        $stmt->close();
        break;

    case 'get_completions_for_child':
        $child_id = $_GET['child_id'] ?? null;
        if(!$child_id) { send_json_response(['success' => false, 'message' => 'Child ID is required.'], 400); }
        
        $stmt = $conn->prepare("SELECT 
                                    tc.task_id, 
                                    DATE_FORMAT(tc.completed_at, '%Y-%m-%dT%H:%i:%s') as completed_at,
                                    t.title,
                                    t.image_url,
                                    t.points,
                                    t.is_family_task,
                                    t.repeat_type,
                                    t.repeat_on_days,
                                    t.due_date,
                                    t.notes
                                FROM task_completions tc
                                JOIN tasks t ON tc.task_id = t.id
                                WHERE tc.child_id = ?
                                ORDER BY tc.completed_at DESC"); // Order by most recent completion
        
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB prepare error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $child_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $completions = [];
        while($row = $result->fetch_assoc()) {
            // Need to convert is_family_task to boolean for JS consumption
            $row['is_family_task'] = (bool)$row['is_family_task'];
            $completions[] = $row;
        }
        send_json_response(['success' => true, 'completions' => $completions]);
        $stmt->close();
        break;

    case 'mark_done_by_kid': 
        $kid_id = $input['kid_id'] ?? null; 
        $task_id = $input['task_id'] ?? null;

        if (!$kid_id || !$task_id) { send_json_response(['success' => false, 'message' => 'Kid ID and Task ID are required.'], 400); }
        
        $conn->begin_transaction();
        try {
            $task_stmt = $conn->prepare("SELECT id, parent_user_id, title, points, is_family_task, repeat_type, due_date, repeat_on_days FROM tasks WHERE id = ? FOR UPDATE");
            if (!$task_stmt) throw new Exception("DB error (fetch task details).");
            $task_stmt->bind_param("i", $task_id);
            $task_stmt->execute();
            $task_to_complete = $task_stmt->get_result()->fetch_assoc();
            $task_stmt->close();
            if (!$task_to_complete) throw new Exception("Task not found.");

            $current_due_date = $task_to_complete['due_date'];
            $today_for_check = date('Y-m-d'); // Use server's current date for checks

            // IMPORTANT: Check if the task's due date has arrived for recurring tasks.
            // A recurring task is only "active" for completion on or after its due_date.
            if ($task_to_complete['repeat_type'] !== 'none' && $current_due_date > $today_for_check) {
                 throw new Exception("This task is not yet due.");
            }

            // Check if the current child has already completed this specific task for its current period
            // For recurring tasks, completion check is tied to the current due_date.
            // For one-time tasks, any completion means it's done.
            $sql_check_child = "SELECT id FROM task_completions WHERE task_id = ? AND child_id = ? AND DATE(completed_at) >= ?";
            $check_child_stmt = $conn->prepare($sql_check_child);
            if (!$check_child_stmt) throw new Exception("DB error (check child completion).");
            $check_child_stmt->bind_param("iis", $task_id, $kid_id, $current_due_date);
            $check_child_stmt->execute();
            $check_child_stmt->store_result();
            if ($check_child_stmt->num_rows > 0) {
                throw new Exception("You have already completed this task for the current period.");
            }
            $check_child_stmt->close();

            // Handle Family Tasks (First-Come, First-Served)
            if ((bool)$task_to_complete['is_family_task']) {
                $sql_check_family = "SELECT id FROM task_completions WHERE task_id = ? AND DATE(completed_at) >= ?";
                $check_family_stmt = $conn->prepare($sql_check_family);
                if (!$check_family_stmt) throw new Exception("DB error (check family completion).");
                $check_family_stmt->bind_param("is", $task_id, $current_due_date);
                $check_family_stmt->execute();
                $check_family_stmt->store_result();
                if ($check_family_stmt->num_rows > 0) {
                    throw new Exception("Sorry, another family member has already completed this task!");
                }
                $check_family_stmt->close();
            }

            $child_stmt = $conn->prepare("SELECT name FROM children WHERE id = ?");
            if (!$child_stmt) throw new Exception("DB error (fetch child name).");
            $child_stmt->bind_param("i", $kid_id);
            $child_stmt->execute();
            $child_info = $child_stmt->get_result()->fetch_assoc();
            $child_stmt->close();
            if (!$child_info) throw new Exception("Child not found.");
            
            // Log task completion
            $comp_stmt = $conn->prepare("INSERT INTO task_completions (task_id, child_id, points_awarded) VALUES (?, ?, ?)");
            if (!$comp_stmt) throw new Exception("DB error (log completion).");
            $comp_stmt->bind_param("iii", $task_id, $kid_id, $task_to_complete['points']);
            if (!$comp_stmt->execute()) throw new Exception("Failed to log task completion.");
            $comp_stmt->close();

            // Award points to child
            $points_stmt = $conn->prepare("UPDATE children SET points = points + ? WHERE id = ?");
            if (!$points_stmt) throw new Exception('DB error (award points).');
            $points_stmt->bind_param("ii", $task_to_complete['points'], $kid_id);
            if (!$points_stmt->execute()) throw new Exception('Failed to award points.');
            $points_stmt->close();

            // --- IMPORTANT: Update task status for ONE-TIME tasks OR advance recurring tasks ---
            if ($task_to_complete['repeat_type'] === 'none') {
                // One-time task: Mark as completed
                // This will effectively "remove" it from active lists for ALL children and parents.
                $update_task_status_stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_date = CURDATE() WHERE id = ?");
                if (!$update_task_status_stmt) throw new Exception("DB error (update one-time task status).");
                $update_task_status_stmt->bind_param("i", $task_id);
                if (!$update_task_status_stmt->execute()) throw new Exception("Failed to update task status.");
                $update_task_status_stmt->close();
            } else {
                // Recurring task: Advance due_date to the next occurrence
                $next_due_date = get_next_due_date($current_due_date, $task_to_complete['repeat_type'], $task_to_complete['repeat_on_days']);
                if ($next_due_date && $next_due_date !== $current_due_date) {
                    $update_task_due_date_stmt = $conn->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
                    if (!$update_task_due_date_stmt) throw new Exception("DB error (advance recurring task due date).");
                    $update_task_due_date_stmt->bind_param("si", $next_due_date, $task_id);
                    if (!$update_task_due_date_stmt->execute()) throw new Exception("Failed to advance recurring task due date.");
                    $update_task_due_date_stmt->close();
                }
            }
            // --- END TASK STATUS/RECURRENCE UPDATE ---

            $notification_message = $child_info['name'] . " completed the task: " . $task_to_complete['title'] . " and earned " . $task_to_complete['points'] . " points!";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (parent_user_id, child_id, task_id, notification_type, message) VALUES (?, ?, ?, 'task_completed_by_child', ?)");
            if (!$notif_stmt) throw new Exception("DB error (create notification).");
            $notif_stmt->bind_param("iiis", $task_to_complete['parent_user_id'], $kid_id, $task_id, $notification_message);
            if (!$notif_stmt->execute()) throw new Exception("Failed to create notification.");
            $notif_stmt->close();

            $conn->commit();
            send_json_response(['success' => true, 'message' => 'Task marked as done! Points awarded.']);
        } catch (Exception $e) {
            $conn->rollback();
            $statusCode = ($e->getMessage() === "You have already completed this task for the current period." || $e->getMessage() === "Sorry, another family member has already completed this task!" || $e->getMessage() === "This task is not yet due.") ? 409 : 500;
            send_json_response(['success' => false, 'message' => $e->getMessage()], $statusCode);
        }
        break;

    case 'delete':
        $parent_user_id = require_parent_login();
        $task_id = $input['id'] ?? null;
        if (!$task_id) { send_json_response(['success' => false, 'message' => 'Task ID is required.'], 400); }
        
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("DELETE FROM task_assignments WHERE task_id = ?");
            if (!$stmt1) throw new Exception('DB error (delete assignments prepare): ' . $conn->error);
            $stmt1->bind_param("i", $task_id);
            $stmt1->execute();
            $stmt1->close();
            
            $stmt2 = $conn->prepare("DELETE FROM task_completions WHERE task_id = ?");
            if (!$stmt2) throw new Exception('DB error (delete completions prepare): ' . $conn->error);
            $stmt2->bind_param("i", $task_id);
            $stmt2->execute();
            $stmt2->close();
            
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND parent_user_id = ?");
            if (!$stmt) throw new Exception('DB error (delete task prepare): ' . $conn->error);
            $stmt->bind_param("ii", $task_id, $parent_user_id);
            if (!$stmt->execute()) throw new Exception('DB error (delete task execute): ' . $stmt->error);
            
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                send_json_response(['success' => true, 'message' => 'Task deleted successfully.']);
            } else {
                throw new Exception("Task not found or not authorized to delete.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            send_json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    default:
        send_json_response(['success' => false, 'message' => 'Invalid task action.'], 400);
        break;
}

$conn->close();
?>