<?php
include 'config/database.php';

// Get all symptoms
$stmt = $db->query("SELECT * FROM symptoms ORDER BY code");
$symptoms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosis Kulit - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="font-semibold text-xl text-gray-800">Sistem Pakar Skincare</span>
                </a>
                <!-- Desktop menu -->
                <div class="hidden md:flex md:items-center md:space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-gray-900 px-3 py-2">Beranda</a>
                    <a href="diagnosis.php" class="text-gray-700 hover:text-gray-900 px-3 py-2">Diagnosis</a>
                    <a href="admin/login.php" class="text-gray-700 hover:text-gray-900 px-3 py-2">Admin Login</a>
                </div>
                <!-- Mobile menu button -->
                <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" id="menuBtn">
                    <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM9 15a1 1 0 011-1h6a1 1 0 110 2h-6a1 1 0 01-1-1z"></path>
                    </svg>
                </button>
            </div>
            <!-- Mobile menu -->
            <div class="hidden md:hidden" id="mobileMenu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="index.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Beranda</a>
                    <a href="diagnosis.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Diagnosis</a>
                    <a href="admin/login.php" class="block px-3 py-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50">Admin Login</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 flex-grow">
        <div class="max-w-4xl mx-auto w-full">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h1 class="text-2xl font-bold mb-6">Diagnosis Jenis Kulit</h1>
                <p class="text-gray-600 mb-8">
                    Pilih gejala-gejala yang sesuai dengan kondisi kulit wajah Anda saat ini.
                </p>

                <form action="result.php" method="POST" id="diagnosisForm">
                    <!-- User Information Section -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-blue-800 mb-4">Informasi Pribadi</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="user_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="user_name" 
                                       name="user_name" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Masukkan nama lengkap Anda">
                            </div>
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nomor Telepon <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       required
                                       pattern="[0-9+\-\s()]+"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Contoh: 08123456789">
                            </div>
                        </div>
                    </div>

                    <!-- Symptoms Selection -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Pilih Gejala Kulit Anda</h2>
                        <div class="space-y-4">
                            <?php foreach ($symptoms as $symptom): ?>
                            <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <input type="checkbox" 
                                       name="symptoms[]" 
                                       value="<?php echo htmlspecialchars($symptom['code']); ?>"
                                       id="<?php echo htmlspecialchars($symptom['code']); ?>"
                                       class="mt-1 w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <label for="<?php echo htmlspecialchars($symptom['code']); ?>" 
                                       class="text-gray-700 cursor-pointer flex-1">
                                    <?php echo htmlspecialchars($symptom['name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <a href="index.php" 
                           class="text-gray-600 hover:text-gray-800 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Kembali
                        </a>
                        <button type="submit" 
                                class="bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                            Dapatkan Hasil Diagnosis
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tips Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <h2 class="text-lg font-semibold text-blue-800 mb-4">Tips Pengisian:</h2>
                <ul class="text-blue-700 space-y-2">
                    <li>• Isi nama dan nomor telepon dengan benar untuk keperluan follow-up</li>
                    <li>• Pilih semua gejala yang sesuai dengan kondisi kulit Anda</li>
                    <li>• Pastikan untuk memperhatikan kondisi kulit dalam keadaan normal (tidak sedang bermasalah)</li>
                    <li>• Jika ragu, Anda bisa mengamati kulit Anda selama beberapa hari</li>
                    <li>• Jawab dengan jujur untuk mendapatkan hasil yang akurat</li>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('menuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Form validation
        document.getElementById('diagnosisForm').addEventListener('submit', function(e) {
            const symptoms = document.querySelectorAll('input[name="symptoms[]"]:checked');
            if (symptoms.length === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal satu gejala untuk melanjutkan diagnosis.');
                return false;
            }
        });

        // Phone number formatting
        document.getElementById('phone_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('62')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                // Keep as is for local format
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
