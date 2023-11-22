<?php
session_start();

$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['id_room'])) {
    $user_id = $_SESSION['user_id'];
    $id_pokoju = $_GET['id_room'];

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    require_once("../connected.php");

    // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
    $sql = "SELECT R.name as roomName, d.name as deviceName, d.ip,d.id as deviceID, state,h.id as house_id
            FROM Room R 
            LEFT JOIN house h ON h.id=r.house_id
            LEFT JOIN device d ON R.id = d.room_id
            WHERE R.id = ?";
    // Przygotuj zapytanie SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pokoju);

    $stmt->execute();
    $result = $stmt->get_result();

    $devices = array(); // Przygotowujemy tablicę na informacje o urządzeniach

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $roomName = $row['roomName'];
            $deviceName = $row['deviceName'];
            $ip = $row['ip'];
            $deviceId=$row['deviceID'];
            $state=$row['state'];
            $houseId=$row['house_id'];

            // Dodaj informacje o urządzeniu do tablicy
            $devices[] = array('deviceName' => $deviceName,'deviceId'=>$deviceId, 'ip' => $ip, 'stan'=>$state);
        }

        $stmt->close(); // Zamknij statement

        $conn->close(); // Zamknij połączenie
    } else {
        // Dodaj obsługę braku wyników
        header('Location: http://localhost/studia/SMARTHOME/404.html');
        exit();
    }
} else {
    // Dodaj obsługę braku parametru id_room
    header('Location: http://localhost/studia/SMARTHOME/404.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $roomName; ?></title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <div class="row">
                    <div class="col-2"><h2> <?php echo $roomName; ?></h2></div>
                    <div class="col-8"></div>
                    <div class="col-2">
                        <a href="http://localhost/studia/SMARTHOME/php_script/delete_room.php?id_room=<?php echo $id_pokoju; ?>" class="btn "><i class="bi bi-trash3"></i></a>
                        <a href="http://localhost/studia/SMARTHOME/php_script/update_room.php?room_id=<?php echo $id_pokoju; ?>"class="btn " ><i class="bi bi-pen-fill"></i></a>
                    </div>
                </div>
                <hr>
                <div class="container border-top border-2 border-dark">
                    <h3>Urządzenia dostępne w pokoju</h3>
                    <div class="p-2 m-2">
                        <?php if (count($devices) > 0) : ?>
                            <?php foreach ($devices as $device): ?>
                                <div class="container mt-2 row">
                                    <div class="col-2">
                                        <!-- Nazwa -->
                                        <?php echo $device['deviceName']; ?>
                                        <?php echo $device['ip']; ?>
                                    </div>
                                    <div class="col-2">
                                    </div>
                                    <div class="col-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="deviceSwitch_<?php echo $device['deviceId']; ?>" onchange="toggleDeviceState(<?php echo $device['deviceId']; ?>)" <?php echo ($device['stan'] == 1) ? "checked" : ""; ?>>
                                            <label class="form-check-label" for="deviceSwitch_<?php echo $device['deviceId']; ?>"></label>
                                        </div>
                                        <input type="hidden" id="deviceButton_<?php echo $device['deviceId']; ?>" onclick="toggleDeviceState(<?php echo $device['deviceId']; ?>)">
                                            <?php // echo ($device['stan'] == 1) ? "On" : "Off"; ?>
                                        </input>
                                    </div>
                                    
                                    <div class="col-2">
                                    </div>
                                    <div class="col-2">
                                        <!-- Dodaj przycisk edycji, który uruchomi tryb edycji dla konkretnego urządzenia -->
                                        <a href="http://localhost/studia/SMARTHOME/php_script/delete_device.php?device_id=<?php echo $device['deviceId']; ?>" class="btn "><i class="bi bi-trash3"></i></a>
                                        <button class="btn " onclick="editDevice(<?php echo $device['deviceId']; ?>)"><i class="bi bi-pen-fill"></i></button>
                                    </div>
                                </div>
                                <div class="pt-4" id="editForm_<?php echo $device['deviceId']; ?>" style="display: none;">
                                    <form action="http://localhost/studia/SMARTHOME/php_script/update_device.php" method="POST">
                                        <label for="editDeviceName">Nowa:</label>
                                        <input type="text" id="editDeviceName_<?php echo $device['deviceId']; ?>" value="<?php echo $device['deviceName']; ?>">

                                        <label for="editDeviceIp">Adres IP:</label>
                                        <input type="text" id="editDeviceIp_<?php echo $device['deviceId']; ?>" value="<?php echo $device['ip']; ?>">

                                        <button class="btn btn-primary p-2"  type="submit"> Zapisz zmiany</button>
                                        <button class="btn btn-secondary p-2" onclick="cancelEdit(<?php echo $device['deviceId']; ?>)">Anuluj</button>
                                    </form>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Brak urządzeń w pokoju.</p>
                        <?php endif; ?>
                        <div>
                            <form action="http://localhost/studia/SMARTHOME/strony/new_device.php" method="GET">
                                <input type="hidden" name="id_house" value="<?php echo $houseId; ?>">
                                <input type="hidden" name="id_room" value="<?php echo $id_pokoju; ?>">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        flag=0;
        function editDevice(device_id) {
            if(flag==0){
                // Ukryj wyświetlanie
                document.getElementById('deviceButton_' + device_id).style.display = 'none';
                document.getElementById('deviceSwitch_' + device_id).style.display = 'none';

                // Wyświetl formularz edycji
                document.getElementById('editForm_' + device_id).style.display = 'block';
                flag=1;
            }else{
                cancelEdit(device_id);
                flag=0;
            }
        
        }

        // Funkcja do anulowania edycji
        function cancelEdit(device_id) {
            // Wyświetl ponownie przyciski
            document.getElementById('deviceButton_' + device_id).style.display = 'block';
            document.getElementById('deviceSwitch_' + device_id).style.display = 'block';

            // Ukryj formularz edycji
            document.getElementById('editForm_' + device_id).style.display = 'none';
        }

        // Funkcja do zapisywania zmian
        function saveDeviceChanges(device_id) {
            // Pobierz nowe wartości z formularza
            var newName = document.getElementById('editDeviceName_' + device_id).value;
            var newIp = document.getElementById('editDeviceIp_' + device_id).value;

            // Tutaj możesz przekazać te dane do serwera za pomocą żądania AJAX, aby zaktualizować bazę danych

            // Aktualizuj dane na stronie
            document.getElementById('deviceButton_' + device_id).innerHTML = newName;
            document.getElementById('editForm_' + device_id).style.display = 'none'; // Ukryj formularz po zapisaniu zmian
        }
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
                console.log("House.php/195: Zmiana stanu");
                if (button.innerHTML === "On") {
                    button.innerHTML = "Off";
                } else {
                    button.innerHTML = "On";
                }
            } else {
                console.error("House.php/202: Wystąpił błąd podczas zmiany stanu urządzenia");
            }
        };

        // Obsługa zdarzenia po rozłączeniu z serwerem WebSocket
        socket.onclose = function (event) {
            if (event.wasClean) {
                console.log("House.php/209: Zamknięto połączenie z serwerem WebSocket.");
            } else {
                console.error("House.php/211: Nieoczekiwane rozłączenie z serwerem WebSocket.");
            }
        };

        // Obsługa zdarzenia błędu
        socket.onerror = function (error) {
            console.error("House.php/217: Błąd połączenia z serwerem WebSocket: " + error.message);
        };
        // Funkcja do przełączania stanu urządzenia
        function toggleDevice(device_id, currentState) {
            console.log("House.php/222: Przycisk kliknięty dla urządzenia o ID: " + device_id);

            // Przygotuj dane do wysłania jako JSON
            var message = JSON.stringify({ device_id: device_id, state: currentState });

            // Wyślij dane na serwer WebSocket
            socket.send(message);
            console.log("House.php/229: Wysyłanie danych na serwer");
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
                        console.log("House.php/241: Zmiana Stanu");
                        if (data.state === 0) {
                            buttonElement.innerHTML = "Off";
                        } else if (data.state === 1){
                            buttonElement.innerHTML = "On";
                        }
                        else{
                            console.error("House.php/248: Błędny stan przycisku")
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
</body>
</html>
