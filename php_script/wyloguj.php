<?php
// Inicjalizacja sesji
session_start();

// Zakończ sesję
session_destroy();

// Przekieruj użytkownika na stronę logowania lub inną stronę po wylogowaniu
header("Location: http://localhost/studia/SMARTHOME/index.html");
exit;
?>
