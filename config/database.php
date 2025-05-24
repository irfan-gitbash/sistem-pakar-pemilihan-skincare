<?php
$host = 'localhost';
$dbname = 'skincare_expert';
$username = 'root';
$password = 'root';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // For backwards compatibility
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}

// Function to validate input
function validate($input, $type = 'string') {
    switch($type) {
        case 'string':
            return !empty($input) && is_string($input);
        case 'number':
            return !empty($input) && is_numeric($input);
        case 'array':
            return !empty($input) && is_array($input);
        default:
            return false;
    }
}
?>
