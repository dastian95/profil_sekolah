<?php
require_once __DIR__ . '/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Delete Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
    $id_jenis_del = $_POST['id_jenis'];
    
    $stmt = $conn->prepare("SELECT nama_file FROM unggah_dokumen WHERE id_pendaftar = ? AND id_jenis = ?");
    $stmt->execute([$user_id, $id_jenis_del]);
    $file_to_delete = $stmt->fetchColumn();
    
    if ($file_to_delete) {
        if (file_exists('uploads/' . $file_to_delete)) {
            unlink('uploads/' . $file_to_delete);
        }
        $stmt = $conn->prepare("DELETE FROM unggah_dokumen WHERE id_pendaftar = ? AND id_jenis = ?");
        $stmt->execute([$user_id, $id_jenis_del]);
        $message = '<div class="alert alert-success alert-dismissible fade show">Document deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $id_jenis = $_POST['id_jenis'];
    $file = $_FILES['document'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($ext, $allowed)) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_name = $user_id . '_' . $id_jenis . '_' . time() . '.' . $ext;
            $dest = 'uploads/' . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Ensure pendaftar record exists (Foreign Key Constraint)
                $stmt_p = $conn->prepare("SELECT id_pendaftar FROM pendaftar WHERE id_pendaftar = ?");
                $stmt_p->execute([$user_id]);
                if (!$stmt_p->fetch()) {
                    // Fetch name and nisn from users table
                    $stmt_u = $conn->prepare("SELECT name, nisn FROM users WHERE id_pendaftar = ?");
                    $stmt_u->execute([$user_id]);
                    $user_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
                    $name = $user_data['name'] ?? '';
                    $nisn = $user_data['nisn'] ?? '';

                    $stmt_i = $conn->prepare("INSERT INTO pendaftar (id_pendaftar, status_pendaftaran, nama_lengkap, nisn) VALUES (?, 'Draft', ?, ?)");
                    $stmt_i->execute([$user_id, $name, $nisn]);
                }

                // Check existing
                $stmt = $conn->prepare("SELECT id_unggah FROM unggah_dokumen WHERE id_pendaftar = ? AND id_jenis = ?");
                $stmt->execute([$user_id, $id_jenis]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $conn->prepare("UPDATE unggah_dokumen SET nama_file = ?, is_verified = 0 WHERE id_unggah = ?");
                    $stmt->execute([$new_name, $existing['id_unggah']]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO unggah_dokumen (id_pendaftar, id_jenis, nama_file) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $id_jenis, $new_name]);
                }
                
                $message = '<div class="alert alert-success alert-dismissible fade show">Document uploaded successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show">Failed to move uploaded file.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show">Invalid file type. Only JPG, PNG, PDF allowed.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show">Upload error code: ' . $file['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch Document Types
$stmt = $conn->prepare("SELECT * FROM jenis_dokumen");
$stmt->execute();
$doc_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Document Descriptions
$doc_descriptions = [
    'Kartu Keluarga' => 'Unggah scan Kartu Keluarga (KK) asli yang masih berlaku. Pastikan tulisan terbaca jelas.',
    'Akta Kelahiran' => 'Unggah scan Akta Kelahiran asli. Pastikan nama dan tanggal lahir sesuai.',
    'Ijazah/SKL' => 'Unggah scan Ijazah atau Surat Keterangan Lulus (SKL) dari sekolah asal.',
    'Pas Foto' => 'Unggah pas foto formal terbaru ukuran 3x4 dengan latar belakang merah atau biru.',
    'Kartu Siswa' => 'Unggah scan Kartu Pelajar / Kartu Identitas Siswa dari sekolah asal.',
    'Profil Belajar Siswa (PBS)' => 'Unggah dokumen Profil Belajar Siswa (PBS) jika dipersyaratkan.'
];

// Fetch User Uploads
$stmt = $conn->prepare("SELECT * FROM unggah_dokumen WHERE id_pendaftar = ?");
$stmt->execute([$user_id]);
$uploads_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$uploads = [];
foreach($uploads_raw as $u) {
    $uploads[$u['id_jenis']] = $u;
}

// Detect if request is from AJAX navigation
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Only output full HTML structure for direct page loads
if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Application - SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="user-dashboard">
    <?php include 'sidebar.php'; ?>
<?php
} // End of full HTML structure - for AJAX requests, we skip to here
?>

    <div class="content">
        <div class="container-fluid">
            <button class="btn btn-primary d-md-none mb-3" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Application</li>
                </ol>
            </nav>
            <h2 class="mb-4 text-dark">My Application Documents</h2>
            <?php echo $message; ?>
            
            <div class="row g-4">
                <?php foreach ($doc_types as $doc): ?>
                    <?php 
                    $uploaded = $uploads[$doc['id_jenis']] ?? null; 
                    $status = $uploaded ? ($uploaded['is_verified'] ? 'Verified' : 'Pending Verification') : 'Not Uploaded'; 
                    $statusClass = $uploaded ? ($uploaded['is_verified'] ? 'bg-success' : 'bg-warning text-dark') : 'bg-secondary'; 
                    $is_optional = ($doc['nama_dokumen'] === 'Kartu Siswa');
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2"><h5 class="card-title fw-bold"><?php echo htmlspecialchars($doc['nama_dokumen']); ?><?php if($is_optional) echo ' <span class="text-muted small fw-normal">(Opsional)</span>'; ?></h5><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></div>
                                <p class="card-text small text-muted mb-3">
                                    <?php echo htmlspecialchars($doc_descriptions[$doc['nama_dokumen']] ?? 'Silakan unggah dokumen ini dalam format JPG, PNG, atau PDF.'); ?>
                                </p>
                                <?php if ($uploaded && !empty($uploaded['catatan_admin'])): ?>
                                    <div class="alert alert-info py-2 px-3 mb-3 small">
                                        <strong>Catatan Admin:</strong><br> <?php echo nl2br(htmlspecialchars($uploaded['catatan_admin'])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($uploaded): ?>
                                    <div class="mb-3"><small class="text-muted">File: <?php echo htmlspecialchars($uploaded['nama_file']); ?></small></div>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="previewFile('uploads/<?php echo htmlspecialchars($uploaded['nama_file']); ?>', '<?php echo pathinfo($uploaded['nama_file'], PATHINFO_EXTENSION); ?>')">
                                            <i class="bi bi-eye me-1"></i> Preview File
                                        </button>
                                        <?php if (!$uploaded['is_verified']): ?>
                                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                            <input type="hidden" name="id_jenis" value="<?php echo $doc['id_jenis']; ?>">
                                            <button type="submit" name="delete_document" class="btn btn-outline-danger btn-sm w-100 mt-1"><i class="bi bi-trash me-1"></i> Delete</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$uploaded || !$uploaded['is_verified']): ?><hr><form action="" method="POST" enctype="multipart/form-data"><input type="hidden" name="id_jenis" value="<?php echo $doc['id_jenis']; ?>"><div class="mb-2"><label class="form-label small text-muted"><?php echo $uploaded ? 'Re-upload Document' : 'Upload Document'; ?></label><input type="file" class="form-control form-control-sm" name="document" <?php echo $is_optional ? '' : 'required'; ?> accept=".jpg,.jpeg,.png,.pdf"></div><div class="d-grid"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload</button></div></form><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Page Scripts moved inside content for AJAX -->
        <script>
            function previewFile(url, ext) {
                const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                const frame = document.getElementById('previewFrame');
                const img = document.getElementById('previewImage');
                
                frame.style.display = 'none';
                img.style.display = 'none';
                frame.src = '';
                img.src = '';

                if (['jpg', 'jpeg', 'png', 'webp'].includes(ext.toLowerCase())) {
                    img.src = url;
                    img.style.display = 'block';
                } else if (ext.toLowerCase() === 'pdf') {
                    frame.src = url;
                    frame.style.display = 'block';
                } else {
                    window.open(url, '_blank');
                    return;
                }
                modal.show();
            }
        </script>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width: 90%;">
            <div class="modal-content" style="height: 90vh;">
                <div class="modal-header">
                    <h5 class="modal-title">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-light d-flex justify-content-center align-items-center">
                    <iframe id="previewFrame" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                    <img id="previewImage" src="" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Global Dashboard Script -->
    <script src="assets/js/dashboard.js"></script>
    </div> <!-- End of .content div (for AJAX requests) -->
<?php
if (!$isAjaxRequest) {
?>
</body>
</html>
<?php
} // End of conditional HTML closing
?>