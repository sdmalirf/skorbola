<?php
session_start();
include('koneksi.php');

// Ambil data pertandingan dari database
$sql = "SELECT team1.team_name AS team1_name, team2.team_name AS team2_name, match_table.match_score, match_table.match_date, team1.logo AS team1_logo, team2.logo AS team2_logo
        FROM football_matches AS match_table
        JOIN football_teams AS team1 ON match_table.team_id = team1.id
        JOIN football_teams AS team2 ON match_table.opponent_team_id = team2.id";
$result = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tim Sepak Bola</title>
    <link href="public/css/styles.css" rel="stylesheet">
</head>

<body class="bg-gray-100 w-full min-h-screen mx-auto text-gray-800 p-5 overflow-x-hidden relative ">
    <nav class="bg-white w-3/4 mx-auto border-2 border-black flex justify-between items-center px-6 py-4 rounded-md">
        <div class="text-xl font-bold">Hasil Pertandingan Bola</div>
    </nav>
    <img src="./bg-dashboard.jpg" alt="" class="absolute top-0 left-0 object-cover -z-10">
    <img src="./ball.png" alt="" class="fixed -bottom-10 -left-24 w-[256px]">
    <img src="./field.png" alt="" class="fixed -bottom-28 -right-24 w-[516px]">


    <div class="flex flex-col w-3/4 mx-auto mt-4">
        <?php while ($match = mysqli_fetch_assoc($result)) : ?>
            <div class="flex w-full py-4 px-8 bg-white mb-4 border-2 border-black rounded-lg shadow-md">
                <!-- Logo Tim 1 -->
                <div class="flex items-center">
                    <!-- <img src="uploads/<?php echo htmlspecialchars($match['team1_logo']); ?>" alt="Logo Tim 1" class="w-12 h-12 mr-4"> -->
                    <p class="font-semibold"><?php echo htmlspecialchars($match['team1_name']); ?></p>
                </div>

                <!-- Skor Pertandingan -->
                <div class="flex items-center mx-8 w-fit">
                    <?php
                    // Assuming match_score is in the format "score1-score2"
                    $scores = explode('-', $match['match_score']);
                    $score_team1 = $scores[0];
                    $score_team2 = $scores[1];
                    ?>
                    <p class="text-xl font-bold w-full"><?php echo $score_team1; ?> <?php echo $score_team2; ?></p>
                </div>

                <!-- Logo Tim 2 -->
                <div class="flex items-center">
                    <p class="font-semibold"><?php echo htmlspecialchars($match['team2_name']); ?></p>
                    <!-- <img src="uploads/<?php echo htmlspecialchars($match['team2_logo']); ?>" alt="Logo Tim 2" class="w-12 h-12 ml-4"> -->
                </div>

                <!-- Tanggal Pertandingan -->
                <div class="flex w-full justify-end items-center">
                    <p class="text-lg text-gray-600"><?php echo date('d-m-Y', strtotime($match['match_date'])); ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

</body>

</html>