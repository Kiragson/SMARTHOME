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
                    <form action="zmien_dane.php" method="post">
                        <button class="btn btn-warning rounded p-2 w-100"type="submit">Zmień dane użytkownika</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'template/footer.php'; ?>
</body>

<?php include 'template/script.php'; ?>
</html>