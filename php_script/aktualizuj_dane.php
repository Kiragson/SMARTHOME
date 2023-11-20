<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once("../connected.php"); // Importuj plik z połączeniem do bazy danych
    //var_dump($_POST);
    // Pobierz dane z formularza
    $imie = $_POST['first_name'] ;
    $nazwisko = $_POST['last_name'] ;
    $email = $_POST['email'] ;
    $telefon = $_POST['phone_number'] ;
    $username = $_POST["username"];
    $user_id=$_SESSION['user_id'];


    // Zaktualizuj dane w bazie danych
    $update_query = "UPDATE user SET first_name = '$imie', last_name = '$nazwisko', email = '$email', phone_number = '$telefon' WHERE login = '$username'";

    if ($conn->query($update_query) === TRUE) {
        // Aktualizacja danych zakończona sukcesem
        $_SESSION["success_message"] = "Dane użytkownika zostały zaktualizowane.";

        //wysłanie wiadomosci
        $message=array(
            'userId'=>$user_id,
            'message'=>'Zamiana danych użytkownika'
        );
        $url='http://localhost/studia/SMARTHOME/php_script/add_mesage.php';
        $ch=curl_init($url);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$message);

        $response=curl_exec($ch);

        echo json_encode($response);

        curl_close($ch);
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
