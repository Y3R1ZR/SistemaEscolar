<?php
// obtener_calificaciones.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'fetch_pct') {
    $id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
    if ($id_grupo <= 0) { echo json_encode(['error'=>'Grupo inválido']); exit; }
    $stmt = $mysqli->prepare("SELECT porcentaje_tareas, porcentaje_asistencias, porcentaje_examenes FROM configuracion_porcentajes WHERE id_grupo = ?");
    $stmt->bind_param('i', $id_grupo);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) echo json_encode(['success'=>true] + $res);
    else echo json_encode(['error'=>'No hay configuración']);
    exit;
}

if ($action === 'alumno') {
    $id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
    $matricula = isset($_POST['matricula']) ? $mysqli->real_escape_string($_POST['matricula']) : '';
    if ($id_grupo <= 0 || !$matricula) { echo json_encode(['error'=>'Datos inválidos']); exit; }

    // total tareas y promedio tareas
    $s = $mysqli->prepare("SELECT COUNT(*) AS total_tareas, IFNULL(AVG(calificacion),0) AS prom_tareas FROM tareas WHERE matricula = ? AND id_grupo = ?");
    $s->bind_param('si', $matricula, $id_grupo);
    $s->execute(); $r = $s->get_result()->fetch_assoc(); $s->close();
    $total_tareas = intval($r['total_tareas']);
    $prom_tareas = floatval($r['prom_tareas']);

    // faltas (asistencias estado = 'Faltó')
    $s = $mysqli->prepare("SELECT COUNT(*) AS faltas FROM asistencias WHERE matricula = ? AND id_grupo = ? AND estado = 'Faltó'");
    $s->bind_param('si', $matricula, $id_grupo);
    $s->execute(); $r = $s->get_result()->fetch_assoc(); $s->close();
    $faltas = intval($r['faltas']);

    // total_clases (mismo método que obtener_asistencias.php)
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM clases WHERE id_grupo = ?");
    $stmt->bind_param('i', $id_grupo);
    $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $total_clases = intval($r['total']); $stmt->close();
    if ($total_clases === 0) {
        $q = $mysqli->prepare("SELECT COUNT(DISTINCT fecha) AS total FROM asistencias WHERE id_grupo = ?");
        $q->bind_param('i', $id_grupo);
        $q->execute(); $r2 = $q->get_result()->fetch_assoc(); $total_clases = intval($r2['total']); $q->close();
    }

    // porcentaje de asistencia (0-100)
    if ($total_clases > 0) {
        $asistidas = max(0, $total_clases - $faltas);
        $por_asistencia = ($asistidas / $total_clases) * 100.0;
    } else $por_asistencia = 0.0;

    // promedio examenes
    $s = $mysqli->prepare("SELECT IFNULL(AVG(calificacion),0) AS prom_exams FROM examenes WHERE matricula = ? AND id_grupo = ?");
    $s->bind_param('si', $matricula, $id_grupo);
    $s->execute(); $row = $s->get_result()->fetch_assoc(); $prom_exams = floatval($row['prom_exams']); $s->close();

    // obtener porcentajes configurados
    $s = $mysqli->prepare("SELECT porcentaje_tareas, porcentaje_asistencias, porcentaje_examenes FROM configuracion_porcentajes WHERE id_grupo = ?");
    $s->bind_param('i', $id_grupo);
    $s->execute(); $cfg = $s->get_result()->fetch_assoc(); $s->close();
    $pt = $cfg ? intval($cfg['porcentaje_tareas']) : 0;
    $pa = $cfg ? intval($cfg['porcentaje_asistencias']) : 0;
    $pe = $cfg ? intval($cfg['porcentaje_examenes']) : 0;

    // convertir %asistencia (0-100) a escala 0-10
    $calif_asist_10 = ($por_asistencia / 100.0) * 10.0;

    // calcular prefinal (usando prom_tareas y prom_exams que asumimos en escala 0-10)
    if (($pt + $pa + $pe) > 0) {
        $prefinal_calc = ($prom_tareas * ($pt/100.0)) + ($calif_asist_10 * ($pa/100.0)) + ($prom_exams * ($pe/100.0));
    } else $prefinal_calc = 0.0;

    // truncar a 2 decimales sin redondeo
    $prefinal_calc_trunc = number_format(floor($prefinal_calc * 100) / 100, 2, '.', '');

    // obtener guardados
    $s = $mysqli->prepare("SELECT calificacion_prefinal, calificacion_final FROM calificaciones_finales WHERE matricula = ? AND id_grupo = ?");
    $s->bind_param('si', $matricula, $id_grupo);
    $s->execute(); $saved = $s->get_result()->fetch_assoc(); $s->close();

    $prefinal_saved = $saved && $saved['calificacion_prefinal'] !== null ? number_format(floatval($saved['calificacion_prefinal']), 2, '.', '') : null;
    $final_saved = $saved && $saved['calificacion_final'] !== null ? number_format(floatval($saved['calificacion_final']), 2, '.', '') : null;

    echo json_encode([
      'tareas_total'=>$total_tareas,
      'faltas'=>$faltas,
      'total_clases'=>$total_clases,
      'porcentaje_asistencia'=>number_format($por_asistencia,2,'.',''),
      'prom_exams'=>number_format($prom_exams,2,'.',''),
      'prefinal_calc'=>$prefinal_calc_trunc,
      'prefinal_saved'=>$prefinal_saved,
      'final_saved'=>$final_saved
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['error'=>'Acción inválida']);
