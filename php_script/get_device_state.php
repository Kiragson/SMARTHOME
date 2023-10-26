<?php
// Połączenie z bazą danych (jeśli jest używane)
require_once("../connected.php"); // Zakładam, że "connected.php" zawiera kod do nawiązania połączenia z bazą danych

// Odczytaj parametr device_id z zapytania
$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

if ($device_id === null) {
    // Błąd - brak lub nieprawidłowy parametr device_id
    $response = array('success' => false, 'message' => 'Brak lub nieprawidłowy parametr device_id');
} else {
    // Przygotuj zapytanie SQL z użyciem zabezpieczeń przed SQL Injection (PDO)
    $sql = "SELECT stan FROM device WHERE id = :device_id";

    // Przygotuj i wykonaj zapytanie
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=your_database_name", "your_username", "your_password");
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':device_id', $device_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $device_state = $result['stan'];
            // Tutaj masz stan urządzenia
        } else {
            // Urządzenie o podanym ID nie istnieje
            $response = array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
        }
    } catch (PDOException $e) {
        // Obsługa błędu związana z bazą danych
        $response = array('success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage());
    }

    // Jeśli wszystko jest w porządku, przygotuj odpowiedź JSON
    if (!isset($response)) {
        $response = array('success' => true, 'device_id' => $device_id, 'state' => $device_state);
    }
}

// Ustaw nagłówki dla odpowiedzi JSON
header('Content-Type: application/json');

// Wyślij odpowiedź JSON
echo json_encode($response);
?>
