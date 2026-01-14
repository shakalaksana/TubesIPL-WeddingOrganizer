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
    $id_sewa = isset($_POST['ID_Sewa']) ? (int)$_POST['ID_Sewa'] : 0;
    $return = isset($_POST['return']) ? trim($_POST['return']) : '';

    $redirect = "admin_pesanan.php";
    if ($return !== '') {
        $redirect .= "?" . $return;
        $redirect .= ((strpos($redirect, '?') !== false) && $return !== '') ? "&" : "?";
    } else {
        $redirect .= "?";
    }

    if ($id_sewa <= 0) {
        header("Location: {$redirect}msg=" . urlencode("Permintaan tidak valid.") . "&type=warning");
        exit();
    }

    if ($action === 'update') {
        $pembayaran = isset($_POST['Pembayaran']) ? trim($_POST['Pembayaran']) : '';
        $tanggal_acara = isset($_POST['Tanggal_Acara']) ? trim($_POST['Tanggal_Acara']) : '';
        $keterangan = isset($_POST['Keterangan']) ? trim($_POST['Keterangan']) : '';

        if (!in_array($pembayaran, ['Lunas', 'Belum Lunas'], true)) {
            header("Location: {$redirect}msg=" . urlencode("Status pembayaran tidak valid.") . "&type=warning");
            exit();
        }

        if ($tanggal_acara === '') {
            header("Location: {$redirect}msg=" . urlencode("Tanggal acara wajib diisi.") . "&type=warning");
            exit();
        }

        $stmt = $conn->prepare("UPDATE Sewa_Wedding SET Pembayaran = ?, Tanggal_Acara = ?, Keterangan = ? WHERE ID_Sewa = ?");
        if (!$stmt) {
            header("Location: {$redirect}msg=" . urlencode("Gagal menyimpan perubahan.") . "&type=danger");
            exit();
        }
        $stmt->bind_param('sssi', $pembayaran, $tanggal_acara, $keterangan, $id_sewa);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: {$redirect}msg=" . urlencode("Pesanan berhasil diperbarui.") . "&type=success");
            exit();
        }

        header("Location: {$redirect}msg=" . urlencode("Gagal memperbarui pesanan.") . "&type=danger");
        exit();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM Sewa_Wedding WHERE ID_Sewa = ?");
        if (!$stmt) {
            header("Location: {$redirect}msg=" . urlencode("Gagal menghapus pesanan.") . "&type=danger");
            exit();
        }
        $stmt->bind_param('i', $id_sewa);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: {$redirect}msg=" . urlencode("Pesanan berhasil dihapus.") . "&type=success");
            exit();
        }

        header("Location: {$redirect}msg=" . urlencode("Gagal menghapus pesanan.") . "&type=danger");
        exit();
    }

    header("Location: {$redirect}msg=" . urlencode("Aksi tidak dikenal.") . "&type=warning");
    exit();
}

$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_sewa_from = isset($_GET['sewa_from']) ? trim($_GET['sewa_from']) : '';
$filter_sewa_to = isset($_GET['sewa_to']) ? trim($_GET['sewa_to']) : '';
$filter_acara_from = isset($_GET['acara_from']) ? trim($_GET['acara_from']) : '';
$filter_acara_to = isset($_GET['acara_to']) ? trim($_GET['acara_to']) : '';

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

if ($filter_sewa_from !== '') {
    $where[] = 'sw.Tanggal_Sewa >= ?';
    $params[] = $filter_sewa_from;
    $types .= 's';
}
if ($filter_sewa_to !== '') {
    $where[] = 'sw.Tanggal_Sewa <= ?';
    $params[] = $filter_sewa_to;
    $types .= 's';
}

if ($filter_acara_from !== '') {
    $where[] = 'sw.Tanggal_Acara >= ?';
    $params[] = $filter_acara_from;
    $types .= 's';
}
if ($filter_acara_to !== '') {
    $where[] = 'sw.Tanggal_Acara <= ?';
    $params[] = $filter_acara_to;
    $types .= 's';
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$orders = [];
$sql = "
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
    LIMIT 200
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '' && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $orders = $res->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

$total = count($orders);
$count_lunas = 0;
$count_belum = 0;
$sum_lunas = 0.0;
$sum_total = 0.0;
foreach ($orders as $o) {
    $harga = (float)($o['Harga_Sewa'] ?? 0);
    $sum_total += $harga;
    if (($o['Pembayaran'] ?? '') === 'Lunas') {
        $count_lunas++;
        $sum_lunas += $harga;
    } else {
        $count_belum++;
    }
}

$return_query = $_SERVER['QUERY_STRING'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Kelola Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        .stat-card{border-radius:16px}
        .table td{vertical-align:middle}
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg wo-navbar">
        <div class="container">
            <a class="navbar-brand text-decoration-none" href="dashboard.php">Wedding Organizer</a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="admin_pelanggan.php" class="btn btn-outline-light btn-sm">Pelanggan</a>
                <a href="admin_profile.php" class="btn btn-light btn-sm text-dark">Profil</a>
                <a href="logout.php" class="btn btn-light btn-sm text-dark">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="wo-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="mb-1">Kelola Pesanan</h3>
                    <div class="wo-muted small">Kelola status pembayaran, tanggal acara, dan keterangan pesanan</div>
                </div>
                <div class="text-end">
                    <div class="small wo-muted">Admin: <?php echo htmlspecialchars($_SESSION['Nama'] ?? ''); ?></div>
                    <div class="small wo-muted">ID: <?php echo htmlspecialchars((string)($_SESSION['ID_Admin'] ?? '')); ?></div>
                </div>
            </div>
        </div>

        <?php if ($flash_message !== '' && in_array($flash_type, ['success','danger','warning','info'], true)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash_type); ?>"><?php echo htmlspecialchars($flash_message); ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="wo-card p-3 stat-card h-100">
                    <div class="wo-muted small">Total Data (maks. 200)</div>
                    <div class="fs-3 fw-bold"><?php echo $total; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wo-card p-3 stat-card h-100">
                    <div class="wo-muted small">Lunas</div>
                    <div class="fs-3 fw-bold"><?php echo $count_lunas; ?></div>
                    <div class="wo-muted small">Rp <?php echo number_format($sum_lunas, 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wo-card p-3 stat-card h-100">
                    <div class="wo-muted small">Belum Lunas</div>
                    <div class="fs-3 fw-bold"><?php echo $count_belum; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="wo-card p-3 stat-card h-100">
                    <div class="wo-muted small">Total Nilai</div>
                    <div class="fs-3 fw-bold">Rp <?php echo number_format($sum_total, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <div class="wo-card p-3 mb-3">
            <form class="row g-2 align-items-end" method="GET" action="admin_pesanan.php">
                <div class="col-12 col-md-2">
                    <label class="form-label wo-form-label small mb-1" for="status">Pembayaran</label>
                    <select class="form-select form-select-sm" id="status" name="status">
                        <option value="" <?php echo ($filter_status === '') ? 'selected' : ''; ?>>Semua</option>
                        <option value="Belum Lunas" <?php echo ($filter_status === 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                        <option value="Lunas" <?php echo ($filter_status === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label wo-form-label small mb-1" for="q">Pencarian</label>
                    <input class="form-control form-control-sm" id="q" name="q" value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Nama / email / telepon / alamat / keterangan">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label wo-form-label small mb-1" for="acara_from">Acara Dari</label>
                    <input type="date" class="form-control form-control-sm" id="acara_from" name="acara_from" value="<?php echo htmlspecialchars($filter_acara_from); ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label wo-form-label small mb-1" for="acara_to">Acara Sampai</label>
                    <input type="date" class="form-control form-control-sm" id="acara_to" name="acara_to" value="<?php echo htmlspecialchars($filter_acara_to); ?>">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm wo-btn-primary w-100" type="submit">Filter</button>
                    <a class="btn btn-sm btn-outline-secondary w-100" href="admin_pesanan.php">Reset</a>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label wo-form-label small mb-1" for="sewa_from">Sewa Dari</label>
                    <input type="date" class="form-control form-control-sm" id="sewa_from" name="sewa_from" value="<?php echo htmlspecialchars($filter_sewa_from); ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label wo-form-label small mb-1" for="sewa_to">Sewa Sampai</label>
                    <input type="date" class="form-control form-control-sm" id="sewa_to" name="sewa_to" value="<?php echo htmlspecialchars($filter_sewa_to); ?>">
                </div>
            </form>
        </div>

        <div class="wo-card p-3">
            <?php if (count($orders) === 0): ?>
                <div class="wo-muted">Tidak ada data pesanan.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle wo-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Kontak</th>
                                <th>Tanggal</th>
                                <th>Harga</th>
                                <th>Pembayaran</th>
                                <th>Alamat</th>
                                <th>Keterangan</th>
                                <th style="min-width: 320px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars((string)$o['ID_Sewa']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($o['Nama_Pelanggan']); ?></div>
                                        <div class="wo-muted small">ID Pelanggan: <?php echo htmlspecialchars((string)$o['ID_Pelanggan']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small"><?php echo htmlspecialchars((string)($o['Email'] ?? '')); ?></div>
                                        <div class="wo-muted small"><?php echo htmlspecialchars((string)($o['No_Telepon'] ?? '')); ?></div>
                                    </td>
                                    <td>
                                        <div class="small">Sewa: <?php echo htmlspecialchars((string)($o['Tanggal_Sewa'] ?? '')); ?></div>
                                        <div class="wo-muted small">Acara: <?php echo htmlspecialchars((string)($o['Tanggal_Acara'] ?? '')); ?></div>
                                    </td>
                                    <td>Rp <?php echo number_format((float)($o['Harga_Sewa'] ?? 0), 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if (($o['Pembayaran'] ?? '') === 'Lunas'): ?>
                                            <span class="badge bg-success wo-badge-pill">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark wo-badge-pill">Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars((string)($o['Alamat'] ?? '')); ?></td>
                                    <td class="small"><?php echo htmlspecialchars((string)($o['Keterangan'] ?? '')); ?></td>
                                    <td>
                                        <form method="POST" action="admin_pesanan.php" class="row g-2">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="ID_Sewa" value="<?php echo htmlspecialchars((string)$o['ID_Sewa']); ?>">
                                            <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_query); ?>">
                                            <div class="col-12 col-md-4">
                                                <select name="Pembayaran" class="form-select form-select-sm">
                                                    <option value="Belum Lunas" <?php echo (($o['Pembayaran'] ?? '') === 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                                                    <option value="Lunas" <?php echo (($o['Pembayaran'] ?? '') === 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <input type="date" name="Tanggal_Acara" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($o['Tanggal_Acara'] ?? '')); ?>" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <input type="text" name="Keterangan" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($o['Keterangan'] ?? '')); ?>" placeholder="Keterangan">
                                            </div>
                                            <div class="col-12 d-flex gap-2 justify-content-end">
                                                <button type="submit" class="btn btn-sm wo-btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="admin_pesanan.php" class="mt-2 d-flex justify-content-end" onsubmit="return confirm('Hapus pesanan ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="ID_Sewa" value="<?php echo htmlspecialchars((string)$o['ID_Sewa']); ?>">
                                            <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_query); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
