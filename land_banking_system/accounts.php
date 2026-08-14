<?php
$title='Land Accounts';
require __DIR__.'/partials/header.php';
$u=current_user();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_role(['WOREDA','ADMIN']);
    $subcity=(int)$_POST['subcity_id'];
    $woreda=(int)$_POST['woreda_id'];
    $stmt=db()->prepare("SELECT subcity_id FROM woredas WHERE id=?");
    $stmt->execute([$woreda]);
    if ((int)$stmt->fetchColumn() !== $subcity) {
        flash('danger','Woreda does not belong to selected Sub-City.');
        redirect('accounts.php');
    }
    $stmt=db()->prepare("INSERT INTO land_accounts(account_number,subcity_id,woreda_id,account_name,address,opening_area,created_by)
                         VALUES(?,?,?,?,?,?,?)");
    $stmt->execute([
        trim($_POST['account_number']),$subcity,$woreda,trim($_POST['account_name']),
        trim($_POST['address']),(float)$_POST['opening_area'],$u['id']
    ]);
    audit('CREATE','LAND_ACCOUNT',(int)db()->lastInsertId());
    flash('success','Land Bank Account created.');
    redirect('accounts.php');
}

$subs=db()->query("SELECT * FROM subcities ORDER BY name")->fetchAll();
$where=''; $params=[];
if($u['role']==='SUBCITY'){ $where='WHERE a.subcity_id=?'; $params[]=$u['subcity_id'];}
if($u['role']==='WOREDA'){ $where='WHERE a.woreda_id=?'; $params[]=$u['woreda_id'];}
$stmt=db()->prepare("SELECT a.*,s.name subcity,w.name woreda FROM land_accounts a
JOIN subcities s ON s.id=a.subcity_id JOIN woredas w ON w.id=a.woreda_id $where ORDER BY a.id DESC");
$stmt->execute($params); $accounts=$stmt->fetchAll();
?>
<div class="page-head"><h1>Land Bank Accounts</h1><?php if(in_array($u['role'],['WOREDA','ADMIN'],true)): ?><button class="btn primary" onclick="togglePanel('new-account')">+ New Account</button><?php endif; ?></div>

<?php if(in_array($u['role'],['WOREDA','ADMIN'],true)): ?>
<section id="new-account" class="panel hidden">
<h2>Create Account</h2>
<form method="post" class="form-grid">
<label>Account Number<input name="account_number" required></label>
<label>Account Name<input name="account_name" required></label>
<label>Sub-City<select id="subcity_id" name="subcity_id" required onchange="loadWoredas(this.value,'woreda_id')">
<option value="">Select</option><?php foreach($subs as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach; ?>
</select></label>
<label>Woreda<select id="woreda_id" name="woreda_id" required><option value="">Select Sub-City first</option></select></label>
<label>Opening Area (m²)<input type="number" step="0.01" name="opening_area" value="0"></label>
<label class="wide">Address<textarea name="address"></textarea></label>
<div class="wide"><button class="btn primary">Save Account</button></div>
</form>
</section>
<?php endif; ?>

<section class="panel">
<table>
<tr><th>Account</th><th>Sub-City</th><th>Woreda</th><th>Name</th><th>Opening Area</th><th>Status</th></tr>
<?php foreach($accounts as $a): ?>
<tr><td><?=e($a['account_number'])?></td><td><?=e($a['subcity'])?></td><td><?=e($a['woreda'])?></td>
<td><?=e($a['account_name'])?></td><td><?=number_format($a['opening_area'],2)?> m²</td><td><?=e($a['status'])?></td></tr>
<?php endforeach; ?>
</table>
</section>
<?php require __DIR__.'/partials/footer.php'; ?>
