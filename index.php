<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Organizer - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/theme.css" rel="stylesheet">
    <style>
        .home-container {
            border-radius: 20px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .home-container h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .home-container p {
            color: #6c757d;
            margin-bottom: 40px;
        }
        .btn-custom {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            font-size: 18px;
            border-radius: 10px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="wo-center">
        <div class="home-container wo-card">
        <h1>🎉 Wedding Organizer</h1>
        <p>Sistem Manajemen Penyewaan Wedding</p>
        
        <div class="d-grid gap-2">
            <a href="login.php" class="btn wo-btn-dark btn-custom">Login Admin</a>
            <a href="customer_login.php" class="btn wo-btn-primary btn-custom">Login Customer</a>
            <a href="register.php" class="btn btn-outline-primary btn-custom">Registrasi Customer</a>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
