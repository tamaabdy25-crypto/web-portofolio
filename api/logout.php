<?php
session_start();

// 1. Hapus semua data Session
session_unset();
session_destroy();

// 2. HAPUS COOKIE (Biar gak Auto Login lagi)
// Caranya: Set waktunya ke masa lalu (time - 3600)
if (isset($_COOKIE['ingat_nomor_induk'])) {
    setcookie('ingat_nomor_induk', '', time() - 3600, "/");
}
if (isset($_COOKIE['token_aman'])) {
    setcookie('token_aman', '', time() - 3600, "/");
}

// 3. Lempar balik ke halaman login
header("Location: login.php");
exit;
?>