<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once("../connected.php"); // Importuj plik z połączeniem do bazy danych
    var_dump($_POST);
    // Pobierz dane z formularza
    $imie = $_POST['first_name'] ;
    $nazwisko = $_POST['last_name'] ;
    $email = $_POST['email'] ;
    $telefon = $_POST['phone_number'] ;
    $username = $_POST["username"];


    // Zaktualizuj dane w bazie danych
    $update_query = "UPDATE user SET first_name = '$imie', last_name = '$nazwisko', email = '$email', phone_number = '$telefon' WHERE login = '$username'";

    if ($conn->query($update_query) === TRUE) {
        // Aktualizacja danych zakończona sukcesem
        $_SESSION["success_message"] = "Dane użytkownika zostały zaktualizowane.";
    } else {
        // Błąd aktualizacji danych
        $_SESSION["error_message"] = "Wystąpił błąd podczas aktualizacji danych użytkownika: " . $conn->error;
    }

    $conn->close();
} else {
    // Jeśli nie jest to zapytanie POST, przekieruj gdzie indziej lub obsłuż inaczej
    header("Location: http://localhost/studia/SMARTHOME/strony/zmien_dane.php");
    exit;
}

// Po aktualizacji danych, przekieruj użytkownika na stronę konto.php
header("Location: http://localhost/studia/SMARTHOME/strony/konto.php");
exit;
?>
