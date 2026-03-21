// Fungsi untuk menginisialisasi semua event listener yang mungkin perlu di-rebind setelah AJAX
function initializePageSpecificScripts() {
    // --- Tooltip Initialization ---
    // Hancurkan tooltip yang ada untuk menghindari duplikasi
    const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    existingTooltips.forEach(el => {
        const tooltip = bootstrap.Tooltip.getInstance(el);
        if (tooltip) {
            tooltip.dispose();
        }
    });
    // Inisialisasi tooltip baru
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover' // Mencegah tooltip stuck saat diklik
        });
    });

    // --- Mobile Sidebar Toggle (Tombol di dalam .content) ---
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.onclick = null; // Hapus listener lama untuk menghindari duplikasi
        toggleBtn.onclick = function() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        };
    }
}

// --- Event Listener Utama yang dijalankan sekali saat halaman pertama kali dimuat ---
document.addEventListener('DOMContentLoaded', () => {
    const contentDiv = document.querySelector('.content');
    const sidebarContainer = document.querySelector('.sidebar');
    const body = document.body;

    // --- AJAX Navigation (menggunakan Event Delegation) ---
    if (sidebarContainer) {
        sidebarContainer.addEventListener('click', function(e) {
            const link = e.target.closest('a.nav-link');
            if (!link) return; // Bukan klik pada link

            const url = link.getAttribute('href');
            // Abaikan link non-navigasi (seperti dark mode, dropdown, dll)
            if (!url || url === '#' || link.hasAttribute('data-bs-toggle') || link.id === 'darkModeToggle') {
                return;
            }

            e.preventDefault();

            // Sembunyikan tooltip saat diklik agar tidak stuck
            const tooltip = bootstrap.Tooltip.getInstance(link);
            if (tooltip) tooltip.hide();

            // Hapus fokus dari tombol
            link.blur();

            // Update Active State
            document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Mulai Animasi Fade Out
            contentDiv.classList.add('fade-out');

            // Tunggu transisi CSS selesai (300ms) sebelum fetch konten baru
            setTimeout(() => {
                fetch(url)
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.querySelector('.content');

                        if (newContent) {
                            contentDiv.innerHTML = newContent.innerHTML;

                            // Jalankan script dari konten baru
                            Array.from(newContent.querySelectorAll('script')).forEach(oldScript => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                document.body.appendChild(newScript).parentNode.removeChild(newScript);
                            });

                            // Update URL di browser
                            window.history.pushState({}, '', url);
                            // Inisialisasi ulang script untuk konten yang baru dimuat
                            initializePageSpecificScripts();
                            
                            // Scroll ke atas
                            window.scrollTo(0, 0);
                        } else {
                            contentDiv.innerHTML = '<div class="alert alert-danger">Gagal memuat konten. Silakan coba lagi.</div>';
                        }
                    })
                    .catch(err => {
                        console.error('Fetch Error:', err);
                        contentDiv.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat halaman.</div>';
                    })
                    .finally(() => {
                        // Kembalikan Opacity (Fade In)
                        contentDiv.classList.remove('fade-out');
                    });
            }, 300); // Waktu delay sesuai dengan CSS transition (0.3s)
        });
    }

    // --- Dark Mode Logic ---
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        // Cek localStorage saat halaman dimuat
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Light Mode';
        }

        darkModeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Light Mode';
            } else {
                localStorage.setItem('darkMode', 'disabled');
                darkModeToggle.innerHTML = '<i class="bi bi-moon-stars me-2"></i> Dark Mode';
            }
        });
    }

    // --- Desktop Sidebar Collapse ---
    const desktopToggle = document.getElementById('desktopToggle');
    if (desktopToggle) {
        // Muat preferensi dari localStorage
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
            desktopToggle.innerHTML = '<i class="bi bi-chevron-right"></i>';
        }

        desktopToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
            desktopToggle.innerHTML = isCollapsed ? '<i class="bi bi-chevron-right"></i>' : '<i class="bi bi-chevron-left"></i>';
        });
    }
    
    // --- Auto-Expand Sidebar on Hover (Desktop Only) ---
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && window.innerWidth > 768) { // Desktop only
        // Track whether sidebar was initially collapsed
        let wasCollapsedBeforeHover = body.classList.contains('sidebar-collapsed');
        
        // Auto-expand on mouse enter
        sidebar.addEventListener('mouseenter', () => {
            wasCollapsedBeforeHover = body.classList.contains('sidebar-collapsed');
            if (wasCollapsedBeforeHover) {
                body.classList.remove('sidebar-collapsed');
            }
        });
        
        // Auto-collapse on mouse leave (only if it was collapsed before hover)
        sidebar.addEventListener('mouseleave', () => {
            if (wasCollapsedBeforeHover) {
                body.classList.add('sidebar-collapsed');
            }
        });
        
        // Also handle window resize to disable/enable hover behavior
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                // Mobile view - disable hover expand
                body.classList.remove('sidebar-collapsed');
            }
        });
    }
    
    // --- Mobile Sidebar Backdrop & Swipe ---
    const sidebarMobile = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop && sidebarMobile) {
        backdrop.addEventListener('click', () => {
            sidebarMobile.classList.remove('show');
            backdrop.classList.remove('show');
        });

        let touchStartX = 0;
        sidebarMobile.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        sidebarMobile.addEventListener('touchend', e => {
            const touchEndX = e.changedTouches[0].screenX;
            if (touchEndX < touchStartX && (touchStartX - touchEndX > 50) && window.innerWidth <= 768 && sidebarMobile.classList.contains('show')) {
                sidebarMobile.classList.remove('show');
                backdrop.classList.remove('show');
            }
        }, { passive: true });
    }

    // Panggilan inisialisasi pertama kali untuk script di dalam .content
    initializePageSpecificScripts();
});