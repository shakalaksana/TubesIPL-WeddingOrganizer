<?php
session_start();
require_once 'config.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['Email']);
    $password = $_POST['Password'];

    if (empty($email) || empty($password)) {
        $error_message = "Harap isi semua field!";
    } else {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT ID_Pelanggan, Nama_Pelanggan, Email, Password FROM Pelanggan WHERE Email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            
            // Verifikasi password
            $stored_password = (string)($customer['Password'] ?? '');
            $is_valid = false;
            if ($stored_password !== '' && password_verify($password, $stored_password)) {
                $is_valid = true;
            } elseif ($stored_password !== '' && hash_equals($stored_password, $password)) {
                $is_valid = true;

                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                if ($new_hash) {
                    $stmt_update = $conn->prepare("UPDATE Pelanggan SET Password = ? WHERE ID_Pelanggan = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param('si', $new_hash, $customer['ID_Pelanggan']);
                        $stmt_update->execute();
                        $stmt_update->close();
                    }
                }
            }

            if ($is_valid) {
                $_SESSION['ID_Pelanggan'] = $customer['ID_Pelanggan'];
                $_SESSION['Nama_Pelanggan'] = $customer['Nama_Pelanggan'];
                $_SESSION['Email'] = $customer['Email'];
                $stmt->close();
                $conn->close();
                
                header("Location: customer_dashboard.php");
                exit();
            } else {
                $error_message = "Email atau password salah!";
            }
        } else {
            $error_message = "Email atau password salah!";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Login Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .login-container {
            border-radius: 15px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .form-label {
            font-weight: bold;
            color: #333;
        }
        .btn-login {
            background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
        }
        .alert {
            border-radius: 8px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container wo-card">
        <div class="login-header">
            <h1>Login Customer</h1>
            <p class="text-muted">Masuk ke akun Anda</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form method="POST" action="customer_login.php">
            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" 
                    value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="Password" class="form-label">Password</label>
                <input type="password" class="form-control" id="Password" name="Password" required>
            </div>

            <button type="submit" class="btn btn-login">Login</button>
        </form>

        <div class="back-link">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            <p><a href="index.php">Kembali ke Home</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
