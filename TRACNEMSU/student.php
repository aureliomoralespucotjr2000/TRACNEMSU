<?php require 'db.php'; need('student');
$u=$_SESSION['u'];
if(isset($_POST['checkin'])){ $p=curPost($pdo); if($p && $p['open']) checkin($pdo,$u['id'],$p); header('Location: student.php'); exit; }
$q=$pdo->prepare("SELECT * FROM attendance WHERE student_id=? ORDER BY att_date DESC"); $q->execute([$u['id']]); $all=$q->fetchAll();
$streak=0; foreach($all as $a){ if($a['status']=='present') $streak++; else break; }
$pts=0; $early=false; $m=0; $ok=0; $mon=date('Y-m');
foreach($all as $a){ $pts+=['present'=>10,'late'=>5,'excused'=>0,'absent'=>0][$a['status']];
  if($a['time_in'] && $a['time_in']<'07:30:00') $early=true;
  if(substr($a['att_date'],0,7)==$mon){ $m++; if($a['status']!='absent') $ok++; } }
$rate=$m?round($ok/$m*100):0;
$badges=[]; if($streak>=3)$badges[]='3-Day Streak'; if($streak>=7)$badges[]='7-Day Streak'; if($early)$badges[]='Early Bird';
$done=$pdo->prepare("SELECT status,time_in FROM attendance WHERE student_id=? AND att_date=?"); $done->execute([$u['id'],date('Y-m-d')]); $done=$done->fetch(); $p=curPost($pdo); $can=$p && $p['open'] && (!$done || $done['status']=='absent');
top('Student'); ?><h2 class="greet"><?=greet()?>, <?=h(explode(' ',$u['name'])[0])?>! 👋</h2><?=tip()?>
<?=announceHtml($pdo)?><?=inboxHtml($pdo,$u['id'])?>
<div class="card" style="border-left:5px solid <?=$can?'#e9a100':($done?($done['status']=='absent'?'#d62828':'#2a9d8f'):'#999')?>"><h3>📢 Today's Attendance</h3>
<?php if($done && !$can): ?><p>✅ You are marked <b><?=h(strtoupper($done['status']))?></b><?=$done['time_in']?' at '.date('g:i A',strtotime($done['time_in'])):''?>.</p>
<?php elseif($can): ?><?php if($done): ?><p>⚠ You are marked <b>ABSENT</b>. Click Present if you are here.</p><?php endif; ?><?php if($p['message']): ?><p><?=h($p['message'])?></p><?php endif; ?>
<p>Closes in <b class="cd" data-left="<?=$p['left']?>"><?=mmss($p['left'])?></b></p>
<form method="post"><button name="checkin" class="b present" style="padding:14px 30px;font-size:18px">✅ Present</button></form>
<?php elseif($p): ?><p>🔒 Attendance is closed. Please tell your teacher if you were present.</p>
<?php else: ?><p>No attendance post yet. Wait for your teacher to open it.</p><?php endif; ?></div>
<div class="card" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
<svg width="120" height="120" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="none" stroke="#e5e5e5" stroke-width="9"/>
<circle cx="50" cy="50" r="45" fill="none" stroke="#2a9d8f" stroke-width="9" stroke-linecap="round" stroke-dasharray="<?=$rate*2.83?> 283" transform="rotate(-90 50 50)"/>
<text x="50" y="56" text-anchor="middle" font-size="20" font-weight="bold"><?=$rate?>%</text></svg>
<div><h3>This month's attendance</h3><p>Streak: <b><?=$streak?></b> days &nbsp; Points: <b><?=$pts?></b></p>
<p><?php foreach($badges as $b) echo '<span class="tag excused">🏅 '.h($b).'</span> '; if(!$badges) echo 'No badges yet. Keep coming on time!'; ?></p>
</div></div>
<div class="card"><h3>My QR Code (show to teacher)</h3><div id="qr"></div><p><?=h($u['code'])?></p></div>
<div class="card"><h3>History</h3><table><tr><th>Date</th><th>Time</th><th>Status</th></tr>
<?php foreach(array_slice($all,0,30) as $a): ?><tr><td><?=$a['att_date']?></td><td><?=h($a['time_in'])?></td><td><span class="tag <?=$a['status']?>"><?=$a['status']?></span></td></tr><?php endforeach; ?></table></div>
<script src="assets/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qr'),{text:<?=json_encode((string)$u['code'],JSON_HEX_TAG)?>,width:150,height:150});</script>
<?php if($can) echo countdownJs(); echo pollJs(stateKey($pdo,$u['id'])); bottom();
