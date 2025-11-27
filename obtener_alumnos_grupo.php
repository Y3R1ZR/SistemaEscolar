<?php
// obtener_alumnos_grupo.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
if ($id_grupo <= 0) { echo json_encode(['error'=>'Grupo inválido']); exit; }

$stmt = $mysqli->prepare("
  SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
  FROM alumno_grupo ag
  JOIN alumnos a ON ag.matricula = a.matricula
  WHERE ag.id_grupo = ?
  ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre
");
$stmt->bind_param('i', $id_grupo);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
$stmt->close();
echo json_encode(['alumnos'=>$out], JSON_UNESCAPED_UNICODE);
