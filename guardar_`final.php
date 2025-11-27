<?php
// guardar_final.php
header('Content-Type: application/json; charset=utf-8');
include_once "conexion.php";

$mat = isset($_POST['matricula']) ? $conn->real_escape_string($_POST['matricula']) : '';
$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
$prefinal = isset($_POST['prefinal']) && $_POST['prefinal'] !== '' ? floatval($_POST['prefinal']) : null;
$final    = isset($_POST['final']) && $_POST['final'] !== '' ? floatval($_POST['final']) : null;

if (!$mat || $id_grupo <= 0) { echo json_encode(['error'=>'Datos inválidos']); exit; }

// existe?
$stmt = $conn->prepare("SELECT id_final FROM calificaciones_finales WHERE matricula = ? AND id_grupo = ?");
$stmt->bind_param("si",$mat,$id_grupo);
$stmt->execute();
$ex = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($ex) {
    $sets = []; $types = ''; $params = [];
    if ($prefinal !== null) { $sets[] = "calificacion_prefinal = ?"; $types .= 'd'; $params[] = $prefinal; }
    if ($final !== null)    { $sets[] = "calificacion_final = ?";   $types .= 'd'; $params[] = $final; }
    if (count($sets) === 0) { echo json_encode(['error'=>'Nada para actualizar']); exit; }
    $sql = "UPDATE calificaciones_finales SET ".implode(',', $sets)." WHERE matricula = ? AND id_grupo = ?";
    $stmt = $conn->prepare($sql);
    // bind dinámico
    $types .= 'si'; // matricula (s), id_grupo (i)
    $params[] = $mat;
    $params[] = $id_grupo;
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=> $ok]);
    exit;
} else {
    // insert
    $p = $prefinal !== null ? $prefinal : 0.00;
    $f = $final !== null ? $final : 0.00;
    $ins = $conn->prepare("INSERT INTO calificaciones_finales (matricula, id_grupo, calificacion_prefinal, calificacion_final) VALUES (?, ?, ?, ?)");
    $ins->bind_param("sidd", $mat, $id_grupo, $p, $f);
    $ok = $ins->execute();
    $ins->close();
    echo json_encode(['success'=>$ok]);
    exit;
}
