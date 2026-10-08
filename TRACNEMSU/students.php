<?php require 'db.php'; need('teacher'); $err='';
if(isset($_GET['ok'])){ $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND role='student'")->execute([(int)$_GET['ok']]); header('Location: students.php'); exit; }
if(isset($_GET['del'])){ $i=(int)$_GET['del']; foreach(['attendance','leaves','parent_log'] as $t) $pdo->prepare("DELETE FROM $t WHERE student_id=?")->execute([$i]);
  $pdo->prepare("DELETE FROM users WHERE id=? AND role='student'")->execute([$i]); header('Location: students.php'); exit; }
if($_POST){ $id=$_POST['id']; $n=trim($_POST['name']); $us=trim($_POST['username']); $ph=trim($_POST['phone']); $pe=trim($_POST['pemail']??'')?:null; $pw=$_POST['password']; $c=trim($_POST['code']); $bid=((int)($_POST['branch_id']??0))?:null;
  try{ if($id){ $pdo->prepare("UPDATE users SET name=?,username=?,parent_phone=?,parent_email=?,code=?,branch_id=? WHERE id=? AND role='student'")->execute([$n,$us,$ph,$pe,$c,$bid,$id]);
      if($pw) $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);
    } else { if(!$c) $c=date('Y').'-'.sprintf('%05d',$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM users")->fetchColumn());
      $pdo->prepare("INSERT INTO users(code,name,username,password,role,parent_phone,parent_email,branch_id) VALUES(?,?,?,?,'student',?,?,?)")->execute([$c,$n,$us,password_hash($pw?:'student123',PASSWORD_DEFAULT),$ph,$pe,$bid]); }
    header('Location: students.php'); exit;
  }catch(Exception $e){ $err='Username or code is already used.'; } }
$ed=null; if(isset($_GET['edit'])){ $q=$pdo->prepare("SELECT * FROM users WHERE id=? AND role='student'"); $q->execute([$_GET['edit']]); $ed=$q->fetch(); }
$rows=$pdo->query("SELECT u.*,b.name branch FROM users u LEFT JOIN branches b ON b.id=u.branch_id WHERE u.role='student' AND u.status='active' ORDER BY u.name")->fetchAll(); $brs=$pdo->query("SELECT id,name FROM branches ORDER BY name")->fetchAll();
$pend=$pdo->query("SELECT * FROM users WHERE role='student' AND status='pending' ORDER BY id")->fetchAll();
top('Students'); ?>
<?php if($pend): ?><div class="card" style="border-left:5px solid #e9a100"><h3>Pending registrations (<?=count($pend)?>)</h3><table><tr><th>Name</th><th>Student ID</th><th>Course</th><th>Contact</th><th>Parent</th><th></th></tr>
<?php foreach($pend as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['code'])?></td><td><?=h($r['course'].' · '.$r['year_level'].' · '.$r['section'])?></td><td><?=h($r['contact'])?></td><td><?=h($r['parent_name'].' '.$r['parent_phone'])?></td>
<td class="acts"><a class="b present" href="?ok=<?=$r['id']?>">✔ Approve</a><a class="b absent" href="?del=<?=$r['id']?>" onclick="return confirm('Reject and delete this registration?')">✖ Reject</a></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<div class="card"><h3><?=$ed?'Edit':'Add'?> Student</h3><p style="color:red"><?=h($err)?></p><form method="post"><input type="hidden" name="id" value="<?=$ed['id']??''?>">
<input name="name" placeholder="Full name" required value="<?=h($ed['name']??'')?>"><input name="username" placeholder="Username" required value="<?=h($ed['username']??'')?>">
<input name="password" placeholder="<?=$ed?'New password (leave blank to keep)':'Password (blank = student123)'?>"><input name="code" placeholder="Student ID number, e.g. 2024-00123 (blank = auto)" value="<?=h($ed['code']??'')?>">
<input name="phone" placeholder="Parent phone number" value="<?=h($ed['parent_phone']??'')?>"><input name="pemail" type="email" placeholder="Parent email (optional)" value="<?=h($ed['parent_email']??'')?>"><select name="branch_id"><option value="">No branch</option><?php foreach($brs as $b): ?><option value="<?=$b['id']?>"<?=($ed['branch_id']??0)==$b['id']?' selected':''?>><?=h($b['name'])?></option><?php endforeach; ?></select><button class="b present" style="padding:10px 16px">Save</button> <?php if($ed): ?><a href="students.php">Cancel</a><?php endif; ?></form></div>
<div class="card"><h3>All Students</h3><table><tr><th>Name</th><th>Code</th><th>Username</th><th>Branch</th><th>Parent</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['code'])?></td><td><?=h($r['username'])?></td><td><?=h($r['branch']?:'-')?></td><td><?=h($r['parent_phone'])?></td>
<td class="acts"><a class="b excused" href="?edit=<?=$r['id']?>">✏ Edit</a><a class="b absent" href="?del=<?=$r['id']?>" onclick="return confirm('Delete this student and all their records?')">🗑 Delete</a></td></tr><?php endforeach; ?></table></div>
<?php bottom();
