<?php
session_start();

function detectDeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $width = isset($_GET['width']) ? (int)$_GET['width'] : 0; // Szerokość ekranu przekazywana jako parametr GET

    if ($width <= 0){
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
            exit;
        }
        else{
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        }
    }
    else if ($width <= 480) {
        echo ($width);
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            //header('Location: http://localhost/studia/SMARTHOME/mobile/login.php');
            exit;
        }
        else{
            //header('Location: http://localhost/studia/SMARTHOME/mobile/house.php');
            exit;
        }
    } else {
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
            exit;
        }
        else{
            header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
            exit;
        }
    }
}

$deviceType = detectDeviceType();


?>

