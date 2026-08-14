<?php
require __DIR__.'/../config/config.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$u=current_user();
$where=["1=1"];$params=[];
$type=$_GET['type']??'ALL';$status=$_GET['status']??'ALL';

if($type!=='ALL'){$where[]='t.transaction_type=?';$params[]=$type;}
if($status!=='ALL'){$where[]='t.status=?';$params[]=$status;}
if($u['role']==='SUBCITY'){$where[]='a.subcity_id=?';$params[]=$u['subcity_id'];}
if($u['role']==='WOREDA'){$where[]='a.woreda_id=?';$params[]=$u['woreda_id'];}

$stmt=db()->prepare("SELECT t.id,t.transaction_number,t.transaction_type,t.area_m2,t.latitude,t.longitude,
t.address,t.statement,t.status,a.account_number,s.name subcity,w.name woreda
FROM land_transactions t
JOIN land_accounts a ON a.id=t.account_id
JOIN subcities s ON s.id=a.subcity_id
JOIN woredas w ON w.id=a.woreda_id
WHERE ".implode(' AND ',$where)." ORDER BY t.id DESC");
$stmt->execute($params);
echo json_encode($stmt->fetchAll());
