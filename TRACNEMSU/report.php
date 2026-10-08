<?php require 'db.php'; need('teacher');
$m=preg_match('/^\d{4}-\d{2}$/',$_GET['m']??'')?$_GET['m']:date('Y-m');
$q=$pdo->prepare("SELECT u.name,COALESCE(SUM(a.status='present'),0) p,COALESCE(SUM(a.status='late'),0) l,COALESCE(SUM(a.status='excused'),0) e,COALESCE(SUM(a.status='absent'),0) a,COUNT(a.id) tot
 FROM users u LEFT JOIN attendance a ON a.student_id=u.id AND DATE_FORMAT(a.att_date,'%Y-%m')=? WHERE u.role='student' AND u.status='active' GROUP BY u.id ORDER BY u.name");
$q->execute([$m]); $rows=$q->fetchAll();
$q=$pdo->prepare("SELECT DAYOFWEEK(att_date) d,SUM(status='absent') a,SUM(status='late') l FROM attendance WHERE DATE_FORMAT(att_date,'%Y-%m')=? GROUP BY d");
$q->execute([$m]); $w=[]; foreach($q->fetchAll() as $r) $w[$r['d']]=$r;
$q=$pdo->prepare("SELECT b.name,COUNT(a.id) tot,COALESCE(SUM(a.status<>'absent'),0) ok FROM branches b LEFT JOIN users u ON u.branch_id=b.id AND u.role='student' AND u.status='active' LEFT JOIN attendance a ON a.student_id=u.id AND DATE_FORMAT(a.att_date,'%Y-%m')=? GROUP BY b.id ORDER BY b.name"); $q->execute([$m]); $bro=$q->fetchAll();
$days=[2=>'Mon',3=>'Tue',4=>'Wed',5=>'Thu',6=>'Fri']; $max=1; foreach($w as $r) $max=max($max,$r['a'],$r['l']);
top('Report'); ?>
<div class="printonly" style="text-align:center"><img src="assets/logo.png" style="height:80px"><h2><?=h(SCHOOL)?></h2><h3>Monthly Attendance Report: <?=date('F Y',strtotime($m.'-01'))?></h3></div>
<div class="card noprint"><button onclick="window.print()" class="b present" style="float:right;padding:8px 14px">🖨 Download PDF (Print → Save as PDF)</button><form method="get">Month: <input type="month" name="m" value="<?=$m?>" style="width:auto" onchange="this.form.submit()"></form></div>
<div class="card"><h3>Monthly Report (red = below 90%)</h3><table><tr><th>Student</th><th>Present</th><th>Late</th><th>Excused</th><th>Absent</th><th>Attendance %</th></tr>
<?php foreach($rows as $r): $pct=$r['tot']?round(($r['p']+$r['l']+$r['e'])/$r['tot']*100):0; ?>
<tr><td><?=h($r['name'])?></td><td><?=$r['p']?></td><td><?=$r['l']?></td><td><?=$r['e']?></td><td><?=$r['a']?></td>
<td <?=($r['tot']&&$pct<90)?'style="color:#d62828;font-weight:bold"':''?>><?=$r['tot']?$pct.'%':'-'?></td></tr><?php endforeach; ?></table></div>
<div class="card"><h3>Branch Comparison (attendance %)</h3>
<?php foreach($bro as $x): $p=$x['tot']?round($x['ok']/$x['tot']*100):0; ?>
<div style="display:flex;align-items:center;gap:8px;margin:8px 0"><b style="width:160px"><?=h($x['name'])?></b><div style="flex:1;background:#8883;border-radius:6px"><div style="background:<?=$p<90?'#d62828':'#2a9d8f'?>;height:16px;border-radius:6px;width:<?=$p?>%;min-width:2px;-webkit-print-color-adjust:exact;print-color-adjust:exact"></div></div><span style="width:90px;text-align:right"><?=$x['tot']?$p.'%':'no data'?></span></div>
<?php endforeach; if(!$bro) echo '<p>No branches.</p>'; ?></div>
<div class="card"><h3>Trend by Weekday</h3><p><span class="tag absent">Absent</span> <span class="tag late">Late</span></p>
<?php foreach($days as $k=>$n): $a=$w[$k]['a']??0; $l=$w[$k]['l']??0; ?>
<div style="display:flex;align-items:center;gap:8px;margin:6px 0"><b style="width:40px"><?=$n?></b><div style="flex:1">
<div style="background:#d62828;height:12px;width:<?=$a/$max*100?>%;min-width:2px"></div><div style="background:#e9a100;height:12px;margin-top:2px;width:<?=$l/$max*100?>%;min-width:2px"></div></div><span><?=$a?> / <?=$l?></span></div>
<?php endforeach; ?></div>
<div class="printonly" style="margin-top:50px">Prepared by: ______________________ &nbsp;&nbsp; Date: ____________</div>
<?php bottom();
