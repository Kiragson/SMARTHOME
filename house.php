<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: login.php');
    exit;
}
require_once("connected.php");

// Pobierz zalogowanego użytkownika (zakładamy, że masz mechanizm uwierzytelniania)
$login = $_SESSION['username'];

// Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
$sql = "SELECT u.id, u.login, h.id AS house_id, h.nazwa AS house_name, f.id_admin as id_admin,
        r.id AS room_id, r.name AS room_name,
        d.id AS device_id, d.name AS device_name, d.stan AS device_stan
        FROM user u
        LEFT JOIN family f ON u.id = f.id_user1 OR u.id = f.id_user2 OR u.id = f.id_user3 OR u.id = f.id_user4 OR u.id = f.id_user5 OR u.id = f.id_user6 
        LEFT JOIN house h ON f.id = h.id_family
        LEFT JOIN room r ON h.id = r.id_house
        LEFT JOIN device d ON r.id = d.id_room
        WHERE u.login = ?"; // Zmieniłem na login, możesz dostosować to do Twojej bazy danych

// Przygotuj zapytanie SQL
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);

// Wykonaj zapytanie SQL
$stmt->execute();
$result = $stmt->get_result();

// Inicjalizacja zmiennych do przechowywania danych
$userInfo = "";
$houses = [];

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

$stmt->close();
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
        var socket = new WebSocket("ws://localhost:8080");

        // Obsługa zdarzenia po nawiązaniu połączenia WebSocket
        socket.onopen = function (event) {
            console.log("Połączono z serwerem WebSocket.");
        };

        // Obsługa zdarzenia po otrzymaniu wiadomości od serwera WebSocket
        socket.onmessage = function (event) {
            var response = JSON.parse(event.data);

            if (response.success) {
                // Zaktualizuj stan przycisku na stronie
                var button = document.getElementById("deviceButton_" + response.device_id);
                console.log("zmianastanu");
                if (button.innerHTML === "On") {
                    button.innerHTML = "Off";
                } else {
                    button.innerHTML = "On";
                }
            } else {
                alert("Wystąpił błąd podczas zmiany stanu urządzenia.");
            }
        };

        // Obsługa zdarzenia po rozłączeniu z serwerem WebSocket
        socket.onclose = function (event) {
            if (event.wasClean) {
                console.log("Zamknięto połączenie z serwerem WebSocket.");
            } else {
                console.error("Nieoczekiwane rozłączenie z serwerem WebSocket.");
            }
        };

        // Obsługa zdarzenia błędu
        socket.onerror = function (error) {
            console.error("Błąd połączenia z serwerem WebSocket: " + error.message);
        };

        // Funkcja do przełączania stanu urządzenia
        function toggleDevice(deviceId, currentState) {
            console.log("Przycisk kliknięty dla urządzenia o ID: " + deviceId);

            // Przygotuj dane do wysłania jako JSON
            var message = JSON.stringify({ device_id: deviceId, state: currentState });

            // Wyślij dane na serwer WebSocket
            socket.send(message);
        }
        function pokazFormularzDodawaniaDomu() {
            var formularz = document.getElementById("formularz-dodawania-domu");
            formularz.style.display = "block"; // Pokaż formularz
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
                    <h3><?php echo $houseData['name']; ?></h3>
                    <?php foreach ($houseData['rooms'] as $roomId => $roomData): ?>
                        <div class="mt-3 px-5">
                            <h4><?php echo $roomData['name']; ?></h4>
                            <?php if (!empty($roomData['devices'])): ?>
                                <ul>
                                <?php foreach ($roomData['devices'] as $deviceData): ?>
                                    <li><?php echo $deviceData['name']; ?> <!--(ID: <?php//echo $deviceData['id']; ?>, Stan: <?php// echo $deviceData['stan']; ?>)-->
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
        <div class="row justify-content-center mt-5">
            <div class="col-8 btn-container">
                <a class="btn btn-light rounded p-3 mb-3" href="#" onclick="pokazFormularzDodawaniaDomu()">Dodaj nowy dom</a>
            </div>
        </div>
    </div>
    <p>test</p>
    <div class="container mt-2">
        <div class="row justify-content-center mt-5">
            <div class="col-8">
                <div class="card" id="formularz-dodawania-domu" style="display: none;">
                    <div class="card-body">
                        <h5 class="card-title">Dodaj nowy dom</h5>
                        <form action="ad_home.php" method="post">
                            <div class="mb-3">
                                <label for="nazwa_domu" class="form-label">Nazwa domu:</label>
                                <input type="text" class="form-control" id="nazwa_domu" name="nazwa_domu" required>
                            </div>

                            <!-- Dodaj inne pola formularza, np. adres, opis itp. -->

                            <button type="submit" class="btn btn-primary">Dodaj Dom</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-2ZR3r5DST6JW6o5Kb8vGwF4L4j4Cz5bsv9S4ts7F5w5qb2LAE6Dfb0BPTw+H5f5F" crossorigin="anonymous"></script>
    <?php include 'template/footer.php'; ?>
</body>
<?php include 'template/script.php'; ?>
</html>
