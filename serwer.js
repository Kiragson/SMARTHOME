const WebSocket = require('ws');
const http = require('http');
const server = http.createServer();
const wss = new WebSocket.Server({ server });

const clients = new Set();

wss.on('connection', (ws) => {
    // Dodaj klienta do listy klientów
    clients.add(ws);
    console.log(`Nowy klient połączony (Adres IP: ${ws._socket.remoteAddress})`);

    // Obsługa wiadomości od klienta
    ws.on('message', async (message) => {
        const data = JSON.parse(message);
        const device_id = data.device_id;
        const state = data.state;
    
        try {
            // Wywołaj logikę do zmiany stanu urządzenia
            updateDeviceStateInDatabase(device_id);
    
            // Przygotuj wiadomość JSON do przesłania
            const response = JSON.stringify({
                success: true,
                device_id: device_id,
            });
    
            // Wyślij wiadomość z potwierdzeniem klientowi
            ws.send(response);
    
            // Powiadom innych klientów o zmianie stanu
            broadcastDeviceStateChange(device_id, state);
        } catch (error) {
            // Obsłuż błąd z funkcji updateDeviceStateInDatabase
            console.error("Serwer.js: Wystąpił błąd - " + error.message);
            // Tutaj możesz obsłużyć błąd lub wyświetlić komunikat użytkownikowi
        }
    });
    

    ws.on('close', () => {
        // Klient rozłączony, usuń go z listy klientów
        clients.delete(ws);
        console.log(`Klient rozłączony (Adres IP: ${ws._socket.remoteAddress})`);
    });
    // Obsługa błędów po stronie serwera
    ws.on('error', (error) => {
        
        console.error(`Błąd: ${error.message}`);
    });
});

function broadcastDeviceStateChange(device_id, new_state) {
    console.log("Serwer.js broadcast device");
    const message = JSON.stringify({
        device_id: device_id,
        state: new_state,
    });

    // Wyślij wiadomość do wszystkich klientów
    clients.forEach((client) => {
        client.send(message);
    });
    
}

const axios = require('axios');

function updateDeviceStateInDatabase(device_id) {
    // Przygotuj dane do wysłania na serwer
    const data = {
        device_id: device_id,
    };

    const jsonData = JSON.stringify(data);

    // Wyślij żądanie HTTP POST, aby odczytać i zmienić stan urządzenia
    axios.post('http://localhost/studia/SMARTHOME/update_device_state.php', jsonData)
        .then(response => {
            // Obsługa odpowiedzi od skryptu PHP
            const responseData = response.data;

            if (responseData.success) {
                // Zaktualizowano stan urządzenia pomyślnie
                console.log("Serwer.js: Stan urządzenia został zaktualizowany pomyślnie.");
                // Tutaj możesz podjąć dodatkowe działania w przypadku sukcesu

                // Przykład: Zaktualizowanie tekstu przycisku
                var buttonElement = document.getElementById("deviceButton_" + device_id);
                if (buttonElement) {
                    if (responseData.newDeviceState === 1) {
                        buttonElement.innerHTML = "Off";
                    } else {
                        buttonElement.innerHTML = "On";
                    }
                }

                // Uruchom funkcję 'switch' z danymi z bazy
                switch_device(responseData.newDeviceState, responseData.ip_address);
            } else {
                // Błąd podczas aktualizacji stanu urządzenia
                console.error("Serwer.js updateDeviceStateInDatabase(): Błąd podczas aktualizacji stanu urządzenia: " + responseData.message);
                // Tutaj możesz obsłużyć błąd lub wyświetlić komunikat użytkownikowi
                // Dodaj obsługę błędu
                handleError(responseData.message);
            }
        })
        .catch(error => {
            console.error("Serwer.js updateDeviceStateInDatabase(): Błąd podczas wysyłania żądania HTTP:" + error.message);
            // Tutaj obsłuż błąd sieci lub inny błąd żądania HTTP
            // Dodaj obsługę błędu
            handleError(error.message);
            return error;
        });
}
function switch_device(stan, ip_address){
    console.log(stan+" "+ip_address);
}
function handleError(errorMessage) {
    // Tutaj możesz wykonać odpowiednie działania w przypadku błędu
    console.error("Serwer.js Obsługa błedu: Wystąpił błąd - " + errorMessage);
    // Na przykład, wyświetlenie komunikatu o błędzie użytkownikowi lub inną obsługę błędu.
}



const port = 8080;
server.listen(port, () => {
    console.log(`Serwer WebSocket nasłuchuje na porcie ${port}`);
});
