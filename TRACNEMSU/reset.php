<?php require 'db.php'; $t=$_GET['t']??''; $msg=''; $err='';
$q=$pdo->prepare("SELECT * FROM pw_resets WHERE token=? AND status='pending' AND expires>NOW()"); $q->execute([$t]); $r=$q->fetch();
if($r && $_POST){ $n=$_POST['new']??'';
  if(strlen($n)<6) $err='Password must be at least 6 characters.'; elseif($n!==($_POST['new2']??'')) $err='Passwords do not match.';
  else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($n,PASSWORD_DEFAULT),$r['user_id']]); $pdo->prepare("UPDATE pw_resets SET status='done' WHERE id=?")->execute([$r['id']]); $msg='Password changed. You can log in now.'; $r=null; } }
top('Reset password','loginpage'); ?>
<div class="reg-top"><h1 class="reg-h">Reset <span>password</span></h1></div>
<div class="card reg-card" style="max-width:440px">
<?php if($msg): ?><p style="color:#2a9d8f"><b><?=h($msg)?></b></p><a href="login.php">Go to login</a>
<?php elseif(!$r): ?><p>This link is invalid or expired.</p><a href="forgot.php">Request a new one</a>
<?php else: if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><label>New password (min 6)</label><input type="password" name="new" required><label>Confirm new password</label><input type="password" name="new2" required><button class="b present big">Save new password</button></form><?php endif; ?></div>
<?php bottom();
