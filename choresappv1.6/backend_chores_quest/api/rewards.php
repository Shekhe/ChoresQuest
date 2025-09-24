<?php // api/rewards.php - Handles Reward Management

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
// --- REWARDS API ACTIONS ---
// =============================================
switch ($action) {
    case 'create':
        $parent_user_id = require_parent_login();
        $title = $input['title'] ?? null;
        $required_points = isset($input['requiredPoints']) ? intval($input['requiredPoints']) : 0;
        // This now receives the final URL from the frontend after upload
        $image_url = $input['image'] ?? null;

        if (empty($title) || $required_points <= 0) {
            send_json_response(['success' => false, 'message' => 'Title and positive required points are required.'], 400);
        }

        // When creating, rewards are active by default
        $stmt = $conn->prepare("INSERT INTO rewards (parent_user_id, title, required_points, image_url, is_active) VALUES (?, ?, ?, ?, 1)");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("isis", $parent_user_id, $title, $required_points, $image_url);

        if ($stmt->execute()) {
            $new_reward_id = $stmt->insert_id;
            $result_stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
            if (!$result_stmt) { send_json_response(['success' => false, 'message' => 'DB error (fetch new reward): ' . $conn->error], 500); }
            $result_stmt->bind_param("i", $new_reward_id);
            $result_stmt->execute();
            $new_reward = $result_stmt->get_result()->fetch_assoc();
            $result_stmt->close();
            send_json_response(['success' => true, 'message' => 'Reward created successfully.', 'reward' => $new_reward]);
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to create reward: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'list': 
        $rewards = [];
        $parent_user_id_for_list = null;
        if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'parent') {
            $parent_user_id_for_list = $_SESSION['user_id'];
        }
        
        // Determine if the request is from the parent (to see all rewards) or child (to see only active)
        // This check is often implicit by who is logged in, but can be made explicit via a parameter if needed.
        // For simplicity, if parent is logged in, show all. If no parent context (e.g., public or kid flow), filter by active.
        // The frontend child module will explicitly call this while activeKidProfile is set.
        $show_all_rewards = (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'parent');

        if ($parent_user_id_for_list) {
            $sql = "SELECT id, parent_user_id, title, required_points, image_url, is_active, created_at, updated_at FROM rewards WHERE parent_user_id = ? ";
            if (!$show_all_rewards) { // If not a parent logged in, only show active ones.
                $sql .= " AND is_active = TRUE";
            }
            $sql .= " ORDER BY required_points ASC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
            $stmt->bind_param("i", $parent_user_id_for_list);
        } else { 
            // If no parent user is logged in, prevent listing rewards
            send_json_response(['success' => false, 'message' => 'Cannot list rewards without parent context or active user session.'], 401);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Convert is_active tinyint to boolean for JavaScript
            $row['is_active'] = (bool)$row['is_active'];
            $rewards[] = $row;
        }
        send_json_response(['success' => true, 'rewards' => $rewards]);
        $stmt->close();
        break;

    case 'update':
        $parent_user_id = require_parent_login();
        $reward_id = $input['id'] ?? null;

        if (!$reward_id) {
            send_json_response(['success' => false, 'message' => 'Reward ID is required for update.'], 400);
        }
        $reward_id = intval($reward_id);

        $fields_to_update = [];
        $update_params = [];
        $update_types = "";

        if (isset($input['title']) && !empty($input['title'])) { 
            $fields_to_update[] = "title = ?"; $update_params[] = $input['title']; $update_types .= "s"; 
        }
        if (isset($input['requiredPoints']) && intval($input['requiredPoints']) > 0) { 
            $fields_to_update[] = "required_points = ?"; $update_params[] = intval($input['requiredPoints']); $update_types .= "i"; 
        }
        if (array_key_exists('image', $input)) { 
            $fields_to_update[] = "image_url = ?"; $update_params[] = $input['image']; $update_types .= "s"; 
        }
        // No change to is_active here, as it's handled by a separate toggle_active_status action
        // if (array_key_exists('is_active', $input)) {
        //     $fields_to_update[] = "is_active = ?"; $update_params[] = (int)$input['is_active']; $update_types .= "i";
        // }

        if (empty($fields_to_update)) {
            send_json_response(['success' => false, 'message' => 'No fields to update.'], 400);
        }

        $sql = "UPDATE rewards SET " . implode(", ", $fields_to_update) . " WHERE id = ? AND parent_user_id = ?";
        $update_params[] = $reward_id;
        $update_params[] = $parent_user_id;
        $update_types .= "ii";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param($update_types, ...$update_params);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $result_stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
                 if (!$result_stmt) { send_json_response(['success' => false, 'message' => 'DB error (fetch updated reward): ' . $conn->error], 500); }
                $result_stmt->bind_param("i", $reward_id);
                $result_stmt->execute();
                $updated_reward = $result_stmt->get_result()->fetch_assoc();
                $result_stmt->close();
                // Convert is_active tinyint to boolean for JavaScript
                $updated_reward['is_active'] = (bool)$updated_reward['is_active'];
                send_json_response(['success' => true, 'message' => 'Reward updated successfully.', 'reward' => $updated_reward]);
            } else {
                send_json_response(['success' => false, 'message' => 'Reward not found or no changes made.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update reward: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'toggle_active_status': // NEW ACTION
        $parent_user_id = require_parent_login();
        $reward_id = $input['id'] ?? null;
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : null;

        if (!$reward_id || $is_active === null) {
            send_json_response(['success' => false, 'message' => 'Reward ID and active status (true/false) are required.'], 400);
        }

        $stmt = $conn->prepare("UPDATE rewards SET is_active = ? WHERE id = ? AND parent_user_id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error (toggle active status): ' . $conn->error], 500); }
        $stmt->bind_param("iii", $is_active, $reward_id, $parent_user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Reward active status updated.', 'reward_id' => $reward_id, 'is_active' => (bool)$is_active]);
            } else {
                send_json_response(['success' => false, 'message' => 'Reward not found or not authorized, or no change in status.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to update reward active status: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'delete':
        $parent_user_id = require_parent_login();
        $reward_id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$reward_id) {
            send_json_response(['success' => false, 'message' => 'Reward ID is required.'], 400);
        }
        $reward_id = intval($reward_id);

        $stmt = $conn->prepare("DELETE FROM rewards WHERE id = ? AND parent_user_id = ?");
        if (!$stmt) { send_json_response(['success' => false, 'message' => 'DB error: ' . $conn->error], 500); }
        $stmt->bind_param("ii", $reward_id, $parent_user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                send_json_response(['success' => true, 'message' => 'Reward deleted successfully.']);
            } else {
                send_json_response(['success' => false, 'message' => 'Reward not found or not authorized.'], 404);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'Failed to delete reward: ' . $stmt->error], 500);
        }
        $stmt->close();
        break;

    case 'claim_by_kid':
        $kid_id = $input['kid_id'] ?? null; 
        $reward_id_to_claim = $input['reward_id'] ?? null;

        if (!$kid_id || !$reward_id_to_claim) {
            send_json_response(['success' => false, 'message' => 'Kid ID and Reward ID are required.'], 400);
        }
        $kid_id = intval($kid_id);
        $reward_id_to_claim = intval($reward_id_to_claim);

        $conn->begin_transaction();
        try {
            // Get child's current points and parent_user_id
            $child_stmt = $conn->prepare("SELECT points, name, parent_user_id FROM children WHERE id = ?");
            if (!$child_stmt) { throw new Exception("DB error (fetch child details)."); }
            $child_stmt->bind_param("i", $kid_id);
            $child_stmt->execute();
            $child_data = $child_stmt->get_result()->fetch_assoc();
            $child_stmt->close();

            if (!$child_data) { throw new Exception("Child not found."); }
            $current_child_points = $child_data['points'];
            $child_name = $child_data['name'];
            $parent_id_for_notification = $child_data['parent_user_id'];

            // Crucially, when a child claims, only allow if reward is_active = TRUE
            $reward_stmt = $conn->prepare("SELECT title, required_points FROM rewards WHERE id = ? AND is_active = TRUE");
            if (!$reward_stmt) { throw new Exception("DB error (fetch reward)."); }
            $reward_stmt->bind_param("i", $reward_id_to_claim);
            $reward_stmt->execute();
            $reward_data = $reward_stmt->get_result()->fetch_assoc();
            $reward_stmt->close();

            if (!$reward_data) { throw new Exception("Reward not found or not active."); }
            $required_points_for_reward = $reward_data['required_points'];
            $reward_title = $reward_data['title'];

            if ($current_child_points < $required_points_for_reward) {
                throw new Exception("Not enough points to claim this reward.");
            }

            $update_points_stmt = $conn->prepare("UPDATE children SET points = points - ? WHERE id = ?");
            if (!$update_points_stmt) { throw new Exception("DB error (deduct points)."); }
            $update_points_stmt->bind_param("ii", $required_points_for_reward, $kid_id);
            if (!$update_points_stmt->execute()) { throw new Exception("Failed to deduct points."); }
            $update_points_stmt->close();

            $log_claim_stmt = $conn->prepare("INSERT INTO claimed_rewards (child_id, reward_id, points_spent) VALUES (?, ?, ?)");
            if (!$log_claim_stmt) { throw new Exception("DB error (log claim)."); }
            $log_claim_stmt->bind_param("iii", $kid_id, $reward_id_to_claim, $required_points_for_reward);
            if (!$log_claim_stmt->execute()) { throw new Exception("Failed to log claimed reward."); }
            $log_claim_stmt->close();

            // Create notification for the parent
            if ($parent_id_for_notification) {
                $notification_message = $child_name . " claimed the reward: " . $reward_title . ".";
                $notification_type = 'reward_claimed';
                $insert_notif_stmt = $conn->prepare("INSERT INTO notifications (parent_user_id, child_id, reward_id, notification_type, message) VALUES (?, ?, ?, ?, ?)");
                if (!$insert_notif_stmt) { throw new Exception("DB error (create notification)."); }
                $insert_notif_stmt->bind_param("iiiss", $parent_id_for_notification, $kid_id, $reward_id_to_claim, $notification_type, $notification_message);
                if (!$insert_notif_stmt->execute()) { throw new Exception("Failed to create notification."); }
                $insert_notif_stmt->close();
            }

            $conn->commit();
            send_json_response([
                'success' => true, 
                'message' => 'Reward claimed successfully!',
                'new_points' => $current_child_points - $required_points_for_reward
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            send_json_response(['success' => false, 'message' => $e->getMessage()], ($e->getMessage() === "Not enough points to claim this reward." || $e->getMessage() === "Child not found." || $e->getMessage() === "Reward not found or not active.") ? 400 : 500);
        }
        break;


    default:
        send_json_response(['success' => false, 'message' => 'Invalid reward action.'], 400);
        break;
}

$conn->close();
?>