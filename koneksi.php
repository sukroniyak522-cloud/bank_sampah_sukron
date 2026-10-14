<?php
// koneksi.php
// Konfigurasi koneksi ke database bank_sampah_sukron

$host     = "localhost";
$username = "root";
$password = "";
$database = "bank_sampah_sukron";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Pastikan karakter UTF-8 supaya huruf/emoji tidak jadi tanda tanya
mysqli_set_charset($koneksi, "utf8mb4");

// Zona waktu WIB, supaya tanggal transaksi sesuai jam Indonesia
date_default_timezone_set("Asia/Jakarta");
mysqli_query($koneksi, "SET time_zone = '+07:00'");
