<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SMK Lab Jakarta</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="assets/img/smk.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Main CSS File -->
    <!-- Cache buster: ensures browser reloads the latest CSS after edits -->
    <link href="assets/css/main.css?v=20260117" rel="stylesheet">

    <!-- =======================================================
    * Template Name: Selecao
    * Template URL: https://bootstrapmade.com/selecao-bootstrap-template/
    * Updated: Aug 07 2024 with Bootstrap v5.3.3
    * Author: BootstrapMade.com
    * License: https://bootstrapmade.com/license/
    ======================================================== -->
</head>
<body class="login-page">

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a class="logo d-flex align-items-center">
                <img class="sitename" src="assets/img/smk.png" style="max-height: 50px;"><h1>SMK Laboratorium Jakarta</h1></img>
            </a>            
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#daftar">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Login Section -->
    <main class="main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12 col-md-9">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden my-5">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-light">
                                    <img src="assets/img/smk.png" alt="Login Illustration" class="img-fluid p-1" style="max-height: 400px;">
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 p-lg-5">
                                        <div class="d-flex align-items-center justify-content-center mb-3 d-md-none">
                                            <img src="assets/img/smk.png" alt="Logo" style="max-height: 60px;">
                                        </div>
                                        <h5 class="card-title text-center pb-0 fs-4">Login</h5>
                                        <p class="text-center small mb-4">Enter your username & password to login</p>

                                    <form class="row g-3 needs-validation" action="login_process.php" method="post" novalidate>

                                        <div class="col-12">
                                            <label for="identifier" class="form-label">Username</label>
                                            <div class="input-group has-validation">
                                                <input type="text" name="identifier" class="form-control" id="identifier" required>
                                                <div class="invalid-feedback">Please enter your username.</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" name="password" class="form-control" id="password" required minlength="8">
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                                <div class="invalid-feedback">Please enter your password!</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Captcha</label>
                                            <div class="row g-2">
                                                <div class="col-5">
                                                    <img src="captcha.php" alt="Captcha" class="img-fluid w-100" style="height: 38px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6;">
                                                </div>
                                                <div class="col-7">
                                                    <input type="text" name="captcha" class="form-control" placeholder="Enter Code" required>
                                                    <div class="invalid-feedback">Please enter captcha.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember_me" value="true" id="rememberMe">
                                                <label class="form-check-label" for="rememberMe">Remember me</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-success w-100" type="submit">Login</button>
                                        </div>
                                        <div class="col-12">
                                            <a href="google_login.php" class="btn btn-outline-danger w-100">
                                                <i class="bi bi-google me-2"></i> Login with Google
                                            </a>
                                        </div>
                                        <div class="col-12">
                                            <p class="small mb-0"><a href="forgot_password.php">Forgot Password?</a></p>
                                        </div>
                                    </form>
                                    <div id="message" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }
            const formData = new FormData(this);
            fetch('login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response was not JSON:', text);
                        // Tampilkan sebagian pesan error dari server untuk debugging
                        throw new Error('Server Error: ' + text.substring(0, 100) + '...');
                    }
                });
            })
            .then(data => {
                document.getElementById('message').innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('message').innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
            });
        });

        // Show/Hide Password Toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function (e) {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // toggle the icon
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        }
    </script>
</body>
</html>