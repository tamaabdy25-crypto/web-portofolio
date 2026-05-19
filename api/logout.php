<?php
session_start();
session_destroy(); // Hancurkan sesi

// Hancurkan KTP (Cookie)
setcookie('ingat_nomor_induk', '', time() - 3600, "/");
setcookie('token_aman', '', time() - 3600, "/");

// Tendang balik ke login
header("Location: login.php");
exit;
?>
