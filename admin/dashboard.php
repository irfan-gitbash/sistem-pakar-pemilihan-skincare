<?php
require_once '../config/database.php';
require_once 'auth.php';

// Verify authentication
requireAuth();

// Get statistics
try {
    // Total diagnoses
    $stmt = $db->query("SELECT COUNT(*) as total FROM diagnosis_history");
    $totalDiagnoses = $stmt->fetch()['total'];

    // Diagnoses by skin type
    $stmt = $db->query("
        SELECT st.name, COUNT(dh.id) as count 
        FROM skin_types st 
        LEFT JOIN diagnosis_history dh ON st.code = dh.diagnosed_type 
        GROUP BY st.code, st.name
    ");
    $diagnosesBySkinType = $stmt->fetchAll();

    // Recent diagnoses with user information
    $stmt = $db->query("
        SELECT dh.*, st.name as skin_type_name, GROUP_CONCAT(s.name) as symptom_names
        FROM diagnosis_history dh
        JOIN skin_types st ON dh.diagnosed_type = st.code
        LEFT JOIN symptoms s ON FIND_IN_SET(s.code, dh.selected_symptoms)
        GROUP BY dh.id
        ORDER BY dh.created_at DESC
        LIMIT 15
    ");
    $recentDiagnoses = $stmt->fetchAll();

    // Recent activity logs
    $recentActivities = getRecentActivityLogs($db, 5);

    // Total symptoms and rules
    $stmt = $db->query("SELECT COUNT(*) as total FROM symptoms");
    $totalSymptoms = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM rules");
    $totalRules = $stmt->fetch()['total'];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <span class="text-xl font-semibold text-gray-800">Admin Dashboard</span>
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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Diagnoses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Diagnosa</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($totalDiagnoses); ?></h3>
                    </div>
                    <div class="p-3 bg-blue-500 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Symptoms -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Gejala</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($totalSymptoms); ?></h3>
                    </div>
                    <div class="p-3 bg-green-500 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Rules -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Aturan</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($totalRules); ?></h3>
                    </div>
                    <div class="p-3 bg-yellow-500 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Status Sistem</p>
                        <h3 class="text-2xl font-bold text-green-500">Aktif</h3>
                    </div>
                    <div class="p-3 bg-purple-500 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Activity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Diagnosis Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Distribusi Diagnosis Jenis Kulit</h3>
                <canvas id="skinTypeChart"></canvas>
            </div>

            <!-- Recent Diagnoses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Diagnosis Terbaru</h3>
                <div class="space-y-3">
                    <?php foreach ($recentDiagnoses as $index => $diagnosis): ?>
                    <div class="border-l-4 border-blue-500 pl-4 py-3 hover:bg-gray-50 transition-colors duration-200 cursor-pointer rounded-r-lg" 
                         onclick="window.location.href='diagnosis_view.php?id=<?php echo $diagnosis['id']; ?>'">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-500 text-white text-xs font-bold rounded-full flex-shrink-0">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-800 text-sm sm:text-base"><?php echo htmlspecialchars($diagnosis['skin_type_name']); ?></p>
                                        <?php if (!empty($diagnosis['user_name'])): ?>
                                        <p class="text-xs text-gray-500">Pasien: <?php echo htmlspecialchars($diagnosis['user_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-600 ml-9 mb-2">Gejala: <?php echo htmlspecialchars($diagnosis['symptom_names']); ?></p>
                                <?php if (!empty($diagnosis['phone_number'])): ?>
                                <p class="text-xs text-gray-500 ml-9">Telepon: <?php echo htmlspecialchars($diagnosis['phone_number']); ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-gray-500 ml-9 sm:ml-0 flex-shrink-0">
                                <?php echo date('d M Y H:i', strtotime($diagnosis['created_at'])); ?>
                            </span>
                        </div>
                        <div class="ml-9 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                Confidence: <?php echo number_format($diagnosis['confidence_score'] * 100, 1); ?>%
                            </span>
                            <div class="flex space-x-2" onclick="event.stopPropagation();">
                                <a href="diagnosis_view.php?id=<?php echo $diagnosis['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm p-1 rounded hover:bg-blue-50 transition-colors"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="diagnosis_edit.php?id=<?php echo $diagnosis['id']; ?>" 
                                   class="text-green-600 hover:text-green-800 text-sm p-1 rounded hover:bg-green-50 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="diagnosis_delete.php?id=<?php echo $diagnosis['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this diagnosis?');"
                                   class="text-red-600 hover:text-red-800 text-sm p-1 rounded hover:bg-red-50 transition-colors"
                                   title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </a>
                                <a href="diagnosis_export.php?id=<?php echo $diagnosis['id']; ?>" 
                                   target="_blank"
                                   class="text-purple-600 hover:text-purple-800 text-sm p-1 rounded hover:bg-purple-50 transition-colors"
                                   title="Export">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- View All Button -->
                <div class="mt-4 text-center">
                    <a href="diagnosis_manage.php" 
                       class="inline-flex items-center px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Lihat Semua Diagnosis
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Mobile menu toggle
    document.getElementById('menuBtn').addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Initialize chart
    const ctx = document.getElementById('skinTypeChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { 
                return '"' . $item['name'] . '"'; 
            }, $diagnosesBySkinType)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_map(function($item) { 
                    return $item['count']; 
                }, $diagnosesBySkinType)); ?>],
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#8B5CF6'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>
