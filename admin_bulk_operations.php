<?php

/**
 * Admin Bulk Operations - User Management
 * Features: CSV Import, Bulk Actions, Batch Processing
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/EmailUtil.php';
require_once __DIR__ . '/env_loader.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error = '';
$import_result = null;

// ============================================================
// HANDLE CSV IMPORT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];

    if (!file_exists($file)) {
        $error = 'File tidak ditemukan.';
    } else if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'csv') {
        $error = 'Hanya file CSV yang diperbolehkan.';
    } else {
        try {
            $conn->beginTransaction();
            $imported = 0;
            $failed = 0;
            $failed_rows = [];

            if (($handle = fopen($file, 'r')) !== false) {
                $header = fgetcsv($handle);

                // Expected columns: name, email, nisn, password (optional - auto-generate if empty)
                $expectedCols = ['name', 'email', 'nisn'];
                $colIndices = [];

                foreach ($expectedCols as $col) {
                    $idx = array_search($col, $header);
                    if ($idx === false) {
                        throw new Exception("Kolom '$col' tidak ditemukan dalam CSV.");
                    }
                    $colIndices[$col] = $idx;
                }

                $rowNum = 2;
                while (($row = fgetcsv($handle)) !== false) {
                    try {
                        $name = trim($row[$colIndices['name']] ?? '');
                        $email = trim($row[$colIndices['email']] ?? '');
                        $nisn = trim($row[$colIndices['nisn']] ?? '');
                        $password = trim($row[$colIndices['password']] ?? '') ?: bin2hex(random_bytes(6));

                        // Validation
                        if (empty($name) || empty($email) || empty($nisn)) {
                            throw new Exception('Nama, Email, dan NISN harus diisi.');
                        }

                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception('Email tidak valid.');
                        }

                        // Check if email exists
                        $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            throw new Exception("Email sudah terdaftar: $email");
                        }

                        // Check if NISN exists
                        $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE nisn = ?");
                        $stmt->execute([$nisn]);
                        if ($stmt->fetch()) {
                            throw new Exception("NISN sudah terdaftar: $nisn");
                        }

                        // Insert user
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            INSERT INTO users (name, email, nisn, password, role, is_verified, created_at)
                            VALUES (?, ?, ?, ?, 'user', 0, NOW())
                        ");
                        $stmt->execute([$name, $email, $nisn, $hashedPassword]);
                        $userId = $conn->lastInsertId();

                        // Insert student profile
                        $stmt = $conn->prepare("
                            INSERT INTO data_peserta (id_pendaftar, nama, nisn, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$userId, $name, $nisn]);

                        $imported++;
                    } catch (Exception $e) {
                        $failed++;
                        $failed_rows[] = [
                            'row' => $rowNum,
                            'error' => $e->getMessage()
                        ];
                    }
                    $rowNum++;
                }

                fclose($handle);
                $conn->commit();

                $import_result = [
                    'success' => $failed === 0,
                    'imported' => $imported,
                    'failed' => $failed,
                    'failed_rows' => $failed_rows
                ];

                if ($imported > 0) {
                    $success_msg = "Berhasil mengimport $imported user" . ($failed > 0 ? ", gagal $failed user." : '.');
                }
                if ($failed > 0) {
                    $error = "Gagal mengimport $failed user. Lihat detail di bawah.";
                }
            } else {
                throw new Exception('Tidak dapat membuka file CSV.');
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// ============================================================
// HANDLE BULK ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $user_ids = isset($_POST['selected_users']) ? (array)$_POST['selected_users'] : [];

    if (empty($user_ids)) {
        $error = 'Pilih minimal satu user untuk aksi bulk.';
    } else {
        try {
            $conn->beginTransaction();
            $success_count = 0;

            if ($action === 'verify') {
                // Verify selected users
                foreach ($user_ids as $uid) {
                    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id_pendaftar = ?");
                    if ($stmt->execute([$uid])) {

                        // Send notification email
                        try {
                            $stmt_u = $conn->prepare("SELECT email, name FROM users WHERE id_pendaftar = ?");
                            $stmt_u->execute([$uid]);
                            $user = $stmt_u->fetch(PDO::FETCH_ASSOC);

                            if ($user) {
                                $emailResult = EmailUtil::sendResetPasswordEmail(
                                    $user['email'],
                                    $user['name'],
                                    rtrim($_ENV['APP_URL'], '/') . '/dashboard.php'
                                );
                            }
                        } catch (Exception $e) {
                            // Continue even if email fails
                        }

                        $success_count++;
                    }
                }
                $success_msg = "Berhasil memverifikasi $success_count user.";
            } elseif ($action === 'ban') {
                // Ban selected users
                foreach ($user_ids as $uid) {
                    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id_pendaftar = ?");
                    if ($stmt->execute([$uid])) {
                        $success_count++;
                    }
                }
                $success_msg = "Berhasil memban $success_count user.";
            } elseif ($action === 'unban') {
                // Unban selected users
                foreach ($user_ids as $uid) {
                    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id_pendaftar = ?");
                    if ($stmt->execute([$uid])) {
                        $success_count++;
                    }
                }
                $success_msg = "Berhasil membuka ban untuk $success_count user.";
            } elseif ($action === 'send_email') {
                // Send email to selected users
                $email_subject = $_POST['email_subject'] ?? 'Notifikasi dari Panitia PPDB';
                $email_body = $_POST['email_body'] ?? '';

                if (empty($email_body)) {
                    throw new Exception('Pesan email tidak boleh kosong.');
                }

                foreach ($user_ids as $uid) {
                    try {
                        $stmt_u = $conn->prepare("SELECT email, name FROM users WHERE id_pendaftar = ?");
                        $stmt_u->execute([$uid]);
                        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);

                        if ($user) {
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $_ENV['SMTP_HOST'] ?? 'localhost';
                            $mail->SMTPAuth = filter_var($_ENV['SMTP_AUTH'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
                            $mail->Port = $_ENV['SMTP_PORT'] ?? 1025;
                            $mail->Username = $_ENV['SMTP_USER'] ?? '';
                            $mail->Password = $_ENV['SMTP_PASS'] ?? '';
                            $mail->SMTPSecure = '';

                            $mail->SMTPOptions = [
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                ]
                            ];

                            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                            $mail->addAddress($user['email'], $user['name']);
                            $mail->isHTML(true);
                            $mail->Subject = $email_subject;
                            $mail->Body = nl2br(htmlspecialchars($email_body));
                            $mail->send();
                            $success_count++;
                        }
                    } catch (Exception $e) {
                        // Continue sending to others
                        error_log("Email send failed for user $uid: " . $e->getMessage());
                    }
                }
                $success_msg = "Email berhasil dikirim ke $success_count user.";
            } elseif ($action === 'delete') {
                // Delete selected users
                foreach ($user_ids as $uid) {
                    try {
                        // Delete files
                        $stmt = $conn->prepare("SELECT nama_file FROM unggah_dokumen WHERE id_pendaftar = ?");
                        $stmt->execute([$uid]);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $filepath = 'uploads/' . $row['nama_file'];
                            if (file_exists($filepath)) @unlink($filepath);
                        }

                        $stmt = $conn->prepare("SELECT foto FROM data_peserta WHERE id_pendaftar = ?");
                        $stmt->execute([$uid]);
                        $foto = $stmt->fetchColumn();
                        if ($foto && file_exists('uploads/' . $foto)) @unlink('uploads/' . $foto);

                        // Delete user
                        $stmt = $conn->prepare("DELETE FROM users WHERE id_pendaftar = ?");
                        if ($stmt->execute([$uid])) {
                            $success_count++;
                        }
                    } catch (Exception $e) {
                        error_log("Delete user failed for $uid: " . $e->getMessage());
                    }
                }
                $success_msg = "Berhasil menghapus $success_count user.";
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Fetch all users for bulk operations
$users_stmt = $conn->query("
    SELECT 
        u.id_pendaftar,
        u.name,
        u.email,
        u.nisn,
        u.is_verified,
        u.is_banned,
        dp.jenis_jurusan
    FROM users u
    LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Operations - Admin SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .main {
            padding-top: 120px;
            padding-bottom: 40px;
        }

        .section-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .csv-upload-area {
            border: 2px dashed #0d6efd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .csv-upload-area:hover {
            background-color: #f0f7ff;
            border-color: #0d6efd;
        }

        .csv-upload-area.dragover {
            background-color: #e7f1ff;
            border-color: #0d6efd;
            transform: scale(1.02);
        }

        .section-title {
            color: #333;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .summary-box h5 {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .summary-box .number {
            font-size: 2rem;
            font-weight: bold;
        }

        .user-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .badge-verify {
            background-color: #28a745;
        }

        .badge-ban {
            background-color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .action-buttons button {
            min-width: 120px;
        }

        .import-result {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .result-item {
            padding: 10px;
            border-left: 4px solid #dc3545;
            margin: 5px 0;
            background: #fff5f5;
            border-radius: 4px;
        }

        .result-item.success {
            border-left-color: #28a745;
            background: #f0fdf4;
        }

        .result-item.error {
            border-left-color: #dc3545;
            background: #fef2f2;
        }

        @media (max-width: 768px) {
            .csv-upload-area {
                padding: 20px;
            }

            .section-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
<?php
} // End of full HTML structure - for AJAX requests, we skip to here
?>

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>🎛️ Bulk Operations</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
                    <li><a href="admin_manage_users.php">Manage Users</a></li>
                    <li><a href="admin_bulk_operations.php">Bulk Operations</a></li>
                    <li><a href="admin_advanced_export.php">Advanced Export</a></li>
                    <li><a href="admin_cache_manager.php">Cache Manager</a></li>
                    <li><a href="admin_query_optimization.php">Optimization</a></li>
                    <li><a href="admin_audit_trail.php">Audit Trail</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">

            <!-- Alert Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ============== CSV IMPORT SECTION ============== -->
            <div class="section-card">
                <h2 class="section-title"><i class="bi bi-file-earmark-csv"></i> Import Users dari CSV</h2>

                <div class="alert alert-info"><i class="bi bi-info-circle"></i> <strong>Format CSV:</strong> name, email, nisn, password (opsional)</div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="csv-upload-area" id="csvUploadArea">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: #0d6efd;"></i>
                        <p class="mt-3">Drag & drop CSV file di sini atau klik untuk memilih</p>
                        <input type="file" name="csv_file" id="csvFileInput" accept=".csv" style="display: none;">
                    </div>
                    <small class="text-muted d-block mt-2">File max 5MB</small>
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-upload"></i> Upload & Import</button>
                </form>

                <!-- Import Result -->
                <?php if ($import_result): ?>
                    <div class="import-result">
                        <h5 class="mt-4">📊 Hasil Import:</h5>
                        <div class="result-item success">
                            <strong>✅ Berhasil:</strong> <?php echo $import_result['imported']; ?> user diimport
                        </div>
                        <?php if ($import_result['failed'] > 0): ?>
                            <div class="result-item error">
                                <strong>❌ Gagal:</strong> <?php echo $import_result['failed']; ?> user
                            </div>
                            <?php foreach ($import_result['failed_rows'] as $failedRow): ?>
                                <div class="result-item">
                                    <strong>Baris <?php echo $failedRow['row']; ?>:</strong> <?php echo $failedRow['error']; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============== BULK ACTIONS SECTION ============== -->
            <div class="section-card">
                <h2 class="section-title"><i class="bi bi-lightning"></i> Bulk Actions</h2>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="summary-box">
                            <h5>Total Users</h5>
                            <div class="number"><?php echo count($users); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <h5>Terverifikasi</h5>
                            <div class="number"><?php echo count(array_filter($users, fn($u) => $u['is_verified'])); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <h5>Banned</h5>
                            <div class="number"><?php echo count(array_filter($users, fn($u) => $u['is_banned'])); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <h5>Selected</h5>
                            <div class="number" id="selectedCount">0</div>
                        </div>
                    </div>
                </div>

                <form method="POST" id="bulkActionsForm">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" class="user-checkbox" id="selectAllCheckbox"></th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>NISN</th>
                                    <th>Jurusan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_users[]" value="<?php echo $user['id_pendaftar']; ?>" class="user-checkbox user-select">
                                        </td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><small><?php echo htmlspecialchars($user['email']); ?></small></td>
                                        <td><?php echo htmlspecialchars($user['nisn'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['jenis_jurusan'] ?? 'Belum dipilih'); ?></td>
                                        <td>
                                            <?php if ($user['is_banned']): ?>
                                                <span class="badge badge-ban">Banned</span>
                                            <?php elseif ($user['is_verified']): ?>
                                                <span class="badge badge-verify">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" name="bulk_action" value="verify" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Verify
                        </button>
                        <button type="submit" name="bulk_action" value="ban" class="btn btn-danger">
                            <i class="bi bi-ban"></i> Ban
                        </button>
                        <button type="submit" name="bulk_action" value="unban" class="btn btn-warning">
                            <i class="bi bi-check-lg"></i> Unban
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="bi bi-envelope"></i> Send Email
                        </button>
                        <button type="submit" name="bulk_action" value="delete" class="btn btn-danger" onclick="return confirm('Hapus user yang dipilih? Ini tidak dapat dibatalkan!')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============== CSV TEMPLATE DOWNLOAD ============== -->
            <div class="section-card">
                <h5><i class="bi bi-download"></i> Download CSV Template</h5>
                <p class="text-muted">Download template CSV untuk referensi format import</p>
                <a href="javascript:void(0)" class="btn btn-outline-primary" onclick="downloadCSVTemplate()">
                    <i class="bi bi-file-earmark-csv"></i> Download Template
                </a>
            </div>

        </div>
    </main>

    <!-- Email Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kirim Email Bulk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="email_subject" class="form-control" value="Notifikasi dari Panitia PPDB" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pesan Email</label>
                            <textarea name="email_body" class="form-control" rows="8" placeholder="Masukkan pesan email..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="bulk_action" value="send_email" class="btn btn-primary">
                            <i class="bi bi-send"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Select All Checkbox
        document.getElementById('selectAllCheckbox').addEventListener('change', function() {
            document.querySelectorAll('.user-select').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });

        // Update selected count on individual checkbox change
        document.querySelectorAll('.user-select').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const count = document.querySelectorAll('.user-select:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // CSV File Upload with Drag & Drop
        const uploadArea = document.getElementById('csvUploadArea');
        const fileInput = document.getElementById('csvFileInput');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
        });

        // Download CSV Template
        function downloadCSVTemplate() {
            const csv = 'name,email,nisn,password\n' +
                'John Doe,john@example.com,1234567890,\n' +
                'Jane Smith,jane@example.com,0987654321,\n';

            const blob = new Blob([csv], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bulk_import_template.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
</body>

</html>