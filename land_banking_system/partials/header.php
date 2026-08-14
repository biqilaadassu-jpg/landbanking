<?php require_once __DIR__ . '/../config/config.php'; require_login(); $u=current_user(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title ?? 'Land Banking System')?></title>
<link rel="stylesheet" href="assets/css/app.css">
<script src="https://maps.googleapis.com/maps/api/js?key=<?=rawurlencode(GOOGLE_MAPS_API_KEY)?>&callback=Function.prototype" async defer></script>
</head>
<body>
<header class="topbar">
  <div class="brand">LAND BANKING</div>
  <div class="top-actions">
    <span><?=e($u['full_name'])?> · <?=e($u['role'])?></span>
    <a href="logout.php" class="btn small">Logout</a>
  </div>
</header>
<div class="layout">
<aside class="sidebar">
  <a href="index.php">Dashboard</a>
  <a href="accounts.php">Land Accounts</a>
  <a href="transactions.php">Transactions</a>
  <?php if (in_array($u['role'], ['CITY','SUBCITY','ADMIN'], true)): ?>
    <a href="approvals.php">Approvals</a>
  <?php endif; ?>
  <a href="map.php">GIS Map</a>
  <?php if ($u['role']==='ADMIN'): ?><a href="users.php">Users</a><?php endif; ?>
</aside>
<main class="main">
<?php if (!empty($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert <?=$f['type']==='success'?'success':'danger'?>"><?=e($f['message'])?></div>
<?php endif; ?>
