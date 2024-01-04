<?php

session_start();
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
}

class UserManager
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function login($loginOrEmail, $password)
    {
        if (empty($loginOrEmail) || empty($password)) {
            return "Proszę wypełnić wszystkie pola.";
        }

        // Sprawdzenie, czy loginOrEmail jest adresem email lub loginem
        $field = filter_var($loginOrEmail, FILTER_VALIDATE_EMAIL) ? "email" : "login";

        // Zapytanie do bazy danych
        $query = "SELECT id, login, email, password FROM user WHERE $field = '$loginOrEmail'";
        $result = $this->database->query($query);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                // Poprawne logowanie
                $_SESSION['zalogowany'] = true;
                $_SESSION["username"] = $row["login"];
                $_SESSION["user_id"] = $row["id"];
                header("Location: http://localhost/studia/SMARTHOME/strony/konto.php");
                exit;
            } else {
                return "Błędne hasło.";
            }
        } else {
            return "Błędny login lub email.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //echo '<pre>';
    //var_dump($_POST);
    //echo '</pre>';

    $loginOrEmail = $_POST["login"];
    $password = $_POST["password"];

    $database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
    $userManager = new UserManager($database);

    $errorMessage = $userManager->login($loginOrEmail, $password);

    if ($errorMessage) {
        header("Location: http://localhost/studia/SMARTHOME/strony/login.php?error=" . urlencode($errorMessage));
        exit;
    }
}

$database->close();
?>
