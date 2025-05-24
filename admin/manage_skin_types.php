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
        $recommendations_cleansing = sanitizeInput($_POST['recommendations_cleansing']);
        $recommendations_moisturizer = sanitizeInput($_POST['recommendations_moisturizer']);
        $recommendations_sunscreen = sanitizeInput($_POST['recommendations_sunscreen']);

        try {
            if (isset($_POST['add'])) {
                // Check if code already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM skin_types WHERE code = ?");
                $stmt->execute([$code]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Kode jenis kulit sudah ada!";
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO skin_types (
                            code, name, description, 
                            recommendations_cleansing, 
                            recommendations_moisturizer, 
                            recommendations_sunscreen
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $code, $name, $description,
                        $recommendations_cleansing,
                        $recommendations_moisturizer,
                        $recommendations_sunscreen
                    ]);

                    logActivity($db, 'create', 'skin_types', $db->lastInsertId(), "Added new skin type: $code");
                    $success = "Jenis kulit berhasil ditambahkan!";
                }
            } else {
                $id = $_POST['id'];
                $stmt = $db->prepare("
                    UPDATE skin_types SET 
                        code = ?, 
                        name = ?, 
                        description = ?,
                        recommendations_cleansing = ?,
                        recommendations_moisturizer = ?,
                        recommendations_sunscreen = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $code, $name, $description,
                    $recommendations_cleansing,
                    $recommendations_moisturizer,
                    $recommendations_sunscreen,
                    $id
                ]);

                logActivity($db, 'update', 'skin_types', $id, "Updated skin type: $code");
                $success = "Jenis kulit berhasil diperbarui!";
            }
        } catch (PDOException $e) {
            error_log("Error managing skin types: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        try {
            $stmt = $db->prepare("SELECT code FROM skin_types WHERE id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetchColumn();

            $stmt = $db->prepare("DELETE FROM skin_types WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($db, 'delete', 'skin_types', $id, "Deleted skin type: $code");
            $success = "Jenis kulit berhasil dihapus!";
        } catch (PDOException $e) {
            error_log("Error deleting skin type: " . $e->getMessage());
            $error = "Terjadi kesalahan saat menghapus data.";
        }
    }
}

// Fetch all skin types
$stmt = $db->query("SELECT * FROM skin_types ORDER BY code");
$skin_types = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jenis Kulit - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div>
                     <a href="dashboard.php" class="text-xl font-semibold text-gray-800">Admin Panel</a>
                </div>
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="manage_skin_types.php" class="text-gray-600 hover:text-blue-500">Jenis Kulit</a>
                    <a href="manage_symptoms.php" class="text-gray-600 hover:text-blue-500">Gejala</a>
                    <a href="manage_rules.php" class="text-gray-600 hover:text-blue-500">Aturan</a>
                    <a href="diagnosis_manage.php" class="text-gray-600 hover:text-blue-500">Hasil Diagnosis</a>
                    <div class="relative group">
                        <button class="text-gray-600 hover:text-blue-500">
                            <?php echo htmlspecialchars($_SESSION['admin_username']); ?> ▼
                        </button>
                        <div class="absolute right-0 w-48 py-2 mt-2 bg-white rounded-lg shadow-xl hidden group-hover:block">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
                <!-- Mobile Menu Button -->
                <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" id="menuBtn">
                    <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z"></path>
                    </svg>
                </button>
            </div>
            <!-- Mobile Menu -->
            <div class="hidden md:hidden" id="mobileMenu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="manage_skin_types.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Jenis Kulit</a>
                    <a href="manage_symptoms.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Gejala</a>
                    <a href="manage_rules.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Aturan</a>
                    <a href="diagnosis_manage.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Hasil Diagnosis</a>
                    <hr class="my-2 border-gray-200">
                    <a href="profile.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Profile</a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-red-600 hover:text-red-700 hover:bg-gray-50">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Header Navigation -->

    <!-- Main Content -->
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

        <!-- Rest of your content -->
        <div class="hidden md:block bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($skin_types) === 0): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data jenis kulit.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($skin_types as $type): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($type['code']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo htmlspecialchars(substr($type['description'], 0, 100)) . '...'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete(<?php echo $type['id']; ?>, '<?php echo $type['code']; ?>')" 
                                        class="text-red-600 hover:text-red-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-4">
            <?php if (count($skin_types) === 0): ?>
            <div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">
                Tidak ada data jenis kulit.
            </div>
            <?php else: ?>
            <?php foreach ($skin_types as $type): ?>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="text-sm text-gray-500">Kode: <?php echo htmlspecialchars($type['code']); ?></span>
                        <h3 class="font-medium"><?php echo htmlspecialchars($type['name']); ?></h3>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                class="text-blue-600 hover:text-blue-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="confirmDelete(<?php echo $type['id']; ?>, '<?php echo $type['code']; ?>')" 
                                class="text-red-600 hover:text-red-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-2">
                    <?php echo htmlspecialchars(substr($type['description'], 0, 100)) . '...'; ?>
                </p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="skinTypeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto">
        <div class="relative min-h-screen md:flex md:items-center md:justify-center">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl m-4 md:m-0 p-6">
                <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" id="modalTitle">Tambah Jenis Kulit</h3>
                <button onclick="closeModal()" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="skinTypeId">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode</label>
                        <input type="text" name="code" id="skinTypeCode" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="name" id="skinTypeName" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="description" id="skinTypeDescription" rows="2" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rekomendasi Pembersih</label>
                    <textarea name="recommendations_cleansing" id="skinTypeCleansing" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rekomendasi Pelembab</label>
                    <textarea name="recommendations_moisturizer" id="skinTypeMoisturizer" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rekomendasi Tabir Surya</label>
                    <textarea name="recommendations_sunscreen" id="skinTypeSunscreen" rows="4" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
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
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-auto p-6">
                <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Konfirmasi Hapus</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus jenis kulit <span id="deleteItemCode" class="font-medium"></span>?
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
    // Mobile menu toggle
    document.getElementById('menuBtn').addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    function showAddModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Jenis Kulit';
        document.getElementById('submitBtn').name = 'add';
        document.getElementById('skinTypeModal').classList.remove('hidden');
        // Clear form
        document.getElementById('skinTypeId').value = '';
        document.getElementById('skinTypeCode').value = '';
        document.getElementById('skinTypeName').value = '';
        document.getElementById('skinTypeDescription').value = '';
        document.getElementById('skinTypeCleansing').value = '';
        document.getElementById('skinTypeMoisturizer').value = '';
        document.getElementById('skinTypeSunscreen').value = '';
    }

    function showEditModal(type) {
        document.getElementById('modalTitle').textContent = 'Edit Jenis Kulit';
        document.getElementById('submitBtn').name = 'edit';
        document.getElementById('skinTypeModal').classList.remove('hidden');
        // Fill form
        document.getElementById('skinTypeId').value = type.id;
        document.getElementById('skinTypeCode').value = type.code;
        document.getElementById('skinTypeName').value = type.name;
        document.getElementById('skinTypeDescription').value = type.description;
        document.getElementById('skinTypeCleansing').value = type.recommendations_cleansing;
        document.getElementById('skinTypeMoisturizer').value = type.recommendations_moisturizer;
        document.getElementById('skinTypeSunscreen').value = type.recommendations_sunscreen;
    }

    function closeModal() {
        document.getElementById('skinTypeModal').classList.add('hidden');
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
