<?php require 'db.php'; need('teacher'); $me=$_SESSION['u']; $isA=$me['role']=='admin'; $err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
  if(($_POST['act']??'')=='del'){ $id=(int)$_POST['id'];
    $q=$pdo->prepare("DELETE FROM subjects WHERE id=?".($isA?'':' AND teacher_id=?')); $q->execute($isA?[$id]:[$id,$me['id']]);
    $pdo->prepare("DELETE FROM class_att WHERE subject_id=?")->execute([$id]); }
  else { $c=trim($_POST['code']??''); $n=trim($_POST['name']??''); $tid=$isA?((int)($_POST['teacher_id']??0)?:null):$me['id']; $bid=((int)($_POST['branch_id']??0))?:null;
    if($c===''||$n==='') $err='Subject code and name are required.';
    else $pdo->prepare("INSERT INTO subjects(code,name,teacher_id,branch_id) VALUES(?,?,?,?)")->execute([mb_substr($c,0,20),mb_substr($n,0,100),$tid,$bid]); }
  if(!$err){ header('Location: classes.php'); exit; } }
$brs=$pdo->query("SELECT id,name FROM branches ORDER BY name")->fetchAll();
$tch=$pdo->query("SELECT id,name FROM users WHERE role IN('teacher','admin') ORDER BY name")->fetchAll();
$rows=$pdo->query("SELECT s.*,u.name teacher,b.name branch FROM subjects s LEFT JOIN users u ON u.id=s.teacher_id LEFT JOIN branches b ON b.id=s.branch_id ORDER BY s.code")->fetchAll();
top('Classes'); ?>
<div class="card"><h3>📚 Add Subject / Class</h3><?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><input name="code" placeholder="Subject code (e.g. IT 327)" maxlength="20" required><input name="name" placeholder="Subject name" maxlength="100" required>
<?php if($isA): ?><select name="teacher_id"><option value="0">No teacher yet</option><?php foreach($tch as $t): ?><option value="<?=$t['id']?>"><?=h($t['name'])?></option><?php endforeach; ?></select><?php else: ?><p class="tip">You will be the teacher of this subject.</p><?php endif; ?>
<select name="branch_id"><option value="">All branches</option><?php foreach($brs as $b): ?><option value="<?=$b['id']?>"><?=h($b['name'])?></option><?php endforeach; ?></select>
<button class="b present" style="padding:10px 16px">Add</button></form></div>
<div class="card"><h3>All Subjects</h3><table><tr><th>Code</th><th>Subject</th><th>Teacher</th><th>Branch</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['code'])?></td><td><?=h($r['name'])?></td><td><?=h($r['teacher']?:'-')?></td><td><?=h($r['branch']?:'All')?></td>
<td class="acts"><a class="b present" href="classatt.php?s=<?=$r['id']?>">🗓 Attendance</a>
<?php if($isA || $r['teacher_id']==$me['id']): ?><form method="post" style="display:inline" onsubmit="return confirm('Delete this subject and its attendance?')"><input type="hidden" name="id" value="<?=$r['id']?>"><button name="act" value="del" class="b absent">🗑</button></form><?php endif; ?></td></tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan="5">No subjects yet.</td></tr>'; ?></table></div>
<?php bottom();
