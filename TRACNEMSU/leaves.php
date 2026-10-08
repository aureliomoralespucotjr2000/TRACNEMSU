<?php require 'db.php'; if(empty($_SESSION['u'])){header('Location: login.php');exit;}
$u=$_SESSION['u']; $t=in_array($u['role'],['teacher','admin']);
$lerr='';
if($_POST && !$t && isset($_POST['reason'])){ $a=$_POST['from']; $b=$_POST['to']?:$a; if($b<$a)$b=$a; $att=null;
  if(!empty($_FILES['proof']['name']) && $_FILES['proof']['error']===UPLOAD_ERR_OK){
    $ext=strtolower(pathinfo($_FILES['proof']['name'],PATHINFO_EXTENSION)); $ok=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','pdf'=>'application/pdf'];
    $mime=function_exists('mime_content_type')?mime_content_type($_FILES['proof']['tmp_name']):$ok[$ext]??'';
    if(!isset($ok[$ext]) || $mime!==$ok[$ext]) $lerr='Attachment must be a JPG, PNG or PDF file.';
    elseif($_FILES['proof']['size']>2*1024*1024) $lerr='Attachment must be 2 MB or smaller.';
    else { if(!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads',0755,true); $att=bin2hex(random_bytes(12)).'.'.$ext; move_uploaded_file($_FILES['proof']['tmp_name'],__DIR__.'/uploads/'.$att); } }
  elseif(!empty($_FILES['proof']['name']) && $_FILES['proof']['error']!==UPLOAD_ERR_OK) $lerr='Upload failed. The file may be too large (max 2 MB).';
  if(!$lerr){ $pdo->prepare("INSERT INTO leaves(student_id,date_from,date_to,reason,attachment) VALUES(?,?,?,?,?)")->execute([$u['id'],$a,$b,trim($_POST['reason']),$att]); header('Location: leaves.php'); exit; } }
if($_POST && $t && isset($_POST['id'])){ $st=$_POST['act']=='approve'?'approved':'rejected';
  $l=$pdo->prepare("SELECT * FROM leaves WHERE id=?"); $l->execute([$_POST['id']]); $l=$l->fetch();
  if($l && $l['status']=='pending'){ $pdo->prepare("UPDATE leaves SET status=? WHERE id=?")->execute([$st,$l['id']]); notify($pdo,$l['student_id'],'Your leave request ('.$l['date_from'].') was '.$st.'.','leaves.php');
    if($st=='approved'){ $d=strtotime($l['date_from']); $e=min(strtotime($l['date_to']),$d+30*86400);
      for(;$d<=$e;$d+=86400) if(date('N',$d)<6) markStatus($pdo,$l['student_id'],date('Y-m-d',$d),'excused'); } }
  header('Location: leaves.php'); exit; }
if($t) $rows=$pdo->query("SELECT l.*,u.name FROM leaves l JOIN users u ON u.id=l.student_id ORDER BY l.status='pending' DESC,l.id DESC")->fetchAll();
else { $q=$pdo->prepare("SELECT l.*,u.name FROM leaves l JOIN users u ON u.id=l.student_id WHERE student_id=? ORDER BY l.id DESC"); $q->execute([$u['id']]); $rows=$q->fetchAll(); }
$col=['pending'=>'late','approved'=>'present','rejected'=>'absent'];
top('Leaves'); if(!$t): ?>
<div class="card"><h3>Request Excuse / Leave</h3><?php if($lerr): ?><p style="color:red"><?=h($lerr)?></p><?php endif; ?><form method="post" enctype="multipart/form-data">From<input type="date" name="from" required min="<?=date('Y-m-d')?>"> To (optional)<input type="date" name="to">
<input name="reason" placeholder="Reason (e.g. sick, family event)" required maxlength="250"><label>Attachment (optional: medical certificate or letter, JPG/PNG/PDF, max 2 MB)</label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf"><button class="b present" style="padding:10px 16px">Submit</button></form></div>
<?php endif; ?>
<div class="card"><h3><?=$t?'Leave Requests':'My Requests'?></h3><table><tr><?=$t?'<th>Student</th>':''?><th>Dates</th><th>Reason</th><th>Proof</th><th>Status</th><?=$t?'<th></th>':''?></tr>
<?php foreach($rows as $r): ?><tr><?=$t?'<td>'.h($r['name']).'</td>':''?><td><?=$r['date_from']?><?=$r['date_to']!=$r['date_from']?' to '.$r['date_to']:''?></td><td><?=h($r['reason'])?></td><td><?=$r['attachment']?'<a href="uploads/'.h($r['attachment']).'" target="_blank">📎 View</a>':'-'?></td>
<td><span class="tag <?=$col[$r['status']]?>"><?=$r['status']?></span></td>
<?php if($t): ?><td><?php if($r['status']=='pending'): ?><form method="post"><input type="hidden" name="id" value="<?=$r['id']?>"><button name="act" value="approve" class="b present">Approve</button><button name="act" value="reject" class="b absent">Reject</button></form><?php endif; ?></td><?php endif; ?></tr>
<?php endforeach; ?></table></div>
<?php bottom();
