<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
        header('Location: login.php');
        exit;
    }
    require_once("connected.php");

    if (isset($_GET['error'])) {
        $errorMessage = urldecode($_GET['error']);
        echo "<script>alert('$errorMessage');</script>";
        
        // Usuń parametr 'error' z adresu URL
        $url = strtok($_SERVER["REQUEST_URI"], '?');
        header('Location: ' . $url);
        exit;
    }
    
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
    <title>Document</title>
    <?php include 'http://localhost/studia/SMARTHOME/template/css.php'; ?>
    
</head>
<body>
    
    <?php include 'http://localhost/studia/SMARTHOME/template/header.php'; ?>
    
    <div class="container">
        <?php echo $userInfo; ?>
        <div class='row justify-content-center mt-5'>
            <?php foreach ($houses as $houseId => $houseData): ?>
                <div class='col-8 navbar-light mt-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                    <div class='row justify-content-center mt-5'>
                        <div class='col-10'>
                            <h3><?php echo $houseData['name']; ?></h3>
                        </div>
                        <div class="col-1 dropdown">
                            <button class="btn" role="button" id="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bi bi-sliders"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>">
                                <a class="dropdown-item" href="edytuj.php?id_domu=<?php echo $houseData['id_house']; ?>">Edytuj</a>
                                <a class="dropdown-item" href="usun.php?id_domu=<?php echo $houseData['id_house']; ?>">Usuń</a>
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
                                        <?php //echo $houseId ?>
                                        <input type="hidden" name="id_house" value="<?php echo $houseId; ?>">
                                        <?php //echo $roomId ?>
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
            <?php endforeach; ?>
        </div>
    </div>
    <div class="container mt-2">
        <div class="row justify-content-center mt-5">
            <div class="col-8 btn-container">
                <a class="btn btn-light rounded p-3 mb-3" href="#" onclick="pokazFormularzDodawaniaDomu()">Dodaj nowy dom</a>
            </div>
            <div>
                <?php if (!empty($message)): ?>
                    <script>alert('$message');</script>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container mt-2">
        <div class="row justify-content-center mt-5">
            <div class="col-8">
                <div class="card" id="formularz-dodawania-domu" >
                    <div class="card-body">
                        <h5 class="card-title">Dodaj nowy dom</h5>
                        <form action="http://localhost/studia/SMARTHOME/strony/ad_home.php" method="post">
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
    
    <?php include 'http://localhost/studia/SMARTHOME/template/script.php'; ?>
    <?php include 'http://localhost/studia/SMARTHOME/template/footer.php'; ?>
</body>
</html>