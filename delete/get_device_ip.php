<?php

$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

if ($device_id === null) {
    // Błąd - brak lub nieprawidłowy parametr device_id
    $response = array('success' => false, 'message' => 'Brak lub nieprawidłowy parametr device_id');
} else {
    require_once("../connected.php");
    $sql = "SELECT ip FROM device WHERE id = :device_id";

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=smarthome", "witryna", "zaq1@WSX");
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':device_id', $device_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $device_ip = $result['ip']; // Poprawiam na 'device_ip'
            // Tutaj masz ip urządzenia
        } else {
            // Urządzenie o podanym ID nie istnieje
            $response = array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
        }
    } catch (PDOException $e) {
        // Obsługa błędu związana z bazą danych
        $response = array('success' => false, 'message' => 'Blad bazy danych: ' . $e->getMessage());
    }

    // Jeśli wszystko jest w porządku, przygotuj odpowiedź JSON
    if (!isset($response)) {
        $response = array('success' => true, 'device_id' => $device_id, 'ip' => $device_ip);
    }
}

// Ustaw nagłówki dla odpowiedzi JSON
header('Content-Type: application/json');

// Wyślij odpowiedź JSON
echo json_encode($response);
?>
