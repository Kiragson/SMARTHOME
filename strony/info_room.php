<?php
    session_start();

    $response = array(); // Tworzymy pusty tablicę na odpowiedź

    if (isset($_GET['id_room'])) {
        $user_id = $_SESSION['user_id'];
        $roomId = $_GET['id_room'];

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
        $stmt->bind_param("i", $roomId);

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
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <div class="row">
                    <div class="col-2"><h2> <?php echo $roomName; ?></h2></div>
                    <div class="col-7"></div>
                    <div class="col-3">
                        <button class="btn" onclick="confirmRoomDelete('<?php echo $roomId; ?>', '<?php echo $roomName; ?>');"><i class="bi bi-trash3"></i></button>
                        <button class="btn" data-bs-toggle="modal" data-bs-target="#updateRoom" onclick="RoomUpdate();"><i class="bi bi-pen-fill"></i></button>
                    </div>
                </div>
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
                                            <input class="form-check-input" type="checkbox" id="deviceSwitch_<?php echo $device['deviceId']; ?>" onchange="toggleDevice(<?php echo $device['deviceId']; ?>)" <?php echo ($device['stan'] == 1) ? "checked" : ""; ?>>
                                            <label class="form-check-label" for="deviceSwitch_<?php echo $device['deviceId']; ?>"></label>
                                        </div>
                                        
                                    </div>
                                    
                                    <div class="col-2">
                                    </div>
                                    <div class="col-2">
                                        <button class="btn" onclick="confirmDeviceDelete('<?php echo $device['deviceId'] ?>', '<?php echo $device['deviceName']; ?>');"><i class="bi bi-trash3"></i></button>
                                        <button class="btn" data-bs-toggle="modal" data-bs-target="#editDeviceModal_<?php echo $device['deviceId']; ?>"><i class="bi bi-pen-fill"></i></button>
                                    </div>
                                    <!-- update device-->
                                    <div class="modal fade" id="editDeviceModal_<?php echo $device['deviceId']; ?>" tabindex="-1" aria-labelledby="editDeviceModalLabel_<?php echo $device['deviceId']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editDeviceModalLabel_<?php echo $device['deviceId']; ?>">Edytuj urządzenie</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="http://localhost/studia/SMARTHOME/php_script/device.php" method="POST" id="update_device-form">
                                                        <div class="mb-3">
                                                            <label for="editDeviceName" class="form-label">Nowa nazwa:</label>
                                                            <input type="text" class="form-control" name="editDeviceName" id="editDeviceName_<?php echo $device['deviceId']; ?>" value="<?php echo $device['deviceName']; ?>">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="editDeviceIp" class="form-label">Adres IP:</label>
                                                            <input type="text" class="form-control" name="editDeviceIp" id="editDeviceIp_<?php echo $device['deviceId']; ?>" value="<?php echo $device['ip']; ?>">
                                                        </div>

                                                        <div class="text-end">
                                                            <input type="hidden" name="method" value="update">
                                                            <input type="hidden" name="device_Id" value="<?php echo $device['deviceId']; ?>">
                                                            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Brak urządzeń w pokoju.</p>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <div class="container">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
                <!--new device-->
                <div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="add_device_label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="add_device_label">Dodaj nowe urządzenie</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="add_device-form">
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
                                        <input type="hidden" name="id_house" value="<?php echo $houseId ?>">
                                        <input type="hidden" name="stan" value="0">
                                        <input type="hidden" name="id_room" value="<?php echo $roomId; ?>">
                                        <input type="hidden" name="stan" value="0">
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
                <!--new updateroom-->
                <div class="modal fade" id="updateRoom" tabindex="-1" aria-labelledby="updateRoom_label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="updateRoom_label">Dodaj nowe urządzenie</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="updateRoom-form">
                                    <div class='row justify-content-center mt-5'>
                                        <div class="mb-3">
                                            <label for="name_room" class="form-label">Nowa Nazwa</label>
                                            <input type="text" class="form-control" id="name_room" name="name_room"  value="<?php echo $roomName; ?>" aria-describedby="text">
                                        </div>
                                    </div>
                                    <div class='row justify-content-center mt-5'>
                                        <div class="col-4 justify-content-center row">
                                            <input type="hidden" name="method" value="edit_room">
                                            <input type="hidden" name="room_id" value="<?php echo $roomId; ?>">
                                            <button class="btn btn-primary p-2" onclick="UpdateRoomForm()">Edytuj Pokój</button>
                                            <button class="btn btn-secondary p-2 mx-2" type="button" data-bs-dismiss="modal">Anuluj</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script>
        function confirmDeviceDelete(device_id, deviceName) {
            // Wywołaj modal z potwierdzeniem
            var isConfirmed = confirm("Czy na pewno chcesz usunąć urządzenie '" + deviceName + "'?");
            console.log(device_id)
            // Jeśli użytkownik potwierdzi, wykonaj usunięcie
            if (isConfirmed) {
                fetch('http://localhost/studia/SMARTHOME/php_script/device.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'method=delete&device_id=' + device_id,
                })
                .then(response => response.json())
                .then(data => {
                    // Obsługa odpowiedzi z serwera
                    console.log(data);
                    if (data.success) {
                        alert('Urządzenie usunięte pomyślnie.');
                        // Tutaj możesz dodać dodatkową logikę, jeśli potrzebujesz
                        window.location.reload();
                    } else {
                        alert('Błąd podczas usuwania urządzenia: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd podczas wysyłania żądania:', error);
                });
            }
        }
        function confirmRoomDelete(room_id, roomName) {
            // Wywołaj modal z potwierdzeniem
            var isConfirmed = confirm("Czy na pewno chcesz usunąć urządzenie '" + roomName + "'?");
            console.log(room_id)
            // Jeśli użytkownik potwierdzi, wykonaj usunięcie
            if (isConfirmed) {
                fetch('http://localhost/studia/SMARTHOME/php_script/room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'method=delete&room_id=' + room_id,
                })
                .then(response => response.json())
                .then(data => {
                    // Obsługa odpowiedzi z serwera
                    console.log(data);
                    if (data.success) {
                        alert('Urządzenie usunięte pomyślnie.');
                        // Tutaj możesz dodać dodatkową logikę, jeśli potrzebujesz
                        window.location.reload();
                    } else {
                        alert('Błąd podczas usuwania urządzenia: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd podczas wysyłania żądania:', error);
                });
            }
        }
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

        function UpdateRoomForm() {
            const updateRoomForm = document.getElementById("updateRoom-form");

            updateRoomForm.addEventListener("submit", function (event) {
                event.preventDefault(); // Prevent the default form submission

                const formData = new FormData(updateRoomForm);

                fetch("../php_script/room.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    // Handle the response from the server
                    if (data.success) {
                        // Actions to perform on success
                        alert("Room updated successfully!");
                        window.location.reload();
                    } else {
                        // Actions to perform on failure
                        alert("Failed to update room:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                });
            });
        }

    
        
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
            console.log("old state:", state);
            toggler(device_id,state);

        }
        };

        function toggler(device_id, newState) {
            console.log("toggler");
            console.log("device_id:", device_id);
            console.log("new state:", newState);
            const checkbox = document.getElementById("deviceSwitch_" + device_id);
            if(newState==1){
                if (checkbox) {
                    checkbox.checked = newState === 1;
                }
            }
            else{
                if (checkbox) {
                    checkbox.checked = newState;
                }
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
            console.log(" Przycisk kliknięty dla urządzenia o ID: " + device_id);

            // Przygotuj dane do wysłania jako JSON
            var message = JSON.stringify({ device_id: device_id, state: currentState });

            // Wyślij dane na serwer WebSocket
            socket.send(message);
            console.log("Wysyłanie danych na serwer");
        }

        // Formularz dodawania urządzenia
        document.addEventListener('DOMContentLoaded', function () {
            const addDeviceForm = document.getElementById('add_device-form');
            const ipAddressInput = document.querySelector('#ip_adres');
            const validationMessage = document.createElement('span');
            validationMessage.className = 'text-danger';
            ipAddressInput.parentNode.appendChild(validationMessage);

            addDeviceForm.addEventListener('submit', function (event) {
                event.preventDefault(); // Zapobiegnij standardowemu przesłaniu formularza

                // Pobierz dane z formularza
                const formData = new FormData(this);

                // Sprawdź poprawność adresu IP
                const ipAddress = formData.get('ip_adres');
                try {
                    if (isValidIPv4(ipAddress)) {
                        validationMessage.textContent = '';
                        //checkConnection(ipAddress); // Próba nawiązania połączenia
                    } else {
                        throw new Error('Wprowadź poprawny adres IP (np. 192.168.1.1).');
                    }
                } catch (error) {
                    validationMessage.textContent = error.message;
                    return; // Przerwij proces, jeśli adres IP jest nieprawidłowy
                }

                // Wysyłka danych za pomocą fetch i metody POST
                fetch('http://localhost/studia/SMARTHOME/php_script/device.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        // Tutaj możesz obsłużyć odpowiedź z serwera
                        console.log(data);

                        // Przykład: Wyświetl komunikat po udanym dodaniu urządzenia
                        if (data.success) {
                            alert('Urządzenie dodane pomyślnie.');
                            window.location.reload();
                        } else {
                            alert('Błąd podczas dodawania urządzenia: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Błąd podczas wysyłania żądania:', error);
                    });
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

                const userIP = ipToNumber(ipAddress);

                for (const range of privateIPRanges) {
                    const startIP = ipToNumber(range[0]);
                    const endIP = ipToNumber(range[1]);
                    if (userIP >= startIP && userIP <= endIP) {
                        return true; // Adres IP jest prywatny
                    }
                }

                return false;
            }

            function checkConnection(ipAddress) {
                const img = new Image();
                img.src = `http://${ipAddress}`;

                img.onload = function () {
                    console.log(`Połączono z adresem IP: ${ipAddress}`);
                };

                img.onerror = function () {
                    throw new Error('Nie można nawiązać połączenia z adresem IP.');
                };
            }

            function ipToNumber(ip) {
                const parts = ip.split('.').map(part => parseInt(part, 10));
                return (parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3];
            }
        });

        //powiadomienia
        document.addEventListener('DOMContentLoaded', function () {
            // Pobierz wartości z parametrów URL
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success') === 'true'; // Sprawdź, czy 'success' to '1'
            const message = urlParams.get('message');

            // Wyświetl wyskakujące okno na podstawie otrzymanych danych
            if (message) {
                alert((success ? "Operacja udana: " : " ") + message);
            }
        });






    </script>
</body>
</html>
