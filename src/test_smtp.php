<?php
// Forzar visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar la librería (ajusta la ruta si vendor está un nivel arriba)
require __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // ESTAS DOS LÍNEAS SON LA CLAVE DEL DIAGNÓSTICO
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Prueba con smtp.office365.com si este falla
    $mail->SMTPAuth   = true;
    $mail->Username   = '@gmail.com';
    $mail->Password   = ''; // ¡Ojo! Sin espacios
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Enviarte un correo a ti mismo para probar
    $mail->setFrom('gernot_roberto357zc@hotmail.com', 'Prueba');
    $mail->addAddress('gernot_roberto357zc@hotmail.com');

    $mail->Subject = 'Prueba de Diagnóstico SMTP';
    $mail->Body    = 'Si lees esto, el SMTP funciona.';

    $mail->send();
    echo "<h3>✅ ¡CONEXIÓN ESTABLECIDA CON ÉXITO!</h3>";
} catch (Exception $e) {
    echo "<h3>❌ ERROR FINAL: {$mail->ErrorInfo}</h3>";
}
?>