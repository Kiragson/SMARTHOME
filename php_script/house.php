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

class SmartHomeManager
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function createHome($postData)
    {
        $response = $this->setResponse(false, "Invalid request.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['username'])) {
                $response = $this->handleCreateHome($postData);
            } else {
                $response['message'] = "User not logged in.";
            }
        }

        header('Content-Type: application/json');
        return $response;
    }

    private function handleCreateHome($postData)
    {
        $response = $this->setResponse(false, "Error creating family.");

        $nazwa_domu = $postData['nazwa_domu'];
        $miasto = $postData['city'];
        $kod = $postData['postalCode'];
        $user_id = $_SESSION['user_id'];

        $insertFamilySql = "INSERT INTO family (id_admin, user1) VALUES (?, ?)";
        $stmt = $this->database->getPDO()->prepare($insertFamilySql);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = $this->setResponse(true, "Family created for user with id: $user_id");

            $lastInsertedFamilyId = $this->database->getPDO()->lastInsertId();

            $insertDomSql = "INSERT INTO house (name, family_id, city, postcode) VALUES (?, ?, ?, ?)";
            $stmt = $this->database->getPDO()->prepare($insertDomSql);
            $stmt->bindParam(1, $nazwa_domu, PDO::PARAM_STR);
            $stmt->bindParam(2, $lastInsertedFamilyId, PDO::PARAM_INT);
            $stmt->bindParam(3, $miasto, PDO::PARAM_STR);
            $stmt->bindParam(4, $kod, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $response = $this->setResponse(true, "House created for family with house id: {$lastInsertedFamilyId}");

                $this->sendMessage($user_id, 'House created: ' . $nazwa_domu);
            } else {
                $response['message'] = "Error creating house: " . $this->database->getPDO()->errorInfo()[2];
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Error creating family: " . $this->database->getPDO()->errorInfo()[2];
        }

        return $response;
    }

    private function setResponse($success, $message)
    {
        return ['success' => $success, 'message' => $message];
    }

    public function deleteHome($id_domu)
    {
        $user_id=$_SESSION['user_id'];
        $response = array();

        // Pobierz dane domu
        $sql = "SELECT h.name, h.family_id, f.user1
                FROM House h 
                LEFT JOIN Family f ON f.id = h.family_id
                WHERE h.id = ?";
        $stmt = $this->database->getPDO()->prepare($sql);
        $stmt->bindParam(1, $id_domu, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $response['success'] = false;
            $response['message'] = "Nie znaleziono domu o podanym identyfikatorze.";
        }

        $user1 = $result['user1'];
        $nazwa_domu = $result['name'];

        // Sprawdź, czy użytkownik jest właścicielem domu
        if ($user_id != $user1) {
            $response['success'] = false;
            $response['message'] = "Użytkownik nie jest właścicielem domu.";
        }

        // Rozpocznij transakcję
        $this->database->getPDO()->beginTransaction();

        try {
            // Usuń rekordy w odwrotnej kolejności: urządzenia, pokoje, rodzina, a następnie dom
            $sql_delete_urzadzenia = "DELETE FROM device WHERE room_id IN (SELECT id FROM room WHERE house_id = ?)";
            $stmt_delete_urzadzenia = $this->database->getPDO()->prepare($sql_delete_urzadzenia);
            $stmt_delete_urzadzenia->bindParam(1, $id_domu, PDO::PARAM_INT);
            $stmt_delete_urzadzenia->execute();
            $deletedUrzadzenia = $stmt_delete_urzadzenia->rowCount();

            $sql_delete_room = "DELETE FROM room WHERE house_id = ?";
            $stmt_delete_room = $this->database->getPDO()->prepare($sql_delete_room);
            $stmt_delete_room->bindParam(1, $id_domu, PDO::PARAM_INT);
            $stmt_delete_room->execute();
            $deletedRoom = $stmt_delete_room->rowCount();

            // Usuń powiązaną rodzinę
            $sql_select_family = "SELECT family_id FROM house WHERE id = ?";
            $stmt_select_family = $this->database->getPDO()->prepare($sql_select_family);
            $stmt_select_family->bindParam(1, $id_domu, PDO::PARAM_INT);
            $stmt_select_family->execute();
            $result_select_family = $stmt_select_family->fetch(PDO::FETCH_ASSOC);

            if ($result_select_family) {
                $family_id = $result_select_family['family_id'];

                // Usuń rodzinę
                $sql_delete_family = "DELETE FROM family WHERE id = ?";
                $stmt_delete_family = $this->database->getPDO()->prepare($sql_delete_family);
                $stmt_delete_family->bindParam(1, $family_id, PDO::PARAM_INT);
                $stmt_delete_family->execute();
                $deletedFamily = $stmt_delete_family->rowCount();
            } else {
                $deletedFamily = 0;
            }

            // Na koniec usuń sam dom
            $sql_delete_house = "DELETE FROM house WHERE id = ?";
            $stmt_delete_house = $this->database->getPDO()->prepare($sql_delete_house);
            $stmt_delete_house->bindParam(1, $id_domu, PDO::PARAM_INT);
            $stmt_delete_house->execute();
            $deletedHouse = $stmt_delete_house->rowCount();

            // Aktualizuj liczbę domów użytkownika
            $sql_update_user_houses = "UPDATE user SET number_of_houses = number_of_houses - 1 WHERE id = ?";
            $stmt_update_user_houses = $this->database->getPDO()->prepare($sql_update_user_houses);
            $stmt_update_user_houses->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt_update_user_houses->execute();

            // Zakończ transakcję
            $this->database->getPDO()->commit();

            // Ustalamy odpowiedź JSON
            $response['success'] = true;
            $response['message'] = "Operacja zakończona pomyślnie. Usunięto dom, pokoje, urządzenia i rodzinę.";

            // Wysyłanie wiadomości
            $this->sendMessage($user_id, 'Usunięto dom i jego zawartość: ' . $nazwa_domu);

        } catch (Exception $e) {
            // W razie błędu cofnij transakcję
            $this->database->getPDO()->rollBack();
            $response['success'] = false;
            $response['message'] = "Błąd podczas usuwania rekordów: " . $e->getMessage();
        }

        return $response;
    }

    public function updateFamilyMembers($postData) {
        $response = array('success' => false, 'message' => '');
    
        if (isset($_SESSION['username'])) {
            try {
                // Sprawdzenie czy użytkownicy istnieją przed aktualizacją
                foreach (['user2', 'user3', 'user4', 'user5', 'user6'] as $userKey) {
                    if (!empty($postData[$userKey])) {
                        $this->checkUserExistence($postData[$userKey]);
                    }
                }
    
                // Aktualizacja składu rodziny
                $sql = "UPDATE Family 
                        SET user2 = :user2, user3 = :user3, user4 = :user4, user5 = :user5, user6 = :user6 
                        WHERE id = :familyId";
    
                $stmt = $this->database->getPDO()->prepare($sql);
                $this->bindNullableParam($stmt, ':user2', $postData['user2'], PDO::PARAM_INT);
                $this->bindNullableParam($stmt, ':user3', $postData['user3'], PDO::PARAM_INT);
                $this->bindNullableParam($stmt, ':user4', $postData['user4'], PDO::PARAM_INT);
                $this->bindNullableParam($stmt, ':user5', $postData['user5'], PDO::PARAM_INT);
                $this->bindNullableParam($stmt, ':user6', $postData['user6'], PDO::PARAM_INT);
                $stmt->bindParam(':familyId', $postData['family_id'], PDO::PARAM_INT);
    
                if ($stmt->execute()) {
                    // Pomyślnie zaktualizowano dane
                    $this->sendMessage($_SESSION['user_id'], 'Zaktualizowano skład rodziny.');
                    $response = array('success' => true, 'message' => 'Famili została zaktualizowana.');
                } else {
                    $response = array('success' => false, 'message' => 'Błąd podczas aktualizacji danych: ' . $stmt->errorInfo()[2]);
                }
            } catch (PDOException $e) {
                $response = array('success' => false, 'message' => 'Błąd podczas wykonywania zapytania SQL: ' . $e->getMessage());
            } catch (Exception $e) {
                $response = array('success' => false, 'message' => 'Błąd: ' . $e->getMessage());
            }
        } else {
            $response = array('success' => false, 'message' => 'Użytkownik nie jest zalogowany.');
        }
    
        return $response;
    }
    
    private function checkUserExistence($userIdOrLogin) {
        $response = array();
    
        try {
            // Przygotuj zapytanie SQL przy użyciu PDO
            $sql = "SELECT id, login FROM user WHERE id = :userIdOrLogin OR login = :userIdOrLogin";
    
            // Przygotuj zapytanie SQL
            $stmt = $this->database->getPDO()->prepare($sql);
    
            // Przypisz wartości do zmiennych w zapytaniu
            $stmt->bindParam(':userIdOrLogin', $userIdOrLogin, PDO::PARAM_INT);
    
            // Tutaj wykonaj zapytanie i przetwórz wyniki...
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                $response = $this->setResponse(true, "Użytkownik o ID lub loginie '$userIdOrLogin' istnieje.");
            } else {
                $response = $this->setResponse(false, "Użytkownik o ID lub loginie '$userIdOrLogin' nie istnieje.");
                throw new Exception(); // Przerwij proces w przypadku błędu
            }
        } catch (PDOException $e) {
            $response = $this->setResponse(false, "Błąd przy wykonaniu zapytania SQL: " . $e->getMessage());
        }
    
        return $response;
    }

    private function bindNullableParam($stmt, $param, $value, $type) {
        if ($value !== null) {
            $stmt->bindParam($param, $value, $type);
        } else {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        }
    }

    public function updateHouse($postData)
    {
        $response = array();

        $newName = isset($postData['name']) ? $postData['name'] : null;
        $city = isset($postData['city']) ? $postData['city'] : null;
        $postalCode = isset($postData['postalCode']) ? $postData['postalCode'] : null;

        // Uaktualnianie informacji o domu
        $updateDomSql = "UPDATE house SET name = ?, city = ?, postcode = ? WHERE id = ?";

        $stmt = $this->database->getPDO()->prepare($updateDomSql);
        $stmt->bindParam(1, $newName, PDO::PARAM_STR);
        $stmt->bindParam(2, $city, PDO::PARAM_STR);
        $stmt->bindParam(3, $postalCode, PDO::PARAM_STR);
        $stmt->bindParam(4, $postData['house_id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = $this->setResponse(true, "Zaktualizowano informacje o domu: " . $newName);
        } else {
            $response = $this->setResponse(false, "Błąd podczas aktualizacji informacji o domu: " . $stmt->errorInfo()[2]);
        }

        return $response;
    }

    

    private function sendMessage($user_id, $message)
    {
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

        // Sprawdź, czy zapytanie cURL zakończyło się sukcesem
        if ($curlResponse === false) {
            
            $response = $this->setResponse(false, 'Błąd podczas wysyłania wiadomości: ' . curl_error($ch));
        }

        curl_close($ch);
    }
}

$database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
$smartHomeManager = new SmartHomeManager($database);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rodzaj = isset($_POST["method"]) ? $_POST["method"] : null;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $rodzaj = isset($_GET["method"]) ? $_GET["method"] : null;
}

if (isset($rodzaj)) {
    $response=array();
    function getRequestParam($param, $source, $message)
    {
        return isset($source[$param]) && $source[$param] !== null ? $source[$param] : setResponse(false, $message);
    }
    switch ($rodzaj) {
        case "create":
            $postData = $_POST;
            $response=$smartHomeManager->createHome($postData);
            break;
        case "delete":
            $house_id = getRequestParam('house_id',$_POST, 'Brak zmiennej id_domu');
            $response=$smartHomeManager->deleteHome($house_id);
            break;
        case "new_roommate":
            $postData = $_POST;
            $response=$smartHomeManager->updateFamilyMembers($postData);
            break;
        case "newHouse":
            $postData = $_POST;
            $response=$smartHomeManager->createHome($postData);
            break;
        case "edit_House":
            $postData = $_POST;
            $response=$smartHomeManager->createHome($postData);
            break;
            
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    
}
else{

}

$database->close();
?>

