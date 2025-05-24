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
$error = '';
$success = '';

try {
    // Fetch diagnosis
    $stmt = $db->prepare("SELECT * FROM diagnosis_history WHERE id = ?");
    $stmt->execute([$id]);
    $diagnosis = $stmt->fetch();

    if (!$diagnosis) {
        header('Location: diagnosis_manage.php');
        exit();
    }

    // Fetch skin types for dropdown
    $skinTypes = $db->query("SELECT code, name FROM skin_types")->fetchAll();

    // Fetch symptoms for multi-select
    $symptoms = $db->query("SELECT code, name FROM symptoms")->fetchAll();

} catch (PDOException $e) {
    error_log("Diagnosis edit fetch error: " . $e->getMessage());
    header('Location: diagnosis_manage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosed_type = $_POST['diagnosed_type'] ?? '';
    $selected_symptoms = $_POST['selected_symptoms'] ?? [];
    $confidence_score = floatval($_POST['confidence_score'] ?? 0);

    if (empty($diagnosed_type) || empty($selected_symptoms)) {
        $error = 'Skin type and symptoms are required.';
    } else {
        try {
            $selected_symptoms_str = implode(',', $selected_symptoms);
            $stmt = $db->prepare("UPDATE diagnosis_history SET diagnosed_type = ?, selected_symptoms = ?, confidence_score = ? WHERE id = ?");
            $stmt->execute([$diagnosed_type, $selected_symptoms_str, $confidence_score, $id]);
            $success = 'Diagnosis updated successfully.';
        } catch (PDOException $e) {
            error_log("Diagnosis update error: " . $e->getMessage());
            $error = 'Failed to update diagnosis.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Diagnosis - Admin - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg p-4 flex justify-between items-center">
        <a href="dashboard.php" class="font-semibold text-xl text-gray-800">Admin Dashboard</a>
        <a href="diagnosis_manage.php" class="text-blue-600 hover:underline">Back to Manage Diagnoses</a>
    </nav>
    <main class="max-w-4xl mx-auto p-6 bg-white rounded shadow mt-6">
        <h1 class="text-2xl font-bold mb-4">Edit Diagnosis</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="diagnosed_type" class="block font-medium text-gray-700 mb-2">Skin Type</label>
                <select id="diagnosed_type" name="diagnosed_type" required class="w-full border border-gray-300 rounded p-2">
                    <option value="">Select Skin Type</option>
                    <?php foreach ($skinTypes as $st): ?>
                        <option value="<?php echo htmlspecialchars($st['code']); ?>" <?php if ($diagnosis['diagnosed_type'] === $st['code']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($st['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="selected_symptoms" class="block font-medium text-gray-700 mb-2">Symptoms</label>
                <select id="selected_symptoms" name="selected_symptoms[]" multiple required class="w-full border border-gray-300 rounded p-2" size="8">
                    <?php
                    $selectedSymptomsArray = explode(',', $diagnosis['selected_symptoms']);
                    foreach ($symptoms as $symptom): ?>
                        <option value="<?php echo htmlspecialchars($symptom['code']); ?>" <?php if (in_array($symptom['code'], $selectedSymptomsArray)) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($symptom['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="confidence_score" class="block font-medium text-gray-700 mb-2">Confidence Score (0 to 1)</label>
                <input type="number" step="0.01" min="0" max="1" id="confidence_score" name="confidence_score" value="<?php echo htmlspecialchars($diagnosis['confidence_score']); ?>" required class="w-full border border-gray-300 rounded p-2" />
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Diagnosis</button>
            </div>
        </form>
    </main>
</body>
</html>
