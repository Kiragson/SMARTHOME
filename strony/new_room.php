<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}
require_once("../connected.php");

// Pobierz listę domów użytkownika
$username = $_SESSION['username'];
$user_id=$_SESSION['user_id'];
$domes = [];
$sql = "SELECT house.id, house.name FROM house
        JOIN family ON house.family_id = family.id
        WHERE family.id_admin = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $domes[] = $row;
}
foreach ($domes as $dom) {
    $houseId = $dom['id'];
    $houseName = $dom['name'];
    
    //echo "Identyfikator domu: $houseId, Nazwa domu: $houseName<br>";
}


if (isset($_GET['error'])) {
    $errorMessage = urldecode($_GET['error']);
    echo "<script>alert('$errorMessage');</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pobieramy dane z formularza
    $roomName = $_POST['room_name'];
    $idHouse = $_POST['id_house'];
    
    // Przygotuj zapytanie SQL do dodania nowego pokoju
    $sql = "INSERT INTO room (name, house_id) VALUES (?, ?)";
    
    // Przygotuj zapytanie SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $roomName, $idHouse);
    
    // Wykonaj zapytanie SQL
    if ($stmt->execute()) {
        // Pokój został dodany pomyślnie
        $message = [
            'success' => true,
            'userId'=>$user_id,
            'message' => 'Pokój został dodany pomyślnie.',
        ];
    } else {
        // Błąd podczas dodawania pokoju
        $message = [
            'success' => false,
            'userId'=>$user_id,
            'message' => 'Błąd podczas dodawania pokoju: ' . $conn->error,
        ];
    }
    //wysłanie wiadomosci
    $url='http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
    $ch=curl_init($url);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$message);

    $response=curl_exec($ch);

    echo json_encode($response);

    curl_close($ch);
    // Ustaw nagłówki HTTP
    header('Content-Type: application/json');
    
    // Zwróć odpowiedź w formie JSON
    //echo json_encode($response);
    
    $stmt->close();
    header('Location: http://localhost/studia/SMARTHOME/strony/house.php'); // Zakładam, że masz stronę o nazwie "house.php" z listą domów.
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
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
                    <h3>Nowy Pokój</h3>
                </div>
                <form action="new_room.php" method="POST" id="new_room-form">
                    <div class='row justify-content-center mt-5'>
                        <div class="mb-3">
                            <label for="room_name" class="form-label">Nazwa pokoju</label>
                            <input type="text" class="form-control" id="room_name" name="room_name" aria-describedby="text" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_house" class="form-label">Wybierz dom:</label>
                            <select class="form-select" name="id_house" id="id_house">
                                <?php foreach ($domes as $dom): ?>
                                    <option value="<?php echo $dom['id']; ?>"><?php echo $dom['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='row justify-content-center mt-5'>
                        <div class="col-4 justify-content-center row">
                            <button class="btn btn-primary p-2" type="submit">Dodaj Pokój</button>
                        </div>
                    </div>
                </form>
            </div>
        </div> 
    </div>
</body>
</html>
