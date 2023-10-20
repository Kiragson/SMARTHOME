<?php
require_once("connected.php");

// Funkcja do sprawdzania, czy hasło spełnia warunki
function is_password_valid($password) {
    $min_length = 8; // Minimalna długość hasła
    $contains_lowercase = preg_match('/[a-z]/', $password); // Przynajmniej jedna mała litera
    $contains_uppercase = preg_match('/[A-Z]/', $password); // Przynajmniej jedna duża litera
    $contains_digit = preg_match('/\d/', $password); // Przynajmniej jedna cyfra
    $contains_special_char = preg_match('/[^a-zA-Z\d]/', $password); // Przynajmniej jeden znak specjalny

    if (!(strlen($password) >= $min_length && $contains_lowercase && $contains_uppercase && $contains_digit && $contains_special_char)) {
        $error_message = "Hasło jest niepoprawne. Upewnij się, że hasło zawiera co najmniej 8 znaków, małe i duże litery, przynajmniej jedną cyfrę i przynajmniej jeden znak specjalny.";
    }

    return "";
}

// Obsługa formularza rejestracji
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $haslo = $_POST["haslo"];

    // Sprawdzenie, czy email i hasło zostały wpisane
    if (empty($email) || empty($haslo)) {
        $error_message = "Proszę wypełnić wszystkie pola.";
    } else {
        // Zabezpiecz dane przed SQL Injection i filtrowaniem skryptów
        $email = mysqli_real_escape_string($conn, $email);
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Filtrowanie tekstu

        // Sprawdzenie, czy login jest poprawnym adresem email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Podany email nie jest poprawnym adresem email.";
        } else {
            // Sprawdzenie, czy użytkownik o danej nazwie już istnieje
            $check_query = "SELECT id FROM user WHERE email = '$email'";
            $check_result = $conn->query($check_query);

            if ($check_result->num_rows > 0) {
                $error_message = "Użytkownik o nazwie '$email' już istnieje.";
            } else {
                // Sprawdzenie, czy hasło spełnia warunki
                $password_error = is_password_valid($haslo);

                if (empty($password_error)) {
                    // Haszowanie hasła (możesz użyć bardziej zaawansowanych metod haszowania)
                    $haslo_hashed = password_hash($haslo, PASSWORD_DEFAULT);

                    // Zapytanie SQL do dodania użytkownika do bazy danych
                    $insert_query = "INSERT INTO user (email, haslo) VALUES ('$email', '$haslo_hashed')";

                    if ($conn->query($insert_query) === TRUE) {
                        $_SESSION["username"] = $email; // Poprawienie przypisania do sesji
                        header("Location: http://localhost/studia/SMARTHOME/strony/house.php");
                        exit; // Przekierowano, nie ma potrzeby dalszego wykonywania kodu
                    } else {
                        $error_message = "Błąd podczas rejestracji: " . $conn->error;
                    }
                } else {
                    $error_message = $password_error;
                }
            }
        }
    }

    // Jeśli wystąpił błąd, wróć do strony register.php z informacją o błędzie w parametrze URL
    header("Location: http://localhost/studia/SMARTHOME/strony/register.php?error_message=" . urlencode($error_message));
    exit; // Przekierowano, nie ma potrzeby dalszego wykonywania kodu
}

$conn->close();
?>
