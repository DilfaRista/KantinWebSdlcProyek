<?php
session_start();
include 'koneksi.php';

// Atur Timezone
date_default_timezone_set('Asia/Jakarta');

// 1. CEK KEAMANAN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// 2. INISIALISASI VARIABEL FILTER
$jenis_laporan = $_GET['jenis'] ?? 'harian'; // Default harian
$tgl_input     = $_GET['tgl'] ?? date('Y-m-d');
$bln_input     = $_GET['bln'] ?? date('m');
$thn_input     = $_GET['thn'] ?? date('Y');

// 3. LOGIKA QUERY BERDASARKAN FILTER
$sql = "SELECT orders.*, users.username 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.payment_status = 'paid'"; // HANYA YANG SUDAH BAYAR

$params = [];

if ($jenis_laporan == 'harian') {
    $sql .= " AND DATE(orders.created_at) = ?";
    $params[] = $tgl_input;
    $label_periode = "Tanggal " . date('d F Y', strtotime($tgl_input));

} elseif ($jenis_laporan == 'bulanan') {
    $sql .= " AND MONTH(orders.created_at) = ? AND YEAR(orders.created_at) = ?";
    $params[] = $bln_input;
    $params[] = $thn_input;
    $nama_bulan = date('F', mktime(0, 0, 0, $bln_input, 10));
    $label_periode = "Bulan $nama_bulan $thn_input";

} elseif ($jenis_laporan == 'tahunan') {
    $sql .= " AND YEAR(orders.created_at) = ?";
    $params[] = $thn_input;
    $label_periode = "Tahun $thn_input";
}

$sql .= " ORDER BY orders.created_at DESC";

// Eksekusi Query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// 4. HITUNG TOTAL OMSET
$total_omset = 0;
$total_transaksi = count($orders);

foreach ($orders as $o) {
    $total_omset += $o['total_amount'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Card Summary */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-green { background: #e8f5e9; color: #2e7d32; }
        .bg-blue { background: #e3f2fd; color: #1565c0; }
        .stat-info h3 { margin: 0; font-size: 1.5rem; color: #333; }
        .stat-info p { margin: 0; color: #777; font-size: 0.9rem; }

        /* Filter Bar */
        .filter-bar { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filter-group { display: flex; gap: 10px; align-items: center; }
        select, input[type="date"], input[type="number"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-filter { background: #333; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        
        /* Table Styling */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #444; }
        tr:hover { background: #f1f1f1; }
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
                <li><a href="admin_pesanan.php"><i class="fas fa-receipt"></i> Pesanan Masuk</a></li>
                <li><a href="admin_laporan.php" class="active"><i class="fas fa-chart-line"></i> Laporan</a></li>
                <li><a href="pelanggan.php"><i class="fas fa-users"></i>Data Pelanggan</a></li>
                <br>
                <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="header-admin">
            <h2>Laporan Penjualan</h2>
            <span>Rekap data transaksi status PAID</span>
        </div>

        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Jenis:</label>
                <select name="jenis" onchange="this.form.submit()">
                    <option value="harian" <?= $jenis_laporan == 'harian' ? 'selected' : '' ?>>Harian</option>
                    <option value="bulanan" <?= $jenis_laporan == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                    <option value="tahunan" <?= $jenis_laporan == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                </select>
            </div>

            <?php if ($jenis_laporan == 'harian'): ?>
                <input type="date" name="tgl" value="<?= $tgl_input ?>" required>
            <?php elseif ($jenis_laporan == 'bulanan'): ?>
                <select name="bln">
                    <?php 
                    for ($i=1; $i<=12; $i++) {
                        $selected = ($i == $bln_input) ? 'selected' : '';
                        echo "<option value='$i' $selected>" . date('F', mktime(0,0,0,$i,10)) . "</option>";
                    }
                    ?>
                </select>
                <select name="thn">
                    <?php 
                    for ($i=2024; $i<=date('Y'); $i++) {
                        $selected = ($i == $thn_input) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
            <?php else: ?>
                <select name="thn">
                    <?php 
                    for ($i=2024; $i<=date('Y'); $i++) {
                        $selected = ($i == $thn_input) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
            <?php endif; ?>

            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Tampilkan</button>
            <a href="admin_laporan.php" style="margin-left:auto; color:#666; text-decoration:none;"><i class="fas fa-sync"></i> Reset</a>
        </form>

        <h4>Hasil: <?= $label_periode ?></h4>
        <div class="summary-grid">
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <p>Total Omset</p>
                    <h3>Rp <?= number_format($total_omset, 0, ',', '.') ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-info">
                    <p>Total Transaksi</p>
                    <h3><?= $total_transaksi ?> Pesanan</h3>
                </div>
            </div>
        </div>

        <div style="background: white; border-radius: 8px; border: 1px solid #eee;">
            <table>
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Tanggal Order</th>
                        <th>Waktu Bayar</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $row): ?>
                        <tr>
                            <td><strong>#<?= $row['id'] ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td style="color: green; font-size: 0.9rem;">
                                <?= $row['paid_at'] ? date('d/m/Y H:i', strtotime($row['paid_at'])) : '-' ?>
                            </td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td style="font-weight: bold;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 30px; color: #999;">
                                Tidak ada data penjualan (PAID) pada periode ini.
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