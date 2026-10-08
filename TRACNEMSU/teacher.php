<?php require 'db.php'; need('teacher');
$today=date('Y-m-d');
if($_SERVER['REQUEST_METHOD']=='POST'){ $a=$_POST['act']??'';
  if($a=='post'){ if(!(curPost($pdo)['open']??false)) $pdo->prepare("INSERT INTO att_posts(post_date,message,opens,closes) VALUES(?,?,?,?)")->execute([$today,trim($_POST['message']??''),date('Y-m-d H:i:s'),date('Y-m-d H:i:s',time()+POST_MINUTES*60)]); }
  elseif($a=='close'){ $pdo->prepare("UPDATE att_posts SET closed=1 WHERE post_date=?")->execute([$today]); }
  elseif($a=='absent'){ if(!(curPost($pdo)['open']??false)){ $ids=$pdo->query("SELECT u.id FROM users u LEFT JOIN attendance a ON a.student_id=u.id AND a.att_date='$today' WHERE u.role='student' AND u.status='active' AND a.id IS NULL")->fetchAll(PDO::FETCH_COLUMN); foreach($ids as $i) markStatus($pdo,$i,$today,'absent'); } }
  elseif(isset($_POST['sid'],$_POST['status'])) markStatus($pdo,(int)$_POST['sid'],$today,$_POST['status']);
  header('Location: teacher.php'); exit; }
$post=curPost($pdo);
$rows=todayRows($pdo); $c=todayCounts($rows);
$tr=$pdo->query("SELECT att_date d,ROUND(SUM(status<>'absent')/COUNT(*)*100) r FROM attendance WHERE att_date>=CURDATE()-INTERVAL 29 DAY GROUP BY att_date ORDER BY att_date")->fetchAll();
$pts=[]; $nn=count($tr); foreach($tr as $k=>$r){ $x=$nn>1?20+$k*(560/($nn-1)):300; $pts[]=round($x).','.round(130-$r['r']*1.1); }
top('Teacher'); ?><h2 class="greet"><?=greet()?>, <?=h(explode(' ',$_SESSION['u']['name'])[0])?>! 👋</h2><?=tip()?>
<div class="card"><h3>📢 Attendance Post</h3>
<?php if($post && $post['open']): ?><p>Open now. Students can click Present. Closes in <b class="cd" data-left="<?=$post['left']?>"><?=mmss($post['left'])?></b></p>
<?php if($post['message']): ?><p><i><?=h($post['message'])?></i></p><?php endif; ?>
<form method="post"><button name="act" value="close" class="b absent" style="padding:10px 16px">Close now</button></form>
<?php else: ?><?php if($post): ?><p>🔒 Today's attendance is <b>closed</b>.</p><?php endif; ?>
<form method="post"><input name="message" placeholder="Message (optional), e.g. Good morning! Click Present." maxlength="200"><button name="act" value="post" class="b present" style="padding:10px 16px"><?=$post?'Post again':'Post attendance'?> (<?=POST_MINUTES?> min)</button></form>
<?php if($post && $c['none']>0): ?><form method="post" onsubmit="return confirm('Mark everyone who did not check in as Absent? Parents will be notified.')"><button name="act" value="absent" class="b absent" style="padding:10px 16px;margin-top:8px">Mark <?=$c['none']?> remaining as Absent</button></form><?php endif; ?>
<?php endif; ?>
<?php if($post): ?><h4>Responses (live)</h4><div id="resp"><?=respHtml($pdo)?></div><?php endif; ?></div>
<div class="card"><h3>Live counters</h3><div id="cnt"><?=countersHtml($c)?></div></div>
<div class="row">
<div class="card" style="flex:none"><h3>Today</h3><div id="pie"><?=pieHtml($c)?></div></div>
<div class="card" style="flex:1;min-width:280px"><h3>Class attendance, last 30 days</h3>
<?php if($pts): ?><svg viewBox="0 0 600 160" style="width:100%"><line x1="20" y1="130" x2="580" y2="130" stroke="#8886"/>
<polyline points="<?=implode(' ',$pts)?>" fill="none" stroke="#2a9d8f" stroke-width="3"/>
<?php foreach($pts as $p): [$px,$py]=explode(',',$p); ?><circle cx="<?=$px?>" cy="<?=$py?>" r="3.5" fill="#2a9d8f"/><?php endforeach; ?>
<text x="20" y="150" font-size="12" fill="currentColor"><?=$tr[0]['d']?></text><text x="580" y="150" font-size="12" fill="currentColor" text-anchor="end"><?=$tr[$nn-1]['d']?></text></svg>
<?php else: ?><p>No attendance records yet. Fill the chart with sample data or start taking attendance.</p><a class="b present" href="demo.php" style="display:inline-block;text-decoration:none;padding:10px 16px">🧪 Generate demo data</a><?php endif; ?></div></div>
<div class="card"><h3>QR Scanner</h3><div id="reader" style="max-width:320px"></div><p id="msg"><b></b></p></div>
<div class="card"><h3>Students</h3><div id="srows"><?=rowsHtml($rows)?></div>
<p><a href="export.php">Download this month (CSV, opens in Excel)</a></p></div>
<script src="assets/html5-qrcode.min.js"></script>
<script>
let last='',t=0;
new Html5QrcodeScanner("reader",{fps:10,qrbox:200}).render(code=>{
  if(code===last && Date.now()-t<4000) return; last=code; t=Date.now();
  fetch('scan.php',{method:'POST',body:new URLSearchParams({code})}).then(r=>r.json()).then(j=>{
    document.querySelector('#msg b').textContent=j.msg; setTimeout(()=>location.reload(),1500); });
});
</script>
<?php if($post && $post['open']){ echo countdownJs(); echo '<script>setInterval(function(){fetch("live.php").then(function(r){return r.json()}).then(function(j){["resp","cnt","pie","srows"].forEach(function(k){var e=document.getElementById(k);if(e&&j[k]!=null)e.innerHTML=j[k]})})},5000)</script>'; } bottom();
