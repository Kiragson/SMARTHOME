<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}

$response = array(); // Pusta tablica na odpowiedź

function getUserName($user_id, $conn) {
    $sql = "SELECT first_name, last_name FROM user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    $stmt->execute();
    $result = $stmt->get_result();

    $full_name = '';

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $full_name = $row['first_name'] . ' ' . $row['last_name'];
    }

    $stmt->close(); // Zamknij statement

    return $full_name;
}

try {
    require_once("../connected.php");

    if (isset($_GET['id_domu'])) {
        $user_id = $_SESSION['user_id'];
        $id_domu = $_GET['id_domu'];

        $sql = "SELECT h.name, h.city, h.postcode, h.family_id, f.user1, f.user2, f.user3, f.user4, f.user5, f.user6
                FROM House h 
                LEFT JOIN Family f ON f.id = h.family_id
                WHERE h.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_domu);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $houseCity = $row['city'];
            $zipcode = $row['postcode'];
            $houseName = $row['name'];
            $familyId = $row['family_id'];
            
            $user_ids = array('user1', 'user2', 'user3', 'user4', 'user5', 'user6');
            foreach ($user_ids as $user_index => $user_field) {
                ${"user" . ($user_index + 1)} = $row[$user_field];
                ${"user" . ($user_index + 1) . "_name"} = getUserName($row[$user_field], $conn);
            }

            if (!in_array($user_id, array($user1, $user2, $user3, $user4, $user5, $user6))) {
                header("Location: house.php");
                exit();
            }
        } else {
            // Brak danych o domu
            header("Location: http://localhost/studia/SMARTHOME/404.html");
            exit();
        }
    } else {
        // Brak parametru 'id_domu'
        header('Location: http://localhost/studia/SMARTHOME/404.html');
        exit();
    }
} catch (Exception $e) {
    // Obsługa błędów
    echo "Wystąpił błąd: " . $e->getMessage();
} finally {
    // Zamknięcie połączenia
    if (isset($conn)) {
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $houseName; ?></title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <div id="oldStructure_house">
                    <h1>Informacje o: </h1><h2 class="ms-4"><?php echo isset($houseName) ? $houseName : ''; ?></h2>
                    
                    <div class="p-2 m-2">
                        <p>Miasto: <?php echo isset($houseCity) ? $houseCity : 'Brak'; ?> <?php echo isset($zipcode) ? $zipcode : '   -   '; ?></p>
                    </div>
                </div>
                
                <h1>Współlokatorzy</h1>
                <div class="p-2 m-2">
                    <?php
                    echo "<p>Właściciel: " . ${"user" . 1 . "_name"} . "</p>";
                    for ($i = 2; $i <= 6; $i++) {
                        echo "<p>Lokator: " . ${"user" . $i . "_name"} . "</p>";
                    }
                    ?>
                </div>

                <div class="container">
                    <div class="row justify-content-center mt-5">
                        <div class="col-4">
                            <button class="btn btn-warning rounded p-2 w-100" type="button"data-bs-target="#editHouseModal" onclick="openNewRoommateModal()">Dodaj lokatorów</button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-warning rounded p-2 w-100" onclick="confirmHouseDelete('<?php echo $id_domu; ?>', '<?php echo $houseName; ?>');">Usuń</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-warning rounded p-2 w-100" onclick="openEditHouseModal()">Edytuj informacje o domu</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--edit house-->
    <div class="modal fade" id="editHouseModal" tabindex="-1" aria-labelledby="editHouseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHouseModalLabel">Edycja Informacji o: <?php echo isset($houseName) ? $houseName : ''; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editHouseForm">
                        <div class="p-2 m-2">
                            <label for="city" class="form-label">Nazwa: </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="<?php echo isset($houseName) ? $houseName : 'Wpisz nazwe'; ?>" aria-describedby="house_name">
                            <label for="city" class="form-label">Miasto: </label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="<?php echo isset($houseCity) ? $houseCity : 'Wpisz miasto'; ?>" aria-describedby="house_city">
                            <label for="postalCode" class="form-label">Kod pocztowy</label>
                            <input type="text" class="form-control" id="postalCode" name="postalCode" placeholder="<?php echo isset($zipcode) ? $zipcode : 'Wpisz Kod pocztowy'; ?>" aria-describedby="house_zipcode">
                            <input type="hidden" id="method" name="method" value="edit_house">
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
    <!--new roommate-->
    <div class="modal fade" id="newRoommateModal" tabindex="-1" aria-labelledby="newRoommateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newRoommateModalLabel">Dodaj lokatorów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newRoommateForm">
                        <?php
                        for ($i = 2; $i <= 6; $i++) {
                            echo "<label for='user{$i}' class='form-label'>Lokator: </label>";
                            echo "<input type='text' class='mb-1 form-control' name='user{$i}' value='" . (isset(${"user" . $i}) ? ${"user" . $i . "_name"} : 'Lokator nie dodany') . "' data-default-value='" . (isset(${"user" . $i}) ? ${"user" . $i . "_name"} : 'Lokator nie dodany') . "' aria-describedby='user'>";
                        }
                        ?>

                        <input type="hidden" name="family_id" value="<?php echo isset($familyId) ? $familyId : ''; ?>">
                        <input type="hidden" name="method" value="new_roommate">
                    </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                        <button type="button" class="btn btn-warning" onclick="submitEditRoommate()">Zapisz zmiany</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById("myForm");

            form.addEventListener('focus', function(event) {
                if (event.target.tagName === 'INPUT' && event.target.type === 'text') {
                    var defaultValue = event.target.getAttribute('data-default-value');
                    if (event.target.value === defaultValue) {
                        event.target.value = '';
                    }
                }
            }, true);

            form.addEventListener('blur', function(event) {
                if (event.target.tagName === 'INPUT' && event.target.type === 'text' && event.target.value === '') {
                    var defaultValue = event.target.getAttribute('data-default-value');
                    event.target.value = defaultValue;
                }
            }, true);
        });

        function openNewRoommateModal() {
            var newRoommateModal = new bootstrap.Modal(document.getElementById('newRoommateModal'));
            newRoommateModal.show();
        }
        function openEditHouseModal() {
            // Otwórz modal
            var editHouseModal = new bootstrap.Modal(document.getElementById('editHouseModal'));
            editHouseModal.show();
            
            // Ustaw wartości pól formularza na aktualne dane
            document.getElementById('city').value = '<?php echo isset($houseCity) ? $houseCity : ''; ?>';
            document.getElementById('postalCode').value = '<?php echo isset($zipcode) ? $zipcode : ''; ?>';
        }

        function submitEditRoommate() {
            var form = document.getElementById("newRoommateForm");
            var formData = new FormData(form);

            fetch('../php_script/house.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                            alert('Zmieniono informacje o lokatorach');
                            window.location.reload();
                } else {
                    alert('Błąd zmiany lokatorów: ' + data.message);
                }
            })
            .catch(error => console.error('Błąd:', error));
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

        function confirmHouseDelete(house_id, houseName) {
            var isConfirmed = confirm("Czy na pewno chcesz usunąć dom " + houseName + "?");

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
                    console.log(data);
                    if (data.success) {
                        alert('Pokój usunięty pomyślnie.');
                        window.location.href = 'house.php';
                    } else {
                        alert('Błąd podczas usuwania pokoju: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Błąd podczas wysyłania żądania:', error);
                });
            }
        }
    </script>
</body>
</html>
