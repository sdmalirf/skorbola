<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "final_project";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Mengecek apakah koneksi berhasil
if (!$koneksi) {
    die("Tidak bisa terkoneksi ke database");
}
?>