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
                $stored_password = (string) ($admin['Password'] ?? '');

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
                    $_SESSION['ID_Admin'] = (int) $admin['ID_Admin'];
                    $_SESSION['Nama'] = (string) $admin['Nama'];
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
    <title>Wedding Organizer - Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="wo-card auth-card p-5 animate-fade-in">
            <div class="text-center mb-4">
                <div class="mb-3 animate-float" style="font-size: 3rem;">🔐</div>
                <h2 class="text-primary mb-1">Login Admin</h2>
                <p class="text-muted">Akses panel kontrol sistem</p>
            </div>

            <!-- Menampilkan pesan kesalahan jika ada -->
            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']); // Menghapus pesan kesalahan setelah ditampilkan
            }
            ?>

            <form action="login.php" method="POST" id="login-form">
                <div class="mb-3">
                    <label for="Username" class="form-label text-muted small text-uppercase">Username</label>
                    <input type="text" class="form-control" id="Username" name="Username" placeholder="Nama pengguna"
                        required>
                </div>
                <div class="mb-4">
                    <label for="Password" class="form-label text-muted small text-uppercase">Password</label>
                    <input type="password" class="form-control" id="Password" name="Password"
                        placeholder="Sandi rahasia" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn wo-btn-dark">Akses Dashboard ⚡</button>
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
<?php
// Tutup koneksi database jika masih terbuka
if (isset($conn) && $conn) {
    $conn->close();
}
?>