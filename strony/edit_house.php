<?php

session_start();

$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['id_domu'])) {
    $id_domu = $_GET['id_domu'];

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    require_once("../connected.php");

    // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
    $sql = "SELECT h.name, h.city, h.family_id, f.user1, f.user2, f.user3, f.user4, f.user5, f.user6
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

        $houseCity = $row['city']; // Poprawione pobieranie danych
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
    <title>Edytuj <?php echo $houseName; ?></title>
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
                <form action="#">
                    <div class="mb-3">
                        <label for="" class="form-label">Miasto: </label>
                        <input type="text" class="form-control" id="city" name="city" placeholder="<?php if (isset( $houseCity)){echo $houseCity;}else{echo 'Wpisz miasto';} ?>" aria-describedby="emailHelp">
                        <label for="postalCode" class="form-label">Kod pocztowy</label>
                        <input type="text" class="form-control" id="postalCode" name="postalCode" placeholder="<?php if (isset( $houseCity)){echo $houseCity;}else{echo 'Wpisz miasto';} ?>" aria-describedby="emailHelp">
                    </div>
                    <h1>Współlokatorzy</h1>
                    <div class="mb-3">
                        <label for="" class="form-label">Lokator: </label>
                        <input type="text" class="mb-1 form-control" id="user2" name="user2" placeholder="<?php if (isset($user2)) { echo $user2; } else { echo 'Dodaj lokatora'; } ?>" aria-describedby="emailHelp">
                        <input type="text" class="mb-1 form-control" id="user3" name="user3" placeholder="<?php if (isset($user3)) { echo $user3; } else { echo 'Dodaj lokatora'; } ?>" aria-describedby="emailHelp">
                        <input type="text" class="mb-1 form-control" id="user4" name="user4" placeholder="<?php if (isset($user4)) { echo $user4; } else { echo 'Dodaj lokatora'; } ?>" aria-describedby="emailHelp">
                        <input type="text" class="mb-1 form-control" id="user5" name="user5" placeholder="<?php if (isset($user5)) { echo $user5; } else { echo 'Dodaj lokatora'; } ?>" aria-describedby="emailHelp">
                        <input type="text" class="mb-1 form-control" id="user6" name="user6" placeholder="<?php if (isset($user6)) { echo $user6; } else { echo 'Dodaj lokatora'; } ?>" aria-describedby="emailHelp">
                    </div>
                    <button class="btn btn-warning rounded p-2 w-100"type="submit">Zapisz zmiany</button>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.getElementById("postalCode").addEventListener("change", function() {
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
</script>
</body>
</html>