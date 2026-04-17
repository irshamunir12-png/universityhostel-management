<?php
require_once 'core/session.php'; // Use the updated session file above
require_once 'core/auth.php';
$auth = new Auth($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... rest of your login logic
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if ($auth->login($identifier, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Credentials or Account Suspended.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Universal System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css" />
    <style>
        body.login-page {
            background-color: #e9ecef;
        }
        /* Login Card ko thoda prominent dikhane ke liye */
        .login-box .card {
            background: rgba(255, 255, 255, 0.95); /* Thoda sa transparent white */
            box-shadow: 0 0 20px rgba(0,0,0,0.5); /* Kala saya (shadow) */
        }
        /* Custom Split Layout Styles */
        .login-card-container {
            max-width: 1050px; /* Thoda wide kiya taake image aur badi lage */
            width: 100%;
        }
        .uni-bg-image {
            background-image: url('assets/img/background.jpg'); /* New background image */
            background-size: cover;
            background-position: center;
            min-height: 400px;
        }
        
        /* Letter Pop Animation Effect */
        @keyframes inputPop {
            0% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
            100% { transform: translateY(0); }
        }
        .typing-pop {
            animation: inputPop 0.15s ease-out;
        }
    </style>
</head>
<body class="login-page d-flex align-items-center justify-content-center">
    <div class="container login-card-container">
        <div class="card card-outline card-primary shadow-lg border-0 overflow-hidden">
            <div class="row g-0">
                <!-- Left Side: Login Form -->
                <div class="col-md-5 p-4 p-lg-5 bg-white border-md-end" style="border-right: 1px solid #dee2e6;">
                    <div class="text-center mb-4">
                        <a href="#" class="link-dark text-decoration-none">
                            <h1 class="mb-0"><b>Universal</b>ERP</h1>
                        </a>
                        <p class="login-box-msg">Sign in to start your session</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger text-center p-2"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <input type="text" name="identifier" class="form-control" id="loginId" placeholder="" required>
                                <label for="loginId">Email / CNIC / Reg No</label>
                            </div>
                            <div class="input-group-text"><span class="bi bi-person"></span></div>
                        </div>
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control" id="loginPass" placeholder="" required>
                                <label for="loginPass">Password</label>
                            </div>
                            <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                        </div>
                        <div class="row">
                            <div class="col-7">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="flexCheckDefault">
                                    <label class="form-check-label" for="flexCheckDefault">Remember Me</label>
                                </div>
                            </div>
                            <div class="col-5">
                                <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            </div>
                        </div>
                    </form>

                    <p class="mb-0 mt-4 text-center">
                        <a href="student/register.php" class="text-decoration-none">Register a new membership</a>
                    </p>
                </div>
                <!-- Right Side: University Image -->
                <div class="col-md-7 d-none d-md-block uni-bg-image">
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script>
        // Har keypress par input field ko animate karne ka logic
        const inputs = document.querySelectorAll('#loginId, #loginPass');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                input.classList.remove('typing-pop');
                void input.offsetWidth; // Browser ko class reset karne par majboor karta hy
                input.classList.add('typing-pop');
            });
        });
    </script>
</body>
</html>