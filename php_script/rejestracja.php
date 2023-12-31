<?php

require_once("../connected.php");

class Database
{
    private $conn;

    public function __construct($host, $dbname, $username, $password)
    {
        $this->conn = new mysqli($host, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Błąd połączenia z bazą danych: " . $this->conn->connect_error);
        }
    }

    public function query($sql)
    {
        return $this->conn->query($sql);
    }

    public function close()
    {
        $this->conn->close();
    }

    public function getConn()
    {
        return $this->conn;
    }
    public function getError()
    {
        return $this->conn->error;
    }
}

class PasswordValidator
{
    public static function isValid($password)
    {
        $min_length = 8;
        $contains_lowercase = preg_match('/[a-z]/', $password);
        $contains_uppercase = preg_match('/[A-Z]/', $password);
        $contains_digit = preg_match('/\d/', $password);
        $contains_special_char = preg_match('/[^a-zA-Z\d]/', $password);

        return strlen($password) >= $min_length && $contains_lowercase && $contains_uppercase && $contains_digit && $contains_special_char;
    }
}

class UserManager
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function registerUser($email, $password, $login)
    {
        // Sprawdzenie, czy email i hasło zostały wpisane
        if (empty($email) || empty($password) || empty($login)) {
            return "Proszę wypełnić wszystkie pola. Wprowadzono hasło: $password email: $email";
        }

        // Zabezpiecz dane przed SQL Injection i filtrowaniem skryptów
        $email = mysqli_real_escape_string($this->database->getConn(), $email);
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Filtrowanie tekstu
        $login = mysqli_real_escape_string($this->database->getConn(), $login);
        $login = htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); // Filtrowanie tekstu

        // Sprawdzenie, czy login jest poprawnym adresem email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return "Podany email nie jest poprawnym adresem email.";
        }

        // Sprawdzenie, czy użytkownik o danej nazwie już istnieje
        $check_query = "SELECT id FROM user WHERE email = '$email'";
        $check_result = $this->database->query($check_query);

        if ($check_result->num_rows > 0) {
            return "Użytkownik z adresem '$email' już istnieje.";
        }

        $check_query = "SELECT id FROM user WHERE email = '$login'";
        $check_result = $this->database->query($check_query);
        if ($check_result->num_rows > 0) {
            return "Użytkownik o nazwie '$login' już istnieje.";
        }

        // Sprawdzenie, czy hasło spełnia warunki
        if (!PasswordValidator::isValid($password)) {
            return "Hasło jest niepoprawne. Upewnij się, że hasło zawiera co najmniej 8 znaków, małe i duże litery, przynajmniej jedną cyfrę i przynajmniej jeden znak specjalny.";
        }

        // Haszowanie hasła (możesz użyć bardziej zaawansowanych metod haszowania)
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Zapytanie SQL do dodania użytkownika do bazy danych
        $insert_query = "INSERT INTO user (email, password, rank) VALUES ('$email', '$password_hashed','2')";

        if ($this->database->query($insert_query) === TRUE) {
            return null; // Brak błędów, zarejestrowano użytkownika pomyślnie
        } else {
            $error_message = "Błąd podczas rejestracji: " . $this->database->getError();
            return $error_message;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $login = $_POST["login"];

    $database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
    $userManager = new UserManager($database);

    $errorMessage = $userManager->registerUser($email, $password, $login);

    if ($errorMessage) {
        header("Location: http://localhost/studia/SMARTHOME/strony/register.php?error_message=" . urlencode($errorMessage));
        exit;
    }
}

$database->close();
?>
