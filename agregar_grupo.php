<?php
include_once("conexion.php");
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = trim($_POST['clave_grupo']);
    $materia = trim($_POST['materia']);
    $periodo = trim($_POST['periodo']);
    $semestre = trim($_POST['semestre']);
    $carrera = trim($_POST['carrera']);

    $stmt = $conn->prepare("INSERT INTO grupos (clave_grupo,materia,periodo,semestre,carrera) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss",$clave,$materia,$periodo,$semestre,$carrera);
    if ($stmt->execute()) $msg = "Grupo creado correctamente.";
    else $msg = "Error: " . $stmt->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agregar Grupo</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="brand"><div class="logo">+G</div><div><h1>Agregar Grupo</h1></div></div><div class="nav"><a href="listar_grupos.php">Volver</a></div></div>

  <div class="grid">
    <div class="card">
      <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
      <form method="post" class="form-grid">
        <div><label>Clave de grupo</label><input type="text" name="clave_grupo" required></div>
        <div><label>Materia</label><input type="text" name="materia" required></div>
        <div><label>Periodo</label><input type="text" name="periodo"></div>
        <div><label>Semestre</label><input type="text" name="semestre"></div>
        <div><label>Carrera</label><input type="text" name="carrera"></div>
        <div style="grid-column:span 2; display:flex; gap:8px; justify-content:flex-end">
          <button class="primary" type="submit">Guardar</button>
          <a class="button-ghost" href="listar_grupos.php">Cancelar</a>
        </div>
      </form>
    </div>

    <aside class="card"><div class="stat-item"><h3>Nota</h3><p>Clave única por grupo.</p></div></aside>
  </div>
</div>
</body>
</html>
