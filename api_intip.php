<?php
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
$sql = "SELECT * FROM meetings 
        WHERE is_finished = 0 
        AND meeting_date >= '$tgl_mulai' 
        AND meeting_date <= '$tgl_selesai' 
        ORDER BY meeting_date ASC, start_time ASC";
        
$res = mysqli_query($conn, $sql);
$data_jadwal = [];

$bulan_indo = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agt','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];

// 3. RAPIKAN FORMAT DATA
while ($row = mysqli_fetch_assoc($res)) {
    $tgl_parts = explode('-', $row['meeting_date']);
    $row['tanggal_format'] = $tgl_parts[2] . ' ' . $bulan_indo[$tgl_parts[1]] . ' ' . $tgl_parts[0];
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