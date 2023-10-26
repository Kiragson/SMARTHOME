<?php

session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/login.php');
    exit;
}
$login = $_SESSION['username'];

require_once("../connected.php");

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

    <title>Document</title>
</head>
<body>
    <?php include 'http://localhost/studia/SMARTHOME/template/header.php'; ?>
    <div class="container">
        <h1>Edycja informacji o koncie</h1>
        <div class="p-2 m-2">
            <form action="http://localhost/studia/SMARTHOME/php_script/aktualizuj_dane.php" method="post">
                <label for="username">Nazwa użytkownika:</label>
                <input type="text" name="username" id="username" value="<?php echo $login; ?>" readonly>
                <!-- Używamy atrybutu "readonly", aby pole nie było edytowalne, tylko do odczytu -->

                <label for="imie">Imię:</label>
                <input type="text" name="imie" id="imie" value="<?php echo $imie; ?>">

                <label for="nazwisko">Nazwisko:</label>
                <input type="text" name="nazwisko" id="nazwisko" value="<?php echo $nazwisko; ?>">

                <label for="email">Adres email:</label>
                <input type="email" name="email" id="email" value="<?php echo $email; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}">

                <label for="telefon">Nr tel:</label>
                <input type="text" name="telefon" id="telefon" value="<?php echo $telefon; ?>">

                <button type="submit">Zapisz zmiany</button>
            </form>
        </div>
    </div>
    <?php include 'http://localhost/studia/SMARTHOME/template/footer.php'; ?>
    
    <?php include 'http://localhost/studia/SMARTHOME/template/script.php'; ?>
</body>

</html>