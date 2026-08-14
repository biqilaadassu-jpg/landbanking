<?php
$title='Approvals';
require __DIR__.'/partials/header.php';
$u=current_user();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_role(['CITY','SUBCITY','ADMIN']);
    $id=(int)$_POST['transaction_id'];
    $action=$_POST['action'];
    $comment=trim($_POST['comment']??'');
    $signature=trim($_POST['digital_signature']??'');

    $stmt=db()->prepare("SELECT t.*,a.subcity_id,a.woreda_id FROM land_transactions t
    JOIN land_accounts a ON a.id=t.account_id WHERE t.id=?");
    $stmt->execute([$id]);$t=$stmt->fetch();
    if(!$t){flash('danger','Transaction not found.');redirect('approvals.php');}

    $allowed=false;$level='';
    if($u['role']==='SUBCITY' && (int)$u['subcity_id']===(int)$t['subcity_id'] && $t['status']==='PENDING_SUBCITY'){
        $allowed=true;$level='SUBCITY';
    }
    if(in_array($u['role'],['CITY','ADMIN'],true) && $t['status']==='PENDING_CITY'){
        $allowed=true;$level='CITY';
    }
    if(!$allowed){flash('danger','This transaction is not available for your approval level.');redirect('approvals.php');}

    if(!in_array($action,['APPROVE','REJECT','RETURN'],true)){flash('danger','Invalid action.');redirect('approvals.php');}
    if($action!=='APPROVE' && $comment===''){flash('danger','Comment/reason is required for rejection or return.');redirect('approvals.php');}

    $newStatus = $action==='APPROVE'
        ? ($level==='SUBCITY'?'PENDING_CITY':'APPROVED')
        : ($action==='REJECT'?'REJECTED':'RETURNED');

    $stmt=db()->prepare("INSERT INTO approval_actions(transaction_id,level,action,approved_by,digital_signature,comment)
                         VALUES(?,?,?,?,?,?)");
    $stmt->execute([$id,$level,$action,$u['id'],$signature?:$u['digital_signature'],$comment]);

    $stmt=db()->prepare("UPDATE land_transactions SET status=?,updated_at=NOW() WHERE id=?");
    $stmt->execute([$newStatus,$id]);
    audit($action,'LAND_TRANSACTION',$id,$level);
    flash('success','Approval action recorded.');
    redirect('approvals.php');
}

$where=[];$params=[];
if($u['role']==='SUBCITY'){$where[]='a.subcity_id=?';$params[]=$u['subcity_id'];$where[]="t.status='PENDING_SUBCITY'";}
elseif(in_array($u['role'],['CITY','ADMIN'],true)){$where[]="t.status='PENDING_CITY'";}
else {http_response_code(403);exit('403 Forbidden');}
$wsql='WHERE '.implode(' AND ',$where);
$stmt=db()->prepare("SELECT t.*,a.account_number,s.name subcity,w.name woreda
FROM land_transactions t JOIN land_accounts a ON a.id=t.account_id
JOIN subcities s ON s.id=a.subcity_id JOIN woredas w ON w.id=a.woreda_id
$wsql ORDER BY t.created_at ASC");
$stmt->execute($params);$rows=$stmt->fetchAll();
?>
<h1>Approval Queue</h1>
<section class="panel">
<table>
<tr><th>Transaction</th><th>Type</th><th>Area</th><th>Location</th><th>Action</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?=e($r['transaction_number'])?><br><small><?=e($r['account_number'])?></small></td>
<td><?=e($r['transaction_type'])?></td><td><?=number_format($r['area_m2'],2)?> m²</td>
<td><?=e($r['subcity'])?> / <?=e($r['woreda'])?><br>
<a target="_blank" href="https://www.google.com/maps?q=<?=urlencode($r['latitude'].','.$r['longitude'])?>">Open Map</a></td>
<td>
<form method="post" class="approval-form">
<input type="hidden" name="transaction_id" value="<?=$r['id']?>">
<input name="digital_signature" placeholder="Digital signature / approval token">
<textarea name="comment" placeholder="Comment (required for reject/return)"></textarea>
<div>
<button name="action" value="APPROVE" class="btn success">Approve</button>
<button name="action" value="RETURN" class="btn">Return</button>
<button name="action" value="REJECT" class="btn danger">Reject</button>
</div>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</section>
<?php require __DIR__.'/partials/footer.php'; ?>
