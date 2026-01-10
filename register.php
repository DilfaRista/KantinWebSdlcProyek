<?php
include 'koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);
    
    // Validasi sederhana
    if (empty($username) || empty($password)) {
        $error = "Username dan Password wajib diisi!";
    } else {
        try {
            // 1. Cek apakah username sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username sudah terdaftar, cari yang lain.";
            } else {
                // 2. Hash Password (Keamanan)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // 3. Insert ke Database (Default role: customer)
                $sql = "INSERT INTO users (username, password, role, phone_number, created_at) 
                        VALUES (?, ?, 'customer', ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $hashed_password, $phone]);
                
                // Redirect ke login setelah sukses
                header("Location: login.php?status=registered");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Kantinku</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h2>Daftar Akun</h2>
        
        <?php if($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Masukkan username">
            </div>
            
            <div class="form-group">
                <label>Nomor HP (WhatsApp)</label>
                <input type="text" name="phone" placeholder="08xxxxxxxx">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Buat password">
            </div>
            
            <button type="submit" class="btn-auth">Daftar Sekarang</button>
        </form>
        
        <a href="login.php" class="auth-link">Sudah punya akun? Login disini</a>
    </div>
</body>
</html>