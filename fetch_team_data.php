<?php
include('koneksi.php');
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM football_teams WHERE id = '$id'";
    $result = mysqli_query($koneksi, $sql);
    $data = mysqli_fetch_assoc($result);
    echo json_encode($data);
} else {
    echo json_encode([]);
}
