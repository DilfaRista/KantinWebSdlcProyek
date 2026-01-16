<?php
// Konfigurasi Database
$host     = 'sql206.infinityfree.com';
$db       = 'if0_40920647_kantin_db';
$user     = 'if0_40920647';      // Default user XAMPP
$password = 'soiU9ysCTEoIy5';          // Default password XAMPP (biasanya kosong)

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

// Opsi agar koneksi lebih aman & mudah debug
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Menampilkan error jika query gagal
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Hasil query otomatis jadi Array Asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Mencegah SQL Injection palsu
];

try {
    // Mencoba membuat koneksi
    $pdo = new PDO($dsn, $user, $password, $options);
    
    // Uncomment baris di bawah ini HANYA untuk cek di awal, lalu hapus/komen lagi
    // echo "Koneksi ke database kantin_db BERHASIL!";
    
} catch (\PDOException $e) {
    // Jika gagal, tampilkan pesan error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>