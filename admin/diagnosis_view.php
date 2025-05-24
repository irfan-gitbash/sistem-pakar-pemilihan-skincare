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
    // Get diagnosis data with skin type details
    $stmt = $db->prepare("
        SELECT dh.*, st.name as skin_type_name, st.description as skin_type_description,
               st.recommendations_cleansing, st.recommendations_moisturizer, st.recommendations_sunscreen
        FROM diagnosis_history dh
        JOIN skin_types st ON dh.diagnosed_type = st.code
        WHERE dh.id = ?
    ");
    $stmt->execute([$id]);
    $diagnosis = $stmt->fetch();

    if (!$diagnosis) {
        header('Location: diagnosis_manage.php');
        exit();
    }

    // Get selected symptoms details
    $selected_symptoms_details = [];
    if (!empty($diagnosis['selected_symptoms'])) {
        $symptom_codes = explode(',', $diagnosis['selected_symptoms']);
        $placeholders = str_repeat('?,', count($symptom_codes) - 1) . '?';
        $stmt = $db->prepare("SELECT * FROM symptoms WHERE code IN ($placeholders)");
        $stmt->execute($symptom_codes);
        $selected_symptoms_details = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Diagnosis view error: " . $e->getMessage());
    header('Location: diagnosis_manage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Diagnosis - Admin - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
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
    <main class="max-w-4xl mx-auto p-6 bg-white rounded shadow mt-6 mb-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-4">Hasil Diagnosis</h1>
            <p class="text-gray-600">
                Berdasarkan gejala yang Anda pilih, berikut adalah hasil diagnosis dan rekomendasi perawatan kulit untuk Anda
            </p>
        </div>

        <!-- User Information -->
        <?php if (!empty($diagnosis['user_name']) || !empty($diagnosis['phone_number'])): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold text-blue-800 mb-4">Informasi Pasien</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($diagnosis['user_name'])): ?>
                <div>
                    <p class="text-sm text-gray-600">Nama Lengkap:</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($diagnosis['user_name']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($diagnosis['phone_number'])): ?>
                <div>
                    <p class="text-sm text-gray-600">Nomor Telepon:</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($diagnosis['phone_number']); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm text-gray-600">Tanggal Diagnosis:</p>
                    <p class="font-medium text-gray-800"><?php echo date('d M Y, H:i', strtotime($diagnosis['created_at'])); ?> WIB</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">ID Diagnosis:</p>
                    <p class="font-medium text-gray-800">#<?php echo str_pad($diagnosis['id'], 6, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Skin Type Result -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Jenis Kulit Anda:</h2>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-2xl text-green-800 font-bold">
                            <?php echo htmlspecialchars($diagnosis['skin_type_name']); ?>
                        </p>
                        <p class="text-green-600 mt-2">
                            <?php echo htmlspecialchars($diagnosis['skin_type_description']); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Confidence: <?php echo number_format($diagnosis['confidence_score'] * 100, 1); ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Symptoms -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Gejala yang Anda Pilih:</h2>
            <div class="bg-gray-50 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($selected_symptoms_details as $symptom): ?>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700"><?php echo htmlspecialchars($symptom['name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div>
            <h2 class="text-xl font-semibold mb-6">Rekomendasi Perawatan:</h2>
            
            <!-- Cleansing Recommendations -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3 text-blue-700">Rekomendasi Pembersih Wajah:</h3>
                <div class="bg-blue-50 rounded-lg p-4">
                    <?php 
                    $cleansing_recs = explode("\n", $diagnosis['recommendations_cleansing']);
                    foreach ($cleansing_recs as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            echo "<p class='text-gray-700 mb-2'>" . htmlspecialchars($line) . "</p>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Moisturizer Recommendations -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3 text-green-700">Rekomendasi Pelembab:</h3>
                <div class="bg-green-50 rounded-lg p-4">
                    <?php 
                    $moisturizer_recs = explode("\n", $diagnosis['recommendations_moisturizer']);
                    foreach ($moisturizer_recs as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            echo "<p class='text-gray-700 mb-2'>" . htmlspecialchars($line) . "</p>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Sunscreen Recommendations -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3 text-orange-700">Rekomendasi Tabir Surya:</h3>
                <div class="bg-orange-50 rounded-lg p-4">
                    <?php 
                    $sunscreen_recs = explode("\n", $diagnosis['recommendations_sunscreen']);
                    foreach ($sunscreen_recs as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            echo "<p class='text-gray-700 mb-2'>" . htmlspecialchars($line) . "</p>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-yellow-800 mb-3">Catatan Penting:</h3>
            <ul class="text-yellow-700 space-y-2">
                <li>• Hasil diagnosis ini berdasarkan gejala yang Anda pilih dan bersifat sebagai panduan awal</li>
                <li>• Konsultasikan dengan dermatologis untuk diagnosis yang lebih akurat</li>
                <li>• Lakukan patch test sebelum menggunakan produk skincare baru</li>
                <li>• Gunakan produk secara konsisten untuk hasil yang optimal</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mt-8">
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                Kembali
            </a>
            <div class="flex space-x-4">
                <a href="diagnosis_edit.php?id=<?php echo $id; ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                    Edit
                </a>
                <a href="diagnosis_export.php?id=<?php echo $id; ?>" target="_blank" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors">
                    Export
                </a>
                <a href="diagnosis_delete.php?id=<?php echo $id; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus diagnosis ini?');" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                    Hapus
                </a>
            </div>
        </div>
    </main>

    <script>
        // Jika ada script tambahan yang diperlukan
    </script>
</body>
</html>
