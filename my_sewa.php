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
                    <a href="my_sewa.php" class="btn wo-btn-dark btn-sm active">Sewa Saya</a>
                    <a href="customer_profile.php" class="btn wo-btn-dark btn-sm">Profil</a>
                    <a href="sewa_wedding.php" class="btn wo-btn-primary btn-sm">+ Sewa Baru</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in">
            <h2 class="mb-0">Daftar Sewa Wedding Saya 📋</h2>
            <a href="sewa_wedding.php" class="btn wo-btn-primary d-none d-md-block">+ Buat Pesanan Baru</a>
        </div>

        <div class="wo-card p-4 mb-5 animate-fade-in">
            <div class="row text-center">
                <div class="col-md-4 mb-3 mb-md-0 border-end border-secondary border-opacity-25">
                    <h3 class="text-primary fw-bold"><?php echo $total_sewa; ?></h3>
                    <p class="text-muted mb-0 small text-uppercase">Total Pesanan</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0 border-end border-secondary border-opacity-25">
                    <h3 class="text-white fw-bold">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></h3>
                    <p class="text-muted mb-0 small text-uppercase">Total Transaksi</p>
                </div>
                <div class="col-md-4">
                    <h3 class="text-success fw-bold"><?php
                    $lunas = 0;
                    foreach ($sewa_list as $sewa) {
                        if ($sewa['Pembayaran'] == 'Lunas')
                            $lunas++;
                    }
                    echo $lunas;
                    ?></h3>
                    <p class="text-muted mb-0 small text-uppercase">Pesanan Lunas</p>
                </div>
            </div>
        </div>

        <?php if (empty($sewa_list)): ?>
            <div class="wo-card p-5 text-center animate-pop-in">
                <div class="mb-3" style="font-size: 4rem;">📭</div>
                <h4 class="text-white mb-2">Belum ada riwayat sewa</h4>
                <p class="text-muted mb-4">Yuk, mulai rencanakan pernikahan impianmu sekarang!</p>
                <a href="sewa_wedding.php" class="btn wo-btn-primary px-4">Sewa Wedding Baru 🚀</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($sewa_list as $index => $sewa): ?>
                    <div class="col-12 mb-4">
                        <div class="wo-card animate-pop-in" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                            <div
                                class="card-header bg-transparent border-bottom border-secondary border-opacity-25 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="text-muted small text-uppercase me-2">ID Sewa</span>
                                    <span
                                        class="badge bg-dark border border-secondary border-opacity-50 text-white">#<?php echo $sewa['ID_Sewa']; ?></span>
                                </div>
                                <span
                                    class="badge <?php echo $sewa['Pembayaran'] == 'Lunas' ? 'bg-success bg-opacity-75' : 'bg-danger bg-opacity-75'; ?> px-3 py-2 rounded-pill">
                                    <?php echo $sewa['Pembayaran']; ?>
                                </span>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <h5 class="card-title text-white mb-3">
                                            <?php echo htmlspecialchars($sewa['Nama_Pelanggan']); ?></h5>
                                        <div class="d-flex align-items-start gap-2 mb-2">
                                            <span>📍</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($sewa['Alamat']); ?></span>
                                        </div>
                                        <?php if (!empty($sewa['Keterangan'])): ?>
                                            <div class="d-flex align-items-start gap-2">
                                                <span>📝</span>
                                                <span
                                                    class="text-muted fst-italic small"><?php echo htmlspecialchars($sewa['Keterangan']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 mb-3 mb-md-0 border-start border-secondary border-opacity-25 ps-md-4">
                                        <div class="mb-2">
                                            <small class="text-muted d-block">Tanggal Sewa</small>
                                            <span
                                                class="text-white"><?php echo date('d M Y', strtotime($sewa['Tanggal_Sewa'])); ?></span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Tanggal Acara</small>
                                            <span
                                                class="text-white fw-bold"><?php echo date('d M Y', strtotime($sewa['Tanggal_Acara'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <small class="text-muted d-block mb-1">Total Biaya</small>
                                        <h4 class="text-primary fw-bold mb-0">Rp
                                            <?php echo number_format($sewa['Harga_Sewa'], 0, ',', '.'); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>