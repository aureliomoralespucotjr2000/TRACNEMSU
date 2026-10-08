<?php require 'db.php'; if(empty($_SESSION['u'])){header('Location: login.php');exit;}
$u=$_SESSION['u']; $t=isStaff(); $err='';
if($_SERVER['REQUEST_METHOD']=='POST'){
  if(!$t && isset($_POST['subject'])){ $s=trim($_POST['subject']); $m=trim($_POST['message']??'');
    if($s===''||$m==='') $err='Subject and message are required.';
    else { $pdo->prepare("INSERT INTO complaints(student_id,subject,message) VALUES(?,?,?)")->execute([$u['id'],mb_substr($s,0,120),$m]); header('Location: complaints.php'); exit; } }
  if($t && isset($_POST['id'])){ $st=in_array($_POST['status']??'',['pending','in_review','resolved'],true)?$_POST['status']:'pending'; $cid=(int)$_POST['id']; $resp=mb_substr(trim($_POST['response']??''),0,500);
    $o=$pdo->prepare("SELECT student_id,subject,status,response FROM complaints WHERE id=?"); $o->execute([$cid]); $old=$o->fetch();
    $pdo->prepare("UPDATE complaints SET status=?,response=? WHERE id=?")->execute([$st,$resp,$cid]);
    if($old && ($old['status']!==$st || (string)$old['response']!==$resp)) notify($pdo,$old['student_id'],'Your complaint "'.$old['subject'].'" was updated: '.str_replace('_',' ',$st).($resp!==''?'. Response: '.$resp:''),'complaints.php');
    header('Location: complaints.php'); exit; }
  if(!$t && isset($_POST['rate'])){ $rt=max(1,min(5,(int)$_POST['rate']));
    $pdo->prepare("UPDATE complaints SET rating=?,rate_note=? WHERE id=? AND student_id=? AND status='resolved' AND rating IS NULL")->execute([$rt,mb_substr(trim($_POST['rate_note']??''),0,255),(int)$_POST['cid'],$u['id']]); header('Location: complaints.php'); exit; } }
if($t) $rows=$pdo->query("SELECT c.*,u.name FROM complaints c JOIN users u ON u.id=c.student_id ORDER BY c.status='resolved',c.id DESC")->fetchAll();
else { $q=$pdo->prepare("SELECT c.*,u.name FROM complaints c JOIN users u ON u.id=c.student_id WHERE c.student_id=? ORDER BY c.id DESC"); $q->execute([$u['id']]); $rows=$q->fetchAll(); }
$col=['pending'=>'late','in_review'=>'excused','resolved'=>'present'];
top('Complaints'); if(!$t): ?>
<div class="card"><h3>💬 Submit a Complaint</h3><?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><input name="subject" placeholder="Subject (e.g. wrong attendance mark)" required maxlength="120"><textarea name="message" placeholder="Describe your complaint" required rows="4"></textarea><button class="b present" style="padding:10px 16px">Submit</button></form></div>
<?php endif; ?>
<div class="card"><h3><?=$t?'Manage Complaints':'My Complaints'?></h3>
<?php foreach($rows as $r): ?><div style="border-top:1px solid var(--line);padding:12px 0">
<b><?=h($r['subject'])?></b> <span class="tag <?=$col[$r['status']]?>"><?=h(str_replace('_',' ',$r['status']))?></span><br><small><?=$t?h($r['name']).' · ':''?><?=h($r['created'])?></small>
<p style="white-space:pre-wrap"><?=h($r['message'])?></p>
<?php if($t): ?><form method="post"><input type="hidden" name="id" value="<?=$r['id']?>"><input name="response" placeholder="Response / action taken" maxlength="500" value="<?=h($r['response'])?>">
<select name="status" style="width:auto"><?php foreach(['pending','in_review','resolved'] as $s): ?><option value="<?=$s?>"<?=$r['status']==$s?' selected':''?>><?=str_replace('_',' ',$s)?></option><?php endforeach; ?></select> <button class="b present">Update</button></form>
<?php else: if($r['response']): ?><p><b>Response:</b> <?=h($r['response'])?></p><?php endif;
 if($r['rating']): ?><p>Your rating: <?=str_repeat('⭐',(int)$r['rating'])?><?=$r['rate_note']?' · '.h($r['rate_note']):''?></p>
 <?php elseif($r['status']=='resolved'): ?><form method="post"><input type="hidden" name="cid" value="<?=$r['id']?>">Rate the response: <select name="rate" style="width:auto"><?php for($i=5;$i>=1;$i--) echo '<option value="'.$i.'">'.str_repeat('⭐',$i).'</option>'; ?></select> <input name="rate_note" placeholder="Comment (optional)" maxlength="255"><button class="b present">Submit rating</button></form><?php endif; endif; ?>
<?php if($t && $r['rating']): ?><p>Student rating: <?=str_repeat('⭐',(int)$r['rating'])?><?=$r['rate_note']?' · '.h($r['rate_note']):''?></p><?php endif; ?></div>
<?php endforeach; if(!$rows) echo '<p>No complaints.</p>'; ?></div>
<?php bottom();
