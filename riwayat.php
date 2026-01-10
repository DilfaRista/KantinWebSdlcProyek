<?php
session_start();
include 'koneksi.php';

// Atur Timezone
date_default_timezone_set('Asia/Jakarta');

// 1. CEK KEAMANAN (Pastikan yang login adalah user/customer)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Atau index.php
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. AMBIL DATA PESANAN USER INI SAJA
// Filter berdasarkan WHERE user_id = ?
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$my_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya - Kantinku</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Menggunakan style dasar yang mirip agar konsisten */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { margin: 0; color: #333; }
        .btn-back { text-decoration: none; color: #555; background: #ddd; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 0.9rem; }

        /* Card Pesanan */
        .order-card { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #eee; overflow: hidden; }
        
        /* Header Card (Status & ID) */
        .card-header { background: #fcfcfc; padding: 15px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .order-date { font-size: 0.8rem; color: #888; display: block; margin-top: 3px; }
        
        /* Body Card (List Barang) */
        .card-body { padding: 15px; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #f5f5f5; font-size: 0.95rem; color: #444; }
        .item-list li:last-child { border-bottom: none; }
        
        /* Footer Card (Total) */
        .card-footer { background: #fafafa; padding: 12px 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 0.9rem; color: #666; }
        .total-value { font-size: 1.1rem; font-weight: bold; color: #333; }

        /* Status Badge */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-unpaid { background: #ffebee; color: #d32f2f; border: 1px solid #ffcdd2; }
        .badge-paid { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

        /* Pesan Kosong */
        .empty-state { text-align: center; padding: 50px 20px; color: #999; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="page-header">
        <div>
            <h2>Riwayat Pesanan</h2>
            <small style="color:#666">Halo, <?= $_SESSION['username'] ?? 'Pelanggan' ?></small>
        </div>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Menu</a>
    </div>

    <?php if (count($my_orders) == 0): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-basket"></i>
            <p>Kamu belum pernah memesan apapun.</p>
            <a href="index.php" style="color: #007bff; text-decoration: none;">Yuk pesan sekarang!</a>
        </div>
    <?php endif; ?>

    <?php foreach ($my_orders as $order): ?>
        <?php
            // Normalisasi status
            $status = strtolower($order['payment_status']);
            $total = $order['total_amount']; // Sesuai database kamu
        ?>
        
        <div class="order-card">
            <div class="card-header">
                <div>
                    <strong>Order #<?= $order['id'] ?></strong>
                    <span class="order-date"><i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <div>
                    <?php if($status == 'paid'): ?>
                        <span class="badge badge-paid"><i class="fas fa-check"></i> Lunas</span>
                    <?php else: ?>
                        <span class="badge badge-unpaid"><i class="fas fa-hourglass-half"></i> Belum Bayar</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <ul class="item-list">
                    <?php
                    // Ambil detail barang untuk order ini
                    $stmt_item = $pdo->prepare("SELECT order_items.*, products.name 
                                                FROM order_items 
                                                JOIN products ON order_items.product_id = products.id 
                                                WHERE order_id = ?");
                    $stmt_item->execute([$order['id']]);
                    $items = $stmt_item->fetchAll();

                    foreach ($items as $item):
                        $subtotal = $item['price_snapshot'] * $item['quantity'];
                    ?>
                        <li>
                            <span><?= htmlspecialchars($item['name']) ?> <small style="color:#888;">x<?= $item['quantity'] ?></small></span>
                            <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card-footer">
                <div>
                    <?php if($status == 'unpaid'): ?>
                        <small style="color: #d32f2f; font-style: italic;">*Silakan bayar di kasir</small>
                    <?php else: ?>
                        <small style="color: #2e7d32;">
                            <i class="fas fa-check-circle"></i> Dibayar: <?= $order['paid_at'] ? date('H:i', strtotime($order['paid_at'])) : '' ?>
                        </small>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="total-label">Total:</span>
                    <span class="total-value">Rp <?= number_format($total, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

</body>
</html>