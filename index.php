<?php
session_start();

// Cek Login: Jika tidak ada session user_id, lempar ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// --- FUNGSI RENDER CARD ---
function renderCard($row) {
    // 1. Format Rupiah
    $harga_jual = "Rp " . number_format($row['price'], 0, ',', '.');
    $gambar_html = '';
    // 2. Cek Gambar
    if (!empty($row['image_url']) && file_exists('uploads/' . $row['image_url'])) {
        $gambar_html = '<img src="uploads/' . $row['image_url'] . '" alt="' . htmlspecialchars($row['name']) . '">';
    } else {
        $gambar_html = '<span>Foto</span>'; 
    }

    // 3. Output HTML
    echo '
    <article class="card">
        <div class="card-img" style="' . (!empty($row['image_url']) ? 'background:none;' : '') . '">
            ' . $gambar_html . '
        </div>
        <div class="card-body">
            <h3 class="card-name">' . htmlspecialchars($row['name']) . '</h3>
            <p class="card-desc">' . htmlspecialchars($row['description']) . '</p>
            
            <div class="card-footer">
                <span class="card-price">' . $harga_jual . '</span>
                
                <div class="qty-control">
                    <div class="qty-display">1</div>
                    <button class="btn-qty" type="button">+</button>
                    <button class="btn-qty" type="button">-</button>
                </div>
            </div>
        </div>
    </article>
    ';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantinku Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-img img { width: 100%; height: 100%; object-fit: cover; }
        .user-welcome { font-size: 0.9rem; margin-right: 15px; color: #555; }
        .empty-msg { text-align: center; width: 100%; color: #888; font-style: italic; margin-top: 20px;}
    </style>
</head>
<body>

    <header>
        <div class="brand">KANTINKU</div>
        <nav>
            <ul>
                <li><span class="user-welcome">Hai, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="logout.php" style="color: red;">Logout</a></li>
            </ul>
        </nav>
        <div class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
    </header>

    <main>
        
        <section class="category-section">
            <h2 class="section-title">Rekomendasi Menu</h2>
            <div class="card-grid">
                <?php
                // Query PDO: Ambil 4 produk acak
                $sql = "SELECT * FROM products WHERE is_available = 1 ORDER BY RAND() LIMIT 4";
                $stmt = $pdo->query($sql); // Eksekusi langsung

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        renderCard($row);
                    }
                } else {
                    echo "<p class='empty-msg'>Belum ada menu tersedia.</p>";
                }
                ?>
            </div>
        </section>

        <section class="category-section">
            <h2 class="section-title">Makanan</h2>
            <div class="card-grid">
                <?php
                // Query PDO dengan Prepared Statement (Lebih aman & rapi)
                $sqlMakan = "SELECT p.* FROM products p 
                             JOIN categories c ON p.category_id = c.id 
                             WHERE c.name = :kategori AND p.is_available = 1";
                
                $stmt = $pdo->prepare($sqlMakan);
                $stmt->execute(['kategori' => 'Makanan']);

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        renderCard($row);
                    }
                } else {
                    echo "<p class='empty-msg'>Menu makanan kosong.</p>";
                }
                ?>
            </div>
        </section>

        <section class="category-section">
            <h2 class="section-title">Minuman</h2>
            <div class="card-grid">
                <?php
                // Gunakan query yang sama, ganti parameter bind-nya jadi 'Minuman'
                // Kita bisa menggunakan variabel $sqlMakan lagi atau tulis ulang
                $stmt = $pdo->prepare($sqlMakan); // Re-use query structure
                $stmt->execute(['kategori' => 'Minuman']);

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        renderCard($row);
                    }
                } else {
                    echo "<p class='empty-msg'>Menu minuman kosong.</p>";
                }
                ?>
            </div>
        </section>

        <div class="info-section">
            <div class="kantinku-badge-large">
                KANTINKU
            </div>
            <div class="app-desc">
                Pesan makanan kantin dengan mudah dan cepat.
            </div>
        </div>

    </main>

    <footer>
        Copyright 2025 - Kantin Sekolah
    </footer>

</body>
</html>