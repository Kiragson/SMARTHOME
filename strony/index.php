<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: http://localhost/studia/SMARTHOME/strony/login.php');
    exit;
}
else{
    header('Location: http://localhost/studia/SMARTHOME/strony/house.php');
    exit;
}
?>