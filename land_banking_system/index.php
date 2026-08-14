<?php
$title='Dashboard';
require __DIR__.'/partials/header.php';

$u=current_user();
$where=[]; $params=[];
if ($u['role']==='SUBCITY') { $where[]='a.subcity_id=?'; $params[]=$u['subcity_id']; }
if ($u['role']==='WOREDA') { $where[]='a.woreda_id=?'; $params[]=$u['woreda_id']; }
$wsql=$where?'WHERE '.implode(' AND ',$where):'';

$base = "FROM land_transactions t JOIN land_accounts a ON a.id=t.account_id $wsql";
$stmt=db()->prepare("SELECT COALESCE(SUM(CASE WHEN t.transaction_type='DEPOSIT' THEN t.area_m2 ELSE 0 END),0) deposit_area,
COALESCE(SUM(CASE WHEN t.transaction_type='WITHDRAW' THEN t.area_m2 ELSE 0 END),0) withdraw_area,
COUNT(CASE WHEN t.transaction_type='DEPOSIT' THEN 1 END) deposit_places,
COUNT(CASE WHEN t.transaction_type='WITHDRAW' THEN 1 END) withdraw_places
$base AND t.status='APPROVED'");
$stmt->execute($params);
$stats=$stmt->fetch();

$stmt=db()->prepare("SELECT COUNT(*) c FROM land_transactions t JOIN land_accounts a ON a.id=t.account_id $wsql");
$stmt->execute($params); $totalTransactions=(int)$stmt->fetch()['c'];

$stmt=db()->prepare("SELECT t.status,COUNT(*) c FROM land_transactions t JOIN land_accounts a ON a.id=t.account_id $wsql GROUP BY t.status");
$stmt->execute($params); $statusRows=$stmt->fetchAll();
?>
<h1>Dashboard</h1>
<div class="cards">
  <div class="card"><span>Approved Deposit Area</span><strong><?=number_format((float)$stats['deposit_area'],2)?> m²</strong></div>
  <div class="card"><span>Approved Withdraw Area</span><strong><?=number_format((float)$stats['withdraw_area'],2)?> m²</strong></div>
  <div class="card"><span>Deposit Places</span><strong><?=number_format((int)$stats['deposit_places'])?></strong></div>
  <div class="card"><span>Withdraw Places</span><strong><?=number_format((int)$stats['withdraw_places'])?></strong></div>
</div>

<div class="grid two">
<section class="panel">
<h2>Workflow Status</h2>
<table><tr><th>Status</th><th>Count</th></tr>
<?php foreach($statusRows as $r): ?><tr><td><?=e($r['status'])?></td><td><?=$r['c']?></td></tr><?php endforeach; ?>
</table>
</section>
<section class="panel">
<h2>System</h2>
<p>Total transactions: <b><?=$totalTransactions?></b></p>
<p>Access level: <b><?=e($u['role'])?></b></p>
<a class="btn primary" href="transactions.php">Manage Transactions</a>
</section>
</div>

<section class="panel">
<h2>Map Overview</h2>
<div id="dashboard-map" class="map"></div>
</section>
<script>
window.addEventListener('load', () => loadMap('dashboard-map', 'approved'));
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
