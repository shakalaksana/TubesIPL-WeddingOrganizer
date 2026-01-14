<?php
// Mulai sesi
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['ID_Admin']) || !isset($_SESSION['Nama'])) {
    header("Location: login.php");
    exit();
}

// Koneksi ke database menggunakan config.php
require_once 'config.php';
$conn = getConnection();

$action_message = '';
$action_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sewa = isset($_POST['ID_Sewa']) ? (int) $_POST['ID_Sewa'] : 0;
    $pembayaran_baru = isset($_POST['Pembayaran']) ? trim($_POST['Pembayaran']) : '';

    if ($id_sewa > 0 && in_array($pembayaran_baru, ['Lunas', 'Belum Lunas'], true)) {
        $stmt_update = $conn->prepare("UPDATE Sewa_Wedding SET Pembayaran = ? WHERE ID_Sewa = ?");
        if ($stmt_update) {
            $stmt_update->bind_param('si', $pembayaran_baru, $id_sewa);
            if ($stmt_update->execute()) {
                $action_message = "Status pembayaran berhasil diperbarui.";
                $action_status = "success";
            } else {
                $action_message = "Gagal memperbarui status pembayaran: " . $conn->error;
                $action_status = "danger";
            }
            $stmt_update->close();
        } else {
            $action_message = "Gagal memperbarui status pembayaran: " . $conn->error;
            $action_status = "danger";
        }
    } else {
        $action_message = "Permintaan tidak valid.";
        $action_status = "warning";
    }
}

$sewa_orders = [];
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_q = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];
$params = [];
$types = '';

if (in_array($filter_status, ['Lunas', 'Belum Lunas'], true)) {
    $where[] = 'sw.Pembayaran = ?';
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_q !== '') {
    $where[] = '(sw.Nama_Pelanggan LIKE ? OR p.Email LIKE ? OR p.No_Telepon LIKE ? OR sw.Alamat LIKE ? OR sw.Keterangan LIKE ?)';
    $like = '%' . $filter_q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sssss';
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$sql_orders = "
    SELECT 
        sw.ID_Sewa,
        sw.ID_Pelanggan,
        sw.Nama_Pelanggan,
        sw.Alamat,
        sw.Harga_Sewa,
        sw.Pembayaran,
        sw.Tanggal_Sewa,
        sw.Tanggal_Acara,
        sw.Keterangan,
        p.Email,
        p.No_Telepon
    FROM Sewa_Wedding sw
    JOIN Pelanggan p ON p.ID_Pelanggan = sw.ID_Pelanggan
    {$where_sql}
    ORDER BY sw.ID_Sewa DESC
    LIMIT 50
";
$stmt_orders = $conn->prepare($sql_orders);
if ($stmt_orders) {
    if ($types !== '' && count($params) > 0) {
        $stmt_orders->bind_param($types, ...$params);
    }
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    if ($result_orders) {
        $sewa_orders = $result_orders->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_orders->close();
}

$unpaid_count = 0;
$unpaid_total = 0.0;
$upcoming_7_count = 0;
$upcoming_list = [];

$result_unpaid = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(Harga_Sewa), 0) AS total FROM Sewa_Wedding WHERE Pembayaran = 'Belum Lunas'");
if ($result_unpaid) {
    $row_unpaid = $result_unpaid->fetch_assoc();
    $unpaid_count = (int) ($row_unpaid['cnt'] ?? 0);
    $unpaid_total = (float) ($row_unpaid['total'] ?? 0);
}

$result_upcoming_7 = $conn->query("SELECT COUNT(*) AS cnt FROM Sewa_Wedding WHERE Tanggal_Acara >= CURDATE() AND Tanggal_Acara <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
if ($result_upcoming_7) {
    $row_upcoming_7 = $result_upcoming_7->fetch_assoc();
    $upcoming_7_count = (int) ($row_upcoming_7['cnt'] ?? 0);
}

$stmt_upcoming = $conn->prepare("SELECT ID_Sewa, Nama_Pelanggan, Tanggal_Acara, Harga_Sewa, Pembayaran, Keterangan FROM Sewa_Wedding WHERE Tanggal_Acara >= CURDATE() ORDER BY Tanggal_Acara ASC, ID_Sewa DESC LIMIT 10");
if ($stmt_upcoming) {
    $stmt_upcoming->execute();
    $result_upcoming = $stmt_upcoming->get_result();
    if ($result_upcoming) {
        $upcoming_list = $result_upcoming->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_upcoming->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg wo-navbar sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand animate-float" href="dashboard.php">🎉 Wedding Organizer Admin</a>
            <button class="navbar-toggler btn-light" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center gap-2">
                    <a href="dashboard.php" class="btn wo-btn-dark btn-sm active">Dashboard</a>
                    <a href="admin_pesanan.php" class="btn wo-btn-dark btn-sm">Pesanan</a>
                    <a href="admin_pelanggan.php" class="btn wo-btn-dark btn-sm">Pelanggan</a>
                    <a href="admin_profile.php" class="btn wo-btn-dark btn-sm">Profil</a>
                    <span class="text-white small d-none d-md-inline ms-2">Halo,
                        <?php echo htmlspecialchars($_SESSION['Nama']); ?> 👋</span>
                    <a href="logout.php" class="btn wo-btn-primary btn-sm ms-2">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mt-4">
        <!-- Welcome Section -->
        <div class="wo-card p-4 mb-4 animate-fade-in"
            style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(217, 70, 239, 0.1) 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-white mb-1">Selamat Datang di Dashboard Admin 🚀</h2>
                    <p class="text-muted mb-0">Kelola pesanan dan pelanggan dengan mudah.</p>
                </div>
                <div class="d-none d-md-block text-white opacity-50 display-6">
                    ⚡
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-pop-in" style="animation-delay: 0.1s;">
                    <h6 class="text-muted text-uppercase mb-2">Total Pelanggan</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM Pelanggan";
                    $result = $conn->query($sql);
                    $total_pelanggan = $result ? $result->fetch_assoc()['total'] : 0;
                    ?>
                    <h2 class="text-white fw-bold mb-0"><?php echo $total_pelanggan; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-pop-in" style="animation-delay: 0.2s;">
                    <h6 class="text-muted text-uppercase mb-2">Total Sewa Wedding</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM Sewa_Wedding";
                    $result = $conn->query($sql);
                    $total_sewa = $result ? $result->fetch_assoc()['total'] : 0;
                    ?>
                    <h2 class="text-primary fw-bold mb-0"><?php echo $total_sewa; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-pop-in" style="animation-delay: 0.3s;">
                    <h6 class="text-muted text-uppercase mb-2">Total Registrasi</h6>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM Registrasi";
                    $result = $conn->query($sql);
                    $total_registrasi = $result ? $result->fetch_assoc()['total'] : 0;
                    ?>
                    <h2 class="text-white fw-bold mb-0"><?php echo $total_registrasi; ?></h2>
                </div>
            </div>
        </div>

        <!-- Detailed Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-fade-in">
                    <h6 class="text-danger mb-2">Pesanan Belum Lunas</h6>
                    <h3 class="text-white fw-bold"><?php echo $unpaid_count; ?></h3>
                    <a href="dashboard.php?status=Belum%20Lunas" class="btn btn-sm btn-outline-danger mt-2">Lihat Data
                        →</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-fade-in">
                    <h6 class="text-warning mb-2">Estimasi Tagihan Pending</h6>
                    <h3 class="text-white fw-bold">Rp <?php echo number_format($unpaid_total, 0, ',', '.'); ?></h3>
                    <p class="text-muted small mb-0">Total nilai transaksi belum lunas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="wo-card p-4 h-100 animate-fade-in">
                    <h6 class="text-info mb-2">Agenda 7 Hari Ke Depan</h6>
                    <h3 class="text-white fw-bold"><?php echo $upcoming_7_count; ?></h3>
                    <p class="text-muted small mb-0">Acara pernikahan dalam minggu ini</p>
                </div>
            </div>
        </div>

        <!-- Upcoming Events Table -->
        <div class="wo-card p-4 mb-4 animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-white mb-0">🎉 Agenda Acara Terdekat</h5>
                <span class="badge bg-dark border border-secondary border-opacity-50">10 Invoice Teratas</span>
            </div>

            <?php if (count($upcoming_list) === 0): ?>
                <div class="text-center py-4">
                    <p class="text-muted">Belum ada agenda acara dalam waktu dekat.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                        <thead>
                            <tr class="text-uppercase small text-muted">
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Tanggal Acara</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($upcoming_list as $u): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($u['ID_Sewa']); ?></td>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($u['Nama_Pelanggan']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($u['Tanggal_Acara'])); ?></td>
                                    <td>Rp <?php echo number_format((float) $u['Harga_Sewa'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span
                                            class="badge <?php echo ($u['Pembayaran'] === 'Lunas') ? 'bg-success bg-opacity-75' : 'bg-danger bg-opacity-75'; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars($u['Pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($u['Keterangan'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders Table with Actions -->
        <?php if ($action_message): ?>
            <div
                class="alert alert-<?php echo htmlspecialchars($action_status); ?> bg-<?php echo htmlspecialchars($action_status); ?> bg-opacity-10 border-<?php echo htmlspecialchars($action_status); ?> border-opacity-25 text-<?php echo htmlspecialchars($action_status); ?> mb-4 animate-pop-in">
                <?php echo htmlspecialchars($action_message); ?>
            </div>
        <?php endif; ?>

        <div class="wo-card p-4 animate-fade-in">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <h5 class="text-white mb-0">📝 Manajemen Pesanan</h5>

                <form class="d-flex flex-wrap gap-2" method="GET" action="dashboard.php">
                    <select class="form-select form-select-sm bg-dark text-white border-secondary" id="status"
                        name="status" style="width: auto;">
                        <option value="" <?php echo ($filter_status === '') ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="Belum Lunas" <?php echo ($filter_status === 'Belum Lunas') ? 'selected' : ''; ?>>
                            Belum Lunas</option>
                        <option value="Lunas" <?php echo ($filter_status === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                    </select>
                    <input class="form-control form-control-sm bg-dark text-white border-secondary" id="q" name="q"
                        value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Cari..." style="width: 200px;">
                    <button class="btn btn-sm wo-btn-primary" type="submit">Filter</button>
                    <a class="btn btn-sm btn-outline-secondary" href="dashboard.php">Reset</a>
                </form>
            </div>

            <?php if (count($sewa_orders) === 0): ?>
                <div class="text-center py-5">
                    <div class="fs-1 mb-3">🔍</div>
                    <p class="text-muted">Data tidak ditemukan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase small text-muted">
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Jadwal</th>
                                <th>Biaya</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sewa_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['ID_Sewa']); ?></td>
                                    <td>
                                        <div class="fw-bold text-white">
                                            <?php echo htmlspecialchars($order['Nama_Pelanggan']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($order['No_Telepon']); ?>
                                        </div>
                                        <div class="text-muted small fst-italic">
                                            <?php echo htmlspecialchars($order['Alamat']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-white">Acara:
                                            <?php echo date('d M Y', strtotime($order['Tanggal_Acara'])); ?></div>
                                        <div class="small text-muted">Sewa:
                                            <?php echo date('d M Y', strtotime($order['Tanggal_Sewa'])); ?></div>
                                    </td>
                                    <td class="text-white">Rp
                                        <?php echo number_format((float) $order['Harga_Sewa'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($order['Pembayaran'] === 'Lunas'): ?>
                                            <span class="badge bg-success bg-opacity-75 rounded-pill">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-75 rounded-pill">Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="dashboard.php" class="d-flex gap-2">
                                            <input type="hidden" name="ID_Sewa"
                                                value="<?php echo htmlspecialchars($order['ID_Sewa']); ?>">
                                            <select name="Pembayaran"
                                                class="form-select form-select-sm bg-dark text-white border-secondary py-0"
                                                style="width: auto;">
                                                <option value="Belum Lunas" <?php echo ($order['Pembayaran'] === 'Belum Lunas') ? 'selected' : ''; ?>>Belum</option>
                                                <option value="Lunas" <?php echo ($order['Pembayaran'] === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0">Ok</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="wo-card p-3 mt-4 animate-fade-in">
            <div class="d-flex justify-content-between align-items-center text-muted small">
                <span>Database: <?php echo $dbname; ?></span>
                <span>Admin Login: <?php echo htmlspecialchars($_SESSION['Nama']); ?> (ID:
                    <?php echo htmlspecialchars($_SESSION['ID_Admin']); ?>)</span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
$conn->close();
?>