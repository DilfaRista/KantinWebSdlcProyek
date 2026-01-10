<?php
session_start();
include 'koneksi.php';

// 1. Cek Keamanan (Hanya Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. LOGIKA HAPUS PELANGGAN
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Hapus user berdasarkan ID (dan pastikan dia customer, jangan hapus sesama admin)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$id]);
    
    header("Location: pelanggan.php?status=deleted");
    exit;
}

// 3. LOGIKA PENCARIAN & AMBIL DATA
$search = isset($_GET['q']) ? $_GET['q'] : '';
$customers = [];

if ($search) {
    // Jika ada pencarian
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'customer' AND username LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%"]);
    $customers = $stmt->fetchAll();
} else {
    // Jika tidak ada pencarian (Tampilkan semua)
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC");
    $customers = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan - Kantinku</title>
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
                <li><a href="product_manage.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="kategori.php"><i class="fas fa-list"></i> Kategori</a></li>
                <li><a href="admin_pesanan.php"><i class="fas fa-list"></i> Pesanan Masuk</a></li>
                <li><a href="pelanggan.php" class="active"><i class="fas fa-users"></i> Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        
        <div class="header-admin">
            <h2>Data Pelanggan</h2>
            
            <form action="" method="GET" style="display:flex; gap:10px;">
                <input type="text" name="q" placeholder="Cari username..." value="<?= htmlspecialchars($search) ?>" 
                       style="padding:8px; border:1px solid #ddd; border-radius:5px;">
                <button type="submit" class="btn-add-new" style="border:none; cursor:pointer;">Cari</button>
                <?php if($search): ?>
                    <a href="pelanggan.php" class="btn-action" style="background:#666; line-height:25px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div style="background:#ffcccc; color:red; padding:10px; margin-bottom:15px; border-radius:5px;">
                Data pelanggan berhasil dihapus.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($customers) > 0): ?>
                        <?php 
                        $no = 1;
                        foreach($customers as $row): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:30px; height:30px; background:#ddd; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#555;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <b><?= htmlspecialchars($row['username']) ?></b>
                                </div>
                            </td>
                            <td>
                                <span style="background:#e2e6ea; color:#333; padding:3px 8px; border-radius:4px; font-size:0.8rem;">
                                    <?= htmlspecialchars($row['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?= isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-' ?>
                            </td>
                            <td>
                                <a href="pelanggan.php?action=delete&id=<?= $row['id'] ?>" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus pelanggan ini? Data tidak bisa dikembalikan.')">
                                   <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 30px; color:#888;">
                                Tidak ada data pelanggan ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>