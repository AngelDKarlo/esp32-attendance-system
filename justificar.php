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

// Esta vista es para alumnos
if ($user['rol'] !== 'alumno') {
    header("Location: index.php");
    exit;
}

$matricula = $user['matricula'];
$error = '';
$success = '';

$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'application/pdf' => 'pdf',
];

$numerosDia = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6];

/**
 * Guarda el archivo subido. Devuelve [nombreGuardado, nombreOriginal] o un
 * string con el mensaje de error.
 */
function guardarArchivoJustificante(array $archivo, array $tiposPermitidos) {
    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        return 'El archivo es demasiado grande (máximo 5 MB).';
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return 'No se pudo subir el archivo. Intenta de nuevo.';
    }
    if ($archivo['size'] > JUSTIFICANTE_MAX_BYTES) {
        return 'El archivo es demasiado grande (máximo 5 MB).';
    }

    // El tipo se detecta por el contenido, no por lo que diga el navegador
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($tiposPermitidos[$mime])) {
        return 'Solo se aceptan fotos (JPG, PNG, WEBP, GIF) o PDF.';
    }

    if (!is_dir(JUSTIFICANTES_DIR) && !mkdir(JUSTIFICANTES_DIR, 0775, true)) {
        error_log('[justificar.php] No se pudo crear ' . JUSTIFICANTES_DIR);
        return 'No se pudo guardar el archivo en el servidor. Avísale al administrador.';
    }

    // Bloquea el acceso directo por URL: los archivos solo se sirven por ver_justificante.php
    $htaccess = JUSTIFICANTES_DIR . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n");
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], JUSTIFICANTES_DIR . '/' . $nombre)) {
        error_log('[justificar.php] move_uploaded_file falló para ' . $nombre);
        return 'No se pudo guardar el archivo en el servidor. Avísale al administrador.';
    }

    return [$nombre, mb_substr(basename($archivo['name']), 0, 200)];
}

// -------------------------------------------------------------------------
// ENVIAR JUSTIFICANTE
// -------------------------------------------------------------------------
$claseForm = (int)($_GET['clase_id'] ?? 0);
$fechaForm = $_GET['fecha'] ?? '';
$motivoForm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claseForm = (int)($_POST['clase_id'] ?? 0);
    $fechaForm = trim($_POST['fecha'] ?? '');
    $motivoForm = trim($_POST['motivo'] ?? '');

    try {
        $stmt = $pdo->prepare("
            SELECT c.* FROM inscripciones i JOIN clases c ON c.id = i.clase_id
            WHERE i.alumno_id = :m AND c.id = :c LIMIT 1
        ");
        $stmt->execute(['m' => $matricula, 'c' => $claseForm]);
        $clase = $stmt->fetch();

        $fechaValida = DateTime::createFromFormat('Y-m-d', $fechaForm);
        $fechaValida = $fechaValida && $fechaValida->format('Y-m-d') === $fechaForm;

        if (!$clase) {
            $error = 'Elige una materia en la que estés inscrito.';
        } elseif (!$fechaValida) {
            $error = 'Indica la fecha de la inasistencia.';
        } elseif ($fechaForm > fechaHoy()) {
            $error = 'No puedes justificar una fecha que todavía no llega.';
        } elseif ((int)date('N', strtotime($fechaForm)) !== ($numerosDia[$clase['dia_semana']] ?? 0)) {
            $error = 'El ' . date('d/m/Y', strtotime($fechaForm)) . ' no hubo clase de esa materia; '
                   . 'se imparte el día: ' . $clase['dia_semana'] . '.';
        } elseif (mb_strlen($motivoForm) < 10) {
            $error = 'Explica el motivo de tu falta (al menos 10 caracteres).';
        } elseif (mb_strlen($motivoForm) > 1000) {
            $error = 'El motivo es muy largo (máximo 1000 caracteres).';
        } elseif (esDiaInhabil($pdo, $claseForm, $fechaForm)) {
            $error = 'Ese día el docente lo marcó como inhábil: no cuenta como falta, no necesitas justificarlo.';
        } else {
            // ¿Ya asistió ese día?
            $stmt = $pdo->prepare("
                SELECT estado FROM asistencias
                WHERE usuario_id = :m AND clase_id = :c AND fecha = :f LIMIT 1
            ");
            $stmt->execute(['m' => $matricula, 'c' => $claseForm, 'f' => $fechaForm]);
            $estadoActual = $stmt->fetchColumn();

            // ¿Ya envió uno para ese día?
            $stmt = $pdo->prepare("
                SELECT id, estado, archivo FROM justificantes
                WHERE alumno_id = :m AND clase_id = :c AND fecha = :f LIMIT 1
            ");
            $stmt->execute(['m' => $matricula, 'c' => $claseForm, 'f' => $fechaForm]);
            $previo = $stmt->fetch();

            if (in_array($estadoActual, ['presente', 'retardo'], true)) {
                $error = 'Ese día sí registraste asistencia; no hay falta que justificar.';
            } elseif ($estadoActual === 'justificado' || ($previo && $previo['estado'] === 'aceptado')) {
                $error = 'Esa falta ya está justificada.';
            } elseif ($previo && $previo['estado'] === 'pendiente') {
                $error = 'Ya enviaste un justificante para ese día y está esperando respuesta del docente.';
            } else {
                $guardado = guardarArchivoJustificante($_FILES['archivo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $tiposPermitidos);

                if (is_string($guardado)) {
                    $error = $guardado;
                } else {
                    [$nombreArchivo, $nombreOriginal] = $guardado;

                    if ($previo) {
                        // Reenvío de uno rechazado: reemplaza al anterior
                        if (!empty($previo['archivo'])) {
                            @unlink(JUSTIFICANTES_DIR . '/' . basename($previo['archivo']));
                        }
                        $stmt = $pdo->prepare("
                            UPDATE justificantes
                            SET motivo = :motivo, archivo = :archivo, archivo_nombre = :orig,
                                estado = 'pendiente', comentario_docente = NULL,
                                resuelto_por = NULL, resuelto_en = NULL, creado_en = NOW()
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'motivo' => $motivoForm, 'archivo' => $nombreArchivo,
                            'orig' => $nombreOriginal, 'id' => $previo['id'],
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO justificantes (alumno_id, clase_id, fecha, motivo, archivo, archivo_nombre)
                            VALUES (:m, :c, :f, :motivo, :archivo, :orig)
                        ");
                        $stmt->execute([
                            'm' => $matricula, 'c' => $claseForm, 'f' => $fechaForm,
                            'motivo' => $motivoForm, 'archivo' => $nombreArchivo, 'orig' => $nombreOriginal,
                        ]);
                    }

                    $success = 'Justificante enviado. Tu docente lo revisará y verás aquí su respuesta.';
                    $claseForm = 0;
                    $fechaForm = '';
                    $motivoForm = '';
                }
            }
        }
    } catch (PDOException $e) {
        $error = mensajeErrorBD($e, 'enviar tu justificante', $user['rol'] ?? null, 'justificar.php');
    }
}

// -------------------------------------------------------------------------
// DATOS PARA LA VISTA
// -------------------------------------------------------------------------
$misClases = [];
$faltasJustificables = [];
$misJustificantes = [];

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nombre_materia, c.dia_semana, c.hora_inicio, c.hora_fin
        FROM inscripciones i JOIN clases c ON c.id = i.clase_id
        WHERE i.alumno_id = :m
        ORDER BY c.nombre_materia, FIELD(c.dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado'), c.hora_inicio
    ");
    $stmt->execute(['m' => $matricula]);
    $misClases = $stmt->fetchAll();

    // Faltas registradas que aún no tienen justificante en curso ni aceptado
    $stmt = $pdo->prepare("
        SELECT a.clase_id, a.fecha, c.nombre_materia, c.dia_semana
        FROM asistencias a
        JOIN clases c ON c.id = a.clase_id
        WHERE a.usuario_id = :m AND a.estado = 'falta'
          AND NOT EXISTS (SELECT 1 FROM dias_inhabiles di WHERE di.clase_id = a.clase_id AND di.fecha = a.fecha)
          AND NOT EXISTS (SELECT 1 FROM justificantes j
                           WHERE j.alumno_id = a.usuario_id AND j.clase_id = a.clase_id
                             AND j.fecha = a.fecha AND j.estado IN ('pendiente','aceptado'))
        ORDER BY a.fecha DESC
        LIMIT 30
    ");
    $stmt->execute(['m' => $matricula]);
    $faltasJustificables = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT j.*, c.nombre_materia, d.nombre AS resolvio_nombre
        FROM justificantes j
        JOIN clases c ON c.id = j.clase_id
        LEFT JOIN usuarios d ON d.matricula = j.resuelto_por
        WHERE j.alumno_id = :m
        ORDER BY j.creado_en DESC
    ");
    $stmt->execute(['m' => $matricula]);
    $misJustificantes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $error ?: mensajeErrorBD($e, 'cargar tus justificantes', $user['rol'] ?? null, 'justificar.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="top-bar">
    <div class="page-title">
        <h2>Justificar una falta</h2>
        <p>Elige el día que faltaste, explica el motivo y, si tienes, adjunta una foto de tu receta médica o comprobante.</p>
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

<?php if (empty($misClases)): ?>
    <div class="panel-card">
        <div style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
            Todavía no estás inscrito en ninguna materia, así que no hay nada que justificar.
        </div>
    </div>
<?php else: ?>

    <?php if (!empty($faltasJustificables)): ?>
        <div class="panel-card" style="margin-bottom: 20px;">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-circle-xmark icon-purple" style="margin-right: 8px;"></i>
                    Faltas que puedes justificar
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($faltasJustificables as $f): ?>
                    <a class="btn-reset" style="padding: 8px 12px; font-size: 0.82rem;"
                       href="justificar.php?clase_id=<?php echo (int)$f['clase_id']; ?>&fecha=<?php echo urlencode($f['fecha']); ?>">
                        <?php echo htmlspecialchars($f['nombre_materia']); ?> ·
                        <?php echo date('d/m/Y', strtotime($f['fecha'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel-card" style="margin-bottom: 20px;">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-file-medical icon-blue" style="margin-right: 8px;"></i>
                Nuevo justificante
            </div>
        </div>

        <form action="justificar.php" method="POST" enctype="multipart/form-data">
            <div class="filter-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="clase_id">MATERIA</label>
                    <select id="clase_id" name="clase_id" class="form-select" required>
                        <option value="">Selecciona la materia...</option>
                        <?php foreach ($misClases as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo $claseForm === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre_materia']); ?> —
                                <?php echo ucfirst($c['dia_semana']); ?>
                                <?php echo date('h:i A', strtotime($c['hora_inicio'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha">DÍA DE LA FALTA</label>
                    <input type="date" id="fecha" name="fecha" class="form-date" required
                           max="<?php echo fechaHoy(); ?>" value="<?php echo htmlspecialchars($fechaForm); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="motivo">MOTIVO</label>
                <textarea id="motivo" name="motivo" class="form-select" rows="4" maxlength="1000" required
                          style="height: auto; resize: vertical;"
                          placeholder="Ej: Tuve cita médica por una infección y me dieron reposo."><?php echo htmlspecialchars($motivoForm); ?></textarea>
            </div>

            <div class="form-group">
                <label for="archivo">FOTO O COMPROBANTE (opcional · JPG, PNG, WEBP o PDF · máx. 5 MB)</label>
                <input type="file" id="archivo" name="archivo" class="form-select"
                       accept="image/jpeg,image/png,image/webp,image/gif,application/pdf">
            </div>

            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-paper-plane"></i> Enviar justificante
            </button>
        </form>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-clock-rotate-left icon-blue" style="margin-right: 8px;"></i>
                Mis justificantes
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Día de la falta</th>
                        <th>Motivo</th>
                        <th>Comprobante</th>
                        <th>Respuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($misJustificantes)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px 10px;">
                                Aún no has enviado ningún justificante.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($misJustificantes as $j): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($j['nombre_materia']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($j['fecha'])); ?></td>
                                <td style="max-width: 280px; font-size: 0.85rem;"><?php echo nl2br(htmlspecialchars($j['motivo'])); ?></td>
                                <td>
                                    <?php if ($j['archivo']): ?>
                                        <a href="ver_justificante.php?id=<?php echo (int)$j['id']; ?>" target="_blank" rel="noopener" class="btn-action" title="Ver archivo">
                                            <i class="fa-solid fa-paperclip"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($j['estado'] === 'pendiente'): ?>
                                        <span class="badge badge-retardo">En revisión</span>
                                    <?php elseif ($j['estado'] === 'aceptado'): ?>
                                        <span class="badge badge-presente">Aceptado</span>
                                    <?php else: ?>
                                        <span class="badge badge-falta">Rechazado</span>
                                    <?php endif; ?>
                                    <?php if ($j['comentario_docente']): ?>
                                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; max-width: 240px;">
                                            <?php echo htmlspecialchars($j['resolvio_nombre'] ?: 'Docente'); ?>:
                                            “<?php echo htmlspecialchars($j['comentario_docente']); ?>”
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($j['estado'] === 'rechazado'): ?>
                                        <div style="margin-top: 4px;">
                                            <a href="justificar.php?clase_id=<?php echo (int)$j['clase_id']; ?>&fecha=<?php echo urlencode($j['fecha']); ?>"
                                               style="font-size: 0.78rem;">Volver a enviar</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php
require_once __DIR__ . '/footer.php';
?>
