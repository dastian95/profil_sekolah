<?php
require_once __DIR__ . '/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's major and details
$stmt = $conn->prepare("SELECT jurusan, nama, nisn, foto FROM data_peserta WHERE id_pendaftar = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$jurusan = $user_data['jurusan'] ?? null;

// Fetch schedule from DB
$my_schedule = null;
if ($jurusan) {
    $stmt = $conn->prepare("SELECT * FROM jadwal_ujian WHERE jurusan = ?");
    $stmt->execute([$jurusan]);
    $db_schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_schedule) {
        // Format tanggal Indonesia
        $fmt_date = date('d F Y', strtotime($db_schedule['tanggal']));
        $my_schedule = [
            'date' => $fmt_date,
            'time' => $db_schedule['waktu'],
            'location' => $db_schedule['lokasi'],
            'subjects' => array_map('trim', explode(',', $db_schedule['materi']))
        ];
    }
}
?>

<?php
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
        <title>Jadwal Ujian - SMK Lab Jakarta</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="assets/css/main.css" rel="stylesheet">
        <link href="assets/css/dashboard.css" rel="stylesheet">
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }

                .content,
                .content * {
                    visibility: visible;
                }

                .content {
                    position: absolute;
                    left: 0;
                    top: 0;
                    margin: 0;
                    padding: 0;
                    width: 100%;
                }

                .sidebar,
                .btn-print,
                .breadcrumb,
                #sidebarToggle {
                    display: none !important;
                }

                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
            }
        </style>
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
                    <li class="breadcrumb-item active" aria-current="page">Jadwal Ujian</li>
                </ol>
            </nav>
            <h2 class="mb-4 text-dark">Jadwal Ujian Masuk</h2>

            <?php if (!$jurusan): ?>
                <div class="alert alert-warning shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Anda belum memilih jurusan. Silakan lengkapi data profil Anda di menu <a href="profile.php" class="alert-link">Profile</a> untuk melihat jadwal ujian.
                </div>
            <?php elseif ($my_schedule): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Kartu Jadwal Ujian</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Data Peserta</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="140" class="text-muted">Nama Lengkap</td>
                                        <td class="fw-bold">: <?php echo htmlspecialchars($user_data['nama']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">NISN</td>
                                        <td class="fw-bold">: <?php echo htmlspecialchars($user_data['nisn']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Jurusan Pilihan</td>
                                        <td class="fw-bold">: <?php echo htmlspecialchars($jurusan); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Detail Pelaksanaan</h5>
                                <div class="p-3 bg-light rounded border">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-white p-2 rounded shadow-sm me-3 text-primary">
                                            <i class="bi bi-calendar-date fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Hari, Tanggal</small>
                                            <span class="fw-bold"><?php echo $my_schedule['date']; ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-white p-2 rounded shadow-sm me-3 text-primary">
                                            <i class="bi bi-clock fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Waktu</small>
                                            <span class="fw-bold"><?php echo $my_schedule['time']; ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-white p-2 rounded shadow-sm me-3 text-primary">
                                            <i class="bi bi-geo-alt fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Lokasi</small>
                                            <span class="fw-bold"><?php echo $my_schedule['location']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="fw-bold mb-3">Materi Ujian</h5>
                        <div class="row g-3">
                            <?php foreach ($my_schedule['subjects'] as $subject): ?>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center p-3 border rounded h-100 bg-white shadow-sm">
                                        <i class="bi bi-book text-success me-3 fs-4"></i>
                                        <span class="fw-medium"><?php echo $subject; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-info mt-4 mb-0 d-flex align-items-start">
                            <i class="bi bi-info-circle-fill me-3 fs-4 mt-1"></i>
                            <div>
                                <strong class="d-block mb-1">Catatan Penting:</strong>
                                <ul class="mb-0 ps-3 small">
                                    <li>Harap hadir 30 menit sebelum ujian dimulai untuk registrasi ulang.</li>
                                    <li>Wajib membawa <strong>Kartu Peserta Ujian</strong> (Cetak di menu Profile) dan Kartu Identitas (Kartu Pelajar/KTP).</li>
                                    <li>Peserta wajib mengenakan seragam sekolah asal yang rapi dan sopan.</li>
                                    <li>Membawa alat tulis lengkap (Pensil 2B, Penghapus, Pulpen Hitam).</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 text-end">
                        <button type="button" id="btnCetakKartu" class="btn btn-success me-2"><i class="bi bi-person-badge me-2"></i>Cetak Kartu Ujian (PDF)</button>
                        <button onclick="window.print()" class="btn btn-outline-primary btn-print"><i class="bi bi-printer me-2"></i>Print Halaman</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    Data jadwal untuk jurusan <strong><?php echo htmlspecialchars($jurusan); ?></strong> belum tersedia saat ini. Silakan hubungi panitia PPDB.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden Card Template for PDF Generation -->
    <div id="examCardTemplate" style="width: 500px; background: #fff; padding: 20px; border: 1px solid #000; position: absolute; left: -9999px; top: 0; font-family: 'Arial', sans-serif;">
        <div style="border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center;">
            <img src="assets/img/smk.png" style="height: 50px; margin-right: 15px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase;">SMK Laboratorium Jakarta</h3>
                <p style="margin: 2px 0 0; font-size: 12px;">KARTU PESERTA UJIAN MASUK 2026/2027</p>
            </div>
        </div>

        <div style="display: flex;">
            <div style="width: 120px; margin-right: 20px;">
                <?php if (!empty($user_data['foto']) && file_exists('uploads/' . $user_data['foto'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($user_data['foto']); ?>" style="width: 100%; border: 1px solid #ddd; padding: 2px;">
                <?php else: ?>
                    <div style="width: 100%; height: 150px; background: #eee; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 10px;">FOTO</div>
                <?php endif; ?>
                <div style="margin-top: 10px; text-align: center;">
                    <div id="qrcode"></div>
                </div>
            </div>
            <div style="flex: 1;">
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold; width: 100px;">No. Peserta</td>
                        <td>: <?php echo 'U-' . $user_data['nisn']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold;">Nama</td>
                        <td>: <?php echo htmlspecialchars($user_data['nama']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold;">NISN</td>
                        <td>: <?php echo htmlspecialchars($user_data['nisn']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold;">Jurusan</td>
                        <td>: <?php echo htmlspecialchars($jurusan); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 15px; font-weight: bold; text-decoration: underline;">Jadwal Ujian</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;">Hari/Tgl</td>
                        <td>: <?php echo $my_schedule['date']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;">Waktu</td>
                        <td>: <?php echo $my_schedule['time']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;">Lokasi</td>
                        <td>: <?php echo $my_schedule['location']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px; font-size: 10px; text-align: center;">
            Kartu ini wajib dibawa saat pelaksanaan ujian. Scan QR Code untuk verifikasi data.
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Global Dashboard Script -->
    <script src="assets/js/dashboard.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate QR Code
            const qrContainer = document.getElementById('qrcode');
            if (qrContainer) {
                new QRCode(qrContainer, {
                    text: "<?php echo 'VERIFY-' . $user_data['nisn']; ?>",
                    width: 100,
                    height: 100
                });
            }

            // Handle Print PDF
            const btnCetak = document.getElementById('btnCetakKartu');
            if (btnCetak) {
                btnCetak.addEventListener('click', function() {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const element = document.getElementById('examCardTemplate');

                    // Show temporarily for capture
                    element.style.left = '0';
                    element.style.zIndex = '9999';

                    html2canvas(element, {
                        scale: 2
                    }).then(canvas => {
                        // Hide again
                        element.style.left = '-9999px';

                        const imgData = canvas.toDataURL('image/png');
                        const pdf = new jsPDF('l', 'mm', 'a5'); // Landscape A5
                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = pdf.internal.pageSize.getHeight();

                        pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth - 20, (canvas.height * (pdfWidth - 20) / canvas.width));
                        pdf.save('Kartu_Ujian_<?php echo $user_data['nisn']; ?>.pdf');
                    });
                });
            }
        });
    </script>
    </div> <!-- End of .content div (for AJAX requests) -->
    <?php
    if (!$isAjaxRequest) {
    ?>
    </body>

    </html>
<?php
    } // End of conditional HTML closing
?>