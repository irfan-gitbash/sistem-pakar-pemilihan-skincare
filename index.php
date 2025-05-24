<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pakar Pemilihan Skincare</title>
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
            <!-- Hero Section -->
            <div class="bg-white rounded-lg shadow-xl p-8 mb-8">
                <h1 class="text-3xl font-bold text-center mb-6">Selamat Datang di Sistem Pakar Pemilihan Skincare</h1>
                <p class="text-gray-600 text-center mb-8">
                    Sistem ini akan membantu Anda menentukan jenis kulit wajah dan memberikan rekomendasi produk skincare yang sesuai dengan kondisi kulit Anda.
                </p>
                <div class="text-center">
                    <a href="diagnosis.php" 
                       class="inline-block bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                        Mulai Diagnosis
                    </a>
                </div>
            </div>

            <!-- Information Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-4 sm:px-0">
                <!-- How it Works -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Cara Kerja</h2>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-2">1.</span>
                            Jawab pertanyaan tentang kondisi kulit Anda
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-2">2.</span>
                            Sistem akan menganalisis jawaban Anda
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-2">3.</span>
                            Dapatkan hasil diagnosis jenis kulit
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-2">4.</span>
                            Terima rekomendasi produk skincare yang sesuai
                        </li>
                    </ul>
                </div>

                <!-- Types of Skin -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Jenis-jenis Kulit</h2>
                    <ul class="space-y-3 text-gray-600">
                        <?php
                        $stmt = mysqli_query($conn, "SELECT name FROM skin_types ORDER BY code");
                        while ($row = mysqli_fetch_assoc($stmt)) {
                            echo '<li class="flex items-center">';
                            echo '<svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>';
                            echo '</svg>';
                            echo htmlspecialchars($row['name']);
                            echo '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('menuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
        const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    dropdownToggle.addEventListener('click', function() {
        dropdownMenu.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(e) {
        if (!dropdownToggle.contains(e.target)) {
            dropdownMenu.classList.add('hidden');
        }
    });
    </script>
</body>
</html>
