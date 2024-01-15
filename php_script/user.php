<?php

session_start();
require_once("../connected.php");
function setResponse($success, $message) {
    return ['success' => $success, 'message' => $message];
}
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


class UserManager
{
    private $database;
    private $conn;

    public function isValid($password)
    {
        $min_length = 8;
        $contains_lowercase = preg_match('/[a-z]/', $password);
        $contains_uppercase = preg_match('/[A-Z]/', $password);
        $contains_digit = preg_match('/\d/', $password);
        $contains_special_char = preg_match('/[^a-zA-Z\d]/', $password);

        return strlen($password) >= $min_length && $contains_lowercase && $contains_uppercase && $contains_digit && $contains_special_char;
    }
    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->conn=$database->getConn();
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
                header("Location: http://localhost/studia/SMARTHOME/strony/house.php");
                exit;
            } else {
                return "Błędne hasło.";
            }
        } else {
            return "Błędny login lub email.";
        }
    }

    public function registerUser($email, $password, $login)
    {
        $response = array();

        // Sprawdzenie, czy email, hasło i login zostały wpisane
        if (empty($email) || empty($password) || empty($login)) {
            $response['success'] = false;
            $response['message'] = "Proszę wypełnić wszystkie pola. Wprowadzono hasło: $password email: $email";
        } else {
            // Zabezpiecz dane przed SQL Injection i filtrowaniem skryptów
            $email = mysqli_real_escape_string($this->database->getConn(), $email);
            $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Filtrowanie tekstu
            $login = mysqli_real_escape_string($this->database->getConn(), $login);
            $login = htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); // Filtrowanie tekstu

            // Sprawdzenie, czy login jest poprawnym adresem email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['success'] = false;
                $response['message'] = "Podany email nie jest poprawnym adresem email.";
            }
            else{

            
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $response['success'] = false;
                $response['message'] = "Podany login nie jest poprawny.";
            } 
            else 
            {
                // Sprawdzenie, czy użytkownik o danej nazwie już istnieje
                $check_query = "SELECT id FROM user WHERE email = '$email'";
                $check_result = $this->database->query($check_query);

                if ($check_result->num_rows > 0) {
                    $response['success'] = false;
                    $response['message'] = "Użytkownik z adresem '$email' już istnieje.";
                } else {
                    $check_query = "SELECT id FROM user WHERE email = '$login'";
                    $check_result = $this->database->query($check_query);

                    if ($check_result->num_rows > 0) {
                        $response['success'] = false;
                        $response['message'] = "Użytkownik o nazwie '$login' już istnieje.";
                    } else {
                        // Sprawdzenie, czy hasło spełnia warunki
                        if (!UserManager::isValid($password)) {
                            $response['success'] = false;
                            $response['message'] = "Hasło jest niepoprawne. Upewnij się, że hasło zawiera co najmniej 8 znaków, małe i duże litery, przynajmniej jedną cyfrę i przynajmniej jeden znak specjalny.";
                        } else {
                            // Haszowanie hasła (możesz użyć bardziej zaawansowanych metod haszowania)
                            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

                            // Zapytanie SQL do dodania użytkownika do bazy danych
                            $insert_query = "INSERT INTO user (login, email, password, rank, number_of_houses) VALUES ('$login','$email', '$password_hashed','2','0')";

                            if ($this->database->query($insert_query) === TRUE) {
                                $response['success'] = true; // Brak błędów, zarejestrowano użytkownika pomyślnie
                            } else {
                                $response['success'] = false;
                                $response['message'] = "Błąd podczas rejestracji: " . $this->database->getError();
                            }
                        }
                    }
                }
            }}
        }

        return $response;
    }

    public function updateUser($imie, $nazwisko, $email, $telefon, $user_id)
    {
        $response = array();

        $update_query = "UPDATE user SET";
        $params = array();

        if (!empty($imie)) {
            $update_query .= " first_name = ?,";
            $params[] = $imie;
        }

        if (!empty($nazwisko)) {
            $update_query .= " last_name = ?,";
            $params[] = $nazwisko;
        }

        if (!empty($email)) {
            $update_query .= " email = ?,";
            $params[] = $email;
        }

        if (!empty($telefon)) {
            $update_query .= " phone_number = ?,";
            $params[] = $telefon;
        }

        // Usuń ostatnią przecinkę, jeśli są jakieś zmiany
        if (!empty($params)) {
            $update_query = rtrim($update_query, ',');
            $update_query .= " WHERE id = ?";
            $params[] = $user_id;
        } else {
            // Brak zmian do aktualizacji
            $response = setResponse(false, "Brak zmian do aktualizacji.");
            // Rest of your code...
            return $response;
        }

        // Przygotuj zapytanie SQL
        $stmt = $this->conn->prepare($update_query);

        // Dopasuj typy parametrów
        $types = str_repeat('s', count($params) - 1) . 'i';  // Zakładam, że wszystkie parametry są stringami

        $stmt->bind_param($types, ...$params);

        // Wykonaj zapytanie SQL
        if ($stmt->execute()) {
            // Sprawdź, czy zapytanie wykonano pomyślnie
            if ($stmt->affected_rows > 0) {
                $response = setResponse(true, "Dane użytkownika zostały zaktualizowane.");
                $this->sendMessage($user_id, 'Zamiana danych użytkownika');
            } else {
                $response = setResponse(false, "Brak zmian w danych użytkownika.");
            }
        } else {
            $response = setResponse(false, "Wystąpił błąd podczas aktualizacji danych użytkownika: " . $stmt->error);
        }

        // Zamknij prepared statement
        $stmt->close();

        return $response;
    }


private function sendMessage($user_id, $message)
    {
        $messageData = array(
            'userId' => $user_id,
            'message' => $message
        );

        $url = 'http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);

        $curlResponse = curl_exec($ch);

        // Sprawdź, czy zapytanie cURL zakończyło się sukcesem
        if ($curlResponse === false) {
            $response['success'] = false;
            $response['message'] = 'Błąd podczas wysyłania wiadomości: ' . curl_error($ch);
        } else {
            $response['success'] = true;
            $response['message'] = 'Dane użytkownika zostały zaktualizowane, a wiadomość wysłana.';
        }

        curl_close($ch);
        return $response;
    }



}

$database = new Database("localhost", "smarthome", "witryna", "zaq1@WSX");
$userManager = new UserManager($database);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $postData = $_POST;
    
    if (isset($_POST["method"])) {
        $rodzaj = $_POST["method"];
    } else {
        $rodzaj = null;
    }
}
else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $getData = $_GET;

    if (isset($_GET["method"])) {
        $rodzaj = $_GET["method"];
    } else {
        $rodzaj = null;
    }
}

if (isset($rodzaj)) {
    function getRequestParam($param, $source, $message) {
        return isset($source[$param]) && $source[$param] !== null ? $source[$param] : setResponse(false, $message);
    }
    switch ($rodzaj) {
        case 'logowanie':

            $login = getRequestParam("login",$_POST, 'Brak wartości zmiennej login');
            $password = getRequestParam("password",$_POST, 'Brak wartości zmiennej password');
            $response = $userManager->login($login, $password);
            header("Location: http://localhost/studia/SMARTHOME/index.html");
            break;

        case 'rejestracja':

            $login = getRequestParam("login",$_POST, 'Brak wartości zmiennej login');
            $password = getRequestParam("password",$_POST, 'Brak wartości zmiennej password');
            $email = getRequestParam("email",$_POST, 'Brak wartości zmiennej email');
            $response = $userManager->registerUser($email, $password, $login);
            //header("Location: http://localhost/studia/SMARTHOME/index.html");
            
            break;
        
        case 'update':
            $imie = getRequestParam('first_name', $postData, 'Brak wartości zmiennej first_name');
            $nazwisko = getRequestParam('last_name', $postData, 'Brak wartości zmiennej last_name');
            $email = getRequestParam('email', $postData, 'Brak wartości zmiennej email');
            $telefon = getRequestParam('phone_number', $postData, 'Brak wartości zmiennej phone_number');
            //$username = getRequestParam('username', $postData, 'Brak wartości zmiennej username');
            $user_id = $_SESSION['user_id'];

            $response = $userManager->updateUser($imie, $nazwisko, $email, $telefon, $user_id);


            break;
        case 'logout':
            // Zakończenie sesję
            session_destroy();
            header("Location: http://localhost/studia/SMARTHOME/index.html");
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
    
}
else {
    //echo "brak rodzaju";
}
$database->close();
?>
