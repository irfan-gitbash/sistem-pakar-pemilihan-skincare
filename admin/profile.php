<?php
require_once '../config/database.php';
require_once 'auth.php';

// Verify authentication
requireAuth();

// Get admin details
$admin = getAdminDetails($db, $_SESSION['admin_id']);

// Handle profile update
$success = '';
$error = '';

if (isValidPostRequest()) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $stored_hash = $stmt->fetchColumn();
        
        if (password_verify($current_password, $stored_hash)) {
            if ($new_password === $confirm_password) {
                if (isPasswordStrong($new_password)) {
                    // Update password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['admin_id']]);
                    
                    // Log activity
                    logActivity($db, 'password_change', 'admin', $_SESSION['admin_id'], 'Password updated successfully');
                    
                    $success = 'Password berhasil diperbarui';
                } else {
                    $error = 'Password baru harus memenuhi kriteria keamanan (minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan karakter khusus)';
                }
            } else {
                $error = 'Password baru dan konfirmasi password tidak cocok';
            }
        } else {
            $error = 'Password saat ini tidak valid';
        }
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Admin - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-semibold text-gray-800">Admin Dashboard</a>
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold mb-6">Profile Admin</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <!-- Admin Info -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Admin</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Username</p>
                            <p class="mt-1"><?php echo htmlspecialchars($admin['username']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tanggal Dibuat</p>
                            <p class="mt-1"><?php echo date('d M Y H:i', strtotime($admin['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password Form -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ubah Password</h3>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">
                            Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, dan karakter khusus
                        </p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Mobile menu toggle
    document.getElementById('menuBtn').addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
    </script>
</body>
</html>
