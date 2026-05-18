<?php
include 'koneksi.php';

$search = $_GET['q'] ?? '';

// Cari nama lengkap karyawan atau email dari tabel users
$query = mysqli_query($conn, "SELECT id, nama_lengkap, email FROM users 
                              WHERE nama_lengkap LIKE '%$search%' 
                              OR email LIKE '%$search%' 
                              LIMIT 10");

$data = [];
while($row = mysqli_fetch_assoc($query)) {
    $data[] = [
        'id'   => $row['id'], // ID ini yang nanti disimpan ke database
        'text' => $row['nama_lengkap'] . " (" . $row['email'] . ")" // Teks ini yang muncul di layar
    ];
}

// Kirim hasil ke form
echo json_encode($data);
?>