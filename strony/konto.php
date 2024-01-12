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
    <?php include '../template/css.php'; ?>
    <title>Informacje o koncie</title>
</head>
<body>
    <?php include '../template/header.php'; ?>

    <!-- Początkowa sekcja z informacjami o koncie -->
    <div class="container" id="accountInfoSection">
        <div class="row justify-content-center mt-5">
            <div class="col-8 account-info-container">
                <h1>Informacje o koncie</h1>
                <div class="account-details">
                    <p>Nazwa użytkownika <?php echo $login; ?> #<?php echo $id; ?> </p>
                    <p>Imię Nazwisko <?php echo $imie; ?> <?php echo $nazwisko; ?></p>
                    <p>Adres email <?php echo $email; ?></p>
                    <p>Nr tel <?php echo $telefon; ?></p>
                </div>

                <!-- Przycisk do zmiany danych użytkownika -->
                <div class="edit-button-container">
                    <button class="btn btn-warning" onclick="showEditForm()">Zmień dane użytkownika</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sekcja z formularzem edycji -->
    <div class="container mt-5" id="editFormSection" style="display: none;">
        <h1>Edycja informacji o koncie</h1>
        <div class="edit-form">
            <form id="editForm">
                <div class="mb-3 row">
                    <label for="username">Nazwa użytkownika:</label>
                    <input type="text" name="username" id="username" value="<?php echo $login; ?>" readonly>
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
                    <button class="btn btn-secondary rounded p-2 mt-5 w-50 mb-3" type="button" onclick="cancelEdit()">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../template/script.php'; ?>

    <script>
        // Funkcja ukazująca formularz edycji i ukrywająca sekcję z informacjami o koncie
        function showEditForm() {
            document.getElementById("accountInfoSection").style.display = "none";
            document.getElementById("editFormSection").style.display = "block";
        }

        function cancelEdit() {
            document.getElementById("accountInfoSection").style.display = "block";
            document.getElementById("editFormSection").style.display = "none";
        }

        document.addEventListener('DOMContentLoaded', function () {
            const editFormSection = document.getElementById('editFormSection');
            const accountInfoSection = document.getElementById('accountInfoSection');

            const editForm = document.getElementById('editForm');
            editForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(editForm);

                fetch('http://localhost/studia/SMARTHOME/php_script/user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Dane użytkownika zostały zaktualizowane pomyślnie.');
                        window.location.reload();
                    } else {
                        alert('Błąd podczas aktualizacji danych użytkownika: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Wystąpił błąd podczas komunikacji z serwerem:', error);
                });
            });
        });
    </script>
</body>
</html>
