<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
        header('Location: login.php');
        exit;
    }
    require_once("../connected.php");

    // Pobierz listę miast z bazy danych
    $sql = "SELECT PostalCode, CityName FROM Cities";
    $result = $conn->query($sql);

    // Utwórz listę opcji dla miast
    $cityOptions = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $postalCode = $row["PostalCode"];
            $cityName = $row["CityName"];
            $cityOptions .= "case '$postalCode': citySelect.options.add(new Option('$cityName', '$cityName')); break;";
        }
    }
    
    // Pobierz zalogowanego użytkownika (zakładamy, że masz mechanizm uwierzytelniania)
    $login = $_SESSION['username'];

    // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
    $sql = "SELECT u.id, u.login, u.number_of_houses AS NOH, 
        h.id AS house_id, h.name AS house_name, h.city as house_city, h.postcode as house_zipcode,
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
            $houseCity=$row['house_city'];
            $housePostcode=$row['house_zipcode'];
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
                    'postcode'=>$housePostcode,
                    'city'=>$houseCity,
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
                        <!--nagłówek-->
                        <div class='row justify-content-center mt-5'>
                            <div class='col-10'>
                                <h3><?php echo $houseData['name']; ?></h3> <?php $houseId; ?>
                            </div>
                            <div class="col-1 dropdown">
                                <button class="btn" type="button" id="dropdownMenuButtonHouse<?php echo $houseData['id_house']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-sliders"></i>
                                </button>
                                
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonHouse<?php echo $houseData['id_house']; ?>">
                                
                                    <button class="dropdown-item btn" data-bs-toggle="modal" data-bs-target="#editHouseModal_<?php echo $houseData['id_house']; ?>">Edytuj</button>

                                    <button class="dropdown-item btn" onclick="confirmHouseDelete('<?php echo $houseData['id_house']; ?>', '<?php echo $houseData['name']; ?>');">Usuń</button>
                                    <a class="dropdown-item" href="info_house.php?id_domu=<?php echo $houseData['id_house']; ?>" >Informacje</a>
                                </div>
                                <!--edit house-->
                                <div class="modal fade" id="editHouseModal_<?php echo $houseData['id_house']; ?>" tabindex="-1" aria-labelledby="editHouseModalLabel" aria-hidden="true">
                                
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editHouseModalLabel">Edycja Informacji o: <?php echo isset($houseData['name']) ? $houseData['name'] : ''; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form id="editHouseForm">
                                                    <div class="p-2 m-2">
                                                        <label for="city" class="form-label">Nazwa: </label>
                                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($houseData['name']) ? $houseData['name'] : 'Wpisz nazwe'; ?>" aria-describedby="house_name">
                                                        <label for="city" class="form-label">Miasto: </label>
                                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($houseData['city']) ? $houseData['city'] : 'Wpisz miasto'; ?>" aria-describedby="house_city">
                                                        <label for="postalCode" class="form-label">Kod pocztowy</label>
                                                        <input type="text" class="form-control" id="postalCode" name="postalCode" value="<?php echo isset($houseData['postcode']) ? $houseData['postcode'] : 'Wpisz Kod pocztowy'; ?>" aria-describedby="house_zipcode">
                                                        <input type="hidden" id="method" name="method" value="edit_House">
                                                        <input type="hidden" id="house_id" name="house_id" value="<?php echo $houseData['id_house']; ?>">
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                                                <button type="button" class="btn btn-warning" onclick="submitInfoHouse()">Zapisz zmiany</button>
                                            </div>
                                        </div>
                                    </div>
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
                                                <button class="dropdown-item btn" data-bs-toggle="modal" data-bs-target="#updateRoom_<?php echo $roomId; ?>">Edytuj</button>
                                                <button class="dropdown-item btn" onclick="confirmRoomDelete('<?php echo $roomId; ?>', '<?php echo $roomData['name']; ?>');">Usuń</button>
                                                <a class="dropdown-item" href="info_room.php?id_room=<?php echo $roomId; ?>">Informacje</a>
                                            </div>
                                            <!--updateroom-->
                                            <div class="modal fade" id="updateRoom_<?php echo $roomId; ?>" tabindex="-1" aria-labelledby="updateRoom_label" aria-hidden="true">
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
                                                                        <input type="text" class="form-control" id="name_room" name="name_room" value="<?php echo $roomData['name']; ?>" aria-describedby="text">
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
                                    <!-- wyświetlanie i obsługa urządzeń -->
                                    <?php if (!empty($roomData['devices'])): ?>
                                        <?php foreach ($roomData['devices'] as $deviceData): ?>
                                            <div class="mt-2 row">
                                                <div class="col-3 p-2">
                                                    <?php echo $deviceData['name']; ?>
                                                </div>
                                                <div class="col-2 p-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="deviceSwitch_<?php echo $deviceData['id'] ?>" onchange="toggleDevice(<?php echo $deviceData['id'] ?>)" <?php echo ($deviceData['stan'] == 1) ? "checked" : ""; ?>>
                                                        <label class="form-check-label" for="deviceSwitch_<?php echo $deviceData['id'] ?>"></label>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <button class="btn" onclick="confirmDeviceDelete('<?php echo $deviceData['id'] ?>', '<?php echo $deviceData['name']; ?>');"><i class="bi bi-trash3"></i></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class='text-center my-5'>
                                            <p class="h4">Brak urządzeń w pokoju</p>
                                        </div>
                                    <?php endif; ?>
                                    <!--new device Button-->
                                    <div class="container">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModalRoom_<?php echo $roomId; ?>" data-roomid="<?php echo $roomId; ?>">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                    </div>

                                    <!--modal addDevice-->
                                    <div class="modal fade" id="addDeviceModalRoom_<?php echo $roomId; ?>" tabindex="-1" aria-labelledby="add_device_label" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="add_device_label">Dodaj nowe urządzenie</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="add_device-form" data-roomid="<?php echo $roomId; ?>">
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

                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class='text-center my-5'>
                                <p class="h4">Brak Pokoi</p>
                            </div>
                        <?php endif; ?>
                        <!--new room button-->
                        <div class='row justify-content-center mt-4'>
                            <div class="col-4 justify-content-center">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal" data-houseid="<?php echo $houseData['id_house']; ?>">
                                    Dodaj pokój
                                </button>

                            </div>
                            
                        </div>
                        <!--formularz dodawania pokoju-->
                        <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addRoomModalLabel">Dodaj nowy pokój</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Formularz dodawania pokoju -->
                                        <form id="addRoomForm">
                                            <div class="mb-3">
                                                <label for="roomName" class="form-label">Nazwa pokoju:</label>
                                                <input type="text" class="form-control" id="roomName" name="roomName" required>
                                            </div>
                                            <div class="mb-3">
                                                <input type="hidden" class="form-control" id="houseId" name="houseId" value="<?php echo $houseData['id_house']; ?>">
                                                <input type="hidden" class="form-control" id="method" name="method" value="newRoom">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Dodaj pokój</button>
                                        </form>
                                    </div>
                                </div>
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
                    <!-- Dodaj ten kod w miejscu, gdzie chcesz wyświetlić przycisk do otwierania formularza -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHouseModal">
                        Dodaj nowy dom
                    </button>

                    <!-- Modal nowy dom -->
                    <div class="modal fade" id="addHouseModal" tabindex="-1" aria-labelledby="addHouseModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addHouseModalLabel">Dodaj nowy dom</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Formularz dodawania nowego domu -->
                                    <form method="POST" id="add_house-form">
                                        <div class="mb-3">
                                            <h3 class="mb-4">Nowy Dom</h3>
                                        </div>
                                        <div class='row justify-content-center mt-3'>
                                            <div class="col-md-6">
                                                <label for="nazwa_domu" class="form-label">Nazwa domu:</label>
                                                <input type="text" class="form-control" id="name_house" name="nazwa_domu" aria-describedby="text" required>
                                            </div>
                                        </div>
                                        <div class='row justify-content-center mt-3'>
                                            <div class="col-md-6">
                                                <label for="postalCode" class="form-label">Kod pocztowy:</label>
                                                <input type="text" class="form-control" id="postalCode" name="postalCode">
                                            </div>
                                        </div>
                                        <div class='row justify-content-center mt-3'>
                                            <div class="col-md-6">
                                                <label for="city" class="form-label">Miasto:</label>
                                                <input type="text" id="city" class="form-control" name="city">   
                                            </div>
                                        </div>
                                        <div class='row justify-content-center mt-5'>
                                            <div class="col-md-6 text-center">
                                                <input type="hidden" name="method" value="newHouse">
                                                <button class="btn btn-primary" type="submit">Dodaj Dom</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

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

    document.getElementById("postalCode").addEventListener("change", function() {
        console.log("zipcode");
        var selectedPostalCode = this.value;
        var citySelect = document.getElementById("city");
        citySelect.innerHTML = ""; // Wyczyść pole wyboru miasta
        switch (selectedPostalCode) {
            case '0':
                alert('Błąd');
                break;
            <?php echo $cityOptions; ?>
            default:
                // W przypadku braku pasującej miejscowości, daj możliwość wpisania niestandardowej
                citySelect.options.add(new Option("Inne", "Inne"));
        }

    });
    function addHouse() {
        // Pobierz dane z formularza
        const formData = new FormData(document.getElementById('add_house-form'));

        // Wysyłka danych za pomocą fetch i metody POST
        fetch('http://localhost/studia/SMARTHOME/php_script/house.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            // Obsługa odpowiedzi z serwera
            console.log(data);

            // Przykład: Wyświetl komunikat po udanym dodaniu domu
            if (data.success) {
                alert('Dom dodany pomyślnie.');
                
            } else {
                alert('Błąd podczas dodawania domu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Błąd podczas wysyłania żądania:', error);
        });
    }
    function confirmRoomDelete(room_id, roomName) {
            // Wywołaj modal z potwierdzeniem
            var isConfirmed = confirm("Czy na pewno chcesz usunąć pokój " + roomName + "?");
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
                        alert('Pokój usunięty pomyślnie.');
                        // Tutaj możesz dodać dodatkową logikę, jeśli potrzebujesz
                        window.location.reload();
                    } else {
                        alert('Błąd podczas usuwania pokoju: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd podczas wysyłania żądania:', error);
                });
            }
        }
    function confirmHouseDelete(house_id, houseName) {
        // Wywołaj modal z potwierdzeniem
        var isConfirmed = confirm("Czy na pewno chcesz usunąć dom " + houseName + "?");
        console.log(house_id)
        // Jeśli użytkownik potwierdzi, wykonaj usunięcie
        if (isConfirmed) {
            fetch('http://localhost/studia/SMARTHOME/php_script/house.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'method=delete&house_id=' + house_id,
            })
            .then(response => response.json())
            .then(data => {
                // Obsługa odpowiedzi z serwera
                console.log(data);
                if (data.success) {
                    alert('Pokój usunięty pomyślnie.');
                    window.location.reload();
                } else {
                    alert('Błąd podczas usuwania pokoju: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Błąd podczas wysyłania żądania:', error);
            });
        }
    }
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
        console.log("House.php/222: Przycisk kliknięty dla urządzenia o ID: " + device_id);

        // Przygotuj dane do wysłania jako JSON
        var message = JSON.stringify({ device_id: device_id, state: currentState });

        // Wyślij dane na serwer WebSocket
        socket.send(message);
        console.log("House.php/229: Wysyłanie danych na serwer");
        console.log('Koniec toggleDevice');
    };

    //sterowanie wyskakującego okna dodania urządzenia
    document.addEventListener('DOMContentLoaded', function () {
        const addDeviceForms = document.querySelectorAll('form[id^="add_device-form"]');

        addDeviceForms.forEach(function (addDeviceForm) {
            const ipAddressInput = addDeviceForm.querySelector('.form-control[name="ip_adres"]');
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
    //obsługa formularza nowy dom
    document.addEventListener('DOMContentLoaded', function() {
        // Pobierz formularz
        const addHouseForm = document.getElementById('add_house-form');

        // Dodaj nasłuchiwanie na zdarzenie submit formularza
        addHouseForm.addEventListener('submit', function(event) {
            // Zapobiegnij domyślnej akcji formularza (przesłaniu danych i przeładowaniu strony)
            event.preventDefault();

            // Pobierz dane z formularza
            const formData = new FormData(addHouseForm);

            // Wyślij żądanie POST do odpowiedniego adresu URL (zmień na swój)
            fetch('http://localhost/studia/SMARTHOME/php_script/house.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Tutaj możesz obsługiwać odpowiedź od serwera
                console.log(data);
                if (data.success) {
                        alert('Dom dodany pomyśłini.');
                        // Tutaj możesz dodać dodatkową logikę, jeśli potrzebujesz
                        window.location.reload();
                    } else {
                        alert('Błąd podczas dodawania domu: ' + data.message);
                    }
            })
            .catch(error => {
                console.error('Błąd podczas wysyłania żądania:', error);
            });
        });
    });


    //sterowanie formularz dodawania pokoju
    document.addEventListener('DOMContentLoaded', function () {
        const addRoomButtons = document.querySelectorAll('.btn-primary[data-bs-toggle="modal"]');

        addRoomButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const houseId = this.getAttribute('data-houseid');
                // Ustawienie aktualnego house_id w formularzu
                document.getElementById('houseId').value = houseId;
            });
        });

        const addRoomForm = document.getElementById('addRoomForm');
        const roomNameInput = document.querySelector('#roomName');
        const houseIdInput = document.querySelector('#houseId');
        const validationMessage = document.createElement('span');
        validationMessage.className = 'text-danger';
        roomNameInput.parentNode.appendChild(validationMessage);

        addRoomForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Zapobiegnij standardowemu przesłaniu formularza

            // Pobierz dane z formularza
            const formData = new FormData(this);

            // Sprawdź poprawność nazwy pokoju
            const roomName = formData.get('roomName');
            if (!roomName || roomName.trim() === '') {
                validationMessage.textContent = 'Wprowadź nazwę pokoju.';
                return;
            } else {
                validationMessage.textContent = '';
            }

            // Wysyłka danych za pomocą fetch i metody POST
            fetch('http://localhost/studia/SMARTHOME/php_script/room.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                // Tutaj możesz obsłużyć odpowiedź z serwera
                console.log(data);

                // Przykład: Wyświetl komunikat po udanym dodaniu pokoju
                if (data.success) {
                    alert('Pokój dodany pomyślnie.');
                    window.location.reload();
                } else {
                    alert('Błąd podczas dodawania pokoju: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Błąd podczas wysyłania żądania:', error);
            });
        });
    });

    //sterowanie formularza edycji domu
    function openEditHouseModal(id) {
        // Otwórz modal
        var editHouseModal = new bootstrap.Modal(document.getElementById('editHouseModal'));
        editHouseModal.show();

        // Pobierz dane z atrybutów
        var city = document.querySelector('[data-city="' + id + '"]').dataset.city;
        var postalCode = document.querySelector('[data-postal-code="' + id + '"]').dataset.postalCode;

        // Ustaw wartości pól formularza
        document.getElementById('city').value = city;
        document.getElementById('postalCode').value = postalCode;
    }
    function submitInfoHouse() {
        var form = document.getElementById("editHouseForm");
        var formData = new FormData(form);

        fetch('../php_script/house.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                        alert('Zmieniono informacje domu');
                        window.location.reload();
            } else {
                alert('Błąd zmiany domu: ' + data.message);
            }
        })
        .catch(error => console.error('Błąd:', error));
    }



</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
