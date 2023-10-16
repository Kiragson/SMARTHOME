const WebSocket = require('ws');
const http = require('http');
const server = http.createServer();
const wss = new WebSocket.Server({ server });

const clients = new Set();

wss.on('connection', (ws) => {
    // Dodaj klienta do listy klientów
    clients.add(ws);
    console.log(`Nowy klient połączony (Adres IP: ${ws._socket.remoteAddress})`);

    ws.on('message', (message) => {
        // Obsługa wiadomości od klienta
        const data = JSON.parse(message);
        const device_id = data.device_id;
        const state = data.state;

        // Tutaj wywołaj logikę do zmiany stanu urządzenia
        updateDeviceStateInDatabase(device_id, state);

        // Przygotuj wiadomość JSON do przesłania
        const response = JSON.stringify({
            success: true,
            device_id: device_id,
        });

        // Wyślij wiadomość z potwierdzeniem klientowi
        ws.send(response);

        // Powiadom innych klientów o zmianie stanu
        broadcastDeviceStateChange(device_id, state);
    });

    ws.on('close', () => {
        // Klient rozłączony, usuń go z listy klientów
        clients.delete(ws);
        console.log(`Klient rozłączony (Adres IP: ${ws._socket.remoteAddress})`);
    });

    ws.on('error', (error) => {
        // Obsługa błędów po stronie serwera
        console.error(`Błąd: ${error.message}`);
    });
});

function broadcastDeviceStateChange(device_id, new_state) {
    const message = JSON.stringify({
        device_id: device_id,
        state: new_state,
    });

    // Wyślij wiadomość do wszystkich klientów
    clients.forEach((client) => {
        client.send(message);
    });
}

function updateDeviceStateInDatabase(device_id, state) {
    // Tutaj dodaj logikę do aktualizacji stanu urządzenia w bazie danych
}

const port = 8080;
server.listen(port, () => {
    console.log(`Serwer WebSocket nasłuchuje na porcie ${port}`);
});
