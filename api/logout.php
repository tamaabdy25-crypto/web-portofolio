<?php
session_start();

// 1. Kosongkan semua data session di memori server
$_SESSION = [];

// 2. Hancurkan cookie session bawaan browser (PHPSESSID)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session secara permanen
session_destroy();

// 4. HANCURKAN COOKIE "REMEMBER ME" / LOGIN (INI BIANG KEROKNYA!)
// Pastikan nama cookie sesuai dengan yang ada di login.php lu
setcookie('ingat_nomor_induk', '', time() - 3600, '/');
setcookie('token_aman', '', time() - 3600, '/');

// 5. Obat Anti-Cache biar browser gak nyimpen tampilan lama pas di-back
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 6. Tendang balik ke halaman login
header("Location: login.php");
exit;
?>
