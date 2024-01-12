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

class RoomController
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function deleteRoomAndDevices($room_id)
    {
        $response = array();

        // Sprawdź, czy sesja jest już rozpoczęta przed używaniem $_SESSION
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            return setResponse(false, "Brak zainicjowanej sesji lub brak user_id.");
        }

        // Pobierz połączenie z bazą danych
        $pdo = $this->database->getPDO();
        $pdo->beginTransaction();

        try {
            // Sprawdź, czy pokój istnieje
            $sqlCheck = "SELECT id, house_id FROM room WHERE id = ?";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(1, $room_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($resultCheck) {
                $house_id = $resultCheck['house_id'];

                // Przykładowa logika sprawdzająca, czy użytkownik jest właścicielem pokoju
                if ($user_id == $this->getHouseOwner($house_id)) {
                    // Pokój należy do użytkownika, można usuwać
                    $deletedDevices = $this->deleteDevicesForRoom($pdo, $room_id);
                    $deletedRoom = $this->deleteRoom($pdo, $room_id);

                    // Zakończ transakcję
                    $pdo->commit();

                    // Ustalamy odpowiedź JSON
                    return setResponse(true, "Operacja zakończona pomyślnie. Usunięto $deletedDevices urządzeń i $deletedRoom pokój.");
                } else {
                    // Użytkownik nie jest właścicielem pokoju
                    return setResponse(false, "Nie jesteś właścicielem pokoju o ID: $room_id");
                }
            } else {
                // Brak pokoju o podanym ID
                return setResponse(false, "Pokój o ID: $room_id nie istnieje.");
            }
        } catch (Exception $e) {
            // Obsługa błędu związana z bazą danych
            $pdo->rollBack();
            return setResponse(false, "Błąd podczas usuwania rekordów: " . $e->getMessage());
        }
    }

    private function deleteDevicesForRoom($pdo, $room_id)
    {
        $sqlDeleteDevices = "DELETE FROM device WHERE room_id = ?";
        $stmtDeleteDevices = $pdo->prepare($sqlDeleteDevices);
        $stmtDeleteDevices->bindParam(1, $room_id, PDO::PARAM_INT);
        $stmtDeleteDevices->execute();

        return $stmtDeleteDevices->rowCount();
    }

    private function deleteRoom($pdo, $room_id)
    {
        $sqlDeleteRoom = "DELETE FROM room WHERE id = ?";
        $stmtDeleteRoom = $pdo->prepare($sqlDeleteRoom);
        $stmtDeleteRoom->bindParam(1, $room_id, PDO::PARAM_INT);
        $stmtDeleteRoom->execute();

        return $stmtDeleteRoom->rowCount();
    }

    public function updateRoom($newname, $newHouse_id, $room_id)
    {
        $response = array();

        // Sprawdź, czy sesja jest już rozpoczęta przed używaniem $_SESSION
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            return setResponse(false, "Brak zainicjowanej sesji lub brak user_id.");
        }

        // Pobierz połączenie z bazą danych
        $pdo = $this->database->getPDO();

        try {
            // Sprawdź, czy pokój istnieje
            $sqlCheck = "SELECT id, house_id FROM room WHERE id = ?";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(1, $room_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($resultCheck) {
                $house_id = $resultCheck['house_id'];

                // Przykładowa logika sprawdzająca, czy użytkownik jest właścicielem pokoju
                if ($user_id == $this->getHouseOwner($house_id)) {
                    // Pokój należy do użytkownika, można aktualizować dane
                    $updatedRoom = $this->updateRoomData($pdo, $newname, $newHouse_id, $room_id);

                    // Ustalamy odpowiedź JSON
                    return setResponse(true, "Pokój został zaktualizowany pomyślnie. Zaktualizowano $updatedRoom rekordów.");
                } else {
                    // Użytkownik nie jest właścicielem pokoju
                    return setResponse(false, "Nie jesteś właścicielem pokoju o ID: $room_id");
                }
            } else {
                // Brak pokoju o podanym ID
                return setResponse(false, "Pokój o ID: $room_id nie istnieje.");
            }
        } catch (Exception $e) {
            // Obsługa błędu związana z bazą danych
            return setResponse(false, "Błąd bazy danych: " . $e->getMessage());
        }
    }

    private function updateRoomData($pdo, $newname, $newHouse_id, $room_id)
    {
        $sqlUpdate = "UPDATE room SET name = ?, house_id = ? WHERE id = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(1, $newname, PDO::PARAM_STR);
        $stmtUpdate->bindParam(2, $newHouse_id, PDO::PARAM_INT);
        $stmtUpdate->bindParam(3, $room_id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        return $stmtUpdate->rowCount();
    }

    public function addRoom($roomName, $idHouse)
    {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } else {
            return setResponse(false, "Brak zainicjowanej sesji lub brak user_id.");
        }

        // Przygotuj zapytanie SQL do dodania nowego pokoju
        $sql = "INSERT INTO room (name, house_id) VALUES (?, ?)";

        // Pobierz połączenie z bazą danych
        $pdo = $this->database->getPDO();

        // Przygotuj zapytanie SQL
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $roomName, PDO::PARAM_STR);
        $stmt->bindParam(2, $idHouse, PDO::PARAM_INT);

        // Wykonaj zapytanie SQL
        if ($stmt->execute()) {
            // Pokój został dodany pomyślnie
            return setResponse(true, "Pokój został dodany pomyślnie.");
        } else {
            // Błąd podczas dodawania pokoju
            return setResponse(false, "Błąd podczas dodawania pokoju: " . $stmt->errorInfo()[2]);
        }
    }

    private function getHouseOwner($house_id)
    {
        $pdo = $this->database->getPDO();

        // Przykładowe zapytanie SQL, dostosuj je do swojej struktury bazy danych
        $sql = "SELECT user1 FROM family WHERE id = (SELECT family_id FROM house WHERE id = ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $house_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['user1'];
        } else {
            // Zwróć coś odpowiedniego w przypadku braku wyników
            return null;
        }
    }
}

// Funkcja pomocnicza do ustawiania odpowiedzi JSON
function setResponse($success, $message)
{
    return ['success' => $success, 'message' => $message];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postData = $_POST;

    if (isset($_POST["method"])) {
        $rodzaj = $_POST["method"];
    } else {
        $rodzaj = null;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $getData = $_GET;

    if (isset($_GET["method"])) {
        $rodzaj = $_GET["method"];
    } else {
        $rodzaj = null;
    }
}

$database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
$roomController = new RoomController($database);

if (isset($rodzaj)) {
    function getRequestParam($param, $source, $message) {
        return isset($source[$param]) && $source[$param] !== null ? $source[$param] : setResponse(false, $message);
    }
    switch ($rodzaj) {
        case 'delete':
            $room_id = getRequestParam('room_id', $_POST, 'Brak wartości zmiennej room_id');
            $response = $roomController->deleteRoomAndDevices($room_id);
            break;
        case 'update':
            $newname = getRequestParam('newName', $_POST, 'Brak wartości zmiennej newName');
            $newHouse_id = getRequestParam('newHouseId', $_POST, 'Brak wartości zmiennej newHouseId');
            $room_id = getRequestParam('room_id', $_POST, 'Brak wartości zmiennej roomId');
            $response = $roomController->updateRoom($newname, $newHouse_id, $room_id);
            break;
        case 'newRoom':
            $roomName = getRequestParam('roomName', $_POST, 'Brak wartości zmiennej roomName');
            $idHouse = getRequestParam('houseId', $_POST, 'Brak wartości zmiennej houseId');
            $response = $roomController->addRoom($roomName, $idHouse);
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    //echo "brak rodzaju";
}

$database->close();
?>
