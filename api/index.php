<?php
/**
 * Motor de Seguridad SafetyPills - Versión Vercel + Supabase
 * Diseñado para ejecución Serverless con persistencia en base de datos.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar dependencias de Composer (PHPMailer)
require __DIR__ . '/../vendor/autoload.php';

// ==========================================
// 1. CONFIGURACIÓN Y VARIABLES DE ENTORNO
// ==========================================
$sbUrl    = getenv('SUPABASE_URL');
$sbKey    = getenv('SUPABASE_KEY');    // Service Role Key de Supabase
$smtpPass = getenv('SMTP_PASSWORD');  // Contraseña de aplicación de Gmail

// Datos del remitente y destino
$emisorEmail    = 'tu_correo@gmail.com'; // Sustituir por tu cuenta real
$nombreEmisor   = 'Ciberseguridad Corporativa';
$destinatario   = 'correo_destino@ejemplo.com'; // Sustituir por destino real

// Rutas relativas a la carpeta /api/
$dirPath      = __DIR__ . '/../assets/pdfs';
$templatePath = __DIR__ . '/../templates/safetymail.html';

// ==========================================
// 2. FUNCIONES DE COMUNICACIÓN CON SUPABASE
// ==========================================

/**
 * Lee de Supabase cuál fue el último archivo enviado
 */
function getSupabasePointer($url, $key) {
    $ch = curl_init("$url/rest/v1/mail_tracker?id=eq.1&select=last_file_sent");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $key",
        "Authorization: Bearer $key"
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    // Si no hay datos, devolvemos cadena vacía para empezar desde el primero
    return isset($data[0]['last_file_sent']) ? $data[0]['last_file_sent'] : '';
}

/**
 * Actualiza la fila en Supabase con el nombre del nuevo PDF enviado
 */
function updateSupabasePointer($url, $key, $filename) {
    $ch = curl_init("$url/rest/v1/mail_tracker?id=eq.1");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["last_file_sent" => $filename]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $key",
        "Authorization: Bearer $key",
        "Content-Type: application/json",
        "Prefer: return=minimal"
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ==========================================
// 3. LÓGICA DE SELECCIÓN DEL SIGUIENTE PDF
// ==========================================

// Obtener lista de archivos PDF en la carpeta assets/pdfs
$files = glob($dirPath . '/*.pdf');

if (empty($files)) {
    die("Error: No hay archivos PDF en la ruta: " . realpath($dirPath));
}

// Ordenar archivos alfabéticamente
natcasesort($files);
$files = array_values($files);

// Consultar base de datos para saber el estado actual
$lastSentPath = getSupabasePointer($sbUrl, $sbKey);
$currentIndex = array_search($lastSentPath, $files);

// Lógica de avance (si no existe o es el último, volvemos al índice 0)
if ($lastSentPath === '' || $currentIndex === false || $currentIndex === (count($files) - 1)) {
    $nextIndex = 0;
} else {
    $nextIndex = $currentIndex + 1;
}

$fileToSend = $files[$nextIndex];
$nombreLimpio = pathinfo($fileToSend, PATHINFO_FILENAME);

// ==========================================
// 4. PREPARACIÓN DEL CONTENIDO DEL CORREO
// ==========================================
if (file_exists($templatePath)) {
    $cuerpoHTML = file_get_contents($templatePath);
    // Inyectamos el nombre del tema en el placeholder del HTML
    $cuerpoHTML = str_replace('[TEMA]', $nombreLimpio, $cuerpoHTML);
} else {
    // Backup si la plantilla no existe
    $cuerpoHTML = "<h1>Seguridad IT</h1><p>Adjuntamos el tip sobre: $nombreLimpio</p>";
}

// ==========================================
// 5. ENVÍO SMTP CON PHPMAILER
// ==========================================
$mail = new PHPMailer(true);

try {
    // Configuración técnica del servidor
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $emisorEmail;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Destinatarios
    $mail->setFrom($emisorEmail, $nombreEmisor);
    $mail->addAddress($destinatario);
    $mail->addReplyTo('soporte@tuempresa.com', 'Soporte IT');

    // Adjuntar el PDF seleccionado
    $mail->addAttachment($fileToSend);

    // Contenido del mensaje
    $mail->isHTML(true);
    $mail->Subject = "Safety Pill: $nombreLimpio";
    $mail->Body    = $cuerpoHTML;
    $mail->AltBody = strip_tags($cuerpoHTML); // Texto plano para apps que no leen HTML

    if ($mail->send()) {
        // Solo si el envío es exitoso, actualizamos Supabase para mañana
        updateSupabasePointer($sbUrl, $sbKey, $fileToSend);
        echo "ÉXITO: Correo enviado correctamente con el archivo: " . basename($fileToSend);
    }

} catch (Exception $e) {
    // Reportar error en caso de fallo SMTP
    header("HTTP/1.1 500 Server Error");
    echo "ERROR: El correo no pudo ser enviado. PHPMailer Error: {$mail->ErrorInfo}";
}