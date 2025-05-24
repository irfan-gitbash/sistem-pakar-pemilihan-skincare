-- Create database
CREATE DATABASE IF NOT EXISTS skincare_expert;
USE skincare_expert;

-- Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: admin123

-- Create skin_types table
CREATE TABLE IF NOT EXISTS skin_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    recommendations_cleansing TEXT,
    recommendations_moisturizer TEXT,
    recommendations_sunscreen TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create symptoms table
CREATE TABLE IF NOT EXISTS symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('Appearance', 'Texture', 'Condition', 'Behavior') NOT NULL,
    severity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create rules table
CREATE TABLE IF NOT EXISTS rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skin_type_code VARCHAR(10) NOT NULL,
    symptom_codes TEXT NOT NULL,
    confidence_score DECIMAL(5,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (skin_type_code) REFERENCES skin_types(code) ON DELETE CASCADE
);

-- Create diagnosis_history table
CREATE TABLE IF NOT EXISTS diagnosis_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    selected_symptoms TEXT NOT NULL,
    diagnosed_type VARCHAR(10) NOT NULL,
    confidence_score DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (diagnosed_type) REFERENCES skin_types(code)
);

-- Insert skin types data
INSERT INTO skin_types (code, name, description, recommendations_cleansing, recommendations_moisturizer, recommendations_sunscreen) VALUES
('P001', 'Kulit Normal', 'Kulit normal memiliki keseimbangan yang baik antara kelembapan dan produksi minyak.', 
'Pilih sabun muka yang mengandung:\na. Gliserin: Membantu mempertahankan kelembapan kulit tanpa menjadikannya berminyak atau kering\nb. Vitamin E & minyak jojoba: Melembapkan kulit dan menjaga keseimbangan minyak alami\nc. Niasinamida (Vitamin B3) & panthenol (pro-vitamin B5): Memperkuat pelindung kulit dan meningkatkan ketahanan kulit\n\nHindari sabun muka dengan kandungan deterjen keras atau alkohol tinggi yang bisa mengganggu keseimbangan kulit.',
'Untuk kulit normal, pelembab sebaiknya memiliki tekstur gel atau krim ringan, dengan kandungan:\na. Asam hialuronat: Mengunci kelembapan dan menjaga elastisitas kulit\nb. Gliserin: Menjaga hidrasi dan memperbaiki pelindung kulit\nc. Seramida: Melindungi dan memperkuat lapisan pelindung kulit\nd. Niasinamida: Membantu menjaga keseimbangan kulit dan mencerahkan\ne. Vitamin E & pro-vitamin B5: Sebagai antioksidan dan perawatan tambahan untuk kulit',
'Pilih Tabir Surya dengan kandungan:\na. SPF minimal 15: lebih tinggi lebih baik, idealnya SPF 30 atau 50\nb. Tekstur gel, lotion, atau krim ringan: agar nyaman digunakan sehari-hari pada kulit normal\nc. Kandungan Pelembab seperti: Aloe Vera, Vitamin E, dan pro-vitamin B5\nd. Niasinamida & hyaluronic acid: Membantu mencegah iritasi dan menjaga hidrasi kulit\ne. Seng oksida atau titanium dioxide: untuk perlindungan fisik dari sinar UV'),
('P002', 'Kulit Kering', 'Kulit kering ditandai dengan kurangnya kelembapan dan minyak alami pada kulit.',
'Pilih sabun muka yang mengandung:\na. Asam hialuronat: Humektan yang menarik dan mengunci kelembapan\nb. Gliserin: Membantu mempertahankan kelembapan kulit\nc. Seramida: Memperkuat struktur pelindung kulit\nd. Niasinamida: Meningkatkan lipid alami kulit\ne. Lidah buaya: Melembapkan dan menenangkan kulit\nf. Vitamin E dan minyak jojoba: Memberikan kelembapan\n\nHindari sabun dengan detergen keras (SLS), alkohol, dan AHA konsentrasi tinggi',
'Pilih pelembab dengan kandungan:\na. Asam hialuronat & gliserin: Mengunci kelembapan\nb. Seramida: Memperkuat pelindung kulit\nc. Urea: Mengurangi kehilangan air\nd. Shea butter, minyak jojoba, minyak kelapa: Menutrisi kulit\ne. Niacinamide dan vitamin E: Memperbaiki struktur kulit',
'Pilih tabir surya dengan tekstur lotion atau krim yang melembapkan dengan kandungan:\na. Aloe vera, hyaluronic acid, & vitamin E\nb. SPF minimal 30 dengan perlindungan spektrum luas (UVA/UVB)\nc. Hindari kandungan alkohol'),
('P003', 'Kulit Berminyak', 'Kulit berminyak ditandai dengan produksi sebum berlebih yang dapat menyebabkan wajah tampak mengkilap.',
'Pilih sabun muka yang mengandung:\na. Asam Salisilat: Mengurangi minyak berlebih\nb. Asam Glikolat (AHA): Mengeksfoliasi kulit\nc. Niasinamida: Mengontrol produksi sebum\nd. Tanah Liat Putih: Menyerap minyak berlebih\ne. Ekstrak Teh Hijau: Antioksidan dan anti-inflamasi\nf. Asam Hialuronat: Melembapkan tanpa berminyak\n\nHindari sabun dengan kandungan minyak berat',
'Pilih pelembab dengan kandungan:\na. Niasinamida: Mengontrol minyak\nb. Asam Hialuronat: Hidrasi tanpa rasa berat\nc. Berbahan dasar gel\nd. Non-komedogenik',
'Pilih tabir surya dengan kandungan:\na. SPF minimal 30 (UVA/UVB)\nb. Tekstur gel atau lotion ringan\nc. Niacinamide dan hyaluronic acid\nd. Non-komedogenik dan oil-free\ne. Hindari tabir surya berminyak'),
('P004', 'Kulit Sensitif', 'Kulit sensitif mudah bereaksi terhadap produk skincare dan faktor lingkungan.',
'Pilih sabun muka yang mengandung:\na. Asam hialuronat: Menjaga kelembapan\nb. Seramida: Memperkuat skin barrier\nc. Niasinamida: Anti-inflamasi\nd. Produk hipoalergenik\ne. pH seimbang (4.5-6.0)\n\nHindari: SLS, pewangi, alkohol, benzoil peroksida',
'Pilih pelembab dengan kandungan:\na. Ceramide dan niacinamide\nb. Asam hialuronat dan gliserin\nc. Formula ringan\nd. Hipoalergenik dan non-komedogenik',
'Pilih tabir surya dengan formula ringan:\na. Mengandung aloe vera, hyaluronic acid, vitamin E\nb. SPF minimal 30 (UVA/UVB)\nc. Mineral dengan zinc oxide\nd. Non-komedogenik'),
('P005', 'Kulit Kombinasi', 'Kulit kombinasi memiliki area berminyak (T-zone) dan area kering pada wajah.',
'Pilih sabun muka dengan kandungan:\na. Asam hialuronat: Melembapkan area kering\nb. Niasinamida: Mengontrol minyak\nc. Asam salisilat: Mengangkat minyak berlebih\nd. Lidah buaya: Menenangkan kulit\ne. Gliserin: Menjaga kelembapan\nf. Minyak pohon teh: Anti-inflamasi\ng. Seramida: Memperkuat pelindung kulit',
'Pilih pelembab dengan kandungan:\na. Asam hialuronat & gliserin\nb. Niasinamida\nc. Seramida\nd. Tekstur ringan dan cepat meresap',
'Pilih tabir surya dengan kandungan:\na. SPF minimal 30 (UVA/UVB)\nb. Formula ringan dan non-komedogenik\nc. Mengandung hyaluronic acid\nd. Tekstur gel atau lotion');

-- Insert symptoms data
INSERT INTO symptoms (code, name, category, severity) VALUES
('G01', 'Tidak berminyak', 'Appearance', 1),
('G02', 'Sebagian kulit terlihat berminyak di area hidung, pipi, dagu', 'Appearance', 2),
('G03', 'Segar dan halus', 'Texture', 1),
('G04', 'Kulit terlihat sehat', 'Appearance', 1),
('G05', 'Kulit mudah terlihat kemerahan', 'Condition', 2),
('G06', 'Kulit terlihat mengkilat', 'Appearance', 2),
('G07', 'Pori-pori halus', 'Texture', 1),
('G08', 'Pori-pori kasar terutama di area hidung, pipi, dagu', 'Texture', 2),
('G09', 'Tidak berjerawat', 'Condition', 1),
('G10', 'Sering ditumbuhi jerawat', 'Condition', 3),
('G11', 'Kadang berjerawat', 'Condition', 2),
('G12', 'Mudah dalam memilih kosmetik', 'Behavior', 1),
('G13', 'Sulit dalam memilih kosmetik', 'Behavior', 2),
('G14', 'Kulit terlihat kering sekali', 'Appearance', 3),
('G15', 'Bahan-bahan kosmetik mudah menempel di kulit', 'Behavior', 2);

-- Insert rules data
INSERT INTO rules (skin_type_code, symptom_codes, confidence_score) VALUES
('P001', 'G01,G03,G04,G07,G09,G12,G15', 1.00),
('P002', 'G08,G13,G14', 0.85),
('P003', 'G02,G06,G10,G13', 0.90),
('P004', 'G05,G11,G13', 0.80),
('P005', 'G03,G04,G06,G07,G10,G13,G15', 0.95);

-- Create activity_log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id)
);
