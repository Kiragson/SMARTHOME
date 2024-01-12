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
    <div class="container mt-5">
        <h1>Edycja informacji o koncie</h1>
        <div class="p-2 m-2 ">
            <form action="http://localhost/studia/SMARTHOME/php_script/user.php" method="post">
                <div class="mb-3 row">
                    <label for="username">Nazwa użytkownika:</label>
                    <input type="text" name="username" id="username" value="<?php echo $login; ?>" readonly>
                <!-- Używamy atrybutu "readonly", aby pole nie było edytowalne, tylko do odczytu -->
                </div>
                <div class="mb-3 row">
                    <label for="first_name">Imię:</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo $imie; ?>">
                </div>
                <div class="mb-3 row">
                    <label for="last_name">Nazwisko:</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo $nazwisko; ?>">
                </div>
                <div class="mb-3 row">
                    <label for="email">Adres email:</label>
                    <input type="email" name="email" id="email" value="<?php echo $email; ?>" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}">
                </div>
                <div class="mb-3 row">
                    <label for="phone_number">Nr tel:</label>
                    <input type="text" name="phone_number" id="phone_number" value="<?php echo $telefon; ?>">
                </div>
                <div class="mb-3 row">
                    <input type="hidden" name="method" value="update">
                    <button class="btn btn-warning rounded p-2 mt-5 w-50 mb-3 container" type="submit" id="zmiany">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../template/script.php'; ?>
</body>

</html>