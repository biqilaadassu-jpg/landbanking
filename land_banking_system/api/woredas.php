<?php
require __DIR__.'/../config/config.php';
header('Content-Type: application/json; charset=utf-8');
$id=(int)($_GET['subcity_id']??0);
$stmt=db()->prepare("SELECT id,name FROM woredas WHERE subcity_id=? ORDER BY name");
$stmt->execute([$id]);
echo json_encode($stmt->fetchAll());
