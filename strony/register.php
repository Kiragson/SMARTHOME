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
    <link rel="icon" href="../img/logov2.png" type="image/x-icon">
    <title>Formularz Rejestracji</title>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h2>Formularz Rejestracji</h2>
                <form id="registration-form">
                    <div class="mb-3">
                        <label for="Login" class="form-label">Login:*</label>
                        <input type="text" class="form-control" id="login" name="login" aria-describedby="LoginHelp" placeholder="Login" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password:*</label>
                        <div class="input-group">
                            <input type="password" id="password" class="form-control" name="password" aria-describedby="passwordHelpBlock" placeholder="Password" required>
                            <button type="button" id="showPassword" class="btn btn-light">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="passwordHelpBlock" class="form-text">
                            Your password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.
                        </div>
                    </div>
                    <div class="mb-3">
                        <input id="method" name="method" type="hidden" value="rejestracja" >
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
    document.addEventListener("DOMContentLoaded", function () {
        const registrationForm = document.getElementById("registration-form");
        const passwordInput = document.getElementById("password");
        const showPasswordButton = document.getElementById("showPassword");

        registrationForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(registrationForm);

            fetch("http://localhost/studia/SMARTHOME/php_script/user.php", {
                method: "POST",
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data);

                    if (data.success) {
                        alert("Rejestracja udana!");
                        window.location.href = 'house.php';
                    } else {
                        alert("Błąd rejestracji: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Błąd fetch:", error);
                });
        });

        showPasswordButton.addEventListener("click", function () {
            passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        });

    registrationForm.addEventListener("submit", function (event) {
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        const emailValidationResult = validateEmail(email);
        const passwordValidationResult = validatePassword(password);

        if (!emailValidationResult.isValid || !passwordValidationResult.isValid) {
            event.preventDefault();

            let errorMessage = "Wprowadź poprawne dane:";
            
            if (!emailValidationResult.isValid) {
                errorMessage += `\n- ${emailValidationResult.message}`;
            }

            if (!passwordValidationResult.isValid) {
                errorMessage += `\n- ${passwordValidationResult.message}`;
            }

            document.getElementById("error-message").innerHTML = errorMessage;
        }
    });

    function validateEmail(email) {
        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        return {
            isValid: emailPattern.test(email),
            message: "Nieprawidłowy adres email.",
        };
    }

    function validatePassword(password) {
        const minLength = 8;
        const minLowercase = 1;
        const minUppercase = 1;
        const minNumbers = 1;
        const minSpecialChars = 1;

        let errorMessage = "";

        // Sprawdź długość hasła
        if (password.length < minLength) {
            errorMessage += `Hasło powinno mieć co najmniej ${minLength} znaków. `;
        }

        // Sprawdź obecność małych liter
        if ((password.match(/[a-z]/g) || []).length < minLowercase) {
            errorMessage += "Hasło powinno zawierać co najmniej jedną małą literę. ";
        }

        // Sprawdź obecność dużych liter
        if ((password.match(/[A-Z]/g) || []).length < minUppercase) {
            errorMessage += "Hasło powinno zawierać co najmniej jedną dużą literę. ";
        }

        // Sprawdź obecność cyfr
        if ((password.match(/[0-9]/g) || []).length < minNumbers) {
            errorMessage += "Hasło powinno zawierać co najmniej jedną cyfrę. ";
        }

        // Sprawdź obecność znaków specjalnych
        if ((password.match(/[!@#$%^&*(),.?":{}|<>]/g) || []).length < minSpecialChars) {
            errorMessage += "Hasło powinno zawierać co najmniej jeden znak specjalny. ";
        }

        return {
            isValid: errorMessage === "",
            message: errorMessage,
        };
    }


    });
</script>

</body>
</html>
