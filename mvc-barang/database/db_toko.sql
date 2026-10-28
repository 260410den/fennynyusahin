CREATE DATABASE IF NOT EXISTS db_toko
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_toko;

CREATE TABLE IF NOT EXISTS barang (
  id_barang INT NOT NULL AUTO_INCREMENT,
  kode_barang VARCHAR(20) NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  kategori VARCHAR(50) NULL,
  harga DECIMAL(12, 2) NOT NULL,
  stok INT NOT NULL,
  PRIMARY KEY (id_barang),
  UNIQUE KEY uq_barang_kode_barang (kode_barang)
) ENGINE=InnoDB;

INSERT IGNORE INTO barang (kode_barang, nama_barang, kategori, harga, stok) VALUES
  ('BRG001', 'Keyboard USB', 'Elektronik', 150000, 20),
  ('BRG002', 'Mouse Wireless', 'Elektronik', 85000, 15),
  ('BRG003', 'Buku Tulis', 'ATK', 5000, 100);
