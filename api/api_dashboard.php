<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

// Cek Keamanan
$my_id = $_SESSION['user_id'] ?? 0;
if (!$my_id) {
    echo json_encode(["status" => "error", "pesan" => "Akses ditolak. Belum login."]);
    exit;
}

// 1. SET ZONE & LOGIKA AUTO-ARCHIVE 
date_default_timezone_set('Asia/Jakarta');
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta'");

// FIX POSTGRESQL: Cara gabung Date + Time cukup pakai tanda (+) tambah.
// FIX SUPABASE: Kita coba update pake angka 1 & 0 dulu. Kalau gagal (karena Supabase pake Boolean), kita pake TRUE & FALSE.

$q_update = "UPDATE meetings SET is_finished = 1 WHERE is_finished = 0 AND (meeting_date + end_time) <= CURRENT_TIMESTAMP";
if (!@pg_query($conn, $q_update)) {
    // Kalau query angka gagal, tembak pakai bahasa Boolean
    pg_query($conn, "UPDATE meetings SET is_finished = TRUE WHERE is_finished = FALSE AND (meeting_date + end_time) <= CURRENT_TIMESTAMP");
}

// 2. AMBIL DATA JADWAL LIVE MILIK USER
$sql = "SELECT * FROM meetings WHERE user_id = '$my_id' AND is_finished = 0 ORDER BY meeting_date ASC, start_time ASC";
$res = @pg_query($conn, $sql);

if (!$res) {
    // Kalau query angka gagal lagi, ambil pakai bahasa Boolean
    $sql = "SELECT * FROM meetings WHERE user_id = '$my_id' AND is_finished = FALSE ORDER BY meeting_date ASC, start_time ASC";
    $res = pg_query($conn, $sql);
}

// Kalau database tetep ngamuk, kita tangkap errornya biar JS gak muter doang
if (!$res) {
    echo json_encode(["status" => "error", "pesan" => "DB Error: " . pg_last_error($conn)]);
    exit;
}

$data_live = [];
$nowWIB = time();
$bulan_indo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

while ($row = pg_fetch_assoc($res)) {
    $startWIB = strtotime($row['meeting_date'] . ' ' . $row['start_time']);
    $endWIB   = strtotime($row['meeting_date'] . ' ' . $row['end_time']);
    
    // Default Status
    $row['status_berjalan'] = 'mendatang';
    $row['persen_jalan'] = 0;
    
    // Cek apakah sedang berlangsung saat ini detik ini
    if ($nowWIB >= $startWIB && $nowWIB <= $endWIB) {
        $row['status_berjalan'] = 'berlangsung';
        $totalDurasi = $endWIB - $startWIB;
        $sudahLewat  = $nowWIB - $startWIB;
        $row['persen_jalan'] = ($totalDurasi > 0) ? round(($sudahLewat / $totalDurasi) * 100) : 0;
    } elseif ($nowWIB > $endWIB) {
        $row['status_berjalan'] = 'lewat'; 
    }

    // Rapikan teks agar Frontend JS tidak perlu pusing mikirin format
    $tgl_db = explode('-', $row['meeting_date']);
    if (count($tgl_db) == 3) {
        $row['tanggal_indo'] = $tgl_db[2] . ' ' . $bulan_indo[$tgl_db[1]] . ' ' . $tgl_db[0];
    } else {
        $row['tanggal_indo'] = $row['meeting_date'];
    }
    
    $row['jam_format'] = substr($row['start_time'], 0, 5) . " - " . substr($row['end_time'], 0, 5);
    
    $data_live[] = $row;
}

// 3. BUNGKUS JADI JSON
echo json_encode([
    "status" => "success",
    "data" => $data_live
]);
?>
