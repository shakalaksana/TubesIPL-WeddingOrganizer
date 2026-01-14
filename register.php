<?php
session_start();
require_once 'config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['Nama_Pelanggan']);
    $alamat = trim($_POST['Alamat']);
    $jenis_kelamin = $_POST['Jenis_Kelamin'];
    $email = trim($_POST['Email']);
    $no_telepon = trim($_POST['No_Telepon']);
    $password = $_POST['Password'];
    $confirm_password = $_POST['Confirm_Password'];

    // Validasi
    if (empty($nama) || empty($alamat) || empty($email) || empty($no_telepon) || empty($password)) {
        $error_message = "Harap isi semua field!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter!";
    } else {
        $conn = getConnection();
        
        // Cek email sudah terdaftar
        $check_email = $conn->prepare("SELECT ID_Pelanggan FROM Pelanggan WHERE Email = ?");
        $check_email->bind_param('s', $email);
        $check_email->execute();
        $result_email = $check_email->get_result();
        
        // Cek no telepon sudah terdaftar
        $check_telp = $conn->prepare("SELECT ID_Pelanggan FROM Pelanggan WHERE No_Telepon = ?");
        $check_telp->bind_param('s', $no_telepon);
        $check_telp->execute();
        $result_telp = $check_telp->get_result();
        
        if ($result_email->num_rows > 0) {
            $error_message = "Email sudah terdaftar!";
        } elseif ($result_telp->num_rows > 0) {
            $error_message = "Nomor telepon sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert data
            $stmt = $conn->prepare("INSERT INTO Pelanggan (Nama_Pelanggan, Alamat, Jenis_Kelamin, Email, No_Telepon, Password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $nama, $alamat, $jenis_kelamin, $email, $no_telepon, $hashed_password);
            
            if ($stmt->execute()) {
                $success_message = "Registrasi berhasil! Silakan login.";
                // Reset form
                $_POST = array();
            } else {
                $error_message = "Registrasi gagal: " . $conn->error;
            }
            
            $stmt->close();
        }
        
        $check_email->close();
        $check_telp->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Registrasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .register-container {
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .form-label {
            font-weight: bold;
            color: #333;
        }
        .btn-register {
            background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
        }
        .alert {
            border-radius: 8px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container wo-card">
        <div class="register-header">
            <h1>Registrasi Customer</h1>
            <p class="text-muted">Buat akun baru untuk menyewa wedding</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="Nama_Pelanggan" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="Nama_Pelanggan" name="Nama_Pelanggan" 
                    value="<?php echo isset($_POST['Nama_Pelanggan']) ? htmlspecialchars($_POST['Nama_Pelanggan']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="Alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="Alamat" name="Alamat" rows="3" required><?php echo isset($_POST['Alamat']) ? htmlspecialchars($_POST['Alamat']) : ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="Jenis_Kelamin" class="form-label">Jenis Kelamin</label>
                <select class="form-control" id="Jenis_Kelamin" name="Jenis_Kelamin" required>
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki" <?php echo (isset($_POST['Jenis_Kelamin']) && $_POST['Jenis_Kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="Perempuan" <?php echo (isset($_POST['Jenis_Kelamin']) && $_POST['Jenis_Kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="Email" class="form-label">Email</label>
                <input type="email" class="form-control" id="Email" name="Email" 
                    value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="No_Telepon" class="form-label">No. Telepon</label>
                <input type="text" class="form-control" id="No_Telepon" name="No_Telepon" 
                    value="<?php echo isset($_POST['No_Telepon']) ? htmlspecialchars($_POST['No_Telepon']) : ''; ?>" 
                    placeholder="08xxxxxxxxxx" required>
            </div>

            <div class="mb-3">
                <label for="Password" class="form-label">Password</label>
                <input type="password" class="form-control" id="Password" name="Password" minlength="6" required>
            </div>

            <div class="mb-3">
                <label for="Confirm_Password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="Confirm_Password" name="Confirm_Password" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-register">Daftar</button>
        </form>

        <div class="back-link">
            <p>Sudah punya akun? <a href="customer_login.php">Login di sini</a></p>
            <p><a href="index.php">Kembali ke Home</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
