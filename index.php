<?php
session_start();

function detectDeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $width = isset($_GET['width']) ? (int)$_GET['width'] : 0; // Szerokość ekranu przekazywana jako parametr GET

    if ($width <= 0){
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            header('Location: login.php');
            exit;
        }
        else{
            header('Location: house.php');
            exit;
        }
    }
    else if ($width <= 480) {
        echo ($width);
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            //header('Location: mlogin.php');
            exit;
        }
        else{
            //header('Location: mhouse.php');
            exit;
        }
    } else {
        if (!isset($_SESSION['username'])) {
            // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
            header('Location: login.php');
            exit;
        }
        else{
            header('Location: house.php');
            exit;
        }
    }
}

$deviceType = detectDeviceType();


?>

