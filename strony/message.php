<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}

$login = $_SESSION['user_id'];

require_once("../connected.php");
// Zapytanie SQL do pobrania wiadomości
$sql = "SELECT date, message FROM messages WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $login); // "i" oznacza, że przekazujemy liczbę całkowitą (integer)
$stmt->execute();
$result = $stmt->get_result();

// Przygotowanie danych do wykorzystania w innym miejscu
$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Zamknięcie połączenia z bazą danych
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <?php include '../template/css.php'; ?>
    <title>Wiadomosci</title>
</head>
<body>
    <?php include '../template/header.php'; ?>
    
    <div class="container">
        <div class="row mt-5 pt-3 px-3">
            <table class="table table-striped table-hover">
                <thead>
                    <th scope="col">ID</th>
                    <th scope="col">Data</th>
                    <th scope="col">Powiadomienie</th>
                    
                </thead>
                <tbody>
                <?php
                    
                    $id = 1;
                    foreach ($data as $message) {
                        echo '<tr>';
                        echo '<th scope="row">'. $id .'</th>';
                        echo '<th >'. $message['date'] .'</th>';
                        echo '<th >'. $message['message'] .'</th>';
                        echo '</tr>';
                        $id++;
                    }
                ?>
            </table>
        </div>
    </div>
</body>

<?php include '../template/script.php'; ?>
</html>
