<?php
$response = array(); // Tworzymy pusty tablicę na odpowiedź

if (isset($_GET['id_domu'])) {
    $id_domu = $_GET['id_domu'];

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    require_once("../connected.php");

    // Rozpocznij transakcję
    $conn->begin_transaction();

    try {
        // Usuń rekordy w odwrotnej kolejności: urządzenia, pokoje, rodzina, a następnie dom
        $sql_delete_urzadzenia = "DELETE FROM device WHERE id_room IN (SELECT id FROM room WHERE id_house = ?)";
        $stmt_delete_urzadzenia = $conn->prepare($sql_delete_urzadzenia);
        $stmt_delete_urzadzenia->bind_param("i", $id_domu);
        $stmt_delete_urzadzenia->execute();
        $deletedUrzadzenia = $stmt_delete_urzadzenia->affected_rows;

        $sql_delete_room = "DELETE FROM room WHERE id_house = ?";
        $stmt_delete_room = $conn->prepare($sql_delete_room);
        $stmt_delete_room->bind_param("i", $id_domu);
        $stmt_delete_room->execute();
        $deletedRoom = $stmt_delete_room->affected_rows;

        // Usuń powiązaną rodzinę
        $sql_select_family = "SELECT id_family FROM house WHERE id = ?";
        $stmt_select_family = $conn->prepare($sql_select_family);
        $stmt_select_family->bind_param("i", $id_domu);
        $stmt_select_family->execute();
        $result_select_family = $stmt_select_family->get_result();

        if ($row_family = $result_select_family->fetch_assoc()) {
            $family_id = $row_family['id_family'];

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

        // Zakończ transakcję
        $conn->commit();

        // Zamknij połączenie
        $conn->close();

        // Ustalamy odpowiedź JSON
        $response['success'] = true;
        $response['message'] = "Operacja zakończona pomyślnie. Usunięto dom, pokoje, urządzenia i rodzinę.";

    } catch (Exception $e) {
        // W razie błędu cofnij transakcję
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = "Błąd podczas usuwania rekordów: " . $e->getMessage();
    }
} else {
    // Jeśli brakuje parametru id_domu, możesz obsłużyć to odpowiednio.
    $response['success'] = false;
    $response['message'] = "Nieprawidłowe żądanie.";
}

// Wyślij odpowiedź JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
