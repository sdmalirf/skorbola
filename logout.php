<?php
session_start();
session_destroy(); // Menghapus session
header("Location: login.php"); // Redirect ke halaman login setelah logout
exit();
?>