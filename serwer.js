const WebSocket = require('ws');
const http = require('http');
const server = http.createServer();
const wss = new WebSocket.Server({ server });

const port = 8080;

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
            updateDeviceState(device_id)
            // Przygotuj wiadomość JSON do przesłania
            

            const device_state = await getDeviceStateInDatabase(device_id);
            const response = JSON.stringify({
                success: true,
                device_id: device_id,
                device_state: device_state,
            });
            

            console.log('Serwer.js 30: wiadomosc');
            // Wyślij wiadomość z potwierdzeniem klientowi
            console.log(response);
            ws.send(response);
    
            // Powiadom innych klientów o zmianie stanu
            broadcastDeviceStateChange(device_id, state);
        } catch (error) {
            // Obsłuż błąd z funkcji updateDeviceStateInDatabase
            console.error("Serwer.js/36: Wystąpił błąd - " + error.message);
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

async function broadcastDeviceStateChange(device_id, new_state) {
    console.log("Serwer.js/55: broadcast device");
    console.log(new_state);
    new_state = await getDeviceStateInDatabase(device_id);
    const message = JSON.stringify({
        device_id: device_id,
        state: new_state,
    });
    console.log(message);

    // Wyślij wiadomość do wszystkich klientów
    clients.forEach((client) => {
        client.send(message);
    });
    
}

const axios = require('axios');

async function updateDeviceState(device_id){
    try {
        const result = await updateDeviceStateInDatabase(device_id);

        if (result && result.success) {
            return { success: true, message: 'update Device State in Database ' };
        } else {
            //console.error("Error updating device state in database:", result && result.message);
            return { success: false, message: 'Failed to update device state' };
        }
    } catch (error) {
        console.error("An error occurred while updating device state:", error);
        return { success: false, message: 'An error occurred while updating device state' };
    }
}
async function updateDeviceStateInDatabase(device_id){
    //switch_device(responseData.newDeviceState, responseData.ip_address);
    rodzaj="changeDeviceState";
    const url = `http://localhost/studia/SMARTHOME/php_script/device.php?device_id=${device_id}&method=${rodzaj}`;
    console.log('Tworzony link: updateDeviceStateInDatabase ', url);

    fetch(url)    
        .then(response => {
            // Check if the response status is OK (200)
            if (!response.ok) {
                console.error('Network response was not ok:', response.status, response.statusText);
                throw new Error('Network response was not ok');
            }
            // Convert the response to JSON
            return response.json();
        })
        .then(responseData => {
            // Obsługa odpowiedzi od skryptu PHP

            if (responseData.success) {
                // Zaktualizowano stan urządzenia pomyślnie
                console.log("Serwer.js: Stan urządzenia został zaktualizowany pomyślnie.");
                
                return { success: true, message: 'updateDeviceState'};
            } else {
                // Błąd podczas aktualizacji stanu urządzenia
                console.error("Serwer.js updateDeviceStateInDatabase(): Błąd podczas aktualizacji stanu urządzenia: " + responseData.message);
                handleError(responseData.message);
            }
        })
        .catch(error => {
            if (error && error.message) {
                console.error("Serwer.js/122: updateDeviceStateInDatabase(): Błąd podczas wysyłania żądania HTTP:" + error.message);
                handleError(error.message);
            }else {
                console.error("Serwer.js/127: updateDeviceStateInDatabase(): Błąd - obiekt błędu jest niezdefiniowany lub nie zawiera właściwości 'message'");
            }
         });
}
function getDeviceStateInDatabase(device_id){
    rodzaj="getDeviceState";
    const url = `http://localhost/studia/SMARTHOME/php_script/device.php?device_id=${device_id}&method=${rodzaj}`;
    console.log('Tworzony link: getDeviceStateInDatabase ', url);

    // Zwróć obietnicę z funkcji fetch
    return fetch(url)
        .then(response => {
            // Check if the response status is OK (200)
            if (!response.ok) {
                console.error('Network response was not ok:', response.status, response.statusText);
                throw new Error('Network response was not ok');
            }
            // Convert the response to JSON
            return response.json();
        })
        .then(responseData => {
            // Obsługa odpowiedzi od skryptu PHP
            if (responseData.success) {
                // Zaktualizowano stan urządzenia pomyślnie
                console.log("Serwer.js: Stan urządzenia został zaktualizowany pomyślnie.");

                // Zwróć tylko wartość 'state'
                const deviceState = responseData.state;
                console.log(deviceState);
                return deviceState;
            } else {
                // Błąd podczas aktualizacji stanu urządzenia
                console.error("Serwer.js getDeviceStateInDatabase(): Błąd podczas pobierania stanu urządzenia: " + responseData.message);
                throw new Error(responseData.message);
            }
        })
        .catch(error => {
            console.error("Serwer.js getDeviceStateInDatabase(): Błąd podczas wykonania żądania HTTP:" + error.message);
            throw error;
        });
}


function handleError(errorMessage) {
    console.error(errorMessage);
}



server.listen(port, () => {
    console.log(`Serwer WebSocket nasłuchuje na porcie ${port}`);
});
