<?php
session_start();
include 'config/database.php';

if (!isset($_POST['symptoms']) || empty($_POST['symptoms']) || 
    !isset($_POST['user_name']) || empty($_POST['user_name']) ||
    !isset($_POST['phone_number']) || empty($_POST['phone_number'])) {
    header('Location: diagnosis.php');
    exit();
}

// Get form data
$selected_symptoms = $_POST['symptoms'];
$user_name = trim($_POST['user_name']);
$phone_number = trim($_POST['phone_number']);

// Get all rules
$stmt = $db->query("SELECT * FROM rules");
$rules = $stmt->fetchAll();

// Forward chaining logic
$max_match = 0;
$matched_skin_type = null;

foreach ($rules as $rule) {
    $rule_symptoms = explode(',', $rule['symptom_codes']);
    $matches = count(array_intersect($selected_symptoms, $rule_symptoms));
    
    if ($matches > $max_match) {
        $max_match = $matches;
        $matched_skin_type = $rule['skin_type_code'];
    }
}

// Get skin type details
$diagnosis_id = null;
if ($matched_skin_type) {
    $stmt = $db->prepare("SELECT * FROM skin_types WHERE code = ?");
    $stmt->execute([$matched_skin_type]);
    $skin_type = $stmt->fetch();

    // Save diagnosis history with user information
    $session_id = session_id();
    $selected_symptoms_str = implode(',', $selected_symptoms);
    $confidence_score = $max_match / count(explode(',', $rules[0]['symptom_codes']));
    
    $stmt = $db->prepare("INSERT INTO diagnosis_history (session_id, user_name, phone_number, selected_symptoms, diagnosed_type, confidence_score) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$session_id, $user_name, $phone_number, $selected_symptoms_str, $matched_skin_type, $confidence_score]);
    $diagnosis_id = $db->lastInsertId();
}

// Get selected symptoms details
$selected_symptoms_details = [];
if (!empty($selected_symptoms)) {
    $placeholders = str_repeat('?,', count($selected_symptoms) - 1) . '?';
    $stmt = $db->prepare("SELECT * FROM symptoms WHERE code IN ($placeholders)");
    $stmt->execute($selected_symptoms);
    $selected_symptoms_details = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Diagnosis - Sistem Pakar Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="font-semibold text-xl text-gray-800">Sistem Pakar Skincare</span>
                </a>
                
                <!-- Desktop menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Beranda</a>
                    <a href="diagnosis.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Diagnosis</a>
                    <a href="admin/login.php" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-md text-sm font-medium transition duration-300">Admin Login</a>
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
            <?php if (isset($skin_type)): ?>
                <!-- Result Card -->
                <div class="bg-white rounded-lg shadow-xl p-8 mb-8" id="diagnosisResult">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold mb-4">Hasil Diagnosis</h1>
                        <p class="text-gray-600">
                            Berdasarkan gejala yang Anda pilih, berikut adalah hasil diagnosis dan rekomendasi perawatan kulit untuk Anda
                        </p>
                    </div>

                    <!-- User Information -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h2 class="text-lg font-semibold text-blue-800 mb-4">Informasi Pasien</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Nama Lengkap:</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Nomor Telepon:</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($phone_number); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Tanggal Diagnosis:</p>
                                <p class="font-medium text-gray-800"><?php echo date('d F Y, H:i'); ?> WIB</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ID Diagnosis:</p>
                                <p class="font-medium text-gray-800">#<?php echo str_pad($diagnosis_id, 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Skin Type Result -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">Jenis Kulit Anda:</h2>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-2xl text-green-800 font-bold">
                                        <?php echo htmlspecialchars($skin_type['name']); ?>
                                    </p>
                                    <p class="text-green-600 mt-2">
                                        <?php echo htmlspecialchars($skin_type['description']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        Confidence: <?php echo number_format($confidence_score * 100, 1); ?>%
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
                                $cleansing_recs = explode("\n", $skin_type['recommendations_cleansing']);
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
                                $moisturizer_recs = explode("\n", $skin_type['recommendations_moisturizer']);
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
                                $sunscreen_recs = explode("\n", $skin_type['recommendations_sunscreen']);
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
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="diagnosis.php" 
                           class="w-full sm:w-auto text-center bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors font-medium">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Diagnosis Ulang
                        </a>
                        <a href="index.php" 
                           class="w-full sm:w-auto text-center bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Kembali ke Beranda
                        </a>
                    </div>
                    <button onclick="generatePDF()" 
                            class="w-full sm:w-auto bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-colors font-medium">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Cetak PDF
                    </button>
                </div>

            <?php else: ?>
                <!-- No Result -->
                <div class="bg-white rounded-lg shadow-xl p-8 text-center">
                    <div class="mb-6">
                        <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <h2 class="text-xl font-semibold text-red-500">Tidak Dapat Menentukan Jenis Kulit</h2>
                    </div>
                    <p class="text-gray-600 mb-8">
                        Maaf, sistem tidak dapat menentukan jenis kulit berdasarkan gejala yang dipilih. 
                        Silakan coba lagi dengan memilih gejala yang lebih spesifik.
                    </p>
                    <a href="diagnosis.php" 
                       class="inline-block bg-blue-500 text-white px-8 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                        Coba Lagi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('menuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // PDF Generation Function
        async function generatePDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('diagnosisResult');
            
            // Show loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Membuat PDF...';
            button.disabled = true;
            
            try {
                // Create canvas from HTML element
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                });
                
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Calculate dimensions
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 295; // A4 height in mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;
                
                // Add first page
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                // Add additional pages if needed
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // Generate filename
                const userName = '<?php echo addslashes($user_name ?? "User"); ?>';
                const diagnosisId = '<?php echo $diagnosis_id ?? "000000"; ?>';
                const date = new Date().toISOString().split('T')[0];
                const filename = `Hasil_Diagnosis_${userName.replace(/\s+/g, '_')}_${diagnosisId}_${date}.pdf`;
                
                // Save PDF
                pdf.save(filename);
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi.');
            } finally {
                // Restore button
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
