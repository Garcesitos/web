<?php
header('Content-Type: text/html; charset=UTF-8');

// Configuración SMTP
$smtpUser = 'quotations@cargoliner.es';       // <- CAMBIA AQUÍ si usas otro correo de IONOS
$smtpPassword = 'Cargoliner=1';               // <- CAMBIA AQUÍ con tu contraseña real
/*
$captchaSiteKey = '6LcDAuQrAAAAAHA5zAdY0Fqx7HQzdovU4eL2D2TM';
$captchaSecretKey = '6LcDAuQrAAAAAC-XJTmvyCPHfKGfbcTlSkVikCHx';

if (empty($smtpPassword) || empty($captchaSiteKey) || empty($captchaSecretKey)) {
    echo '<div class="alert alert-danger">Faltan valores de configuración.</div>';
    exit;
}

// 1. Verificación reCAPTCHA
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptchaResponse)) {
    echo '<div class="alert alert-danger">Por favor completa el reCAPTCHA.</div>';
    exit;
}

$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
$resp = file_get_contents(
    $verifyUrl
    . '?secret=' . urlencode($captchaSecretKey)
    . '&response=' . urlencode($recaptchaResponse)
    . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'])
);
$json = json_decode($resp, true);
if (empty($json['success'])) {
    echo '<div class="alert alert-danger">La verificación reCAPTCHA ha fallado.</div>';
    exit;
} */

// 2. Cargar PHPMailer
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Recoger datos del formulario (ORIGINALES)
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phonePrefix = trim($_POST['phonePrefix'] ?? '');
$phoneNumber = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

// 3.b NUEVOS CAMPOS (solo añadidos, sin cambiar la lógica existente)
$nbultos     = trim($_POST['nbultos'] ?? '');
$pbultos     = trim($_POST['pbultos'] ?? '');
$dimensiones = trim($_POST['dimensiones'] ?? '');
$recogida    = trim($_POST['recogida'] ?? '');
$entrega     = trim($_POST['entrega'] ?? '');
$detalles    = trim($_POST['detalles'] ?? '');

// 4. Validaciones (SIN CAMBIOS)
$errors = [];
if (mb_strlen($name) < 15) {
    $errors[] = "El nombre completo debe tener al menos 15 caracteres.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "El correo electrónico no tiene un formato válido.";
}
if (!preg_match('/^[\+][0-9]{1,4}[\s][0-9]{6,15}$/', $phoneNumber)) {
    $errors[] = "El número de teléfono debe tener entre 6 y 15 dígitos.";
}
$allowedServices = ['Aéreo', 'Marítimo', /*'Adicionales'*/];
if (!in_array($service, $allowedServices, true)) {
    $errors[] = "Debes seleccionar un servicio de interés.";
}

if (!empty($errors)) {
    echo '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        echo '<li>' . htmlspecialchars($err) . '</li>';
    }
    echo '</ul></div>';
    exit;
}

// 5. Envío de correo con PHPMailer (SIN CAMBIOS salvo el cuerpo)
$mail = new PHPMailer(true);
try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = 'smtp.dondominio.com';              // <- Servidor SMTP de IONOS
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($smtpUser, 'Cotizaciones WEB');      // <- Remitente real
    $mail->addReplyTo($email, $name);                 // <- Para poder responder al cliente

    $destinatarios = [
        //['correo' => 'garcesitosauto@gmail.com', 'nombre' => 'Sweet Check-In'],
        ['correo' => 'informaciongarces@gmail.com', 'nombre' => 'Responsable 2'],
        //['correo' => 'alejandrigarces@gmail.com', 'nombre' => 'RRHH']
    ];

    foreach ($destinatarios as $dest) {
        $mail->addAddress($dest['correo'], $dest['nombre']);
    }

    $mail->isHTML(false);
    $mail->Subject = 'Nueva solicitud de cotización - Web CargoLiner';

    // CUERPO: añadimos los NUEVOS CAMPOS manteniendo el resto igual
    $body = "Nombre: {$name}\nEmail: {$email}\n";
    $body .= "Teléfono:{$phoneNumber}\n";
    $body .= "Servicio de interés: Transporte {$service}\n";
    $body .= "\n--- Detalles de la carga ---\n";
    $body .= "Número de bultos: {$nbultos}\n";
    $body .= "Peso total (kg): {$pbultos}\n";
    $body .= "Dimensiones (L x A x H cm): {$dimensiones}\n";
    $body .= "Lugar de recogida (CP): {$recogida}\n";
    $body .= "Lugar de entrega (CP, Ciudad, País): {$entrega}\n";
    $body .= "Otros detalles: {$detalles}\n";

    $body .= "\nMensaje:\n{$message}\n";

    $mail->Body = $body;
    $mail->send();

    echo '<div class="alert alert-success">Gracias, tu mensaje ha sido enviado correctamente.</div>';
} catch (Exception $e) {
    error_log('Mail error: ' . $mail->ErrorInfo);
    echo '<div class="alert alert-danger">Lo siento, hubo un error al enviar tu mensaje.</div>';
}
?>
