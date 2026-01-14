<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['ID_Pelanggan'])) {
    header("Location: customer_login.php");
    exit();
}

$conn = getConnection();

$customer_id = (int) $_SESSION['ID_Pelanggan'];
$stmt = $conn->prepare("SELECT ID_Pelanggan, Nama_Pelanggan, Email, No_Telepon, Alamat, Password FROM Pelanggan WHERE ID_Pelanggan = ?");
$customer = null;
if ($stmt) {
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $customer = $res->fetch_assoc();
    }
    $stmt->close();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = isset($_POST['Current_Password']) ? (string) $_POST['Current_Password'] : '';
    $new_password = isset($_POST['New_Password']) ? (string) $_POST['New_Password'] : '';
    $confirm_password = isset($_POST['Confirm_Password']) ? (string) $_POST['Confirm_Password'] : '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $message = "Harap isi semua field.";
        $message_type = "warning";
    } elseif (strlen($new_password) < 6) {
        $message = "Password baru minimal 6 karakter.";
        $message_type = "warning";
    } elseif (!hash_equals($new_password, $confirm_password)) {
        $message = "Password baru dan konfirmasi tidak cocok.";
        $message_type = "warning";
    } elseif (!$customer) {
        $message = "Data customer tidak ditemukan.";
        $message_type = "danger";
    } else {
        $stored_password = (string) ($customer['Password'] ?? '');

        $is_valid = false;
        if ($stored_password !== '' && password_verify($current_password, $stored_password)) {
            $is_valid = true;
        } elseif ($stored_password !== '' && hash_equals($stored_password, $current_password)) {
            $is_valid = true;
        }

        if (!$is_valid) {
            $message = "Password saat ini salah.";
            $message_type = "danger";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            if (!$new_hash) {
                $message = "Gagal membuat hash password.";
                $message_type = "danger";
            } else {
                $stmt_update = $conn->prepare("UPDATE Pelanggan SET Password = ? WHERE ID_Pelanggan = ?");
                if (!$stmt_update) {
                    $message = "Gagal memperbarui password.";
                    $message_type = "danger";
                } else {
                    $stmt_update->bind_param('si', $new_hash, $customer_id);
                    $ok = $stmt_update->execute();
                    $stmt_update->close();

                    if ($ok) {
                        $message = "Password berhasil diperbarui.";
                        $message_type = "success";
                    } else {
                        $message = "Gagal memperbarui password.";
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Profil Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg wo-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand animate-float" href="customer_dashboard.php">🎉 Wedding Organizer</a>
            <button class="navbar-toggler btn-light" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center gap-2">
                    <a href="customer_dashboard.php" class="btn wo-btn-dark btn-sm">Home</a>
                    <a href="my_sewa.php" class="btn wo-btn-dark btn-sm">Sewa Saya</a>
                    <a href="customer_profile.php" class="btn wo-btn-dark btn-sm active">Profil</a>
                    <a href="customer_logout.php" class="btn wo-btn-primary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="wo-card p-4 mb-4 animate-fade-in">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="bg-primary bg-opacity-25 p-3 rounded-circle text-primary border border-primary border-opacity-50">
                                <span class="fs-4">👤</span>
                            </div>
                            <div>
                                <h3 class="mb-1 text-white">Profil Customer</h3>
                                <div class="text-muted small">Kelola informasi akun dan keamanan</div>
                            </div>
                        </div>
                        <?php if ($customer): ?>
                            <div class="text-end d-none d-md-block">
                                <span
                                    class="badge bg-dark border border-secondary border-opacity-50 text-muted px-3 py-2 rounded-pill">
                                    ID: #<?php echo htmlspecialchars((string) ($customer['ID_Pelanggan'] ?? '')); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message !== '' && in_array($message_type, ['success', 'danger', 'warning', 'info'], true)): ?>
                    <div
                        class="alert alert-<?php echo htmlspecialchars($message_type); ?> bg-<?php echo htmlspecialchars($message_type); ?> bg-opacity-10 border-<?php echo htmlspecialchars($message_type); ?> border-opacity-25 text-<?php echo htmlspecialchars($message_type); ?> mb-4 animate-pop-in">
                        <?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="wo-card p-5 animate-fade-in" style="animation-delay: 0.2s;">
                    <h5 class="text-white mb-4 pb-3 border-bottom border-secondary border-opacity-25">Ganti Password 🔒
                    </h5>

                    <form method="POST" action="customer_profile.php">
                        <div class="mb-4">
                            <label class="form-label text-muted small text-uppercase mb-2"
                                for="Current_Password">Password Saat Ini</label>
                            <input type="password" class="form-control" id="Current_Password" name="Current_Password"
                                required placeholder="Masukkan password lama">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-muted small text-uppercase mb-2"
                                    for="New_Password">Password Baru</label>
                                <input type="password" class="form-control" id="New_Password" name="New_Password"
                                    minlength="6" required placeholder="Minimal 6 karakter">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-muted small text-uppercase mb-2"
                                    for="Confirm_Password">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="Confirm_Password"
                                    name="Confirm_Password" minlength="6" required placeholder="Ulangi password baru">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-3">
                            <button class="btn wo-btn-primary px-4" type="submit">Simpan Perubahan 💾</button>
                        </div>
                    </form>
                </div>

                <?php if ($customer): ?>
                    <div class="mt-4 text-center animate-fade-in" style="animation-delay: 0.4s;">
                        <p class="text-muted small">Terdaftar dengan email <span
                                class="text-white"><?php echo htmlspecialchars((string) ($customer['Email'] ?? '')); ?></span>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
$conn->close();
?>