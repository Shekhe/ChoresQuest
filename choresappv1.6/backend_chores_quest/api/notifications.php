<?php // api/notifications.php - Handles Notifications for Parents

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

// =============================================
// --- NOTIFICATIONS API ACTIONS ---
// =============================================
switch ($action) {
    case 'list': // List all notifications for the logged-in parent
        $parent_user_id = require_parent_login();
        
        // FIX: Remove 'Z' suffix to send as local time, not UTC. The DB connection now handles the timezone.
        $stmt = $conn->prepare("SELECT n.id, n.child_id, c.name as child_name, n.task_id, t.title as task_title, n.reward_id, r.title as reward_title, n.notification_type, n.message, n.is_read, DATE_FORMAT(n.created_at, '%Y-%m-%dT%H:%i:%s') as created_at_iso 
                                FROM notifications n
                                LEFT JOIN children c ON n.child_id = c.id
                                LEFT JOIN tasks t ON n.task_id = t.id
                                LEFT JOIN rewards r ON n.reward_id = r.id
                                WHERE n.parent_user_id = ? 
                                ORDER BY n.created_at DESC");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (list notifications prepare): ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        send_json_response(['success' => true, 'notifications' => $notifications]);
        $stmt->close();
        break;

    case 'mark_read':
        $parent_user_id = require_parent_login();
        $notification_id = $input['notification_id'] ?? null;

        if (!$notification_id) {
            send_json_response(['success' => false, 'message' => 'Notification ID is required.'], 400);
        }
        $notification_id = intval($notification_id);

        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND parent_user_id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("ii", $notification_id, $parent_user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Notification marked as read.']);
            } else {
                send_json_response(['success' => false, 'message' => 'Notification not found or already read.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to mark notification as read: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;
    
    case 'mark_all_read':
        $parent_user_id = require_parent_login();
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE parent_user_id = ? AND is_read = FALSE");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        
        if ($stmt->execute()) {
            send_json_response(['success' => true, 'message' => 'All notifications marked as read.']);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to mark all notifications as read: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    default:
        send_json_response(['success' => false, 'message' => 'Invalid notification action.'], 400);
        break;
}

$conn->close();
?>