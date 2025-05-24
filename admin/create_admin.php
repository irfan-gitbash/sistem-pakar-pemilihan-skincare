<?php
require_once '../config/database.php';

try {
    // Create admin credentials
    $username = 'admin';
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // First, delete existing admin if any
    $stmt = $db->prepare("DELETE FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    
    // Insert new admin
    $stmt = $db->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashed_password]);
    
    echo "Admin account created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
