<?php
session_start();
include 'koneksi.php';

// Cek Login & Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Inisialisasi Variabel Form
$id = '';
$name = '';
$is_edit = false;
$msg = '';

// --- 1. LOGIKA DELETE ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $del_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$del_id]);
        $msg = "Kategori berhasil dihapus.";
    } catch (PDOException $e) {
        // Error biasanya karena ada produk yang masih pake kategori ini (Foreign Key)
        $msg = "Gagal hapus: Kategori ini sedang dipakai oleh produk.";
    }
}

// --- 2. LOGIKA EDIT (AMBIL DATA KE FORM) ---
if (isset($_GET['id']) && !isset($_GET['action'])) {
    $is_edit = true;
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    if ($data) {
        $name = $data['name'];
    }
}

// --- 3. LOGIKA SIMPAN (INSERT / UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_input = $_POST['name'];
    
    if ($is_edit) {
        // Update
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name_input, $id]);
        // Reset URL bersih
        header("Location: kategori.php"); 
        exit;
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name_input]);
        $msg = "Kategori berhasil ditambahkan.";
    }
}

// --- 4. AMBIL SEMUA DATA UNTUK TABEL ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori - Kantinku</title>
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
                <li><a href="kategori.php" class="active"><i class="fas fa-list"></i> Kategori</a></li>
                <li><a href="admin_pesanan.php"><i class="fas fa-list"></i> Pesanan Masuk</a></li>
                <li><a href="pelanggan.php"><i class="fas fa-users"></i> Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        
        <div class="header-admin">
            <h2>Kelola Kategori</h2>
        </div>

        <?php if($msg): ?>
            <div style="background: #e2e3e5; color: #333; padding: 10px; margin-bottom: 20px; border-radius: 5px; border-left: 5px solid #333;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 300px;">
                <div class="admin-form-card" style="margin: 0;">
                    <h3 style="margin-bottom: 15px;"><?= $is_edit ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
                    
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Nama Kategori</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Contoh: Makanan, Snack..." required>
                        </div>

                        <button type="submit" class="btn-submit">
                            <?= $is_edit ? 'Update Kategori' : 'Simpan Kategori' ?>
                        </button>

                        <?php if($is_edit): ?>
                            <a href="kategori.php" class="btn-action" style="background:#6c757d; display:block; text-align:center; margin-top:10px;">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div style="flex: 2; min-width: 300px;">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Kategori</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach($categories as $row): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><b><?= htmlspecialchars($row['name']) ?></b></td>
                                <td>
                                    <a href="kategori.php?id=<?= $row['id'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                    
                                    <a href="kategori.php?action=delete&id=<?= $row['id'] ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Hapus kategori ini? Pastikan tidak ada produk di dalamnya.')">
                                       <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>
</div>

</body>
</html>