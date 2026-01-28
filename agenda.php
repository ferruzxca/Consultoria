<?php
// Procesa la solicitud de agenda, guarda en clientes_potenciales y envía confirmación.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html#agenda');
    exit;
}

$config = include __DIR__ . '/config.php';

function respond(string $message, bool $ok = false): void {
    $color = $ok ? '#7df4c5' : '#ff7b7b';
    $title = $ok ? 'Solicitud registrada' : 'No se pudo registrar';
    echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>{$title}</title>
    <style>body{font-family:Arial, sans-serif;background:#0c1018;color:#e7edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
    .box{max-width:520px;background:#0f1724;border:1px solid #1f2b3d;border-radius:14px;padding:22px;box-shadow:0 20px 40px rgba(0,0,0,0.45);}
    h1{margin:0 0 12px;font-size:22px;}p{margin:0 0 14px;color:#c7d5e5;}a{color:#00c2ff;text-decoration:none;font-weight:700;}</style></head><body>
    <div class=\"box\"><h1 style=\"color:{$color};\">{$title}</h1><p>{$message}</p><a href=\"index.html#agenda\">Regresar</a></div></body></html>";
    exit;
}

$nombre   = trim($_POST['nombre'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$servicio = trim($_POST['servicio'] ?? '');

if ($nombre === '' || $correo === '' || $telefono === '') {
    respond('Nombre, correo y teléfono son obligatorios.');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    respond('El correo no tiene un formato válido.');
}

$serviceIds = $config['service_ids'] ?? [];
$idServicio = null;
if ($servicio !== '' && isset($serviceIds[$servicio]) && is_numeric($serviceIds[$servicio])) {
    $idServicio = (int) $serviceIds[$servicio];
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare('INSERT INTO clientes_potenciales (nombre, correo, telefono, id_servicio) VALUES (:nombre, :correo, :telefono, :id_servicio)');
    $stmt->bindValue(':nombre', $nombre);
    $stmt->bindValue(':correo', $correo);
    $stmt->bindValue(':telefono', $telefono);
    $stmt->bindValue(':id_servicio', $idServicio, $idServicio === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    if ((int)$e->getCode() === 23000) {
        respond('Ese correo ya está registrado como cliente potencial. Te contactaremos pronto.');
    }
    respond('Error al guardar la solicitud: ' . htmlspecialchars($e->getMessage()));
}

// Envío de confirmación al cliente.
$from       = $config['mail_from'] ?? 'no-reply@example.com';
$fromName   = $config['mail_from_name'] ?? 'Consultoría TI';
$subject    = 'Confirmación de agenda - Consultoría TI';
$folio      = 'P-' . date('Ymd-His');
$lineas     = [
    "Hola {$nombre},",
    "Gracias por tu interés. Hemos recibido tu solicitud y la estamos asignando a un consultor.",
    "Servicio de interés: " . ($servicio ?: 'No especificado'),
    "Teléfono de contacto: {$telefono}",
    "Folio de seguimiento: {$folio}",
    "Te contactaremos en menos de 1 hora hábil. Si requieres ajustar el horario responde a este correo."
];
$body = implode(PHP_EOL . PHP_EOL, $lineas);

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . sprintf('"%s" <%s>', $fromName, $from);
$headers[] = 'Reply-To: ' . $from;
if (!empty($config['mail_bcc'])) {
    $headers[] = 'Bcc: ' . $config['mail_bcc'];
}

// Ignorar fallo de mail, pero notificar en el mensaje.
$mailOk = @mail($correo, $subject, $body, implode("\r\n", $headers));

$msgFinal = $mailOk
    ? 'Hemos registrado tu solicitud y enviamos un correo de confirmación al email proporcionado.'
    : 'Solicitud guardada. No pudimos enviar el correo automático; un consultor te contactará manualmente.';

respond($msgFinal, true);
