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
        header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
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
        <div class='row justify-content-center mt-5'>
            <?php foreach ($houses as $houseId => $houseData): ?>
                <?php if ($numberHouse>0): ?>
                    <div class='col-lg-8 col-m-19 col-s-10 col-xs-11 navbar-light mt-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                        <div class='row justify-content-center mt-5'>
                            <div class='col-10'>
                                <h3><?php echo $houseData['name']; ?></h3>
                            </div>
                            <div class="col-1 dropdown">
                                <button class="btn" type="button" id="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-sliders"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonRoom<?php echo $houseData['id_house']; ?>">
                                    <a class="dropdown-item" href="edit_house.php?id_domu=<?php echo $houseData['id_house']; ?>">Edytuj</a>
                                    <a class="dropdown-item" href="../php_script/house.php?id_domu=<?php echo $houseData['id_house']; ?>">Usuń</a>
                                    <a class="dropdown-item" href="info_house.php?id_domu=<?php echo $houseData['id_house']; ?>" >Informacje</a>
                                </div>
                            </div>
                        </div>
                        <!--Wyświetlanie i obsługa pokoi-->
                        <?php if (!empty($houseData['rooms'])): ?>
                            <?php foreach ($houseData['rooms'] as $roomId => $roomData): ?>
                                <div class="mt-3 px-5 py-2 rounded border border-3">
                                    <div class='row mt-2'>
                                        <div class="col-2 ml-5">
                                            <h4><?php echo $roomData['name']; ?></h4>
                                        </div>
                                        <div class="col-9 ml-5"></div>
                                        <div class="col-1 ml-5">
                                            <!-- lista rozwijana dla pokoju -->
                                            <button class="btn" type="button" id="dropdownMenuButtonRoom<?php echo $roomId; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-sliders"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonRoom<?php echo $roomId; ?>">
                                                <a class="dropdown-item" href="edit_room.php?id_room=<?php echo $roomId; ?>">Edytuj</a>
                                                <a class="dropdown-item" href="../php_script/delete_room.php?id_room=<?php echo $roomId; ?>&id_domu=<?php echo $houseData['id_house']; ?>">Usuń</a>
                                                <a class="dropdown-item" href="info_room.php?id_room=<?php echo $roomId; ?>">Informacje</a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- wyświetlanie i obsługa urządzeń -->
                                    <?php if (!empty($roomData['devices'])): ?>
                                        <?php foreach ($roomData['devices'] as $deviceData): ?>
                                            <div class="mt-2 row">
                                                <div class="col-3 p-2">
                                                    <?php echo $deviceData['name']; ?>
                                                </div>
                                                <div class="col-2 p-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="deviceSwitch_<?php echo $deviceData['id'] ?>" onchange="toggleDeviceState(<?php echo $deviceData['id'] ?>)" <?php echo ($deviceData['stan'] == 1) ? "checked" : ""; ?>>
                                                        <label class="form-check-label" for="deviceSwitch_<?php echo $deviceData['id'] ?>"></label>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <!-- Dodaj przycisk edycji, który uruchomi tryb edycji dla konkretnego urządzenia -->
                                                    <a href="http://localhost/studia/SMARTHOME/php_script/delete_device.php?device_id=<?php echo $deviceData['id']; ?>" class="btn"><i class="bi bi-trash3"></i></a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class='text-center my-5'>
                                            <p class="h4">Brak urządzeń w pokoju</p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="container">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                    </div>

                                    <div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Dodaj nowe urządzenie</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="http://localhost/studia/SMARTHOME/php_script/device.php" method="POST" id="add_device-form">
                                                        <div class='row justify-content-center mt-5'>
                                                            <div class="mb-3">
                                                                <label for="name_device" class="form-label">Nazwa</label>
                                                                <input type="text" class="form-control" id="name_device" name="name_device" aria-describedby="text">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="Ip_address" class="form-label">Adres IP:</label>
                                                                <input type="text" class="form-control" id="ip_adres" name="ip_adres" aria-describedby="text" required>
                                                                <small class="text-muted">Podaj poprawny adres IP (IPv4).</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <input type="hidden" name="id_house" value="<?php echo $houseData['id_house']; ?>">
                                                                <input type="hidden" name="stan" value="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="room" class="form-label">Wybierz pokój:</label>
                                                                <input type="text" class="form-control" name="roomID<?php echo $roomId; ?>" id="<?php echo $roomId; ?>" value="<?php echo $roomData['name']; ?>">
                                                            </div>
                                                        </div>
                                                        <div class='row justify-content-center mt-5'>
                                                            <div class="col-4 justify-content-center row">
                                                                <input type="hidden" name="method" value="addDevice">
                                                                <button class="btn btn-primary p-2" type="submit">Dodaj Urządzenie</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class='text-center my-5'>
                                <p class="h4">Brak Pokoi</p>
                            </div>
                        <?php endif; ?>

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
     <!--Za duża ilośc pokoi-->
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
        console.log(event);
        if (event.data && event.data.includes('state')) {
        var response = JSON.parse(event.data);
        console.log(response);

        var device_id = response.device_id;
        var state = response.state;
        
        console.log("device_id:", device_id);
        console.log("state:", state);
        if (state==0){
            state=1;
        }
        else if (state==1){
            state=0;
        }
        
        togler(device_id,state);

    }

    };
    function togler(device_id,state){
         // Zaktualizuj stan przycisku na stronie
        var checkbox = document.getElementById("deviceSwitch_" + device_id); 
        var statusParagraph = document.getElementById("statusParagraph_" + device_id);

        
                // Obsługa, gdy przycisk jest zaznaczony (checked)
        console.log("House.php/togler: Zmiana stanu na",state);
        
        statusParagraph.innerHTML = state;
        checkbox.addEventListener("change", function () {
            var newState = state;
            // Aktualizuj tekst paragrafu
           // statusParagraph.innerHTML = "Stan: " + newState;
        });



            console.log('Koniec togler');
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
        console.log('Koniec toggleDevice');
    };

    // Funkcja do pobierania i aktualizacji stanu urządzenia
    function toggleDeviceState(device_id) {
        // Wysyłanie zapytania do serwera w celu pobrania stanu urządzenia
        const rodzaj = "changeDeviceState";
        const url = `http://localhost/studia/SMARTHOME/php_script/device.php?device_id=${device_id}&method=${rodzaj}`;

        console.log('Tworzony link:', url);

        fetch(url)
        
            .then(response => response.json())
            .then(data => {
                console.log(data);
                // Aktualizacja tekstu przycisku i wywołanie funkcji do przełączania stanu
                const buttonElement = document.getElementById('deviceSwitch_' + device_id);
                if (buttonElement) {
                    console.log("House.php/241: Zmiana Stanu");
                    if (data.state === 0) {
                        buttonElement.innerHTML = "Off";
                    } else if (data.state === 1) {
                        buttonElement.innerHTML = "On";
                    } else {
                        console.error("House.php/248: Błędny stan przycisku");
                    }

                    // Wywołanie funkcji do przełączania stanu z aktualnym stanem
                    toggleDevice(device_id, data.state);
                }
                console.log('deviceSwitch_' + device_id + " " + data.state);
            })
            .catch(error => {
                console.error('Błąd podczas pobierania stanu urządzenia: ' + error.message);
            });
            

        console.log('Koniec toggleDeviceState');
    }

    //sterowanie wyskakującego okna dodania urządzenia
    document.addEventListener('DOMContentLoaded', function () {
        const ipAddressInput = document.querySelector('#Ip_address');
        const validationMessage = document.createElement('span');
        validationMessage.className = 'text-danger';
        ipAddressInput.parentNode.appendChild(validationMessage);

        ipAddressInput.addEventListener('input', function () {
            const ipAddress = this.value;
            const isValidIpAddress = isValidIPv4(ipAddress);

            if (isValidIpAddress) {
                this.setCustomValidity('');
                validationMessage.textContent = '';
                // Próba nawiązania połączenia
                checkConnection(ipAddress);
            } else {
                this.setCustomValidity('Wprowadź poprawny adres IP (np. 192.168.1.1).');
                validationMessage.textContent = 'Wprowadź poprawny adres IP (np. 192.168.1.1).';
            }
        });

        function isValidIPv4(ipAddress) {
            const blocks = ipAddress.split('.');
            if (blocks.length !== 4) {
                return false;
            }

            for (const block of blocks) {
                const number = parseInt(block, 10);
                if (isNaN(number) || number < 0 || number > 255) {
                    return false;
                }
            }

            // Sprawdzanie prywatnych adresów IP (możesz dostosować tę listę)
            const privateIPRanges = [
                ['10.0.0.0', '10.255.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255']
            ];

            for (const range of privateIPRanges) {
                const startIP = ipToNumber(range[0]);
                const endIP = ipToNumber(range[1]);
                const userIP = ipToNumber(ipAddress);
                if (userIP >= startIP && userIP <= endIP) {
                    return false;
                }
            }

            return true;
        }

        function checkConnection(ipAddress) {
            const img = new Image();
            img.src = `http://${ipAddress}`;

            img.onload = function () {
                console.log(`Połączono z adresem IP: ${ipAddress}`);
            };

            img.onerror = function () {
                const errorMessage = 'Nie można nawiązać połączenia z adresem IP.';
                validationMessage.textContent = errorMessage;
            };
        }

        function ipToNumber(ip) {
            const parts = ip.split('.').map(part => parseInt(part, 10));
            return (parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3];
        }

    });

</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
