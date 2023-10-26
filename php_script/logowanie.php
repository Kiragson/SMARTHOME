<?php

session_start();
require_once("../connected.php");

// Funkcja do sprawdzania, czy tekst zawiera skrypty
function contains_script($text) {
    $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';
    return preg_match($pattern, $text);
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    echo '<pre>';
    var_dump($_POST);
    echo '</pre>';

    $login_or_email = $_POST["login"];
    $haslo = $_POST["password"];
    if (empty($login_or_email) || empty($haslo)){
        $error_message = "Proszę wypełnić wszystkie pola.";
        echo $error_message;
    }else{
        // Sprawdzenie, czy login_or_email jest adresem email lub loginem
        if (filter_var($login_or_email, FILTER_VALIDATE_EMAIL)) {
            $field = "email";
        } else {
            $field = "login";
        }

        // Zapytanie do bazy danych
        $query = "SELECT id, login, email, password FROM user WHERE $field = '$login_or_email'";
        $result = $conn->query($query);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($haslo, $row["password"])) {
                // Poprawne logowanie
                $_SESSION['zalogowany'] = true;
                $_SESSION["username"] = $row["login"];
                $_SESSION["user_id"]=$row["id"];
                header("Location: http://localhost/studia/SMARTHOME/strony/konto.php");
            } else {
                $error = "Błędne hasło.";
            }
        } else {
            $error = "Błędny login lub email.";
            header("Location: http://localhost/studia/SMARTHOME/strony/login.php?error=" . urlencode($error));
        }
    }
   
}
$conn->close();
?>