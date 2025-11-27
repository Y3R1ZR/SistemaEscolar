<?php
// export_print.php
include_once "conexion.php";
$id_grupo = isset($_GET['grupo']) ? intval($_GET['grupo']) : 0;
if ($id_grupo<=0) { echo "Grupo inválido"; exit; }
// cargar datos (similar a lista_grupo.php)
$stmt = $conn->prepare("SELECT clave_grupo, materia FROM grupos WHERE id_grupo=?");
$stmt->bind_param("i",$id_grupo); $stmt->execute(); $g = $stmt->get_result()->fetch_assoc(); $stmt->close();

// alumnos
$stmt = $conn->prepare("SELECT a.matricula, a.nombre, a.apellido_paterno, a.apellido_materno FROM alumno_grupo ag JOIN alumnos a ON ag.matricula=a.matricula WHERE ag.id_grupo=? ORDER BY a.apellido_paterno, a.apellido_materno, a.nombre ASC");
$stmt->bind_param("i",$id_grupo); $stmt->execute(); $res = $stmt->get_result();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Imprimir Lista - <?= htmlspecialchars($g['clave_grupo']) ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#111}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border:1px solid #ccc;text-align:left}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
</style>
</head>
<body>
<div class="header">
  <div>
    <h2>Lista de Grupo: <?= htmlspecialchars($g['clave_grupo'].' - '.$g['materia']) ?></h2>
    <div>Generado: <?= date('d/m/Y H:i') ?></div>
  </div>
  <div>
    <button onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>
</div>
<table>
  <thead>
    <tr><th>#</th><th>Matrícula</th><th>Nombre</th></tr>
  </thead>
  <tbody>
    <?php $i=1; while($r=$res->fetch_assoc()): ?>
      <tr><td><?= $i++ ?></td><td><?= htmlspecialchars($r['matricula']) ?></td><td><?= htmlspecialchars($r['apellido_paterno'].' '.$r['apellido_materno'].' '.$r['nombre']) ?></td></tr>
    <?php endwhile; ?>
  </tbody>
</table>
</body>
</html>
