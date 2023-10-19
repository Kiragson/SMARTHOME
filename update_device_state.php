<?php
// Włącz obsługę sesji, jeśli jeszcze nie jest włączona
session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

// Połącz się z bazą danych
require_once("connected.php");

// $conn odzwierciedla połączenie z bazą
function updateDeviceStateInDatabase($conn, $deviceId) {
    //echo("Komunikacja serwer(php) "+$deviceId);
    if(isset($deviceId))
    {
        // Pobierz bieżący stan urządzenia z bazy danych
    $sql = "SELECT stan FROM device WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $currentDeviceState=0;
    $stmt->bind_result($currentDeviceState);
    $stmt->fetch();
    $stmt->close();

    // Odwróć bieżący stan urządzenia (zmień 0 na 1 i odwrotnie)
    $newDeviceState = ($currentDeviceState == 0) ? 1 : 0;

    // Przygotuj zapytanie SQL, aby zaktualizować stan urządzenia
    $updateSql = "UPDATE device SET stan = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $newDeviceState, $deviceId);

    // Wykonaj zaktualizowanie stanu urządzenia
    if ($stmt->execute()) {
        // Zaktualizowano stan urządzenia pomyślnie
        $response['success'] = true;
        $response['message'] = "update_device_state.php: Stan urządzenia został zaktualizowany.";
        $response['newDeviceState'] = $newDeviceState; // Nowy stan urządzenia
    } else {
        // Błąd podczas aktualizacji stanu urządzenia
        $response['success'] = false;
        $response['message'] = "update_device_state.php: Błąd podczas aktualizacji stanu urządzenia : " . $stmt->error;
    }

    $stmt->close();
    }
    else {
        $response['success'] = false;
        $response['message'] = "update_device_state.php:  Zmienna deviceId jest pusta";
    }
    
}

// Zakończ połączenie z bazą danych
$conn->close();

// Ustaw nagłówki odpowiedzi JSON
header('Content-Type: application/json');

// Zwróć odpowiedź jako JSON
echo json_encode($response);

?>
