<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Formularz Rejestracji</title>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h2>Formularz Rejestracji</h2>
                <form method="post" action="http://localhost/studia/SMARTHOME/php_script/rejestracja.php" id="registration-form">
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" placeholder="Email">
                        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                    </div>
                    <div class="mb-3">
                        <label for="inputPassword5" class="form-label">Password:</label>
                        <input type="password" id="haslo" class="form-control" name="haslo" aria-describedby="passwordHelpBlock" placeholder="Password">
                        <div id="passwordHelpBlock" class="form-text">
                            Your password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.
                        </div>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-warning rounded p-2 w-100 mb-3" type="submit">Rejestracja</button>
                        <a class="btn btn-light rounded p-2 w-100" href="login.php">Zaloguj</a>
                    </div>
                </form>
                <div id="error-message" class="text-danger">
                <?php
                if (!empty($_GET["error_message"])) {
                    echo '<div class="error-message">' . htmlspecialchars($_GET["error_message"]) . '</div>';
                }
                ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("registration-form").addEventListener("submit", function(event) {
            const email = document.getElementById("email").value;
            const haslo = document.getElementById("haslo").value;

            if (!validateEmail(email) || !validatePassword(haslo)) {
                event.preventDefault(); // Zatrzymaj wysyłanie formularza
                document.getElementById("error-message").innerHTML = "Wprowadź poprawny email i hasło.";
            }
        });

        function validateEmail(email) {
            // Prosta walidacja adresu email za pomocą wyrażenia regularnego
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
            return emailPattern.test(email);
        }

        function validatePassword(haslo) {
            // Prosta walidacja hasła - może być dostosowana do Twoich wymagań
            const minPasswordLength = 8;
            return haslo.length >= minPasswordLength;
        }
    </script>
</body>
</html>
