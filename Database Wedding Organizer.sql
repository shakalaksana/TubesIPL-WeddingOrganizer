-- Membuat database Wedding Organizer
CREATE DATABASE IF NOT EXISTS WeddingOrganizer;
USE WeddingOrganizer;

-- Tabel Admin
CREATE TABLE Admin (
    ID_Admin INT AUTO_INCREMENT PRIMARY KEY,
    Nama VARCHAR(100) NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL
);

-- Menambahkan data admin default
INSERT INTO Admin (Nama, Username, Password) VALUES 
('Admin', 'admin', 'admin123');

-- Tabel Pelanggan
CREATE TABLE Pelanggan (
    ID_Pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Pelanggan VARCHAR(100) NOT NULL,
    Alamat TEXT NOT NULL,
    Jenis_Kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    No_Telepon VARCHAR(15) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL
);

-- Tabel Sewa Wedding
CREATE TABLE Sewa_Wedding (
    ID_Sewa INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pelanggan INT NOT NULL,
    Nama_Pelanggan VARCHAR(100) NOT NULL,
    Alamat TEXT NOT NULL,
    Harga_Sewa DECIMAL(10,2) NOT NULL,
    Pembayaran ENUM('Lunas', 'Belum Lunas') NOT NULL DEFAULT 'Belum Lunas',
    Tanggal_Sewa DATE NOT NULL,
    Tanggal_Acara DATE NOT NULL,
    Keterangan TEXT,
    FOREIGN KEY (ID_Pelanggan) REFERENCES Pelanggan(ID_Pelanggan) ON DELETE CASCADE
);

-- Tabel Registrasi
CREATE TABLE Registrasi (
    ID_Pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Pelanggan VARCHAR(100) NOT NULL,
    Jenis_Kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Alamat TEXT NOT NULL,
    No_Telepon VARCHAR(15) NOT NULL UNIQUE
);
