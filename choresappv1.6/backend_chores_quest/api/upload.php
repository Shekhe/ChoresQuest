<?php // api/upload.php - Handles Image File Uploads (Enhanced Security & Transparency Fix)

// --- Security Best Practice: Strict CORS Policy ---
// Replace '*' with your actual frontend domain to prevent unauthorized access from other websites.
// For example: header("Access-Control-Allow-Origin: https://www.choresquest.com");
$allowed_origin = 'https://choresquest.com'; // <-- IMPORTANT: SET YOUR DOMAIN HERE
// For local development, you might use:
// $allowed_origin = 'http://localhost:5500'; // Or whatever your local server port is

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed_origin) {
    header("Access-Control-Allow-Origin: " . $allowed_origin);
} else {
    header("Access-Control-Allow-Origin: *"); // Keep as wildcard for now, but update for production
}

header("Access-control-allow-methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!function_exists('send_json_response')) {
    function send_json_response($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parent') {
    send_json_response(['success' => false, 'message' => 'Unauthorized. Parent login required.'], 401);
}

if (!isset($_FILES['imageFile']) || $_FILES['imageFile']['error'] !== UPLOAD_ERR_OK) {
    send_json_response(['success' => false, 'message' => 'No file uploaded or an upload error occurred.'], 400);
}

$file = $_FILES['imageFile'];
$target_dir = __DIR__ . '/../uploads/';

if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
         send_json_response(['success' => false, 'message' => 'Failed to create uploads directory.'], 500);
    }
}

$max_file_size = 5 * 1024 * 1024;
if ($file['size'] > $max_file_size) {
    send_json_response(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'], 400);
}

$check = getimagesize($file["tmp_name"]);
if ($check === false) {
    send_json_response(['success' => false, 'message' => 'File is not a valid image.'], 400);
}

$imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($imageFileType, $allowed_types)) {
    send_json_response(['success' => false, 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.'], 400);
}

// --- Sanitize by Re-creating the Image ---
$source_image = null;
switch ($imageFileType) {
    case 'jpg':
    case 'jpeg':
        $source_image = @imagecreatefromjpeg($file["tmp_name"]);
        break;
    case 'png':
        $source_image = @imagecreatefrompng($file["tmp_name"]);
        break;
    case 'gif':
        $source_image = @imagecreatefromgif($file["tmp_name"]);
        break;
}

if (!$source_image) {
    send_json_response(['success' => false, 'message' => 'Could not process the uploaded image. It may be corrupt.'], 400);
}

// --- FIX: Handle PNG transparency correctly ---
$success = false;
if ($imageFileType === 'png') {
    // For PNGs, preserve transparency and save as PNG
    $unique_filename_sanitized = uniqid('img_sanitized_', true) . '.png';
    $target_file_path_sanitized = $target_dir . $unique_filename_sanitized;

    // These settings are crucial for preserving transparency
    imagepalettetotruecolor($source_image);
    imagealphablending($source_image, true);
    imagesavealpha($source_image, true);
    
    $success = imagepng($source_image, $target_file_path_sanitized);

} else {
    // For JPG, GIF, save as JPEG for consistency and compression
    $unique_filename_sanitized = uniqid('img_sanitized_', true) . '.jpeg';
    $target_file_path_sanitized = $target_dir . $unique_filename_sanitized;

    $success = imagejpeg($source_image, $target_file_path_sanitized, 90); // 90 is the quality
}

imagedestroy($source_image);

if ($success) {
    // Set secure file permissions
    chmod($target_file_path_sanitized, 0644);

    $public_url = 'backend_chores_quest/uploads/' . $unique_filename_sanitized;

    send_json_response([
        'success' => true,
        'message' => 'File uploaded successfully.',
        'url' => $public_url
    ]);
} else {
    send_json_response(['success' => false, 'message' => 'Sorry, there was an error saving the processed image.'], 500);
}
?>
