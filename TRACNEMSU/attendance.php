<?php require 'db.php'; need('teacher');
$today=date('Y-m-d'); $d=preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['d']??'')?$_GET['d']:$today; if($d>$today) $d=$today; $b=(int)($_GET['b']??0);
$self='attendance.php?d='.$d.'&b='.$b;
$where="u.role='student' AND u.status='active'".($b?" AND u.branch_id=".$b:'');
$q=$pdo->prepare("SELECT u.id,u.code,u.name,a.status,a.time_in FROM users u LEFT JOIN attendance a ON a.student_id=u.id AND a.att_date=? WHERE $where ORDER BY u.name"); $q->execute([$d]); $rows=$q->fetchAll();
if($_SERVER['REQUEST_METHOD']=='POST'){
  if(($_POST['act']??'')=='allpresent'){ foreach($rows as $r) if(!$r['status']) markStatus($pdo,$r['id'],$d,'present'); }
  elseif(isset($_POST['sid'],$_POST['status'])){ foreach($rows as $r) if($r['id']==(int)$_POST['sid']){ markStatus($pdo,(int)$_POST['sid'],$d,$_POST['status']); break; } }
  header('Location: '.$self); exit; }
$c=todayCounts($rows); $brs=$pdo->query("SELECT id,name FROM branches ORDER BY name")->fetchAll();
top('Attendance'); ?>
<div class="card noprint"><h3>🗓 Attendance Records (Add / Modify / View)</h3>
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">Date: <input type="date" name="d" value="<?=h($d)?>" max="<?=$today?>" style="width:auto" onchange="this.form.submit()">
Branch: <select name="b" style="width:auto" onchange="this.form.submit()"><option value="0">All branches</option><?php foreach($brs as $x): ?><option value="<?=$x['id']?>"<?=$b==$x['id']?' selected':''?>><?=h($x['name'])?></option><?php endforeach; ?></select></form></div>
<?=countersHtml($c)?><br>
<div class="card"><h3>Students on <?=date('F j, Y',strtotime($d))?></h3>
<form method="post" action="<?=h($self)?>" onsubmit="return confirm('Mark everyone without a record as Present?')"><button name="act" value="allpresent" class="b present" style="padding:8px 14px;margin-bottom:8px">✅ Mark all unmarked as Present</button></form>
<table><tr><th>Name</th><th>Code</th><th>Status</th><th>Time</th><th>Change</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['code'])?></td><td><?=$r['status']?'<span class="tag '.$r['status'].'">'.$r['status'].'</span>':'-'?></td><td><?=h($r['time_in'])?></td>
<td><form method="post" action="<?=h($self)?>" style="display:inline"><input type="hidden" name="sid" value="<?=$r['id']?>"><?php foreach(['present','late','excused','absent'] as $k): ?><button name="status" value="<?=$k?>" class="b <?=$k?>"<?=$r['status']==$k?' style="outline:3px solid #0006"':''?>><?=ucfirst($k[0])?></button><?php endforeach; ?></form></td></tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan="5">No students found.</td></tr>'; ?></table>
<p class="tip">P = Present, L = Late, E = Excused, A = Absent (parents are notified when a student is marked Absent).</p></div>
<?php bottom();
