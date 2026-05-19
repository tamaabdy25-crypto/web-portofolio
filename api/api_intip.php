<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
// Izin akses API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

// Cek keamanan
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "pesan" => "Akses ditolak. Belum login."]);
    exit;
}

// 1. SET TANGGAL (Hari ini sampai 30 hari ke depan)
date_default_timezone_set('Asia/Jakarta');
$tgl_mulai = date('Y-m-d');
$tgl_selesai = date('Y-m-d', strtotime('+30 days'));

// 2. QUERY KE DATABASE (Cari yang belum selesai & masuk dalam rentang 30 hari)
// FIX POSTGRESQL: Coba pakai angka 0 dulu, kalau database ngamuk ganti pakai FALSE
$sql_angka = "SELECT * FROM meetings 
        WHERE is_finished = 0 
        AND meeting_date >= '$tgl_mulai' 
        AND meeting_date <= '$tgl_selesai' 
        ORDER BY meeting_date ASC, start_time ASC";
        
$res = @pg_query($conn, $sql_angka);

if (!$res) {
    // Kalau query angka gagal, tembak pakai bahasa Boolean
    $sql_bool = "SELECT * FROM meetings 
            WHERE is_finished = FALSE 
            AND meeting_date >= '$tgl_mulai' 
            AND meeting_date <= '$tgl_selesai' 
            ORDER BY meeting_date ASC, start_time ASC";
    $res = @pg_query($conn, $sql_bool);
}

// Kalau masih error, tangkap pesan errornya biar frontend tau masalahnya
if (!$res) {
    echo json_encode(["status" => "error", "pesan" => "DB Error: " . pg_last_error($conn)]);
    exit;
}

$data_jadwal = [];

$bulan_indo = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agt','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];

// 3. RAPIKAN FORMAT DATA
while ($row = pg_fetch_assoc($res)) {
    $tgl_parts = explode('-', $row['meeting_date']);
    
    // Pastikan format tanggal aman
    if (count($tgl_parts) == 3) {
        $row['tanggal_format'] = $tgl_parts[2] . ' ' . $bulan_indo[$tgl_parts[1]] . ' ' . $tgl_parts[0];
    } else {
        $row['tanggal_format'] = $row['meeting_date'];
    }
    
    $row['jam_format'] = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5) . ' WIB';
    
    $data_jadwal[] = $row;
}

// 4. BUNGKUS JADI JSON
echo json_encode([
    "status" => "success",
    "info_mulai" => date('d M', strtotime($tgl_mulai)),
    "info_selesai" => date('d M Y', strtotime($tgl_selesai)),
    "data" => $data_jadwal
]);
?>
