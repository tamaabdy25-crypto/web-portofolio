<?php
// Vercel akan otomatis mengisi ini dari Environment Variable yang tadi lu simpan
$db_url = getenv('DATABASE_URL');

// Koneksi ke PostgreSQL
$conn = pg_connect($db_url);

if (!$conn) {
    // Kalau gagal, kasih pesan pendek biar nggak error fatal
    die("Koneksi Database Gagal!");
}

// FUNGSI PENERJEMAH (Wrapper) AGAR KODE LAMA LU TETEP JALAN
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
?>
