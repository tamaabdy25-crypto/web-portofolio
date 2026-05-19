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
?>
