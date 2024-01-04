<?php

    session_start();

    $response = array(); // Tworzymy pusty tablicę na odpowiedź

    if (isset($_GET['id_domu'])) {
        $id_domu = $_GET['id_domu'];

        // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
        require_once("../connected.php");

        // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
        $sql = "SELECT h.name, h.city,h.postcode, h.family_id, f.user1, f.user2, f.user3, f.user4, f.user5, f.user6
            FROM House h 
            LEFT JOIN Family f ON f.id = h.family_id
            WHERE h.id = ?";
        // Przygotuj zapytanie SQL
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_domu);

        $stmt->execute();
        $result = $stmt->get_result();

        $housesinfo="";

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc(); // Pobierz dane do tablicy $row

            $houseCity = $row['city'];        
            $zipcode=$row['postcode'];
            $houseName = $row['name'];
            $familyId = $row['family_id'];
            $user1 = $row['user1'];
            $user2 = $row['user2'];
            $user3 = $row['user3'];
            $user4 = $row['user4'];
            $user5 = $row['user5'];
            $user6 = $row['user6'];
        }


        $conn->close();


    }else{
        header('Location: http://localhost/studia/SMARTHOME/404.html');
    }
    ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowy Współlokator</title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
    <script>
        // Funkcja do sprawdzania, czy wartości w inputach nie są takie same
        function checkUniqueValues() {
            var user2 = document.getElementById("user2").value;
            var user3 = document.getElementById("user3").value;
            var user4 = document.getElementById("user4").value;
            var user5 = document.getElementById("user5").value;
            var user6 = document.getElementById("user6").value;

            var values = [user2, user3, user4, user5, user6];
            var uniqueValues = [...new Set(values)]; // Usunięcie duplikatów

            if (values.length !== uniqueValues.length) {
                alert("Wartości w inputach nie mogą być takie same.");
                return false;
            }

            return true;
        }

        // Nasłuchuj na submit formularza i wykonaj sprawdzenie przed przesłaniem
        document.querySelector("form").addEventListener("submit", function (event) {
            if (!checkUniqueValues()) {
                event.preventDefault(); // Zatrzymaj wysyłanie formularza
            }
        });
    </script>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h1>Informacje <?php echo $houseName; ?></h1>
                <form action="../php_script/house.php" method="POST">
                <div class="p-2 m-2">
                    <p>Miasto: <?php if (isset($houseCity)) { echo $houseCity; echo ' '; } else { echo 'Brak'; } ?><?php if (isset($zipcode)) { echo $zipcode; } else { echo '   -   '; } ?></p>
                </div>
                <h1>Współlokatorzy</h1>
                <div class="mb-3">
                    <label for="user2" class="form-label">Lokator: </label>
                    <input type="text" class="mb-1 form-control" id="user2" name="user2" value="<?php echo isset($user2) ? $user2 : 'Dodaj lokatora'; ?>" aria-describedby="emailHelp">
                    <input type="text" class="mb-1 form-control" id="user3" name="user3" value="<?php echo isset($user3) ? $user3 : 'Dodaj lokatora'; ?>" aria-describedby="emailHelp">
                    <input type="text" class="mb-1 form-control" id="user4" name="user4" value="<?php echo isset($user4) ? $user4 : 'Dodaj lokatora'; ?>" aria-describedby="emailHelp">
                    <input type="text" class="mb-1 form-control" id="user5" name="user5" value="<?php echo isset($user5) ? $user5 : 'Dodaj lokatora'; ?>" aria-describedby="emailHelp">
                    <input type="text" class="mb-1 form-control" id="user6" name="user6" value="<?php echo isset($user6) ? $user6 : 'Dodaj lokatora'; ?>" aria-describedby="emailHelp">
                    <input type="hidden" name="family_id" id="family_id" value="<?php echo isset($familyId) ? $familyId : ''; ?>">
                    <input type="hidden" name="rodzaj" id="rodzaj" value="new_roommate">
                </div>
                <button class="btn btn-warning rounded p-2 w-100" type="submit">Zapisz zmiany</button>
            </form>

            </div>
        </div>
    </div>
    <script>
    
</script>
</body>
</html>