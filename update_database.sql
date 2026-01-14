-- Script untuk update database yang sudah ada
-- Jalankan script ini jika database sudah pernah diimport sebelumnya
-- Script ini menambahkan kolom Password ke tabel Pelanggan dan menambahkan kolom ke Sewa_Wedding
-- CATATAN: Jika kolom sudah ada, script ini akan error. Solusi: Drop dan reimport database yang baru

USE WeddingOrganizer;

-- Tambahkan kolom Password ke tabel Pelanggan
-- Hapus baris ini jika kolom sudah ada (akan error jika kolom sudah ada)
ALTER TABLE Pelanggan 
ADD COLUMN Password VARCHAR(255) NOT NULL DEFAULT '';

-- Tambahkan kolom ke tabel Sewa_Wedding
-- Hapus baris-baris ini jika kolom sudah ada (akan error jika kolom sudah ada)
ALTER TABLE Sewa_Wedding 
ADD COLUMN Tanggal_Sewa DATE NOT NULL DEFAULT (CURRENT_DATE);

ALTER TABLE Sewa_Wedding 
ADD COLUMN Tanggal_Acara DATE NOT NULL DEFAULT (CURRENT_DATE);

ALTER TABLE Sewa_Wedding 
ADD COLUMN Keterangan TEXT;

-- Update default value Pembayaran
ALTER TABLE Sewa_Wedding 
MODIFY COLUMN Pembayaran ENUM('Lunas', 'Belum Lunas') NOT NULL DEFAULT 'Belum Lunas';

-- CATATAN PENTING:
-- Jika database sudah ada dan script ini error karena kolom sudah ada,
-- SOLUSI TERBAIK: Drop database dan import ulang file "Database Wedding Organizer.sql" yang sudah diupdate
-- Atau hapus kolom yang sudah ada terlebih dahulu, lalu jalankan script ini
