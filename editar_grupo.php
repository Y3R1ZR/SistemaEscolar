<?php
include_once("conexion.php");
if (!isset($_GET['id'])) { header("Location: listar_grupos.php"); exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id_grupo,clave_grupo,materia,periodo,semestre,carrera FROM grupos WHERE id_grupo=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
$g = $res->fetch_assoc();
$stmt->close();
if (!$g) { echo "Grupo no encontrado."; exit; }

$msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $clave = trim($_POST['clave_grupo']);
    $materia = trim($_POST['materia']);
    $periodo = trim($_POST['periodo']);
    $semestre = trim($_POST['semestre']);
    $carrera = trim($_POST['carrera']);
    $u = $conn->prepare("UPDATE grupos SET clave_grupo=?, materia=?, periodo=?, semestre=?, carrera=? WHERE id_grupo=?");
    $u->bind_param("sssssi",$clave,$materia,$periodo,$semestre,$carrera,$id);
    if ($u->execute()) $msg="Grupo actualizado.";
    else $msg="Error: ".$u->error;
    $u->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Editar Grupo</title><link rel="stylesheet" href=" estilo.css"></head>
<body>
<div class="container">
  <div class="header"><div class="brand"><div class="logo">EG</div><div><h1>Editar Grupo</h1></div></div><div class="nav"><a href="listar_grupos.php">Volver</a></div></div>
  <div class="grid">
    <div class="card">
      <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
      <form method="post" class="form-grid">
        <div><label>Clave</label><input type="text" name="clave_grupo" value="<?=htmlspecialchars($g['clave_grupo'])?>" required></div>
        <div><label>Materia</label><input type="text" name="materia" value="<?=htmlspecialchars($g['materia'])?>" required></div>
        <div><label>Periodo</label><input type="text" name="periodo" value="<?=htmlspecialchars($g['periodo'])?>"></div>
        <div><label>Semestre</label><input type="text" name="semestre" value="<?=htmlspecialchars($g['semestre'])?>"></div>
        <div><label>Carrera</label><input type="text" name="carrera" value="<?=htmlspecialchars($g['carrera'])?>"></div>
        <div style="grid-column:span 2; display:flex; gap:8px; justify-content:flex-end">
          <button class="primary" type="submit">Guardar</button>
          <a class="button-ghost" href="listar_grupos.php">Cancelar</a>
        </div>
      </form>
    </div>

    <aside class="card"><div class="stat-item"><h3>Clave</h3><p>No cambies la clave si ya tiene alumnos asignados a ese grupo (puedes, pero se recomienda revisar asignaciones).</p></div></aside>
  </div>
</div>
</body>
</html>
