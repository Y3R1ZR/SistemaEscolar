<?php
include_once("conexion.php");
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula']);
    $nombre = trim($_POST['nombre']);
    $ap = trim($_POST['apellido_paterno']);
    $am = trim($_POST['apellido_materno']);
    $correo = trim($_POST['correo']);

    // Insert seguro
    $stmt = $conn->prepare("INSERT INTO alumnos (matricula,nombre,apellido_paterno,apellido_materno,correo) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $matricula,$nombre,$ap,$am,$correo);
    if ($stmt->execute()) {
        $msg = "Alumno agregado correctamente.";
    } else {
        $msg = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agregar Alumno</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand"><div class="logo">+A</div><div><h1>Agregar Alumno</h1></div></div>
    <div class="nav"><a href="listar_alumnos.php"> &#8617; Volver</a></div>
  </div>

  <div class="grid">
    <div class="card">
      <?php if($msg): ?><div class="success" style="margin-bottom:12px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

      <form method="post" class="form-grid">
        <div>
          <label>Matrícula</label>
          <input type="text" name="matricula" required>
        </div>
        <div>
          <label>Nombre</label>
          <input type="text" name="nombre" required>
        </div>
        <div>
          <label>Apellido paterno</label>
          <input type="text" name="apellido_paterno" required>
        </div>
        <div>
          <label>Apellido materno</label>
          <input type="text" name="apellido_materno">
        </div>
        <div>
          <label>Correo</label>
          <input type="email" name="correo">
        </div>
      

        <div style="grid-column:span 2; display:flex; gap:8px; justify-content:flex-end">
          <button type="submit" class="primary">Guardar</button>
          <a class="button-ghost" href="listar_alumnos.php">Cancelar</a>
        </div>
      </form>
    </div>

    <aside class="card">
      <div class="stat-item">
        <h3>Consejo</h3> <br>
        <p>La matrícula es única (clave primaria). Asegúrate de no duplicarla.</p>
      </div>
    </aside>
  </div>
</div>
</body>
</html>
