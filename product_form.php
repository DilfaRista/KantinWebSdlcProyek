<?php
session_start();
include 'koneksi.php';

// Cek Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// Inisialisasi Variabel
$id = '';
$name = '';
$description = '';
$price = ''; // Ini Harga Jual
$hpp = '';   // Ini HPP (Modal)
$category_id = '';
$is_available = 1; 
$current_image = '';
$is_edit = false;

// --- MODE EDIT ---
if (isset($_GET['id'])) {
    $is_edit = true;
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if($data) {
        $name = $data['name'];
        $description = $data['description'];
        $price = $data['price'];
        $hpp = $data['hpp']; // Ambil HPP dari DB
        $category_id = $data['category_id'];
        $is_available = $data['is_available'];
        $current_image = $data['image_url'];
    }
}

// --- PROSES SIMPAN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $hpp = $_POST['hpp']; // Ambil input HPP
    $category_id = $_POST['category_id'];
    $is_available = $_POST['is_available'];
    
    $image_url = $current_image; 
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_name = time() . '_' . rand(100, 999) . '.' . $file_ext; 
        
        if(move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name)) {
            $image_url = $new_name;
            if ($is_edit && !empty($current_image) && file_exists($target_dir . $current_image)) {
                unlink($target_dir . $current_image);
            }
        }
    }

    try {
        if ($is_edit) {
            // Update HPP juga
            $sql = "UPDATE products SET name=?, description=?, price=?, hpp=?, category_id=?, is_available=?, image_url=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $hpp, $category_id, $is_available, $image_url, $id]);
        } else {
            // Insert HPP juga
            $sql = "INSERT INTO products (name, description, price, hpp, category_id, is_available, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $hpp, $category_id, $is_available, $image_url]);
        }
        
        header("Location: product_manage.php");
        exit;
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Produk</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Script Sederhana untuk hitung profit otomatis saat ngetik
        function hitungProfit() {
            let hpp = document.getElementById('hpp').value || 0;
            let harga = document.getElementById('price').value || 0;
            let profit = harga - hpp;
            let label = document.getElementById('profit-info');
            
            if(profit >= 0) {
                label.innerHTML = "Estimasi Keuntungan: <span style='color:green; font-weight:bold;'>Rp " + profit.toLocaleString('id-ID') + "</span>";
            } else {
                label.innerHTML = "Estimasi Kerugian: <span style='color:red; font-weight:bold;'>Rp " + profit.toLocaleString('id-ID') + "</span>";
            }
        }
    </script>
</head>
<body style="background-color: #f4f4f4; padding: 20px;">

    <a href="product_manage.php" class="btn-back">&larr; Kembali ke Daftar Produk</a>

    <div class="admin-form-card">
        <h2 style="margin-bottom:20px; text-align:center;"><?= $is_edit ? 'Edit Menu' : 'Tambah Menu Baru' ?></h2>
        
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Nama Menu</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="category_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $category_id) ? 'selected' : '' ?>>
                            <?= $cat['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>HPP (Modal)</label>
                    <input type="number" id="hpp" name="hpp" value="<?= $hpp ?>" required oninput="hitungProfit()" placeholder="0">
                </div>

                <div class="form-group" style="flex: 1;">
                    <label>Harga Jual</label>
                    <input type="number" id="price" name="price" value="<?= $price ?>" required oninput="hitungProfit()" placeholder="0">
                </div>
            </div>
            
            <div id="profit-info" style="margin-bottom: 20px; font-size: 0.9rem; text-align: right;">
                Estimasi Keuntungan: Rp 0
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="form-group">
                <label>Status Ketersediaan</label>
                <select name="is_available">
                    <option value="1" <?= ($is_available == 1) ? 'selected' : '' ?>>Tersedia</option>
                    <option value="0" <?= ($is_available == 0) ? 'selected' : '' ?>>Habis</option>
                </select>
            </div>

            <div class="form-group">
                <label>Foto Menu</label>
                <input type="file" name="image" accept="image/*">
                <?php if($is_edit && !empty($current_image)): ?>
                    <div style="font-size: 0.8rem; color: #666; margin-top:5px;">Foto saat ini:</div>
                    <img src="uploads/<?= $current_image ?>" class="img-preview">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">Simpan Menu</button>
        </form>
    </div>

    <script>hitungProfit();</script>
</body>
</html>