<?php // api/auth.php - Handles User Authentication

// CORS Headers - Add these at the very top
header("Access-Control-Allow-Origin: *"); // Allows all origins
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// IMPORTANT: Start session at the very beginning of any script that uses sessions.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../db.php'; // Assumes db.php is one directory up

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

// --- NEW HELPER: Generate Recovery Code ---
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


// --- ACTION: REGISTER ---
if ($action == 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    $name = $input['name'] ?? null;
    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;
    $confirmPassword = $input['confirmPassword'] ?? null;
    $userType = 'parent';

    // Basic validation
    if (empty($name) || empty($username) || empty($password) || empty($confirmPassword)) {
        send_json_response(['success' => false, 'message' => 'All fields are required.'], 400);
    }
    if (strlen($password) < 6) {
        send_json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
    }
    if ($password !== $confirmPassword) {
        send_json_response(['success' => false, 'message' => 'Passwords do not match.'], 400);
    }

    $conn = get_db_connection();

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        send_json_response(['success' => false, 'message' => 'Database prepare error (username check): ' . $conn->error], 500);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        send_json_response(['success' => false, 'message' => 'Username already registered.'], 409);
    }
    $stmt->close();

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // --- NEW: Generate and hash recovery code ---
    $recovery_code_plain = generate_recovery_code();
    $recovery_code_hash = password_hash($recovery_code_plain, PASSWORD_DEFAULT);

    // Insert new user with recovery code
    $stmt = $conn->prepare("INSERT INTO users (name, username, password_hash, user_type, recovery_code_hash) VALUES (?, ?, ?, ?, ?)");
     if (!$stmt) {
        send_json_response(['success' => false, 'message' => 'Database prepare error (insert user): ' . $conn->error], 500);
    }
    $stmt->bind_param("sssss", $name, $username, $password_hash, $userType, $recovery_code_hash);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        // Automatically log in the user after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $userType;

        send_json_response([
            'success' => true, 
            'message' => 'Registration successful!',
            'user' => [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'userType' => $userType,
                'has_pin_set' => false
            ],
            'recovery_code' => $recovery_code_plain // --- NEW: Send plain code to frontend
        ]);
    } else {
        send_json_response(['success' => false, 'message' => 'Registration failed: ' . $stmt->error], 500);
    }
    $stmt->close();
    $conn->close();
}

// --- ACTION: LOGIN ---
elseif ($action == 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }

    $username = $input['username'] ?? null;
    $password = $input['password'] ?? null;

    if (empty($username) || empty($password)) {
        send_json_response(['success' => false, 'message' => 'Username and password are required.'], 400);
    }

    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, name, username, password_hash, user_type, pin_hash FROM users WHERE username = ?");
    if (!$stmt) {
        send_json_response(['success' => false, 'message' => 'Database prepare error: ' . $conn->error], 500);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); 

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            send_json_response([
                'success' => true, 
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'userType' => $user['user_type'],
                    'has_pin_set' => !empty($user['pin_hash'])
                ]
            ]);
        } else {
            send_json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
        }
    } else {
        send_json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
    }
    $stmt->close();
    $conn->close();
}

// --- ACTION: LOGOUT ---
elseif ($action == 'logout') {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    send_json_response(['success' => true, 'message' => 'Logout successful.']);
}

// --- ACTION: STATUS (Check if user is logged in) ---
elseif ($action == 'status') {
    if (isset($_SESSION['user_id'])) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT pin_hash FROM users WHERE id = ?");
        if (!$stmt) {
             send_json_response(['success' => false, 'message' => 'Database error checking PIN status.'], 500);
        }
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_pin_data = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        send_json_response([
            'success' => true,
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'username' => $_SESSION['username'],
                'userType' => $_SESSION['user_type'],
                'has_pin_set' => !empty($user_pin_data['pin_hash'])
            ]
        ]);
    } else {
        send_json_response(['success' => true, 'loggedIn' => false]);
    }
}

// --- NEW: ACTION: VERIFY RECOVERY CODE ---
elseif ($action == 'verify_recovery_code') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    
    $recovery_code_attempt = $input['recovery_code'] ?? null;
    if (empty($recovery_code_attempt)) {
        send_json_response(['success' => false, 'message' => 'Recovery code is required.'], 400);
    }

    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, username, recovery_code_hash FROM users");
    if (!$stmt) { send_json_response(['success' => false, 'message' => 'Database error: ' . $conn->error], 500); }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user_found = false;
    while ($user = $result->fetch_assoc()) {
        if (!empty($user['recovery_code_hash']) && password_verify($recovery_code_attempt, $user['recovery_code_hash'])) {
            $user_found = true;
            send_json_response([
                'success' => true,
                'message' => 'Recovery code verified.',
                'userId' => $user['id'],
                'username' => $user['username']
            ]);
            break; 
        }
    }
    $stmt->close();
    $conn->close();

    if (!$user_found) {
        send_json_response(['success' => false, 'message' => 'Invalid recovery code.'], 401);
    }
}

// --- NEW: ACTION: RESET PASSWORD ---
elseif ($action == 'reset_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    
    $userId = $input['userId'] ?? null;
    $newPassword = $input['newPassword'] ?? null;
    $confirmPassword = $input['confirmPassword'] ?? null;

    if (empty($userId) || empty($newPassword) || empty($confirmPassword)) {
        send_json_response(['success' => false, 'message' => 'All fields are required.'], 400);
    }
    if (strlen($newPassword) < 6) {
        send_json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
    }
    if ($newPassword !== $confirmPassword) {
        send_json_response(['success' => false, 'message' => 'Passwords do not match.'], 400);
    }

    $new_password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $conn = get_db_connection();
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if (!$stmt) { send_json_response(['success' => false, 'message' => 'Database error: ' . $conn->error], 500); }
    $stmt->bind_param("si", $new_password_hash, $userId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            send_json_response(['success' => true, 'message' => 'Password has been successfully reset. You can now log in.']);
        } else {
            send_json_response(['success' => false, 'message' => 'User not found.'], 404);
        }
    } else {
        send_json_response(['success' => false, 'message' => 'Failed to reset password: ' . $stmt->error], 500);
    }
    $stmt->close();
    $conn->close();
}


else {
    send_json_response(['success' => false, 'message' => 'Invalid action or not logged in for certain actions.'], 400);
}

?>
