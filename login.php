<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: portal.html');
    exit;
}

$config = include __DIR__ . '/config.php';

function respond(string $message, bool $ok = false): void {
    $color = $ok ? '#7df4c5' : '#ff7b7b';
    $title = $ok ? 'Acceso confirmado' : 'No se pudo acceder';
    echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>{$title}</title>
    <style>body{font-family:Arial, sans-serif;background:#0c1018;color:#e7edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
    .box{max-width:520px;background:#0f1724;border:1px solid #1f2b3d;border-radius:14px;padding:22px;box-shadow:0 20px 40px rgba(0,0,0,0.45);}h1{margin:0 0 12px;font-size:22px;}p{margin:0 0 14px;color:#c7d5e5;}a{color:#00c2ff;text-decoration:none;font-weight:700;}</style></head><body>
    <div class=\"box\"><h1 style=\"color:{$color};\">{$title}</h1><p>{$message}</p><a href=\"portal.html\">Regresar</a></div></body></html>";
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    respond('Correo y contraseña son obligatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('El correo no tiene un formato válido.');
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare('SELECT id_cliente_reg, nombre, mail, password_hash, nivel, status FROM clientes_registrados WHERE mail = :mail LIMIT 1');
    $stmt->bindValue(':mail', $email);
    $stmt->execute();
    $client = $stmt->fetch();

    if (!$client) {
        respond('No encontramos una cuenta con ese correo.');
    }

    if ((int)$client['status'] !== 1) {
        respond('Tu cuenta está inactiva. Contacta a tu ejecutivo para reactivarla.');
    }

    if (!password_verify($password, $client['password_hash'])) {
        respond('La contraseña no es correcta.');
    }

    session_regenerate_id(true);
    $_SESSION['cliente'] = [
        'id' => (int) $client['id_cliente_reg'],
        'nombre' => $client['nombre'],
        'mail' => $client['mail'],
        'nivel' => $client['nivel'],
    ];

    header('Location: dashboard.php');
    exit;
} catch (PDOException $e) {
    respond('No se pudo acceder por un error interno: ' . htmlspecialchars($e->getMessage()));
}
