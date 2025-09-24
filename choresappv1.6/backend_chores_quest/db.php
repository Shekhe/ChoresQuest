<?php // db.php - Database Connection Function

/**
 * Establishes a connection to the MySQL database.
 * Uses credentials defined in config.php.
 *
 * @return mysqli|false The mysqli connection object on success, or false on failure (though it exits on failure).
 */
function get_db_connection() {
    // Ensure config is loaded. If db.php is in the same directory as config.php:
    require_once 'config.php'; 
    // If config.php is one directory up (e.g., db.php is in an 'includes' folder):
    // require_once __DIR__ . '/../config.php';


    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // For an API, it's better to return a JSON error than die() directly here.
        // This part is more for initial setup or direct script execution debugging.
        // In the actual API files (like auth.php), we'll handle sending JSON errors.
        error_log("Database Connection Failed: " . $conn->connect_error); // Log the error
        
        // If this script is directly accessed or included in a non-API context:
        if (php_sapi_name() !== 'cli' && !headers_sent() && basename($_SERVER['PHP_SELF']) === 'db.php') {
            http_response_code(500); // Internal Server Error
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error. Please check server logs.'
            ]);
            exit;
        } elseif (php_sapi_name() !== 'cli' && headers_sent()) {
            // If headers already sent, can't send JSON error, so just die.
             die("Database connection failed. Please check server logs.");
        }
        // If called from an API script that will handle JSON response,
        // the calling script should check the return value of get_db_connection if it could be false.
        // However, for simplicity in this setup, we'll usually let it try and fail,
        // and the API script will catch the mysqli errors if $conn is not an object.
        // A more robust approach would be to throw an exception here.
        return false; // Indicate failure
    }

    // --- FIX: Set the timezone for the database session ---
    // This ensures NOW(), CURDATE(), and other date/time functions use the correct timezone.
    $conn->query("SET time_zone = 'America/Edmonton'");
    // --- END OF FIX ---


    // Set character set
    if (!$conn->set_charset(DB_CHARSET)) {
        error_log("Error loading character set " . DB_CHARSET . ": " . $conn->error);
        if (php_sapi_name() !== 'cli' && !headers_sent() && basename($_SERVER['PHP_SELF']) === 'db.php') {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database charset error. Please check server logs.'
            ]);
            exit;
        } elseif (php_sapi_name() !== 'cli' && headers_sent()) {
             die("Database charset error. Please check server logs.");
        }
        $conn->close();
        return false; // Indicate failure
    }
    
    return $conn;
}

?>