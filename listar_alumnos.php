<?php
include_once("conexion.php");
$stmt = $conn->prepare("SELECT matricula, nombre, apellido_paterno, apellido_materno, correo FROM alumnos ORDER BY nombre ASC, apellido_paterno ASC");
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Alumnos</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">AL</div>
      <div><h1>Alumnos</h1><small style="color:#64748b">Agregar · Editar · Eliminar · Orden alfabético</small></div>
    </div>
    <div class="nav">
      <a href="index.php"> 🏠 Inicio</a>
      <a href="agregar_alumno.php" class="button-ghost">&#9989; Agregar alumno</a>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <table class="table" aria-label="Lista de alumnos">
        <thead>
          <tr>
            <th>Matrícula</th><th>Nombre</th><th>Apellidos</th><th>Correo</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['matricula']) ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['apellido_paterno'] . ' ' . $row['apellido_materno']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
     
            <td class="actions">
              <a class="edit" href="editar_alumno.php?matricula=<?= urlencode($row['matricula']) ?>">✏️Editar</a> <br>
              <a class="delete" href="eliminar_alumno.php?matricula=<?= urlencode($row['matricula']) ?>" onclick="return confirm('Eliminar alumno <?= htmlspecialchars($row['nombre']) ?>?')">🗑️Eliminar</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <aside class="card">
      <div class="stat-item">
        <h3>Total de alumnos</h3> <br>
        <?php
          $c = $conn->query("SELECT COUNT(*) AS total FROM alumnos")->fetch_assoc();
          echo "<p>{$c['total']}</p>";
        ?>
      </div>
      <div style="margin-top:12px">
        <a class="button-ghost" href="index.php">&#8617; Volver</a>
      </div>
    </aside>
  </div>
</div>
</body>
</html>
<?php $stmt->close(); ?>
