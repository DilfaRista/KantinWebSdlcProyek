<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- TAMBAH KE KERANJANG ---
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity']; // Pastikan jadi angka integer

    // Validasi input minimal 1
    if ($quantity < 1) { $quantity = 1; }

    // Ambil data produk dari DB
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && $product['is_available']) {
        // Siapkan array item
        $item = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'qty' => $quantity,
            'image' => $product['image_url']
        ];

        // Buat session cart jika belum ada
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Cek apakah barang sudah ada di keranjang?
        $found = false;
        foreach ($_SESSION['cart'] as $key => $cartItem) {
            if ($cartItem['id'] == $product_id) {
                // Kalau sudah ada, tambahkan jumlahnya (Qty lama + Qty baru)
                $_SESSION['cart'][$key]['qty'] += $quantity;
                $found = true;
                break;
            }
        }

        // Jika barang baru, masukkan ke array
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
    }
    
    // Kembali ke index
    header("Location: index.php");
    exit;
}

// --- HAPUS ITEM DARI KERANJANG ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['index'])) {
    $index = $_GET['index'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Rapikan urutan array
    }
    header("Location: keranjang.php");
    exit;
}

// Jika dibuka langsung tanpa aksi, kembalikan ke index
header("Location: index.php");
exit;
?>