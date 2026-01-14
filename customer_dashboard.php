<?php
session_start();
require_once 'config.php';

// Cek apakah customer sudah login
if (!isset($_SESSION['ID_Pelanggan'])) {
    header("Location: customer_login.php");
    exit();
}

$conn = getConnection();

// Ambil data customer
$stmt = $conn->prepare("SELECT * FROM Pelanggan WHERE ID_Pelanggan = ?");
$stmt->bind_param('i', $_SESSION['ID_Pelanggan']);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// Ambil jumlah sewa customer
$stmt_sewa = $conn->prepare("SELECT COUNT(*) as total FROM Sewa_Wedding WHERE ID_Pelanggan = ?");
$stmt_sewa->bind_param('i', $_SESSION['ID_Pelanggan']);
$stmt_sewa->execute();
$result_sewa = $stmt_sewa->get_result();
$total_sewa = $result_sewa->fetch_assoc()['total'];
$stmt_sewa->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Dashboard Customer</title>
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
                    <a href="customer_dashboard.php" class="btn wo-btn-dark btn-sm active">Home</a>
                    <a href="my_sewa.php" class="btn wo-btn-dark btn-sm">Sewa Saya</a>
                    <a href="customer_profile.php" class="btn wo-btn-dark btn-sm">Profil</a>
                    <span class="navbar-text text-white small d-none d-md-inline mx-2">
                        Halo, <span
                            class="text-primary fw-bold"><?php echo htmlspecialchars($customer['Nama_Pelanggan']); ?></span>!
                    </span>
                    <a href="customer_logout.php" class="btn wo-btn-primary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="wo-card p-4 mb-5 animate-fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Dashboard Customer ✨</h2>
                    <p class="text-muted mb-0">Selamat datang kembali! Kelola pernikahan impianmu di sini.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div
                        class="p-3 rounded-4 bg-dark bg-opacity-50 border border-secondary border-opacity-25 d-inline-block">
                        <small class="text-muted d-block text-uppercase" style="letter-spacing: 1px;">ID
                            Pelanggan</small>
                        <span class="fs-5 fw-bold text-white">#<?php echo $customer['ID_Pelanggan']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="wo-card h-100 p-4 text-center animate-pop-in" style="animation-delay: 0.1s;">
                    <div class="mb-3">
                        <span class="display-4 fw-bold text-primary"><?php echo $total_sewa; ?></span>
                    </div>
                    <h5 class="text-white mb-0">Total Sewa Wedding</h5>
                    <p class="text-muted small">Pesanan aktif Anda</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="wo-card h-100 p-4 text-center animate-pop-in" style="animation-delay: 0.2s;">
                    <div class="mb-4" style="font-size: 3rem;">🛍️</div>
                    <a href="sewa_wedding.php" class="btn wo-btn-primary w-100 stretched-link">
                        + Sewa Wedding Baru
                    </a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="wo-card h-100 p-4 text-center animate-pop-in" style="animation-delay: 0.3s;">
                    <div class="mb-4" style="font-size: 3rem;">📋</div>
                    <a href="my_sewa.php" class="btn wo-btn-dark w-100 stretched-link">
                        Lihat Riwayat Sewa
                    </a>
                </div>
            </div>
        </div>

        <div class="row animate-fade-in" style="animation-delay: 0.4s;">
            <div class="col-12">
                <div class="wo-card">
                    <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 p-4">
                        <h5 class="mb-0 text-white">👤 Informasi Akun</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small text-uppercase mb-1">Nama Lengkap</label>
                                    <div class="fs-5 text-white">
                                        <?php echo htmlspecialchars($customer['Nama_Pelanggan']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small text-uppercase mb-1">Email</label>
                                    <div class="fs-5 text-white"><?php echo htmlspecialchars($customer['Email']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-muted small text-uppercase mb-1">No. Telepon</label>
                                    <div class="fs-5 text-white">
                                        <?php echo htmlspecialchars($customer['No_Telepon']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small text-uppercase mb-1">Jenis Kelamin</label>
                                    <div class="fs-5 text-white">
                                        <?php echo htmlspecialchars($customer['Jenis_Kelamin']); ?></div>
                                </div>
                                <div>
                                    <label class="text-muted small text-uppercase mb-1">Alamat</label>
                                    <div class="fs-5 text-white"><?php echo htmlspecialchars($customer['Alamat']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>