<?php
// backend/utils/EmailService.php
// Servei per enviar emails automàtics

require_once __DIR__ . '/../config/Database.php';

class EmailService {
    private $from_email = 'noreply@kairos.cat';
    private $from_name = 'KAIROS - Programa ENGINY';
    
    /**
     * Enviar email d'assignació de taller
     */
    public function enviarEmailAsignacio($centre_email, $centre_nom, $taller_nom) {
        $subject = "✅ Taller Assignat - {$taller_nom}";
        
        $message = $this->getTemplate('assignacio', [
            'centre_nom' => $centre_nom,
            'taller_nom' => $taller_nom
        ]);
        
        return $this->enviarEmail($centre_email, $subject, $message);
    }
    
    /**
     * Enviar email de rebuig de sol·licitud
     */
    public function enviarEmailRebuig($centre_email, $centre_nom, $taller_nom, $motiu = null) {
        $subject = "❌ Sol·licitud Rebutjada - {$taller_nom}";
        
        $message = $this->getTemplate('rebuig', [
            'centre_nom' => $centre_nom,
            'taller_nom' => $taller_nom,
            'motiu' => $motiu ?? 'Capacitat completa'
        ]);
        
        return $this->enviarEmail($centre_email, $subject, $message);
    }
    
    /**
     * Enviar recordatori a professor
     */
    public function enviarEmailRecordatori($professor_email, $professor_nom, $taller_nom, $data_taller, $centre_nom) {
        $subject = "📅 Recordatori de Taller - {$taller_nom}";
        
        $message = $this->getTemplate('recordatori', [
            'professor_nom' => $professor_nom,
            'taller_nom' => $taller_nom,
            'data_taller' => $data_taller,
            'centre_nom' => $centre_nom
        ]);
        
        return $this->enviarEmail($professor_email, $subject, $message);
    }
    
    /**
     * Enviar email de checklist completat
     */
    public function enviarEmailChecklistComplet($centre_email, $centre_nom, $taller_nom) {
        $subject = "✅ Checklist Completat - {$taller_nom}";
        
        $message = $this->getTemplate('checklist_complet', [
            'centre_nom' => $centre_nom,
            'taller_nom' => $taller_nom
        ]);
        
        return $this->enviarEmail($centre_email, $subject, $message);
    }
    
    /**
     * Obtenir template d'email
     */
    private function getTemplate($tipus, $data) {
        $templates = [
            'assignacio' => "
                <h2>🎉 Taller Assignat!</h2>
                <p>Hola <strong>{$data['centre_nom']}</strong>,</p>
                <p>Ens complau informar-vos que el taller <strong>{$data['taller_nom']}</strong> ha estat assignat al vostre centre.</p>
                <p><strong>Pròxims passos:</strong></p>
                <ul>
                    <li>Accediu a la plataforma KAIROS</li>
                    <li>Consulteu els detalls del taller a 'Les Meves Sol·licituds'</li>
                    <li>Completeu el checklist de validació</li>
                    <li>Afegiu la llista d'alumnes participants</li>
                </ul>
                <p>Si teniu qualsevol dubte, no dubteu en contactar-nos.</p>
            ",
            'rebuig' => "
                <h2>❌ Sol·licitud Rebutjada</h2>
                <p>Hola <strong>{$data['centre_nom']}</strong>,</p>
                <p>Lamentem informar-vos que la sol·licitud del taller <strong>{$data['taller_nom']}</strong> ha estat rebutjada.</p>
                <p><strong>Motiu:</strong> {$data['motiu']}</p>
                <p>Us animem a sol·licitar altres tallers del nostre catàleg.</p>
            ",
            'recordatori' => "
                <h2>📅 Recordatori de Taller</h2>
                <p>Hola <strong>{$data['professor_nom']}</strong>,</p>
                <p>Aquest és un recordatori que tens programat el taller <strong>{$data['taller_nom']}</strong>.</p>
                <p><strong>Detalls:</strong></p>
                <ul>
                    <li><strong>Data:</strong> {$data['data_taller']}</li>
                    <li><strong>Centre:</strong> {$data['centre_nom']}</li>
                </ul>
                <p>Assegura't de tenir tot el material preparat.</p>
            ",
            'checklist_complet' => "
                <h2>✅ Checklist Completat</h2>
                <p>Hola <strong>{$data['centre_nom']}</strong>,</p>
                <p>Hem rebut correctament el checklist completat del taller <strong>{$data['taller_nom']}</strong>.</p>
                <p>Gràcies per la vostra col·laboració!</p>
            "
        ];
        
        $template_content = $templates[$tipus] ?? '';
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #0F172A; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    {$template_content}
                    <div class='footer'>
                        <p>Aquest és un email automàtic del sistema KAIROS - Programa ENGINY del Consorci.</p>
                        <p>Si us plau, no respongueu a aquest email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Enviar email (funció base)
     * NOTA: Aquesta és una implementació bàsica. Per producció, usar PHPMailer o similar
     */
    private function enviarEmail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$this->from_name} <{$this->from_email}>",
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // En desenvolupament, només registrar en log
        if (getenv('APP_ENV') === 'development' || true) {
            error_log("EMAIL ENVIAT:\nTo: {$to}\nSubject: {$subject}\n");
            return [
                'success' => true,
                'message' => 'Email registrat en log (mode desenvolupament)'
            ];
        }
        
        // En producció, enviar email real
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Email enviat correctament'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error al enviar email'
            ];
        }
    }
}
?>
