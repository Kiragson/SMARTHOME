<?php

session_start();
$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['id_room'])) {
    $id_pokoju = $_GET['id_room'];

    // Sprawdź, czy sesja jest już rozpoczęta przed używaniem $_SESSION
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // Obsłuż brak zainicjowanej sesji lub brak user_id
        $response['success'] = false;
        $response['message'] = "Brak zainicjowanej sesji lub brak user_id.";
        echo json_encode($response);
        exit();
    }

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    require_once("../connected.php");

    // Rozpocznij transakcję
    $conn->begin_transaction();
    
    try {
        // Zapytanie SQL
        $sql = "SELECT h.name, h.id, h.family_id, f.user1
                FROM House h 
                LEFT JOIN Family f ON f.id = h.family_id
                LEFT JOIN Room r ON r.house_id = h.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pokoju);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $user1 = $row['user1'];
                $nazwa_domu = $row['name'];

                // Przykładowa logika sprawdzająca, czy użytkownik jest właścicielem
                if ($user_id == $user1) {
                    // Usuń rekordy w odwrotnej kolejności: urządzenia, pokoje, rodzina, a następnie dom
                    $sql_delete_urzadzenia = "DELETE FROM device WHERE room_id = ?";
                    $stmt_delete_urzadzenia = $conn->prepare($sql_delete_urzadzenia);
                    $stmt_delete_urzadzenia->bind_param("i", $id_pokoju);
                    $stmt_delete_urzadzenia->execute();
                    $deletedUrzadzenia = $stmt_delete_urzadzenia->affected_rows;
            
                    $sql_delete_room = "DELETE FROM room WHERE id = ?";
                    $stmt_delete_room = $conn->prepare($sql_delete_room);
                    $stmt_delete_room->bind_param("i", $id_pokoju);
                    $stmt_delete_room->execute();
                    $deletedRoom = $stmt_delete_room->affected_rows;

                    // Zakończ transakcję
                    $conn->commit();
            
                    // Zamknij połączenie
                    $stmt->close();
                    $conn->close();
            
                    // Ustalamy odpowiedź JSON
                    $response['success'] = true;
                    $response['message'] = "Operacja zakończona pomyślnie. Pokój i urządzenia.";
            
                    // Wysłanie wiadomości
                    $message = array(
                        'userId' => $user_id,
                        'message' => 'Operacja zakończona pomyślnie. Pokój i urządzenia.' . $id_pokoju
                    );
                    $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
                    $ch = curl_init($url);
            
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
            
                    $curlResponse = curl_exec($ch);
            
                    curl_close($ch);
            
                    // Wyślij odpowiedź JSON
                    header('Content-Type: application/json');
                    //echo json_encode($response);
                    //exit();
            
                } else {
                    // Użytkownik nie jest właścicielem
                    $response['success'] = false;
                    $response['message'] = "Usun.php uzytkownik nie jest wlascicielem.";
                    //echo json_encode($response);
                    //exit();
                }
            }
        } else {
            // Brak wyników dla podanego id_pokoju
            $response['success'] = false;
            $response['message'] = 'Brak wyników dla podanego id_pokoju';
           // echo json_encode($response);
            //exit();
        }
    } catch (Exception $e) {
        // W razie błędu cofnij transakcję
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = "Błąd podczas usuwania rekordów: " . $e->getMessage();
        //echo json_encode($response);
        //exit();
    }

} else {
    // Jeśli brakuje parametru id_pokoju, możesz obsłużyć to odpowiednio.
    $response['success'] = false;
    $response['message'] = "Nieprawidlowe zadanie.";
    //echo json_encode($response);
    //exit();
}
header("Location: ../house.php");
exit;
?>
