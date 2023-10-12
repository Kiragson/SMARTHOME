<?php
if (isset($_GET['id_domu'])) {
    $id_domu = $_GET['id_domu'];

    // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
    $conn = new mysqli("localhost", "użytkownik", "hasło", "baza_danych");

    // Sprawdź połączenie
    if ($conn->connect_error) {
        die("Błąd połączenia z bazą danych: " . $conn->connect_error);
    }
    $family_id="SELECT id_family FROM house WHERE id=?";
    // Usuń rekordy związane z domem, pokojami, rodzinami i urządzeniami
    $sql1 = "DELETE FROM house WHERE id = ?";
    $sql2 = "DELETE FROM room WHERE id_domu = ?";
    $sql3 = "DELETE FROM family WHERE id = $family_id";
    $sql4 = "DELETE FROM urzadzenia WHERE id_room
     IN (SELECT id_pokoju FROM pokoj WHERE id_domu = ?)";

    // Przygotowanie i wykonanie zapytań
    if (
        $stmt1 = $conn->prepare($sql1) &&
        $stmt2 = $conn->prepare($sql2) &&
        $stmt3 = $conn->prepare($sql3) &&
        $stmt4 = $conn->prepare($sql4)
    ) {
        $stmt1->bind_param("i", $id_domu);
        $stmt2->bind_param("i", $id_domu);
        $stmt3->bind_param("i", $id_domu);
        $stmt4->bind_param("i", $id_domu);

        // Rozpoczęcie transakcji, aby zapewnić spójność operacji
        $conn->begin_transaction();

        // Wykonaj zapytania
        if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute() && $stmt4->execute()) {
            // Zakończ transakcję i zatwierdź zmiany w bazie danych
            $conn->commit();
        } else {
            // W razie błędu cofnij transakcję
            $conn->rollback();
            echo "Błąd podczas usuwania rekordów: " . $conn->error;
        }

        // Zamknij przygotowane zapytania
        $stmt1->close();
        $stmt2->close();
        $stmt3->close();
        $stmt4->close();
    } else {
        echo "Błąd podczas przygotowywania zapytań: " . $conn->error;
    }

    // Zamknij połączenie
    $conn->close();

    // Przekierowanie po zakończeniu operacji.
    header("Location: lista_domow.php"); // Przekierowuje na stronę listy domów po usunięciu.
    exit;
} else {
    // Jeśli brakuje parametru id_domu, możesz obsłużyć to odpowiednio.
    echo "Nieprawidłowe żądanie.";
}
?>
