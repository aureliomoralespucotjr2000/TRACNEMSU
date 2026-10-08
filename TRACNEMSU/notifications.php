<?php require 'db.php'; need('teacher');
$rows=$pdo->query("SELECT * FROM parent_log ORDER BY id DESC LIMIT 100")->fetchAll();
top('Parent Log'); ?>
<div class="card"><h3>Parent Alerts (simulated, no real SMS sent)</h3><table><tr><th>When</th><th>Message</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['created'])?></td><td><?=h($r['message'])?></td></tr><?php endforeach; ?>
<?php if(!$rows) echo '<tr><td colspan="2">No alerts yet. Mark a student Absent to create one.</td></tr>'; ?></table></div>
<?php bottom();
