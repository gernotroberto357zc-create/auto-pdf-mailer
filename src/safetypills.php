<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Importar las clases de PHPMailer al espacio de nombres global
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- CARGA DE LA LIBRERÍA ---
// Si usaste Composer (Opción A), descomenta la siguiente línea:
// require __DIR__ . '/../vendor/autoload.php';

// Si lo descargaste manualmente (Opción B), descomenta estas 3 líneas (ajusta la ruta si es necesario):
/*
require __DIR__ . '/../PHPMailer/Exception.php';
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';
*/

require __DIR__ . '/../vendor/autoload.php';
/**
 * Función mejorada con PHPMailer
 */
function SendMail(string $to, string $asunto, string $cuerpoHTML, string $filePath, array $smtpConfig): bool {
    if (!file_exists($filePath)) {
        error_log("Archivo no encontrado: $filePath");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
       // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomenta esta línea si quieres ver un log detallado de errores
       // $mail->Debugoutput = 'html';
        $mail->isSMTP();
$mail->Host       = $smtpConfig['host'];
        $mail->SMTPAuth   = true;                      // Activar autenticación SMTP
        $mail->Username   = $smtpConfig['username'];   // Tu correo
        $mail->Password   = $smtpConfig['password'];   // Tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Encriptación TLS (o ENCRYPTION_SMTPS para SSL)
        $mail->Port       = $smtpConfig['port'];       // Puerto TCP (587 para TLS, 465 para SSL)
        $mail->CharSet    = 'UTF-8';

        // Remitente y Destinatario
        $mail->setFrom($smtpConfig['username'], $smtpConfig['from_name']);
        $mail->addReplyTo('seguridad@empresa.com', 'Equipo de Seguridad');
        $mail->addAddress($to);

        // Adjuntos
        $mail->addAttachment($filePath); // PHPMailer hace todo el trabajo de Base64 y MIME automáticamente

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHTML;
        // Texto alternativo por si el cliente de correo no soporta HTML
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $cuerpoHTML)); 

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar el mensaje: {$mail->ErrorInfo}");
        return false;
    }
}

// =====================================================================
// 1. CONFIGURACIÓN SMTP (AQUÍ PONES TUS DATOS REALES)
// =====================================================================
$smtpConfig = [
    'host'       => 'smtp.gmail.com', // Ej: smtp.office365.com
    'username'   => '@gmail.com', // Tu correo real
    'password'   => '',         // Tu contraseña real o "Contraseña de aplicación"
    'port'       => 587,                     // Normalmente 587 (TLS) o 465 (SSL)
    'from_name'  => 'Notificaciones Gernot'
];

// =====================================================================
// 2. CONFIGURACIÓN DE RUTAS Y DATOS
// =====================================================================
$dirPath      = __DIR__ . '/../assets/pdfs';
$trackerFile  = __DIR__ . '/puntero.txt';
$templatePath = __DIR__ . '/../templates/safetymail.html';
$destinatario = "gernot_roberto357zc@hotmail.com"; // Tu correo de prueba

$modoPrueba = false; // Mantenlo en false para probar el envío real con PHPMailer

// =====================================================================
// 3. LÓGICA DE DIRECTORIO Y ORDENACIÓN
// =====================================================================
$files = glob($dirPath . '/*.pdf');

if (empty($files)) {
    die("Error: No se encontraron archivos PDFs.");
}

natcasesort($files);
$files = array_values($files);

$lastSent = file_exists($trackerFile) ? trim(file_get_contents($trackerFile)) : '';
$currentIndex = array_search($lastSent, $files);

// Lógica de bucle infinito
if ($lastSent === '' || $currentIndex === false || $currentIndex === (count($files) - 1)) {
    $nexIndex = 0;
} else {
    $nexIndex = $currentIndex + 1;
}

$fileToSend = $files[$nexIndex];
$nombreSinExtension = pathinfo($fileToSend, PATHINFO_FILENAME);

// =====================================================================
// 4. PROCESAMIENTO DE PLANTILLA
// =====================================================================
if (file_exists($templatePath)) {
    $htmlRaw = file_get_contents($templatePath);
    $cuerpoHTML = str_replace('[TEMA]', $nombreSinExtension, $htmlRaw);
} else {
    $cuerpoHTML = "<html><body><h1>Tip de Seguridad: $nombreSinExtension</h1><p>Revisa el adjunto.</p></body></html>";
}

$asuntoEmail = "Tips de seguridad: \"" . $nombreSinExtension . "\"";

// =====================================================================
// 5. EJECUCIÓN (PRUEBA VS PRODUCCIÓN)
// =====================================================================
if ($modoPrueba) {
    echo "<h2>=== MODO PRUEBA ACTIVO ===</h2>";
    echo "<b>Destinatario:</b> $destinatario<br>";
    echo "<b>Asunto:</b> $asuntoEmail<br>";
    echo "<b>Archivo PDF a adjuntar:</b> " . basename($fileToSend) . "<br>";
    
    file_put_contents($trackerFile, $fileToSend);
} else {
    // Fíjate que ahora pasamos el array $smtpConfig a la función
    if (SendMail($destinatario, $asuntoEmail, $cuerpoHTML, $fileToSend, $smtpConfig)) {
        file_put_contents($trackerFile, $fileToSend);
        echo "Éxito: Correo enviado a la bandeja de entrada -> " . basename($fileToSend);
    } else {
        echo "Error: Falló el envío. Revisa tus credenciales SMTP y el log de errores.";
    }
}