<?php

session_start();

require_once("../connected.php");

class DeviceManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addDevice($name, $ipAddress, $roomId, $stan)
    {
        $response = [];

        // Sprawdź, czy urządzenie o podanym IP już istnieje w bazie
        $sqlCheck = "SELECT id FROM device WHERE ip = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $ipAddress);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            // Urządzenie o podanym IP już istnieje
            $response = [
                'success' => false,
                'message' => 'Urządzenie o podanym adresie IP już istnieje.'
            ];
        } else {
            // Urządzenie o podanym IP nie istnieje, więc dodajemy nowe
            $sql = "INSERT INTO device (name, ip, room_id, state) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssii", $name, $ipAddress, $roomId, $stan);

            if ($stmt->execute()) {
                // Urządzenie zostało dodane pomyślnie
                $response = [
                    'success' => true,
                    'message' => 'Urządzenie zostało dodane pomyślnie.'
                ];

                // Wysłanie wiadomości
                $message = [
                    'userId' => $_SESSION['user_id'],
                    'message' => 'Urządzenie zostało dodane pomyślnie.'
                ];

                $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

                $curlResponse = curl_exec($ch);

                curl_close($ch);

            } else {
                // Błąd podczas dodawania urządzenia
                $response = [
                    'success' => false,
                    'message' => 'Błąd podczas dodawania urządzenia: ' . $this->conn->error
                ];
            }

            $stmt->close();
        }

        // Ustaw nagłówki HTTP
        header('Content-Type: application/json');

        // Zwróć odpowiedź w formie JSON
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pobieramy dane z formularza
    $name = $_POST['name_device'];
    $ipAddress = $_POST['ip_adres'];
    $roomId = $_POST['room'];
    $stan = $_POST['stan'];

    // Sprawdź, czy użytkownik jest zalogowany
    if (!isset($_SESSION['username'])) {
        // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
        header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
        exit;
    }

    $deviceManager = new DeviceManager($conn);
    $deviceManager->addDevice($name, $ipAddress, $roomId, $stan);
}

// Pozostała część kodu, jeśli istnieje
?>
