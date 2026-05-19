<?php
// AMBIL DARI VERCEL (PRODUCTION) ATAU DARI STRING LU (DEVELOPMENT)
$db_url = getenv('DATABASE_URL') ?: "postgresql://postgres:23UmWlFdePBFCEqn@db.psskhwflnkyzzhdtxdku.supabase.co:5432/postgres";

// Koneksi ke PostgreSQL
$conn = pg_connect($db_url);

if (!$conn) {
    die("Gagal konek ke database: " . pg_last_error());
}

// FUNGSI PENERJEMAH (Wrapper) dengan pengecekan function_exists
if (!function_exists('mysqli_query')) {
    function mysqli_query($conn, $query) {
        return pg_query($conn, $query);
    }
}

if (!function_exists('mysqli_fetch_assoc')) {
    function mysqli_fetch_assoc($result) {
        return pg_fetch_assoc($result);
    }
}

if (!function_exists('mysqli_num_rows')) {
    function mysqli_num_rows($result) {
        return pg_num_rows($result);
    }
}

if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($conn, $str) {
        return pg_escape_string($conn, $str);
    }
}

if (!function_exists('mysqli_error')) {
    function mysqli_error($conn) {
        return pg_last_error($conn);
    }
}

if (!function_exists('mysqli_insert_id')) {
    function mysqli_insert_id($conn) {
        return null; // Pastikan pakai 'RETURNING id' di query insert lu
    }
}
?>
