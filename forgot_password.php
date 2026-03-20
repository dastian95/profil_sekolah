<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="login-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a class="logo d-flex align-items-center">
                <img class="sitename" src="assets/img/smk.png" style="max-height: 50px;"><h1>SMK Laboratorium Jakarta</h1></img>
            </a>            
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="login.php">Back to Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Forgot Password</h3>
                    </div>
                    <div class="card-body">
                        <form action="forgot_password_process.php" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Enter your email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        </form>
                        <div id="message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const msgDiv = document.getElementById('message');
            msgDiv.innerHTML = '<div class="alert alert-info">Sending...</div>';
            
            fetch('forgot_password_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid server response');
                    }
                });
            })
            .then(data => {
                msgDiv.innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
            })
            .catch(error => {
                msgDiv.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
            });
        });
    </script>
</body>
</html>