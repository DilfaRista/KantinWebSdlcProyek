<?php
// Nyalakan laporan error agar ketahuan jika ada masalah
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pastikan session cart aman
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- LOGIKA CHECKOUT ---
if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        try {
            // Pastikan PDO mode error exception nyala
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $pdo->beginTransaction(); 

            // 1. Hitung Total
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total_amount += ($item['price'] * $item['qty']);
            }

            // 2. Simpan ke Tabel ORDERS
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $total_amount]);
            
            $order_id = $pdo->lastInsertId(); 

            // 3. Simpan ke Tabel ORDER_ITEMS
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_snapshot) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $item) {
                $stmt_item->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
            }

            // 4. Reset Keranjang & Commit
            $_SESSION['cart'] = [];
            $pdo->commit();
            
            // --- SOLUSI UTAMA: Ganti header() dengan JavaScript Rsedirect ---
            // Ini jauh lebih kuat menembus error output buffering
            echo "<script>
                alert('Pesanan Berhasil dibuat! ID: $order_id');
                window.location.href = 'pembayaran.php?id=$order_id';
            </script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            // Tampilkan error detail ke layar biar terbaca
            echo "<script>alert('ERROR SISTEM: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Keranjang kosong, pilih menu dulu!'); window.location='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <header>
        <div class="brand">KANTINKU</div>
        <nav>
            <ul>
                <li><a href="index.php">Kembali ke Menu</a></li>
            </ul>
        </nav>
    </header>

    <main style="padding: 20px; max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">
            Keranjang Saya
        </h2>

        <?php if (empty($_SESSION['cart'])): ?>
            
            <div style="text-align: center; padding: 50px; color: #888;">
                <i class="fas fa-shopping-basket" style="font-size: 4rem; margin-bottom: 20px; color: #ccc;"></i>
                <p style="font-size: 1.2rem;">Keranjang kamu masih kosong.</p>
                <a href="index.php" class="btn-action" style="background:#28a745; margin-top:20px; display:inline-block; text-decoration:none; padding: 10px 20px; color: white; border-radius: 5px;">
                    Mulai Pesan
                </a>
            </div>

        <?php else: ?>
            
            <div class="table-container">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; text-align: left;">
                            <th style="padding: 10px;">Menu</th>
                            <th style="padding: 10px;">Harga</th>
                            <th style="padding: 10px;">Qty</th>
                            <th style="padding: 10px;">Subtotal</th>
                            <th style="padding: 10px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($_SESSION['cart'] as $index => $item): 
                            $subtotal = $item['price'] * $item['qty'];
                            $grand_total += $subtotal;
                        ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if(!empty($item['image'])): ?>
                                        <img src="uploads/<?= $item['image'] ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                    <?php else: ?>
                                        <div style="width:50px; height:50px; background:#ddd; border-radius:5px;"></div>
                                    <?php endif; ?>
                                    <b><?= htmlspecialchars($item['name']) ?></b>
                                </div>
                            </td>
                            <td style="padding: 10px;">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                            <td style="padding: 10px; font-weight: bold;"><?= $item['qty'] ?></td>
                            <td style="padding: 10px;">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                            <td style="padding: 10px;">
                                <a href="cart.php?action=delete&index=<?= $index ?>" style="color: red; font-size: 1.2rem;" onclick="return confirm('Hapus menu ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr style="background-color: #f1f1f1; font-weight: bold;">
                            <td colspan="3" style="text-align: right; padding: 15px;">TOTAL BAYAR</td>
                            <td colspan="2" style="color: #28a745; font-size: 1.2rem; padding: 15px;">
                                Rp <?= number_format($grand_total, 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; text-align: right;">
                <form action="keranjang.php" method="POST">
                    <button type="submit" name="checkout" style="background: #28a745; color: white; border: none; font-size: 1.1rem; padding: 15px 30px; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Kirim Pesanan Sekarang
                    </button>
                </form>
            </div>

        <?php endif; ?>
    </main>

</body>
</html>