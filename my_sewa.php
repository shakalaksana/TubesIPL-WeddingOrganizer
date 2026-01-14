<?php
session_start();
require_once 'config.php';

// Cek apakah customer sudah login
if (!isset($_SESSION['ID_Pelanggan'])) {
    header("Location: customer_login.php");
    exit();
}

$conn = getConnection();

// Ambil data sewa customer
$stmt = $conn->prepare("SELECT * FROM Sewa_Wedding WHERE ID_Pelanggan = ? ORDER BY Tanggal_Sewa DESC");
$stmt->bind_param('i', $_SESSION['ID_Pelanggan']);
$stmt->execute();
$result = $stmt->get_result();
$sewa_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung total sewa
$total_sewa = count($sewa_list);
$total_harga = 0;
foreach ($sewa_list as $sewa) {
    $total_harga += $sewa['Harga_Sewa'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Daftar Sewa Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .badge-lunas {
            background-color: #28a745;
        }
        .badge-belum-lunas {
            background-color: #dc3545;
        }
        .stats-card {
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar wo-navbar">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">🎉 Wedding Organizer</a>
            <div class="navbar-nav ms-auto">
                <a href="customer_dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                <a href="customer_profile.php" class="btn btn-outline-light btn-sm me-2">Profil</a>
                <a href="sewa_wedding.php" class="btn btn-outline-light btn-sm">+ Sewa Baru</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Daftar Sewa Wedding Saya</h2>

        <div class="stats-card">
            <div class="row text-center">
                <div class="col-md-4">
                    <h3><?php echo $total_sewa; ?></h3>
                    <p class="mb-0">Total Sewa</p>
                </div>
                <div class="col-md-4">
                    <h3>Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></h3>
                    <p class="mb-0">Total Harga</p>
                </div>
                <div class="col-md-4">
                    <h3><?php 
                        $lunas = 0;
                        foreach ($sewa_list as $sewa) {
                            if ($sewa['Pembayaran'] == 'Lunas') $lunas++;
                        }
                        echo $lunas;
                    ?></h3>
                    <p class="mb-0">Sewa Lunas</p>
                </div>
            </div>
        </div>

        <?php if (empty($sewa_list)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <h4 class="text-muted">Belum ada sewa wedding</h4>
                    <p class="text-muted">Mulai sewa wedding Anda sekarang</p>
                    <a href="sewa_wedding.php" class="btn btn-primary">Sewa Wedding Baru</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($sewa_list as $sewa): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">ID Sewa: #<?php echo $sewa['ID_Sewa']; ?></h5>
                        <span class="badge <?php echo $sewa['Pembayaran'] == 'Lunas' ? 'badge-lunas' : 'badge-belum-lunas'; ?> px-3 py-2">
                            <?php echo $sewa['Pembayaran']; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($sewa['Nama_Pelanggan']); ?></p>
                                <p><strong>Alamat Acara:</strong> <?php echo htmlspecialchars($sewa['Alamat']); ?></p>
                                <p><strong>Harga Sewa:</strong> Rp <?php echo number_format($sewa['Harga_Sewa'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tanggal Sewa:</strong> <?php echo date('d F Y', strtotime($sewa['Tanggal_Sewa'])); ?></p>
                                <p><strong>Tanggal Acara:</strong> <?php echo date('d F Y', strtotime($sewa['Tanggal_Acara'])); ?></p>
                                <?php if (!empty($sewa['Keterangan'])): ?>
                                    <p><strong>Keterangan:</strong> <?php echo htmlspecialchars($sewa['Keterangan']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
