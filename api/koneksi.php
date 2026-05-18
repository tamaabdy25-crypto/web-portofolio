<?php
// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "sql113.infinityfree.com"; 
$user = "if0_41783112";
$pass = "CsJT9eyWL5"; 
$db   = "if0_41783112_evision";

// Membuat koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// --- KUNCI UTAMA SINKRONISASI WAKTU ---
// Baris ini memastikan database menggunakan zona waktu Jakarta (WIB)
// Ini adalah solusi agar jam tidak perlu dikurangi -26 detik secara manual
mysqli_query($conn, "SET time_zone = '+07:00'");

// Variabel bantu untuk mengecek status login di file lain
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $_SESSION['user_id'] ?? 0;

// Mengatur timezone di level PHP juga
date_default_timezone_set('Asia/Jakarta');
?>
