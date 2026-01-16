<?php
session_start();
include 'koneksi.php';

// Atur timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// 1. CEK KEAMANAN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); 
    exit;
}

// 2. LOGIKA UPDATE STATUS
if (isset($_POST['toggle_status'])) {
    $order_id = $_POST['order_id'];
    $current_status = $_POST['current_status'];
    $order_created_at = $_POST['created_at'];

    // Jika status kosong, anggap unpaid
    if (empty($current_status)) $current_status = 'unpaid';

    // A. LOGIKA PENGUNCIAN 24 JAM (Hanya berlaku jika mau mengubah dari PAID ke UNPAID)
    if ($current_status == 'paid') {
        $time_diff = time() - strtotime($order_created_at);
        // 86400 detik = 24 jam
        if ($time_diff > 86400) {
            // Jika sudah lewat 24 jam, tidak boleh diedit lagi
            header("Location: admin_pesanan.php?error=locked");
            exit;
        }
    }

    // B. TENTUKAN STATUS BARU & WAKTU BAYAR
    if ($current_status == 'paid') {
        // Mau diubah jadi UNPAID (Koreksi)
        $new_status = 'unpaid';
        $paid_at_val = NULL; // Kosongkan waktu bayar
    } else {
        // Mau diubah jadi PAID (Bayar)
        $new_status = 'paid';
        $paid_at_val = date('Y-m-d H:i:s'); // Isi waktu sekarang
    }

    // C. UPDATE DATABASE (Update status dan kolom paid_at)
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, paid_at = ? WHERE id = ?");
    $stmt->execute([$new_status, $paid_at_val, $order_id]);
    
    header("Location: admin_pesanan.php");
    exit;
}

// 3. AMBIL DATA PESANAN
// Kita join dengan tabel users untuk mengambil nama pemesan
$query = "SELECT orders.*, users.username 
          FROM orders 
          JOIN users ON orders.user_id = users.id 
          ORDER BY orders.created_at DESC";
$stmt = $pdo->query($query);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - Admin Kantinku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Badge Status */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-unpaid { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .status-paid { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

        /* CSS Card */
        .order-card { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #eee; overflow: hidden; }
        .order-header { background: #fcfcfc; padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .order-body { padding: 15px; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #f0f0f0; }
        
        /* Tombol */
        .btn-toggle { width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 0.95rem; }
        
        .btn-mark-paid { background: #28a745; color: white; } /* HIJAU (Terima Bayar) */
        .btn-mark-paid:hover { background: #218838; }
        
        .btn-mark-unpaid { background: #ffc107; color: #333; } /* KUNING (Koreksi) */
        .btn-mark-unpaid:hover { background: #e0a800; }
        
        .btn-disabled { background: #e9ecef; color: #6c757d; cursor: not-allowed; border: 1px solid #ced4da; }
    </style>
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
                <li><a href="admin_pesanan.php" class="active"><i class="fas fa-receipt"></i> Pesanan Masuk</a></li>
                <li><a href="admin_laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
                <li><a href="pelanggan.php"><i class="fas fa-users"></i> Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="header-admin">
            <h2>Pesanan Masuk</h2>
        </div>

        <?php if (count($orders) == 0): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 8px; color: #777;">
                <p>Belum ada pesanan masuk.</p>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($orders as $order): ?>
                
                <?php
                // 1. CEK WAKTU UNTUK FITUR LOCK
                $order_time = strtotime($order['created_at']);
                $is_locked = (time() - $order_time) > 86400; // Lebih dari 24 jam (86400 detik)

                // 2. PASTIKAN NAMA KOLOM BENAR (Sesuai Tabelmu: total_amount)
                $total_bayar = $order['total_amount']; 
                
                // 3. NORMALISASI STATUS (Biar aman, ubah ke huruf kecil)
                $status = strtolower($order['payment_status']); 
                ?>

                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>#ID: <?= $order['id'] ?></strong>
                            <div style="font-size: 0.85rem; color: #666; margin-top: 4px;">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($order['username']) ?>
                                <br>
                                <small><?= date('d M H:i', $order_time) ?></small>
                            </div>
                        </div>
                        <div>
                            <?php if($status == 'paid'): ?>
                                <span class="status-badge status-paid">PAID</span>
                            <?php else: ?>
                                <span class="status-badge status-unpaid">UNPAID</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-body">
                        <ul class="item-list">
                            <?php
                            // Mengambil detail item
                            $stmt_items = $pdo->prepare("SELECT order_items.*, products.name 
                                                         FROM order_items 
                                                         JOIN products ON order_items.product_id = products.id 
                                                         WHERE order_id = ?");
                            $stmt_items->execute([$order['id']]);
                            $items = $stmt_items->fetchAll();
                            
                            foreach ($items as $item):
                                $subtotal = $item['price_snapshot'] * $item['quantity'];
                            ?>
                                <li>
                                    <span><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
                                    <span><?= number_format($subtotal, 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Total:</span>
                            <strong style="font-size: 1.2rem; color: #333;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></strong>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $status ?>">
                                <input type="hidden" name="created_at" value="<?= $order['created_at'] ?>">
                                
                                <?php 
                                // LOGIKA TOMBOL:
                                // Tombol default (UNPAID) -> Muncul tombol Hijau (Terima Bayar)
                                // Jika PAID -> Muncul tombol Kuning (Koreksi) atau Terkunci
                                ?>

                                <?php if($status == 'paid'): ?>
                                    
                                    <?php if($is_locked): ?>
                                        <button type="button" class="btn-toggle btn-disabled" disabled title="Data dikunci setelah 24 jam">
                                            <i class="fas fa-lock"></i> Selesai (Permanen)
                                        </button>
                                        <small style="display:block; text-align:center; color:#888; margin-top:5px; font-size:0.75rem;">
                                            Dibayar pada: <?= $order['paid_at'] ? date('d M H:i', strtotime($order['paid_at'])) : '-' ?>
                                        </small>
                                    <?php else: ?>
                                        <button type="submit" name="toggle_status" class="btn-toggle btn-mark-unpaid" onclick="return confirm('Koreksi status kembali menjadi UNPAID?')">
                                            <i class="fas fa-undo"></i> Koreksi (Set Unpaid)
                                        </button>
                                        <small style="display:block; text-align:center; color:#888; margin-top:5px; font-size:0.75rem;">
                                            Bisa dikoreksi dlm 24 jam
                                        </small>
                                    <?php endif; ?>

                                <?php else: ?>
                                    
                                    <button type="submit" name="toggle_status" class="btn-toggle btn-mark-paid" onclick="return confirm('Terima pembayaran sejumlah Rp <?= number_format($total_bayar,0,',','.') ?>?')">
                                        <i class="fas fa-money-bill-wave"></i> Terima Pembayaran
                                    </button>

                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>