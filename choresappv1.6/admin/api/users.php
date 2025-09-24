<?php // admin/api/users.php - Handles Admin Management of All Users

// REMOVED TEMPORARY DEBUGGING LINES:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);


// CORS Headers
header("Access-Control-Allow-Origin: *"); // For development, update to specific admin domain in production
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection (path is relative to this file)
require_once '../../backend_chores_quest/db.php'; 

// REMOVED: require_once '../../backend_chores_quest/api/auth.php'; 
// REASON: To avoid modifying app's auth.php which was causing a fatal error.

header('Content-Type: application/json');

// --- Helper function to send JSON response ---
if (!function_exists('send_json_response')) { // Ensure it's not defined twice
    function send_json_response($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// --- NEW HELPER (DUPLICATED): Generate Recovery Code ---
// This function is duplicated from the main app's auth.php to avoid
// including that file directly and to respect the "no app file edits" rule.
function generate_recovery_code() {
    // Generates a code in the format XXXX-XXXX-XXXX
    $chars = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'; // Omitted O and 0 for clarity
    $code = '';
    for ($i = 0; $i < 12; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
        if (($i + 1) % 4 == 0 && $i < 11) {
            $code .= '-';
        }
    }
    return $code;
}


// --- Require Admin Login ---
function require_admin_login() {
    if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_user_type'] !== 'admin') {
        send_json_response(['success' => false, 'message' => 'Unauthorized. Admin login required.'], 401);
    }
    return $_SESSION['admin_user_id'];
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$conn = get_db_connection();

if (!$conn) {
    send_json_response(['success' => false, 'message' => 'Database connection failed.'], 500);
}

// Require admin login for all actions in this file
require_admin_login();

switch ($action) {
    case 'list_all':
        // List all users in the system
        $stmt = $conn->prepare("SELECT id, name, username, user_type, created_at, pin_hash FROM users ORDER BY created_at DESC"); 
        if (!$stmt) {
            send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['has_pin'] = !empty($row['pin_hash']); 
            unset($row['pin_hash']); 
            $users[] = $row;
        }
        send_json_response(['success' => true, 'users' => $users]);
        $stmt->close();
        break;

    case 'get_stats':
        // Get overall statistics (total parents, children, tasks)
        $stats = [
            'total_parents' => 0,
            'total_children' => 0,
            'total_tasks' => 0
        ];

        // Total Parents
        $stmt_parents = $conn->prepare("SELECT COUNT(id) AS count FROM users WHERE user_type = 'parent'");
        if ($stmt_parents && $stmt_parents->execute()) {
            $result = $stmt_parents->get_result();
            $row = $result->fetch_assoc();
            $stats['total_parents'] = (int)$row['count'];
        }
        $stmt_parents->close();

        // Total Children
        $stmt_children = $conn->prepare("SELECT COUNT(id) AS count FROM children");
        if ($stmt_children && $stmt_children->execute()) {
            $result = $stmt_children->get_result();
            $row = $result->fetch_assoc();
            $stats['total_children'] = (int)$row['count'];
        }
        $stmt_children->close();

        // Total Tasks
        $stmt_tasks = $conn->prepare("SELECT COUNT(id) AS count FROM tasks");
        if ($stmt_tasks && $stmt_tasks->execute()) {
            $result = $stmt_tasks->get_result();
            $row = $result->fetch_assoc();
            $stats['total_tasks'] = (int)$row['count'];
        }
        $stmt_tasks->close();

        send_json_response(['success' => true, 'stats' => $stats]);
        break;
        
    case 'update_user_type':
        $userId = $input['user_id'] ?? null;
        $newUserType = $input['new_user_type'] ?? null;

        if (!$userId || !in_array($newUserType, ['parent', 'admin'])) {
            send_json_response(['success' => false, 'message' => 'Invalid user ID or user type.'], 400);
        }

        // Prevent changing own user type (or ensure at least one admin remains if that's a rule)
        if ($userId == $_SESSION['admin_user_id'] && $newUserType != 'admin') {
            send_json_response(['success' => false, 'message' => 'Cannot change your own user type from admin.'], 403);
        }

        $stmt = $conn->prepare("UPDATE users SET user_type = ? WHERE id = ?");
        if (!$stmt) {
            send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500);
        }
        $stmt->bind_param("si", $newUserType, $userId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'User type updated successfully.']);
            } else {
                send_json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update user type: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'change_username': 
        $userId = $input['user_id'] ?? null;
        $newUsername = $input['new_username'] ?? null;

        if (!$userId || empty($newUsername)) {
            send_json_response(['success' => false, 'message' => 'User ID and new username are required.'], 400);
        }

        // Check if new username already exists (for other users)
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        if (!$stmt_check) { send_json_response(['success' => false, 'message' => 'DB error (username check): ' . $conn->error], 500); }
        $stmt_check->bind_param("si", $newUsername, $userId);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            send_json_response(['success' => false, 'message' => 'Username already taken.'], 409);
        }
        $stmt_check->close();

        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (change username): ' . $conn->error], 500); }
        $stmt->bind_param("si", $newUsername, $userId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Username updated successfully.']);
            } else {
                send_json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update username: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'reset_password': 
        $userId = $input['user_id'] ?? null;
        $newPassword = $input['new_password'] ?? null;

        if (!$userId || empty($newPassword)) {
            send_json_response(['success' => false, 'message' => 'User ID and new password are required.'], 400);
        }
        if (strlen($newPassword) < 6) {
            send_json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Also clear the recovery code when password is reset for security
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, recovery_code_hash = NULL WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (reset password): ' . $conn->error], 500); }
        $stmt->bind_param("si", $passwordHash, $userId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Password reset and recovery code cleared successfully.']);
            } else {
                send_json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to reset password: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;
    
    case 'generate_new_recovery_code': 
        $userId = $input['user_id'] ?? null;

        if (!$userId) {
            send_json_response(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        // The generate_recovery_code function is now defined in this file (duplicated)
        // Ensure this function is available (it should be via its local definition)
        if (!function_exists('generate_recovery_code')) {
            send_json_response(['success' => false, 'message' => 'Recovery code generation function not found. Internal error.'], 500);
        }

        $newRecoveryCodePlain = generate_recovery_code();
        $newRecoveryCodeHash = password_hash($newRecoveryCodePlain, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET recovery_code_hash = ? WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (generate recovery code): ' . $conn->error], 500); }
        $stmt->bind_param("si", $newRecoveryCodeHash, $userId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'New recovery code generated.', 'recovery_code' => $newRecoveryCodePlain]);
            } else {
                send_json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to generate new recovery code: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'set_pin': 
        $userId = $input['user_id'] ?? null;
        $pin = $input['pin'] ?? null; 

        if (!$userId) {
            send_json_response(['success' => false, 'message' => 'User ID is required.'], 400);
        }

        $pin_hash = null;
        $message = 'PIN cleared successfully.';
        if (!empty($pin)) {
            if (!preg_match('/^\d{4}$/', $pin)) {
                send_json_response(['success' => false, 'message' => 'PIN must be exactly 4 digits.'], 400);
            }
            $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
            $message = 'PIN set successfully.';
        }

        $stmt = $conn->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (set pin): ' . $conn->error], 500); }
        $stmt->bind_param("si", $pin_hash, $userId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => $message]);
            } else {
                send_json_response(['success' => false, 'message' => 'User not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to set PIN: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'delete_user': // NEW ACTION: Delete a user
        $userIdToDelete = $input['user_id'] ?? null;

        if (!$userIdToDelete) {
            send_json_response(['success' => false, 'message' => 'User ID is required for deletion.'], 400);
        }

        // Prevent admin from deleting themselves
        if ($userIdToDelete == $_SESSION['admin_user_id']) {
            send_json_response(['success' => false, 'message' => 'You cannot delete your own admin account.'], 403);
        }

        // Check if this is the last admin account (optional, but good practice to prevent lockout)
        // You would need to add logic here to count admin users before allowing deletion.
        // For simplicity, we'll omit this advanced check for now.

        $conn->begin_transaction();
        try {
            // Get username for confirmation message
            $stmt_get_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
            if (!$stmt_get_username) { throw new Exception("DB error (get username for delete): " . $conn->error); }
            $stmt_get_username->bind_param("i", $userIdToDelete);
            $stmt_get_username->execute();
            $result_username = $stmt_get_username->get_result();
            $user_data = $result_username->fetch_assoc();
            $stmt_get_username->close();

            if (!$user_data) {
                throw new Exception("User not found.");
            }
            $deleted_username = $user_data['username'];

            // The 'DELETE' query itself. Foreign key constraints with ON DELETE CASCADE will handle
            // deleting associated data in 'children', 'tasks', 'rewards', 'notifications', etc.
            $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$stmt_delete) { throw new Exception("DB error (delete user): " . $conn->error); }
            $stmt_delete->bind_param("i", $userIdToDelete);

            if (!$stmt_delete->execute()) {
                throw new Exception("Failed to delete user: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            if ($stmt_delete->affected_rows > 0) {
                $conn->commit();
                send_json_response(['success' => true, 'message' => "User '{$deleted_username}' and all associated data deleted successfully."]);
            } else {
                throw new Exception("User not found or no changes made.");
            }

        } catch (Exception $e) {
            $conn->rollback();
            send_json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    default:
        send_json_response(['success' => false, 'message' => 'Invalid admin user action.'], 400);
        break;
}

$conn->close();