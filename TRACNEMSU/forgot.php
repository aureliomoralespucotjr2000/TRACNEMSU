<?php require 'db.php'; $msg=''; $err='';
if($_POST){ $us=trim($_POST['username']??''); $q=$pdo->prepare("SELECT id,email FROM users WHERE username=? AND status='active'"); $q->execute([$us]); $u=$q->fetch();
  if($u){ $tok=bin2hex(random_bytes(16)); $pdo->prepare("INSERT INTO pw_resets(user_id,token,expires) VALUES(?,?,?)")->execute([$u['id'],$tok,date('Y-m-d H:i:s',time()+3600)]);
    if($u['email'] && MAIL_ENABLED){ $url=(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/reset.php?t='.$tok; sendMail($u['email'],'TRACNEMSU password reset',"Click this link to set a new password (valid for 1 hour):\n$url\n\nIf you did not ask for this, ignore this email."); } }
  $msg='If the username exists, a reset link was emailed (if the account has an email). Your teacher or admin can also give you a temporary password.'; }
top('Forgot password','loginpage'); ?>
<div class="hero"><div class="hero-l"><div class="brand"><img src="assets/logo.png"><div><b><?=APP?></b><small>Attendance Management System</small></div></div></div>
<div class="hero-r"><div class="card login-card"><h2>Forgot password</h2><p class="sub">Enter your username.</p>
<?php if($msg): ?><p style="color:#2a9d8f"><b><?=h($msg)?></b></p><?php endif; ?>
<form method="post"><label>Username</label><input name="username" required autofocus><button class="b present big">Request reset</button></form>
<p class="sub" style="text-align:center"><a href="login.php">Back to login</a></p></div></div></div>
<?php bottom();
