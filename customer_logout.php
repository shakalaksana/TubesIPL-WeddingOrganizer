<?php
session_start();

// Hapus semua variabel sesi customer
unset($_SESSION['ID_Pelanggan']);
unset($_SESSION['Nama_Pelanggan']);
unset($_SESSION['Email']);

// Hancurkan sesi
session_destroy();

// Redirect ke halaman utama
header("Location: index.php");
exit();
?>
