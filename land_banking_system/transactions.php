<?php
$title='Transactions';
require __DIR__.'/partials/header.php';
$u=current_user();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_role(['WOREDA','ADMIN']);
    $account=(int)$_POST['account_id'];
    $stmt=db()->prepare("SELECT * FROM land_accounts WHERE id=?");
    $stmt->execute([$account]); $acc=$stmt->fetch();
    if(!$acc){ flash('danger','Invalid account.'); redirect('transactions.php'); }

    $lat=(float)$_POST['latitude']; $lng=(float)$_POST['longitude'];
    if($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180){
        flash('danger','Invalid GPS coordinates.'); redirect('transactions.php');
    }

    $num='TRX-'.date('YmdHis').'-'.random_int(100,999);
    $stmt=db()->prepare("INSERT INTO land_transactions
    (transaction_number,account_id,transaction_type,area_m2,x_coordinate,y_coordinate,latitude,longitude,address,statement,created_by)
    VALUES(?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $num,$account,$_POST['transaction_type'],(float)$_POST['area_m2'],
        $_POST['x_coordinate']!==''?(float)$_POST['x_coordinate']:null,
        $_POST['y_coordinate']!==''?(float)$_POST['y_coordinate']:null,
        $lat,$lng,trim($_POST['address']),trim($_POST['statement']),$u['id']
    ]);
    audit('CREATE','LAND_TRANSACTION',(int)db()->lastInsertId(),$num);
    flash('success','Transaction submitted for Sub-City approval.');
    redirect('transactions.php');
}

$where=[];$params=[];
if($u['role']==='SUBCITY'){ $where[]='a.subcity_id=?';$params[]=$u['subcity_id'];}
if($u['role']==='WOREDA'){ $where[]='a.woreda_id=?';$params[]=$u['woreda_id'];}
$type=$_GET['type']??''; $status=$_GET['status']??''; $sub=(int)($_GET['subcity_id']??0); $wor=(int)($_GET['woreda_id']??0);
if($type){$where[]='t.transaction_type=?';$params[]=$type;}
if($status){$where[]='t.status=?';$params[]=$status;}
if($sub && $u['role']!=='WOREDA'){$where[]='a.subcity_id=?';$params[]=$sub;}
if($wor && $u['role']!=='WOREDA'){$where[]='a.woreda_id=?';$params[]=$wor;}
$wsql=$where?'WHERE '.implode(' AND ',$where):'';

$stmt=db()->prepare("SELECT t.*,a.account_number,a.account_name,s.name subcity,w.name woreda
FROM land_transactions t JOIN land_accounts a ON a.id=t.account_id
JOIN subcities s ON s.id=a.subcity_id JOIN woredas w ON w.id=a.woreda_id
$wsql ORDER BY t.id DESC");
$stmt->execute($params);$rows=$stmt->fetchAll();

$subs=db()->query("SELECT * FROM subcities ORDER BY name")->fetchAll();
$accountsQ="SELECT id,account_number,account_name FROM land_accounts ".($u['role']==='WOREDA'?'WHERE woreda_id='.(int)$u['woreda_id']:'')." ORDER BY account_number";
$accounts=db()->query($accountsQ)->fetchAll();
?>
<div class="page-head"><h1>Transactions</h1><?php if(in_array($u['role'],['WOREDA','ADMIN'],true)): ?><button class="btn primary" onclick="togglePanel('new-transaction')">+ Deposit / Withdraw</button><?php endif; ?></div>

<?php if(in_array($u['role'],['WOREDA','ADMIN'],true)): ?>
<section id="new-transaction" class="panel hidden">
<h2>New Land Transaction</h2>
<form method="post" class="form-grid">
<label>Account<select name="account_id" required><?php foreach($accounts as $a): ?><option value="<?=$a['id']?>"><?=e($a['account_number'].' — '.$a['account_name'])?></option><?php endforeach; ?></select></label>
<label>Type<select name="transaction_type"><option>DEPOSIT</option><option>WITHDRAW</option></select></label>
<label>Area (m²)<input type="number" step="0.01" min="0.01" name="area_m2" required></label>
<label>X Coordinate<input name="x_coordinate" placeholder="Optional GIS X"></label>
<label>Y Coordinate<input name="y_coordinate" placeholder="Optional GIS Y"></label>
<label>Latitude<input id="latitude" name="latitude" required></label>
<label>Longitude<input id="longitude" name="longitude" required></label>
<label class="wide">Address<input name="address"></label>
<label class="wide">Statement / Information<textarea name="statement"></textarea></label>
<div class="wide map-tools"><button type="button" class="btn" onclick="getGPS()">Use Current GPS</button><span id="gps-status"></span></div>
<div id="entry-map" class="map wide"></div>
<div class="wide"><button class="btn primary">Submit Transaction</button></div>
</form>
</section>
<script>
window.addEventListener('load',()=>loadMap('entry-map','entry'));
</script>
<?php endif; ?>

<section class="panel">
<h2>Filters</h2>
<form class="filter" method="get">
<select name="subcity_id"><option value="">All Sub-Cities</option><?php foreach($subs as $s): ?><option value="<?=$s['id']?>" <?=((int)($_GET['subcity_id']??0)==$s['id']?'selected':'')?>><?=e($s['name'])?></option><?php endforeach; ?></select>
<select name="type"><option value="">All Types</option><option <?=($type==='DEPOSIT'?'selected':'')?>>DEPOSIT</option><option <?=($type==='WITHDRAW'?'selected':'')?>>WITHDRAW</option></select>
<select name="status"><option value="">All Status</option><?php foreach(['PENDING_SUBCITY','PENDING_CITY','APPROVED','REJECTED','RETURNED'] as $s): ?><option <?=($status===$s?'selected':'')?>><?=$s?></option><?php endforeach; ?></select>
<button class="btn">Filter</button>
</form>
</section>

<section class="panel">
<table>
<tr><th>Number</th><th>Type</th><th>Area</th><th>Sub-City</th><th>Woreda</th><th>Coordinates</th><th>Status</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?=e($r['transaction_number'])?></td>
<td><span class="pill <?=strtolower($r['transaction_type'])?>"><?=e($r['transaction_type'])?></span></td>
<td><?=number_format($r['area_m2'],2)?> m²</td>
<td><?=e($r['subcity'])?></td><td><?=e($r['woreda'])?></td>
<td><a target="_blank" href="https://www.google.com/maps?q=<?=urlencode($r['latitude'].','.$r['longitude'])?>"><?=number_format($r['latitude'],6)?>, <?=number_format($r['longitude'],6)?></a></td>
<td><span class="pill"><?=e($r['status'])?></span></td>
</tr>
<?php endforeach; ?>
</table>
</section>
<?php require __DIR__.'/partials/footer.php'; ?>
