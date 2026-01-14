<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['ID_Pelanggan'])) {
    header("Location: customer_login.php");
    exit();
}

$conn = getConnection();

$customer_id = (int)$_SESSION['ID_Pelanggan'];
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
    $current_password = isset($_POST['Current_Password']) ? (string)$_POST['Current_Password'] : '';
    $new_password = isset($_POST['New_Password']) ? (string)$_POST['New_Password'] : '';
    $confirm_password = isset($_POST['Confirm_Password']) ? (string)$_POST['Confirm_Password'] : '';

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
        $stored_password = (string)($customer['Password'] ?? '');

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
    <nav class="navbar wo-navbar">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">🎉 Wedding Organizer</a>
            <div class="navbar-nav ms-auto align-items-center flex-row gap-2">
                <a href="customer_dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="my_sewa.php" class="btn btn-outline-light btn-sm">Sewa Saya</a>
                <a href="sewa_wedding.php" class="btn btn-outline-light btn-sm">+ Sewa Baru</a>
                <a href="customer_logout.php" class="btn btn-light btn-sm text-dark">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="wo-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="mb-1">Profil Customer</h3>
                    <div class="wo-muted small">Ganti password akun Anda</div>
                </div>
                <?php if ($customer): ?>
                    <div class="text-end">
                        <div class="small wo-muted">Nama</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars((string)($customer['Nama_Pelanggan'] ?? '')); ?></div>
                        <div class="small wo-muted mt-2">Email</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars((string)($customer['Email'] ?? '')); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message !== '' && in_array($message_type, ['success','danger','warning','info'], true)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="wo-card p-3">
            <form method="POST" action="customer_profile.php" class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label wo-form-label small mb-1" for="Current_Password">Password Saat Ini</label>
                    <input type="password" class="form-control" id="Current_Password" name="Current_Password" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label wo-form-label small mb-1" for="New_Password">Password Baru</label>
                    <input type="password" class="form-control" id="New_Password" name="New_Password" minlength="6" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label wo-form-label small mb-1" for="Confirm_Password">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="Confirm_Password" name="Confirm_Password" minlength="6" required>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn wo-btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>

