<?php
session_start();
include 'koneksi.php';

// Cek Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- LOGIKA HAPUS PRODUK ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. Ambil nama file gambar dulu sebelum data dihapus
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    // 2. Hapus file gambar dari folder 'uploads' jika ada
    if ($prod && !empty($prod['image_url'])) {
        $path = 'uploads/' . $prod['image_url'];
        if (file_exists($path)) {
            unlink($path); // Hapus file fisik
        }
    }

    // 3. Hapus data dari database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: product_manage.php?status=deleted");
    exit;
}

// --- AMBIL SEMUA PRODUK ---
$query = "SELECT p.*, c.name as category_name FROM products p 
          JOIN categories c ON p.category_id = c.id 
          ORDER BY p.id DESC";
$products = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk - Kantinku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    
    <aside class="sidebar">
        <div class="brand">KANTINKU <small>Admin</small></div>
        <nav>
            <ul>
                <li><a href="admin.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="product_manage.php" class="active"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="#"><i class="fas fa-list"></i> Kategori</a></li>
                <li><a href="#"><i class="fas fa-shopping-bag"></i> Pesanan Masuk</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="header-admin">
            <h2>Daftar Menu Kantin</h2>
            <a href="product_form.php" class="btn-add-new"><i class="fas fa-plus"></i> Tambah Menu Baru</a>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div style="background:#ffcccc; color:red; padding:10px; margin-bottom:15px; border-radius:5px;">
                Produk berhasil dihapus.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-container">
              <table class="admin-table">
                  <thead>
                      <tr>
                          <th>No</th>
                          <th>Foto</th>
                          <th>Nama</th>
                          <th>Kategori</th>
                          <th>HPP (Modal)</th>  <th>Harga Jual</th>
                          <th>Profit</th>       <th>Status</th>
                          <th>Aksi</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php 
                      $no = 1;
                      foreach($products as $row): 
                          $img_path = 'uploads/' . $row['image_url'];
                          
                          // Hitung Profit
                          $profit = $row['price'] - $row['hpp'];
                          
                          // Warna Profit (Hijau untung, Merah rugi/0)
                          $profit_color = ($profit > 0) ? 'green' : '#d9534f';
                      ?>
                      <tr>
                          <td><?= $no++ ?></td>
                          <td>
                              <?php if(!empty($row['image_url']) && file_exists($img_path)): ?>
                                  <img src="<?= $img_path ?>" alt="img" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                              <?php else: ?>
                                  <span style="font-size:0.8rem; color:#ccc;">No Pic</span>
                              <?php endif; ?>
                          </td>
                          <td><b><?= htmlspecialchars($row['name']) ?></b></td>
                          <td><?= htmlspecialchars($row['category_name']) ?></td>
                          
                          <td>Rp <?= number_format($row['hpp'], 0, ',', '.') ?></td>
                          <td>Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                          
                          <td style="color: <?= $profit_color ?>; font-weight: bold;">
                              Rp <?= number_format($profit, 0, ',', '.') ?>
                          </td>

                          <td>
                              <?= ($row['is_available']) ? 
                                  '<span style="background:#d4edda; color:#155724; padding:3px 8px; border-radius:4px; font-size:0.8rem;">Tersedia</span>' : 
                                  '<span style="background:#f8d7da; color:#721c24; padding:3px 8px; border-radius:4px; font-size:0.8rem;">Habis</span>' ?>
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
                  </tbody>
              </table>
        </div>
    </main>
</div>
</body>
</html>