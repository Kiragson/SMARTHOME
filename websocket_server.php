<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class DeviceControl implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $conn)
    {
        // Klient podłączony, nie trzeba podejmować żadnych działań na razie
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
    // Obsługa wiadomości od klienta
    $data = json_decode($msg, true);

    // Zakładamy, że w wiadomości przesyłane są dwa klucze: "device_id" i "state"
    $device_id = $data['device_id'];
    $state = $data['state'];

    // Tutaj dodałbyś logikę do zmiany stanu urządzenia
    // Na przykład, uaktualnij bazę danych lub kontroluj urządzenie fizyczne
    // Poniżej przedstawiam przykładowe kroki, których należy dokonać

    // Aktualizacja stanu urządzenia w bazie danych
    updateDeviceStateInDatabase($device_id, $state);

    // Powiadom innych klientów o zmianie stanu
    broadcastDeviceStateChange($device_id, $state);
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Klient rozłączony, można dodać odpowiednią obsługę
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Obsługa błędów po stronie serwera
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new DeviceControl()
        )
    ),
    8080
);

$server->run();
?>
