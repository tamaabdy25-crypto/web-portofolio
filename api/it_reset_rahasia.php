<?php
// ==========================================
// OBAT ANTI-CACHE TINGKAT DEWA (PHP HEADER)
// ==========================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();
include 'koneksi.php';

// Fallback kalau di file koneksi.php lu pakenya $koneksi
if(isset($koneksi) && !isset($conn)) {
    $conn = $koneksi;
}

// PROTEKSI HALAMAN (Hanya Role IT yang boleh masuk)
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'IT') {
    echo "<script>alert('Akses Ditolak! Anda bukan admin IT.'); window.location.href='input.php';</script>";
    exit;
}

$status_msg = "";
$search = $_GET['search'] ?? '';

// --- LOGIKA HAPUS AKUN ---
if (isset($_POST['hapus_akun'])) {
    $id_user   = $_POST['id_user_hapus'];

    // Hapus file tema jika ada sebelum menghapus user
    $cek_file = pg_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$id_user'");
    $data_file = pg_fetch_assoc($cek_file);
    if (!empty($data_file['theme_wallpaper']) && file_exists($data_file['theme_wallpaper'])) {
        unlink($data_file['theme_wallpaper']); 
    }

    if (pg_query($conn, "DELETE FROM users WHERE id = '$id_user'")) {
        $status_msg = "sukses_hapus";
    } else {
        $status_msg = "gagal_sistem";
    }
}

// ==============================================================
// 💡 BLOK BARU: LOGIKA EDIT & RESET AKUN (SISTEM AJAX ANTI-LOADING)
// ==============================================================
if (isset($_POST['edit_akun_ajax'])) {
    header('Content-Type: application/json');
    $id_user   = $_POST['id_user'];
    $nama      = pg_escape_string($conn, $_POST['nama_lengkap']);
    $email     = pg_escape_string($conn, $_POST['email']);
    $role      = $_POST['role'];
    $pw_custom = trim($_POST['pw_custom']); 

    // Ambil data user target dulu buat ngecek password lamanya
    $q_target = pg_query($conn, "SELECT password FROM users WHERE id='$id_user'");
    $target_user = pg_fetch_assoc($q_target);

    // Kalau kolom password diisi, berarti sekalian ganti password
    if (!empty($pw_custom)) {
        if (strlen($pw_custom) < 6) {
            echo json_encode(["status" => "error", "pesan" => "Password baru minimal 6 karakter bosku!"]);
            exit;
        }
        // 💡 GEMBOK BARU: Gak boleh sama dengan password target saat ini
        if (password_verify($pw_custom, $target_user['password'])) {
            echo json_encode(["status" => "error", "pesan" => "Kocak! Password reset gak boleh sama persis kayak password dia saat ini!"]);
            exit;
        }
        $hashed_pw = password_hash($pw_custom, PASSWORD_DEFAULT);
        $query_update = "UPDATE users SET nama_lengkap='$nama', email='$email', role='$role', password='$hashed_pw' WHERE id='$id_user'";
    } else {
        // Kalau kolom password kosong, update nama, email & role aja
        $query_update = "UPDATE users SET nama_lengkap='$nama', email='$email', role='$role' WHERE id='$id_user'";
    }

    if(pg_query($conn, $query_update)) {
        echo json_encode(["status" => "success", "pesan" => "Data akun berhasil di-update!"]);
    } else {
        echo json_encode(["status" => "error", "pesan" => "Gagal update ke database!"]);
    }
    exit; // Stop HTML render, cukup kirim JSON ke JS
}
// ==============================================================

// --- LOGIKA REGISTER AKUN BARU ---
if (isset($_POST['register_akun'])) {
    $no_induk = pg_escape_string($conn, $_POST['no_induk']);
    $nama     = pg_escape_string($conn, $_POST['nama_lengkap']);
    $email    = pg_escape_string($conn, $_POST['email']);
    $pass     = $_POST['password'];
    $role     = $_POST['role'];

    if (strlen($pass) < 6) {
        $status_msg = "pw_kurang";
    } else {
        $cek = pg_query($conn, "SELECT id FROM users WHERE nomor_induk='$no_induk'");
        if (pg_num_rows($cek) > 0) {
            $status_msg = "duplikat";
        } else {
            $hashed_pw = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (nomor_induk, nama_lengkap, email, password, role) 
                    VALUES ('$no_induk', '$nama', '$email', '$hashed_pw', '$role')";            
            if (pg_query($conn, $sql)) {
                $status_msg = "sukses_register";
            } else {
                $status_msg = "gagal_sistem";
            }
        }
    }
}

// --- LOGIKA PAGINATION & SEARCH (NAMA ATAU NO INDUK) ---
$limit = 10; 
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$offset = ($halaman - 1) * $limit;

$where_clause = "";
if (!empty($search)) {
    $search_safe = pg_escape_string($conn, $search);
    // 💡 POSTGRESQL FIX: Menggunakan ILIKE agar pencarian tidak case-sensitive
    $where_clause = " WHERE nomor_induk ILIKE '%$search_safe%' OR nama_lengkap ILIKE '%$search_safe%' OR email ILIKE '%$search_safe%' ";
}

$sql_count = pg_query($conn, "SELECT COUNT(*) as total FROM users $where_clause");
$row_count = pg_fetch_assoc($sql_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$query_user = pg_query($conn, "SELECT id, nama_lengkap, nomor_induk, email, role FROM users 
    $where_clause 
    ORDER BY nama_lengkap ASC 
    LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IT RESET PANEL - MANAGEMENT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-color: #0f172a;
            --brand-accent: #3b82f6;
            --bg-color: #f8fafc;
        }

        body { 
            background-color: var(--bg-color); 
            font-family: 'Inter', sans-serif; 
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Layout & Card Styles */
        .admin-container { max-width: 1200px; margin: 0 auto; }
        .main-card { 
            border-radius: 20px; 
            border: none; 
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); 
            background: #ffffff; 
            overflow: hidden;
        }
        
        /* Modern Header */
        .admin-header { 
            background: #ffffff; 
            border-bottom: 1px solid #f1f5f9;
            padding: 24px 30px;
        }
        .header-title-box { display: flex; flex-direction: column; }
        .header-title { color: var(--brand-color); font-weight: 800; font-size: 1.4rem; letter-spacing: -0.5px; }
        .header-subtitle { color: #64748b; font-size: 0.85rem; font-weight: 500; margin-top: 4px; }
        
        /* Search & Actions */
        .toolbar-wrapper { background: #f8fafc; padding: 15px 30px; border-bottom: 1px solid #f1f5f9; }
        .search-input-group { border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .search-input-group:focus-within { border-color: var(--brand-accent); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .search-input-group input { border: none; font-size: 0.9rem; padding: 12px 15px; box-shadow: none !important; }
        .search-input-group .input-group-text { background: transparent; border: none; color: #94a3b8; }
        
        .btn-add-account { background: var(--brand-color); color: white; border-radius: 12px; font-weight: 600; padding: 10px 20px; transition: 0.3s; border: none; font-size: 0.9rem; }
        .btn-add-account:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); color: white; }
        .btn-back { background: white; color: #475569; border: 1px solid #e2e8f0; border-radius: 12px; font-weight: 600; padding: 10px 20px; transition: 0.3s; font-size: 0.9rem; }
        .btn-back:hover { background: #f1f5f9; color: #0f172a; }

        /* Modern Table */
        .table-wrapper { padding: 0 10px; }
        .table { margin-bottom: 0; }
        .table thead th { 
            background: #ffffff; 
            color: #64748b; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding: 16px 20px;
        }
        .table tbody tr { transition: all 0.2s ease; cursor: default; }
        .table tbody tr:hover { background-color: #f8fafc; }
        .table tbody td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        /* Elegant Badges */
        .badge-role { font-size: 0.7rem; padding: 6px 12px; border-radius: 8px; font-weight: 700; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; }
        .bg-it { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .bg-user { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }

        /* Action Buttons */
        .action-btn-group { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.2s; border: none; }
        .btn-icon-edit { background: #eff6ff; color: #3b82f6; }
        .btn-icon-edit:hover { background: #3b82f6; color: white; }
        .btn-icon-delete { background: #fef2f2; color: #ef4444; }
        .btn-icon-delete:hover { background: #ef4444; color: white; }

        /* Pagination */
        .pagination-container { padding: 20px 30px; border-top: 1px solid #f1f5f9; background: #ffffff; }
        .pagination { margin: 0; gap: 5px; }
        .pagination .page-link { border: none; color: #64748b; background: transparent; border-radius: 8px !important; font-weight: 600; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .pagination .page-link:hover { background: #f1f5f9; color: var(--brand-color); }
        .pagination .page-item.active .page-link { background: var(--brand-color); color: white; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2); }

        /* Modal Customization */
        .modal-content { border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 20px 24px; }
        .modal-body { padding: 24px; }
        .form-label { font-weight: 600; color: #475569; font-size: 0.85rem; margin-bottom: 8px; }
        .modal .form-control, .modal .form-select { border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.95rem; }
        .modal .form-control:focus, .modal .form-select:focus { border-color: var(--brand-accent); background: #ffffff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        
        .modal-header-danger { background: #fef2f2; color: #dc2626; border-bottom: none; border-radius: 20px 20px 0 0; }
        .modal-header-primary { background: #eff6ff; color: #2563eb; border-bottom: none; border-radius: 20px 20px 0 0; }

        /* --- MEDIA QUERY KHUSUS MOBILE --- */
        @media (max-width: 768px) {
            body { padding: 15px !important; }
            .admin-header { padding: 20px; flex-direction: column; align-items: flex-start !important; gap: 15px; }
            .header-title-box { text-align: left; }
            
            .toolbar-wrapper { padding: 15px 20px; flex-direction: column; gap: 15px; }
            .search-form { width: 100%; }
            .header-actions { width: 100%; display: flex; gap: 10px; }
            .btn-add-account, .btn-back { flex: 1; justify-content: center; padding: 12px; }

            .table-wrapper { padding: 0; }
            .table thead { display: none; } 
            .table tbody tr { 
                display: flex; 
                flex-direction: column; 
                padding: 15px 20px; 
                border-bottom: 1px solid #e2e8f0; 
                position: relative;
            }
            .table tbody td { display: block; padding: 0; border: none; }
            
            .mobile-row-1 { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
            .mobile-row-2 { font-size: 0.85rem; color: #64748b; margin-bottom: 15px; }
            
            .action-btn-group { justify-content: flex-start; width: 100%; gap: 10px; }
            .btn-icon { width: auto; flex: 1; padding: 8px; font-size: 0.9rem; font-weight: 600; gap: 6px; }
            .btn-icon::after { content: attr(data-label); }
        }

        /* =======================================================
           💡 CSS BARU: ANIMASI SHAKE BUAT MODAL ERROR
           ======================================================= */
        @keyframes shakeModal {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .shake-modal {
            animation: shakeModal 0.3s ease-in-out 0s 2;
            border: 2px solid #ef4444 !important;
        }
        .error-text {
            color: #ef4444;
            font-size: 11px;
            font-weight: 700;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
</head>
<body class="py-4">
<div class="admin-container">
    <div class="main-card">
        
        <div class="admin-header d-flex justify-content-between align-items-center">
            <div class="header-title-box">
                <h1 class="header-title m-0"><i class="bi bi-shield-check text-primary me-2"></i>IT Access Panel</h1>
                <div class="header-subtitle">Management Sistem Karyawan & Autentikasi E-VISION</div>
            </div>
            <div class="header-actions d-none d-md-flex gap-2">
                <a href="input.php" class="btn btn-back">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <button type="button" class="btn btn-add-account shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRegister">
                    <i class="bi bi-plus-lg me-1"></i> Buat Akun
                </button>
            </div>
        </div>
        
        <div class="toolbar-wrapper d-flex justify-content-between align-items-center">
            <form method="GET" class="search-form flex-grow-1" style="max-width: 500px;">
                <div class="input-group search-input-group">
                    <span class="input-group-text px-3"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control ps-0" placeholder="Cari berdasarkan NIK, Nama, atau Email..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($search)): ?>
                        <a href="it_reset_rahasia.php" class="btn btn-light border-start px-3 text-muted"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="header-actions d-flex d-md-none mt-3 mt-md-0">
                <button type="button" class="btn btn-add-account w-100" data-bs-toggle="modal" data-bs-target="#modalRegister">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Informasi Karyawan</th>
                        <th>Kontak Email</th>
                        <th>Hak Akses</th>
                        <th class="text-end pe-4">Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(pg_num_rows($query_user) > 0): ?>
                        <?php while($u = pg_fetch_assoc($query_user)): ?>
                        <tr>
                            <td class="ps-md-4">
                                <div class="mobile-row-1">
                                    <div>
                                        <div class="fw-bold fs-6 text-dark" style="letter-spacing: -0.2px;">
                                            <?php echo !empty($u['nama_lengkap']) ? htmlspecialchars($u['nama_lengkap']) : '<span class="text-danger fst-italic">Tanpa Nama</span>'; ?>
                                        </div>
                                        <div class="text-muted small mt-1 fw-medium" style="font-family: monospace; font-size: 0.8rem;">
                                            <i class="bi bi-fingerprint me-1"></i>NIK: <?php echo !empty($u['nomor_induk']) ? htmlspecialchars($u['nomor_induk']) : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="d-block d-md-none">
                                        <span class="badge-role <?php echo ($u['role'] == 'IT') ? 'bg-it' : 'bg-user'; ?>">
                                            <i class="bi <?php echo ($u['role'] == 'IT') ? 'bi-shield-lock-fill' : 'bi-person-fill'; ?>"></i> <?php echo $u['role']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mobile-row-2 d-block d-md-none">
                                    <i class="bi bi-envelope me-1"></i> <?php echo !empty($u['email']) ? htmlspecialchars($u['email']) : 'Belum diatur'; ?>
                                </div>
                            </td>
                            
                            <td class="d-none d-md-table-cell text-muted fw-medium" style="font-size: 0.9rem;">
                                <?php echo !empty($u['email']) ? htmlspecialchars($u['email']) : '<span class="opacity-50 fst-italic">Belum diatur</span>'; ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="badge-role <?php echo ($u['role'] == 'IT') ? 'bg-it' : 'bg-user'; ?>">
                                    <i class="bi <?php echo ($u['role'] == 'IT') ? 'bi-shield-lock-fill' : 'bi-person-fill'; ?>"></i> <?php echo $u['role']; ?>
                                </span>
                            </td>
                            
                            <td class="pe-md-4">
                                <div class="action-btn-group">
                                    <button class="btn-icon btn-icon-edit" data-label="Edit" onclick="bukaEditUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['nomor_induk']); ?>', '<?php echo htmlspecialchars($u['nama_lengkap']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role']; ?>')" title="Edit Akun">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn-icon btn-icon-delete" data-label="Hapus" onclick="bukaHapusUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['nama_lengkap']); ?>')" title="Hapus Akun">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-search fs-1 text-muted opacity-25 d-block mb-3"></i>
                                    <h6 class="text-dark fw-bold mb-1">Data Tidak Ditemukan</h6>
                                    <p class="text-muted small">Coba gunakan kata kunci pencarian yang lain.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_halaman > 1): ?>
        <div class="pagination-container">
            <ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$total_halaman; $i++): ?>
                    <li class="page-item <?php if($halaman == $i) echo 'active'; ?>">
                        <a class="page-link" href="?hal=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="modalRegister" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-primary align-items-center">
        <h5 class="modal-title fw-bold m-0"><i class="bi bi-person-plus-fill me-2"></i>Registrasi Akun Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label">Nomor Induk Karyawan</label>
                  <input type="text" name="no_induk" class="form-control" placeholder="Contoh: 2023001" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
              </div>
              <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Alamat Email</label>
                  <input type="email" name="email" class="form-control" placeholder="karyawan@e-vision.com" required>
              </div>
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Role Akses</label>
                      <select name="role" class="form-select" required>
                          <option value="USER" selected>Karyawan (USER)</option>
                          <option value="IT">Administrator (IT)</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Password Sementara</label>
                      <input type="text" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                  </div>
              </div>
          </div>
          <div class="modal-footer gap-2">
            <button type="button" class="btn btn-light fw-bold px-4 m-0" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
            <button type="submit" name="register_akun" class="btn btn-primary fw-bold px-4 m-0" style="background: var(--brand-color); border: none; border-radius: 10px;">Daftarkan Akun</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" id="modalEditContent">
      <div class="modal-header align-items-center border-bottom-0 pb-0 pt-4 px-4">
        <h5 class="modal-title fw-bold text-dark m-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Akun</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditUser" onsubmit="editAkunAjax(event)">
          <input type="hidden" name="id_user" id="edit_id_user">
          <div class="modal-body px-4 pt-3 pb-2">
              <div class="mb-3">
                  <label class="form-label text-muted"><i class="bi bi-lock-fill me-1"></i>Nomor Induk (Permanen)</label>
                  <input type="text" id="edit_no_induk" class="form-control shadow-none" readonly style="background: #f1f5f9; font-weight: 700; color: #64748b; border: 1px dashed #cbd5e1;">
              </div>
              <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Alamat Email</label>
                  <input type="email" name="email" id="edit_email" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Role Akses</label>
                  <select name="role" id="edit_role" class="form-select" required>
                      <option value="USER">Karyawan (USER)</option>
                      <option value="IT">Administrator (IT)</option>
                  </select>
              </div>
              
              <hr class="my-4 opacity-25">
              
              <div class="mb-2">
                  <label class="form-label text-danger fw-bold"><i class="bi bi-shield-lock me-1"></i>Reset Password (Opsional)</label>
                  <input type="text" name="pw_custom" class="form-control" style="background: #fef2f2; border-color: #fecaca; color: #b91c1c;" placeholder="Ketik password baru..." minlength="6">
                  <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">*Biarkan kosong jika tidak ingin mengubah password user saat ini.</small>
              </div>
              
              <div id="error-edit-container" class="text-center mt-3" style="display: none;"></div>
          </div>
          <div class="modal-footer px-4 pb-4 border-top-0 gap-2">
            <button type="button" class="btn btn-light fw-bold flex-fill m-0" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
            <button type="submit" id="btn-simpan-edit" class="btn btn-primary fw-bold flex-fill m-0" style="background: var(--brand-accent); border: none; border-radius: 10px;">Simpan Perubahan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHapusUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content">
      <div class="modal-header modal-header-danger justify-content-center pt-4 pb-0">
        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 70px; height: 70px;">
            <i class="bi bi-trash3-fill text-danger m-0" style="font-size: 2rem;"></i>
        </div>
      </div>
      <form method="POST">
          <input type="hidden" name="id_user_hapus" id="hapus_id_user">
          <div class="modal-body text-center px-4 pt-3 pb-4">
              <h5 class="fw-bold text-dark mb-2">Hapus Permanen?</h5>
              <p class="text-muted small mb-0">Semua data, profil, dan akses milik <strong class="text-danger" id="hapus_nama_user"></strong> akan terhapus dan tidak dapat dikembalikan.</p>
          </div>
          <div class="modal-footer bg-light border-0 d-flex gap-2 p-3" style="border-radius: 0 0 20px 20px;">
            <button type="button" class="btn btn-white border fw-bold flex-fill m-0" data-bs-dismiss="modal" style="border-radius: 10px; color: #475569;">Batal</button>
            <button type="submit" name="hapus_akun" class="btn btn-danger fw-bold flex-fill m-0 shadow-sm" style="border-radius: 10px;">Ya, Hapus Akun</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function bukaEditUser(id, no_induk, nama, email, role) {
    document.getElementById('edit_id_user').value = id;
    document.getElementById('edit_no_induk').value = no_induk;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_email').value = email;
    const roleSelect = document.getElementById('edit_role');
    for(let i=0; i<roleSelect.options.length; i++){
        if(roleSelect.options[i].value === role){
            roleSelect.selectedIndex = i;
            break;
        }
    }
    new bootstrap.Modal(document.getElementById('modalEditUser')).show();
}

function bukaHapusUser(id, nama) {
    document.getElementById('hapus_id_user').value = id;
    document.getElementById('hapus_nama_user').innerText = nama;
    new bootstrap.Modal(document.getElementById('modalHapusUser')).show();
}

// =======================================================
// 💡 JAVASCRIPT: AJAX EDIT & RESET AKUN
// =======================================================
function editAkunAjax(event) {
    event.preventDefault(); 
    
    let form = document.getElementById('formEditUser');
    let formData = new FormData(form);
    formData.append('edit_akun_ajax', 'true'); 

    let btn = document.getElementById('btn-simpan-edit');
    let originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Loading...`;
    btn.disabled = true;

    fetch('it_reset_rahasia.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalEditUser')).hide();
            document.getElementById('error-edit-container').style.display = 'none';
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.pesan, confirmButtonColor: '#3b82f6', customClass: { popup: 'rounded-4' } })
            .then(() => window.location.reload());
        } else {
            // JURUS SHAKE MODAL
            let modalContent = document.getElementById('modalEditContent');
            modalContent.classList.remove('shake-modal');
            void modalContent.offsetWidth; 
            modalContent.classList.add('shake-modal');
            
            let errContainer = document.getElementById('error-edit-container');
            errContainer.innerHTML = `<span class="error-text bg-white px-3 py-2 rounded-3 border"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.pesan}</span>`;
            errContainer.style.display = 'block';
        }
    })
    .catch(err => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal terhubung ke server.', confirmButtonColor: '#ef4444' });
    });
}

// PEMBERSIH OTOMATIS PAS MODAL DITUTUP
document.getElementById('modalEditUser').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalEditContent').classList.remove('shake-modal');
    document.getElementById('error-edit-container').style.display = 'none';
    document.querySelector('input[name="pw_custom"]').value = ''; 
});

// PEMBERSIH URL & ALERT PHP
function cleanURL() { window.history.replaceState({}, document.title, window.location.pathname); }

const swalProps = { customClass: { popup: 'rounded-4 shadow-lg border-0' } };

<?php if($status_msg == "sukses_hapus"): ?>
    Swal.fire({icon: 'success', title: 'Terhapus!', text: 'Akun berhasil dihapus.', confirmButtonColor: '#10b981', ...swalProps}).then(cleanURL);
<?php elseif($status_msg == "sukses_register"): ?>
    Swal.fire({icon: 'success', title: 'Berhasil!', text: 'Akun baru telah aktif.', confirmButtonColor: '#3b82f6', ...swalProps}).then(cleanURL);
<?php elseif($status_msg == "pw_kurang"): ?>
    Swal.fire({icon: 'warning', title: 'Password Lemah!', text: 'Minimal 6 karakter.', confirmButtonColor: '#f59e0b', ...swalProps}).then(cleanURL);
<?php elseif($status_msg == "duplikat"): ?>
    Swal.fire({icon: 'error', title: 'Gagal', text: 'Nomor Induk tersebut sudah digunakan.', confirmButtonColor: '#ef4444', ...swalProps}).then(cleanURL);
<?php elseif($status_msg == "gagal_sistem"): ?>
    Swal.fire({icon: 'error', title: 'Error Sistem', text: 'Database gagal memproses.', confirmButtonColor: '#ef4444', ...swalProps}).then(cleanURL);
<?php endif; ?>
</script>
</body>
</html>
