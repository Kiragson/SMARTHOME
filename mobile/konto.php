<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}
$login = $_SESSION['username'];

require_once("connected.php");

// Zapytanie SQL do pobrania danych użytkownika
$sql = "SELECT id, imie, nazwisko, email, telefon, Role as ranga FROM user WHERE login = '$login'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Dane użytkownika zostały znalezione
    $row = $result->fetch_assoc();
    $imie = $row['imie'] ?? '---';
    $nazwisko = $row['nazwisko'] ?? '---';
    $email = $row['email'] ?? '---';
    $telefon = $row['telefon'] ?? '---';
    $id=$row['id'];
    $ranga= $row['ranga'] ??'2';
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="../template/style.css"rel="stylesheet">

    <?php include 'http://localhost/studia/SMARTHOME/template/css.php'; ?>
    <title>Document</title>
</head>
<body>
    <div class="py-2 px-3">
        <h1>Informacje o koncie</h1>
    </div>
    <div class="navbar-light pt-3 pb-3 px-3 rounded h-100" style="background-color: #e3f2fd;">
        <div class="mb-3 row">
            <div class="col-5">
                <p class="small">Login</p>
            </div>
            <div class="col-5">
                <p class=" small text-dark "><?php echo $login; ?></p>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="col-5">
                <p class="small">Hasło</p>
            </div>
            <div class="col-5">
                <p class=" small text-dark ">******</p>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="col-5">
                <p class="small">Imię Nazwisko</p>
            </div>
            <div class="col-5">
                <p class=" small text-dark "><?php echo $imie; ?> <?php echo $nazwisko; ?></p>
            </div>
             
        </div>
        <div class="mb-3 row">
            <div class="col-5">
                <p class="small">Email</p>
            </div>
            <div class="col-5">
                <p class=" small text-dark "><?php echo $email; ?></p>
            </div>
        </div>
        <div class=" row">
            <div class="col-5">
                <p class="small">Telefon</p>
            </div>
            <div class="col-5">
                <p class=" small text-dark "><?php echo $telefon; ?></p>
            </div>
        </div>
        
    </div>
    <div class="py-3 px-3">

        <!-- Przycisk do zmiany danych użytkownika -->
        <div class="container mt-2">
            <form action="http://localhost/studia/SMARTHOME/strony/zmien_dane.php" method="post">
                <button class="btn btn-warning rounded p-2 w-100"type="submit">Zmień dane użytkownika</button>
            </form>
        </div>
        <div class="container mt-2">
            <form action="http://localhost/studia/SMARTHOME/strony/zmien_dane.php" method="post">
                <button class="btn btn-light rounded p-2 w-100"type="submit">Zmień hasło</button>
            </form>
        </div>
        <div class="container mt-2">
            <form action="http://localhost/studia/SMARTHOME/strony/zmien_dane.php" method="post">
                <button class="btn btn-light rounded p-2 w-100"type="submit">Wyloguj się</button>
            </form>
        </div>
    </div>
        
    
    <?php include 'http://localhost/studia/SMARTHOME/template/nav_mobile.php'; ?>
</body>

<?php include 'http://localhost/studia/SMARTHOME/template/script.php'; ?>
</html>