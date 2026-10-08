<?php require 'db.php'; need('admin');
$n=function($sql) use($pdo){ return (int)$pdo->query($sql)->fetchColumn(); };
$cards=[
 ['Students',$n("SELECT COUNT(*) FROM users WHERE role='student' AND status='active'"),'👥','#2a9d8f','students.php'],
 ['Staff',$n("SELECT COUNT(*) FROM users WHERE role='teacher'"),'👨‍🏫','#457b9d','staff.php'],
 ['Branches',$n("SELECT COUNT(*) FROM branches"),'🏫','#1d3557','branches.php'],
 ['Pending registrations',$n("SELECT COUNT(*) FROM users WHERE role='student' AND status='pending'"),'🆕','#e9a100','students.php'],
 ['Pending leaves',$n("SELECT COUNT(*) FROM leaves WHERE status='pending'"),'📝','#e9a100','leaves.php'],
 ['Open complaints',$n("SELECT COUNT(*) FROM complaints WHERE status<>'resolved'"),'💬','#d62828','complaints.php'],
];
$br=$pdo->query("SELECT b.name,b.address,(SELECT COUNT(*) FROM users u WHERE u.branch_id=b.id AND u.role='student') s,(SELECT COUNT(*) FROM users u WHERE u.branch_id=b.id AND u.role='teacher') t FROM branches b ORDER BY b.name")->fetchAll();
$cm=$pdo->query("SELECT c.*,u.name FROM complaints c JOIN users u ON u.id=c.student_id ORDER BY c.id DESC LIMIT 5")->fetchAll();
$col=['pending'=>'late','in_review'=>'excused','resolved'=>'present'];
top('Admin'); ?><h2 class="greet"><?=greet()?>, <?=h(explode(' ',$_SESSION['u']['name'])[0])?>! 🛡</h2><p class="tip"><?=date('l, M d')?> · Administrator panel: manage branches, staff, students, attendance, leaves and complaints.</p>
<div class="stats2">
<?php foreach($cards as $c): ?><a href="<?=$c[4]?>" style="text-decoration:none;color:inherit"><div class="sc" style="--c:<?=$c[3]?>"><div class="sc-h"><span><?=h($c[0])?></span><i><?=$c[2]?></i></div><b><?=$c[1]?></b></div></a><?php endforeach; ?>
</div><br>
<div class="card"><h3>Quick actions</h3><a class="b present" href="branches.php">🏫 Add / Modify Branch</a> <a class="b excused" href="staff.php">👨‍🏫 Add Staff</a> <a class="b late" href="students.php">👥 Add Student</a> <a class="b present" href="attendance.php">🗓 Add / Modify Attendance</a> <a class="b excused" href="complaints.php">💬 Manage Complaints</a></div>
<div class="card"><h3>Branches overview</h3><table><tr><th>Branch</th><th>Address</th><th>Students</th><th>Staff</th></tr>
<?php foreach($br as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['address'])?></td><td><?=$r['s']?></td><td><?=$r['t']?></td></tr><?php endforeach; ?></table></div>
<div class="card"><h3>Latest complaints</h3><table><tr><th>Student</th><th>Subject</th><th>Status</th></tr>
<?php foreach($cm as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['subject'])?></td><td><span class="tag <?=$col[$r['status']]?>"><?=h($r['status'])?></span></td></tr><?php endforeach; if(!$cm) echo '<tr><td colspan="3">No complaints yet.</td></tr>'; ?></table></div>
<?php bottom();
