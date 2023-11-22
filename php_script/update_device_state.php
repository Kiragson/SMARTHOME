<?php
// Włącz obsługę sesji, jeśli jeszcze nie jest włączona
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Połącz się z bazą danych
require_once("../connected.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $deviceId = $_GET['device_id'];
    $device_state = isset($_GET['device_state']) ? $_GET['device_state'] : null;
    echo $device_state;
    error_log($deviceId);
}
echo $device_state;
if(isset($deviceId))
{
        // Pobierz bieżący stan urządzenia z bazy danych
    $sql = "SELECT state FROM device WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $currentDeviceState=0;
    $stmt->bind_result($currentDeviceState);
    $stmt->fetch();
    $stmt->close();

    
    if($device_state==Null){
        $newDeviceState = ($currentDeviceState == 0) ? 1 : 0;
    }
    else if($device_state==3){

        $newDeviceState = $device_state;
    }
    else if($device_state==1){
        $newDeviceState=$currentDeviceState;
    }

    // Przygotuj zapytanie SQL, aby zaktualizować stan urządzenia
    $updateSql = "UPDATE device SET state = ? WHERE id = ?";
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
    error_log("Skrypt PHP działa");
    $stmt->close();
}
else {
    $response['success'] = false;
    $response['message'] = "update_device_state.php:  Zmienna deviceId jest pusta";
    error_log("Skrypt PHP nie działa, Zmienna deviceId jest pusta");

}
    


// Zakończ połączenie z bazą danych
$conn->close();

// Ustaw nagłówki odpowiedzi JSON
header('Content-Type: application/json');

// Zwróć odpowiedź jako JSON
echo json_encode($response);

?>
