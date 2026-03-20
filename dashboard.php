<?php
require_once __DIR__ . '/../src/conn.php';

// Check for "Remember Me" cookie if session is not set
require_once __DIR__ . '/../src/check_remember_me.php';

// Check if user is logged in and is a user
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Handle Quick Photo Upload (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['quick_foto'])) {
    header('Content-Type: application/json');
    try {
        if ($_FILES['quick_foto']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload gagal. Kode error: ' . $_FILES['quick_foto']['error']);
        }
        
        // Validasi ukuran (Max 2MB)
        if ($_FILES['quick_foto']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar. Maksimal 2MB.');
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['quick_foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            throw new Exception('Format file tidak valid. Hanya JPG, PNG, WEBP.');
        }

        // Cek apakah data_peserta ada (karena tabel memiliki constraint NOT NULL pada kolom lain)
        $stmt = $conn->prepare("SELECT id_pendaftar, foto FROM data_peserta WHERE id_pendaftar = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Silakan lengkapi data profil Anda terlebih dahulu di menu Profile sebelum mengganti foto.');
        }

        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $new_name = 'foto_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['quick_foto']['tmp_name'], 'uploads/' . $new_name)) {
            // Hapus foto lama jika ada
            if (!empty($existing['foto']) && file_exists('uploads/' . $existing['foto'])) {
                unlink('uploads/' . $existing['foto']);
            }
            $stmt = $conn->prepare("UPDATE data_peserta SET foto = ? WHERE id_pendaftar = ?");
            $stmt->execute([$new_name, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Foto berhasil diperbarui!', 'new_src' => 'uploads/' . $new_name]);
        } else {
            throw new Exception('Gagal menyimpan file.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT 
            u.name, 
            u.email,
            u.is_verified, 
            p.status_pendaftaran,
            dp.jurusan,
            dp.foto
        FROM users u
        LEFT JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        WHERE u.id_pendaftar = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user not found, destroy session and redirect to login
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Fetch document stats for progress bar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jenis_dokumen");
    $stmt->execute();
    $total_docs_count = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT id_jenis) FROM unggah_dokumen WHERE id_pendaftar = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $uploaded_docs_count = $stmt->fetchColumn();

    $progress = $total_docs_count > 0 ? round(($uploaded_docs_count / $total_docs_count) * 100) : 0;

    // Fetch notifications
    $notifications = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* Table might not exist */ }

    // Fetch Announcements
    $announcements = [];
    try {
        $stmt = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC");
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* Table might not exist */ }

    // Logic Pengumuman Kelulusan
    // Ganti tanggal di bawah ini sesuai jadwal pengumuman
    $announcement_date = $_ENV['ANNOUNCEMENT_DATE'] ?? '2026-06-05 10:00:00'; 
    $show_announcement = time() >= strtotime($announcement_date);
    $graduation_result = null;

    if ($show_announcement) {
        $stmt = $conn->prepare("SELECT hasil FROM hasil_daftar WHERE id_pendaftar = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $graduation_result = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SMK Lab Jakarta</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
</head>
<body class="user-dashboard">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <button class="btn btn-primary d-md-none mb-3" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
            <h2 class="mb-4 text-dark">Dashboard Overview</h2>

            <!-- Pengumuman Kelulusan Section -->
            <?php if ($show_announcement): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Pengumuman Kelulusan</h5>
                    </div>
                    <div class="card-body text-center py-5">
                        <?php if ($graduation_result): ?>
                            <?php if ($graduation_result['hasil'] == 'diterima'): ?>
                                <div class="mb-3"><i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i></div>
                                <h2 class="fw-bold text-success mb-3">SELAMAT!</h2>
                                <p class="lead">Anda dinyatakan <strong>DITERIMA</strong> sebagai siswa baru SMK Laboratorium Jakarta.</p>
                                <p class="text-muted">Silakan lakukan daftar ulang pada tanggal yang telah ditentukan.</p>
                            <?php else: ?>
                                <div class="mb-3"><i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i></div>
                                <h2 class="fw-bold text-danger mb-3">MOHON MAAF</h2>
                                <p class="lead">Anda dinyatakan <strong>TIDAK DITERIMA</strong>.</p>
                                <p class="text-muted">Jangan patah semangat, tetap terus belajar dan mencoba di kesempatan lain.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning d-inline-block">
                                <i class="bi bi-exclamation-circle me-2"></i>Data hasil seleksi untuk akun Anda belum tersedia. Silakan hubungi panitia.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Welcome & Profile Card -->
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white overflow-hidden">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="position-relative me-4">
                        <?php 
                        $photoUrl = !empty($user['foto']) && file_exists('uploads/' . $user['foto']) 
                            ? 'uploads/' . $user['foto'] 
                            : '';
                        ?>
                        <?php if($photoUrl): ?>
                            <img src="<?php echo htmlspecialchars($photoUrl); ?>" id="dashboardProfileImg" class="rounded-circle border border-3 border-white shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <div id="dashboardProfileIcon" class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center border border-3 border-white shadow-sm" style="width: 80px; height: 80px;">
                                <i class="bi bi-person-fill" style="font-size: 40px;"></i>
                            </div>
                            <img src="" id="dashboardProfileImg" class="rounded-circle border border-3 border-white shadow-sm d-none" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php endif; ?>
                        <button class="btn btn-light btn-sm position-absolute bottom-0 end-0 rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" data-bs-toggle="modal" data-bs-target="#quickPhotoModal" title="Ganti Foto">
                            <i class="bi bi-camera-fill text-primary"></i>
                        </button>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h3>
                        <p class="mb-0 opacity-75"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <!-- Circular Progress Bar -->
                    <div class="ms-3 d-none d-md-block text-center">
                        <div class="position-relative d-flex justify-content-center align-items-center" style="width: 70px; height: 70px; border-radius: 50%; background: conic-gradient(#fff <?php echo $progress * 3.6; ?>deg, rgba(255,255,255,0.2) 0deg);">
                            <div class="bg-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;">
                                <span class="fw-bold text-white"><?php echo $progress; ?>%</span>
                            </div>
                        </div>
                        <small class="d-block mt-1 text-white opacity-75" style="font-size: 0.7rem;">Profile</small>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- Status Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-subtitle text-muted">Registration Status</h6>
                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                </div>
                            </div>
                            <h3 class="card-title mb-0">
                                <?php echo htmlspecialchars($user['status_pendaftaran'] ?? 'Not Started'); ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Verified Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-subtitle text-muted">Account Status</h6>
                                <div class="bg-success bg-opacity-10 p-2 rounded">
                                    <i class="bi bi-shield-check text-success"></i>
                                </div>
                            </div>
                            <h3 class="card-title mb-0">
                                <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Jurusan Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-subtitle text-muted">Selected Major</h6>
                                <div class="bg-info bg-opacity-10 p-2 rounded">
                                    <i class="bi bi-mortarboard text-info"></i>
                                </div>
                            </div>
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($user['jurusan'] ?? '-'); ?>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Application Checklist Card -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Application Checklist</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold">Document Upload Progress</span>
                                    <span><?php echo $progress; ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="text-muted small mt-2">You have uploaded <?php echo $uploaded_docs_count; ?> out of <?php echo $total_docs_count; ?> required documents.</p>
                            </div>
                            
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="bi bi-person-check me-2 <?php echo $user['jurusan'] ? 'text-success' : 'text-secondary'; ?>"></i> Complete Profile Data</span>
                                    <?php if($user['jurusan']): ?>
                                        <span class="badge bg-success rounded-pill">Completed</span>
                                    <?php else: ?>
                                        <a href="profile.php" class="btn btn-sm btn-outline-primary">Complete Now</a>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="bi bi-file-earmark-arrow-up me-2 <?php echo $progress == 100 ? 'text-success' : 'text-secondary'; ?>"></i> Upload Documents</span>
                                    <?php if($progress == 100): ?>
                                        <span class="badge bg-success rounded-pill">Completed</span>
                                    <?php else: ?>
                                        <a href="application.php" class="btn btn-sm btn-outline-primary">Upload</a>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><i class="bi bi-shield-check me-2 <?php echo $user['is_verified'] ? 'text-success' : 'text-secondary'; ?>"></i> Account Verification</span>
                                    <?php if($user['is_verified']): ?>
                                        <span class="badge bg-success rounded-pill">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Announcements Card -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-bell me-2 text-danger"></i>Notifications</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="notificationList">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <li class="list-group-item bg-transparent">
                                            <small class="text-muted d-block"><?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></small>
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted text-center py-3">No new notifications</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-megaphone me-2 text-warning"></i>Announcements</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($announcements)): ?>
                                <?php foreach ($announcements as $ann): ?>
                                    <div class="alert alert-<?php echo htmlspecialchars($ann['type']); ?> mb-3">
                                        <h6 class="alert-heading fw-bold"><?php echo htmlspecialchars($ann['title']); ?></h6>
                                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                                        <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;"><?php echo date('d M Y', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small text-center mb-0">No active announcements.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        
        <!-- Page Specific Scripts (Moved inside content for AJAX reloading) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        <script>
            // Quick Photo Upload Logic
            const quickPhotoForm = document.getElementById('quickPhotoForm');
            const quickPhotoInput = document.getElementById('quickPhotoInput');
            const quickPhotoCropContainer = document.getElementById('quickPhotoCropContainer');
            const quickPhotoImage = document.getElementById('quickPhotoImage');
            const btnZoomIn = document.getElementById('btnZoomIn');
            const btnZoomOut = document.getElementById('btnZoomOut');
            const uploadProgress = document.getElementById('uploadProgress');
            let quickCropper = null;

            if (quickPhotoForm) {
                // Handle File Selection
                quickPhotoInput.addEventListener('change', function(e) {
                    const files = e.target.files;
                    if (files && files.length > 0) {
                        const file = files[0];
                        if (file.size > 2 * 1024 * 1024) {
                            alert('Ukuran file terlalu besar. Maksimal 2MB.');
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            quickPhotoImage.src = e.target.result;
                            quickPhotoCropContainer.classList.remove('d-none');
                            
                            if (quickCropper) {
                                quickCropper.destroy();
                            }
                            quickCropper = new Cropper(quickPhotoImage, {
                                aspectRatio: 3 / 4, // Kunci Rasio 3:4
                                viewMode: 1,
                                autoCropArea: 1,
                                dragMode: 'move', // User hanya bisa geser gambar, tidak bisa gambar ulang box
                                toggleDragModeOnDblclick: false // Matikan fitur ubah mode via double click
                            });
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Zoom Controls
                if(btnZoomIn) {
                    btnZoomIn.addEventListener('click', () => { if(quickCropper) quickCropper.zoom(0.1); });
                }
                if(btnZoomOut) {
                    btnZoomOut.addEventListener('click', () => { if(quickCropper) quickCropper.zoom(-0.1); });
                }

                // Handle Submit
                quickPhotoForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!quickCropper) {
                        alert('Silakan pilih foto terlebih dahulu.');
                        return;
                    }

                    const btn = this.querySelector('button[type="submit"]');
                    const msg = document.getElementById('quickPhotoMsg');
                    const originalText = btn.innerHTML;

                    btn.disabled = true;
                    btn.innerHTML = 'Memproses...';
                    msg.innerHTML = '';
                    if(uploadProgress) uploadProgress.classList.remove('d-none');

                    quickCropper.getCroppedCanvas({
                        width: 300,
                        height: 400
                    }).toBlob((blob) => {
                        const formData = new FormData();
                        formData.append('quick_foto', blob, 'avatar.jpg');

                        fetch('dashboard.php', { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    msg.innerHTML = `<div class="alert alert-success py-2 small">${data.message}</div>`;
                                    // Update image on dashboard
                                    const img = document.getElementById('dashboardProfileImg');
                                    const icon = document.getElementById('dashboardProfileIcon');
                                    if (img) {
                                        img.src = data.new_src + '?t=' + new Date().getTime();
                                        img.classList.remove('d-none');
                                    }
                                    if (icon) icon.classList.add('d-none');
                                    
                                    setTimeout(() => {
                                        const modal = bootstrap.Modal.getInstance(document.getElementById('quickPhotoModal'));
                                        if(modal) modal.hide();
                                        msg.innerHTML = '';
                                        quickPhotoForm.reset();
                                        quickPhotoCropContainer.classList.add('d-none');
                                        if(quickCropper) {
                                            quickCropper.destroy();
                                            quickCropper = null;
                                        }
                                    }, 1500);
                                } else {
                                    msg.innerHTML = `<div class="alert alert-danger py-2 small">${data.message}</div>`;
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                msg.innerHTML = `<div class="alert alert-danger py-2 small">Terjadi kesalahan sistem.</div>`;
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                                if(uploadProgress) uploadProgress.classList.add('d-none');
                            });
                    }, 'image/jpeg', 0.9);
                });
            }

            // Real-time Notifications Polling
            function fetchNotifications() {
                fetch('fetch_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const list = document.getElementById('notificationList');
                            let html = '';
                            
                            if (data.notifications.length > 0) {
                                data.notifications.forEach(notif => {
                                    html += `
                                        <li class="list-group-item bg-transparent">
                                            <small class="text-muted d-block">${notif.formatted_date}</small>
                                            ${notif.message}
                                        </li>`;
                                });
                            } else {
                                html = '<li class="list-group-item text-muted text-center py-3">No new notifications</li>';
                            }
                            list.innerHTML = html;
                        }
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }

            // Poll every 5 seconds
            // Clear previous interval if exists to prevent duplicates on reload
            if (window.notifInterval) clearInterval(window.notifInterval);
            window.notifInterval = setInterval(fetchNotifications, 5000);

        </script>

        <footer class="dashboard-footer">
            Copyright &copy; <?php echo date('Y'); ?> <strong>SMK Laboratorium Jakarta</strong>. All Rights Reserved.
        </footer>
    </div>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Quick Photo Modal -->
    <div class="modal fade" id="quickPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Ganti & Crop Foto Profil</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickPhotoForm">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Pilih Foto (Max 2MB)</label>
                            <input type="file" class="form-control form-control-sm" id="quickPhotoInput" accept=".jpg,.jpeg,.png,.webp" required>
                        </div>
                        <div id="quickPhotoCropContainer" class="mb-3 d-none">
                            <div style="max-height: 400px; overflow: hidden;">
                                <img id="quickPhotoImage" src="" style="max-width: 100%;">
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-2">
                                <button type="button" class="btn btn-light btn-sm border" id="btnZoomIn" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                                <button type="button" class="btn btn-light btn-sm border" id="btnZoomOut" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                            </div>
                        </div>
                        <div id="uploadProgress" class="mb-3 d-none">
                            <div class="d-flex justify-content-between small text-muted mb-1"><span>Mengunggah...</span><span>Mohon tunggu</span></div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Upload & Simpan</button>
                    </form>
                    <div id="quickPhotoMsg" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

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
</body>
</html>