<?php
// calcular_prefinal.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
if ($id_grupo <= 0) { echo json_encode(['error'=>'Grupo inválido']); exit; }

// total_clases
$stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM clases WHERE id_grupo = ?");
$stmt->bind_param('i', $id_grupo);
$stmt->execute(); $total_clases = intval($stmt->get_result()->fetch_assoc()['total']); $stmt->close();
if ($total_clases === 0) {
  $q = $mysqli->prepare("SELECT COUNT(DISTINCT fecha) AS total FROM asistencias WHERE id_grupo = ?");
  $q->bind_param('i', $id_grupo);
  $q->execute(); $total_clases = intval($q->get_result()->fetch_assoc()['total']); $q->close();
}

// porcentajes
$q = $mysqli->prepare("SELECT porcentaje_tareas, porcentaje_asistencias, porcentaje_examenes FROM configuracion_porcentajes WHERE id_grupo = ?");
$q->bind_param('i', $id_grupo); $q->execute(); $cfg = $q->get_result()->fetch_assoc(); $q->close();
$pt = $cfg ? intval($cfg['porcentaje_tareas']) : 0;
$pa = $cfg ? intval($cfg['porcentaje_asistencias']) : 0;
$pe = $cfg ? intval($cfg['porcentaje_examenes']) : 0;

// alumnos
$s = $mysqli->prepare("SELECT a.matricula FROM alumno_grupo ag JOIN alumnos a ON ag.matricula = a.matricula WHERE ag.id_grupo = ?");
$s->bind_param('i', $id_grupo); $s->execute(); $res = $s->get_result(); $mats = $res->fetch_all(MYSQLI_ASSOC); $s->close();

foreach ($mats as $row) {
  $mat = $row['matricula'];
  // prom tareas
  $p = $mysqli->prepare("SELECT IFNULL(AVG(calificacion),0) AS prom_tareas FROM tareas WHERE matricula = ? AND id_grupo = ?");
  $p->bind_param('si', $mat, $id_grupo); $p->execute(); $prom_tareas = floatval($p->get_result()->fetch_assoc()['prom_tareas']); $p->close();
  // faltas
  $f = $mysqli->prepare("SELECT COUNT(*) AS faltas FROM asistencias WHERE matricula = ? AND id_grupo = ? AND estado = 'Faltó'");
  $f->bind_param('si', $mat, $id_grupo); $f->execute(); $faltas = intval($f->get_result()->fetch_assoc()['faltas']); $f->close();
  // prom exams
  $e = $mysqli->prepare("SELECT IFNULL(AVG(calificacion),0) AS prom_exams FROM examenes WHERE matricula = ? AND id_grupo = ?");
  $e->bind_param('si', $mat, $id_grupo); $e->execute(); $prom_exams = floatval($e->get_result()->fetch_assoc()['prom_exams']); $e->close();

  // porcentaje asistencia 0-100
  if ($total_clases > 0) {
    $asistidas = max(0, $total_clases - $faltas);
    $por_asistencia = ($asistidas / $total_clases) * 100.0;
  } else $por_asistencia = 0.0;
  // convertir a escala 0-10
  $calif_asist_10 = ($por_asistencia / 100.0) * 10.0;

  if (($pt + $pa + $pe) > 0) {
    $prefinal = ($prom_tareas * ($pt/100.0)) + ($calif_asist_10 * ($pa/100.0)) + ($prom_exams * ($pe/100.0));
  } else $prefinal = 0.0;

  // truncar sin redondeo a 2 decimales
  $prefinal_trunc = number_format(floor($prefinal * 100) / 100, 2, '.', '');

  // upsert en calificaciones_finales (solo prefinal)
  $check = $mysqli->prepare("SELECT id_final FROM calificaciones_finales WHERE matricula = ? AND id_grupo = ?");
  $check->bind_param('si', $mat, $id_grupo); $check->execute(); $ex = $check->get_result()->fetch_assoc(); $check->close();

  if ($ex) {
    $upd = $mysqli->prepare("UPDATE calificaciones_finales SET calificacion_prefinal = ? WHERE matricula = ? AND id_grupo = ?");
    $upd->bind_param('dsi', $prefinal_trunc, $mat, $id_grupo); $upd->execute(); $upd->close();
  } else {
    $ins = $mysqli->prepare("INSERT INTO calificaciones_finales (matricula, id_grupo, calificacion_prefinal) VALUES (?, ?, ?)");
    $ins->bind_param('sid', $mat, $id_grupo, $prefinal_trunc); $ins->execute(); $ins->close();
  }
}

echo json_encode(['success'=>true,'message'=>'Prefinales recalculadas']);
