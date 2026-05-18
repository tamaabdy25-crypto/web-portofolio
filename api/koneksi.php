<?php
$db_url = getenv('DATABASE_URL');
// Supabase pake PostgreSQL, jadi kita pake fungsi pg_connect
$conn = pg_connect($db_url);

if (!$conn) {
    die("Koneksi gagal bosku: " . pg_last_error());
}
?>
