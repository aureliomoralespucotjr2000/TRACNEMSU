<?php require 'db.php'; need('teacher'); $me=$_SESSION['u'];
$q=$pdo->prepare("SELECT s.*,u.name teacher FROM subjects s LEFT JOIN users u ON u.id=s.teacher_id WHERE s.id=?"); $q->execute([(int)($_GET['s']??0)]); $sub=$q->fetch();
if(!$sub){ header('Location: classes.php'); exit; }
$today=date('Y-m-d'); $d=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['d']??'')?$_GET['d']:$today; if($d>$today) $d=$today;
$canEdit=$me['role']=='admin' || !$sub['teacher_id'] || $sub['teacher_id']==$me['id'];
$self='classatt.php?s='.$sub['id'].'&d='.$d;
$w="u.role='student' AND u.status='active'".($sub['branch_id']?" AND u.branch_id=".(int)$sub['branch_id']:'');
$q=$pdo->prepare("SELECT u.id,u.code,u.name,c.status FROM users u LEFT JOIN class_att c ON c.student_id=u.id AND c.subject_id=? AND c.att_date=? WHERE $w ORDER BY u.name"); $q->execute([$sub['id'],$d]); $rows=$q->fetchAll();
if($_SERVER['REQUEST_METHOD']=='POST' && $canEdit){
  $ins=$pdo->prepare("INSERT INTO class_att(subject_id,student_id,att_date,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
  if(($_POST['act']??'')=='allpresent'){ foreach($rows as $r) if(!$r['status']) $ins->execute([$sub['id'],$r['id'],$d,'present']); }
  elseif(isset($_POST['sid'],$_POST['status']) && in_array($_POST['status'],['present','late','excused','absent'],true)){ foreach($rows as $r) if($r['id']==(int)$_POST['sid']){ $ins->execute([$sub['id'],$r['id'],$d,$_POST['status']]); break; } }
  header('Location: '.$self); exit; }
$c=['present'=>0,'late'=>0,'excused'=>0,'absent'=>0,'none'=>0]; foreach($rows as $r) $c[$r['status']?:'none']++;
top('Class Attendance'); ?>
<div class="card noprint"><h3>📚 <?=h($sub['code'].' · '.$sub['name'])?></h3><p>Teacher: <b><?=h($sub['teacher']?:'-')?></b></p>
<form method="get">Date: <input type="hidden" name="s" value="<?=$sub['id']?>"><input type="date" name="d" value="<?=h($d)?>" max="<?=$today?>" style="width:auto" onchange="this.form.submit()"> <a href="classes.php">← Back to classes</a></form></div>
<?=countersHtml($c)?><br>
<div class="card"><h3>Students on <?=date('F j, Y',strtotime($d))?></h3>
<?php if(!$canEdit): ?><p class="tip">View only. This subject belongs to another teacher.</p><?php else: ?>
<form method="post" onsubmit="return confirm('Mark everyone without a record as Present?')"><button name="act" value="allpresent" class="b present" style="padding:8px 14px;margin-bottom:8px">✅ Mark all unmarked as Present</button></form><?php endif; ?>
<table><tr><th>Name</th><th>Code</th><th>Status</th><?=$canEdit?'<th>Change</th>':''?></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['code'])?></td><td><?=$r['status']?'<span class="tag '.$r['status'].'">'.$r['status'].'</span>':'-'?></td>
<?php if($canEdit): ?><td><form method="post" style="display:inline"><input type="hidden" name="sid" value="<?=$r['id']?>"><?php foreach(['present','late','excused','absent'] as $k): ?><button name="status" value="<?=$k?>" class="b <?=$k?>"<?=$r['status']==$k?' style="outline:3px solid #0006"':''?>><?=ucfirst($k[0])?></button><?php endforeach; ?></form></td><?php endif; ?></tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan="4">No students found.</td></tr>'; ?></table></div>
<?php bottom();
