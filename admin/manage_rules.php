<?php
require_once '../config/database.php';
require_once 'auth.php';

// Verify authentication
requireAuth();

$error = '';
$success = '';

// Handle form submissions
if (isValidPostRequest()) {
    if (isset($_POST['add']) || isset($_POST['edit'])) {
        $skin_type_code = sanitizeInput($_POST['skin_type_code']);
        $symptom_codes = isset($_POST['symptom_codes']) ? $_POST['symptom_codes'] : [];
        $confidence_score = floatval($_POST['confidence_score']);

        // Validate confidence score
        if ($confidence_score < 0 || $confidence_score > 1) {
            $error = "Skor kepercayaan harus antara 0 dan 1";
        } else {
            try {
                $symptom_codes_str = implode(',', $symptom_codes);

                if (isset($_POST['add'])) {
                    // Check if rule already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM rules WHERE skin_type_code = ?");
                    $stmt->execute([$skin_type_code]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Aturan untuk jenis kulit ini sudah ada!";
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO rules (skin_type_code, symptom_codes, confidence_score) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$skin_type_code, $symptom_codes_str, $confidence_score]);

                        logActivity($db, 'create', 'rules', $db->lastInsertId(), 
                            "Added new rule for skin type: $skin_type_code");
                        $success = "Aturan berhasil ditambahkan!";
                    }
                } else {
                    $id = $_POST['id'];
                    $stmt = $db->prepare("
                        UPDATE rules 
                        SET skin_type_code = ?, symptom_codes = ?, confidence_score = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$skin_type_code, $symptom_codes_str, $confidence_score, $id]);

                    logActivity($db, 'update', 'rules', $id, 
                        "Updated rule for skin type: $skin_type_code");
                    $success = "Aturan berhasil diperbarui!";
                }
            } catch (PDOException $e) {
                error_log("Error managing rules: " . $e->getMessage());
                $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
            }
        }
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        try {
            $stmt = $db->prepare("SELECT skin_type_code FROM rules WHERE id = ?");
            $stmt->execute([$id]);
            $skin_type_code = $stmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM rules WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($db, 'delete', 'rules', $id, 
                "Deleted rule for skin type: $skin_type_code");
            $success = "Aturan berhasil dihapus!";
        } catch (PDOException $e) {
            error_log("Error deleting rule: " . $e->getMessage());
            $error = "Terjadi kesalahan saat menghapus data.";
        }
    }
}

// Fetch all data
$skin_types = $db->query("SELECT * FROM skin_types ORDER BY code")->fetchAll();
$symptoms = $db->query("SELECT * FROM symptoms ORDER BY code")->fetchAll();
$rules = $db->query("
    SELECT r.*, st.name as skin_type_name 
    FROM rules r 
    JOIN skin_types st ON r.skin_type_code = st.code 
    ORDER BY r.skin_type_code
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aturan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-800 hover:text-blue-500">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <span class="text-xl font-semibold text-gray-800">Kelola Aturan</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showAddModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Tambah Aturan
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>

        <!-- Rules List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Kulit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gejala</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor Kepercayaan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($rule['skin_type_code']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($rule['skin_type_name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <?php 
                                $rule_symptoms = explode(',', $rule['symptom_codes']);
                                foreach ($rule_symptoms as $code):
                                    $symptom = array_filter($symptoms, function($s) use ($code) {
                                        return $s['code'] === $code;
                                    });
                                    $symptom = reset($symptom);
                                    if ($symptom):
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($symptom['code']); ?>
                                </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo number_format($rule['confidence_score'] * 100, 0) . '%'; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($rule)); ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="confirmDelete(<?php echo $rule['id']; ?>, '<?php echo $rule['skin_type_code']; ?>')" 
                                    class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="ruleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" id="modalTitle">Tambah Aturan</h3>
                <button onclick="closeModal()" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="ruleId">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis Kulit</label>
                    <select name="skin_type_code" id="ruleSkinType" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Jenis Kulit</option>
                        <?php foreach ($skin_types as $type): ?>
                        <option value="<?php echo $type['code']; ?>">
                            <?php echo $type['code'] . ' - ' . $type['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gejala-gejala</label>
                    <div class="mt-1 max-h-60 overflow-y-auto border rounded-md p-4 space-y-2">
                        <?php foreach ($symptoms as $symptom): ?>
                        <div class="flex items-center">
                            <input type="checkbox" name="symptom_codes[]" 
                                   value="<?php echo $symptom['code']; ?>"
                                   id="symptom_<?php echo $symptom['code']; ?>"
                                   class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <label for="symptom_<?php echo $symptom['code']; ?>" class="ml-2 text-sm text-gray-700">
                                <?php echo $symptom['code'] . ' - ' . $symptom['name']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Skor Kepercayaan (0-1)
                    </label>
                    <input type="number" name="confidence_score" id="ruleConfidence" 
                           step="0.01" min="0" max="1" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Batal
                    </button>
                    <button type="submit" name="add" id="submitBtn"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-md hover:bg-blue-600">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Konfirmasi Hapus</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus aturan untuk jenis kulit <span id="deleteItemCode" class="font-medium"></span>?
                    </p>
                </div>
                <div class="flex justify-center mt-4 space-x-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="id" id="deleteItemId">
                        <button type="button" onclick="closeDeleteModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="submit" name="delete"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-md hover:bg-red-600">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showAddModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Aturan';
        document.getElementById('submitBtn').name = 'add';
        document.getElementById('ruleModal').classList.remove('hidden');
        // Clear form
        document.getElementById('ruleId').value = '';
        document.getElementById('ruleSkinType').value = '';
        document.getElementById('ruleConfidence').value = '1.00';
        // Uncheck all symptoms
        document.querySelectorAll('input[name="symptom_codes[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    function showEditModal(rule) {
        document.getElementById('modalTitle').textContent = 'Edit Aturan';
        document.getElementById('submitBtn').name = 'edit';
        document.getElementById('ruleModal').classList.remove('hidden');
        // Fill form
        document.getElementById('ruleId').value = rule.id;
        document.getElementById('ruleSkinType').value = rule.skin_type_code;
        document.getElementById('ruleConfidence').value = rule.confidence_score;
        // Check appropriate symptoms
        const symptomCodes = rule.symptom_codes.split(',');
        document.querySelectorAll('input[name="symptom_codes[]"]').forEach(checkbox => {
            checkbox.checked = symptomCodes.includes(checkbox.value);
        });
    }

    function closeModal() {
        document.getElementById('ruleModal').classList.add('hidden');
    }

    function confirmDelete(id, code) {
        document.getElementById('deleteItemId').value = id;
        document.getElementById('deleteItemCode').textContent = code;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    </script>
</body>
</html>
