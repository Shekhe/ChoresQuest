<?php // admin/api/auth.php - Handles Admin User Authentication

// CORS Headers
header("Access-Control-Allow-Origin: *"); // For development, update to specific admin domain in production
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection (path is relative to this file)
require_once '../../backend_chores_quest/db.php'; 

header('Content-Type: application/json'); // All responses will be JSON

// Get the action from the query string
$action = $_GET['action'] ?? '';

// Get input data (for POST requests, typically JSON)
$input = json_decode(file_get_contents('php://input'), true);

// --- Helper function to send JSON response ---
function send_json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// --- ACTION: LOGIN (for Admin users) ---
if ($action == 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;

    if (empty($username) || empty($password)) {
        send_json_response(['success' => false, 'message' => 'Username and password are required.'], 400);
    }

    $conn = get_db_connection();
    if (!$conn) {
        send_json_response(['success' => false, 'message' => 'Database connection failed.'], 500);
    }

    // Select user by username AND check if they are an 'admin'
    $stmt = $conn->prepare("SELECT id, name, username, password_hash, user_type FROM users WHERE username = ? AND user_type = 'admin'");
    if (!$stmt) {
        send_json_response(['success' => false, 'message' => 'Database prepare error: ' . $conn->error], 500);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Regeneration of session ID for security
            session_regenerate_id(true); 

            // Set session variables for admin
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_user_name'] = $user['name'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_user_type'] = $user['user_type']; // Should be 'admin'

            send_json_response([
                'success' => true, 
                'message' => 'Admin login successful!',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'userType' => $user['user_type']
                ]
            ]);
        } else {
            send_json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
        }
    } else {
        // User not found or not an admin
        send_json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
    }
    $stmt->close();
    $conn->close();
}

// --- ACTION: LOGOUT (for Admin users) ---
elseif ($action == 'logout') {
    // Clear specific admin session variables
    unset($_SESSION['admin_user_id']);
    unset($_SESSION['admin_user_name']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_user_type']);

    // If no other session variables are left, consider destroying the session.
    // For this isolated admin section, it's safer to just unset admin-specific keys.
    // If you want a full session destroy (affecting parent login in same browser),
    // you'd need more complex logic. For now, this is sufficient.
    
    send_json_response(['success' => true, 'message' => 'Admin logout successful.']);
}

// --- ACTION: STATUS (Check if Admin is logged in) ---
elseif ($action == 'status') {
    if (isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_type'] === 'admin') {
        send_json_response([
            'success' => true,
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['admin_user_id'],
                'name' => $_SESSION['admin_user_name'],
                'username' => $_SESSION['admin_username'],
                'userType' => $_SESSION['admin_user_type']
            ]
        ]);
    } else {
        send_json_response(['success' => true, 'loggedIn' => false]);
    }
}

else {
    send_json_response(['success' => false, 'message' => 'Invalid action.'], 400);
}