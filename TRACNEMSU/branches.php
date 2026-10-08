<?php require 'db.php'; need('admin'); $err='';
if($_SERVER['REQUEST_METHOD']=='POST'){ $a=$_POST['act']??''; $id=(int)($_POST['id']??0); $n=trim($_POST['name']??''); $ad=trim($_POST['address']??'');
  try{
    if($a=='del'){ $pdo->prepare("UPDATE users SET branch_id=NULL WHERE branch_id=?")->execute([$id]); $pdo->prepare("DELETE FROM branches WHERE id=?")->execute([$id]); }
    elseif($n===''){ $err='Branch name is required.'; }
    elseif($id){ $pdo->prepare("UPDATE branches SET name=?,address=? WHERE id=?")->execute([$n,$ad,$id]); }
    else { $pdo->prepare("INSERT INTO branches(name,address) VALUES(?,?)")->execute([$n,$ad]); }
    if(!$err){ header('Location: branches.php'); exit; }
  }catch(Exception $e){ $err='That branch name already exists.'; } }
$ed=null; if(isset($_GET['edit'])){ $q=$pdo->prepare("SELECT * FROM branches WHERE id=?"); $q->execute([(int)$_GET['edit']]); $ed=$q->fetch(); }
$rows=$pdo->query("SELECT b.*,(SELECT COUNT(*) FROM users u WHERE u.branch_id=b.id AND u.role='student') s,(SELECT COUNT(*) FROM users u WHERE u.branch_id=b.id AND u.role='teacher') t FROM branches b ORDER BY b.name")->fetchAll();
top('Branches'); ?>
<div class="card"><h3><?=$ed?'Modify':'Add'?> Branch</h3><?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="id" value="<?=$ed['id']??''?>"><input name="name" placeholder="Branch / campus name" required maxlength="100" value="<?=h($ed['name']??'')?>"><input name="address" placeholder="Address (optional)" maxlength="200" value="<?=h($ed['address']??'')?>">
<button class="b present" style="padding:10px 16px">Save</button> <?php if($ed): ?><a href="branches.php">Cancel</a><?php endif; ?></form></div>
<div class="card"><h3>All Branches</h3><table><tr><th>Name</th><th>Address</th><th>Students</th><th>Staff</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['address'])?></td><td><?=$r['s']?></td><td><?=$r['t']?></td>
<td class="acts"><a class="b excused" href="?edit=<?=$r['id']?>">✏ Edit</a><form method="post" style="display:inline" onsubmit="return confirm('Delete this branch? Students and staff in it will have no branch.')"><input type="hidden" name="id" value="<?=$r['id']?>"><button name="act" value="del" class="b absent">🗑 Delete</button></form></td></tr><?php endforeach; ?></table></div>
<?php bottom();
