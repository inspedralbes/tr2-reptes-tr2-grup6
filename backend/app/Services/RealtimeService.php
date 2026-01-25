<?php
/**
 * Servei Realtime
 * Path: /backend/app/Services/RealtimeService.php
 * 
 * Emet events al servidor Socket.io per a notificacions en temps real
 * Utilitza HTTP POST al servidor realtime-server
 */

namespace Services;

class RealtimeService {
    
    private $realtime_url;
    private $enabled = true;
    
    public function __construct() {
        // URL del servidor realtime-server
        $this->realtime_url = $_ENV['REALTIME_SERVER_URL'] ?? 'http://localhost:3000';
        $this->enabled = $_ENV['REALTIME_ENABLED'] ?? true;
    }
    
    /**
     * Emet un event al servidor realtime per broadcast a clients
     * 
     * @param string $event El nom de l'event (ex: 'request:created', 'assignment:executed')
     * @param array $payload Les dades a enviar (ex: ['requestId' => 1, 'userId' => 5])
     * @param array $options Opcions addicionals (ex: ['room' => 'workshop:42'])
     * 
     * @return bool True si s'emet correctament, false si falla
     */
    public function emit($event, array $payload = [], array $options = []): bool {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $data = [
                'event' => $event,
                'payload' => $payload,
                'timestamp' => time(),
                'server' => 'backend-php'
            ];
            
            // Afegir room si es proporciona (broadcast només a una sala)
            if (isset($options['room'])) {
                $data['room'] = $options['room'];
            }
            
            // Fer POST request al servidor realtime
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->realtime_url . '/emit');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout curt per no bloquejar
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log si es vol
            if (getenv('DEBUG_REALTIME')) {
                error_log("[Realtime] Event: $event | HTTP: $http_code | Payload: " . json_encode($payload));
            }
            
            return $http_code >= 200 && $http_code < 300;
            
        } catch (\Exception $e) {
            error_log("[Realtime] Error emitting event: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Emet una notificació a un usuari específic
     * 
     * @param int $userId ID de l'usuari destinatari
     * @param string $title Títol de la notificació
     * @param string $message Missatge de la notificació
     * @param string $type Tipus de notificació (info, success, warning, error, etc.)
     * @param array $meta Metadades adicionals (requestId, slotId, etc.)
     */
    public function notifyUser($userId, $title, $message, $type = 'info', array $meta = []): bool {
        return $this->emit('notification:send', [
            'userId' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'meta' => $meta
        ], [
            'room' => "user:$userId"
        ]);
    }
    
    /**
     * Notifica que una nova sol·licitud ha estat creada
     */
    public function notifyRequestCreated($requestId, $userId, $workshopName, $centerName): bool {
        // Notificar usuari
        $this->notifyUser($userId, '✅ Sol·licitud creada', 
            "Has creat una sol·licitud per al taller \"$workshopName\" al centre \"$centerName\"",
            'request');
        
        // Broadcast event
        return $this->emit('request:created', [
            'requestId' => $requestId,
            'userId' => $userId,
            'workshopName' => $workshopName,
            'centerName' => $centerName
        ]);
    }
    
    /**
     * Notifica que una assignació ha estat executada
     */
    public function notifyAssignmentExecuted($allocationId, $userId, $workshopName, $slotInfo): bool {
        // Notificar usuari
        $this->notifyUser($userId, '🎉 Assignació realizada',
            "Se te ha asignado el taller \"$workshopName\" - {$slotInfo['date']} {$slotInfo['time']}",
            'assignment',
            ['allocationId' => $allocationId]
        );
        
        // Broadcast event
        return $this->emit('assignment:executed', [
            'allocationId' => $allocationId,
            'userId' => $userId,
            'workshopName' => $workshopName,
            'slotInfo' => $slotInfo
        ]);
    }
    
    /**
     * Notifica que un slot ha canviat d'estat
     */
    public function notifySlotChanged($slotId, $workshopId, $status, array $data = []): bool {
        return $this->emit("slot:$status", [
            'slotId' => $slotId,
            'workshopId' => $workshopId,
            'status' => $status,
            ...$data
        ], [
            'room' => "workshop:$workshopId"
        ]);
    }
    
    /**
     * Health check del servidor realtime
     */
    public function isConnected(): bool {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->realtime_url . '/health');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $http_code === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
