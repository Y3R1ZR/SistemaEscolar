<?php
// obtener_asistencias.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
if ($id_grupo <= 0) { echo json_encode(['error'=>'Grupo inválido']); exit; }

// Total de clases: contamos en la tabla 'clases' si existe, sino contamos dias únicos en 'asistencias'
$res = $mysqli->prepare("SELECT COUNT(*) AS total FROM clases WHERE id_grupo = ?");
$res->bind_param('i', $id_grupo);
$res->execute();
$r = $res->get_result()->fetch_assoc();
$total_clases = intval($r['total']);
$res->close();

if ($total_clases === 0) {
    // fallback: conteo de fechas únicas en asistencias
    $q = $mysqli->prepare("SELECT COUNT(DISTINCT fecha) AS total FROM asistencias WHERE id_grupo = ?");
    $q->bind_param('i', $id_grupo);
    $q->execute();
    $r2 = $q->get_result()->fetch_assoc();
    $total_clases = intval($r2['total']);
    $q->close();
}

echo json_encode(['total_clases'=>$total_clases], JSON_UNESCAPED_UNICODE);
