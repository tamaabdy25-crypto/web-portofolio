<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

// 1. Ambil input pencarian
$search = pg_escape_string($conn, $_GET['q'] ?? '');

// 2. QUERY PAKAI ILIKE (Case-Insensitive)
// ILIKE bikin pencarian lu jadi pinter, mau ketik 'yudha', 'YUDHA', atau 'YuDha' tetep ketemu
$sql = "SELECT id, nama_lengkap, email FROM users 
        WHERE nama_lengkap ILIKE '%$search%' 
        OR email ILIKE '%$search%' 
        LIMIT 10";

$query = @pg_query($conn, $sql);

if (!$query) {
    // Kalau query gagal, kasih tau lewat JSON biar frontend gak muter-muter
    echo json_encode(["status" => "error", "pesan" => pg_last_error($conn)]);
    exit;
}

$data = [];
while($row = pg_fetch_assoc($query)) {
    $data[] = [
        'id'   => $row['id'], 
        'text' => $row['nama_lengkap'] . " (" . $row['email'] . ")" 
    ];
}

// 3. Kirim hasil
echo json_encode($data);
?>
