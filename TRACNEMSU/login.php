<?php require 'db.php';
$err='';
if($_POST){ $q=$pdo->prepare("SELECT * FROM users WHERE username=?"); $q->execute([trim($_POST['username'])]); $u=$q->fetch();
  if(!$u || !password_verify($_POST['password'],$u['password'])) $err='Wrong username or password';
  elseif(($u['status']??'active')!='active') $err='Your account is still waiting for teacher approval.';
  else { session_regenerate_id(true); $_SESSION['u']=['id'=>$u['id'],'name'=>$u['name'],'code'=>$u['code'],'role'=>$u['role']]; header('Location: '.($u['role']=='admin'?'admin.php':($u['role']=='teacher'?'teacher.php':'student.php'))); exit; } }
top('Login','loginpage'); ?>
<div class="hero">
 <div class="hero-l">
  <div class="brand"><img src="assets/logo.png"><div><b><?=APP?></b><small>Attendance Management System</small></div></div>
  <span class="pill">● <?=h(strtoupper(SCHOOL))?></span>
  <h1 class="hero-t">Automated Student Attendance System <span>Using QR Code and Web Technology</span> in NEMSU Cantilan Campus</h1>
  <p>One platform for online attendance posts, QR check-in, leave requests and parent alerts, built for NEMSU.</p>
  <div class="chips"><span>✅ Online Attendance</span><span>📷 QR Check-in</span><span>📝 Leave</span><span>📈 Reports</span><span>🔔 Parent Alerts</span><span>⚡ Real-time</span></div>
 </div>
 <div class="hero-r"><div class="card login-card">
  <h2>Login</h2><p class="sub">Welcome back! Please sign in.</p>
  <?php if($err): ?><div class="alert"><?=h($err)?></div><?php endif; ?>
  <form method="post"><label>Username</label><input name="username" placeholder="Enter your username" required autofocus>
  <label>Password</label><div class="pwrow"><input id="pw" name="password" type="password" placeholder="Enter your password" required><button type="button" class="eye" onclick="var p=document.getElementById('pw');p.type=p.type=='password'?'text':'password'">👁</button></div>
  <button class="b present big">Log In</button></form>
  <p class="sub" style="text-align:center"><a href="forgot.php">Forgot password?</a></p>
  <p class="sub" style="text-align:center">Don't have an account? <a href="register.php"><b>Sign Up</b></a></p>
 </div></div>
</div>
<?php bottom();
