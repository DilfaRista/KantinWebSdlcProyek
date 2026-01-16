<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek ID Pesanan dari URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil Data Pesanan (Pastikan pesanan ini milik user yang sedang login agar aman)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "Pesanan tidak ditemukan.";
    exit;
}

// --- KONFIGURASI WHATSAPP ADMIN ---
// Ganti dengan nomor WhatsApp kamu (format: 628xxx, tanpa + atau 0 di depan)
$nomor_admin = "6281234567890"; 

// Pesan otomatis
$pesan = "Halo Admin Kantinku, saya mau konfirmasi pembayaran untuk Pesanan ID: #$order_id dengan Total: Rp " . number_format($order['total_amount'],0,',','.');
$pesan_encoded = urlencode($pesan); // Ubah spasi jadi %20 biar bisa masuk URL

// Link WA
$link_wa = "https://wa.me/62895631132041?text=$pesan_encoded";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Kantinku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-card {
            background: white;
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .total-amount {
            font-size: 2rem;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        .qr-box {
            border: 2px dashed #ddd;
            padding: 10px;
            margin: 20px 0;
            display: inline-block;
        }
        .qr-box img {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }
        .btn-wa {
            background-color: #25D366;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-wa:hover {
            background-color: #1ebc57;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37, 211, 102, 0.4);
        }
    </style>
</head>
<body style="background-color: #f4f4f4;">

    <div class="payment-card">
        <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
        <h2>Pesanan Berhasil Dibuat!</h2>
        <p style="color: #666;">Order ID: #<?= $order_id ?></p>
        
        <div style="margin-top: 20px;">
            <small>Total yang harus dibayar:</small>
            <div class="total-amount">
                Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
            </div>
        </div>

        <div class="qr-box">
            <img src="qris.jpg" alt="Scan QRIS">
            <p style="margin-top: 5px; font-size: 0.8rem; color: #555;">Scan QRIS untuk Bayar</p>
        </div>

        <p style="font-size: 0.9rem; color: #555; margin-bottom: 20px;">
            Setelah transfer, silakan kirim bukti pembayaran ke WhatsApp kami agar pesanan diproses.
        </p>

        <a href="<?= $link_wa ?>" target="_blank" class="btn-wa">
            <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i>
            Konfirmasi ke WhatsApp
        </a>
        
        <div style="margin-top: 20px;">
            <a href="index.php" style="color: #666; font-size: 0.9rem; text-decoration: none;">&larr; Kembali ke Menu Utama</a>
        </div>
    </div>

</body>
</html>