<?php require 'db.php'; if(empty($_SESSION['u'])){header('Location: login.php');exit;}
$uid=$_SESSION['u']['id'];
$q=$pdo->prepare("SELECT * FROM inbox WHERE user_id=? ORDER BY id DESC LIMIT 100"); $q->execute([$uid]); $rows=$q->fetchAll();
$pdo->prepare("UPDATE inbox SET is_read=1 WHERE user_id=?")->execute([$uid]);
top('Notifications'); ?>
<div class="card"><h3>🔔 Notifications</h3>
<?php foreach($rows as $x): ?><div style="padding:8px 0;border-top:1px solid var(--line)"><?=$x['is_read']?'':'<span class="badge" style="float:none">new</span> '?><?php if($x['link']): ?><a href="<?=h($x['link'])?>"><?=h($x['message'])?></a><?php else: echo h($x['message']); endif; ?> <small><?=h($x['created'])?></small></div>
<?php endforeach; if(!$rows) echo '<p>No notifications.</p>'; ?></div>
<?php bottom();
