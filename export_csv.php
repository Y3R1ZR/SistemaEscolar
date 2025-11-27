<?php
// export_csv.php
include_once "conexion.php";

$id_grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
if ($id_grupo <= 0) { header('Location: lista_grupo.php'); exit; }

// Cabeceras CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=lista_grupo_'.$id_grupo.'.csv');
$output = fopen('php://output', 'w');

// Cabecera
fputcsv($output, ['#','Matricula','Nombre','Faltas','Tareas entregadas','Prom Tareas','Prom Examen','Prefinal (calc)','Prefinal guardada','Final guardada']);

// obtener alumnos
$stmt = $conn->prepare("
    SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno
    FROM alumno_grupo ag
    JOIN alumnos a ON a.matricula = ag.matricula
    WHERE ag.id_grupo = ?
    ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre ASC
");
$stmt->bind_param("i",$id_grupo);
$stmt->execute();
$res = $stmt->get_result();
$i=1;

// precalculos (faltas,tareas,exams) - reusar lógica similar a lista_grupo.php
// faltas
$faltas_map=[];
if ($conn->query("SHOW TABLES LIKE 'asistencias'")->num_rows>0) {
    $st = $conn->prepare("SELECT matricula, SUM(CASE WHEN estado='Faltó' THEN 1 ELSE 0 END) AS faltas FROM asistencias WHERE id_grupo = ? GROUP BY matricula");
    $st->bind_param("i",$id_grupo); $st->execute(); $r=$st->get_result();
    while ($row=$r->fetch_assoc()) $faltas_map[$row['matricula']] = intval($row['faltas']);
    $st->close();
}
// tareas
$tmap=[]; $tprom=[];
if ($conn->query("SHOW TABLES LIKE 'tareas'")->num_rows>0) {
    $st = $conn->prepare("SELECT matricula, COUNT(*) AS ent, IFNULL(AVG(calificacion),0) AS prom FROM tareas WHERE id_grupo=? GROUP BY matricula");
    $st->bind_param("i",$id_grupo); $st->execute(); $r=$st->get_result();
    while ($row=$r->fetch_assoc()) $tmap[$row['matricula']] = intval($row['ent']);
    $st->close();
}
// exams
$emap=[];
if ($conn->query("SHOW TABLES LIKE 'examenes'")->num_rows>0) {
    $st = $conn->prepare("SELECT matricula, IFNULL(AVG(calificacion),0) AS prom FROM examenes WHERE id_grupo=? GROUP BY matricula");
    $st->bind_param("i",$id_grupo); $st->execute(); $r=$st->get_result();
    while ($row=$r->fetch_assoc()) $emap[$row['matricula']] = floatval($row['prom']);
    $st->close();
}
// pref/final guardados
$saved=[];
if ($conn->query("SHOW TABLES LIKE 'calificaciones_finales'")->num_rows>0) {
    $st = $conn->prepare("SELECT matricula, calificacion_prefinal, calificacion_final FROM calificaciones_finales WHERE id_grupo=?");
    $st->bind_param("i",$id_grupo); $st->execute(); $r=$st->get_result();
    while ($row=$r->fetch_assoc()) $saved[$row['matricula']] = $row;
    $st->close();
}

while ($a = $res->fetch_assoc()) {
    $mat = $a['matricula'];
    $falt = $faltas_map[$mat] ?? 0;
    $t_ent = $tmap[$mat] ?? 0;
    $t_prom = $tprom[$mat] ?? 0;
    $e_prom = $emap[$mat] ?? 0;
    $s_pref = isset($saved[$mat]['calificacion_prefinal']) ? $saved[$mat]['calificacion_prefinal'] : '';
    $s_fin = isset($saved[$mat]['calificacion_final']) ? $saved[$mat]['calificacion_final'] : '';
    fputcsv($output, [$i++, $mat, $a['apellido_paterno'].' '.$a['apellido_materno'].' '.$a['nombre'], $falt, $t_ent, number_format($t_prom,2,'.',''), number_format($e_prom,2,'.',''), $s_pref, $s_pref, $s_fin]);
}
fclose($output);
exit;
