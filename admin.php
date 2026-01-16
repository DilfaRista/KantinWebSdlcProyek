<?php
session_start();
include 'koneksi.php';

// 1. CEK KEAMANAN: Apakah user login & role-nya ADMIN?
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, alihkan ke index atau login
    header("Location: index.php"); 
    exit;
}

// 2. AMBIL DATA STATISTIK
// Hitung Total Produk
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_produk = $stmt->fetchColumn();

// Hitung Total User
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'");
$total_user = $stmt->fetchColumn();

// Hitung Total Pesanan (Opsional, jika tabel orders sudah ada)
// $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
// $total_order = $stmt->fetchColumn();
$total_order = 0; // Placeholder jika tabel order belum diisi

// 3. AMBIL DAFTAR PRODUK (Limit 5 terbaru untuk dashboard)
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.id DESC LIMIT 5");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kantinku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="admin-container">
    
    <aside class="sidebar">
        <div class="brand">KANTINKU <small>Admin</small></div>
        <nav>
            <ul>
                <li><a href="admin.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="product_manage.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="kategori.php"><i class="fas fa-list"></i> Kategori</a></li>
                <li><a href="admin_pesanan.php"><i class="fas fa-receipt"></i>Pesanan Masuk</a></li>
                <li><a href="admin_laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
                <li><a href="pelanggan.php"><i class="fas fa-users"></i> Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        
        <div class="header-admin">
            <h2>Dashboard Overview</h2>
            <span>Halo, <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $total_produk ?></h3>
                    <p>Total Menu</p>
                </div>
                <div class="stat-icon"><i class="fas fa-utensils"></i></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $total_order ?></h3>
                    <p>Pesanan Baru</p>
                </div>
                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $total_user ?></h3>
                    <p>Pelanggan</p>
                </div>
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 15px; color: #555;">Menu Terbaru Ditambahkan</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($products) > 0): ?>
                        <?php foreach($products as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <b><?= htmlspecialchars($row['name']) ?></b>
                            </td>
                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                            <td>Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                            <td>
                                <?php if($row['is_available']): ?>
                                    <span style="color: green; font-weight: bold;">Tersedia</span>
                                <?php else: ?>
                                    <span style="color: red; font-weight: bold;">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="product_form.php?id=<?= $row['id'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="product_manage.php?action=delete&id=<?= $row['id'] ?>" 
                                class="btn-action btn-delete" 
                                onclick="return confirm('Yakin ingin menghapus menu ini?')">
                                <i class="fas fa-trash"></i>
                              </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">Belum ada data produk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>