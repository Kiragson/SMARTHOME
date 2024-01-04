<?php
session_start();

require_once("../connected.php");
class Database
{
    private $conn;
    private $pdo;

    public function __construct($host, $dbname, $username, $password)
    {
        $this->conn = new mysqli($host, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Błąd połączenia z bazą danych: " . $this->conn->connect_error);
        }
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Błąd połączenia z bazą danych: " . $e->getMessage());
        }
    }

    public function getConn()
    {
        return $this->conn;
    }

    public function queryDeviceState($device_id)
    {
        $sql = "SELECT state FROM device WHERE id = :device_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':device_id', $device_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['state'];
            } else {
                return null; // Urządzenie o podanym ID nie istnieje
            }
        } catch (PDOException $e) {
            throw new Exception("Błąd bazy danych: " . $e->getMessage());
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
    private $conn;

    public function __construct(Database $database,$conn)
    {
        $this->database = $database;
        $this->conn = $conn;
    }

    public function handleDeviceUpdate($device_id)
    {
        //echo "__handleDeviceUpdate__";
        $response = array();
        $device_state = $this->database->queryDeviceState($device_id);

        if (isset($device_id)) {
            try {
                // Pobierz bieżący stan urządzenia z bazy danych
                
                $currentDeviceState = $this->database->queryDeviceState($device_id);

                //echo  $currentDeviceState;
                // Ustaw nowy stan urządzenia
                
                $newDeviceState = ($currentDeviceState == 0) ? 1 : 0;
               

                //echo $newDeviceState;

                // Wykonaj aktualizację stanu urządzenia
                if ($this->updateDeviceState($device_id, $newDeviceState)) {
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

    public function updateDeviceState($device_id, $newDeviceState)
    {
        //echo "__updateDeviceState__";
        $updateSql = "UPDATE device SET state = ? WHERE id = ?";
        $stmt = $this->conn->prepare($updateSql);
        $stmt->bind_param("ii", $newDeviceState, $device_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Zaktualizowano stan urządzenia pomyślnie";
        } else {
            $response['success'] = false;
            $response['message'] = "Błąd podczas aktualizacji stanu urządzenia";
        }
        return $response;
    }

    public function getDeviceIp($device_id)
    {
        //echo "__getDeviceIp__";
        $responseArray = array();

        if ($device_id === null) {
            // Błąd - brak lub nieprawidłowy parametr device_id
            $responseArray  = array('success' => false, 'message' => 'Brak lub nieprawidłowy parametr device_id');
        } else {
            $sql = "SELECT ip FROM device WHERE id = ?";
            $stmt = $this->database->getConn()->prepare($sql);

            try {
                $stmt->bind_param("i", $device_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $device_ip = $result->fetch_assoc()['ip'];
                    $responseArray = array('success' => true, 'device_id' => $device_id, 'ip' => $device_ip);
                } else {
                    $responseArray = array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
                }
            } catch (Exception $e) {
                // Obsługa błędu związana z bazą danych
                $responseArray = array('success' => false, 'message' => 'Blad bazy danych: ' . $e->getMessage());
            }

            $stmt->close();
        }
        return $responseArray;
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
            echo json_encode($response);
            exit();
        }

        // Connection to the database
        $conn = $this->database->getConn();

        // Start transaction
        $conn->begin_transaction();

        try {
            // SQL query to delete the device with a specified device_id
            $sql_delete_device = "DELETE FROM device WHERE id = ?";
            $stmt_delete_device = $conn->prepare($sql_delete_device);
            $stmt_delete_device->bind_param("i", $device_id);
            $stmt_delete_device->execute();
            $deletedDevice = $stmt_delete_device->affected_rows;

            // Commit the transaction
            $conn->commit();

            // Close the connection
            $stmt_delete_device->close();

            // Set JSON response
            $response['success'] = true;
            $response['message'] = "Operacja zakończona pomyślnie. Usunięto urządzenie.";

            // Send message
            $this->sendMessage($user_id, 'Operacja zakończona pomyślnie. Usunięto urządzenie o id ' . $device_id);

            // Send JSON response
            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            $conn->rollback();
            $response['success'] = false;
            $response['message'] = "Blad podczas usuwania urzadzenia: " . $e->getMessage();
            echo json_encode($response);
            exit();
        }
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
    $postData = $_POST;
    //echo '<pre>';
    //print_r($postData);
    //echo '</pre>';
    
    $rodzaj = $_POST["method"];
   //echo "POST";
}
else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $getData = $_GET;
    //echo '<pre>';
    //print_r($getData);
    //echo '</pre>';

    $rodzaj =$getData['method'];
    //echo "GET";
}
$database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
$deviceController = new DeviceController($database,$conn);

switch ($rodzaj) {
    case "getDeviceIp":
        try {
            
            $device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;
            $responseArray  = $deviceController->getDeviceIp($device_id);
        }catch (Exception $e) {
            $responseArray  = array('success' => false, 'message' => 'Błąd: ' . $e->getMessage());
        }
        // Ustaw nagłówki dla odpowiedzi JSON
        header('Content-Type: application/json');
        //echo "__odp__";
        echo json_encode($response);
        break;
    case "getDeviceState":
        
        //echo "getDeviceState";
        try {   
            $device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;
            $response = $deviceController->getDeviceState($device_id);
        }catch (Exception $e) {
            $response = array('success' => false, 'message' => 'Błąd: ' . $e->getMessage());
        }
        // Ustaw nagłówki dla odpowiedzi JSON
        header('Content-Type: application/json');
        
        echo json_encode($response);
        //echo "__odp__";
        break;
    case "delete":
        //echo "deleteDevice";
        $device_id = $_GET['device_id'];
        $deviceController->deleteDevice($device_id, $_SESSION['user_id']);
        break;
    case "changeDeviceState":
        //echo "changeDeviceState";
        $device_id = $_GET['device_id'];
        $response=$deviceController->handleDeviceUpdate($device_id);
        echo json_encode($response);
        break;
    case "addDevice":
        $name = $_POST['name_device'];
        $ipAddress = $_POST['ip_adres'];
        $roomId = $_POST['room'];
        $stan = $_POST['stan'];
        $response=$deviceController->addDevice($name, $ipAddress, $roomId, $stan);
        
        header('Content-Type: application/json');
        echo json_encode($response);
        header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
        break;
}
//echo "getDeviceState";
$database->close();
?>
