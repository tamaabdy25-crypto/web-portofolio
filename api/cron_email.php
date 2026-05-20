<?php
// SETTING WAKTU INDONESIA
date_default_timezone_set('Asia/Jakarta');

// PANGGIL DATABASE
include 'koneksi.php';

// ==========================================
// KODE SAKTI: SINKRONISASI JAM SERVER BULE KE JAM JAKARTA
// ==========================================
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta'");
// ==========================================

// PANGGIL ALAT KURIR (PHPMAILER)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==========================================
// KODE SAKTI: AUTO-DETECT ALAMAT PHPMAILER 
// ==========================================
$jalur_dalam = __DIR__ . '/PHPMailer/src/Exception.php';
$jalur_luar  = __DIR__ . '/../PHPMailer/src/Exception.php';

if (file_exists($jalur_dalam)) {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
} elseif (file_exists($jalur_luar)) {
    require __DIR__ . '/../PHPMailer/src/Exception.php';
    require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/../PHPMailer/src/SMTP.php';
} else {
    die("Error Gawat: Folder PHPMailer ilang Bosku! Pastiin folder 'PHPMailer' udah lu push ke GitHub ya!");
}
// ==========================================

// ==========================================
// ⚠️ BAGIAN WAJIB LU UBAH ⚠️
// ==========================================
$email_robot    = "pengelola.evision@gmail.com"; 
$password_robot = "*cmozgaxoxqrajrii"; // <-- INGET PASSWORD LU JANGAN LUPA DIISI LAGI YA!
$nama_pengirim  = "E-VISION";
// ==========================================

// FUNGSI UTAMA NGIRIM EMAIL
function kirimEmail($ke_email, $ke_nama, $subjek, $pesan_html) {
    global $email_robot, $password_robot, $nama_pengirim;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_robot;
        $mail->Password   = $password_robot;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Pakai keamanan SSL
        $mail->Port       = 465;

        $mail->setFrom($email_robot, $nama_pengirim);
        $mail->addAddress($ke_email, $ke_nama);

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = $pesan_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$log_aktivitas = ""; 

// ==========================================
// MESIN 1: KIRIM EMAIL UNDANGAN BARU
// ==========================================
$q_undangan = pg_query($conn, "SELECT ap.id as id_ap, u.email, u.nama_lengkap, m.title, m.room_name, m.meeting_date, m.start_time, m.end_time, m.nama_pengusul 
    FROM agenda_peserta ap 
    JOIN users u ON ap.id_user = u.id 
    JOIN meetings m ON ap.id_agenda = m.id 
    WHERE ap.email_terkirim_undangan = 0 AND u.email IS NOT NULL AND u.email != ''");

while ($row = pg_fetch_assoc($q_undangan)) {
    pg_query($conn, "UPDATE agenda_peserta SET email_terkirim_undangan = 1 WHERE id = '{$row['id_ap']}'");

    $subjek = "Undangan Meeting: " . $row['title'];
    $pesan = "
        <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
            <h3 style='color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px;'>E-VISION INVITATION</h3>
            <p>Halo, <b>{$row['nama_lengkap']}</b>!</p>
            <p>Anda diundang untuk menghadiri meeting oleh <b>{$row['nama_pengusul']}</b>.</p>
            <table border='0' cellpadding='6' style='background: #f8fafc; width: 100%; border-radius: 8px;'>
                <tr><td width='30%'><b>Agenda</b></td><td>: {$row['title']}</td></tr>
                <tr><td><b>Tanggal</b></td><td>: {$row['meeting_date']}</td></tr>
                <tr><td><b>Waktu</b></td><td>: " . substr($row['start_time'], 0, 5) . " - " . substr($row['end_time'], 0, 5) . " WIB</td></tr>
                <tr><td><b>Ruangan</b></td><td>: {$row['room_name']}</td></tr>
            </table>
            <p style='margin-top: 20px;'>Mohon hadir tepat waktu. Terima kasih!</p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='font-size: 12px; color: #94a3b8; margin: 0;'>Pesan otomatis dari sistem,<br><b>Admin E-VISION</b></p>
        </div>
    ";

    if (kirimEmail($row['email'], $row['nama_lengkap'], $subjek, $pesan)) {
        $log_aktivitas .= "<div class='mb-2 text-success fw-bold'><i class='bi bi-check-circle-fill me-2'></i>Undangan terkirim ke: <span class='text-dark'>{$row['email']}</span></div>";
    } else {
        pg_query($conn, "UPDATE agenda_peserta SET email_terkirim_undangan = 0 WHERE id = '{$row['id_ap']}'");
        $log_aktivitas .= "<div class='mb-2 text-danger fw-bold'><i class='bi bi-x-circle-fill me-2'></i>Gagal kirim undangan ke: <span class='text-dark'>{$row['email']}</span></div>";
    }
}

// ==========================================
// MESIN 2: KIRIM EMAIL PENGINGAT (5 Menit Sebelum Mulai)
// ==========================================
$q_pengingat = pg_query($conn, "SELECT ap.id as id_ap, u.email, u.nama_lengkap, m.title, m.room_name, m.start_time, m.end_time 
    FROM agenda_peserta ap 
    JOIN users u ON ap.id_user = u.id 
    JOIN meetings m ON ap.id_agenda = m.id 
    WHERE ap.email_terkirim_pengingat = 0 
    AND m.meeting_date = CURRENT_DATE
    AND TO_TIMESTAMP(m.meeting_date || ' ' || m.start_time, 'YYYY-MM-DD HH24:MI:SS') BETWEEN NOW() AND NOW() + INTERVAL '5 minutes'
    AND u.email IS NOT NULL AND u.email != ''");

while ($row = pg_fetch_assoc($q_pengingat)) {
    pg_query($conn, "UPDATE agenda_peserta SET email_terkirim_pengingat = 1 WHERE id = '{$row['id_ap']}'");

    $subjek = "PENGINGAT: Meeting '{$row['title']}' Segera Dimulai!";
    $pesan = "
        <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ef4444; border-radius: 10px; max-width: 500px;'>
            <h3 style='color: #ef4444; text-align: center; border-bottom: 2px solid #ef4444; padding-bottom: 10px;'>WAKTU MEETING TIBA!</h3>
            <p>Halo, <b>{$row['nama_lengkap']}</b>.</p>
            <p>Meeting <b>{$row['title']}</b> akan dimulai dalam waktu <b>kurang dari 5 menit</b>.</p>
            <table border='0' cellpadding='6' style='background: #fef2f2; width: 100%; border-radius: 8px; margin-bottom: 15px;'>
                <tr><td width='30%'><b>Waktu</b></td><td>: " . substr($row['start_time'], 0, 5) . " - " . substr($row['end_time'], 0, 5) . " WIB</td></tr>
                <tr><td><b>Ruangan</b></td><td>: {$row['room_name']}</td></tr>
            </table>
            <p style='background: #ef4444; color: white; padding: 10px; border-radius: 5px; text-align: center; font-weight: bold;'>
                Silakan segera menuju lokasi.
            </p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='font-size: 12px; color: #94a3b8; margin: 0;'>Pesan otomatis dari sistem,<br><b>Admin E-VISION</b></p>
        </div>
    ";

    if (kirimEmail($row['email'], $row['nama_lengkap'], $subjek, $pesan)) {
        $log_aktivitas .= "<div class='mb-2 text-warning fw-bold'><i class='bi bi-alarm-fill me-2'></i>Pengingat terkirim ke: <span class='text-dark'>{$row['email']}</span></div>";
    } else {
        pg_query($conn, "UPDATE agenda_peserta SET email_terkirim_pengingat = 0 WHERE id = '{$row['id_ap']}'");
    }
}

// ==========================================
// MESIN 3: KIRIM EMAIL PEMBATALAN DARI ANTREAN
// ==========================================
$q_batal = pg_query($conn, "SELECT * FROM antrean_email WHERE status = 0");
while ($row = pg_fetch_assoc($q_batal)) {
    // Kunci data biar nggak double spam!
    pg_query($conn, "UPDATE antrean_email SET status = 1 WHERE id = '{$row['id']}'");
    
    // Eksekusi ngirim suratnya
    if (kirimEmail($row['email_tujuan'], $row['nama_tujuan'], $row['subjek'], $row['pesan_html'])) {
        // Kalau sukses kekirim, datanya langsung dihancurkan biar database lu ga penuh!
        pg_query($conn, "DELETE FROM antrean_email WHERE id = '{$row['id']}'");
        $log_aktivitas .= "<div class='mb-2 text-danger fw-bold'><i class='bi bi-trash-fill me-2'></i>Pembatalan terkirim ke: <span class='text-dark'>{$row['email_tujuan']}</span></div>";
    } else {
        // Kalau gagal, balikin statusnya biar besok dikirim lagi
        pg_query($conn, "UPDATE antrean_email SET status = 0 WHERE id = '{$row['id']}'");
    }
}

// ==========================================
// TAMPILAN OUTPUT LOG (UI RAPIH DENGAN BOOTSTRAP)
// ==========================================
echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Log E-VISION Mailer</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">';
echo '<style>body { background-color: #f8f9fa; font-family: "Inter", sans-serif; padding: 30px; }</style>';
echo '</head><body>';

if ($log_aktivitas == "") {
    echo "<div class='alert alert-secondary shadow-sm border-0' style='max-width: 600px;'>
            <div class='d-flex align-items-center'>
                <i class='bi bi-info-circle-fill fs-4 me-3 text-muted'></i>
                <div>
                    <strong>Sistem Standby</strong><br>
                    <span class='small'>Tidak ada tugas pengiriman email saat ini. (Jam: " . date('H:i:s') . " WIB)</span>
                </div>
            </div>
          </div>";
} else {
    echo "<div class='card p-4 shadow-sm border-0' style='max-width: 600px; border-radius: 12px;'>";
    echo "<h5 class='border-bottom pb-3 mb-3 fw-bold text-success'><i class='bi bi-envelope-paper-fill me-2'></i>Laporan Pengiriman Email</h5>";
    echo $log_aktivitas;
    echo "</div>";
}

echo '</body></html>';
?>
