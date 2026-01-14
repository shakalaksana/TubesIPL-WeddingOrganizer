<?php
// File konfigurasi database
// SESUAIKAN dengan kredensial database Anda!

$db_host = 'localhost';
$db_user = 'root';
// Jika MySQL Anda tidak menggunakan password, biarkan kosong: ''
// Jika MySQL Anda menggunakan password, isi dengan password Anda: 'password_anda'
$db_pass = '';
$db_name = 'WeddingOrganizer';

$dbname = $db_name;

// Fungsi untuk koneksi database
function getConnection() {
    global $db_host, $db_user, $db_pass, $db_name, $dbname;

    $dbname = $db_name;

    $last_error_message = '';
    $conn = null;

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    } catch (mysqli_sql_exception $e) {
        $last_error_message = $e->getMessage();
        $conn = null;
    }

    if ($conn === null || $conn->connect_error) {
        try {
            $server_conn = new mysqli($db_host, $db_user, $db_pass);
            if (!$server_conn->connect_error) {
                $server_conn->select_db($db_name);
                if ($server_conn->error) {
                    die("Database '{$db_name}' tidak ditemukan!<br>Pastikan database sudah diimport.<br>Error: " . $server_conn->error);
                }
                $conn = $server_conn;
            }
        } catch (mysqli_sql_exception $e) {
            $last_error_message = $e->getMessage();
        }
    }

    if ($conn === null || $conn->connect_error) {
        $msg = ($conn !== null && $conn->connect_error) ? $conn->connect_error : $last_error_message;
        if ($msg === '') {
            $msg = 'Unknown error';
        }
        die("Koneksi gagal: " . $msg . "<br><br>Pastikan:<br>1. MySQL sudah running di XAMPP Control Panel<br>2. Database 'WeddingOrganizer' sudah dibuat (import file SQL)<br>3. Username/password MySQL benar (update variabel \$db_user / \$db_pass di config.php)");
    }

    try {
        if (!$conn->set_charset("utf8mb4")) {
            $conn->set_charset("utf8");
        }
    } catch (mysqli_sql_exception $e) {
    }

    return $conn;
}
?>
