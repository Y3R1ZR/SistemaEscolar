<?php
// guardar_escala.php
header('Content-Type: application/json; charset=utf-8');
include_once "conexion.php";

$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
$pt = isset($_POST['por_tareas']) ? intval($_POST['por_tareas']) : 0;
$pa = isset($_POST['por_asist']) ? intval($_POST['por_asist']) : 0;
$pe = isset($_POST['por_exams']) ? intval($_POST['por_exams']) : 0;

if ($id_grupo <= 0) { echo json_encode(['error'=>'Grupo inválido']); exit; }
if (($pt + $pa + $pe) !== 100) { echo json_encode(['error'=>'Los porcentajes deben sumar 100']); exit; }

// upsert
$stmt = $conn->prepare("SELECT id_config FROM configuracion_porcentajes WHERE id_grupo = ?");
$stmt->bind_param("i",$id_grupo);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res) {
    $upd = $conn->prepare("UPDATE configuracion_porcentajes SET porcentaje_tareas=?, porcentaje_asistencias=?, porcentaje_examenes=?, fecha_modificacion = NOW() WHERE id_grupo = ?");
    $upd->bind_param("iiii",$pt,$pa,$pe,$id_grupo);
    $ok = $upd->execute();
    $upd->close();
} else {
    $ins = $conn->prepare("INSERT INTO configuracion_porcentajes (id_grupo, porcentaje_tareas, porcentaje_asistencias, porcentaje_examenes) VALUES (?, ?, ?, ?)");
    $ins->bind_param("iiii",$id_grupo,$pt,$pa,$pe);
    $ok = $ins->execute();
    $ins->close();
}

if ($ok) echo json_encode(['success'=>true,'message'=>'Escala guardada']);
else echo json_encode(['error'=>'Error al guardar escala']);
