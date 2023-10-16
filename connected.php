<?php
$host = "localhost";
$DBusername = "witryna";
$DBpassword = "Witryna";
$database = "smarthome";

$conn = new mysqli($host, $DBusername, $DBpassword, $database);

if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
?>
