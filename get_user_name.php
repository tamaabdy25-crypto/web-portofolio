<?php
include 'koneksi.php';

if (isset($_GET['no_induk'])) {
    $no_induk = mysqli_real_escape_string($conn, $_GET['no_induk']);
    
    // Ganti 'users' dengan nama tabel kamu, dan 'nomor_induk'/'nama_lengkap' sesuai kolommu
    $sql = "SELECT nama_lengkap FROM users WHERE nomor_induk = '$no_induk' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo $row['nama_lengkap'];
    } else {
        echo "Data tidak ditemukan";
    }
}
?>