<?php
session_start();
include 'koneksi.php';

// 1. CEK KEAMANAN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); 
    exit;
}

// --- LOGIKA UPLOAD QRIS BARU ---
$pesan_qris = "";
if (isset($_POST['upload_qris'])) {
    $target_dir = ""; // Simpan di root folder (kosongkan jika file php ini satu folder dengan gambar)
    $target_file = $target_dir . "qris.jpg"; // NAMA FILE DIPAKSA JADI 'qris.jpg'
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($_FILES["file_qris"]["name"], PATHINFO_EXTENSION));

    // Cek apakah file benar-benar gambar
    $check = getimagesize($_FILES["file_qris"]["tmp_name"]);
    if($check !== false) {
        // Cek ekstensi (opsional, tapi disarankan)
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
             $pesan_qris = "<div class='alert error'>Maaf, hanya file JPG, JPEG, & PNG yang diperbolehkan.</div>";
             $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            // Proses Upload & Timpa File Lama
            if (move_uploaded_file($_FILES["file_qris"]["tmp_name"], $target_file)) {
                $pesan_qris = "<div class='alert success'>Berhasil! Gambar QRIS telah diperbarui.</div>";
            } else {
                $pesan_qris = "<div class='alert error'>Maaf, terjadi kesalahan saat mengupload gambar.</div>";
            }
        }
    } else {
        $pesan_qris = "<div class='alert error'>File bukan gambar.</div>";
    }
}
// -------------------------------

// 2. AMBIL DATA STATISTIK
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_produk = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'");
$total_user = $stmt->fetchColumn();

// Hitung Total Pesanan (Cek apakah tabel orders ada)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_order = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_order = 0; 
}

// 3. AMBIL DAFTAR PRODUK (Limit 5)
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
    <style>
        /* Style Tambahan untuk Box QRIS & Alert */
        .qris-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .qris-preview {
            width: 100px;
            height: 100px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }
        .qris-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .qris-form { flex: 1; }
        .qris-form label { font-weight: bold; display: block; margin-bottom: 5px; color: #555; }
        
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 0.9rem; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-upload {
            background-color: #333; 
            color: white; 
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-upload:hover { background-color: #555; }
    </style>
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
                <li><a href="admin_pesanan.php"><i class="fas fa-receipt"></i> Pesanan Masuk</a></li>
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

        <h3 style="margin-bottom: 10px; color: #555;">Pengaturan Pembayaran</h3>
        <div class="qris-box">
            <div class="qris-preview">
                <img src="qris.jpg?v=<?= time() ?>" alt="No QRIS" onerror="this.src='https://via.placeholder.com/100x100?text=No+IMG'">
            </div>
            <div class="qris-form">
                <?= $pesan_qris ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <label>Upload Gambar QRIS Baru (Format JPG/PNG)</label>
                    <input type="file" name="file_qris" accept="image/*" required>
                    <br>
                    <button type="submit" name="upload_qris" class="btn-upload">
                        <i class="fas fa-upload"></i> Ganti QRIS
                    </button>
                    <small style="display:block; margin-top:5px; color:#888;">*File ini akan disimpan sebagai <b>qris.jpg</b> dan langsung tampil di halaman pembayaran user.</small>
                </form>
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