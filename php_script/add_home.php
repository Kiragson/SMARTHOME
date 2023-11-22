<?php
session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upewnij się, że użytkownik jest zalogowany
    if (isset($_SESSION['username'])) {
        require_once("../connected.php"); // Zaimportuj połączenie do bazy danych

        $nazwa_domu = $_POST['nazwa_domu'];
        $miasto=$_POST['city'];
        $kod=$_POST['postalCode'];
        $login = $_SESSION['username'];
        $user_id = $_SESSION['user_id']; // Pobierz ID zalogowanego użytkownika
        
    
        

        $insertFamilySql = "INSERT INTO family (id_admin,user1) VALUES (?,?)";
        $stmt = $conn->prepare($insertFamilySql);
        $stmt->bind_param("ii", $user_id,$user_id);

        
        if ($stmt->execute()) {
            // Dodano Rodzine pomyślnie
            $response['success'] = true;
            $response['message'] = "Utworzono Rodzine dla usera id: $user_id";

            // Pobierz ostatnio dodane id_domu
            $lastInsertedFamilyId = $conn->insert_id;

            // Utwórz dom z family_id ustawionym na id_domu
            $insertDomSql = "INSERT INTO house (name, family_id, city, postcode) VALUES (?, ?,?,?)";
            $stmt = $conn->prepare($insertDomSql);
            $stmt->bind_param("sisi", $nazwa_domu, $lastInsertedFamilyId,$miasto,$kod);
            $lastInsertedDomId=$conn->insert_id;

            if ($stmt->execute()) {
                // Dodano rodzinę pomyślnie
                $response['family_success'] = true;
                $response['family_message'] = "Utworzono dom dla rodziny z id_domu: $lastInsertedDomId";

                $alteruser_housenumber = "SELECT number_of_houses FROM user WHERE id = $user_id";
                $result = $conn->query($alteruser_housenumber);

                if ($result) {
                    $row = $result->fetch_assoc();
                    $currentHouseNumber = $row['number_of_houses'];

                    // Inkrementujemy numer domu o 1
                    $newHouseNumber = $currentHouseNumber + 1;
                    $alteruser = "UPDATE user SET number_of_houses = $newHouseNumber WHERE id = $user_id";

                    // Wykonujemy zapytanie do aktualizacji numeru domu użytkownika w bazie danych
                    $updateResult = $conn->query($alteruser);

                }
                //wysłanie wiadomosci
                $message=array(
                    'userId'=>$user_id,
                    'message'=>'Utworzono dom '.$nazwa_domu
                );
                $url='http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
                $ch=curl_init($url);

                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($ch,CURLOPT_POST,true);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$message);

                $response=curl_exec($ch);

                echo json_encode($response);

                curl_close($ch);
                header('Location: http://localhost/studia/SMARTHOME/strony/house.php'); // Zakładam, że masz stronę o nazwie "house.php" z listą domów.
                exit;
            } else {
                // Błąd podczas dodawania rodziny
                $response['family_success'] = false;
                $response['family_message'] = "Błąd podczas tworzenia domu: " . $conn->error;
            }
        } else {
            // Błąd podczas dodawania domu
            $response['success'] = false;
            $response['message'] = "Błąd podczas tworzenia rodziny: " . $conn->error;
        }

        // Zamknij połączenie z bazą danych
        $stmt->close();
        $conn->close();
    } else {
        // Użytkownik nie jest zalogowany
        $response['success'] = false;
        $response['message'] = "Użytkownik nie jest zalogowany.";
    }
} else {
    // To nie jest zapytanie POST
    $response['success'] = false;
    $response['message'] = "Nieprawidłowe żądanie.";
}

// Ustaw nagłówki odpowiedzi JSON
header('Content-Type: add_home/json');

// Zwróć odpowiedź jako JSON
echo json_encode($response);
?>
