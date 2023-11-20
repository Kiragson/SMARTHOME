<?php
echo "add_message";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once("../connected.php"); // Zaimportuj połączenie do bazy danych
    var_dump($_POST);
    $user_id=$_POST['userId'];
    $message=$_POST['message'];
    $date=date("Y-m-d H:i:s");

    $insertMessage = "INSERT INTO messages (user_id,message,date) VALUES (?,?,?)";
    $stmt = $conn->prepare($insertMessage);
    $stmt->bind_param("iss", $user_id,$message,$date);
    if ($stmt->execute()) {
        // Dodano Rodzine pomyślnie
        $response['success'] = true;
        $response['message'] = "Tworzenie wiadomosci przebiegło pomyślnie";

    } else {
        // Błąd podczas dodawania domu
        $response['success'] = false;
        $response['message'] = "Błąd podczas tworzenia wiadomosci: " . $conn->error;
    }

    // Zamknij połączenie z bazą danych
    $stmt->close();


}
else{
    // Jeśli nie jest to zapytanie POST, przekieruj gdzie indziej lub obsłuż inaczej
    header("Location: http://localhost/studia/SMARTHOME/404.html");
    exit;
}
// Ustaw nagłówki odpowiedzi JSON
header('Content-Type: add_message/json');
//echo json_encode($response);
?>