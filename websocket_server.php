<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class DeviceControl implements MessageComponentInterface
{
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Dodaj klienta do listy klientów
        $this->clients->attach($conn);
        echo "Nowy klient połączony (Adres IP: {$conn->remoteAddress})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Obsługa wiadomości od klienta
        $data = json_decode($msg, true);
        $device_id = $data['device_id'];
        $state = $data['state'];

        // Tutaj wywołaj logikę do zmiany stanu urządzenia
        updateDeviceStateInDatabase($device_id, $state);

        // Przygotuj wiadomość JSON do przesłania
        $message = json_encode([
            'success' => true,
            'device_id' => $device_id,
        ]);

        // Wyślij wiadomość z potwierdzeniem klientowi
        $from->send($message);

        // Powiadom innych klientów o zmianie stanu
        $this->broadcastDeviceStateChange($device_id, $state);
    }

    public function onClose(ConnectionInterface $conn) {
        // Klient rozłączony, usuń go z listy klientów
        $this->clients->detach($conn);
        echo "Klient rozłączony (Adres IP: {$conn->remoteAddress})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        // Obsługa błędów po stronie serwera
        echo "Błąd: {$e->getMessage()}\n";
    }

    public function broadcastDeviceStateChange($device_id, $new_state) {
        foreach ($this->clients as $client) {
            // Przygotuj wiadomość JSON do przesłania
            $message = json_encode([
                'device_id' => $device_id,
                'state' => $new_state,
            ]);

            // Wyślij wiadomość do klienta
            $client->send($message);
        }
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

