<?php

// Włącz obsługę sesji, jeśli jeszcze nie jest włączona
header('Content-Type: text/html; charset=UTF-8');

session_start();

class Database
{
    private $conn;

    public function __construct($host, $dbname, $username, $password)
    {
        $this->conn = new mysqli($host, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Błąd połączenia z bazą danych: " . $this->conn->connect_error);
        }
    }

    public function getDeviceState($deviceId)
    {
        $sql = "SELECT state FROM device WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $deviceId);
        $stmt->execute();
        $currentDeviceState = 0;
        $stmt->bind_result($currentDeviceState);
        $stmt->fetch();
        $stmt->close();

        return $currentDeviceState;
    }

    public function updateDeviceState($deviceId, $newDeviceState)
    {
        $updateSql = "UPDATE device SET state = ? WHERE id = ?";
        $stmt = $this->conn->prepare($updateSql);
        $stmt->bind_param("ii", $newDeviceState, $deviceId);

        if ($stmt->execute()) {
            return true; // Zaktualizowano stan urządzenia pomyślnie
        } else {
            return false; // Błąd podczas aktualizacji stanu urządzenia
        }
    }

    public function close()
    {
        $this->conn->close();
    }
}

class DeviceController
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function handleDeviceUpdate($deviceId, $deviceState)
    {
        $response = array();

        if (isset($deviceId)) {
            try {
                // Pobierz bieżący stan urządzenia z bazy danych
                $currentDeviceState = $this->database->getDeviceState($deviceId);

                // Ustaw nowy stan urządzenia
                if ($deviceState === null) {
                    $newDeviceState = ($currentDeviceState == 0) ? 1 : 0;
                } elseif ($deviceState == 3) {
                    $newDeviceState = $deviceState;
                } elseif ($deviceState == 1) {
                    $newDeviceState = $currentDeviceState;
                }

                // Wykonaj aktualizację stanu urządzenia
                if ($this->database->updateDeviceState($deviceId, $newDeviceState)) {
                    // Zaktualizowano stan urządzenia pomyślnie
                    $response['success'] = true;
                    $response['message'] = "update_device_state.php: Stan urządzenia został zaktualizowany.";
                    $response['newDeviceState'] = $newDeviceState; // Nowy stan urządzenia
                } else {
                    // Błąd podczas aktualizacji stanu urządzenia
                    $response['success'] = false;
                    $response['message'] = "update_device_state.php: Błąd podczas aktualizacji stanu urządzenia.";
                }
            } catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = "update_device_state.php: Błąd: " . $e->getMessage();
            }
        } else {
            $response['success'] = false;
            $response['message'] = "update_device_state.php: Zmienna deviceId jest pusta.";
        }

        return $response;
    }
}

// Sprawdź, czy żądanie to GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;
    $deviceState = isset($_GET['device_state']) ? intval($_GET['device_state']) : null;

    $database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
    $deviceController = new DeviceController($database);

    $response = $deviceController->handleDeviceUpdate($deviceId, $deviceState);

    // Ustaw nagłówki odpowiedzi JSON
    header('Content-Type: application/json');

    // Zwróć odpowiedź jako JSON
    echo json_encode($response);

    // Zamknij połączenie z bazą danych
    $database->close();
}
?>
