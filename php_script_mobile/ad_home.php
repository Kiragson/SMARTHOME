<?php
session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upewnij się, że użytkownik jest zalogowany
    if (isset($_SESSION['username'])) {
        require_once("connected.php"); // Zaimportuj połączenie do bazy danych

        $nazwa_domu = $_POST['nazwa_domu'];
        $login = $_SESSION['username'];
        $user_id = $_SESSION['user_id']; // Pobierz ID zalogowanego użytkownika
        //echo $user_id;
        // Przygotuj zapytanie SQL, aby dodać nowy dom
        

        $insertFamilySql = "INSERT INTO family (id_admin,id_user1) VALUES (?,?)";
        $stmt = $conn->prepare($insertFamilySql);
        $stmt->bind_param("ii", $user_id,$user_id);

        
        if ($stmt->execute()) {
            // Dodano dom pomyślnie
            $response['success'] = true;
            $response['message'] = "Utworzono Rodzine dla usera id: $user_id";

            // Pobierz ostatnio dodane id_domu
            $lastInsertedFamilyId = $conn->insert_id;

            // Utwórz rodzinę z id_admin ustawionym na id_domu
            $insertDomSql = "INSERT INTO house (nazwa, id_family) VALUES (?, ?)";
            $stmt = $conn->prepare($insertDomSql);
            $stmt->bind_param("si", $nazwa_domu, $lastInsertedFamilyId);
            

            if ($stmt->execute()) {
                // Dodano rodzinę pomyślnie
                $response['family_success'] = true;
                $response['family_message'] = "Utworzono dom dla rodziny z id_domu: $lastInsertedDomId";
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
header('Content-Type: application/json');

// Zwróć odpowiedź jako JSON
echo json_encode($response);
?>
