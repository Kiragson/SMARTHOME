<?php

session_start();
$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['device_id'])) {
    $device_id = $_GET['device_id'];

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
        // Zapytanie SQL do usuwania urządzenia o określonym device_id
        $sql_delete_urzadzenie = "DELETE FROM device WHERE id = ?";
        $stmt_delete_urzadzenie = $conn->prepare($sql_delete_urzadzenie);
        $stmt_delete_urzadzenie->bind_param("i", $device_id);
        $stmt_delete_urzadzenie->execute();
        $deletedUrzadzenie = $stmt_delete_urzadzenie->affected_rows;

        // Zakończ transakcję
        $conn->commit();
        
        // Zamknij połączenie
        $stmt_delete_urzadzenie->close();
        $conn->close();

        // Ustalamy odpowiedź JSON
        $response['success'] = true;
        $response['message'] = "Operacja zakończona pomyślnie. Usunięto urządzenie.";

        // Wysłanie wiadomości
        $message = array(
            'userId' => $user_id,
            'message' => 'Operacja zakończona pomyślnie. Usunięto urządzenie o id ' . $device_id
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
        echo json_encode($response);
        //exit();

    } catch (Exception $e) {
        // W razie błędu cofnij transakcję
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = "Blad podczas usuwania urzadzenia: " . $e->getMessage();
        echo json_encode($response);
        //exit();
    }

} else {
    // Jeśli brakuje parametru device_id, możesz obsłużyć to odpowiednio.
    $response['success'] = false;
    $response['message'] = "Nieprawidlowe zadanie.";
    echo json_encode($response);
   // exit();
}
//header("Location: {$_SERVER['HTTP_REFERER']}");
?>
