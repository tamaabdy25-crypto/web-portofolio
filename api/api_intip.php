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

// --- LOGIKA HALAMAN (PAGINATION) ---
$limit = 10; 
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$offset = ($halaman - 1) * $limit;

// FIX POSTGRESQL: Coba pakai angka 0 dulu, kalau database ngamuk ganti pakai FALSE
$syarat_angka = "FROM meetings WHERE is_finished = 0 AND meeting_date >= '$tgl_mulai' AND meeting_date <= '$tgl_selesai'";
$syarat_bool = "FROM meetings WHERE is_finished = FALSE AND meeting_date >= '$tgl_mulai' AND meeting_date <= '$tgl_selesai'";

// Cek dulu database maunya bahasa apa
$sql_cek = @pg_query($conn, "SELECT 1 $syarat_angka LIMIT 1");
$query_syarat = ($sql_cek !== false) ? $syarat_angka : $syarat_bool;

// 2. HITUNG TOTAL HALAMAN DULU
$sql_total = @pg_query($conn, "SELECT COUNT(*) as total $query_syarat");

if (!$sql_total) {
    echo json_encode(["status" => "error", "pesan" => "DB Error Total: " . pg_last_error($conn)]);
    exit;
}

$row_total = pg_fetch_assoc($sql_total);
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $limit);
if ($total_halaman == 0) $total_halaman = 1; // Minimal 1 halaman

// 3. AMBIL DATA SESUAI HALAMAN
$sql = "SELECT * $query_syarat ORDER BY meeting_date ASC, start_time ASC LIMIT $limit OFFSET $offset";
$res = @pg_query($conn, $sql);

if (!$res) {
    echo json_encode(["status" => "error", "pesan" => "DB Error: " . pg_last_error($conn)]);
    exit;
}

$data_jadwal = [];
$bulan_indo = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agt','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];

// 4. RAPIKAN FORMAT DATA
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

// 5. BUNGKUS JADI JSON LENGKAP DENGAN DATA HALAMAN
echo json_encode([
    "status" => "success",
    "info_mulai" => date('d M', strtotime($tgl_mulai)),
    "info_selesai" => date('d M Y', strtotime($tgl_selesai)),
    "halaman_sekarang" => $halaman,
    "total_halaman" => $total_halaman,
    "data" => $data_jadwal
]);
?>
