<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: portal.html');
    exit;
}

$config = include __DIR__ . '/config.php';

function respond(string $message, bool $ok = false): void {
    $color = $ok ? '#7df4c5' : '#ff7b7b';
    $title = $ok ? 'Registro completado' : 'No se pudo registrar';
    echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>{$title}</title>
    <style>body{font-family:Arial, sans-serif;background:#0c1018;color:#e7edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
    .box{max-width:520px;background:#0f1724;border:1px solid #1f2b3d;border-radius:14px;padding:22px;box-shadow:0 20px 40px rgba(0,0,0,0.45);}h1{margin:0 0 12px;font-size:22px;}p{margin:0 0 14px;color:#c7d5e5;}a{color:#00c2ff;text-decoration:none;font-weight:700;}</style></head><body>
    <div class=\"box\"><h1 style=\"color:{$color};\">{$title}</h1><p>{$message}</p><a href=\"portal.html\">Regresar</a></div></body></html>";
    exit;
}

$empresa = trim($_POST['empresa'] ?? '');
$contacto = trim($_POST['contacto'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';
$frase = trim($_POST['frase'] ?? '');
$clave = trim($_POST['clave'] ?? '');
$claveExtra = trim($_POST['clave_extra'] ?? '');

if ($empresa === '' || $contacto === '' || $correo === '' || $telefono === '' || $password === '') {
    respond('Todos los campos son obligatorios.');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    respond('El correo no tiene un formato válido.');
}

if (strlen($password) < 8) {
    respond('La contraseña debe tener al menos 8 caracteres.');
}

if ($frase !== ($config['master_phrase'] ?? '') ||
    $clave !== ($config['master_key'] ?? '') ||
    $claveExtra !== ($config['master_key_extra'] ?? '')) {
    respond('Las credenciales maestras no son válidas.');
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare('INSERT INTO clientes_registrados (nombre, password_hash, direccion, telefono, mail, pagina_web, nivel, status) VALUES (:nombre, :password_hash, :direccion, :telefono, :mail, :pagina_web, :nivel, 1)');
    $stmt->bindValue(':nombre', $empresa);
    $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT));
    $stmt->bindValue(':direccion', 'Contacto: ' . $contacto);
    $stmt->bindValue(':telefono', $telefono);
    $stmt->bindValue(':mail', $correo);
    $stmt->bindValue(':pagina_web', null, PDO::PARAM_NULL);
    $stmt->bindValue(':nivel', 'BASICO');
    $stmt->execute();

    session_regenerate_id(true);
    $_SESSION['cliente'] = [
        'id' => (int) $pdo->lastInsertId(),
        'nombre' => $empresa,
        'mail' => $correo,
        'nivel' => 'BASICO',
    ];

    header('Location: dashboard.php');
    exit;
} catch (PDOException $e) {
    if ((int) $e->getCode() === 23000) {
        respond('Ese correo ya está registrado. Intenta ingresar con tu contraseña.');
    }
    respond('Error al registrar el cliente: ' . htmlspecialchars($e->getMessage()));
}
