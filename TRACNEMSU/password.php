<?php require 'db.php'; if(empty($_SESSION['u'])){header('Location: login.php');exit;}
$msg=''; $err='';
if($_POST){ $q=$pdo->prepare("SELECT password FROM users WHERE id=?"); $q->execute([$_SESSION['u']['id']]); $hash=$q->fetchColumn(); $new=$_POST['new']??'';
  if(!password_verify($_POST['old']??'',$hash)) $err='Current password is wrong.';
  elseif(strlen($new)<6) $err='New password must be at least 6 characters.';
  elseif($new!==($_POST['new2']??'')) $err='New passwords do not match.';
  else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['u']['id']]); $msg='Password changed successfully.'; } }
top('Change Password'); ?>
<div class="card" style="max-width:480px"><h3>🔑 Change Password</h3>
<?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?><?php if($msg): ?><p style="color:#2a9d8f"><b><?=h($msg)?></b></p><?php endif; ?>
<form method="post"><label>Current password</label><input type="password" name="old" required><label>New password (min 6)</label><input type="password" name="new" required><label>Confirm new password</label><input type="password" name="new2" required>
<button class="b present" style="padding:10px 16px;margin-top:8px">Update password</button></form></div>
<?php bottom();
