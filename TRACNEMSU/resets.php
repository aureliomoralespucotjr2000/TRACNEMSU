<?php require 'db.php'; need('teacher'); $new='';
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['uid'])){ $uid=(int)$_POST['uid'];
  $pw=substr(bin2hex(random_bytes(4)),0,8);
  $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role<>'admin'")->execute([password_hash($pw,PASSWORD_DEFAULT),$uid]);
  $pdo->prepare("UPDATE pw_resets SET status='done' WHERE user_id=?")->execute([$uid]);
  $n=$pdo->prepare("SELECT name FROM users WHERE id=?"); $n->execute([$uid]); $new=$n->fetchColumn().': '.$pw; }
$rows=$pdo->query("SELECT r.*,u.name,u.username,u.role FROM pw_resets r JOIN users u ON u.id=r.user_id WHERE r.status='pending' AND u.role<>'admin' ORDER BY r.id DESC LIMIT 50")->fetchAll();
top('Password Resets'); ?>
<div class="card"><h3>🔑 Password Reset Requests</h3>
<?php if($new): ?><p style="color:#2a9d8f"><b>Temporary password (give it to the user, shown once) → <?=h($new)?></b></p><?php endif; ?>
<table><tr><th>Name</th><th>Username</th><th>Requested</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?=h($r['name'])?></td><td><?=h($r['username'])?></td><td><?=h($r['created'])?></td>
<td><form method="post" onsubmit="return confirm('Set a temporary password for this user?')"><input type="hidden" name="uid" value="<?=$r['user_id']?>"><button class="b present">Give temporary password</button></form></td></tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan="4">No pending requests.</td></tr>'; ?></table>
<p class="tip">Tell the user to change this password after logging in (Change Password page).</p></div>
<?php bottom();
