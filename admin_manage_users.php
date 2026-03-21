<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/AuditLogger.php';

// Initialize session and AuditLogger
session_start();
if (isset($_SESSION['user_id'])) {
    AuditLogger::init($conn, $_SESSION['user_id']);
}

// Handle Toggle Verification Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_verify'])) {
    $v_uid = $_POST['v_uid'];
    $v_status = $_POST['v_status'];
    $new_status = ($v_status == 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id_pendaftar = ?");
    if ($stmt->execute([$new_status, $v_uid])) {
        $success_msg = "Status verifikasi user berhasil diubah.";
        
        // Log audit
        AuditLogger::log(AuditLogger::ACTION_VERIFY, 'users', $v_uid, [
            'is_verified' => $new_status,
            'action_type' => $new_status == 1 ? 'verify' : 'unverify'
        ]);

        // Kirim Email Notifikasi jika status menjadi Verified (1)
        if ($new_status == 1) {
            try {
                $stmt_u = $conn->prepare("SELECT email, name FROM users WHERE id_pendaftar = ?");
                $stmt_u->execute([$v_uid]);
                $user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);

                if ($user_data) {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $_ENV['SMTP_HOST'];
                    $mail->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN);
                    $mail->Port       = $_ENV['SMTP_PORT'];
                    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                    $mail->addAddress($user_data['email'], $user_data['name']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Akun Anda Telah Diverifikasi - SMK Lab Jakarta';
                    $mail->Body    = "Halo " . htmlspecialchars($user_data['name']) . ",<br><br>Selamat! Akun Anda telah diverifikasi oleh Admin. Anda sekarang dapat login dan mengakses fitur lengkap pendaftaran.<br><br>Salam,<br>Panitia PPDB";
                    $mail->send();
                    $success_msg .= " Email notifikasi terkirim.";
                }
            } catch (Exception $e) {
                // Jangan gagalkan proses utama jika email gagal
                $success_msg .= " (Gagal kirim email: " . $e->getMessage() . ")";
            }
        }
    } else {
        $error = "Gagal mengubah status verifikasi.";
    }
}

// Handle Toggle Ban Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ban'])) {
    $b_uid = $_POST['b_uid'];
    $b_status = $_POST['b_status'];
    $new_ban_status = ($b_status == 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE users SET is_banned = ? WHERE id_pendaftar = ?");
    if ($stmt->execute([$new_ban_status, $b_uid])) {
        $success_msg = "Status ban user berhasil diubah.";
        
        // Log audit
        AuditLogger::log(AuditLogger::ACTION_BAN, 'users', $b_uid, [
            'is_banned' => $new_ban_status,
            'action_type' => $new_ban_status == 1 ? 'ban' : 'unban'
        ]);
    } else {
        $error = "Gagal mengubah status ban.";
    }
}

// Handle Reset Password Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_user_password'])) {
    $target_uid = $_POST['reset_uid'];
    $new_pass = $_POST['reset_new_password'];
    
    if (!empty($target_uid) && !empty($new_pass)) {
        if (strlen($new_pass) < 8) {
             $error = "Password must be at least 8 characters.";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_pendaftar = ?");
            if ($stmt->execute([$hashed, $target_uid])) {
                $success_msg = "Password berhasil direset.";
                
                // Log audit
                AuditLogger::log(AuditLogger::ACTION_PASSWORD_RESET, 'users', $target_uid, [
                    'action_type' => 'admin_reset'
                ]);
            } else {
                $error = "Gagal mereset password.";
            }
        }
    }
}

// Handle Edit User Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_uid = $_POST['edit_uid'];
    $edit_name = trim($_POST['edit_name']);
    $edit_email = trim($_POST['edit_email']);

    if (!empty($edit_uid) && !empty($edit_name) && !empty($edit_email)) {
        // Check if email is taken by another user
        $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE email = ? AND id_pendaftar != ?");
        $stmt->execute([$edit_email, $edit_uid]);
        if ($stmt->fetch()) {
            $error = "Email sudah digunakan oleh user lain.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id_pendaftar = ?");
            if ($stmt->execute([$edit_name, $edit_email, $edit_uid])) {
                $success_msg = "Data user berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui data user.";
            }
        }
    } else {
        $error = "Nama dan Email wajib diisi.";
    }
}

// Handle Delete User Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $del_uid = $_POST['delete_uid'];
    if (!empty($del_uid)) {
        try {
            $conn->beginTransaction();

            // Delete files (Documents & Photo)
            $stmt = $conn->prepare("SELECT nama_file FROM unggah_dokumen WHERE id_pendaftar = ?");
            $stmt->execute([$del_uid]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (file_exists('uploads/' . $row['nama_file'])) unlink('uploads/' . $row['nama_file']);
            }
            
            $stmt = $conn->prepare("SELECT foto FROM data_peserta WHERE id_pendaftar = ?");
            $stmt->execute([$del_uid]);
            $foto = $stmt->fetchColumn();
            if ($foto && file_exists('uploads/' . $foto)) unlink('uploads/' . $foto);

            // Delete User (Cascading will handle some, but manual cleanup ensures consistency)
            $stmt = $conn->prepare("DELETE FROM users WHERE id_pendaftar = ?");
            $stmt->execute([$del_uid]);
            
            $conn->commit();
            $success_msg = "User berhasil dihapus beserta data terkait.";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Gagal menghapus user: " . $e->getMessage();
        }
    }
}

// Handle Update Student Profile Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student_profile'])) {
    $p_uid = $_POST['p_uid'];
    $p_nama = trim($_POST['p_nama']);
    $p_nisn = trim($_POST['p_nisn']);
    $p_tempat_lahir = trim($_POST['p_tempat_lahir']);
    $p_tanggal_lahir = $_POST['p_tanggal_lahir'];
    $p_asal_sekolah = trim($_POST['p_asal_sekolah']);
    $p_npsn = trim($_POST['p_npsn']);
    $p_jurusan = $_POST['p_jurusan'];
    $p_telp_siswa = trim($_POST['p_telp_siswa']);
    $p_telp_ortu = trim($_POST['p_telp_ortu']);
    $p_alamat = trim($_POST['p_alamat']);

    try {
        $conn->beginTransaction();

        // Check if record exists
        $stmt = $conn->prepare("SELECT id_pendaftar FROM data_peserta WHERE id_pendaftar = ?");
        $stmt->execute([$p_uid]);
        
        if ($stmt->fetch()) {
            $sql = "UPDATE data_peserta SET nama=?, nisn=?, kota=?, tanggal_lahir=?, asal_sekolah=?, npsn=?, jurusan=?, no_telp_siswa=?, no_telp_ortu=?, alamat=? WHERE id_pendaftar=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$p_nama, $p_nisn, $p_tempat_lahir, $p_tanggal_lahir, $p_asal_sekolah, $p_npsn, $p_jurusan, $p_telp_siswa, $p_telp_ortu, $p_alamat, $p_uid]);
        } else {
            // Insert logic if record doesn't exist
            $sql = "INSERT INTO data_peserta (id_pendaftar, nama, nisn, kota, tanggal_lahir, asal_sekolah, npsn, jurusan, no_telp_siswa, no_telp_ortu, alamat) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$p_uid, $p_nama, $p_nisn, $p_tempat_lahir, $p_tanggal_lahir, $p_asal_sekolah, $p_npsn, $p_jurusan, $p_telp_siswa, $p_telp_ortu, $p_alamat]);
        }

        // Sync users table
        $stmt = $conn->prepare("UPDATE users SET name = ?, nisn = ? WHERE id_pendaftar = ?");
        $stmt->execute([$p_nama, $p_nisn, $p_uid]);

        $conn->commit();
        $success_msg = "Profil siswa berhasil diperbarui.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Gagal memperbarui profil: " . $e->getMessage();
    }
}

// Handle Bulk Edit School Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_edit_school'])) {
    $old_school = trim($_POST['old_school_name']);
    $new_school = trim($_POST['new_school_name']);

    if (!empty($old_school) && !empty($new_school) && $old_school !== $new_school) {
        $stmt = $conn->prepare("UPDATE data_peserta SET asal_sekolah = ? WHERE asal_sekolah = ?");
        if ($stmt->execute([$new_school, $old_school])) {
            $count = $stmt->rowCount();
            $success_msg = "Berhasil memperbarui nama sekolah untuk $count siswa.";
        } else {
            $error = "Gagal memperbarui data sekolah.";
        }
    }
}

// Pagination Setup
$limit = 10;
$page_num = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$offset = ($page_num - 1) * $limit;

// Search Parameter
$search = trim($_GET['search'] ?? '');
$search_param = "%{$search}%";
// Filter Status Parameter
$filter_status = isset($_GET['filter_status']) && $_GET['filter_status'] !== '' ? $_GET['filter_status'] : '';
// Filter Jurusan Parameter
$filter_jurusan = isset($_GET['filter_jurusan']) && $_GET['filter_jurusan'] !== '' ? $_GET['filter_jurusan'] : '';
// Filter Kota Parameter
$filter_kota = isset($_GET['filter_kota']) && $_GET['filter_kota'] !== '' ? $_GET['filter_kota'] : '';

// Fetch Cities for Filter
$cities_stmt = $conn->query("SELECT DISTINCT kota FROM data_peserta WHERE kota IS NOT NULL AND kota != '' ORDER BY kota ASC");
$cities = $cities_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Schools for Bulk Edit Datalist
$schools_stmt = $conn->query("SELECT DISTINCT asal_sekolah FROM data_peserta WHERE asal_sekolah IS NOT NULL AND asal_sekolah != '' ORDER BY asal_sekolah ASC");
$schools_list = $schools_stmt->fetchAll(PDO::FETCH_COLUMN);

// Count Total Users
$count_sql = "SELECT COUNT(*) FROM users u LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar WHERE u.role = 'user'";
if (!empty($search)) {
    $count_sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.nisn LIKE :search)";
}
if ($filter_status !== '') {
    $count_sql .= " AND u.is_verified = :status";
}
if ($filter_jurusan !== '') {
    $count_sql .= " AND dp.jurusan = :jurusan";
}
if ($filter_kota !== '') {
    $count_sql .= " AND dp.kota = :kota";
}
$stmt = $conn->prepare($count_sql);
if (!empty($search)) $stmt->bindValue(':search', $search_param);
if ($filter_status !== '') $stmt->bindValue(':status', $filter_status, PDO::PARAM_INT);
if ($filter_jurusan !== '') $stmt->bindValue(':jurusan', $filter_jurusan);
if ($filter_kota !== '') $stmt->bindValue(':kota', $filter_kota);
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch users data
try {
    $sql = "
        SELECT 
            u.id_pendaftar,
            u.nisn,
            u.name,
            u.email,
            u.is_verified,
            u.is_banned,
            u.created_at,
            p.status_pendaftaran,
            h.hasil,
            dp.tanggal_lahir,
            dp.asal_sekolah,
            dp.no_telp_siswa,
            dp.no_telp_ortu,
            dp.alamat,
            dp.jurusan,
            dp.foto,
            dp.npsn,
            dp.provinsi,
            dp.kota,
            dp.Kecamatan
        FROM users u
        LEFT JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        LEFT JOIN hasil_daftar h ON u.id_pendaftar = h.id_pendaftar
        LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        WHERE u.role = 'user'
    ";

    if (!empty($search)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.nisn LIKE :search)";
    }
    if ($filter_status !== '') {
        $sql .= " AND u.is_verified = :status";
    }
    if ($filter_jurusan !== '') {
        $sql .= " AND dp.jurusan = :jurusan";
    }
    if ($filter_kota !== '') {
        $sql .= " AND dp.kota = :kota";
    }

    $sql .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', $search_param);
    }
    if ($filter_status !== '') {
        $stmt->bindValue(':status', $filter_status, PDO::PARAM_INT);
    }
    if ($filter_jurusan !== '') {
        $stmt->bindValue(':jurusan', $filter_jurusan);
    }
    if ($filter_kota !== '') {
        $stmt->bindValue(':kota', $filter_kota);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<?php if (isset($success_msg)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<!-- Manage Users Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Manage Users</h5>
        <div>
            <a href="admin_print_users.php" target="_blank" class="btn btn-danger btn-sm me-1"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</a>
            <a href="admin_export_users.php" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Download Excel</a>
            <button type="button" class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#bulkEditSchoolModal"><i class="bi bi-pencil-square me-1"></i> Bulk Edit Sekolah</button>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <form method="GET" action="" class="row g-2">
                <input type="hidden" name="page" value="users">
                <div class="col-md-2 col-6">
                    <select name="filter_status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="1" <?php echo $filter_status === '1' ? 'selected' : ''; ?>>Verified</option>
                        <option value="0" <?php echo $filter_status === '0' ? 'selected' : ''; ?>>Unverified</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="filter_jurusan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Jurusan</option>
                        <option value="Rekayasa Perangkat Lunak (RPL)" <?php echo $filter_jurusan === 'Rekayasa Perangkat Lunak (RPL)' ? 'selected' : ''; ?>>RPL</option>
                        <option value="Teknik Komputer dan Jaringan (TKJ)" <?php echo $filter_jurusan === 'Teknik Komputer dan Jaringan (TKJ)' ? 'selected' : ''; ?>>TKJ</option>
                        <option value="Asisten Keperawatan (AP)" <?php echo $filter_jurusan === 'Asisten Keperawatan (AP)' ? 'selected' : ''; ?>>AP</option>
                        <option value="Tata Kecantikan Kulit dan Rambut (TKKR)" <?php echo $filter_jurusan === 'Tata Kecantikan Kulit dan Rambut (TKKR)' ? 'selected' : ''; ?>>TKKR</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_kota" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kota</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_kota === $city ? 'selected' : ''; ?>><?php echo htmlspecialchars($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari user (Nama, NISN, Email)..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                        <a href="?page=users" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NISN</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Verified</th>
                        <th>Status Dokument</th>
                        <th>Hasil</th>
                        <th>Created At</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nisn'] ?? '-'); ?></td>
                                <td>
                                    <a href="#" class="text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#viewProfileModal" 
                                       data-uid="<?php echo $user['id_pendaftar']; ?>"
                                       data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                       data-nisn="<?php echo htmlspecialchars($user['nisn'] ?? '-'); ?>"
                                       data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                       data-sekolah="<?php echo htmlspecialchars($user['asal_sekolah'] ?? '-'); ?>"
                                       data-npsn="<?php echo htmlspecialchars($user['npsn'] ?? '-'); ?>"
                                       data-jurusan="<?php echo htmlspecialchars($user['jurusan'] ?? '-'); ?>"
                                       data-telp="<?php echo htmlspecialchars($user['no_telp_siswa'] ?? '-'); ?>"
                                       data-ortu="<?php echo htmlspecialchars($user['no_telp_ortu'] ?? '-'); ?>"
                                       data-alamat="<?php echo htmlspecialchars($user['alamat'] ?? '-'); ?>"
                                       data-foto="<?php echo htmlspecialchars($user['foto'] ?? ''); ?>"
                                       data-tgl-lahir="<?php echo htmlspecialchars($user['tanggal_lahir'] ?? ''); ?>"
                                       data-tempat-lahir="<?php echo htmlspecialchars($user['kota'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if (!empty($user['is_banned'])): ?>
                                            <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">BANNED</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_verified']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Batalkan verifikasi user ini? User tidak akan bisa login.');">
                                            <input type="hidden" name="v_uid" value="<?php echo $user['id_pendaftar']; ?>">
                                            <input type="hidden" name="v_status" value="1">
                                            <button type="submit" name="toggle_verify" class="btn btn-link btn-sm text-danger p-0 ms-1" title="Unverify">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Unverified</span>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="v_uid" value="<?php echo $user['id_pendaftar']; ?>">
                                            <input type="hidden" name="v_status" value="0">
                                            <button type="submit" name="toggle_verify" class="btn btn-sm btn-outline-success py-0 px-2 ms-1" title="Verifikasi Manual">
                                                <i class="bi bi-check-lg"></i> Verify
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status = $user['status_pendaftaran'] ?? 'Belum Daftar';
                                    $statusClass = match($status) {
                                        'Terkirim' => 'bg-success',
                                        'Draft' => 'bg-secondary',
                                        default => 'bg-light text-dark border'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $hasil = $user['hasil'] ?? '-';
                                    $hasilClass = match($hasil) {
                                        'diterima' => 'bg-success',
                                        'tidak diterima' => 'bg-danger',
                                        default => 'bg-light text-dark border'
                                    };
                                    ?>
                                    <span class="badge <?php echo $hasilClass; ?>"><?php echo htmlspecialchars(ucfirst($hasil)); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($user['created_at']))); ?></td>
                                <td class="text-nowrap text-center">   <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" data-uid="<?php echo $user['id_pendaftar']; ?>" data-uname="<?php echo htmlspecialchars($user['name']); ?>" data-uemail="<?php echo htmlspecialchars($user['email']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('<?php echo !empty($user['is_banned']) ? 'Buka blokir user ini?' : 'Blokir user ini? User tidak akan bisa login.'; ?>');">
                                        <input type="hidden" name="b_uid" value="<?php echo $user['id_pendaftar']; ?>">
                                        <input type="hidden" name="b_status" value="<?php echo $user['is_banned'] ?? 0; ?>">
                                        <button type="submit" name="toggle_ban" class="btn btn-<?php echo !empty($user['is_banned']) ? 'success' : 'dark'; ?> btn-sm" title="<?php echo !empty($user['is_banned']) ? 'Unban User' : 'Ban User'; ?>">
                                            <i class="bi bi-<?php echo !empty($user['is_banned']) ? 'unlock' : 'slash-circle'; ?>"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-uid="<?php echo $user['id_pendaftar']; ?>" data-uname="<?php echo htmlspecialchars($user['name']); ?>">
                                        <i class="bi bi-key"></i> Reset Pass
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-uid="<?php echo $user['id_pendaftar']; ?>" data-uname="<?php echo htmlspecialchars($user['name']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <a href="?page=documents&download_zip=1&uid=<?php echo $user['id_pendaftar']; ?>" class="btn btn-secondary btn-sm" title="Download ZIP Dokumen">
                                        <i class="bi bi-file-zip"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page_num <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=users&p=<?php echo $page_num - 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_kota=<?php echo urlencode($filter_kota); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page_num == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=users&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_kota=<?php echo urlencode($filter_kota); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=users&p=<?php echo $page_num + 1; ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo urlencode($filter_status); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_kota=<?php echo urlencode($filter_kota); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Edit School Modal -->
<div class="modal fade" id="bulkEditSchoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Edit Nama Sekolah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p class="small text-muted">Fitur ini akan mengubah nama sekolah yang salah (typo) menjadi nama yang benar untuk <strong>semua siswa</strong> yang memiliki nama sekolah asal tersebut.</p>
                    <div class="mb-3">
                        <label class="form-label">Nama Sekolah Lama (Salah)</label>
                        <input type="text" name="old_school_name" class="form-control" list="schoolList" required placeholder="Ketik untuk mencari...">
                        <datalist id="schoolList">
                            <?php foreach ($schools_list as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Sekolah Baru (Benar)</label>
                        <input type="text" name="new_school_name" class="form-control" required placeholder="Masukkan nama yang benar">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="bulk_edit_school" class="btn btn-primary" onclick="return confirm('Yakin ingin mengubah nama sekolah ini untuk semua siswa terkait?')">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_uid" id="edit_uid">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="reset_uid" id="reset_uid">
                    <p>Reset password untuk: <strong id="reset_uname"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="text" name="reset_new_password" class="form-control" placeholder="Masukkan password baru" required minlength="8">
                        <div class="form-text">Password minimal 8 karakter.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="reset_user_password" class="btn btn-primary">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="delete_uid" id="delete_uid">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Tindakan ini tidak dapat dibatalkan!
                    </div>
                    <p>Apakah Anda yakin ingin menghapus user: <strong id="delete_uname"></strong>?</p>
                    <p class="small text-muted">Semua data pendaftaran, dokumen, dan foto akan dihapus permanen.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Hapus User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Profile Modal -->
<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-lines-fill me-2"></i>Edit Profil Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="p_uid" id="p_uid">
                
                <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Data Siswa</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Activity Log</button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <!-- Tab Data Siswa -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <img id="view_foto" src="" class="img-thumbnail" style="width: 150px; height: 200px; object-fit: cover; display: none;">
                                <div id="view_no_foto" class="bg-light d-flex align-items-center justify-content-center mx-auto border" style="width: 150px; height: 200px;">
                                    <span class="text-muted">No Photo</span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <table class="table table-borderless table-sm align-middle">
                                    <tr><td width="30%" class="fw-bold">Nama Lengkap</td><td><input type="text" name="p_nama" id="p_nama" class="form-control form-control-sm" required></td></tr>
                                    <tr><td class="fw-bold">NISN</td><td><input type="text" name="p_nisn" id="p_nisn" class="form-control form-control-sm" required></td></tr>
                                    <tr><td class="fw-bold">Email</td><td><input type="text" id="p_email" class="form-control form-control-sm bg-light" readonly disabled title="Email tidak dapat diubah dari sini"></td></tr>
                                    <tr><td class="fw-bold">Tempat Lahir</td><td><input type="text" name="p_tempat_lahir" id="p_tempat_lahir" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">Tanggal Lahir</td><td><input type="date" name="p_tanggal_lahir" id="p_tanggal_lahir" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">Asal Sekolah</td><td><input type="text" name="p_asal_sekolah" id="p_asal_sekolah" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">NPSN</td><td><input type="text" name="p_npsn" id="p_npsn" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">Jurusan</td><td>
                                        <select name="p_jurusan" id="p_jurusan" class="form-select form-select-sm">
                                            <option value="">Pilih Jurusan</option>
                                            <option value="Rekayasa Perangkat Lunak (RPL)">RPL</option>
                                            <option value="Teknik Komputer dan Jaringan (TKJ)">TKJ</option>
                                            <option value="Asisten Keperawatan (AP)">AP</option>
                                            <option value="Tata Kecantikan Kulit dan Rambut (TKKR)">TKKR</option>
                                        </select>
                                    </td></tr>
                                    <tr><td class="fw-bold">Telp Siswa</td><td><input type="text" name="p_telp_siswa" id="p_telp_siswa" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">Telp Ortu</td><td><input type="text" name="p_telp_ortu" id="p_telp_ortu" class="form-control form-control-sm"></td></tr>
                                    <tr><td class="fw-bold">Alamat</td><td><textarea name="p_alamat" id="p_alamat" class="form-control form-control-sm" rows="2"></textarea></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Activity Log -->
                    <div class="tab-pane fade" id="activity" role="tabpanel">
                        <table class="table table-striped table-sm">
                            <thead><tr><th>Waktu</th><th>Aktivitas</th><th>IP Address</th></tr></thead>
                            <tbody id="activityLogBody">
                                <tr><td colspan="3" class="text-center">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="update_student_profile" class="btn btn-primary">Simpan Perubahan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    var editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var uid = button.getAttribute('data-uid');
        var uname = button.getAttribute('data-uname');
        var uemail = button.getAttribute('data-uemail');
        
        document.getElementById('edit_uid').value = uid;
        document.getElementById('edit_name').value = uname;
        document.getElementById('edit_email').value = uemail;
    });

    var resetModal = document.getElementById('resetPasswordModal');
    resetModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var uid = button.getAttribute('data-uid');
        var uname = button.getAttribute('data-uname');
        
        document.getElementById('reset_uid').value = uid;
        document.getElementById('reset_uname').textContent = uname;
    });

    var deleteModal = document.getElementById('deleteUserModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var uid = button.getAttribute('data-uid');
        var uname = button.getAttribute('data-uname');
        
        document.getElementById('delete_uid').value = uid;
        document.getElementById('delete_uname').textContent = uname;
    });

    var viewModal = document.getElementById('viewProfileModal');
    viewModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var uid = button.getAttribute('data-uid');
        
        document.getElementById('p_uid').value = uid;
        document.getElementById('p_nama').value = button.getAttribute('data-name');
        document.getElementById('p_nisn').value = button.getAttribute('data-nisn');
        document.getElementById('p_email').value = button.getAttribute('data-email');
        document.getElementById('p_tempat_lahir').value = button.getAttribute('data-tempat-lahir');
        document.getElementById('p_tanggal_lahir').value = button.getAttribute('data-tgl-lahir');
        document.getElementById('p_asal_sekolah').value = button.getAttribute('data-sekolah');
        document.getElementById('p_npsn').value = button.getAttribute('data-npsn');
        document.getElementById('p_jurusan').value = button.getAttribute('data-jurusan');
        document.getElementById('p_telp_siswa').value = button.getAttribute('data-telp');
        document.getElementById('p_telp_ortu').value = button.getAttribute('data-ortu');
        document.getElementById('p_alamat').value = button.getAttribute('data-alamat');
        
        var foto = button.getAttribute('data-foto');
        if (foto) {
            document.getElementById('view_foto').src = 'uploads/' + foto;
            document.getElementById('view_foto').style.display = 'block';
            document.getElementById('view_no_foto').style.display = 'none';
        } else {
            document.getElementById('view_foto').style.display = 'none';
            document.getElementById('view_no_foto').style.display = 'flex';
        }

        // Reset Tab to Details
        var firstTabEl = document.querySelector('#profileTabs button[data-bs-target="#details"]');
        var tab = new bootstrap.Tab(firstTabEl);
        tab.show();

        // Fetch Activity Logs
        var logBody = document.getElementById('activityLogBody');
        logBody.innerHTML = '<tr><td colspan="3" class="text-center">Memuat data...</td></tr>';
        
        var formData = new FormData();
        formData.append('uid', uid);
        
        fetch('admin_fetch_user_activity.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                logBody.innerHTML = '';
                if(data.success && data.data.length > 0) {
                    data.data.forEach(log => {
                        logBody.innerHTML += `<tr><td>${log.formatted_date}</td><td>${log.action}</td><td>${log.ip_address}</td></tr>`;
                    });
                } else {
                    logBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada aktivitas.</td></tr>';
                }
            })
            .catch(err => {
                logBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat log.</td></tr>';
            });
    });
</script>