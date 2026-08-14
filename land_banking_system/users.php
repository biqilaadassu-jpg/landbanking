<?php
$title='Users';
require __DIR__.'/partials/header.php';
require_role(['ADMIN']);
$subs=db()->query("SELECT * FROM subcities ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $hash=password_hash($_POST['password'],PASSWORD_DEFAULT);
  $stmt=db()->prepare("INSERT INTO users(username,full_name,password_hash,role,subcity_id,woreda_id,digital_signature) VALUES(?,?,?,?,?,?,?)");
  $stmt->execute([
    trim($_POST['username']),trim($_POST['full_name']),$hash,$_POST['role'],
    $_POST['subcity_id']?:null,$_POST['woreda_id']?:null,trim($_POST['digital_signature']??'')
  ]);
  flash('success','User created.');redirect('users.php');
}
$users=db()->query("SELECT u.*,s.name subcity,w.name woreda FROM users u LEFT JOIN subcities s ON s.id=u.subcity_id LEFT JOIN woredas w ON w.id=u.woreda_id ORDER BY u.id DESC")->fetchAll();
?>
<div class="page-head"><h1>User Management</h1><button class="btn primary" onclick="togglePanel('new-user')">+ User</button></div>
<section id="new-user" class="panel hidden">
<form method="post" class="form-grid">
<label>Username<input name="username" required></label>
<label>Full Name<input name="full_name" required></label>
<label>Password<input type="password" name="password" required></label>
<label>Role<select name="role"><option>WOREDA</option><option>SUBCITY</option><option>CITY</option><option>ADMIN</option></select></label>
<label>Sub-City<select id="subcity_id" name="subcity_id" onchange="loadWoredas(this.value,'woreda_id')"><option value="">None</option><?php foreach($subs as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach;?></select></label>
<label>Woreda<select id="woreda_id" name="woreda_id"><option value="">None</option></select></label>
<label class="wide">Digital Signature / Approval Token<textarea name="digital_signature"></textarea></label>
<div class="wide"><button class="btn primary">Create User</button></div>
</form>
</section>
<section class="panel"><table><tr><th>User</th><th>Role</th><th>Sub-City</th><th>Woreda</th><th>Active</th></tr>
<?php foreach($users as $x): ?><tr><td><?=e($x['username'])?><br><?=e($x['full_name'])?></td><td><?=e($x['role'])?></td><td><?=e($x['subcity']??'—')?></td><td><?=e($x['woreda']??'—')?></td><td><?=e((string)$x['active'])?></td></tr><?php endforeach;?>
</table></section>
<?php require __DIR__.'/partials/footer.php'; ?>
