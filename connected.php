<?php
$host = "localhost";
$DBusername = "admin";
$DBpassword = "admin"; // Zmień na prawidłowe hasło
$database = "smarthouse";

$conn = new mysqli($host, $DBusername, $DBpassword, $database);

if ($conn->connect_error) {
    // Błąd połączenia, wyświetl komunikat
    echo "Błąd połączenia z bazą danych: " . $conn->connect_error;
    // Możesz przekierować na stronę błędu lub inaczej obsłużyć błąd
    header("Location: 404.html");
    exit;
}

// Połączenie z bazą danych zostało nawiązane pomyślnie
// Możesz kontynuować wykonywanie operacji na bazie danych
?>
