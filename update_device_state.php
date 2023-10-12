<?php
// Włącz obsługę sesji, jeśli jeszcze nie jest włączona
session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

// Połącz się z bazą danych
require_once("connected.php");

function updateDeviceStateInDatabase($conn, $deviceId) {
    // Pobierz bieżący stan urządzenia z bazy danych
    $sql = "SELECT stan FROM device WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
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
        $response['message'] = "Stan urządzenia został zaktualizowany.";
        $response['newDeviceState'] = $newDeviceState; // Nowy stan urządzenia
    } else {
        // Błąd podczas aktualizacji stanu urządzenia
        $response['success'] = false;
        $response['message'] = "Błąd podczas aktualizacji stanu urządzenia: " . $conn->error;
    }

    $stmt->close();
}

// Zakończ połączenie z bazą danych
$conn->close();

// Ustaw nagłówki odpowiedzi JSON
header('Content-Type: application/json');

// Zwróć odpowiedź jako JSON
echo json_encode($response);

?>
