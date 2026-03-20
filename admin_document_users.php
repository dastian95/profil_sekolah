<?php
// Logic for Document Users Section

// Handle ZIP Download
if (isset($_GET['download_zip']) && isset($_GET['uid'])) {
    $uid = $_GET['uid'];
    
    // Get User Info
    $stmt = $conn->prepare("SELECT name, nisn FROM users WHERE id_pendaftar = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    
    if ($u) {
        $zip = new ZipArchive();
        $zipName = "Dokumen_" . preg_replace('/[^a-zA-Z0-9]/', '_', $u['name']) . "_" . $u['nisn'] . ".zip";
        $tempFile = sys_get_temp_dir() . '/' . $zipName;
        
        if ($zip->open($tempFile, ZipArchive::CREATE) === TRUE) {
            $stmt = $conn->prepare("SELECT ud.nama_file, jd.nama_dokumen FROM unggah_dokumen ud JOIN jenis_dokumen jd ON ud.id_jenis = jd.id_jenis WHERE ud.id_pendaftar = ?");
            $stmt->execute([$uid]);
            $files = $stmt->fetchAll();
            
            foreach ($files as $f) {
                $filePath = 'uploads/' . $f['nama_file'];
                if (file_exists($filePath)) {
                    $ext = pathinfo($f['nama_file'], PATHINFO_EXTENSION);
                    $zip->addFile($filePath, $f['nama_dokumen'] . '.' . $ext);
                }
            }
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zipName);
            header('Content-Length: ' . filesize($tempFile));
            readfile($tempFile);
            unlink($tempFile);
            exit;
        }
    }
}

// Handle Verification Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['user_id'];

    // Helper Function: Log Activity
    function logActivity($conn, $admin_id, $action, $details) {
        try {
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
            $stmt->execute([$admin_id, $action, $details]);
        } catch (Exception $e) { /* Ignore log error */ }
    }

    // Helper Function: Process Verification
    function processVerification($conn, $id_unggah, $status, $note, $admin_id) {
        $stmt = $conn->prepare("UPDATE unggah_dokumen SET is_verified = ?, catatan_admin = ? WHERE id_unggah = ?");
        $stmt->execute([$status, $note, $id_unggah]);

        // Notification & Log
        $stmt = $conn->prepare("SELECT ud.id_pendaftar, jd.nama_dokumen, u.name as student_name FROM unggah_dokumen ud JOIN jenis_dokumen jd ON ud.id_jenis = jd.id_jenis JOIN users u ON ud.id_pendaftar = u.id_pendaftar WHERE ud.id_unggah = ?");
        $stmt->execute([$id_unggah]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc) {
            $actionText = $status ? "diverifikasi" : "ditolak";
            $msg = "Dokumen " . $doc['nama_dokumen'] . " " . $actionText . ".";
            if (!empty($note)) $msg .= " Catatan: " . $note;
            
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->execute([$doc['id_pendaftar'], $msg]);

            // Log to Database
            $logAction = $status ? "VERIFY_DOC" : "REJECT_DOC";
            $logDetails = "Admin memverifikasi dokumen '{$doc['nama_dokumen']}' milik siswa '{$doc['student_name']}' (ID: {$doc['id_pendaftar']}). Status: $actionText.";
            logActivity($conn, $admin_id, $logAction, $logDetails);
        }
    }

    // 1. Handle Bulk Verify
    if (isset($_POST['bulk_verify']) && !empty($_POST['selected_docs'])) {
        foreach ($_POST['selected_docs'] as $id_unggah) {
            processVerification($conn, $id_unggah, 1, '', $admin_id);
        }
    }
    // 2. Handle Single Verify/Reject
    elseif (isset($_POST['verify_single']) || isset($_POST['reject_single'])) {
        $id_unggah = $_POST['verify_single'] ?? $_POST['reject_single'];
        $is_verify = isset($_POST['verify_single']);
        $status = $is_verify ? 1 : 0;
        $note = $_POST['admin_note'][$id_unggah] ?? '';
        
        processVerification($conn, $id_unggah, $status, $note, $admin_id);
    }
    // 3. Handle Admin Upload
    elseif (isset($_POST['upload_single'])) {
        // Because we are in a loop/array structure now
        $u_id = $_POST['id_pendaftar'];
        $j_id = $_POST['id_jenis'];
        
        // Construct the file key from the array
        // HTML: name="admin_doc_file[id_jenis]"
        // PHP $_FILES: ['admin_doc_file']['name'][id_jenis]
        
        $file_error = $_FILES['admin_doc_file']['error'][$j_id] ?? UPLOAD_ERR_NO_FILE;
        
        if ($file_error === UPLOAD_ERR_OK) {
            $file_name = $_FILES['admin_doc_file']['name'][$j_id];
            $file_tmp = $_FILES['admin_doc_file']['tmp_name'][$j_id];
            
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($ext, $allowed)) {
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                $new_name = $u_id . '_' . $j_id . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($file_tmp, 'uploads/' . $new_name)) {
                    // Cek existing
                    $stmt = $conn->prepare("SELECT id_unggah FROM unggah_dokumen WHERE id_pendaftar = ? AND id_jenis = ?");
                    $stmt->execute([$u_id, $j_id]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        $stmt = $conn->prepare("UPDATE unggah_dokumen SET nama_file = ?, is_verified = 1 WHERE id_unggah = ?");
                        $stmt->execute([$new_name, $existing['id_unggah']]);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO unggah_dokumen (id_pendaftar, id_jenis, nama_file, is_verified) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$u_id, $j_id, $new_name]);
                    }

                    // Ensure pendaftar record
                    $stmt_p = $conn->prepare("SELECT id_pendaftar FROM pendaftar WHERE id_pendaftar = ?");
                    $stmt_p->execute([$u_id]);
                    if (!$stmt_p->fetch()) {
                         $stmt_u = $conn->prepare("SELECT name, nisn FROM users WHERE id_pendaftar = ?");
                         $stmt_u->execute([$u_id]);
                         $udata = $stmt_u->fetch(PDO::FETCH_ASSOC);
                         $stmt_i = $conn->prepare("INSERT INTO pendaftar (id_pendaftar, status_pendaftaran, nama_lengkap, nisn) VALUES (?, 'Draft', ?, ?)");
                         $stmt_i->execute([$u_id, $udata['name'] ?? '', $udata['nisn'] ?? '']);
                    }

                    logActivity($conn, $admin_id, 'UPLOAD_DOC', "Admin mengunggah dokumen untuk User ID: $u_id, Jenis: $j_id");
                }
            }
        }
    }

    // Refresh to show changes
    echo "<script>window.location.href='?page=documents';</script>";
    exit;
}

try {
    // 1. Get total required documents count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jenis_dokumen");
    $stmt->execute();
    $total_doc_types = $stmt->fetchColumn();

    // 2. Update status_pendaftaran to 'Terkirim' if user has all documents
    // We use a subquery to count distinct document types uploaded by each user
    $update_sql = "UPDATE pendaftar p
    JOIN (
        SELECT id_pendaftar, COUNT(DISTINCT id_jenis) as uploaded_count
        FROM unggah_dokumen
        GROUP BY id_pendaftar
    ) u ON p.id_pendaftar = u.id_pendaftar
    SET p.status_pendaftaran = 'Terkirim'
    WHERE u.uploaded_count >= :total AND p.status_pendaftaran = 'Draft'";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->execute([':total' => $total_doc_types]);

    // 3. Fetch Data Peserta for cards
    $search = trim($_GET['search'] ?? '');
    $status_filter = $_GET['status'] ?? '';

    // Pagination Setup
    $limit = 9; // Jumlah siswa per halaman
    $page_num = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
    if ($page_num < 1) $page_num = 1;
    $offset = ($page_num - 1) * $limit;

    $base_query = "FROM data_peserta dp JOIN pendaftar p ON dp.id_pendaftar = p.id_pendaftar";
    $params = [];
    $conditions = [];
    
    if (!empty($search)) {
        $conditions[] = "(dp.nama LIKE ? OR dp.nisn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($status_filter)) {
        $conditions[] = "p.status_pendaftaran = ?";
        $params[] = $status_filter;
    }

    $where_sql = "";
    if (!empty($conditions)) {
        $where_sql = " WHERE " . implode(" AND ", $conditions);
    }

    // Count Total Records (Hitung total data untuk pagination)
    $count_stmt = $conn->prepare("SELECT COUNT(*) " . $base_query . $where_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch Data with Limit (Ambil data sesuai halaman)
    $sql = "SELECT dp.id_pendaftar, dp.nisn, dp.nama, dp.foto, p.status_pendaftaran " . $base_query . $where_sql . " LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Document Types and Uploads
    $stmt = $conn->prepare("SELECT * FROM jenis_dokumen");
    $stmt->execute();
    $jenis_dokumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM unggah_dokumen");
    $stmt->execute();
    $uploads_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $user_uploads = [];
    foreach($uploads_raw as $up) {
        $user_uploads[$up['id_pendaftar']][$up['id_jenis']] = $up;
    }
} catch (PDOException $e) {
    $error_docs = "Database error: " . $e->getMessage();
}
?>

<!-- Document Users Card -->
<div class="card shadow-sm" id="document-users">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-file-earmark-text-fill me-2 text-warning"></i>Document Users</h5>
    </div>
    <div class="card-body">
        <!-- Search Bar -->
        <form method="GET" class="mb-4">
            <input type="hidden" name="page" value="documents">
            <div class="input-group">
                <select class="form-select" name="status" style="max-width: 150px;">
                    <option value="">All Status</option>
                    <option value="Terkirim" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Terkirim') ? 'selected' : ''; ?>>Terkirim</option>
                    <option value="Draft" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                </select>
                <input type="text" class="form-control" name="search" placeholder="Search by Name or NISN..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                <?php if(!empty($search) || !empty($status_filter)): ?>
                    <a href="?page=documents" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (isset($error_docs)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_docs); ?></div>
        <?php endif; ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if (!empty($participants)): ?>
                <?php foreach ($participants as $participant): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'" data-bs-toggle="modal" data-bs-target="#docModal<?php echo $participant['id_pendaftar']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($participant['foto']); ?>" class="card-img-top" alt="Foto Peserta" style="height: 200px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($participant['nama']); ?></h5>
                                <p class="card-text text-muted mb-2">NISN: <?php echo htmlspecialchars($participant['nisn']); ?></p>
                                <span class="badge <?php echo $participant['status_pendaftaran'] == 'Terkirim' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($participant['status_pendaftaran']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="docModal<?php echo $participant['id_pendaftar']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Documents: <?php echo htmlspecialchars($participant['nama']); ?></h5>
                                    <a href="?page=documents&download_zip=1&uid=<?php echo $participant['id_pendaftar']; ?>" class="btn btn-success btn-sm ms-auto"><i class="bi bi-file-zip"></i> Download All (ZIP)</a>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <!-- Wrap Modal Body in ONE Form for Bulk Actions -->
                                <form method="POST" enctype="multipart/form-data">
                                <div class="modal-body bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 rounded shadow-sm">
                                        <div class="form-check ms-2">
                                            <input class="form-check-input" type="checkbox" id="selectAll<?php echo $participant['id_pendaftar']; ?>" onchange="toggleAllDocs(this, '<?php echo $participant['id_pendaftar']; ?>')">
                                            <label class="form-check-label fw-bold" for="selectAll<?php echo $participant['id_pendaftar']; ?>">Select All</label>
                                        </div>
                                        <button type="submit" name="bulk_verify" class="btn btn-success btn-sm"><i class="bi bi-check-all"></i> Verify Selected</button>
                                    </div>

                                    <div class="row g-4">
                                        <?php foreach ($jenis_dokumen as $jd): ?>
                                            <?php $uploaded = $user_uploads[$participant['id_pendaftar']][$jd['id_jenis']] ?? null; ?>
                                            <div class="col-md-4 col-sm-6">
                                                <div class="card h-100 border-0 shadow-sm position-relative">
                                                    <?php if ($uploaded): ?>
                                                        <div class="position-absolute top-0 start-0 m-2">
                                                            <input type="checkbox" class="form-check-input doc-checkbox-<?php echo $participant['id_pendaftar']; ?>" name="selected_docs[]" value="<?php echo $uploaded['id_unggah']; ?>">
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="card-body text-center">
                                                        <div class="mb-3">
                                                            <i class="bi bi-file-earmark-text fs-1 text-secondary"></i>
                                                        </div>
                                                        <h6 class="card-title fw-bold"><?php echo htmlspecialchars($jd['nama_dokumen']); ?></h6>
                                                        <div class="mb-3">
                                                            <?php if ($uploaded): ?>
                                                                <?php echo $uploaded['is_verified'] ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-warning text-dark">Pending</span>'; ?>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Not uploaded</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($uploaded): ?>
                                                            <a href="uploads/<?php echo htmlspecialchars($uploaded['nama_file']); ?>" target="_blank" class="btn btn-primary btn-sm w-100"><i class="bi bi-eye"></i> View</a>
                                                            <div class="mt-2 d-flex gap-2">
                                                                <div class="w-100">
                                                                    <textarea name="admin_note[<?php echo $uploaded['id_unggah']; ?>]" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan..."><?php echo htmlspecialchars($uploaded['catatan_admin'] ?? ''); ?></textarea>
                                                                    <?php if (!$uploaded['is_verified']): ?>
                                                                        <button type="submit" name="verify_single" value="<?php echo $uploaded['id_unggah']; ?>" class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg"></i> Verify</button>
                                                                    <?php else: ?>
                                                                        <button type="submit" name="reject_single" value="<?php echo $uploaded['id_unggah']; ?>" class="btn btn-warning btn-sm w-100"><i class="bi bi-x-lg"></i> Reject</button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <!-- Admin Replace Upload -->
                                                            <button class="btn btn-link btn-sm text-decoration-none mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#admUp<?php echo $participant['id_pendaftar'].$jd['id_jenis']; ?>">Ganti File</button>
                                                        <?php else: ?>
                                                            <button class="btn btn-outline-primary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#admUp<?php echo $participant['id_pendaftar'].$jd['id_jenis']; ?>"><i class="bi bi-upload"></i> Upload</button>
                                                        <?php endif; ?>
                                                        
                                                        <div class="collapse mt-2" id="admUp<?php echo $participant['id_pendaftar'].$jd['id_jenis']; ?>">
                                                            <div class="border p-2 rounded bg-white">
                                                                <input type="hidden" name="id_pendaftar" value="<?php echo $participant['id_pendaftar']; ?>">
                                                                <input type="hidden" name="id_jenis" value="<?php echo $jd['id_jenis']; ?>">
                                                                <label class="form-label small text-muted text-start w-100">Upload File (Admin)</label>
                                                                <!-- Array name for file input -->
                                                                <input type="file" name="admin_doc_file[<?php echo $jd['id_jenis']; ?>]" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.pdf">
                                                                <button type="submit" name="upload_single" class="btn btn-primary btn-sm w-100">Simpan & Verifikasi</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No participants found.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page_num <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=documents&p=<?php echo $page_num - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page_num == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=documents&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=documents&p=<?php echo $page_num + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <script>
    function toggleAllDocs(source, uid) {
        const checkboxes = document.querySelectorAll('.doc-checkbox-' + uid);
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
    </script>
</div>