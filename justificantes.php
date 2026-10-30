<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/api/attendance_helper.php';

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['usuario'];
$rol = $user['rol'];

// Solo docentes y administrativos
if (!in_array($rol, ['docente', 'administrativo'], true)) {
    header("Location: index.php");
    exit;
}

$esDocente = ($rol === 'docente');
$error = '';
$success = '';

$filtro = $_GET['estado'] ?? 'pendiente';
if (!in_array($filtro, ['pendiente', 'aceptado', 'rechazado', 'todos'], true)) {
    $filtro = 'pendiente';
}

// -------------------------------------------------------------------------
// RESOLVER UN JUSTIFICANTE (aceptar / rechazar)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $comentario = trim($_POST['comentario'] ?? '');
    $filtro = $_POST['filtro'] ?? $filtro;

    try {
        $stmt = $pdo->prepare("
            SELECT j.*, c.docente_id, c.hora_inicio, c.nombre_materia
            FROM justificantes j JOIN clases c ON c.id = j.clase_id
            WHERE j.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $j = $stmt->fetch();

        if (!$j || ($esDocente && $j['docente_id'] !== $user['matricula'])) {
            $error = 'Ese justificante no existe o no es de una de tus clases.';
        } elseif ($j['estado'] !== 'pendiente') {
            $error = 'Ese justificante ya fue resuelto.';
        } elseif (!in_array($decision, ['aceptar', 'rechazar'], true)) {
            $error = 'Elige si aceptas o rechazas el justificante.';
        } elseif ($decision === 'rechazar' && mb_strlen($comentario) < 5) {
            $error = 'Para rechazarlo escribe un comentario con el motivo (al menos 5 caracteres).';
        } elseif (mb_strlen($comentario) > 500) {
            $error = 'El comentario es muy largo (máximo 500 caracteres).';
        } else {
            $pdo->beginTransaction();

            $nuevoEstado = $decision === 'aceptar' ? 'aceptado' : 'rechazado';
            $stmt = $pdo->prepare("
                UPDATE justificantes
                SET estado = :e, comentario_docente = :c, resuelto_por = :d, resuelto_en = NOW()
                WHERE id = :id AND estado = 'pendiente'
            ");
            $stmt->execute([
                'e' => $nuevoEstado,
                'c' => $comentario === '' ? null : $comentario,
                'd' => $user['matricula'],
                'id' => $id,
            ]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                $error = 'Ese justificante ya fue resuelto.';
            } else {
                if ($decision === 'aceptar') {
                    // La falta pasa a "justificado": cuenta como asistencia
                    $stmt = $pdo->prepare("
                        SELECT id FROM asistencias
                        WHERE usuario_id = :a AND clase_id = :c AND fecha = :f LIMIT 1
                    ");
                    $stmt->execute(['a' => $j['alumno_id'], 'c' => $j['clase_id'], 'f' => $j['fecha']]);
                    $existente = $stmt->fetchColumn();

                    if ($existente) {
                        $pdo->prepare("
                            UPDATE asistencias
                            SET estado = 'justificado', docente_autorizo_id = :d
                            WHERE id = :id
                        ")->execute(['d' => $user['matricula'], 'id' => $existente]);
                    } else {
                        $pdo->prepare("
                            INSERT INTO asistencias (usuario_id, clase_id, fecha, hora, tipo, estado, docente_autorizo_id)
                            VALUES (:a, :c, :f, :h, 'AUTORIZADO_DOCENTE', 'justificado', :d)
                        ")->execute([
                            'a' => $j['alumno_id'], 'c' => $j['clase_id'], 'f' => $j['fecha'],
                            'h' => $j['hora_inicio'], 'd' => $user['matricula'],
                        ]);
                    }
                }

                $pdo->commit();
                $success = $decision === 'aceptar'
                    ? 'Justificante aceptado: la falta quedó como justificada.'
                    : 'Justificante rechazado. El alumno verá tu comentario.';
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = mensajeErrorBD($e, 'resolver el justificante', $user['rol'] ?? null, 'justificantes.php');
    }
}

// -------------------------------------------------------------------------
// LISTADO
// -------------------------------------------------------------------------
$lista = [];
$conteos = ['pendiente' => 0, 'aceptado' => 0, 'rechazado' => 0];

try {
    $where = [];
    $params = [];
    if ($esDocente) {
        $where[] = "c.docente_id = :doc";
        $params['doc'] = $user['matricula'];
    }
    $whereBase = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT j.estado, COUNT(*) FROM justificantes j JOIN clases c ON c.id = j.clase_id
        $whereBase GROUP BY j.estado
    ");
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$est, $n]) {
        $conteos[$est] = (int)$n;
    }

    $whereLista = $where;
    $paramsLista = $params;
    if ($filtro !== 'todos') {
        $whereLista[] = "j.estado = :est";
        $paramsLista['est'] = $filtro;
    }

    $stmt = $pdo->prepare("
        SELECT j.*, c.nombre_materia, c.dia_semana, c.grupo, c.hora_inicio,
               u.nombre AS alumno_nombre, d.nombre AS resolvio_nombre
        FROM justificantes j
        JOIN clases c ON c.id = j.clase_id
        JOIN usuarios u ON u.matricula = j.alumno_id
        LEFT JOIN usuarios d ON d.matricula = j.resuelto_por
        " . ($whereLista ? 'WHERE ' . implode(' AND ', $whereLista) : '') . "
        ORDER BY (j.estado = 'pendiente') DESC, j.creado_en DESC
        LIMIT 100
    ");
    $stmt->execute($paramsLista);
    $lista = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $error ?: mensajeErrorBD($e, 'cargar los justificantes', $user['rol'] ?? null, 'justificantes.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="top-bar">
    <div class="page-title">
        <h2>Justificantes</h2>
        <p>Lee lo que envían tus alumnos, revisa su comprobante y decide si aceptas o rechazas la justificación.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
    <?php
        $pestanas = [
            'pendiente' => 'Pendientes (' . $conteos['pendiente'] . ')',
            'aceptado' => 'Aceptados (' . $conteos['aceptado'] . ')',
            'rechazado' => 'Rechazados (' . $conteos['rechazado'] . ')',
            'todos' => 'Todos',
        ];
    ?>
    <?php foreach ($pestanas as $clave => $texto): ?>
        <a href="justificantes.php?estado=<?php echo $clave; ?>"
           class="<?php echo $filtro === $clave ? 'btn-filter' : 'btn-reset'; ?>"
           style="padding: 8px 14px; height: auto; font-size: 0.85rem; text-decoration: none;">
            <?php echo $texto; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($lista)): ?>
    <div class="panel-card">
        <div style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 12px; display: block;"></i>
            <?php echo $filtro === 'pendiente' ? 'No tienes justificantes pendientes.' : 'No hay justificantes en esta categoría.'; ?>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($lista as $j): ?>
    <?php
        $ext = strtolower(pathinfo((string)$j['archivo'], PATHINFO_EXTENSION));
        $esImagen = in_array($ext, ['jpg', 'png', 'webp', 'gif'], true);
        $urlArchivo = 'ver_justificante.php?id=' . (int)$j['id'];
    ?>
    <div class="panel-card" style="margin-bottom: 16px;">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-user-graduate icon-purple" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($j['alumno_nombre']); ?>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400;">
                    (<?php echo htmlspecialchars($j['alumno_id']); ?>)
                </span>
            </div>
            <div>
                <?php if ($j['estado'] === 'pendiente'): ?>
                    <span class="badge badge-retardo">Pendiente</span>
                <?php elseif ($j['estado'] === 'aceptado'): ?>
                    <span class="badge badge-presente">Aceptado</span>
                <?php else: ?>
                    <span class="badge badge-falta">Rechazado</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1 1 320px; min-width: 0;">
                <div style="font-size: 0.85rem; margin-bottom: 8px;">
                    <strong><?php echo htmlspecialchars($j['nombre_materia']); ?></strong>
                    <?php echo $j['grupo'] ? ' · Grupo ' . htmlspecialchars($j['grupo']) : ''; ?><br>
                    <span style="color: var(--text-muted);">
                        Falta del <strong><?php echo date('d/m/Y', strtotime($j['fecha'])); ?></strong>
                        (<?php echo ucfirst($j['dia_semana']); ?>) ·
                        enviado el <?php echo date('d/m/Y h:i A', strtotime($j['creado_en'])); ?>
                    </span>
                </div>

                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">MOTIVO</div>
                <div style="background: rgba(255,255,255,0.03); border-radius: 8px; padding: 12px; font-size: 0.9rem; margin-bottom: 14px;">
                    <?php echo nl2br(htmlspecialchars($j['motivo'])); ?>
                </div>

                <?php if ($j['estado'] === 'pendiente'): ?>
                    <form action="justificantes.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo (int)$j['id']; ?>">
                        <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
                        <div class="form-group">
                            <label for="comentario_<?php echo (int)$j['id']; ?>">COMENTARIO (obligatorio si lo rechazas)</label>
                            <textarea id="comentario_<?php echo (int)$j['id']; ?>" name="comentario" class="form-select"
                                      rows="2" maxlength="500" style="height: auto; resize: vertical;"
                                      placeholder="Ej: El comprobante no corresponde a la fecha de la falta."></textarea>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" name="decision" value="aceptar" class="btn-filter"
                                    style="background: var(--success, #22c55e);">
                                <i class="fa-solid fa-check"></i> Aceptar
                            </button>
                            <button type="submit" name="decision" value="rechazar" class="btn-filter"
                                    style="background: var(--danger, #ef4444);"
                                    onclick="return confirm('¿Rechazar este justificante? El alumno verá tu comentario.')">
                                <i class="fa-solid fa-xmark"></i> Rechazar
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="font-size: 0.85rem;">
                        <span style="color: var(--text-muted);">
                            Resuelto por <?php echo htmlspecialchars($j['resolvio_nombre'] ?: '—'); ?>
                            <?php echo $j['resuelto_en'] ? 'el ' . date('d/m/Y h:i A', strtotime($j['resuelto_en'])) : ''; ?>
                        </span>
                        <?php if ($j['comentario_docente']): ?>
                            <div style="margin-top: 6px;">“<?php echo htmlspecialchars($j['comentario_docente']); ?>”</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="flex: 0 1 280px;">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">COMPROBANTE</div>
                <?php if (!$j['archivo']): ?>
                    <div style="color: var(--text-muted); font-size: 0.85rem; padding: 20px 0;">
                        <i class="fa-solid fa-image-slash"></i> El alumno no adjuntó archivo.
                    </div>
                <?php elseif ($esImagen): ?>
                    <a href="<?php echo $urlArchivo; ?>" target="_blank" rel="noopener">
                        <img src="<?php echo $urlArchivo; ?>" alt="Comprobante de <?php echo htmlspecialchars($j['alumno_nombre']); ?>"
                             loading="lazy"
                             style="max-width: 100%; max-height: 260px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); display: block;">
                    </a>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">Clic para abrirla completa</div>
                <?php else: ?>
                    <a href="<?php echo $urlArchivo; ?>" target="_blank" rel="noopener" class="btn-reset"
                       style="display: inline-flex; gap: 8px; align-items: center; padding: 10px 14px;">
                        <i class="fa-solid fa-file-pdf"></i>
                        <?php echo htmlspecialchars($j['archivo_nombre'] ?: 'Abrir PDF'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/footer.php';
?>
