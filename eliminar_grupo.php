<?php
include_once("conexion.php");
if (!isset($_GET['id'])) { header("Location: listar_grupos.php"); exit; }
$id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM grupos WHERE id_grupo=?");
$stmt->bind_param("i",$id);
if ($stmt->execute()) { header("Location: listar_grupos.php?msg=Grupo eliminado"); exit; }
else echo "Error: ".$stmt->error;
$stmt->close();
$conn->close();
?>
