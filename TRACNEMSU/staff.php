<?php require 'db.php'; need('admin'); $err='';
if($_SERVER['REQUEST_METHOD']=='POST'){ $a=$_POST['act']??''; $id=(int)($_POST['id']??0); $n=trim($_POST['name']??''); $us=trim($_POST['username']??''); $pw=$_POST['password']??''; $bid=((int)($_POST['branch_id']??0))?:null;
  if($a=='del'){ $pdo->prepare("DELETE FROM users WHERE id=? AND role='teacher' AND id<>?")->execute([$id,$_SESSION['u']['id']]); header('Location: staff.php'); exit; }
  if($n==='') $err='Full name is required.';
  elseif(!preg_match('/^\w{4,20}$/',$us)) $err='Username: 4-20 letters, numbers or underscore.';
  elseif(!$id && strlen($pw)<6) $err='Password must be at least 6 characters.';
  elseif($id && $pw!=='' && strlen($pw)<6) $err='New password must be at least 6 characters.';
  if(!$err){ try{
    if($id){ $pdo->prepare("UPDATE users SET name=?,username=?,branch_id=? WHERE id=? AND role='teacher'")->execute([$n,$us,$bid,$id]);
      if($pw!=='') $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='teacher'")->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);
    } else $pdo->prepare("INSERT INTO users(code,name,username,password,role,status,branch_id) VALUES(NULL,?,?,?,'teacher','active',?)")->execute([$n,$us,password_hash($pw,PASSWORD_DEFAULT),$bid]);
    header('Location: staff.php'); exit;
  }catch(Exception $e){ $err='That username is already used.'; } } }
$ed=null; if(isset($_GET['edit'])){ $q=$pdo->prepare("SELECT * FROM users WHERE id=? AND role='teacher'"); $q->execute([(int)$_GET['edit']]); $ed=$q->fetch(); }
$brs=$pdo->query("SELECT id,name FROM branches ORDER BY name")->fetchAll();
$rows=$pdo->query("SELECT u.*,b.name branch FROM users u LEFT JOIN branches b ON b.id=u.branch_id WHERE u.role='teacher' ORDER BY u.name")->fetchAll();
top('Staff'); ?>
<div class="card"><h3><?=$ed?'Edit':'Add'?> Staff (Teacher)</h3><?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="id" value="<?=$ed['id']??''?>"><input name="name" placeholder="Full name" required value="<?=h($ed['name']??'')?>"><input name="username" placeholder="Username" required value="<?=h($ed['username']??'')?>">
<input name="password" type="password" placeholder="<?=$ed?'New password (leave blank to keep)':'Password (min 6)'?>">
<select name="branch_id"><option value="">No branch</option><?php foreach($brs as $b): ?><option value="<?=$b['id']?>"<?=($ed['branch_id']??0)==$b['id']?' selected':''?>><?=h($b['name'])?></option><?php endforeach; ?></select>
<button class="b present" style="padding:10px 16px">Save</button> <?php if($ed): ?><a href="staff.php">Cancel</a><?php endif; ?></form></div>
<div class="card"><h3>All Staff</h3><table><tr><th>Name</th><th>Username</th><th>Branch</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['username'])?></td><td><?=h($r['branch']?:'-')?></td>
<td class="acts"><a class="b excused" href="?edit=<?=$r['id']?>">✏ Edit</a><form method="post" style="display:inline" onsubmit="return confirm('Delete this staff account?')"><input type="hidden" name="id" value="<?=$r['id']?>"><button name="act" value="del" class="b absent">🗑 Delete</button></form></td></tr><?php endforeach; if(!$rows) echo '<tr><td colspan="4">No staff yet.</td></tr>'; ?></table></div>
<?php bottom();
