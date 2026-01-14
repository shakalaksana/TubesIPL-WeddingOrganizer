<?php
session_start();
require_once 'config.php';

// Cek apakah customer sudah login
if (!isset($_SESSION['ID_Pelanggan'])) {
    header("Location: customer_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Ambil data customer untuk form
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM Pelanggan WHERE ID_Pelanggan = ?");
$stmt->bind_param('i', $_SESSION['ID_Pelanggan']);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggan = $_SESSION['ID_Pelanggan'];
    $nama_pelanggan = trim($_POST['Nama_Pelanggan']);
    $alamat = trim($_POST['Alamat']);
    $harga_sewa = $_POST['Harga_Sewa'];
    $tanggal_sewa = $_POST['Tanggal_Sewa'];
    $tanggal_acara = $_POST['Tanggal_Acara'];
    $keterangan = trim($_POST['Keterangan']);
    $pembayaran = 'Belum Lunas'; // Default pembayaran

    // Validasi
    if (empty($nama_pelanggan) || empty($alamat) || empty($harga_sewa) || empty($tanggal_sewa) || empty($tanggal_acara)) {
        $error_message = "Harap isi semua field yang wajib!";
    } elseif ($harga_sewa <= 0) {
        $error_message = "Harga sewa harus lebih dari 0!";
    } elseif (strtotime($tanggal_acara) < strtotime($tanggal_sewa)) {
        $error_message = "Tanggal acara tidak boleh sebelum tanggal sewa!";
    } else {
        // Insert data sewa
        $stmt = $conn->prepare("INSERT INTO Sewa_Wedding (ID_Pelanggan, Nama_Pelanggan, Alamat, Harga_Sewa, Pembayaran, Tanggal_Sewa, Tanggal_Acara, Keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issdssss', $id_pelanggan, $nama_pelanggan, $alamat, $harga_sewa, $pembayaran, $tanggal_sewa, $tanggal_acara, $keterangan);
        
        if ($stmt->execute()) {
            $success_message = "Sewa wedding berhasil ditambahkan!";
            // Reset form
            $_POST = array();
        } else {
            $error_message = "Gagal menambahkan sewa: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Sewa Wedding</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .form-container {
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #333;
        }
        .btn-submit {
            background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar wo-navbar">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">🎉 Wedding Organizer</a>
            <div class="navbar-nav ms-auto">
                <a href="customer_dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                <a href="customer_profile.php" class="btn btn-outline-light btn-sm">Profil</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container wo-card">
            <div class="form-header">
                <h2>Form Sewa Wedding</h2>
                <p class="text-muted">Isi formulir untuk menyewa wedding</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?>
                    <br><a href="my_sewa.php" class="alert-link">Lihat daftar sewa saya</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="sewa_wedding.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Nama_Pelanggan" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="Nama_Pelanggan" name="Nama_Pelanggan" 
                            value="<?php echo isset($_POST['Nama_Pelanggan']) ? htmlspecialchars($_POST['Nama_Pelanggan']) : htmlspecialchars($customer['Nama_Pelanggan']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="Tanggal_Sewa" class="form-label">Tanggal Sewa <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="Tanggal_Sewa" name="Tanggal_Sewa" 
                            value="<?php echo isset($_POST['Tanggal_Sewa']) ? htmlspecialchars($_POST['Tanggal_Sewa']) : date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="Alamat" class="form-label">Alamat Acara <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="Alamat" name="Alamat" rows="3" required><?php echo isset($_POST['Alamat']) ? htmlspecialchars($_POST['Alamat']) : htmlspecialchars($customer['Alamat']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Tanggal_Acara" class="form-label">Tanggal Acara <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="Tanggal_Acara" name="Tanggal_Acara" 
                            value="<?php echo isset($_POST['Tanggal_Acara']) ? htmlspecialchars($_POST['Tanggal_Acara']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="Harga_Sewa" class="form-label">Harga Sewa (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="Harga_Sewa" name="Harga_Sewa" 
                            value="<?php echo isset($_POST['Harga_Sewa']) ? htmlspecialchars($_POST['Harga_Sewa']) : ''; ?>" 
                            min="1" step="0.01" placeholder="Contoh: 5000000" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="Keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="Keterangan" name="Keterangan" rows="3" 
                        placeholder="Tambahkan keterangan atau detail tambahan..."><?php echo isset($_POST['Keterangan']) ? htmlspecialchars($_POST['Keterangan']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <p class="text-muted"><small>* Field wajib diisi</small></p>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-submit">Simpan Sewa Wedding</button>
                    <a href="customer_dashboard.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
