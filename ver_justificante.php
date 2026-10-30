<?php
// Entrega el archivo de un justificante solo a quien tiene derecho a verlo:
// el alumno que lo envió, el docente de esa clase o un administrativo.
// Los archivos viven en uploads/ (con acceso web bloqueado), por eso se
// sirven desde aquí y no por URL directa.
session_start();
require_once __DIR__ . '/includes/conexion.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit('Inicia sesión para ver este archivo.');
}

$user = $_SESSION['usuario'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT j.archivo, j.archivo_nombre, j.alumno_id, c.docente_id
    FROM justificantes j JOIN clases c ON c.id = j.clase_id
    WHERE j.id = :id LIMIT 1
");
$stmt->execute(['id' => $id]);
$j = $stmt->fetch();

$autorizado = $j && (
    $user['rol'] === 'administrativo'
    || ($user['rol'] === 'alumno' && $j['alumno_id'] === $user['matricula'])
    || ($user['rol'] === 'docente' && $j['docente_id'] === $user['matricula'])
);

if (!$autorizado || empty($j['archivo'])) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$ruta = JUSTIFICANTES_DIR . '/' . basename($j['archivo']);
if (!is_file($ruta)) {
    http_response_code(404);
    exit('El archivo ya no está en el servidor.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $j['archivo_nombre'] ?: $j['archivo']) . '"');
readfile($ruta);
