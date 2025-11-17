<?php
include_once("conexion.php");
$stmt = $conn->prepare("SELECT id_grupo, clave_grupo, materia, periodo, semestre, carrera FROM grupos ORDER BY materia ASC, clave_grupo ASC");
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Grupos</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand"><div class="logo">GR</div><div><h1>Grupos</h1></div></div>
    <div class="nav"><a href="index.php">Inicio</a><a class="button-ghost" href="agregar_grupo.php">+ Nuevo grupo</a><a href="alumno_grupo.php">Asignar alumnos</a></div>    
  </div>

  <div class="grid">
    <div class="card">
      <table class="table">
        <thead><tr><th>Clave</th><th>Materia</th><th>Periodo</th><th>Semestre</th><th>Carrera</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['clave_grupo']) ?></td>
            <td><?= htmlspecialchars($row['materia']) ?></td>
            <td><?= htmlspecialchars($row['periodo']) ?></td>
            <td><?= htmlspecialchars($row['semestre']) ?></td>
            <td><?= htmlspecialchars($row['carrera']) ?></td>
            <td class="actions">
              <a class="edit" href="editar_grupo.php?id=<?= $row['id_grupo'] ?>">Editar</a>
              <a class="delete" href="eliminar_grupo.php?id=<?= $row['id_grupo'] ?>" onclick="return confirm('Eliminar grupo?')">Eliminar</a>
              <a class="assign" href="ver_materia.php?id=<?= $row['id_grupo'] ?>">Ver</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <aside class="card">
      <div class="stat-item"><h3>Total</h3><p><?php echo $conn->query("SELECT COUNT(*) AS t FROM grupos")->fetch_assoc()['t']; ?></p></div>
    </aside>
  </div>
</div>
</body>
</html>
<?php $stmt->close(); ?>
