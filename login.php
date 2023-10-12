<?php

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Formularz Logowania</title>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h2>Formularz Logowania</h2>
                <form method="post" action="logowanie.php" id="login-form">
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Login:</label>
                        <input type="text" class="form-control" id="login" name="login" placeholder="Login lub Email" aria-describedby="emailHelp">
                        <div id="emailHelp" class="form-text" aria-expanded="true">We'll never share your email with anyone else.</div>
                    </div>
                    <div class="mb-3">
                        <label for="inputPassword5" class="form-label">Password:</label>
                        <input type="password" id="haslo" name="haslo" placeholder="Hasło" class="form-control" aria-describedby="passwordHelpBlock">
                        <div id="passwordHelpBlock" class="form-text">
                            Your password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.
                        </div>
                    </div>
                    <button class="btn btn-warning rounded p-2  w-100 mb-3" type="submit" id="login-button">Zaloguj</button>
                    <a class="btn btn-light rounded p-2 mb-3 w-100" href="register.php">Rejestracja</a>
                    <div id="error-message" class="text-danger" role="alert">
                    <?php
                    if (!empty($_GET["error"])) {
                        echo '<div class="alert alert-danger error" role="alert">' . htmlspecialchars($_GET["error"]) . '</div>';
                    }
                    ?>
                    </div>
                </form>
                <div class="mb-3">
                    <div class="text-end">
                    <a class="" href="#" style="background-color: #e3f2fd;">Nie pamietam hasła</a>
                    </div>
                
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("login-form").addEventListener("submit", function(event) {
            const login = document.getElementById("login").value;
            const haslo = document.getElementById("haslo").value;
            const errorMessage = document.getElementById("error-message");

            if (login.trim() === "" || haslo.trim() === "") {
                event.preventDefault(); // Zatrzymaj wysyłanie formularza
                errorMessage.innerHTML = "Wprowadź login i hasło.";
                errorMessage.setAttribute("aria-alert", "true"); // Zmiana atrybutu aria-alert na true
            } else {
                errorMessage.setAttribute("aria-alert", "false"); // Zmiana atrybutu aria-alert na false
            }
        });
    </script>
</body>
</html>
