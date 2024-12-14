<?php
session_start();

include('koneksi.php');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Initialize variables
$team_name = "";
$match_score = "";
$stadium = "";
$match_date = "";
$sukses = "";
$error = "";
$team1_id = '';
$team2_id = '';
$score_team1 = '';
$score_team2 = '';
$match_date = '';



// Get URL and form parameters
$op = isset($_GET['op']) ? $_GET['op'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$search_term = isset($_POST['search_term']) ? $_POST['search_term'] : '';

// Function to get match details
function getMatchDetails($koneksi, $id)
{
    $sql_edit = "SELECT fm.*, t1.team_name AS team1_name, t2.team_name AS team2_name 
                 FROM football_matches fm
                 JOIN football_teams t1 ON fm.team_id = t1.id
                 JOIN football_teams t2 ON fm.opponent_team_id = t2.id
                 WHERE fm.id = ?";
    $stmt_edit = $koneksi->prepare($sql_edit);
    $stmt_edit->bind_param("i", $id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();

    if ($result_edit->num_rows > 0) {
        $edit_data = $result_edit->fetch_assoc();
        $edit_data['score_team1'] = explode("-", $edit_data['match_score'])[0];
        $edit_data['score_team2'] = explode("-", $edit_data['match_score'])[1];
        return $edit_data;
    }
    return null;
}

// Handle fetch match data for edit
if (isset($_GET['fetch_match']) && $op == 'edit' && $id) {
    header('Content-Type: application/json');
    $match_details = getMatchDetails($koneksi, $id);
    echo json_encode($match_details);
    exit;
}

// Delete Operation
if ($op == 'delete' && $id) {
    $sql1 = "DELETE FROM football_matches WHERE id = ?";
    $stmt = $koneksi->prepare($sql1);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $sukses = "Berhasil hapus data";
    } else {
        $error = "Gagal menghapus data";
    }
    $stmt->close();
}

// Edit Operation - Retrieve match details
if ($op == 'edit' && $id) {
    $edit_data = getMatchDetails($koneksi, $id);

    if ($edit_data) {
        $team1_id = $edit_data['team_id'];
        $team2_id = $edit_data['opponent_team_id'];
        $score_team1 = $edit_data['score_team1'];
        $score_team2 = $edit_data['score_team2'];
        $match_date = $edit_data['match_date'];
        $stadium = $edit_data['stadium'];
    }
}

// Create Team Operation
if (isset($_POST['simpan_tim'])) {
    $team_name = $_POST['team_name'];
    $stadium = $_POST['stadium'];
    $logo_path = '';

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo_name = $_FILES['logo']['name'];
        $logo_tmp = $_FILES['logo']['tmp_name'];
        $logo_path = 'uploads/' . $logo_name;

        // Ensure uploads directory exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }

        // Move logo to 'uploads' folder
        if (!move_uploaded_file($logo_tmp, $logo_path)) {
            $error = "Logo gagal di-upload. Cek apakah folder 'uploads' memiliki izin tulis yang benar.";
        }
    }

    if ($team_name && $stadium) {
        // Insert new team data
        $sql1 = "INSERT INTO football_teams (team_name, stadium, logo) VALUES (?, ?, ?)";
        $stmt = $koneksi->prepare($sql1);
        $stmt->bind_param("sss", $team_name, $stadium, $logo_path);
        if ($stmt->execute()) {
            $sukses = "Berhasil memasukkan data tim baru";
            echo "<script>window.location.href = 'dashboard.php';</script>";
        } else {
            $error = "Gagal memasukkan data tim";
        }
        $stmt->close();
    } else {
        $error = "Silakan masukkan semua data tim";
    }
}

// Match Operation (Create/Update)
if (isset($_POST['simpan_match'])) {
    $team1_id = $_POST['team1_id'];
    $team2_id = $_POST['team2_id'];
    $match_date = $_POST['match_date'];
    $stadium = $_POST['stadium'];
    $score_team1 = $_POST['score_team1'];
    $score_team2 = $_POST['score_team2'];

    // Identify if this is an edit or new match
    $edit_id = isset($_POST['id']) ? $_POST['id'] : null;

    if ($team1_id && $team2_id && $match_date && $stadium && $score_team1 !== null && $score_team2 !== null) {
        if ($edit_id) {
            // Update existing match
            $sql2 = "UPDATE football_matches SET 
                        team_id = ?, 
                        opponent_team_id = ?, 
                        match_score = ?, 
                        match_date = ?, 
                        stadium = ? 
                    WHERE id = ?";
            $stmt = $koneksi->prepare($sql2);
            $match_score = "$score_team1-$score_team2";
            $stmt->bind_param("iisssi", $team1_id, $team2_id, $match_score, $match_date, $stadium, $edit_id);
        } else {
            // Insert new match
            $sql2 = "INSERT INTO football_matches (team_id, opponent_team_id, match_score, match_date, stadium) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($sql2);
            $match_score = "$score_team1-$score_team2";
            $stmt->bind_param("iisss", $team1_id, $team2_id, $match_score, $match_date, $stadium);
        }

        if ($stmt->execute()) {
            $sukses = $edit_id ? "Berhasil memperbarui data pertandingan" : "Berhasil memasukkan data pertandingan baru";
            echo "<script>window.location.href = 'dashboard.php';</script>";
        } else {
            $error = $edit_id ? "Gagal memperbarui data pertandingan" : "Gagal memasukkan data pertandingan";
        }
        $stmt->close();
    } else {
        $error = "Silakan masukkan semua data pertandingan";
    }
}

// Fetch teams for dropdowns
$sql_tim = "SELECT * FROM football_teams";
$result_tim = mysqli_query($koneksi, $sql_tim);

// Fetch matches (with search functionality)
$sql_match = "SELECT fm.*, t1.team_name AS team1_name, t2.team_name AS team2_name
              FROM football_matches fm
              JOIN football_teams t1 ON fm.team_id = t1.id
              JOIN football_teams t2 ON fm.opponent_team_id = t2.id";

if ($search_term) {
    $sql_match .= " WHERE t1.team_name LIKE '%$search_term%' OR t2.team_name LIKE '%$search_term%'";
}

$sql_match .= " ORDER BY fm.match_date DESC";
$result_match = mysqli_query($koneksi, $sql_match);
?>

<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tim Sepak Bola</title>
    <link href="public/css/styles.css" rel="stylesheet">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-gray-100 w-full text-gray-800 p-5 overflow-x-hidden">
    <img src="./bg-dashboard.jpg" alt="" class="absolute top-0 left-0 object-cover w-full -z-10">
    <nav class="bg-white w-full flex justify-between items-center px-6 py-4 rounded-lg">
        <div class="text-xl font-bold">Selamat datang, <?php echo htmlspecialchars($username); ?></div>
        <div>
            <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded">Logout</a>
        </div>
    </nav>

    <div class="flex items-center w-full justify-between mt-4">
        <!-- Search Form -->
        <form action="" method="POST" class="mb-4 flex w-1/2 gap-2 justify-center items-center">
            <input type="text" name="search_term" placeholder="Cari Pertanding..." class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="px-4 py-2 bg-white font-semibold text-black rounded">Cari</button>
        </form>
        <div>
            <button id="openModal" class="px-4 py-2 bg-white font-semibold text-black rounded mb-4" onclick="openModal('add')">Tambah Tim Sepak Bola</button>
            <button id="openMatchModal" class="px-4 py-2 bg-white font-semibold text-black rounded mb-4" onclick="openModal('add_match')">Tambah Pertandingan</button>
        </div>
    </div>

    <div class="w-full mx-auto rounded-xl overflow-hidden">
        <!-- Add Tim Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('add')">&times;</span>
                <h3 class="text-xl font-bold mb-4">Tambah Data Tim Sepak Bola</h3>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="team_name" class="block font-medium">Nama Tim</label>
                        <input type="text" id="team_name" name="team_name" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="stadium" class="block font-medium">Stadion</label>
                        <input type="text" id="stadium" name="stadium" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="logo" class="block font-medium">Logo</label>
                        <input type="file" id="logo" name="logo" class="w-full p-2 border rounded">
                    </div>
                    <button type="submit" name="simpan_tim" class="px-4 py-2 bg-green-500 text-white rounded">Simpan Data</button>
                </form>
            </div>
        </div>

        <!-- Edit Match Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('edit')">&times;</span>
                <h3 class="text-xl font-bold mb-4">Edit Pertandingan</h3>
                <!-- <form action="" method="POST">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <label for="team1_id" class="block font-medium">Tim 1</label>
                    <select name="team1_id" id="team1_id" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Tim 1</option>
                        <?php
                        $result_tim = mysqli_query($koneksi, "SELECT * FROM football_teams");
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo $team['id']; ?>"
                                <?php echo (isset($team1_id) && $team1_id == $team['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="score_team1" class="block font-medium">Skor Tim 1</label>
                    <input type="number" id="score_team1" name="score_team1" class="w-full p-2 border rounded"
                        value="<?php echo htmlspecialchars($score_team1); ?>" required>

                    <label for="team2_id" class="block font-medium">Tim 2</label>
                    <select name="team2_id" id="team2_id" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Tim 2</option>
                        <?php
                        mysqli_data_seek($result_tim, 0);
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo $team['id']; ?>"
                                <?php echo ($team2_id == $team['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="score_team2" class="block font-medium">Skor Tim 2</label>
                    <input type="number" id="score_team2" name="score_team2" class="w-full p-2 border rounded"
                        value="<?php echo htmlspecialchars($score_team2); ?>" required>

                    <label for="match_date" class="block font-medium">Tanggal Pertandingan</label>
                    <input type="date" id="match_date" name="match_date" class="w-full p-2 border rounded"
                        value="<?php echo htmlspecialchars($match_date); ?>" required>

                    <label for="stadium" class="block font-medium">Stadion</label>
                    <select id="stadium" name="stadium" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Stadion</option>
                        <?php
                        mysqli_data_seek($result_tim, 0);
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo htmlspecialchars($team['stadium']); ?>"
                                <?php echo ($stadium == $team['stadium']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['stadium']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" name="simpan_match" class="px-4 py-2 bg-green-500 text-white rounded">Simpan Pertandingan</button>
                </form> -->
                <form action="" method="POST">
                    <input type="hidden" id="edit_match_id" name="id" value="<?php echo $id; ?>">

                    <label for="team1_id_edit" class="block font-medium">Tim 1</label>
                    <select name="team1_id" id="team1_id_edit" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Tim 1</option>
                        <?php
                        $result_tim = mysqli_query($koneksi, "SELECT * FROM football_teams");
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo $team['id']; ?>"
                                <?php echo (isset($team1_id) && $team1_id == $team['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="score_team1_edit" class="block font-medium">Skor Tim 1</label>
                    <input type="number" id="score_team1_edit" name="score_team1" class="w-full p-2 border rounded" required>

                    <label for="team2_id_edit" class="block font-medium">Tim 2</label>
                    <select name="team2_id" id="team2_id_edit" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Tim 2</option>
                        <?php
                        mysqli_data_seek($result_tim, 0);
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo $team['id']; ?>"
                                <?php echo ($team2_id == $team['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="score_team2_edit" class="block font-medium">Skor Tim 2</label>
                    <input type="number" id="score_team2_edit" name="score_team2" class="w-full p-2 border rounded" required>

                    <label for="match_date_edit" class="block font-medium">Tanggal Pertandingan</label>
                    <input type="date" id="match_date_edit" name="match_date" class="w-full p-2 border rounded" required>

                    <label for="stadium_edit" class="block font-medium">Stadion</label>
                    <select id="stadium_edit" name="stadium" class="w-full p-2 border rounded" required>
                        <option value="">Pilih Stadion</option>
                        <?php
                        mysqli_data_seek($result_tim, 0);
                        while ($team = mysqli_fetch_assoc($result_tim)) :
                        ?>
                            <option value="<?php echo htmlspecialchars($team['stadium']); ?>"
                                <?php echo ($stadium == $team['stadium']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['stadium']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" name="simpan_match" class="mt-4 px-4 py-2 bg-green-500 text-white rounded">Simpan Pertandingan</button>
                </form>
            </div>
        </div>



        <!-- Add Match Modal -->
        <div id="addMatchModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('add_match')">&times;</span>
                <h3 class="text-xl font-bold mb-4">Tambah Pertandingan</h3>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="team1_id" class="block font-medium">Tim 1</label>
                        <select name="team1_id" id="team1_id" class="w-full p-2 border rounded" required onchange="autoFillStadium()">
                            <option value="">Pilih Tim 1</option>
                            <?php
                            // Menampilkan daftar tim dari database
                            $sql_tim = "SELECT * FROM football_teams";
                            $result_tim = mysqli_query($koneksi, $sql_tim);
                            if ($result_tim) {
                                while ($team = mysqli_fetch_assoc($result_tim)) :
                            ?>
                                    <option value="<?php echo $team['id']; ?>" data-stadium="<?php echo $team['stadium']; ?>">
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </option>
                            <?php
                                endwhile;
                            } else {
                                echo "<option disabled>No teams available</option>"; // Menampilkan jika tidak ada tim
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="score_team1" class="block font-medium">Skor Tim 1</label>
                        <input type="number" id="score_team1" name="score_team1" class="w-full p-2 border rounded" required>
                    </div>

                    <div class="mb-4">
                        <label for="team2_id" class="block font-medium">Tim 2</label>
                        <select name="team2_id" id="team2_id" class="w-full p-2 border rounded" required>
                            <option value="">Pilih Tim 2</option>
                            <?php
                            // Menampilkan daftar tim untuk Tim 2
                            if ($result_tim) {
                                mysqli_data_seek($result_tim, 0); // Mengatur ulang pointer hasil query
                                while ($team = mysqli_fetch_assoc($result_tim)) :
                            ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="score_team2" class="block font-medium">Skor Tim 2</label>
                        <input type="number" id="score_team2" name="score_team2" class="w-full p-2 border rounded" required>
                    </div>

                    <div class="mb-4">
                        <label for="match_date" class="block font-medium">Tanggal Pertandingan</label>
                        <input type="date" id="match_date" name="match_date" class="w-full p-2 border rounded" required>
                    </div>

                    <div class="mb-4">
                        <label for="stadium" class="block font-medium">Stadion</label>
                        <select id="stadium" name="stadium" class="w-full p-2 border rounded" required>
                            <option value="">Pilih Stadion</option>
                            <?php
                            // Menampilkan daftar tim untuk Tim 2
                            if ($result_tim) {
                                mysqli_data_seek($result_tim, 0); // Mengatur ulang pointer hasil query
                                while ($team = mysqli_fetch_assoc($result_tim)) :
                            ?>
                                    <option value="<?php echo htmlspecialchars($team['stadium']); ?>">
                                        <?php echo htmlspecialchars($team['stadium']); ?>
                                    </option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="simpan_match" class="px-4 py-2 bg-green-500 text-white rounded">Simpan Pertandingan</button>
                </form>
            </div>
        </div>


        <table class="bg-white w-full mt-4 rounded-lg shadow-md">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="p-4">#</th>
                    <th class="p-4">Tim 1</th>
                    <th class="p-4">Tim 2</th>
                    <th class="p-4">Skor</th>
                    <th class="p-4">Stadion</th>
                    <th class="p-4">Tanggal</th>
                    <th class="p-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_match) : ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result_match)) : ?>
                        <tr>
                            <td class="p-4 text-center"><?php echo $no++; ?></td>
                            <td class="p-4 text-center"><?php echo htmlspecialchars($row['team1_name']); ?></td>
                            <td class="p-4 text-center"><?php echo htmlspecialchars($row['team2_name']); ?></td>
                            <td class="p-4 text-center"><?php echo htmlspecialchars($row['match_score']); ?></td>
                            <td class="p-4 text-center"><?php echo htmlspecialchars($row['stadium']); ?></td>
                            <td class="p-4 text-center"><?php echo htmlspecialchars($row['match_date']); ?></td>
                            <td class="p-4 text-center">
                                <button onclick="openModal('edit', <?php echo $row['id']; ?>)" class="px-4 py-2 bg-yellow-500 text-white rounded">Edit</button>
                                <a href="dashboard.php?op=delete&id=<?php echo $row['id']; ?>" class="px-4 py-2 bg-red-500 text-white rounded" onclick="return confirm('Yakin ingin menghapus data?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>

<script>
    function openModal(action, id = null) {
        if (action === 'add') {
            document.getElementById('addModal').style.display = 'block';
        } else if (action === 'edit') {
            // Fetch match data and populate edit modal
            fetch(`dashboard.php?fetch_match=1&op=edit&id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Check if data exists before populating
                    if (data) {
                        document.getElementById('edit_match_id').value = id;

                        // Populate team dropdowns
                        const team1Select = document.getElementById('team1_id_edit');
                        const team2Select = document.getElementById('team2_id_edit');

                        // Set selected team for both dropdowns
                        for (let option of team1Select.options) {
                            if (option.value == data.team_id) {
                                option.selected = true;
                                break;
                            }
                        }

                        for (let option of team2Select.options) {
                            if (option.value == data.opponent_team_id) {
                                option.selected = true;
                                break;
                            }
                        }

                        // Populate other fields
                        document.getElementById('score_team1_edit').value = data.score_team1;
                        document.getElementById('score_team2_edit').value = data.score_team2;
                        document.getElementById('match_date_edit').value = data.match_date;
                        document.getElementById('stadium_edit').value = data.stadium;

                        // Show the edit modal
                        document.getElementById('editModal').style.display = 'block';
                    } else {
                        alert('Data pertandingan tidak ditemukan');
                    }
                })
                .catch(error => {
                    console.error('Error fetching match data:', error);
                    alert('Gagal mengambil data pertandingan');
                });
        } else if (action === 'add_match') {
            document.getElementById('addMatchModal').style.display = 'block';
        }
    }

    function closeModal(action) {
        if (action === 'add') {
            document.getElementById('addModal').style.display = 'none';
        } else if (action === 'edit') {
            document.getElementById('editModal').style.display = 'none';
        } else if (action === 'add_match') {
            document.getElementById('addMatchModal').style.display = 'none';
        }
    }
</script>