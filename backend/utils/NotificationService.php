<?php
// backend/utils/NotificationService.php
// Servei per gestionar notificacions del sistema

require_once __DIR__ . '/../config/Database.php';

class NotificationService {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Crear una notificació
     */
    public function crearNotificacio($usuari_id, $tipus, $titol, $missatge, $sollicitud_id = null) {
        try {
            $sql = "INSERT INTO notificacions (usuari_id, tipus, titol, missatge, sollicitud_id) 
                    VALUES (:usuari_id, :tipus, :titol, :missatge, :sollicitud_id)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuari_id' => $usuari_id,
                ':tipus' => $tipus,
                ':titol' => $titol,
                ':missatge' => $missatge,
                ':sollicitud_id' => $sollicitud_id
            ]);
            
            return [
                'success' => true,
                'notificacio_id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Notificar assignació de taller
     */
    public function notificarAsignacio($sollicitud_id) {
        try {
            // Obtenir dades de la sol·licitud
            $sql = "SELECT s.centre_id, t.nom as taller_nom, u.nom_complet as centre_nom
                    FROM sollicituds s
                    JOIN tallers t ON s.taller_id = t.id
                    JOIN usuaris u ON s.centre_id = u.id
                    WHERE s.id = :sollicitud_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':sollicitud_id' => $sollicitud_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                return ['error' => 'Sol·licitud no trobada'];
            }
            
            // Crear notificació per al centre
            $titol = "🎉 Taller Assignat!";
            $missatge = "El taller '{$data['taller_nom']}' ha estat assignat al vostre centre. Podeu consultar els detalls i completar el checklist a 'Les Meves Sol·licituds'.";
            
            return $this->crearNotificacio(
                $data['centre_id'],
                'assignacio',
                $titol,
                $missatge,
                $sollicitud_id
            );
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Notificar rebuig de sol·licitud
     */
    public function notificarRebuig($sollicitud_id, $motiu = null) {
        try {
            $sql = "SELECT s.centre_id, t.nom as taller_nom
                    FROM sollicituds s
                    JOIN tallers t ON s.taller_id = t.id
                    WHERE s.id = :sollicitud_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':sollicitud_id' => $sollicitud_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                return ['error' => 'Sol·licitud no trobada'];
            }
            
            $titol = "❌ Sol·licitud Rebutjada";
            $missatge = "La sol·licitud del taller '{$data['taller_nom']}' ha estat rebutjada.";
            
            if ($motiu) {
                $missatge .= " Motiu: {$motiu}";
            }
            
            return $this->crearNotificacio(
                $data['centre_id'],
                'rebuig',
                $titol,
                $missatge,
                $sollicitud_id
            );
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Notificar recordatori a professor
     */
    public function notificarRecordatori($professor_id, $taller_nom, $data_taller) {
        $titol = "📅 Recordatori de Taller";
        $missatge = "Recordatori: Tens programat el taller '{$taller_nom}' per al dia {$data_taller}.";
        
        return $this->crearNotificacio(
            $professor_id,
            'recordatori',
            $titol,
            $missatge
        );
    }
    
    /**
     * Notificar checklist pendent
     */
    public function notificarChecklistPendent($centre_id, $sollicitud_id, $taller_nom) {
        $titol = "📋 Checklist Pendent";
        $missatge = "Si us plau, completa el checklist del taller '{$taller_nom}' per finalitzar el procés.";
        
        return $this->crearNotificacio(
            $centre_id,
            'checklist',
            $titol,
            $missatge,
            $sollicitud_id
        );
    }
    
    /**
     * Obtenir notificacions d'un usuari
     */
    public function obtenirNotificacions($usuari_id, $nomes_no_llegides = false) {
        try {
            $sql = "SELECT * FROM notificacions 
                    WHERE usuari_id = :usuari_id";
            
            if ($nomes_no_llegides) {
                $sql .= " AND llegida = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 50";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':usuari_id' => $usuari_id]);
            
            return [
                'success' => true,
                'notificacions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Marcar notificació com a llegida
     */
    public function marcarLlegida($notificacio_id, $usuari_id) {
        try {
            $sql = "UPDATE notificacions 
                    SET llegida = 1 
                    WHERE id = :id AND usuari_id = :usuari_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $notificacio_id,
                ':usuari_id' => $usuari_id
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Marcar totes les notificacions com a llegides
     */
    public function marcarTotesLlegides($usuari_id) {
        try {
            $sql = "UPDATE notificacions 
                    SET llegida = 1 
                    WHERE usuari_id = :usuari_id AND llegida = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':usuari_id' => $usuari_id]);
            
            return [
                'success' => true,
                'actualitzades' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Comptar notificacions no llegides
     */
    public function comptarNoLlegides($usuari_id) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM notificacions 
                    WHERE usuari_id = :usuari_id AND llegida = 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':usuari_id' => $usuari_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'total' => (int)$result['total']
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>
