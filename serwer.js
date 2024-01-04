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
            const response = JSON.stringify({
                success: true,
                device_id: device_id,
            });
            console.log('wiadomosc');
            // Wyślij wiadomość z potwierdzeniem klientowi
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

function broadcastDeviceStateChange(device_id, new_state) {
    console.log("Serwer.js/55: broadcast device");
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
async function updateDeviceState(device_id){
    try {
        try{

        //const device = await   updateDeviceStateFizical(device_id);
        //if(device && device.success){
            const result = await updateDeviceStateInDatabase(device_id);

            if (result && result.success) {
                return { success: true, message: 'updateDeviceState' };
            } else {
                console.error("Error updating device state in database:", result && result.message);
                return { success: false, message: 'Failed to update device state' };
            }
        //}
        //else {
        //    console.error("Error updating device state in database:", device && device.message);
        //    return { success: false, message: 'Failed to update device state' };
        //}
        
        }
        catch(error){
            console.error("An error occurred while updating device state:", error);
            return { success: false, message: 'An error occurred while updating device state' };
        }
    } catch (error) {
        console.error("An error occurred while updating device state:", error);
        return { success: false, message: 'An error occurred while updating device state' };
    }
}
async function updateDeviceStateFizical(device_id){
    try{
        // Pobierz adres IP urządzenia przy użyciu funkcji getDeviceIP
        const deviceIP = await getDeviceIP(device_id);
        if (deviceIP !== null) {
            // W tym miejscu możesz używać deviceIP w funkcji updateDeviceStateInDatabase
            const infoURL = `http://${deviceIP}/zeroconf/info`;

            // Pobieramy stan urządzenia
            const response = await fetch(infoURL);
            const data = await response.json();
            console.log('Odpowiedź z pobierania stanu urządzenia:', data);
            const currentState = data.data.switch;

            // Tworzymy link do wysłania zapytania
            const switchURL = `http://${deviceIP}/zeroconf/switch`;

            // Tworzymy dane JSON do wysłania
            const requestData = {
                deviceid: device_id,
                data: {
                    switch: currentState === 'on' ? 'off' : 'on'
                }
            };

            // Wysyłamy zapytanie POST z danymi JSON
            const switchResponse = await fetch(switchURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const responseData = await switchResponse.json();
            console.log('Odpowiedź z zapytania POST:', responseData)

            // Teraz możesz używać deviceIP w dalszej części funkcji
            console.log('Adres IP urządzenia:', deviceIP);

            //zwóróć pozytywnie
            return { success: true, message: 'Pomyślnie zaktualizowano stan urządzenia.' };
        } else {
            console.error('Błąd podczas pobierania adresu IP urządzenia.');
        }
    }
    catch (error) {
        // Obsługa błędów
        console.error('Błąd podczas wykonywania funkcji updateDeviceStateInDatabase:', error);
        return { success: false, message: 'Błąd w funkcji updateDeviceStateFizical.',error };
    }
}   
async function updateDeviceStateInDatabase(device_id,device_state=null){
    //switch_device(responseData.newDeviceState, responseData.ip_address);
    rodzaj="getDeviceState";
    const url = `http://localhost/studia/SMARTHOME/php_script/device.php?device_id=${device_id}&device_state=${device_state}&method=${rodzaj}`;
    console.log('Tworzony link:', url);

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
                if (typeof document !== 'undefined') {
                    var buttonElement = document.getElementById("deviceButton_" + device_id);
                    if (buttonElement) {
                        if (responseData.newDeviceState === 1) {
                            buttonElement.innerHTML = "Off";
                        } else {
                            buttonElement.innerHTML = "On";
                        }
                        console.log("Serwer.js system działa poprawnie");
                    }
                }else{
                    console.log('Ten kod jest wykonywany poza środowiskiem przeglądarki.');
                }
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

                //return error;
            }else {
                console.error("Serwer.js/127: updateDeviceStateInDatabase(): Błąd - obiekt błędu jest niezdefiniowany lub nie zawiera właściwości 'message'");
            }
         });
}
async function getDeviceIP(device_id) {
    try {
        rodzaj="getDeviceIp";
        const url = `http://localhost/studia/SMARTHOME/php_script/device.php?device_id=${device_id}&method=${rodzaj}`;
        console.log('Tworzony link:', url);

        fetch(url)
        
        .then(response => {
            console.log("___________________________________________________________________________")
            console.log(response.json())
            console.log("___________________________________________________________________________")
            // Check if the response status is OK (200)
            if (!response.ok) {
                console.error('Network response was not ok:', response.status, response.statusText);
                throw new Error('Network response was not ok');
            }
            // Convert the response to JSON
            return response.json();
        })
        .catch(error => {
            if (error && error.message) {
                console.error("Serwer.js/122: getDeviceIP(): Błąd podczas wysyłania żądania HTTP:" + error.message);
                handleError(error.message);

                //return error;
            }else {
                console.error("Serwer.js/127: getDeviceIP(): Błąd - obiekt błędu jest niezdefiniowany lub nie zawiera właściwości 'message'");
            }
         })
         .then(responseData => {
            if (responseData.success) {

                console.log(response.json())

                // Pobierz JSON z odpowiedzi
                const data = response.json();

                // Pobierz wartość deviceIP z danych JSON
                const deviceIP = data.deviceIP;

                // Zwróć wartość deviceIP
                return deviceIP;
            } else {
                // Błąd podczas aktualizacji stanu urządzenia
                console.error("Serwer.js getDeviceIP(): Błąd podczas sprawdzania stanu urządzenia: " + responseData.message);
                handleError(responseData.message);
            }

         });
        
    } catch (error) {
        // Obsługa błędów
        console.error('Błąd podczas pobierania adresu IP urządzenia:', error);
        return null;
    }
}
async function getDeviceState(deviceIP, device_id) {
    try {
        const infoURL = `http://${deviceIP}:8081/zeroconf/info`;
        const requestData = {
            deviceid: "",
            data: {}
          };
        const requestOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json', // Ustawienie typu danych na JSON
        },
        body: JSON.stringify(requestData), // Konwersja obiektu na format JSON i przekazanie w ciele żądania
        };
        try {
            // Wysyłamy żądanie POST
            const response = await fetch(infoURL, requestOptions);
          
            if (response.ok) {
                // Jeśli odpowiedź jest poprawna (status 200 OK), przetwarzamy dane JSON z odpowiedzi
                const data = await response.json();
                console.log('Odpowiedź z pobierania stanu urządzenia:', data);
              
                const currentState = data.data.switch;
                // Aktualizujemy stan urządzenia w bazie danych
                await updateDeviceStateInDatabase(device_id, currentState);

                // Wyświetlamy stan urządzenia w konsoli
                console.log(`Stan urządzenia ${device_id}: ${currentState}`);
            } else {
              // W przypadku błędu, wyświetlamy odpowiedni komunikat
              console.error('Błąd podczas pobierania stanu urządzenia:', response.statusText);
            }
          } catch (error) {
            // W przypadku błędu połączenia
            console.error('Błąd połączenia:', error.message);
          }

        
    } catch (error) {
        // Obsługa błędów
        console.error(`Błąd podczas pobierania stanu urządzenia ${device_id}:`, error);
        await updateDeviceStateInDatabase(device_id, 3);
        // Dodaj kod do obsługi utraty połączenia z urządzeniem
        console.log(`Utracono połączenie z urządzeniem ${device_id}`);
    }
}
/*
async function startCommunicationWithDevices(deviceIds) {
    // Uruchom stałą komunikację z każdym urządzeniem
    deviceIds.forEach(device_id => {
        getDeviceIP(device_id)
            .then(deviceIP => {
                if (deviceIP !== null) {
                    // Uruchom funkcję getDeviceState cyklicznie co np. 5 sekund
                    const intervalId = setInterval(() => {
                        getDeviceState(deviceIP, device_id);
                    }, 5000); // 5000 milisekund = 5 sekund

                    // Zapisz intervalId w obiekcie, aby móc go później zatrzymać
                    const communicationIntervals = communicationIntervals || {};
                    communicationIntervals[device_id] = intervalId;

                    console.log(`Rozpoczęto stałą komunikację z urządzeniem ${device_id}`);
                } else {
                    console.error(`Błąd podczas pobierania adresu IP urządzenia ${device_id}.`);
                }
            })
            .catch(error => {
                console.error(`Błąd podczas rozpoczynania stałej komunikacji z urządzeniem ${device_id}:`, error);
            });
    });
}
*/
function handleError(errorMessage) {
    console.error(errorMessage);
}

//const deviceIds = [3]; // Lista ID urządzeń
//startCommunicationWithDevices(deviceIds);




server.listen(port, () => {
    console.log(`Serwer WebSocket nasłuchuje na porcie ${port}`);
});
