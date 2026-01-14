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
    <style>
        .navbar-brand {
            font-size: 20px;
        }
        .welcome-card {
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.95) 0%, rgba(244, 114, 182, 0.95) 55%, rgba(251, 113, 133, 0.95) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 18px 60px rgba(17, 24, 39, 0.14);
        }
        .stat-card {
            border-radius: 15px;
            box-shadow: 0 18px 60px rgba(17, 24, 39, 0.10);
            transition: transform 0.2s;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .btn-custom {
            background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg wo-navbar">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">🎉 Wedding Organizer</a>
            <div class="navbar-nav ms-auto align-items-center flex-row gap-2">
                <a href="customer_dashboard.php" class="btn btn-outline-light btn-sm">Home</a>
                <a href="my_sewa.php" class="btn btn-outline-light btn-sm">Sewa Saya</a>
                <a href="customer_profile.php" class="btn btn-outline-light btn-sm">Profil</a>
                <span class="navbar-text text-white small d-none d-md-inline">Halo, <?php echo htmlspecialchars($customer['Nama_Pelanggan']); ?>!</span>
                <a href="customer_logout.php" class="btn btn-light btn-sm text-dark">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="welcome-card">
            <h2>Dashboard Customer</h2>
            <p class="mb-0">Kelola penyewaan wedding Anda dengan mudah</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h1 class="text-primary"><?php echo $total_sewa; ?></h1>
                        <p class="text-muted mb-0">Total Sewa Wedding</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <a href="sewa_wedding.php" class="btn btn-custom w-100">+ Sewa Wedding Baru</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <a href="my_sewa.php" class="btn btn-outline-primary w-100">Lihat Sewa Saya</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Akun</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($customer['Nama_Pelanggan']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['Email']); ?></p>
                                <p><strong>No. Telepon:</strong> <?php echo htmlspecialchars($customer['No_Telepon']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Jenis Kelamin:</strong> <?php echo htmlspecialchars($customer['Jenis_Kelamin']); ?></p>
                                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($customer['Alamat']); ?></p>
                                <p><strong>ID Pelanggan:</strong> <?php echo $customer['ID_Pelanggan']; ?></p>
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
