<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
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

//var_dump($cityOptions);
// Zamknij połączenie z bazą danych
$conn->close();



?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowy pokój</title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
</head>
<body>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class='col-8 navbar-light mt-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                <div class="mb-3">
                    <h3>Nowy Dom</h3>
                </div>
                <form action="new_room.php" method="POST" id="new_room-form">
                    <div class='row justify-content-center mt-5'>
                        <div class="mb-3">
                            <label for="room_name" class="form-label">Nazwa domu</label>
                            <input type="text" class="form-control" id="room_name" name="room_name" aria-describedby="text" required>
                        </div>
                        <div class="mb-3">
                            <label for="postalCode" class="form-label">Kod pocztowy</label>
                            <input type="text" class="form-control" id="postalCode" name="postalCode">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">Miasto</label>
                            <select id="city" class="form-control" name="city">
                                <option value="">Wpisz Miasto</option>
                            </select>
                        </div>
                    </div>
                    <div class='row justify-content-center mt-5'>
                        <div class="col-4 justify-content-center row">
                            <button class="btn btn-primary p-2" type="submit">Dodaj Dom</button>
                        </div>
                    </div>
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
