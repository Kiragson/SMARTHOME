<?php

if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: login.php');
    exit;
}
$login = $_SESSION['username'];

require_once("connected.php");

// Zapytanie SQL do pobrania danych użytkownika
$sql = "SELECT imie, nazwisko, email, telefon FROM user WHERE login = '$login'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Dane użytkownika zostały znalezione
    $row = $result->fetch_assoc();
    $imie = $row['imie'] ?? '---';
    $nazwisko = $row['nazwisko'] ?? '---';
    $email = $row['email'] ?? '---';
    $telefon = $row['telefon'] ?? '---';
    $id=$row['id'];
}

$conn->close();
?>

