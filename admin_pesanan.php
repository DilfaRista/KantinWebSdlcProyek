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

// =========================================================
// FITUR BARU: AUTO CANCEL ORDER UNPAID > 24 JAM
// =========================================================
// Jalankan query ini setiap admin membuka halaman ini
try {
    // Ubah status jadi 'cancelled' jika masih 'unpaid' dan umurnya > 24 jam (86400 detik)
    $sql_auto_cancel = "UPDATE orders 
                        SET payment_status = 'cancelled' 
                        WHERE payment_status = 'unpaid' 
                        AND created_at < (NOW() - INTERVAL 1 DAY)";
    $pdo->query($sql_auto_cancel);
} catch (Exception $e) {
    // Silent error (biar gak ganggu tampilan kalau gagal update)
}
// =========================================================


// 2. LOGIKA HAPUS PESANAN
if (isset($_POST['delete_order'])) {
    $order_id_to_delete = $_POST['order_id'];

    try {
        $stmt_del_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_del_items->execute([$order_id_to_delete]);

        $stmt_del_order = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt_del_order->execute([$order_id_to_delete]);

        header("Location: admin_pesanan.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        echo "Gagal menghapus: " . $e->getMessage(); exit;
    }
}

// 3. LOGIKA UPDATE STATUS (MANUAL)
if (isset($_POST['toggle_status'])) {
    $order_id = $_POST['order_id'];
    $current_status = $_POST['current_status'];
    $order_created_at = $_POST['created_at'];

    // Cek umur pesanan
    $time_diff = time() - strtotime($order_created_at);
    $is_expired = $time_diff > 86400; // 86400 detik = 24 jam

    // Cegah edit jika sudah expired (baik paid maupun unpaid/cancelled)
    if ($is_expired) {
        header("Location: admin_pesanan.php?error=locked");
        exit;
    }

    if ($current_status == 'paid') {
        $new_status = 'unpaid';
        $paid_at_val = NULL;
    } else {
        $new_status = 'paid';
        $paid_at_val = date('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, paid_at = ? WHERE id = ?");
    $stmt->execute([$new_status, $paid_at_val, $order_id]);
    
    header("Location: admin_pesanan.php");
    exit;
}

// 4. AMBIL DATA PESANAN
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
        /* Style baru untuk Cancelled */
        .status-cancelled { background: #eee; color: #777; border: 1px solid #ccc; text-decoration: line-through; }

        /* CSS Card */
        .order-card { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #eee; overflow: hidden; }
        .order-header { background: #fcfcfc; padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .order-body { padding: 15px; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #f0f0f0; }
        
        .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .btn-toggle { flex: 1; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 0.95rem; }
        
        .btn-mark-paid { background: #28a745; color: white; }
        .btn-mark-paid:hover { background: #218838; }
        
        .btn-mark-unpaid { background: #ffc107; color: #333; }
        .btn-mark-unpaid:hover { background: #e0a800; }
        
        /* Tombol Disabled (Abu-abu) */
        .btn-disabled { background: #e9ecef; color: #6c757d; cursor: not-allowed; border: 1px solid #ced4da; }

        .btn-delete { width: 45px; background: #fff; border: 1px solid #ffcdd2; color: #d32f2f; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-delete:hover { background: #ffebee; border-color: #d32f2f; }
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
            <div class="menu-toggle-admin" onclick="toggleAdminSidebar()"><i class="fas fa-bars"></i></div>
            <h2>Pesanan Masuk</h2>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffcdd2;">
                <i class="fas fa-trash"></i> Pesanan berhasil dihapus permanen.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'locked'): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba;">
                <i class="fas fa-lock"></i> <b>Pesanan Terkunci!</b> Tidak bisa mengubah status pesanan yang sudah lebih dari 24 jam.
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($orders as $order): ?>
                
                <?php
                // Hitung logika lock & status
                $order_time = strtotime($order['created_at']);
                $is_expired = (time() - $order_time) > 86400; // Lebih dari 24 Jam
                $total_bayar = $order['total_amount']; 
                $status = strtolower($order['payment_status']); 
                ?>

                <div class="order-card" style="<?= ($status == 'cancelled') ? 'opacity: 0.6;' : '' ?>">
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
                            <?php elseif($status == 'cancelled'): ?>
                                <span class="status-badge status-cancelled">EXPIRED</span>
                            <?php else: ?>
                                <span class="status-badge status-unpaid">UNPAID</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-body">
                        <ul class="item-list">
                            <?php
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
                        
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="current_status" value="<?= $status ?>">
                            <input type="hidden" name="created_at" value="<?= $order['created_at'] ?>">

                            <div class="action-buttons">
                                <button type="submit" name="delete_order" class="btn-delete" title="Hapus Permanen" onclick="return confirm('Yakin ingin MENGHAPUS pesanan ini? Data akan hilang permanen.')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                <?php if($status == 'cancelled'): ?>
                                    <button type="button" class="btn-toggle btn-disabled" disabled>
                                        <i class="fas fa-ban"></i> Dibatalkan
                                    </button>

                                <?php elseif($status == 'paid'): ?>
                                    
                                    <?php if($is_expired): ?>
                                        <button type="button" class="btn-toggle btn-disabled" disabled>
                                            <i class="fas fa-check-double"></i> Selesai (Locked)
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="toggle_status" class="btn-toggle btn-mark-unpaid" onclick="return confirm('Koreksi status kembali menjadi UNPAID?')">
                                            <i class="fas fa-undo"></i> Koreksi
                                        </button>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <button type="submit" name="toggle_status" class="btn-toggle btn-mark-paid" onclick="return confirm('Terima pembayaran?')">
                                        <i class="fas fa-money-bill-wave"></i> Terima
                                    </button>

                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if($status == 'paid'): ?>
                             <small style="display:block; text-align:center; color:#888; margin-top:5px; font-size:0.75rem;">
                                Dibayar: <?= $order['paid_at'] ? date('H:i', strtotime($order['paid_at'])) : '-' ?>
                            </small>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script>
    function toggleAdminSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }
</script>
</body>
</html>