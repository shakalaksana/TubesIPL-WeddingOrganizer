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
</head>
<body>
    <div class="auth-container">
        <div class="wo-card auth-card p-5 animate-fade-in">
            <div class="text-center mb-4">
                <div class="mb-3 animate-float" style="font-size: 3rem;">👋</div>
                <h2 class="text-primary mb-1">Login Customer</h2>
                <p class="text-muted">Masuk untuk melanjutkan pesananmu</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form method="POST" action="customer_login.php">
                <div class="mb-3">
                    <label for="Email" class="form-label text-muted small text-uppercase">Email</label>
                    <input type="email" class="form-control" id="Email" name="Email" 
                        value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>" required placeholder="namamu@email.com">
                </div>

                <div class="mb-4">
                    <label for="Password" class="form-label text-muted small text-uppercase">Password</label>
                    <input type="password" class="form-control" id="Password" name="Password" required placeholder="******">
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn wo-btn-primary">Masuk Sekarang 🚀</button>
                    <div class="text-center">
                        <span class="text-muted small">Belum punya akun?</span>
                        <a href="register.php" class="text-primary text-decoration-none fw-bold ms-1">Daftar di sini</a>
                    </div>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-muted text-decoration-none small">← Kembali ke Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
