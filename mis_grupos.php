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
$cursos = [];
$estadisticas = [];   // por clave de curso
$inhabiles = [];      // por clave de curso

try {
    $cursos = cursosDeDocente($pdo, $esDocente ? $user['matricula'] : null);

    foreach ($cursos as $curso) {
        $estadisticas[$curso['clave']] = estadisticasCurso($pdo, $curso['clase_ids']);

        $in = implode(',', array_map('intval', $curso['clase_ids']));
        $inhabiles[$curso['clave']] = $pdo->query("
            SELECT di.fecha, di.motivo, c.dia_semana
            FROM dias_inhabiles di JOIN clases c ON c.id = di.clase_id
            WHERE di.clase_id IN ($in)
            ORDER BY di.fecha DESC
        ")->fetchAll();
    }
} catch (PDOException $e) {
    $error = mensajeErrorBD($e, 'cargar tus grupos', $user['rol'] ?? null, 'mis_grupos.php');
}

require_once __DIR__ . '/header.php';
?>

<div class="top-bar">
    <div class="page-title">
        <h2><?php echo $esDocente ? 'Mis grupos' : 'Grupos y alumnos'; ?></h2>
        <p>
            Horario de cada grupo y lista completa de alumnos con su porcentaje de asistencia.
            Con menos de <?php echo PORCENTAJE_MINIMO_ASISTENCIA; ?>% el alumno queda en riesgo de reprobar.
        </p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if (empty($cursos) && empty($error)): ?>
    <div class="panel-card">
        <div style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
            No tienes grupos asignados. Pide al administrador que te registre tus clases.
        </div>
    </div>
<?php endif; ?>

<?php foreach ($cursos as $curso): ?>
    <?php
        $est = $estadisticas[$curso['clave']];
        $alumnos = $est['alumnos'];
        $totalAlumnos = count($alumnos);
    ?>

    <div class="panel-card" style="margin-bottom: 28px;">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-people-group icon-purple" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($curso['nombre_materia']); ?>
                <?php echo $curso['grupo'] ? ' — Grupo ' . htmlspecialchars($curso['grupo']) : ''; ?>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">
                <?php echo htmlspecialchars($curso['carrera'] ?: 'Sin carrera'); ?>
                <?php echo $curso['semestre'] ? ' · ' . (int)$curso['semestre'] . '° semestre' : ''; ?>
                <?php echo !$esDocente ? ' · ' . htmlspecialchars($curso['docente_nombre']) : ''; ?>
            </span>
        </div>

        <!-- HORARIO: cada día enlaza con la lista de ese día -->
        <div style="margin-bottom: 20px;">
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">HORARIO · toca un día para ver su lista de alumnos</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($curso['clases'] as $c): ?>
                    <?php
                        $esHoy = (obtenerDiaSemanaEspanol() === $c['dia_semana']);
                        $fechaLista = ultimaFechaDeDia($c['dia_semana']);
                    ?>
                    <a href="pase_lista.php?clase_id=<?php echo (int)$c['id']; ?>&fecha=<?php echo $fechaLista; ?>"
                       class="btn-reset"
                       style="padding: 8px 12px; font-size: 0.82rem; text-decoration: none; <?php echo $esHoy ? 'border-color: #22c55e;' : ''; ?>">
                        <strong><?php echo ucfirst($c['dia_semana']); ?></strong>
                        <?php echo date('h:i A', strtotime($c['hora_inicio'])); ?> –
                        <?php echo date('h:i A', strtotime($c['hora_fin'])); ?>
                        · <?php echo htmlspecialchars($c['aula'] ?: 'S/A'); ?>
                        <?php echo $esHoy ? '<span style="color:#22c55e; font-size:0.7rem;"> HOY</span>' : ''; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ESTADÍSTICAS GENERALES -->
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-value"><?php echo $totalAlumnos; ?></span>
                    <span class="stat-label">Alumnos inscritos</span>
                </div>
                <div class="stat-icon stat-blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-value"><?php echo $est['sesiones']; ?></span>
                    <span class="stat-label">Clases impartidas</span>
                </div>
                <div class="stat-icon stat-purple"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-value"><?php echo formatoPorcentaje($est['promedio']); ?></span>
                    <span class="stat-label">Asistencia promedio</span>
                </div>
                <div class="stat-icon stat-green"><i class="fa-solid fa-percent"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-value" style="<?php echo $est['bajo_minimo'] > 0 ? 'color: var(--danger);' : ''; ?>">
                        <?php echo $est['bajo_minimo']; ?>
                    </span>
                    <span class="stat-label">Con menos de <?php echo PORCENTAJE_MINIMO_ASISTENCIA; ?>%</span>
                </div>
                <div class="stat-icon stat-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <!-- LISTA COMPLETA DE ALUMNOS -->
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Presentes</th>
                        <th>Retardos</th>
                        <th>Justificadas</th>
                        <th>Faltas</th>
                        <th>% asistencia</th>
                        <th>Situación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alumnos)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px 10px;">
                                No hay alumnos inscritos en este grupo.
                                <?php if (!$esDocente): ?>
                                    <a href="inscripciones.php?clase_id=<?php echo (int)$curso['clase_ids'][0]; ?>">Inscribirlos ahora</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alumnos as $a): ?>
                            <?php
                                $pct = $a['porcentaje'];
                                $color = $pct === null ? '#94a3b8' : ($a['en_riesgo'] ? '#ef4444' : ($pct < PORCENTAJE_MINIMO_ASISTENCIA + 10 ? '#f59e0b' : '#22c55e'));
                            ?>
                            <tr <?php echo $a['en_riesgo'] ? 'style="background: rgba(239,68,68,0.06);"' : ''; ?>>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($a['nombre']); ?></strong></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($a['matricula']); ?>
                                        <?php echo $a['estado_usuario'] === 'inactivo' ? ' · <span style="color: var(--danger);">baja</span>' : ''; ?>
                                    </div>
                                </td>
                                <td><?php echo $a['presentes']; ?></td>
                                <td><?php echo $a['retardos']; ?></td>
                                <td><?php echo $a['justificados']; ?></td>
                                <td>
                                    <strong style="color: <?php echo $a['faltas'] > 0 ? 'var(--danger)' : 'inherit'; ?>;">
                                        <?php echo $a['faltas']; ?>
                                    </strong>
                                </td>
                                <td style="min-width: 130px;">
                                    <strong style="color: <?php echo $color; ?>;"><?php echo formatoPorcentaje($pct); ?></strong>
                                    <div style="height: 6px; border-radius: 3px; background: rgba(255,255,255,0.08); margin-top: 4px; overflow: hidden;">
                                        <div style="height: 100%; width: <?php echo $pct === null ? 0 : min(100, round($pct)); ?>%; background: <?php echo $color; ?>;"></div>
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                        <?php echo $a['asistidas']; ?>/<?php echo $est['sesiones']; ?> clases
                                    </div>
                                </td>
                                <td>
                                    <?php if ($pct === null): ?>
                                        <span class="badge badge-neutro">Sin clases aún</span>
                                    <?php elseif ($a['en_riesgo']): ?>
                                        <span class="badge badge-falta">En riesgo de reprobar</span>
                                    <?php else: ?>
                                        <span class="badge badge-presente">Cumple</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($inhabiles[$curso['clave']])): ?>
            <div style="margin-top: 18px;">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">
                    <i class="fa-solid fa-calendar-xmark"></i> DÍAS INHÁBILES (no cuentan para el porcentaje)
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($inhabiles[$curso['clave']] as $d): ?>
                        <span class="badge badge-neutro">
                            <?php echo date('d/m/Y', strtotime($d['fecha'])); ?>
                            <?php echo $d['motivo'] ? '· ' . htmlspecialchars($d['motivo']) : ''; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/footer.php';
?>
