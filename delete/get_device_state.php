<?php

require_once("../connected.php");

class Database
{
    private $pdo;

    public function __construct($host, $dbname, $username, $password)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Błąd bazy danych: " . $e->getMessage());
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
        $this->pdo = null;
    }
}

class DeviceController
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getDeviceState($device_id)
    {
        try {
            $device_state = $this->database->queryDeviceState($device_id);

            if ($device_state !== null) {
                return array('success' => true, 'device_id' => $device_id, 'state' => $device_state);
            } else {
                return array('success' => false, 'message' => 'Urządzenie o podanym ID nie istnieje');
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

    if ($device_id === null) {
        $response = array('success' => false, 'message' => 'Brak lub nieprawidłowy parametr device_id');
    } else {
        try {
            $database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
            $deviceController = new DeviceController($database);

            $response = $deviceController->getDeviceState($device_id);
        } catch (Exception $e) {
            $response = array('success' => false, 'message' => 'Błąd: ' . $e->getMessage());
        }
    }

    // Ustaw nagłówki dla odpowiedzi JSON
    header('Content-Type: application/json');

    // Wyślij odpowiedź JSON
    echo json_encode($response);

    // Zamknij połączenie z bazą danych
    $database->close();
}
?>
