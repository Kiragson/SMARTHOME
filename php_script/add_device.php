<?php
session_start();

if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}

require_once("../connected.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pobieramy dane z formularza
    $name = $_POST['name_device'];
    $ipAddress = $_POST['ip_adres'];
    $roomId = $_POST['room'];
    $stan = $_POST['stan'];

    // Sprawdź, czy urządzenie o podanym IP już istnieje w bazie
    $sqlCheck = "SELECT id FROM device WHERE ip = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $ipAddress);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        // Urządzenie o podanym IP już istnieje, przekieruj z komunikatem błędu
        $errorMessage = 'Urządzenie o podanym adresie IP już istnieje.';
        header('Location: http://localhost/studia/SMARTHOME/strony/house.php?error=' . urlencode($errorMessage));
        exit;
    }

    // Urządzenie o podanym IP nie istnieje, więc dodajemy nowe
    $sql = "INSERT INTO device (name, ip, room_id, state) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $name, $ipAddress, $roomId, $stan);

    if ($stmt->execute()) {
        // Urządzenie zostało dodane pomyślnie
        $response = [
            'success' => true,
            
            'userId'=>$user_id,
            'message' => 'Urządzenie zostało dodane pomyślnie.'
        ];
    } else {
        // Błąd podczas dodawania urządzenia
        $response = [
            'success' => false,
            
            'userId'=>$user_id,
            'message' => 'Błąd podczas dodawania urządzenia: ' . $conn->error
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
