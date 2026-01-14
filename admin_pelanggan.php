<?php
session_start();

if (!isset($_SESSION['ID_Admin'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = getConnection();

$flash_message = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$flash_type = isset($_GET['type']) ? trim($_GET['type']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $id_pelanggan = isset($_POST['ID_Pelanggan']) ? (int)$_POST['ID_Pelanggan'] : 0;
    $return = isset($_POST['return']) ? trim($_POST['return']) : '';

    $redirect = "admin_pelanggan.php";
    if ($return !== '') {
        $redirect .= "?" . $return;
        $redirect .= "&";
    } else {
        $redirect .= "?";
    }

    if ($id_pelanggan <= 0) {
        header("Location: {$redirect}msg=" . urlencode("Permintaan tidak valid.") . "&type=warning");
        exit();
    }

    if ($action === 'update') {
        $nama = isset($_POST['Nama_Pelanggan']) ? trim($_POST['Nama_Pelanggan']) : '';
        $alamat = isset($_POST['Alamat']) ? trim($_POST['Alamat']) : '';
        $jenis_kelamin = isset($_POST['Jenis_Kelamin']) ? trim($_POST['Jenis_Kelamin']) : '';
        $email = isset($_POST['Email']) ? trim($_POST['Email']) : '';
        $no_telepon = isset($_POST['No_Telepon']) ? trim($_POST['No_Telepon']) : '';

        if ($nama === '' || $alamat === '' || $email === '' || $no_telepon === '' || !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)) {
            header("Location: {$redirect}msg=" . urlencode("Data pelanggan belum lengkap.") . "&type=warning");
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: {$redirect}msg=" . urlencode("Format email tidak valid.") . "&type=warning");
            exit();
        }

        $stmt = $conn->prepare("UPDATE Pelanggan SET Nama_Pelanggan = ?, Alamat = ?, Jenis_Kelamin = ?, Email = ?, No_Telepon = ? WHERE ID_Pelanggan = ?");
        if (!$stmt) {
            header("Location: {$redirect}msg=" . urlencode("Gagal menyimpan perubahan.") . "&type=danger");
            exit();
        }
        $stmt->bind_param('sssssi', $nama, $alamat, $jenis_kelamin, $email, $no_telepon, $id_pelanggan);
        $ok = $stmt->execute();
        $err = $conn->error;
        $stmt->close();

        if ($ok) {
            header("Location: {$redirect}msg=" . urlencode("Data pelanggan berhasil diperbarui.") . "&type=success");
            exit();
        }

        header("Location: {$redirect}msg=" . urlencode("Gagal memperbarui pelanggan: {$err}") . "&type=danger");
        exit();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM Pelanggan WHERE ID_Pelanggan = ?");
        if (!$stmt) {
            header("Location: {$redirect}msg=" . urlencode("Gagal menghapus pelanggan.") . "&type=danger");
            exit();
        }
        $stmt->bind_param('i', $id_pelanggan);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: {$redirect}msg=" . urlencode("Pelanggan berhasil dihapus.") . "&type=success");
            exit();
        }

        header("Location: {$redirect}msg=" . urlencode("Gagal menghapus pelanggan.") . "&type=danger");
        exit();
    }

    if ($action === 'reset_password') {
        $temp_password = bin2hex(random_bytes(4));
        $hash = password_hash($temp_password, PASSWORD_DEFAULT);
        if (!$hash) {
            header("Location: {$redirect}msg=" . urlencode("Gagal membuat password baru.") . "&type=danger");
            exit();
        }

        $stmt = $conn->prepare("UPDATE Pelanggan SET Password = ? WHERE ID_Pelanggan = ?");
        if (!$stmt) {
            header("Location: {$redirect}msg=" . urlencode("Gagal reset password.") . "&type=danger");
            exit();
        }
        $stmt->bind_param('si', $hash, $id_pelanggan);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: {$redirect}msg=" . urlencode("Password direset. Password sementara: {$temp_password}") . "&type=success");
            exit();
        }

        header("Location: {$redirect}msg=" . urlencode("Gagal reset password.") . "&type=danger");
        exit();
    }

    header("Location: {$redirect}msg=" . urlencode("Aksi tidak dikenal.") . "&type=warning");
    exit();
}

$filter_q = isset($_GET['q']) ? trim($_GET['q']) : '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$edit_customer = null;
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM Pelanggan WHERE ID_Pelanggan = ?");
    if ($stmt) {
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $edit_customer = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

$customers = [];
$where_sql = '';
$params = [];
$types = '';
if ($filter_q !== '') {
    $where_sql = "WHERE (Nama_Pelanggan LIKE ? OR Email LIKE ? OR No_Telepon LIKE ? OR Alamat LIKE ?)";
    $like = '%' . $filter_q . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$sql = "SELECT ID_Pelanggan, Nama_Pelanggan, Jenis_Kelamin, Alamat, Email, No_Telepon FROM Pelanggan {$where_sql} ORDER BY ID_Pelanggan DESC LIMIT 200";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '' && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $customers = $res->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

$total = count($customers);
$return_query = $_SERVER['QUERY_STRING'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Kelola Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg wo-navbar">
        <div class="container">
            <a class="navbar-brand text-decoration-none" href="dashboard.php">Wedding Organizer</a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="admin_pesanan.php" class="btn btn-outline-light btn-sm">Pesanan</a>
                <a href="admin_profile.php" class="btn btn-light btn-sm text-dark">Profil</a>
                <a href="logout.php" class="btn btn-light btn-sm text-dark">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="wo-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="mb-1">Kelola Pelanggan</h3>
                    <div class="wo-muted small">Edit data pelanggan, reset password, atau hapus pelanggan</div>
                </div>
                <div class="text-end">
                    <div class="wo-muted small">Total Data (maks. 200)</div>
                    <div class="fs-4 fw-bold"><?php echo $total; ?></div>
                </div>
            </div>
        </div>

        <?php if ($flash_message !== '' && in_array($flash_type, ['success','danger','warning','info'], true)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <?php if ($edit_customer): ?>
            <div class="wo-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Edit Pelanggan #<?php echo htmlspecialchars((string)$edit_customer['ID_Pelanggan']); ?></div>
                    <a href="admin_pelanggan.php<?php echo $filter_q !== '' ? ('?q=' . urlencode($filter_q)) : ''; ?>" class="btn btn-sm btn-outline-secondary">Tutup</a>
                </div>
                <form method="POST" action="admin_pelanggan.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="ID_Pelanggan" value="<?php echo htmlspecialchars((string)$edit_customer['ID_Pelanggan']); ?>">
                    <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_query); ?>">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label wo-form-label small mb-1">Nama</label>
                            <input class="form-control form-control-sm" name="Nama_Pelanggan" value="<?php echo htmlspecialchars((string)$edit_customer['Nama_Pelanggan']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label wo-form-label small mb-1">Jenis Kelamin</label>
                            <select class="form-select form-select-sm" name="Jenis_Kelamin" required>
                                <option value="Laki-laki" <?php echo ((string)$edit_customer['Jenis_Kelamin'] === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php echo ((string)$edit_customer['Jenis_Kelamin'] === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label wo-form-label small mb-1">Email</label>
                            <input type="email" class="form-control form-control-sm" name="Email" value="<?php echo htmlspecialchars((string)$edit_customer['Email']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label wo-form-label small mb-1">No Telepon</label>
                            <input class="form-control form-control-sm" name="No_Telepon" value="<?php echo htmlspecialchars((string)$edit_customer['No_Telepon']); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label wo-form-label small mb-1">Alamat</label>
                            <input class="form-control form-control-sm" name="Alamat" value="<?php echo htmlspecialchars((string)$edit_customer['Alamat']); ?>" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button class="btn btn-sm wo-btn-primary" type="submit">Simpan</button>
                            <a class="btn btn-sm btn-outline-secondary" href="admin_pelanggan.php<?php echo $filter_q !== '' ? ('?q=' . urlencode($filter_q)) : ''; ?>">Batal</a>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="wo-card p-3 mb-3">
            <form class="row g-2 align-items-end" method="GET" action="admin_pelanggan.php">
                <div class="col-12 col-md-10">
                    <label class="form-label wo-form-label small mb-1" for="q">Pencarian</label>
                    <input class="form-control form-control-sm" id="q" name="q" value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Nama / Email / Telepon / Alamat">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm wo-btn-primary w-100" type="submit">Cari</button>
                    <a class="btn btn-sm btn-outline-secondary w-100" href="admin_pelanggan.php">Reset</a>
                </div>
            </form>
        </div>

        <div class="wo-card p-3">
            <?php if (count($customers) === 0): ?>
                <div class="wo-muted">Tidak ada data pelanggan.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle wo-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Alamat</th>
                                <th style="min-width: 240px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars((string)$c['ID_Pelanggan']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars((string)$c['Nama_Pelanggan']); ?></div>
                                        <div class="wo-muted small"><?php echo htmlspecialchars((string)$c['Jenis_Kelamin']); ?></div>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars((string)$c['Email']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars((string)$c['No_Telepon']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars((string)$c['Alamat']); ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <a class="btn btn-sm btn-outline-secondary" href="admin_pelanggan.php?edit=<?php echo urlencode((string)$c['ID_Pelanggan']); ?><?php echo $filter_q !== '' ? ('&q=' . urlencode($filter_q)) : ''; ?>">Edit</a>
                                            <a class="btn btn-sm btn-outline-primary" href="admin_pesanan.php?q=<?php echo urlencode((string)$c['Nama_Pelanggan']); ?>">Pesanan</a>
                                            <form method="POST" action="admin_pelanggan.php" class="m-0">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="ID_Pelanggan" value="<?php echo htmlspecialchars((string)$c['ID_Pelanggan']); ?>">
                                                <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_query); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Reset Password</button>
                                            </form>
                                            <form method="POST" action="admin_pelanggan.php" class="m-0" onsubmit="return confirm('Hapus pelanggan ini? Semua pesanan terkait akan ikut terhapus.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="ID_Pelanggan" value="<?php echo htmlspecialchars((string)$c['ID_Pelanggan']); ?>">
                                                <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_query); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
