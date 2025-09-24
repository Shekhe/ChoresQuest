<?php // api/children.php - Handles Child Profile Management

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
// --- CHILDREN API ACTIONS ---
// =============================================
switch ($action) {
    case 'create':
        $parent_user_id = require_parent_login();
        $name = $input['name'] ?? null;
        // This will receive the URL of the uploaded image from the frontend
        $profile_pic_url = $input['profilePic'] ?? null; 

        if (empty($name)) {
            send_json_response(['success' => false, 'message' => 'Child name is required.'], 400);
        }

        $stmt = $conn->prepare("INSERT INTO children (parent_user_id, name, profile_pic_url, points) VALUES (?, ?, ?, 0)");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("iss", $parent_user_id, $name, $profile_pic_url);

        if ($stmt->execute()) {
            $new_child_id = $stmt->insert_id;
            $result_stmt = $conn->prepare("SELECT id, name, profile_pic_url, points, DATE_FORMAT(created_at, '%Y-%m-%d') as joinedDate, parent_user_id FROM children WHERE id = ?");
            if (!$result_stmt) { send_json_response(['success' => false, 'message' => 'DB error (fetch new child prepare): ' . $conn->error], 500); }
            $result_stmt->bind_param("i", $new_child_id);
            $result_stmt->execute();
            $new_child = $result_stmt->get_result()->fetch_assoc();
            $result_stmt->close();
            send_json_response(['success' => true, 'message' => 'Child profile created.', 'child' => $new_child]);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to create child profile: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'list': 
        $parent_user_id = require_parent_login();
        $stmt = $conn->prepare("SELECT id, name, profile_pic_url, points, DATE_FORMAT(created_at, '%Y-%m-%d') as joinedDate, parent_user_id FROM children WHERE parent_user_id = ? ORDER BY name ASC");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (list children prepare): ' . $conn->error], 500); }
        $stmt->bind_param("i", $parent_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $children = [];
        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }
        send_json_response(['success' => true, 'children' => $children]);
        $stmt->close();
        break;

    case 'update':
        $parent_user_id = require_parent_login();
        $child_id = $input['id'] ?? null;

        if (!$child_id) {
            send_json_response(['success' => false, 'message' => 'Child ID is required for update.'], 400);
        }
        $child_id = intval($child_id);

        $set_clauses = [];
        $bind_params = [];
        $types = "";

        // Only add 'name' to the update if it was provided in the input
        if (isset($input['name']) && !empty($input['name'])) {
            $set_clauses[] = "name = ?";
            $bind_params[] = $input['name'];
            $types .= "s";
        }

        // Only add 'profile_pic_url' to the update if it was provided
        // Use array_key_exists to allow for setting the URL to an empty string (removing the photo)
        if (array_key_exists('profilePic', $input)) {
            $set_clauses[] = "profile_pic_url = ?";
            $bind_params[] = $input['profilePic'];
            $types .= "s";
        }

        if (empty($set_clauses)) {
            send_json_response(['success' => false, 'message' => 'No fields provided to update.'], 400);
        }

        // Add the WHERE clause parameters
        $sql = "UPDATE children SET " . implode(", ", $set_clauses) . " WHERE id = ? AND parent_user_id = ?";
        $bind_params[] = $child_id;
        $bind_params[] = $parent_user_id;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (update child prepare): ' . $conn->error], 500); }
        $stmt->bind_param($types, ...$bind_params);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $result_stmt = $conn->prepare("SELECT id, name, profile_pic_url, points, DATE_FORMAT(created_at, '%Y-%m-%d') as joinedDate, parent_user_id FROM children WHERE id = ?");
                if (!$result_stmt) { send_json_response(['success' => false, 'message' => 'DB error (fetch updated child prepare): ' . $conn->error], 500); }
                $result_stmt->bind_param("i", $child_id);
                $result_stmt->execute();
                $updated_child = $result_stmt->get_result()->fetch_assoc();
                $result_stmt->close();
                send_json_response(['success' => true, 'message' => 'Child profile updated.', 'child' => $updated_child]);
            } else {
                send_json_response(['success' => false, 'message' => 'Child not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update child profile: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    // --- NEW ACTION: Update Child Points ---
    case 'update_points':
        $parent_user_id = require_parent_login();
        $child_id = $input['child_id'] ?? null;
        $points_change = isset($input['points_change']) ? intval($input['points_change']) : 0; // Can be positive or negative

        if (!$child_id || $points_change === 0) {
            send_json_response(['success' => false, 'message' => 'Child ID and a non-zero point change are required.'], 400);
        }

        $conn->begin_transaction();
        try {
            // First, get the current points and ensure the child belongs to the parent
            $stmt_select = $conn->prepare("SELECT points, name FROM children WHERE id = ? AND parent_user_id = ? FOR UPDATE");
            if (!$stmt_select) throw new Exception('DB error (select child points prepare): ' . $conn->error);
            $stmt_select->bind_param("ii", $child_id, $parent_user_id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();
            $child_data = $result->fetch_assoc();
            $stmt_select->close();

            if (!$child_data) {
                throw new Exception("Child not found or unauthorized to modify points.");
            }

            $new_points = $child_data['points'] + $points_change;
            // Prevent negative points if not desired (optional, depends on your game logic)
            if ($new_points < 0) {
                $new_points = 0; // Cap points at 0
            }

            $stmt_update = $conn->prepare("UPDATE children SET points = ? WHERE id = ?");
            if (!$stmt_update) throw new Exception('DB error (update points prepare): ' . $conn->error);
            $stmt_update->bind_param("ii", $new_points, $child_id);

            if (!$stmt_update->execute()) {
                throw new Exception('Failed to update child points: ' . $stmt_update->error);
            }
            $stmt_update->close();

            // Log this action as a notification for transparency
            $notification_type = ($points_change > 0) ? 'points_added_by_parent' : 'points_deducted_by_parent';
            $message = ($points_change > 0) ? 
                       "You added {$points_change} points to {$child_data['name']}'s total. New total: {$new_points}." :
                       "You deducted " . abs($points_change) . " points from {$child_data['name']}'s total. New total: {$new_points}.";
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (parent_user_id, child_id, notification_type, message) VALUES (?, ?, ?, ?)");
            if (!$notif_stmt) throw new Exception("DB error (create notification): " . $conn->error);
            $notif_stmt->bind_param("iiss", $parent_user_id, $child_id, $notification_type, $message);
            if (!$notif_stmt->execute()) {
                // Log the notification error, but don't fail the main transaction
                error_log("Failed to create point adjustment notification: " . $notif_stmt->error);
            }
            $notif_stmt->close();


            $conn->commit();
            send_json_response([
                'success' => true,
                'message' => 'Child points updated successfully.',
                'child_id' => $child_id,
                'new_points' => $new_points
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            send_json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;
    // --- END NEW ACTION ---

    case 'delete':
        $parent_user_id = require_parent_login();
        $child_id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$child_id) {
            send_json_response(['success' => false, 'message' => 'Child ID is required.'], 400);
        }
        $child_id = intval($child_id);

        $stmt = $conn->prepare("DELETE FROM children WHERE id = ? AND parent_user_id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (delete child prepare): ' . $conn->error], 500); }
        $stmt->bind_param("ii", $child_id, $parent_user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Child profile deleted.']);
            } else {
                send_json_response(['success' => false, 'message' => 'Child not found or not authorized.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to delete child profile: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    default:
        send_json_response(['success' => false, 'message' => 'Invalid child action.'], 400);
        break;
}

$conn->close();
?>