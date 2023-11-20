<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
    header('Location: login.php');
    exit;
}
require_once("../connected.php");
if (isset($_GET['error'])) {
    $errorMessage = urldecode($_GET['error']);
    echo "<script>alert('$errorMessage');</script>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idHouse = $_POST['id_house'];
    
    // Sprawdź, czy parametr id_house został przekazany w adresie URL

    $sql = "SELECT name FROM house WHERE id = ?";

    // Przygotuj zapytanie SQL przy użyciu przygotowanych zapytań
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idHouse);

    // Wykonaj zapytanie SQL
    $stmt->execute();
    $result = $stmt->get_result();

    // Sprawdzamy, czy zapytanie zostało wykonane poprawnie
    if ($result) {
        // Pobieramy wynik z zapytania
        $row = $result->fetch_assoc();

        // Pobieramy nazwę domu
        $houseName = $row['name'];

        // Możesz teraz wykorzystać zmienną $houseName, która zawiera nazwę domu
    } else {
        echo "Błąd w zapytaniu: " . $conn->error;
    }

    $stmt->close();

    // Pobierz dostępne pokoje dla danego domu
    $sql = "SELECT id, name FROM room WHERE house_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idHouse);
    $stmt->execute();
    $result = $stmt->get_result();

    // Zapisz dostępne pokoje do tablicy
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
} else {
    echo "Brak przekazanego id_house.";
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?php include '../template/css.php'; ?>
    <?php include '../template/script.php'; ?>
</head>
<body>
    <div class="container">
        <div class='row justify-content-center mt-5'>
            <div class='col-8 navbar-light mt-5 p-3 rounded border border-3 h-100' style='background-color: #e3f2fd;'>
                <div class="mb-3">
                    <h3>Nowe urządzenie</h3>
                </div>
                <form action="http://localhost/studia/SMARTHOME/php_script/add_device.php" method="POST" id="add_device-form">
                <div class='row justify-content-center mt-5'>
                    <div class="mb-3">
                        <label for="name_device" class="form-label">Nazwa</label>
                        <input type="text" class="form-control" id="name_device" name="name_device" aria-describedby="text">
                    </div>
                    <div class="mb-3">
                        <label for="Ip_address" class="form-label">Adres IP:</label>
                        <input type="text" class="form-control" id="ip_adres" name="ip_adres" aria-describedby="text" required>
                        <small class="text-muted">Podaj poprawny adres IP (IPv4).</small>
                    </div>
                    <div class="mb-3">
                        <input type="hidden" name="id_house" value="<?php echo $idHouse; ?>">
                        <input type="hidden" name="stan" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="room" class="form-label">Wybierz pokój:</label>
                        <select class="form-select" name="room" id="room">
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>"><?php echo $room['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <div class='row justify-content-center mt-5'>
                    <div class="col-4 justify-content-center row">
                        <button class="btn btn-primary p-2"  type="submit">Dodaj Urządzenie</button>
                    </div>
                </div>
                </form>
            </div>
        </div> 
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ipAddressInput = document.querySelector('#Ip_address');
        const validationMessage = document.createElement('span');
        validationMessage.className = 'text-danger';
        ipAddressInput.parentNode.appendChild(validationMessage);

        ipAddressInput.addEventListener('input', function () {
            const ipAddress = this.value;
            const isValidIpAddress = isValidIPv4(ipAddress);

            if (isValidIpAddress) {
                this.setCustomValidity('');
                validationMessage.textContent = '';
                // Próba nawiązania połączenia
                checkConnection(ipAddress);
            } else {
                this.setCustomValidity('Wprowadź poprawny adres IP (np. 192.168.1.1).');
                validationMessage.textContent = 'Wprowadź poprawny adres IP (np. 192.168.1.1).';
            }
        });

        function isValidIPv4(ipAddress) {
            const blocks = ipAddress.split('.');
            if (blocks.length !== 4) {
                return false;
            }

            for (const block of blocks) {
                const number = parseInt(block, 10);
                if (isNaN(number) || number < 0 || number > 255) {
                    return false;
                }
            }

            // Sprawdzanie prywatnych adresów IP (możesz dostosować tę listę)
            const privateIPRanges = [
                ['10.0.0.0', '10.255.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255']
            ];

            for (const range of privateIPRanges) {
                const startIP = ipToNumber(range[0]);
                const endIP = ipToNumber(range[1]);
                const userIP = ipToNumber(ipAddress);
                if (userIP >= startIP && userIP <= endIP) {
                    return false;
                }
            }

            return true;
        }
        function checkConnection(ipAddress) {
            const img = new Image();
            img.src = `http://${ipAddress}`;

            img.onload = function () {
                console.log(`Połączono z adresem IP: ${ipAddress}`);
            };

            img.onerror = function () {
                const errorMessage = 'Nie można nawiązać połączenia z adresem IP.';
                validationMessage.textContent = errorMessage;
            };
        }

        function ipToNumber(ip) {
            const parts = ip.split('.').map(part => parseInt(part, 10));
            return (parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3];
        }

    });

</script>

</body>
</html>
