<?php
session_start();

// Inicjalizacja tablicy do przechowywania odpowiedzi
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upewnij się, że użytkownik jest zalogowany
    if (isset($_SESSION['username'])) {
        require_once("../connected.php"); // Zaimportuj połączenie do bazy danych

        
        $familyId = $_POST['family_id'];
        $user2 = $_POST['user2'];
        $user3 = $_POST['user3'];
        $user4 = $_POST['user4'];
        $user5 = $_POST['user5'];
        $user6 = $_POST['user6'];
        // Przygotuj zapytanie SQL do aktualizacji danych w tabeli Family
        $sql = "UPDATE Family 
        SET user2 = ?, user3 = ?, user4 = ?, user5 = ?, user6 = ? 
        WHERE id = ?";

        // Przygotuj zapytanie SQL
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiii", $user2, $user3, $user4, $user5, $user6, $familyId);

        // Wykonaj zapytanie
        if ($stmt->execute()) {
            // Użytkownik nie jest zalogowany
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php'); // Zakładam, że masz stronę o nazwie "house.php" z listą domów.
            exit;
        } else {
            $response['success'] = false;
            $response['message'] = "Błąd podczas aktualizacji danych: " . $stmt->error;
        }

        // Zamknij połączenie
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
