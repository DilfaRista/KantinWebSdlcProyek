<?php
session_start();

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';
$total_qty = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_qty += $item['qty']; // Jumlahkan quantity setiap item
    }
}
// Hitung jumlah item di keranjang untuk badge notifikasi
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// --- FUNGSI RENDER CARD (UPDATE: Pake Form) ---
function renderCard($row) {
    // 1. Format Rupiah
    $harga_jual = "Rp " . number_format($row['price'], 0, ',', '.');
    
    // 2. Cek Gambar
    $gambar_html = '';
    if (!empty($row['image_url']) && file_exists('uploads/' . $row['image_url'])) {
        $gambar_html = '<img src="uploads/' . $row['image_url'] . '" alt="' . htmlspecialchars($row['name']) . '">';
    } else {
        $gambar_html = '<span>Foto</span>'; 
    }

    // 3. Output HTML dengan FORM
    echo '
    <article class="card">
        <div class="card-img" style="' . (!empty($row['image_url']) ? 'background:none;' : '') . '">
            ' . $gambar_html . '
        </div>
        <div class="card-body">
            <h3 class="card-name">' . htmlspecialchars($row['name']) . '</h3>
            <p class="card-desc">' . htmlspecialchars($row['description']) . '</p>
            
            <form action="cart.php" method="POST">
                <input type="hidden" name="product_id" value="' . $row['id'] . '">
                
                <div class="card-footer">
                    <span class="card-price">' . $harga_jual . '</span>
                    
                    <div class="qty-control">
                        <input type="number" name="quantity" value="1" min="1" max="10" class="input-qty">
                        
                        <button type="submit" name="add_to_cart" class="btn-qty-add">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </form>
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

        /* CSS Tambahan untuk Input Quantity & Tombol */
        .qty-control { display: flex; align-items: center; gap: 5px; }
        
        .input-qty {
            width: 40px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-qty-add {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-qty-add:hover { background-color: #218838; }

        /* Badge Keranjang */
        .cart-wrapper { position: relative; display: inline-block; }
        .cart-icon a { color: #333; font-size: 1.2rem; }
        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <header>
        <div class="brand">KANTINKU</div>
        <nav>
            <ul>
                <li><a class="user-welcome">Hai, <?= htmlspecialchars($_SESSION['username']) ?></a></li>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="logout.php" style="color: red;">Logout</a></li>
                <a href="riwayat.php" class="tombol-riwayat">
                <i class="fas fa-history"></i> Riwayat</a>
            </ul>
        </nav>
        
        <div class="cart-wrapper">
            <a href="keranjang.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </a>
            <?php if($total_qty > 0): ?>
                <span class="badge"><?= $total_qty ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main>
        
        <section class="category-section">
            <h2 class="section-title">Rekomendasi Menu</h2>
            <div class="card-grid">
                <?php
                // Query: Ambil 4 produk acak
                $sql = "SELECT * FROM products WHERE is_available = 1 ORDER BY RAND() LIMIT 4";
                $stmt = $pdo->query($sql);

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
                // Query Makanan
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
                // Query Minuman
                $stmt = $pdo->prepare($sqlMakan); 
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