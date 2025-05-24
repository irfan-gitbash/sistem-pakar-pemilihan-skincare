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
        $code = sanitizeInput($_POST['code']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $severity = (int)$_POST['severity'];

        try {
            if (isset($_POST['add'])) {
                // Check if code already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM symptoms WHERE code = ?");
                $stmt->execute([$code]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Kode gejala sudah ada!";
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO symptoms (code, name, description, category, severity) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$code, $name, $description, $category, $severity]);

                    logActivity($db, 'create', 'symptoms', $db->lastInsertId(), "Added new symptom: $code");
                    $success = "Gejala berhasil ditambahkan!";
                }
            } else {
                $id = $_POST['id'];
                $stmt = $db->prepare("
                    UPDATE symptoms 
                    SET code = ?, name = ?, description = ?, category = ?, severity = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$code, $name, $description, $category, $severity, $id]);

                logActivity($db, 'update', 'symptoms', $id, "Updated symptom: $code");
                $success = "Gejala berhasil diperbarui!";
            }
        } catch (PDOException $e) {
            error_log("Error managing symptoms: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        try {
            $stmt = $db->prepare("SELECT code FROM symptoms WHERE id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM symptoms WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($db, 'delete', 'symptoms', $id, "Deleted symptom: $code");
            $success = "Gejala berhasil dihapus!";
        } catch (PDOException $e) {
            error_log("Error deleting symptom: " . $e->getMessage());
            $error = "Terjadi kesalahan saat menghapus data.";
        }
    }
}

// Fetch all symptoms
$stmt = $db->query("SELECT * FROM symptoms ORDER BY code");
$symptoms = $stmt->fetchAll();

// Get symptom categories
$categories = ['Appearance', 'Texture', 'Condition', 'Behavior'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Gejala - Admin</title>
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
                    <span class="text-xl font-semibold text-gray-800">Kelola Gejala</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showAddModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Tambah Gejala
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

        <!-- Symptoms List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tingkat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($symptoms as $symptom): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($symptom['code']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo htmlspecialchars($symptom['name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                switch($symptom['category']) {
                                    case 'Appearance': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'Texture': echo 'bg-green-100 text-green-800'; break;
                                    case 'Condition': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'Behavior': echo 'bg-purple-100 text-purple-800'; break;
                                }
                                ?>">
                                <?php echo htmlspecialchars($symptom['category']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                            $severity = (int)$symptom['severity'];
                            echo str_repeat('★', $severity) . str_repeat('☆', 3 - $severity);
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($symptom)); ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="confirmDelete(<?php echo $symptom['id']; ?>, '<?php echo $symptom['code']; ?>')" 
                                    class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="symptomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" id="modalTitle">Tambah Gejala</h3>
                <button onclick="closeModal()" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="symptomId">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode</label>
                    <input type="text" name="code" id="symptomCode" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama</label>
                    <input type="text" name="name" id="symptomName" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="description" id="symptomDescription" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select name="category" id="symptomCategory" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tingkat Keparahan</label>
                    <select name="severity" id="symptomSeverity" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Ringan (★☆☆)</option>
                        <option value="2">Sedang (★★☆)</option>
                        <option value="3">Berat (★★★)</option>
                    </select>
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
                        Apakah Anda yakin ingin menghapus gejala <span id="deleteItemCode" class="font-medium"></span>?
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
        document.getElementById('modalTitle').textContent = 'Tambah Gejala';
        document.getElementById('submitBtn').name = 'add';
        document.getElementById('symptomModal').classList.remove('hidden');
        // Clear form
        document.getElementById('symptomId').value = '';
        document.getElementById('symptomCode').value = '';
        document.getElementById('symptomName').value = '';
        document.getElementById('symptomDescription').value = '';
        document.getElementById('symptomCategory').value = 'Appearance';
        document.getElementById('symptomSeverity').value = '1';
    }

    function showEditModal(symptom) {
        document.getElementById('modalTitle').textContent = 'Edit Gejala';
        document.getElementById('submitBtn').name = 'edit';
        document.getElementById('symptomModal').classList.remove('hidden');
        // Fill form
        document.getElementById('symptomId').value = symptom.id;
        document.getElementById('symptomCode').value = symptom.code;
        document.getElementById('symptomName').value = symptom.name;
        document.getElementById('symptomDescription').value = symptom.description;
        document.getElementById('symptomCategory').value = symptom.category;
        document.getElementById('symptomSeverity').value = symptom.severity;
    }

    function closeModal() {
        document.getElementById('symptomModal').classList.add('hidden');
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
