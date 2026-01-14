<?php
// Mulai sesi
session_start();

// Koneksi ke database
require_once 'config.php';
$conn = getConnection();

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['Username']) ? trim($_POST['Username']) : '';
    $password = isset($_POST['Password']) ? $_POST['Password'] : '';

    // Validasi input
    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT ID_Admin, Nama, Username, Password FROM Admin WHERE Username = ?");
        if (!$stmt) {
            $_SESSION['error_message'] = "Error: " . $conn->error;
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                $stored_password = (string)($admin['Password'] ?? '');

                $is_valid = false;
                if ($stored_password !== '' && password_verify($password, $stored_password)) {
                    $is_valid = true;
                } elseif ($stored_password !== '' && hash_equals($stored_password, $password)) {
                    $is_valid = true;

                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($new_hash) {
                        $stmt_update = $conn->prepare("UPDATE Admin SET Password = ? WHERE ID_Admin = ?");
                        if ($stmt_update) {
                            $stmt_update->bind_param('si', $new_hash, $admin['ID_Admin']);
                            $stmt_update->execute();
                            $stmt_update->close();
                        }
                    }
                }

                if ($is_valid) {
                    $_SESSION['ID_Admin'] = (int)$admin['ID_Admin'];
                    $_SESSION['Nama'] = (string)$admin['Nama'];
                    $stmt->close();
                    $conn->close();
                    header("Location: dashboard.php");
                    exit();
                }
            }

            $_SESSION['error_message'] = "Username atau password salah!";
            $stmt->close();
        }
    } else {
        // Set pesan kesalahan jika form tidak lengkap
        $_SESSION['error_message'] = "Harap isi semua field!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .login-container {
        padding: 30px 40px;
        border-radius: 16px;
        max-width: 420px;
        width: 100%;
        text-align: center;
    }

    .login-header {
        margin-bottom: 25px;
    }

    .login-header h1 {
        color: #333;
        font-size: 24px;
        font-weight: bold;
    }

    .login-header p {
        color: #6c757d;
        font-size: 14px;
        margin-top: 5px;
    }

    .form-group label {
        color: #333;
        font-weight: bold;
        text-align: left;
        display: block;
    }

    .form-control {
        border: 1px solid rgba(148, 163, 184, 0.7);
        border-radius: 8px;
    }

    .btn-login {
        background: linear-gradient(135deg, #111827 0%, #374151 100%);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        width: 100%;
        transition: background-color 0.3s ease-in-out;
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #0b1220 0%, #1f2937 100%);
    }

    .footer-text {
        margin-top: 15px;
        font-size: 12px;
        color: #6c757d;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-top: 20px;
        border-radius: 8px;
        font-size: 16px;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="login-container wo-card">
        <div class="login-header">
            <h1>Wedding Organizer</h1>
            <p>Login untuk mengakses sistem admin</p>
        </div>

        <!-- Menampilkan pesan kesalahan jika ada -->
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']); // Menghapus pesan kesalahan setelah ditampilkan
        }
        ?>

        <form action="login.php" method="POST" id="login-form">
            <div class="form-group mb-3">
                <label for="Username">Username:</label>
                <input type="text" class="form-control" id="Username" name="Username"
                    placeholder="Masukkan username admin" required>
            </div>
            <div class="form-group mb-3">
                <label for="Password">Password:</label>
                <input type="password" class="form-control" id="Password" name="Password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
        <div class="footer-text">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Tutup koneksi database jika masih terbuka
if (isset($conn) && $conn) {
    $conn->close();
}
?>