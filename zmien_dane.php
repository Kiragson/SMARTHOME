<?php

session_start();
require_once("sesja.php");
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
    <?php include 'template/header.php'; ?>
    <div class="container">
        <h1>Edycja informacji o koncie</h1>
        <div class="p-2 m-2">
            <form action="aktualizuj_dane.php" method="post">
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
    <?php include 'template/footer.php'; ?>
</body>

<?php include 'template/script.php'; ?>
</html>