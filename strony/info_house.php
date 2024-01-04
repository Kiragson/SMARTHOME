<?php

    session_start();

    $response = array(); // Tworzymy pusty tablicę na odpowiedź


    if (isset($_GET['id_domu'])) {
        
        $user_id = $_SESSION['user_id'];
        $id_domu = $_GET['id_domu'];

        // Połączenie z bazą danych (zakładam, że masz już skonfigurowane połączenie)
        require_once("../connected.php");

        // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
        $sql = "SELECT h.name, h.city, h.postcode, h.family_id, f.user1, f.user2, f.user3, f.user4, f.user5, f.user6
            FROM House h 
            LEFT JOIN Family f ON f.id = h.family_id
            WHERE h.id = ?";
        // Przygotuj zapytanie SQL
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_domu);

        $stmt->execute();
        $result = $stmt->get_result();

        $housesinfo="";

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc(); // Pobierz dane do tablicy $row

            $houseCity = $row['city'];
            $zipcode=$row['postcode'];
            $houseName = $row['name'];
            $familyId = $row['family_id'];
            $user1 = $row['user1'];
            $user2 = $row['user2'];
            $user3 = $row['user3'];
            $user4 = $row['user4'];
            $user5 = $row['user5'];
            $user6 = $row['user6'];
            // Sprawdź, czy użytkownik jest członkiem domu
            if ($user_id == $user1 || $user_id == $user2 || $user_id == $user3 || $user_id == $user4 || $user_id == $user5 || $user_id == $user6) {
                // Użytkownik jest członkiem domu
                $response['is_member'] = true;
            } else {
                // Użytkownik nie jest członkiem domu
                $response['is_member'] = false;

                // Przekieruj na stronę home.php
                header("Location: house.php");
                exit(); // Upewnij się, że skrypt zakończy działanie po przekierowaniu
            }
        }


        $conn->close();


    }else{
        header('Location: http://localhost/studia/SMARTHOME/404.html');
    }
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $houseName; ?></title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
</head>
<body>
    <?php include '../template/header.php'; ?>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class="col-8 navbar-light p-5 rounded h-100" style="background-color: #e3f2fd;">
                <h1>Informacje o: </h1><h2 class="ms-4"><?php if (isset(   $houseName)){echo $houseName;} ?></h2>
                
                <div class="p-2 m-2">
                    <p>Miasto: <?php if (isset( $houseCity)){echo $houseCity; echo ' ';}else{echo 'Brak';} ?><?php if (isset( $zipcode)){echo $zipcode;}else{echo '   -   ';} ?> </p>
                </div>
                <h1>Współlokatorzy</h1>
                <div class="p-2 m-2">
                    <p>Lokator: <?php if (isset($user2)) { echo $user2; } else { echo 'Brak'; } ?></p>
                    <p>Lokator: <?php if (isset($user3)) { echo $user3; } else { echo 'Brak'; } ?></p>
                    <p>Lokator: <?php if (isset($user4)) { echo $user4; } else { echo 'Brak'; } ?></p>
                    <p>Lokator: <?php if (isset($user5)) { echo $user5; } else { echo 'Brak'; } ?></p>
                    <p>Lokator: <?php if (isset($user6)) { echo $user6; } else { echo 'Brak'; } ?></p>
                </div>
                <div class="container">
                    <div class="row justify-content-center mt-5">
                        <div class="col-4">
                            <form action="new_rommate.php?id_domu=<?php echo $id_domu; ?>" method="post">
                                <button class="btn btn-warning rounded p-2 w-100"type="submit">Dodaj lokatorów</button>
                            </form>
                        </div>
                        <div class="col-4">
                            <form action="../php_script/house.php?id_domu=<?php echo $id_domu; ?>" method="post">
                                <input type="hidden" name="rodzaj" id="rodzaj" value="delete">
                                <button class="btn btn-warning rounded p-2 w-100"type="submit">Usuń</button>
                            </form>
                        </div>
                        <div class="col-4">
                            <form action="edit_house.php?id_domu=<?php echo $id_domu; ?>" method="post">
                                <button class="btn btn-warning rounded p-2 w-100"type="submit">Zarządzaj</button>
                            </form>
                        </div>
                    </div>
                   
                </div>
                
            </div>
        </div>
    </div>

</body>
</html>