<?php
// Inicjalizacja sesji
session_start();

// Zakończ sesję
session_destroy();

// Przekieruj użytkownika na stronę logowania lub inną stronę po wylogowaniu
header("Location: index.php"); // Zmień "login.php" na odpowiednią stronę logowania
exit;
?>
