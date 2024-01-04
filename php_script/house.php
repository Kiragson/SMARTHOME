<?php

class SmartHomeManager
{
    private $conn;

    public function __construct($conn)
    {
        session_start();
        $this->conn = $conn;
    }

    public function createHome($postData)
    {
        $response = array();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['username'])) {
                $this->handleCreateHome($postData);
            } else {
                $response['success'] = false;
                $response['message'] = "User not logged in.";
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Invalid request.";
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }
    private function handleCreateHome($postData)
    {
        $nazwa_domu = $postData['nazwa_domu'];
        $miasto = $postData['city'];
        $kod = $postData['postalCode'];
        $user_id = $_SESSION['user_id'];

        $insertFamilySql = "INSERT INTO family (id_admin, user1) VALUES (?,?)";
        $stmt = $this->conn->prepare($insertFamilySql);
        $stmt->bind_param("ii", $user_id, $user_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Family created for user with id: $user_id";

            $lastInsertedFamilyId = $this->conn->insert_id;

            $insertDomSql = "INSERT INTO house (name, family_id, city, postcode) VALUES (?, ?,?,?)";
            $stmt = $this->conn->prepare($insertDomSql);
            $stmt->bind_param("sisi", $nazwa_domu, $lastInsertedFamilyId, $miasto, $kod);

            if ($stmt->execute()) {
                $response['family_success'] = true;
                $response['family_message'] = "House created for family with house id: {$this->conn->insert_id}";

                $alteruser_housenumber = "SELECT number_of_houses FROM user WHERE id = $user_id";
                $result = $this->conn->query($alteruser_housenumber);

                if ($result) {
                    $row = $result->fetch_assoc();
                    $currentHouseNumber = $row['number_of_houses'];

                    $newHouseNumber = $currentHouseNumber + 1;
                    $alteruser = "UPDATE user SET number_of_houses = $newHouseNumber WHERE id = $user_id";

                    $updateResult = $this->conn->query($alteruser);
                }

                $message = array(
                    'userId' => $user_id,
                    'message' => 'House created: ' . $nazwa_domu
                );

                $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

                $response = curl_exec($ch);

                curl_close($ch);

                header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
                exit;
            } else {
                $response['family_success'] = false;
                $response['family_message'] = "Error creating house: " . $this->conn->error;
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Error creating family: " . $this->conn->error;
        }

        $stmt->close();
    }

    public function deleteHome($id_domu)
    {
        $response = array();

        if (isset($_GET['id_domu'])) {
            $user_id = $_SESSION['user_id'];
            $this->handleDeleteHome($id_domu, $user_id);
        } else {
            $response['success'] = false;
            $response['message'] = "Invalid request.";
            header("Location: ../strony/house.php");
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }
    private function handleDeleteHome($id_domu, $user_id)
    {
        $response = array();

        // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
        //require_once("../connected.php");

        // Pobierz dane domu
        $sql = "SELECT h.name, h.family_id, f.user1
                FROM House h 
                LEFT JOIN Family f ON f.id = h.family_id
                WHERE h.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_domu);

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            $response['success'] = false;
            $response['message'] = "Nie znaleziono domu o podanym identyfikatorze.";
            header('Content-Type: application/json');
            echo json_encode($response);
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        }

        $user1 = $row['user1'];
        $nazwa_domu = $row['name'];

        // Sprawdź, czy użytkownik jest właścicielem domu
        if ($user_id != $user1) {
            $response['success'] = false;
            $response['message'] = "Użytkownik nie jest właścicielem domu.";
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Rozpocznij transakcję
        $this->conn->begin_transaction();

        try {
            // Usuń rekordy w odwrotnej kolejności: urządzenia, pokoje, rodzina, a następnie dom
            $sql_delete_urzadzenia = "DELETE FROM device WHERE room_id IN (SELECT id FROM room WHERE house_id = ?)";
            $stmt_delete_urzadzenia = $this->conn->prepare($sql_delete_urzadzenia);
            $stmt_delete_urzadzenia->bind_param("i", $id_domu);
            $stmt_delete_urzadzenia->execute();
            $deletedUrzadzenia = $stmt_delete_urzadzenia->affected_rows;

            $sql_delete_room = "DELETE FROM room WHERE house_id = ?";
            $stmt_delete_room = $this->conn->prepare($sql_delete_room);
            $stmt_delete_room->bind_param("i", $id_domu);
            $stmt_delete_room->execute();
            $deletedRoom = $stmt_delete_room->affected_rows;

            // Usuń powiązaną rodzinę
            $sql_select_family = "SELECT family_id FROM house WHERE id = ?";
            $stmt_select_family = $this->conn->prepare($sql_select_family);
            $stmt_select_family->bind_param("i", $id_domu);
            $stmt_select_family->execute();
            $result_select_family = $stmt_select_family->get_result();

            if ($row_family = $result_select_family->fetch_assoc()) {
                $family_id = $row_family['family_id'];

                // Usuń rodzinę
                $sql_delete_family = "DELETE FROM family WHERE id = ?";
                $stmt_delete_family = $this->conn->prepare($sql_delete_family);
                $stmt_delete_family->bind_param("i", $family_id);
                $stmt_delete_family->execute();
                $deletedFamily = $stmt_delete_family->affected_rows;
            } else {
                $deletedFamily = 0;
            }

            // Na koniec usuń sam dom
            $sql_delete_house = "DELETE FROM house WHERE id = ?";
            $stmt_delete_house = $this->conn->prepare($sql_delete_house);
            $stmt_delete_house->bind_param("i", $id_domu);
            $stmt_delete_house->execute();
            $deletedHouse = $stmt_delete_house->affected_rows;

            // Aktualizuj liczbę domów użytkownika
            $sql_update_user_houses = "UPDATE user SET number_of_houses = number_of_houses - 1 WHERE id = ?";
            $stmt_update_user_houses = $this->conn->prepare($sql_update_user_houses);
            $stmt_update_user_houses->bind_param("i", $user_id);
            $stmt_update_user_houses->execute();

            // Zakończ transakcję
            $this->conn->commit();

            // Ustalamy odpowiedź JSON
            $response['success'] = true;
            $response['message'] = "Operacja zakończona pomyślnie. Usunięto dom, pokoje, urządzenia i rodzinę.";

            // Wysłanie wiadomości
            $message = array(
                'userId' => $user_id,
                'message' => 'Usunięto dom i jego zawartość: ' . $nazwa_domu
            );
            $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

            $response_curl = curl_exec($ch);

            // Sprawdź, czy zapytanie cURL zakończyło się sukcesem
            if ($response_curl === false) {
                $response['success'] = false;
                $response['message'] = 'Błąd podczas wysyłania wiadomości: ' . curl_error($ch);
            }

            curl_close($ch);
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        } catch (Exception $e) {
            // W razie błędu cofnij transakcję
            $this->conn->rollback();
            $response['success'] = false;
            $response['message'] = "Błąd podczas usuwania rekordów: " . $e->getMessage();
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        }

        // Zamknij połączenie
        $this->conn->close();

        // Wyślij odpowiedź JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    public function updateFamilyMembers($postData)
    {
        $response = array();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_SESSION['username'])) {
                $this->handleUpdateFamily($postData);
            } else {
                $response['success'] = false;
                $response['message'] = "Użytkownik nie jest zalogowany.";
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Nieprawidłowe żądanie.";
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    private function handleUpdateFamily($postData)
    {
        
        $familyId = $postData['family_id'];
        $user2 = $postData['user2'];
        $user3 = $postData['user3'];
        $user4 = $postData['user4'];
        $user5 = $postData['user5'];
        $user6 = $postData['user6'];

        $sql = "UPDATE Family 
        SET user2 = ?, user3 = ?, user4 = ?, user5 = ?, user6 = ? 
        WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiiii", $user2, $user3, $user4, $user5, $user6, $familyId);

        if ($stmt->execute()) {
            //header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        } else {
            $response['success'] = false;
            $response['message'] = "Błąd podczas aktualizacji danych: " . $stmt->error;
        }
        if(isset($familyId)){
            
            echo "Family ID: " . $familyId . "<br>";
            echo "User2: " . $user2 . "<br>";
            echo "User3: " . $user3 . "<br>";
            echo "User4: " . $user4 . "<br>";
            echo "User5: " . $user5 . "<br>";
            echo "User6: " . $user6 . "<br>";
            echo "Statement Execute: " . ($stmt->execute() ? 'true' : 'false') . "<br>";
        }else {echo "Brak familyId";}
        

        $stmt->close();
        $this->conn->close();
    }

}

$smartHomeManager = new SmartHomeManager($conn);
if ($_SERVER["REQUEST_METHOD"] == "POST"){

    $rodzaj=$_POST["method"];
    
}
if ($_SERVER["REQUEST_METHOD"] == "GET"){

    $rodzaj=isset($_GET["method"]);
    
}

switch($rodzaj){
    case "create":
        $postData = $_POST;
        $smartHomeManager->createHome($postData);
        break;
    case "delete":
        $id_domu = isset($_GET['id_domu']) ? $_GET['id_domu'] : null;
        $smartHomeManager->deleteHome($id_domu);
        break;
    case "new_roommate":
        $postData = $_POST;
        $smartHomeManager->updateFamilyMembers($postData);
        break;
}


