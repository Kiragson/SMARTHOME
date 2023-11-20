<?php

session_start();
$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['id_domu'])) {
    $id_domu = $_GET['id_domu'];
    $user_id=$_SESSION['user_id'];

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    require_once("../connected.php");
    $sql = "SELECT h.family_id, f.user1
    FROM House h 
    LEFT JOIN Family f ON f.id = h.family_id
    WHERE h.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_domu);

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $user1 = $row['user1'];

    // Sprawdź, czy użytkownik jest członkiem domu
    if ($user_id == $user1) {
        
        // Rozpocznij transakcję
        $conn->begin_transaction();
        try {
            // Usuń rekordy w odwrotnej kolejności: urządzenia, pokoje, rodzina, a następnie dom
            $sql_delete_urzadzenia = "DELETE FROM device WHERE room_id IN (SELECT id FROM room WHERE house_id = ?)";
            $stmt_delete_urzadzenia = $conn->prepare($sql_delete_urzadzenia);
            $stmt_delete_urzadzenia->bind_param("i", $id_domu);
            $stmt_delete_urzadzenia->execute();
            $deletedUrzadzenia = $stmt_delete_urzadzenia->affected_rows;
    
            $sql_delete_room = "DELETE FROM room WHERE house_id = ?";
            $stmt_delete_room = $conn->prepare($sql_delete_room);
            $stmt_delete_room->bind_param("i", $id_domu);
            $stmt_delete_room->execute();
            $deletedRoom = $stmt_delete_room->affected_rows;
    
            // Usuń powiązaną rodzinę
            $sql_select_family = "SELECT family_id FROM house WHERE id = ?";
            $stmt_select_family = $conn->prepare($sql_select_family);
            $stmt_select_family->bind_param("i", $id_domu);
            $stmt_select_family->execute();
            $result_select_family = $stmt_select_family->get_result();
    
            if ($row_family = $result_select_family->fetch_assoc()) {
                $family_id = $row_family['family_id'];
    
                // Usuń rodzinę
                $sql_delete_family = "DELETE FROM family WHERE id = ?";
                $stmt_delete_family = $conn->prepare($sql_delete_family);
                $stmt_delete_family->bind_param("i", $family_id);
                $stmt_delete_family->execute();
                $deletedFamily = $stmt_delete_family->affected_rows;
            } else {
                $deletedFamily = 0;
            }
    
            // Na koniec usuń sam dom
            $sql_delete_house = "DELETE FROM house WHERE id = ?";
            $stmt_delete_house = $conn->prepare($sql_delete_house);
            $stmt_delete_house->bind_param("i", $id_domu);
            $stmt_delete_house->execute();
            $deletedHouse = $stmt_delete_house->affected_rows;
    
            $alteruser_housenumber = "SELECT number_of_houses FROM user WHERE id = $user_id";
            $result = $conn->query($alteruser_housenumber);
    
            if ($result) {
                $row = $result->fetch_assoc();
                $currentHouseNumber = $row['number_of_houses'];
    
                // Inkrementujemy numer domu o 1
                $newHouseNumber = $currentHouseNumber - 1;
                $alteruser = "UPDATE user SET number_of_houses = $newHouseNumber WHERE id = $user_id";
    
                // Wykonujemy zapytanie do aktualizacji numeru domu użytkownika w bazie danych
                $updateResult = $conn->query($alteruser);
    
            }
            // Zakończ transakcję
            $conn->commit();
    
            // Zamknij połączenie
            $conn->close();
    
            // Ustalamy odpowiedź JSON
            $response['success'] = true;
            $response['message'] = "Operacja zakończona pomyślnie. Usunięto dom, pokoje, urządzenia i rodzinę.";
    
            // Po wykonaniu operacji przekieruj użytkownika z powrotem na poprzednią stronę
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit;
    
        } catch (Exception $e) {
            // W razie błędu cofnij transakcję
            $conn->rollback();
            $response['success'] = false;
            $response['message'] = "Błąd podczas usuwania rekordów: " . $e->getMessage();
        }

    } else {
        // Użytkownik nie jest właścicielem
        $response['success'] = false;
        $response['message'] = "Usun.php użytkownik nie jest właścicielem.";
        header("Location: ../strony/house.php");
        exit(); // Upewnij się, że skrypt zakończy działanie po przekierowaniu
    }

    
    
} else {
    // Jeśli brakuje parametru id_domu, możesz obsłużyć to odpowiednio.
    $response['success'] = false;
    $response['message'] = "Nieprawidłowe żądanie.";
    header("Location: ../strony/house.php");
    exit(); // Upewnij się, że skrypt zakończy działanie po przekierowaniu
}

// Wyślij odpowiedź JSON
header('Content-Type: application/json');
echo json_encode($response);
//echo $response;
?>
