<?php
include_once("conexion.php");
$msg = "";
if (!isset($_GET['matricula'])) {
    header("Location: listar_alumnos.php");
    exit;
}
$mat = $_GET['matricula'];
// obtener datos
$stmt = $conn->prepare("SELECT matricula,nombre,apellido_paterno,apellido_materno,correo FROM alumnos WHERE matricula=?");
$stmt->bind_param("s",$mat);
$stmt->execute();
$res = $stmt->get_result();
$al = $res->fetch_assoc();
$stmt->close();

if (!$al) { echo "Alumno no encontrado."; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $ap = trim($_POST['apellido_paterno']);
    $am = trim($_POST['apellido_materno']);
    $correo = trim($_POST['correo']);
  

    $up = $conn->prepare("UPDATE alumnos SET nombre=?, apellido_paterno=?, apellido_materno=?, correo=? WHERE matricula=?");
    $up->bind_param("sssss",$nombre,$ap,$am,$correo,$mat);
    if ($up->execute()) {
        $msg = "Alumno actualizado correctamente.";
        // refrescar datos
        $al['nombre']=$nombre; $al['apellido_paterno']=$ap; $al['apellido_materno']=$am; $al['correo']=$correo;
    } else {
        $msg = "Error: " . $up->error;
    }
    $up->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar Alumno</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand"><div class="logo">ED</div><div><h1>Editar Alumno</h1></div></div>
    <div class="nav"><a href="listar_alumnos.php">Volver</a></div>
  </div>

  <div class="grid">
    <div class="card">
      <?php if($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

      <form method="post" class="form-grid">
        <div><label>Matrícula</label><input type="text" value="<?= htmlspecialchars($al['matricula']) ?>" disabled></div>
        <div><label>Nombre</label><input type="text" name="nombre" value="<?= htmlspecialchars($al['nombre']) ?>" required></div>
        <div><label>Apellido paterno</label><input type="text" name="apellido_paterno" value="<?= htmlspecialchars($al['apellido_paterno']) ?>" required></div>
        <div><label>Apellido materno</label><input type="text" name="apellido_materno" value="<?= htmlspecialchars($al['apellido_materno']) ?>"></div>
        <div><label>Correo</label><input type="email" name="correo" value="<?= htmlspecialchars($al['correo']) ?>"></div>
        
        <div style="grid-column:span 2; display:flex; gap:8px; justify-content:flex-end">
          <button class="primary" type="submit">&#128190;Guardar cambios</button>
          <a class="button-ghost" href="listar_alumnos.php">&#128683;Cancelar</a>
        </div>
      </form>
    </div>

    <aside class="card">
      <div class="stat-item">
        <h3>Ayuda</h3>
        <p>Si deseas cambiar la matrícula (ID) hay que hacerlo manualmente desde SQL — no se modifica aquí.</p>
      </div>
    </aside>
  </div>
</div>
</body>
</html>
