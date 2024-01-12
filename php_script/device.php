<?php
session_start();

class Database
{
    private $pdo;

    public function __construct($host, $dbname, $username, $password)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Błąd połączenia z bazą danych: " . $e->getMessage());
        }
    }

    public function queryDeviceState($device_id)
    {
        $sql = "SELECT state FROM device WHERE id = :device_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':device_id', $device_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['state'] : null;
        } catch (PDOException $e) {
            throw new Exception("Błąd bazy danych: " . $e->getMessage());
        }
    }
    public function getPDO()
    {
        return $this->pdo;
    }
    public function close()
    {
        // Nie trzeba zamykać połączenia, ponieważ PDO automatycznie zarządza połączeniem
        // Zamykanie może być konieczne w innych sytuacjach, ale w tym przypadku nie jest wymagane
    }
}

class DeviceController
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function handleDeviceUpdate($device_id)
    {
        $response = array();

        if (isset($device_id)) {
            try {
                $currentDeviceState = $this->database->queryDeviceState($device_id);
                $newDeviceState = ($currentDeviceState == 0) ? 1 : 0;

                if ($this->updateDeviceState($device_id, $newDeviceState)) {
                    $response['success'] = true;
                    $response['message'] = "update_device_state.php: Stan urządzenia został zaktualizowany.";
                    $response['newDeviceState'] = $newDeviceState;
                } else {
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

    public function updateDeviceState($device_id, $newDeviceState)
    {
        $response = array();
        $updateSql = "UPDATE device SET state = ? WHERE id = ?";
        $stmt = $this->database->getPDO()->prepare($updateSql);
        $stmt->bindParam(1, $newDeviceState, PDO::PARAM_INT);
        $stmt->bindParam(2, $device_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Zaktualizowano stan urządzenia pomyślnie";
        } else {
            $response['success'] = false;
            $response['message'] = "Błąd podczas aktualizacji stanu urządzenia";
        }

        $stmt->closeCursor();
        return $response;
    }

    public function deleteDevice($device_id, $user_id)
    {
        //echo "__deleteDevice__";
        $response = array();

        // Check if session is started and user_id is set
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            $response['success'] = false;
            $response['message'] = "Brak zainicjowanej sesji lub brak user_id.";
            return $response;
        }

        // Connection to the database
        $pdo = $this->database->getPDO();

        // Start transaction
        $pdo->beginTransaction();

        try {
            // SQL query to delete the device with a specified device_id
            $sql_delete_device = "DELETE FROM device WHERE id = :device_id";
            $stmt_delete_device = $pdo->prepare($sql_delete_device);
            $stmt_delete_device->bindParam(':device_id', $device_id, PDO::PARAM_INT);
            $stmt_delete_device->execute();
            $deletedDevice = $stmt_delete_device->rowCount();

            // Commit the transaction
            $pdo->commit();

            // Set JSON response
            $response['success'] = true;
            $response['message'] = "Operacja zakończona pomyślnie. Usunięto urządzenie.";

            // Send message
            $this->sendMessage($user_id, 'Operacja zakończona pomyślnie. Usunięto urządzenie o id ' . $device_id);

            // Send JSON response
            //header('Content-Type: application/json');
            //echo json_encode($response);

        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            $pdo->rollBack();
            $response['success'] = false;
            $response['message'] = "Blad podczas usuwania urzadzenia: " . $e->getMessage();
            //echo json_encode($response);
            
        }
        return $response;
        exit();
    }

    public function getDeviceIp($device_id)
    {
        $responseArray = array();

        if ($device_id === null) {
            // Błąd - brak lub nieprawidłowy parametr device_id
            $responseArray  = array('success' => false, 'message' => 'Brak lub nieprawidłowy parametr device_id');
        } else {
            $sql = "SELECT ip FROM device WHERE id = :device_id";
            $pdo = $this->database->getPDO();

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':device_id', $device_id, PDO::PARAM_INT);
                $stmt->execute();

                $device_ip = $stmt->fetchColumn();

                if ($device_ip !== false) {
                    $responseArray = array('success' => true, 'device_id' => $device_id, 'ip' => $device_ip);
                } else {
                    $responseArray = array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
                }
            } catch (Exception $e) {
                // Obsługa błędu związana z bazą danych
                $responseArray = array('success' => false, 'message' => 'Blad bazy danych: ' . $e->getMessage());
            }
        }

        return $responseArray;
    }



    public function getDeviceState($device_id)
    {
        //echo "__getDeviceState__";
        $responseArray = array();
        try {
            $device_state = $this->database->queryDeviceState($device_id);

            if ($device_state !== null) {
                $responseArray = array('success' => true, 'device_id' => $device_id, 'state' => $device_state);
            } else {
                $responseArray = array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
            }
        } catch (Exception $e) {
            $responseArray = array('success' => false, 'message' => $e->getMessage());
        }

        return $responseArray;
    }

    private function sendMessage($user_id, $message)
    {
        $response = array();

        $messageData = array(
            'userId' => $user_id,
            'message' => $message
        );

        $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);

        $curlResponse = curl_exec($ch);

        curl_close($ch);
    }

    public function addDevice($name, $ipAddress, $roomId, $stan)
    {
        $response = [];

        // Sprawdź, czy urządzenie o podanym IP już istnieje w bazie
        $sqlCheck = "SELECT id FROM device WHERE ip = :ipAddress";
        $stmtCheck = $this->database->getPDO()->prepare($sqlCheck);
        $stmtCheck->bindParam(':ipAddress', $ipAddress, PDO::PARAM_STR);
        $stmtCheck->execute();
        
        $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($resultCheck) {
            // Urządzenie o podanym IP już istnieje
            $response = [
                'success' => false,
                'message' => 'Urządzenie o podanym adresie IP już istnieje.'
            ];
        } else {
            // Urządzenie o podanym IP nie istnieje, więc dodajemy nowe
            $sql = "INSERT INTO device (name, ip, room_id, state) VALUES (?, ?, ?, ?)";
            $stmt = $this->database->getPDO()->prepare($sql);
            $stmt->bindParam(1, $name, PDO::PARAM_STR);
            $stmt->bindParam(2, $ipAddress, PDO::PARAM_STR);
            $stmt->bindParam(3, $roomId, PDO::PARAM_INT);
            $stmt->bindParam(4, $stan, PDO::PARAM_INT);
        
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
                    'message' => 'Błąd podczas dodawania urządzenia: ' . $stmt->errorInfo()[2]
                ];
            }
        
            $stmt->closeCursor();
        }
        
        // Ustaw nagłówki HTTP
        //header('Content-Type: application/json');
        
        // Zwróć odpowiedź w formie JSON
        return $response;
    }        
    public function updateDevice($newname, $newipAddress, $device_id)
    {
        $response = [];

        // Sprawdź, czy urządzenie o podanym IP już istnieje w bazie, i czy to to urządzenie
        $sqlCheck = "SELECT id FROM device WHERE ip = ?";
        $stmtCheck = $this->database->getPDO()->prepare($sqlCheck);

        if ($stmtCheck === false) {
            $response = [
                'success' => false,
                'message' => 'Błąd przygotowania zapytania: ' . $this->database->getPDO()->errorInfo()[2]
            ];
        } else {
            $stmtCheck->bindParam(1, $newipAddress, PDO::PARAM_STR);

            if (!$stmtCheck->execute()) {
                $response = [
                    'success' => false,
                    'message' => 'Błąd wykonania zapytania: ' . $stmtCheck->errorInfo()[2]
                ];
            } else {
                $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($resultCheck && $resultCheck['id'] == $device_id) {
                    //aktualizacja danych
                    // Urządzenie o podanym IP istnieje, więc aktualizuj jego dane
                    $sqlUpdate = "UPDATE device SET name = ?, ip = ? WHERE id = ?";
                    $stmtUpdate = $this->database->getPDO()->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(1, $newname, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(2, $newipAddress, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(3, $device_id, PDO::PARAM_INT);

                    if ($stmtUpdate->execute()) {
                        // Urządzenie zostało zaktualizowane pomyślnie
                        $response = [
                            'success' => true,
                            'message' => 'Urządzenie zostało zaktualizowane pomyślnie.'
                        ];
                    } else {
                        // Błąd podczas aktualizacji urządzenia
                        $response = [
                            'success' => false,
                            'message' => 'Błąd podczas aktualizacji urządzenia: ' . $stmtUpdate->errorInfo()[2]
                        ];
                    }

                    $stmtUpdate->closeCursor();
                } else {
                    // Urządzenie o podanym IP już istnieje
                    $response = [
                        'success' => false,
                        'message' => 'Urządzenie o podanym adresie IP już istnieje.'
                    ];
                }
            }
        }

        $stmtCheck->closeCursor();

        // Zwróć odpowiedź w formie JSON
        return $response;
    }

}




if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postData = $_POST;
    
    if (isset($_POST["method"])) {
        $rodzaj = $_POST["method"];
    } else {
        $rodzaj = null;
    }
}
else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $getData = $_GET;

    if (isset($_GET["method"])) {
        $rodzaj = $_GET["method"];
    } else {
        $rodzaj = null;
    }
}

$database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
$deviceController = new DeviceController($database);

if (isset($rodzaj)) {
    function getRequestParam($param, $source, $message) {
        return isset($source[$param]) && $source[$param] !== null ? $source[$param] : setResponse(false, $message);
    }
    
    switch ($rodzaj) {
        case "getDeviceIp":
            try {
                $device_id = getRequestParam('device_id', $_GET, 'Brak wartości zmiennej device_id');
                $responseArray = $deviceController->getDeviceIp($device_id);
            } catch (Exception $e) {
                $responseArray = setResponse(false, 'Błąd: ' . $e->getMessage());
            }
            break;
    
        case "getDeviceState":
            try {
                $device_id = getRequestParam('device_id', $_GET, 'Brak wartości zmiennej device_id');
                $response = $deviceController->getDeviceState($device_id);
            } catch (Exception $e) {
                $response = setResponse(false, 'Błąd: ' . $e->getMessage());
            }
            break;
    
        case "delete":
            $device_id = getRequestParam('device_id', $_POST, 'Brak wartości zmiennej device_id');
            $response = $deviceController->deleteDevice($device_id, $_SESSION['user_id']);
            break;
    
        case "changeDeviceState":
            $device_id = getRequestParam('device_id', $_GET, 'Brak wartości zmiennej device_id');
            $response = $deviceController->handleDeviceUpdate($device_id);
            break;
    
        case "addDevice":
            $name = getRequestParam('name_device', $_POST, 'Brak wartości zmiennej name_device');
            $ipAddress = getRequestParam('ip_adres', $_POST, 'Brak wartości zmiennej ip_adres');
            $roomId = getRequestParam('id_room', $_POST, 'Brak wartości zmiennej id_room');
            $stan = getRequestParam('stan', $_POST, 'Brak wartości zmiennej stan');
            $response = $deviceController->addDevice($name, $ipAddress, $roomId, $stan);
            break;
    
        case "update":
            $newname = getRequestParam('editDeviceName', $_POST, 'Brak wartości zmiennej editDeviceName');
            $newipAddress = getRequestParam('editDeviceIp', $_POST, 'Brak wartości zmiennej editDeviceIp');
            $device_id = getRequestParam('device_Id', $_POST, 'Brak wartości zmiennej device_Id');
            $response = $deviceController->updateDevice($newname, $newipAddress, $device_id);
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
    function setResponse($success, $message) {
        return ['success' => $success, 'message' => $message];
    }
}
else {
    //echo "brak rodzaju";
}

$database->close();


?>
