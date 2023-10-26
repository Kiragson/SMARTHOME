<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}
$login = $_SESSION['username'];

require_once("../connected.php");

// Zapytanie SQL do pobrania danych użytkownika
$sql = "SELECT id, first_name, last_name, email, phone_number, rank FROM user WHERE login = '$login'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Dane użytkownika zostały znalezione
    $row = $result->fetch_assoc();
    $imie = $row['first_name'] ?? '---';
    $nazwisko = $row['last_name'] ?? '---';
    $email = $row['email'] ?? '---';
    $telefon = $row['phone_number'] ?? '---';
    $id=$row['id'];
    $ranga= $row['rank'] ??'2';
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Document</title>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class="row justify-content-center mt-5 ">
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h1>Informacje o koncie</h1>
                <div class="p-2 m-2">
                    <p>Nazwa użytkownika <?php echo $login; ?></p>
                    <p>Imię Nazwisko <?php echo $imie; ?> <?php echo $nazwisko; ?></p>
                    <p>Adres email <?php echo $email; ?></p>
                    <p>Nr tel <?php echo $telefon; ?></p>
                </div>

                <!-- Przycisk do zmiany danych użytkownika -->
                <div class="container">
                    <form action="http://localhost/studia/SMARTHOME/strony/zmien_dane.php" method="post">
                        <button class="btn btn-warning rounded p-2 w-100"type="submit">Zmień dane użytkownika</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include '../template/footer.php'; ?>
</body>

<?php include '../template/script.php'; ?>
</html>