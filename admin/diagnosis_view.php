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
    $stmt = $db->prepare("
        SELECT dh.*, st.name as skin_type_name, GROUP_CONCAT(s.name) as symptom_names
        FROM diagnosis_history dh
        JOIN skin_types st ON dh.diagnosed_type = st.code
        LEFT JOIN symptoms s ON FIND_IN_SET(s.code, dh.selected_symptoms)
        WHERE dh.id = ?
        GROUP BY dh.id
    ");
    $stmt->execute([$id]);
    $diagnosis = $stmt->fetch();

    if (!$diagnosis) {
        header('Location: diagnosis_manage.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Diagnosis view error: " . $e->getMessage());
    header('Location: diagnosis_manage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Diagnosis - Admin - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <a href="dashboard.php" class="font-semibold text-xl text-gray-800">Admin Dashboard</a>
        <a href="diagnosis_manage.php" class="text-blue-600 hover:underline">Back to Manage Diagnoses</a>
    </nav>
    <main class="max-w-4xl mx-auto p-6 bg-white rounded shadow mt-6">
        <h1 class="text-2xl font-bold mb-4">Diagnosis Details</h1>
        <div class="mb-4">
            <strong>ID:</strong> <?php echo htmlspecialchars($diagnosis['id']); ?>
        </div>
        <div class="mb-4">
            <strong>Skin Type:</strong> <?php echo htmlspecialchars($diagnosis['skin_type_name']); ?>
        </div>
        <div class="mb-4">
            <strong>Symptoms:</strong> <?php echo htmlspecialchars($diagnosis['symptom_names']); ?>
        </div>
        <div class="mb-4">
            <strong>Confidence Score:</strong> <?php echo number_format($diagnosis['confidence_score'] * 100, 1); ?>%
        </div>
        <div class="mb-4">
            <strong>Created At:</strong> <?php echo date('d M Y H:i', strtotime($diagnosis['created_at'])); ?>
        </div>
    </main>
</body>
</html>
