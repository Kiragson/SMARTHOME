<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
        header('Location: login.php');
        exit;
    }
    require_once("../connected.php");

    if (isset($_GET['error'])) {
        $errorMessage = urldecode($_GET['error']);
        echo "<script>alert('$errorMessage');</script>";
        exit;
    }
    
    // Pobierz zalogowanego użytkownika (zakładamy, że masz mechanizm uwierzytelniania)
    $login = $_SESSION['username'];

    // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
    $sql = "SELECT u.id, u.login, u.number_of_houses AS NOH, 
        h.id AS house_id, h.name AS house_name, 
        f.id_admin AS id_admin,
        r.id AS room_id, r.name AS room_name,
        d.id AS device_id, d.name AS device_name, d.state AS device_stan
        FROM User u
        LEFT JOIN Family f ON u.id = f.user1 OR u.id = f.user2 OR u.id = f.user3 OR u.id = f.user4 OR u.id = f.user5 OR u.id = f.user6 
        LEFT JOIN House h ON f.id = h.family_id
        LEFT JOIN Room r ON h.id = r.house_id
        LEFT JOIN Device d ON r.id = d.room_id
        WHERE u.login = ?";
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
            $numberHouse= $row['NOH'];
            // Jeśli urzytkownik ma domy
            if(!isset($numberHouse)){
                $numberHouse=0;
            }
            // Jeśli to jest nowy dom, utwórz nową strukturę domu
            if (!isset($houses[$houseId])) {
                $houses[$houseId] = [
                    'name' => $houseName,
                    'id_house'=>$houseId,
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
    <title>Twoje domy</title>
    <?php include '../template/css.php'; ?>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <?php echo $userInfo; ?>
        <div class='row justify-content-center mt-5'>
            <?php foreach ($houses as $houseId => $houseData): ?>
                <?php if ($numberHouse>0): ?>
                    <div class='col-8 navbar-light mt-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                        <div class='row justify-content-center mt-5'>
                            <div class='col-10'>
                                <h3><?php echo $houseData['name']; ?></h3>
                            </div>
                            <div class="col-1 dropdown">
                                <button class="btn" type="button" id="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-sliders"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>">
                                    <a class="dropdown-item" href="edytuj.php?id_domu=<?php echo $houseData['id_house']; ?>">Edytuj</a>
                                    <a class="dropdown-item" href="../php_script/usun.php?id_domu=<?php echo $houseData['id_house']; ?>">Usuń</a>
                                    <a class="dropdown-item" href="#" onclick="pokazInformacje(<?php echo $houseData['id_house']; ?>)">Informacje</a>
                                </div>
                            </div>
                        </div>
                        <?php foreach ($houseData['rooms'] as $roomId => $roomData): ?>
                            <div class="mt-3 px-5 py-2 rounded border border-3">
                                <div class='row  mt-2'>
                                    <div class="col-3 ml-5 row">
                                        <h4><?php echo $roomData['name']; ?></h4>
                                    </div>
                                    <div class="col-3 ml-5 row"></div>
                                    <div class="col-3 ml-5 row">
                                        <form action="http://localhost/studia/SMARTHOME/strony/new_device.php" method="POST">
                                            <input type="hidden" name="id_house" value="<?php echo $houseId; ?>">
                                            <button class="btn btn-primary p-2" type="submit">Dodaj urządzenia</button>
                                        </form>
                                    </div>
                                </div>
                                <?php if (!empty($roomData['devices'])): ?>
                                    <ul>
                                        <?php foreach ($roomData['devices'] as $deviceData): ?>
                                            <li>
                                                <?php echo $deviceData['name']; ?>
                                                <button id="deviceButton_<?php echo $deviceData['id']; ?>" onclick="toggleDeviceState(<?php echo $deviceData['id']; ?>)">
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
                        <div class='row justify-content-center mt-4'>
                            <div class="col-4 justify-content-center row">
                                <a class="btn btn-primary p-2" href="http://localhost/studia/SMARTHOME/strony/new_room.php">Dodaj pokój</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class='col-8 navbar-light my-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                        <div class='text-center my-5'>
                            <p class="h3">Nie posiadaż żadnych domów</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if ($numberHouse<=5): ?>
        <div class="container mt-2">
            <div class="row justify-content-center mt-5">
                <div class="col-8 btn-container text-center">
                    <a class="btn btn-secondary rounded p-3 mb-3" href="http://localhost/studia/SMARTHOME/strony/new_house.php" >Dodaj nowy dom</a>
                </div>
                <div>
                    <?php if (!empty($message)): ?>
                        <script>alert('$message');</script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
                console.log("Zmiana stanu");
                if (button.innerHTML === "On") {
                    button.innerHTML = "Off";
                } else {
                    button.innerHTML = "On";
                }
            } else {
                console.error("Wystąpił błąd podczas zmiany stanu urządzenia");
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
            console.log("Wysyłanie danych na serwer");
        }

        // Funkcja do pobierania i aktualizacji stanu urządzenia
        function toggleDeviceState(device_id) {
            // Wysyłanie zapytania do serwera w celu pobrania stanu urządzenia
            fetch('http://localhost/studia/SMARTHOME/php_script/get_device_state.php?device_id=' + device_id)
                .then(response => response.json())
                .then(data => {
                    // Aktualizacja tekstu przycisku i wywołanie funkcji do przełączania stanu
                    const buttonElement = document.getElementById('deviceButton_' + device_id);
                    if (buttonElement) {
                        console.log("Zmiana Stanu");
                        if (data.state === 0) {
                            buttonElement.innerHTML = "Off";
                        } else if (data.state === 1){
                            buttonElement.innerHTML = "On";
                        }
                        else{
                            console.error("Hoise.php: Błędny stan przycisku")
                        }

                        // Wywołanie funkcji do przełączania stanu z aktualnym stanem
                        toggleDevice(device_id, data.state);
                    }
                    console.log('deviceButton_' + device_id+" "+data.state);
                })
                .catch(error => {
                    console.error('Błąd podczas pobierania stanu urządzenia: ' + error.message);
                });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
