<?php
include('koneksi.php');

$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id) {
    $sql = "SELECT * FROM football_teams WHERE id = '$id'";
    $result = mysqli_query($koneksi, $sql);
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode($data);  // Kirim data dalam format JSON
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
}
