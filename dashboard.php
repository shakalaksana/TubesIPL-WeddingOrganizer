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
    $id_sewa = isset($_POST['ID_Sewa']) ? (int)$_POST['ID_Sewa'] : 0;
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
    $unpaid_count = (int)($row_unpaid['cnt'] ?? 0);
    $unpaid_total = (float)($row_unpaid['total'] ?? 0);
}

$result_upcoming_7 = $conn->query("SELECT COUNT(*) AS cnt FROM Sewa_Wedding WHERE Tanggal_Acara >= CURDATE() AND Tanggal_Acara <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
if ($result_upcoming_7) {
    $row_upcoming_7 = $result_upcoming_7->fetch_assoc();
    $upcoming_7_count = (int)($row_upcoming_7['cnt'] ?? 0);
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
    <title>Wedding Organizer - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 30px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 18px 60px rgba(17, 24, 39, 0.10);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
        }
        .welcome-card {
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.95) 0%, rgba(244, 114, 182, 0.95) 55%, rgba(251, 113, 133, 0.95) 100%);
            color: white;
            border: 0;
        }
        .stat-title {
            color: rgba(17, 24, 39, 0.72);
            margin-bottom: 8px;
            font-weight: bold;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .stat-sub {
            color: rgba(17, 24, 39, 0.55);
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg wo-navbar">
        <div class="container-fluid">
            <a class="navbar-brand text-white mb-0 h4 text-decoration-none" href="dashboard.php">Wedding Organizer</a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="admin_pesanan.php" class="btn btn-outline-light btn-sm">Pesanan</a>
                <a href="admin_pelanggan.php" class="btn btn-outline-light btn-sm">Pelanggan</a>
                <a href="admin_profile.php" class="btn btn-light btn-sm text-dark">Profil</a>
                <span class="text-white small d-none d-md-inline">Halo, <?php echo htmlspecialchars($_SESSION['Nama']); ?></span>
                <a href="logout.php" class="btn btn-light btn-sm text-dark">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card welcome-card">
                    <div class="card-body">
                        <h2 class="card-title">Selamat Datang di Dashboard</h2>
                        <p class="card-text">Anda berhasil login sebagai Admin (ID: <?php echo htmlspecialchars($_SESSION['ID_Admin']); ?>)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Data Pelanggan</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM Pelanggan";
                        $result = $conn->query($sql);
                        if ($result) {
                            $row = $result->fetch_assoc();
                            echo "<h2>" . $row['total'] . "</h2>";
                        } else {
                            echo "<h2>0</h2>";
                        }
                        ?>
                        <p class="text-muted">Total Pelanggan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sewa Wedding</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM Sewa_Wedding";
                        $result = $conn->query($sql);
                        if ($result) {
                            $row = $result->fetch_assoc();
                            echo "<h2>" . $row['total'] . "</h2>";
                        } else {
                            echo "<h2>0</h2>";
                        }
                        ?>
                        <p class="text-muted">Total Sewa</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Registrasi</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM Registrasi";
                        $result = $conn->query($sql);
                        if ($result) {
                            $row = $result->fetch_assoc();
                            echo "<h2>" . $row['total'] . "</h2>";
                        } else {
                            echo "<h2>0</h2>";
                        }
                        ?>
                        <p class="text-muted">Total Registrasi</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-title">Pesanan Belum Lunas</div>
                        <div class="stat-value"><?php echo $unpaid_count; ?></div>
                        <p class="stat-sub mb-0"><a href="dashboard.php?status=Belum%20Lunas" class="link-primary text-decoration-none">Lihat data</a></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-title">Total Tagihan Belum Lunas</div>
                        <div class="stat-value">Rp <?php echo number_format($unpaid_total, 0, ',', '.'); ?></div>
                        <p class="stat-sub">Estimasi total harga sewa belum lunas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-title">Agenda 7 Hari</div>
                        <div class="stat-value"><?php echo $upcoming_7_count; ?></div>
                        <p class="stat-sub">Jumlah acara dalam 7 hari ke depan</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Agenda Acara Terdekat</h5>
                        <span class="text-muted small">Maks. 10 data</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($upcoming_list) === 0): ?>
                            <p class="text-muted mb-0">Belum ada agenda acara.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID Sewa</th>
                                            <th>Pelanggan</th>
                                            <th>Tanggal Acara</th>
                                            <th>Harga</th>
                                            <th>Pembayaran</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_list as $u): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($u['ID_Sewa']); ?></td>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($u['Nama_Pelanggan']); ?></td>
                                                <td><?php echo htmlspecialchars($u['Tanggal_Acara']); ?></td>
                                                <td>Rp <?php echo number_format((float)$u['Harga_Sewa'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if (($u['Pembayaran'] ?? '') === 'Lunas'): ?>
                                                        <span class="badge bg-success">Lunas</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small"><?php echo htmlspecialchars($u['Keterangan'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <?php if ($action_message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($action_status); ?>">
                        <?php echo htmlspecialchars($action_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pesanan Sewa Wedding Terbaru</h5>
                        <span class="text-muted small">Maks. 50 data terakhir</span>
                    </div>
                    <div class="card-body">
                        <form class="row g-2 align-items-end mb-3" method="GET" action="dashboard.php">
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1" for="status">Status Pembayaran</label>
                                <select class="form-select form-select-sm" id="status" name="status">
                                    <option value="" <?php echo ($filter_status === '') ? 'selected' : ''; ?>>Semua</option>
                                    <option value="Belum Lunas" <?php echo ($filter_status === 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                                    <option value="Lunas" <?php echo ($filter_status === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1" for="q">Pencarian</label>
                                <input class="form-control form-control-sm" id="q" name="q" value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Nama / email / telepon / alamat / keterangan">
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2">
                                <button class="btn btn-sm btn-primary w-100" type="submit">Filter</button>
                                <a class="btn btn-sm btn-outline-secondary w-100" href="dashboard.php">Reset</a>
                            </div>
                        </form>

                        <?php if (count($sewa_orders) === 0): ?>
                            <p class="text-muted mb-0">Belum ada pesanan sewa wedding.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID Sewa</th>
                                            <th>Pelanggan</th>
                                            <th>Kontak</th>
                                            <th>Tanggal</th>
                                            <th>Harga</th>
                                            <th>Pembayaran</th>
                                            <th>Alamat</th>
                                            <th>Keterangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sewa_orders as $order): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['ID_Sewa']); ?></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($order['Nama_Pelanggan']); ?></div>
                                                    <div class="text-muted small">ID Pelanggan: <?php echo htmlspecialchars($order['ID_Pelanggan']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="small"><?php echo htmlspecialchars($order['Email']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($order['No_Telepon']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="small">Sewa: <?php echo htmlspecialchars($order['Tanggal_Sewa']); ?></div>
                                                    <div class="text-muted small">Acara: <?php echo htmlspecialchars($order['Tanggal_Acara']); ?></div>
                                                </td>
                                                <td>Rp <?php echo number_format((float)$order['Harga_Sewa'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($order['Pembayaran'] === 'Lunas'): ?>
                                                        <span class="badge bg-success">Lunas</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small"><?php echo htmlspecialchars($order['Alamat']); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($order['Keterangan'] ?? ''); ?></td>
                                                <td style="min-width: 180px;">
                                                    <form method="POST" action="dashboard.php" class="d-flex gap-2">
                                                        <input type="hidden" name="ID_Sewa" value="<?php echo htmlspecialchars($order['ID_Sewa']); ?>">
                                                        <select name="Pembayaran" class="form-select form-select-sm">
                                                            <option value="Belum Lunas" <?php echo ($order['Pembayaran'] === 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                                                            <option value="Lunas" <?php echo ($order['Pembayaran'] === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Informasi Sistem</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> Sistem Wedding Organizer berjalan dengan baik</p>
                        <p><strong>Database:</strong> <?php echo $dbname; ?></p>
                        <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($_SESSION['ID_Admin']); ?></p>
                        <p><strong>Nama Admin:</strong> <?php echo htmlspecialchars($_SESSION['Nama']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
