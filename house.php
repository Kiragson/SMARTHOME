<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: login.php');
    exit;
}
require_once("connected.php");

// Pobierz zalogowanego użytkownika (zakładamy, że masz mechanizm uwierzytelniania)
$loggedInUserId = 3; // Przykład - ID zalogowanego użytkownika

// Inicjalizacja zmiennych do przechowywania danych
$userInfo = "";
$houses = [];

// Pobierz informacje o użytkowniku i jego domach
$sql = "SELECT u.id, u.login, h.id AS house_id, h.nazwa AS house_name, f.id_admin as id_admin,
        r.id AS room_id, r.name AS room_name,
        d.id AS device_id, d.name AS device_name, d.stan AS device_stan
        FROM user u
        LEFT JOIN family f ON u.id = f.id_user1 OR u.id = f.id_user2 OR u.id = f.id_user3 OR u.id = f.id_user4 OR u.id = f.id_user5 OR u.id = f.id_user6 
        LEFT JOIN house h ON f.id = h.id_family
        LEFT JOIN room r ON h.id = r.id_house
        LEFT JOIN device d ON r.id = d.id_room
        WHERE u.id = $loggedInUserId";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userId = $row['id'];
        $userName = $row['login'];
        $houseId = $row['house_id'];
        $houseName = $row['house_name'];
        $idAdmin = $row['id_admin'];
        $roomName = $row['room_name'];
        $roomId = $row['room_id'];
        $deviceName = $row['device_name'];
        $deviceId = $row['device_id'];
        $deviceStan = $row['device_stan'];
        $isAdmin = ($userId == $idAdmin);

        // Jeśli to jest nowy dom, utwórz nową strukturę domu
        if (!isset($houses[$houseId])) {
            $houses[$houseId] = [
                'name' => $houseName,
                'rooms' => [],
            ];
        }

        // Jeśli to jest nowy pokój w domu, utwórz nową strukturę pokoju
        if ($roomName && !isset($houses[$houseId]['rooms'][$roomId])) {
            $houses[$houseId]['rooms'][$roomId] = [
                'name' => $roomName,
                'devices' => [],
            ];
        }

        // Jeśli to jest nowe urządzenie w pokoju, dodaj je do listy urządzeń
        if ($deviceName) {
            $houses[$houseId]['rooms'][$roomId]['devices'][] = [
                'name' => $deviceName,
                'id' => $deviceId,
                'stan' => $deviceStan,
            ];
        }
    }
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
    <script>
        // Funkcja do przełączania stanu urządzenia
        function toggleDevice(deviceId) {
            console.log("Przycisk kliknięty dla urządzenia o ID: " + deviceId);

            // Pobierz element przycisku
            var button = document.getElementById("deviceButton_" + deviceId);

            // Wysyłamy żądanie AJAX, aby zmienić stan urządzenia
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_device_state.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Po zakończeniu żądania, zaktualizuj stan przycisku
                    console.log(xhr.responseText);

                    var response = JSON.parse(xhr.responseText);
                    console.log(JSON.stringify(response, null, 2));
                    var currentState = button.innerHTML;
                    if (response.success) {
                        // Zaktualizuj stan przycisku na stronie
                        //alert("Zmiana przycisku");
                        console.log("zmiana");
                        if (currentState === "On") {
                            button.innerHTML = "Off";
                        } else {
                            button.innerHTML = "On";
                        }
                    } else {
                        alert("Wystąpił błąd podczas zmiany stanu urządzenia.");
                    }
                }
            };

            // Przygotuj dane do wysłania
            var data = "device_id=" + deviceId;

            // Wyślij żądanie
            xhr.send(data);
        }



    </script>
</head>
<body>
    <?php include 'template/header.php'; ?>
    <div class="container">
        <?php echo $userInfo; ?>
        <div class='row justify-content-center mt-5'>
            
            <?php foreach ($houses as $houseId => $houseData): ?>
                <div class='col-8 navbar-light mt-5 p-3 rounded h-100' style='background-color: #e3f2fd;'>
                    <h3>Nazwa domu: <?php echo $houseData['name']; ?></h3>
                    <?php foreach ($houseData['rooms'] as $roomId => $roomData): ?>
                        <div class="mt-3 px-5">
                            <h4>Nazwa pokoju: <?php echo $roomData['name']; ?></h4>
                            <?php if (!empty($roomData['devices'])): ?>
                                <ul>
                                <?php foreach ($roomData['devices'] as $deviceData): ?>
                                    <li>Urządzenie: <?php echo $deviceData['name']; ?> (ID: <?php echo $deviceData['id']; ?>, Stan: <?php echo $deviceData['stan']; ?>)
                                        <button id="deviceButton_<?php echo $deviceData['id']; ?>" onclick="toggleDevice(<?php echo $deviceData['id']; ?>,<?php echo $deviceData['stan']; ?>)">
                                            <?php echo ($deviceData['stan'] == 1) ? "On" : "Off"; ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Brak urządzeń w pokoju</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
        </div>
    </div>
    <div class="container mt-2">
        <div class='row justify-content-center mt-5'>
            <div class='col-8 btn-container'>
                <a class="btn btn-light rounded p-3 mb-3" href='#'>Dodaj dom</a>
            </div>
        </div>
    </div>
    <?php include 'template/footer.php'; ?>
</body>
<?php include 'template/script.php'; ?>
</html>
