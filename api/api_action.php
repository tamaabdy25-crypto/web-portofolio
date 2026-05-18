<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "pesan" => "Akses ditolak. Belum login."]);
    exit;
}

$my_id = $_SESSION['user_id'];
$my_no_induk = $_SESSION['no_induk'] ?? "";
$nama_lengkap = $_SESSION['nama_lengkap'] ?? "Tamu";

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i');

// ==============================================================
// 0. AUTO-CREATE TABEL KOTAK POS (ANTREAN EMAIL) BIAR KILAT ⚡
// ==============================================================
$cek_tabel = mysqli_query($conn, "SHOW TABLES LIKE 'antrean_email'");
if(mysqli_num_rows($cek_tabel) == 0) {
    mysqli_query($conn, "CREATE TABLE antrean_email (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email_tujuan VARCHAR(100),
        nama_tujuan VARCHAR(100),
        subjek VARCHAR(255),
        pesan_html TEXT,
        status INT DEFAULT 0
    )");
}

// ==============================================================
// 1. OTOMATIS TAMBAH KOLOM PESERTA BIAR GA ERROR
// ==============================================================
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM meetings LIKE 'peserta'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE meetings ADD peserta TEXT NULL AFTER end_time");
}

// ==============================================================
// 2. OBAT SAKTI: PATCH OTOMATIS BIAR JADWAL LAMA KEBACA PESERTANYA
// ==============================================================
$q_patch = mysqli_query($conn, "SELECT id FROM meetings WHERE peserta IS NULL OR peserta = ''");
if ($q_patch) {
    while($r_patch = mysqli_fetch_assoc($q_patch)) {
        $id_patch = $r_patch['id'];
        $sync_p = mysqli_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_patch'");
        if ($sync_p) {
            $arr_p = [];
            while($r_p = mysqli_fetch_assoc($sync_p)){ $arr_p[] = $r_p['nama_lengkap']; }
            if(count($arr_p) > 0) {
                $str_p = mysqli_real_escape_string($conn, implode(', ', $arr_p));
                mysqli_query($conn, "UPDATE meetings SET peserta = '$str_p' WHERE id = '$id_patch'");
            }
        }
    }
}

$action = $_POST['action'] ?? '';

// --- 1. AKSI TAMBAH JADWAL ---
if ($action == 'tambah') {
    $date  = $_POST['meeting_date'];
    $title = strtoupper(mysqli_real_escape_string($conn, $_POST['title'])); 
    $room  = strtoupper(mysqli_real_escape_string($conn, $_POST['room_name'])); 
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    
    if ($date < $hari_ini) {
        echo json_encode(["status" => "error", "pesan" => "Tanggal sudah terlewat!"]); exit;
    } else if ($date == $hari_ini && $end <= $jam_sekarang) {
        echo json_encode(["status" => "error", "pesan" => "Waktu sudah lewat dari jam saat ini!"]); exit;
    } else if ($end <= $start) {
        echo json_encode(["status" => "error", "pesan" => "Waktu selesai tidak logis!"]); exit;
    } else {
        $check_bentrok = mysqli_query($conn, "SELECT * FROM meetings WHERE room_name = '$room' AND meeting_date = '$date' AND is_finished = 0 AND (('$start' >= start_time AND '$start' < end_time) OR ('$end' > start_time AND '$end' <= end_time) OR (start_time >= '$start' AND start_time < '$end'))");
        
        if (mysqli_num_rows($check_bentrok) > 0) {
            echo json_encode(["status" => "error", "pesan" => "Ruangan dipakai di jam tersebut!"]); exit;
        } else {
            $simpan = mysqli_query($conn, "INSERT INTO meetings (title, room_name, meeting_date, start_time, end_time, user_id, is_finished, nomor_induk, nama_pengusul) VALUES ('$title', '$room', '$date', '$start', '$end', '$my_id', 0, '$my_no_induk', '$nama_lengkap')");
            
            if ($simpan) {
                $id_meeting_baru = mysqli_insert_id($conn);

                $peserta_clean = [];
                if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
                    foreach ($_POST['peserta'] as $p) {
                        $p = mysqli_real_escape_string($conn, $p);
                        if (is_numeric($p)) {
                            $peserta_clean[] = $p;
                        } else {
                            $q_cari = mysqli_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$p' LIMIT 1");
                            if($r_cari = mysqli_fetch_assoc($q_cari)){ $peserta_clean[] = $r_cari['id']; }
                        }
                    }
                }
                $peserta_clean = array_unique($peserta_clean);

                foreach ($peserta_clean as $id_karyawan) {
                    mysqli_query($conn, "INSERT INTO agenda_peserta (id_agenda, id_user, email_terkirim_undangan, email_terkirim_pengingat) VALUES ('$id_meeting_baru', '$id_karyawan', 0, 0)");
                }

                $sync_peserta = mysqli_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_meeting_baru'");
                $arr_nama = [];
                while($r = mysqli_fetch_assoc($sync_peserta)){
                    $arr_nama[] = $r['nama_lengkap'];
                }
                $str_nama = mysqli_real_escape_string($conn, implode(', ', $arr_nama));
                mysqli_query($conn, "UPDATE meetings SET peserta = '$str_nama' WHERE id = '$id_meeting_baru'");

                echo json_encode(["status" => "success", "pesan" => "Jadwal Baru Disimpan"]); exit;
            }
        }
    }
}

// --- 2. AKSI EDIT JADWAL ---
if ($action == 'edit') {
    $id_edit = $_POST['id_edit'];
    $date  = $_POST['meeting_date'];
    $title = strtoupper(mysqli_real_escape_string($conn, $_POST['title'])); 
    $room  = strtoupper(mysqli_real_escape_string($conn, $_POST['room_name'])); 
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    
    if ($date < $hari_ini) {
        echo json_encode(["status" => "error", "pesan" => "Tanggal sudah terlewat!"]); exit;
    } else if ($date == $hari_ini && $end <= $jam_sekarang) {
        echo json_encode(["status" => "error", "pesan" => "Waktu sudah lewat dari jam saat ini!"]); exit;
    } else if ($end <= $start) {
        echo json_encode(["status" => "error", "pesan" => "Waktu selesai tidak logis!"]); exit;
    } else {
        $check_bentrok = mysqli_query($conn, "SELECT * FROM meetings WHERE room_name = '$room' AND meeting_date = '$date' AND is_finished = 0 AND id != '$id_edit' AND (('$start' >= start_time AND '$start' < end_time) OR ('$end' > start_time AND '$end' <= end_time) OR (start_time >= '$start' AND start_time < '$end'))");
        
        if (mysqli_num_rows($check_bentrok) > 0) {
            echo json_encode(["status" => "error", "pesan" => "Ruangan dipakai di jam tersebut!"]); exit;
        } else {
            $update = mysqli_query($conn, "UPDATE meetings SET title='$title', room_name='$room', meeting_date='$date', start_time='$start', end_time='$end' WHERE id='$id_edit'");
            
            if ($update) {
                $peserta_baru = [];
                if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
                    foreach ($_POST['peserta'] as $p) {
                        $p = mysqli_real_escape_string($conn, $p);
                        if (is_numeric($p)) {
                            $peserta_baru[] = $p; 
                        } else {
                            $q_cari = mysqli_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$p' LIMIT 1");
                            if ($r_cari = mysqli_fetch_assoc($q_cari)) {
                                $peserta_baru[] = $r_cari['id'];
                            }
                        }
                    }
                }
                $peserta_baru = array_unique($peserta_baru);
                
                $q_lama = mysqli_query($conn, "SELECT id_user FROM agenda_peserta WHERE id_agenda = '$id_edit'");
                $peserta_lama = [];
                while($r_lama = mysqli_fetch_assoc($q_lama)){
                    $peserta_lama[] = $r_lama['id_user'];
                }

                $peserta_dihapus = array_diff($peserta_lama, $peserta_baru);

                // ==============================================================
                // HAPUS KORBAN & LEMPAR KE ANTREAN EMAIL (BIAR INSTAN LOADINGNYA)
                // ==============================================================
                if (!empty($peserta_dihapus)) {
                    foreach ($peserta_dihapus as $id_kick) {
                        $id_kick = mysqli_real_escape_string($conn, $id_kick);
                        
                        $q_korban = mysqli_query($conn, "SELECT nama_lengkap, email FROM users WHERE id = '$id_kick'");
                        if($r_korban = mysqli_fetch_assoc($q_korban)) {
                            $nama_korban = $r_korban['nama_lengkap'];
                            $email_korban = $r_korban['email'];
                            
                            if (!empty($email_korban)) {
                                $subject = "Undangan Dibatalkan: " . $title;
                                $message = "
                                <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                                    <h3 style='color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;'>UNDANGAN DIBATALKAN </h3>
                                    <p>Halo, <b>$nama_korban</b>!</p>
                                    <p>Mohon maaf, Anda telah <b>dihapus dari daftar peserta</b> untuk agenda meeting berikut:</p>
                                    <table border='0' cellpadding='6' style='background: #fef2f2; width: 100%; border-radius: 8px;'>
                                        <tr><td width='30%'><b>Agenda</b></td><td>: $title</td></tr>
                                        <tr><td><b>Tanggal</b></td><td>: $date</td></tr>
                                        <tr><td><b>Waktu</b></td><td>: " . substr($start, 0, 5) . " - " . substr($end, 0, 5) . " WIB</td></tr>
                                        <tr><td><b>Ruangan</b></td><td>: $room</td></tr>
                                    </table>
                                    <p style='margin-top: 20px;'>Silakan abaikan undangan sebelumnya. Mohon maklum atas perubahan jadwal ini.</p>
                                </div>
                                ";

                                // SIMPAN KE TABEL KOTAK POS AJA
                                $em = mysqli_real_escape_string($conn, $email_korban);
                                $nm = mysqli_real_escape_string($conn, $nama_korban);
                                $sb = mysqli_real_escape_string($conn, $subject);
                                $ps = mysqli_real_escape_string($conn, $message);
                                mysqli_query($conn, "INSERT INTO antrean_email (email_tujuan, nama_tujuan, subjek, pesan_html) VALUES ('$em', '$nm', '$sb', '$ps')");
                            }
                        }

                        mysqli_query($conn, "DELETE FROM agenda_peserta WHERE id_agenda = '$id_edit' AND id_user = '$id_kick'");
                    }
                }

                foreach ($peserta_baru as $id_karyawan) {
                    $id_karyawan = mysqli_real_escape_string($conn, $id_karyawan);
                    
                    $cek_peserta = mysqli_query($conn, "SELECT id FROM agenda_peserta WHERE id_agenda='$id_edit' AND id_user='$id_karyawan'");
                    
                    if (mysqli_num_rows($cek_peserta) == 0) {
                        mysqli_query($conn, "INSERT INTO agenda_peserta (id_agenda, id_user, email_terkirim_undangan, email_terkirim_pengingat) VALUES ('$id_edit', '$id_karyawan', 0, 0)");
                    }
                }
                
                $sync_peserta = mysqli_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_edit'");
                $arr_nama = [];
                while($r = mysqli_fetch_assoc($sync_peserta)){
                    $arr_nama[] = $r['nama_lengkap'];
                }
                $str_nama = mysqli_real_escape_string($conn, implode(', ', $arr_nama));
                mysqli_query($conn, "UPDATE meetings SET peserta = '$str_nama' WHERE id = '$id_edit'");
                
                echo json_encode(["status" => "success", "pesan" => "Agenda Diperbarui"]); exit;
            } else {
                echo json_encode(["status" => "error", "pesan" => "Gagal mengupdate agenda!"]); exit;
            }
        }
    }
}

// --- 3. AKSI HAPUS JADWAL ---
if ($action == 'hapus') {
    $id = $_POST['id'];

    $q_meet = mysqli_query($conn, "SELECT title, meeting_date, room_name, start_time, end_time FROM meetings WHERE id='$id'");
    if ($r_meet = mysqli_fetch_assoc($q_meet)) {
        $title = $r_meet['title'];
        $date  = $r_meet['meeting_date'];
        $room  = $r_meet['room_name'];
        $start = substr($r_meet['start_time'], 0, 5);
        $end   = substr($r_meet['end_time'], 0, 5);

        $q_peserta = mysqli_query($conn, "SELECT u.nama_lengkap, u.email FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda='$id'");
        
        while ($r_peserta = mysqli_fetch_assoc($q_peserta)) {
            $nama_peserta = $r_peserta['nama_lengkap'];
            $email_peserta = $r_peserta['email'];

            if (!empty($email_peserta)) {
                $subject = "MEETING DIBATALKAN: " . $title;
                $message = "
                <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                    <h3 style='color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;'>MEETING DIBATALKAN </h3>
                    <p>Halo, <b>$nama_peserta</b>!</p>
                    <p>Mohon maaf, agenda meeting berikut ini telah <b>dibatalkan sepenuhnya</b> oleh penyelenggara:</p>
                    <table border='0' cellpadding='6' style='background: #fef2f2; width: 100%; border-radius: 8px;'>
                        <tr><td width='30%'><b>Agenda</b></td><td>: $title</td></tr>
                        <tr><td><b>Tanggal</b></td><td>: $date</td></tr>
                        <tr><td><b>Waktu</b></td><td>: $start - $end WIB</td></tr>
                        <tr><td><b>Ruangan</b></td><td>: $room</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>Mohon sesuaikan kembali jadwal Anda. Terima kasih atas pengertiannya.</p>
                </div>
                ";

                // SIMPAN KE TABEL KOTAK POS AJA
                $em = mysqli_real_escape_string($conn, $email_peserta);
                $nm = mysqli_real_escape_string($conn, $nama_peserta);
                $sb = mysqli_real_escape_string($conn, $subject);
                $ps = mysqli_real_escape_string($conn, $message);
                mysqli_query($conn, "INSERT INTO antrean_email (email_tujuan, nama_tujuan, subjek, pesan_html) VALUES ('$em', '$nm', '$sb', '$ps')");
            }
        }
    }

    mysqli_query($conn, "DELETE FROM agenda_peserta WHERE id_agenda='$id'");
    mysqli_query($conn, "DELETE FROM meetings WHERE id='$id'");
    
    echo json_encode(["status" => "success", "pesan" => "Agenda Dihapus"]); exit;
}

// --- 4. AKSI SELESAIKAN JADWAL ---
if ($action == 'selesai') {
    $id = $_POST['id'];
    mysqli_query($conn, "UPDATE meetings SET is_finished=1 WHERE id='$id'");
    echo json_encode(["status" => "success", "pesan" => "Agenda Selesai"]); exit;
}

// --- 5. AKSI SIMPAN NOTULENSI ---
if ($action == 'notulensi') {
    $id_rapat = $_POST['id_meeting'];
    $notulensi = mysqli_real_escape_string($conn, $_POST['notulensi']);
    $daftar_hadir = mysqli_real_escape_string($conn, $_POST['daftar_hadir']);
    $link = mysqli_real_escape_string($conn, $_POST['link_lampiran']);

    mysqli_query($conn, "UPDATE meetings SET notulensi='$notulensi', daftar_hadir='$daftar_hadir', link_lampiran='$link' WHERE id='$id_rapat' AND user_id='$my_id'");
    echo json_encode(["status" => "success", "pesan" => "Notulensi Disimpan"]); exit;
}
?>