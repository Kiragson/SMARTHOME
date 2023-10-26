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
    $sqlCheck = "SELECT id FROM device WHERE ip_adres = ?";
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
    $sql = "INSERT INTO device (name, ip_adres, id_room, stan) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $name, $ipAddress, $roomId, $stan);

    if ($stmt->execute()) {
        // Urządzenie zostało dodane pomyślnie
        $response = [
            'success' => true,
            'message' => 'Urządzenie zostało dodane pomyślnie.'
        ];
        header('Location: http://localhost/studia/SMARTHOME/strony/house.php?message=' . urlencode($response['message']));
    } else {
        // Błąd podczas dodawania urządzenia
        $response = [
            'success' => false,
            'message' => 'Błąd podczas dodawania urządzenia: ' . $conn->error
        ];
        header('Location: http://localhost/studia/SMARTHOME/strony/new_device.php?message=' . urlencode($response['message']));
    }

    // Przekieruj z komunikatem sukcesu lub błędu
    

    $stmt->close();
    exit;
}

?>
