<?php
// AMBIL DARI VERCEL (PRODUCTION) ATAU DARI STRING LU (DEVELOPMENT)
$db_url = getenv('DATABASE_URL') ?: "postgresql://postgres:23UmWlFdePBFCEqn@db.psskhwflnkyzzhdtxdku.supabase.co:5432/postgres";

// Koneksi ke PostgreSQL
$conn = pg_connect($db_url);

if (!$conn) {
    die("Gagal konek ke database: " . pg_last_error());
}

// FUNGSI PENERJEMAH (Wrapper)
// Ini biar kodingan PHP lu yang lama (mysqli_) tetep jalan di PostgreSQL
function mysqli_query($conn, $query) {
    return pg_query($conn, $query);
}

function mysqli_fetch_assoc($result) {
    return pg_fetch_assoc($result);
}

function mysqli_num_rows($result) {
    return pg_num_rows($result);
}

function mysqli_real_escape_string($conn, $str) {
    return pg_escape_string($conn, $str);
}

function mysqli_error($conn) {
    return pg_last_error($conn);
}

function mysqli_insert_id($conn) {
    // PostgreSQL butuh cara khusus buat dapet ID terakhir, 
    // tapi kalau lu pake wrapper ini, pastikan query INSERT lu di PHP 
    // sudah diubah jadi '... RETURNING id'.
    return null; 
}
?>
