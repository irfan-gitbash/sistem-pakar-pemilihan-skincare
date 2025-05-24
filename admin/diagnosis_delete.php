<?php
require_once '../config/database.php';
require_once 'auth.php';

// Verify authentication
requireAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: diagnosis_manage.php');
    exit();
}

$id = intval($_GET['id']);

try {
    // Delete diagnosis entry
    $stmt = $db->prepare("DELETE FROM diagnosis_history WHERE id = ?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    error_log("Diagnosis delete error: " . $e->getMessage());
    // Optionally, set an error message in session or redirect with error
}

header('Location: diagnosis_manage.php');
exit();
?>
