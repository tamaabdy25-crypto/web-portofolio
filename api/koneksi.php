<?php
// Ambil URL-nya
$db_url = getenv('DATABASE_URL');

// JANGAN LANGSUNG MASUKIN $db_url ke pg_connect kalau dia gagal
// Kita pastiin dulu dia gak kosong
if (empty($db_url)) {
    die("Error: DATABASE_URL tidak ditemukan di Environment Variables Vercel!");
}

// Koneksi ke PostgreSQL
$conn = pg_connect($db_url);

if (!$conn) {
    die("Koneksi Database Gagal!");
}

// ... fungsi wrapper mysqli_ tetap taruh di bawah sini ...
if (!function_exists('mysqli_query')) {
    function mysqli_query($conn, $query) { return pg_query($conn, $query); }
}
if (!function_exists('mysqli_fetch_assoc')) {
    function mysqli_fetch_assoc($result) { return pg_fetch_assoc($result); }
}
if (!function_exists('mysqli_num_rows')) {
    function mysqli_num_rows($result) { return pg_num_rows($result); }
}
if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($conn, $str) { return pg_escape_string($conn, $str); }
}
if (!function_exists('mysqli_error')) {
    function mysqli_error($conn) { return pg_last_error($conn); }
}
    // ==================================================
// GLOBAL VERCEL SESSION & COOKIE RESTORER
// ==================================================
// 1. Pastikan session menyala & aman di folder /tmp Vercel
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', '/tmp');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_secure', 'On'); 
    ini_set('session.cookie_httponly', 'On');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// 2. BACA KTP (COOKIE) UNTUK SEMUA HALAMAN & API
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['ingat_nomor_induk']) && isset($_COOKIE['token_aman'])) {
    $cookie_no_induk = pg_escape_string($conn, $_COOKIE['ingat_nomor_induk']);
    $cookie_token    = $_COOKIE['token_aman'];
    
    // Cek database
    if ($cookie_token === hash('sha256', $cookie_no_induk)) {
        $q_cek_sesi = pg_query($conn, "SELECT * FROM users WHERE nomor_induk = '$cookie_no_induk'");
        if (pg_num_rows($q_cek_sesi) > 0) {
            $data_sesi = pg_fetch_assoc($q_cek_sesi);
            
            // Masukkan kembali ke ingatan (Session)
            $_SESSION['logged_in']    = true;
            $_SESSION['user_id']      = $data_sesi['id'];
            $_SESSION['no_induk']     = $data_sesi['nomor_induk'];
            $_SESSION['nama_lengkap'] = $data_sesi['nama_lengkap'];
            $_SESSION['role']         = $data_sesi['role'];
        }
    }
}
?>
