<?php // api/parent_settings.php

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';
header('Content-Type: application/json');

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

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$conn = get_db_connection();
if (!$conn) { send_json_response(['success' => false, 'message' => 'Database connection failed.'], 500); }

$parent_user_id = require_parent_login();

switch ($action) {
    case 'set_pin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        $pin = $input['pin'] ?? null;
        if (empty($pin) || !preg_match('/^\d{4}$/', $pin)) {
            send_json_response(['success' => false, 'message' => 'PIN must be exactly 4 digits.'], 400);
        }

        $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("si", $pin_hash, $parent_user_id);
        if ($stmt->execute()) {
            send_json_response(['success' => true, 'message' => 'PIN set successfully.']);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to set PIN: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'verify_pin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        $pin_attempt = $input['pin'] ?? null;
        if (empty($pin_attempt) || !preg_match('/^\d{4}$/', $pin_attempt)) {
            send_json_response(['success' => false, 'message' => 'PIN must be exactly 4 digits.'], 400);
        }

        $stmt = $conn->prepare("SELECT pin_hash FROM users WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user_data = $result->fetch_assoc()) {
            if (!empty($user_data['pin_hash']) && password_verify($pin_attempt, $user_data['pin_hash'])) {
                send_json_response(['success' => true, 'message' => 'PIN verified.']);
            } else {
                send_json_response(['success' => false, 'message' => 'Invalid PIN.'], 401);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'User not found.'], 404);
        }
        $stmt->close();
        break;

    case 'clear_pin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        $stmt = $conn->prepare("UPDATE users SET pin_hash = NULL WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        if ($stmt->execute()) {
            send_json_response(['success' => true, 'message' => 'PIN cleared successfully.']);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to clear PIN: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;
    
    case 'check_pin_status': 
         $stmt = $conn->prepare("SELECT pin_hash FROM users WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        send_json_response(['success' => true, 'has_pin_set' => !empty($user_data['pin_hash'])]);
        break;

    case 'get_settings':
        $stmt = $conn->prepare("SELECT enable_overdue_task_notifications, auto_delete_completed_tasks, auto_delete_completed_tasks_days, auto_delete_notifications, auto_delete_notifications_days FROM users WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();
        if ($settings) {
            // Convert boolean values from strings '0'/'1' to actual booleans for JS
            $settings['enable_overdue_task_notifications'] = (bool)$settings['enable_overdue_task_notifications'];
            $settings['auto_delete_completed_tasks'] = (bool)$settings['auto_delete_completed_tasks'];
            $settings['auto_delete_notifications'] = (bool)$settings['auto_delete_notifications'];
            send_json_response(['success' => true, 'settings' => $settings]);
        } else {
            send_json_response(['success' => false, 'message' => 'Could not retrieve settings.'], 404);
        }
        break;

    case 'update_settings':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        
        $enable_overdue = isset($input['enable_overdue_task_notifications']) ? ($input['enable_overdue_task_notifications'] ? 1 : 0) : null;
        $auto_delete_tasks = isset($input['auto_delete_completed_tasks']) ? ($input['auto_delete_completed_tasks'] ? 1 : 0) : null;
        $auto_delete_tasks_days = isset($input['auto_delete_completed_tasks_days']) ? intval($input['auto_delete_completed_tasks_days']) : 30;
        $auto_delete_notifications = isset($input['auto_delete_notifications']) ? ($input['auto_delete_notifications'] ? 1 : 0) : null;
        $auto_delete_notifications_days = isset($input['auto_delete_notifications_days']) ? intval($input['auto_delete_notifications_days']) : 30;

        $update_fields = [];
        $update_values = [];
        $types = "";

        if ($enable_overdue !== null) {
            $update_fields[] = "enable_overdue_task_notifications = ?";
            $update_values[] = $enable_overdue;
            $types .= "i";
        }
        if ($auto_delete_tasks !== null) {
            $update_fields[] = "auto_delete_completed_tasks = ?";
            $update_values[] = $auto_delete_tasks;
            $types .= "i";
        }
        // Always update days if the corresponding boolean is being set, or if provided
        if ($auto_delete_tasks !== null || isset($input['auto_delete_completed_tasks_days'])) {
            $update_fields[] = "auto_delete_completed_tasks_days = ?";
            $update_values[] = $auto_delete_tasks_days;
            $types .= "i";
        }
         if ($auto_delete_notifications !== null) {
            $update_fields[] = "auto_delete_notifications = ?";
            $update_values[] = $auto_delete_notifications;
            $types .= "i";
        }
        if ($auto_delete_notifications !== null || isset($input['auto_delete_notifications_days'])) {
            $update_fields[] = "auto_delete_notifications_days = ?";
            $update_values[] = $auto_delete_notifications_days;
            $types .= "i";
        }

        if (empty($update_fields)) {
            send_json_response(['success' => false, 'message' => 'No settings to update.'], 400);
        }

        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_values[] = $parent_user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param($types, ...$update_values);

        if ($stmt->execute()) {
            send_json_response(['success' => true, 'message' => 'Settings updated successfully.']);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update settings: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    default:
        send_json_response(['success' => false, 'message' => 'Invalid parent settings action.'], 400);
        break;
}
$conn->close();
?>
