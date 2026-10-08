<?php require 'db.php'; $err=[]; $done=false; $v=array_map('trim',$_POST); $f=fn($k)=>h($v[$k]??'');
if($_POST){
  foreach(['name'=>'Full name','student_no'=>'Student ID number','course'=>'Course','year_level'=>'Year level','section'=>'Section','email'=>'Email','contact'=>'Contact number','parent_name'=>'Parent/guardian name','parent_phone'=>'Parent phone','username'=>'Username'] as $k=>$l) if(($v[$k]??'')==='') $err[]="$l is required.";
  if(!preg_match('/^[A-Za-z0-9-]{4,20}$/',$v['student_no']??'')) $err[]='Student ID: 4-20 letters, numbers or dash.';
  if(($v['parent_email']??'')!=='' && !filter_var($v['parent_email'],FILTER_VALIDATE_EMAIL)) $err[]='Parent email is not valid.';
  if(($v['email']??'')!=='' && !filter_var($v['email'],FILTER_VALIDATE_EMAIL)) $err[]='Email is not valid.';
  foreach(['contact'=>'Contact','parent_phone'=>'Parent'] as $k=>$l) if(!preg_match('/^09\d{9}$/',$v[$k]??'')) $err[]="$l number must be 11 digits starting with 09.";
  if(!preg_match('/^\w{4,20}$/',$v['username']??'')) $err[]='Username: 4-20 letters, numbers or underscore.';
  if(strlen($_POST['password']??'')<6) $err[]='Password must be at least 6 characters.';
  if(($_POST['password']??'')!==($_POST['password2']??'')) $err[]='Passwords do not match.';
  if(!$err){ try{
    $pdo->prepare("INSERT INTO users(code,name,username,password,role,status,email,contact,course,year_level,section,parent_name,parent_phone,parent_email,branch_id) VALUES(?,?,?,?,'student','pending',?,?,?,?,?,?,?,?,?)")
      ->execute([$v['student_no'],$v['name'],$v['username'],password_hash($_POST['password'],PASSWORD_DEFAULT),$v['email'],$v['contact'],$v['course'],$v['year_level'],$v['section'],$v['parent_name'],$v['parent_phone'],($v['parent_email']??'')?:null,((int)($v['branch_id']??0))?:null]);
    $done=true; }catch(Exception $e){ $err[]='That username or student ID is already registered.'; } } }
function opts($list,$sel){ foreach($list as $o) echo '<option'.($o==$sel?' selected':'').'>'.h($o).'</option>'; }
top('Register','loginpage'); ?>
<div class="reg-top"><div class="brand"><img src="assets/logo.png"><div><b><?=APP?></b><small>Attendance Management System</small></div></div>
<span class="pill">● <?=h(strtoupper(SCHOOL))?></span><h1 class="reg-h">Create your <span>account</span></h1><p>Fill in your details. Your teacher will approve your account.</p></div>
<div class="card reg-card">
<?php if($done): ?><div style="text-align:center;padding:20px"><div style="font-size:50px">✅</div><h3>Registration submitted!</h3><p>Your teacher must approve your account first. After that you can log in.</p><a href="login.php">Back to login</a></div>
<?php else: if($err): ?><div style="background:#d6282822;border-left:4px solid #d62828;padding:10px;border-radius:6px;margin-bottom:10px"><?php foreach($err as $e) echo '• '.h($e).'<br>'; ?></div><?php endif; ?>
<form method="post"><h4>👤 Personal Information</h4>
<label>Full name</label><input name="name" placeholder="Last name, First name M." value="<?=$f('name')?>" required>
<div class="g2"><div><label>Student ID number</label><input name="student_no" placeholder="e.g. 2022-00123" value="<?=$f('student_no')?>" required></div>
<div><label>Email</label><input type="email" name="email" placeholder="you@email.com" value="<?=$f('email')?>" required></div>
<div><label>Course</label><select name="course"><?php opts(['BS Information Technology','BS Computer Science','BS Education','BS Business Administration','Other'],$v['course']??''); ?></select></div>
<div><label>Year level</label><select name="year_level"><?php opts(['1st Year','2nd Year','3rd Year','4th Year'],$v['year_level']??''); ?></select></div>
<div><label>Branch / Campus</label><select name="branch_id"><option value="">Select branch</option><?php foreach($pdo->query("SELECT id,name FROM branches ORDER BY name") as $b): ?><option value="<?=$b['id']?>"<?=($v['branch_id']??'')==$b['id']?' selected':''?>><?=h($b['name'])?></option><?php endforeach; ?></select></div>
<div><label>Section</label><input name="section" placeholder="e.g. 4A" value="<?=$f('section')?>" required></div>
<div><label>Your contact number</label><input name="contact" placeholder="09XXXXXXXXX" maxlength="11" value="<?=$f('contact')?>" required></div></div>
<h4>👪 Parent / Guardian</h4><div class="g2"><div><label>Name</label><input name="parent_name" value="<?=$f('parent_name')?>" required></div>
<div><label>Parent email (optional, for absence alerts)</label><input type="email" name="parent_email" value="<?=$f('parent_email')?>"></div>
<div><label>Phone (for absence alerts)</label><input name="parent_phone" placeholder="09XXXXXXXXX" maxlength="11" value="<?=$f('parent_phone')?>" required></div></div>
<h4>🔐 Account</h4><label>Username</label><input name="username" value="<?=$f('username')?>" required>
<div class="g2"><div><label>Password (min 6)</label><input class="pw" id="pw" type="password" name="password" required></div>
<div><label>Confirm password</label><input class="pw" type="password" name="password2" required></div></div>
<div style="height:5px;background:#8883;border-radius:4px"><div id="meter" style="height:5px;width:0;border-radius:4px"></div></div>
<p><label><input type="checkbox" style="width:auto" onclick="[...document.querySelectorAll('.pw')].forEach(i=>i.type=this.checked?'text':'password')"> Show passwords</label></p>
<button class="b present big">Submit Registration</button></form>
<p style="text-align:center">Already registered? <a href="login.php">Login</a></p>
<script>const p=document.getElementById('pw'),m=document.getElementById('meter');
p.oninput=()=>{let s=0;if(p.value.length>=6)s++;if(p.value.length>=10)s++;if(/[A-Z]/.test(p.value)&&/\d/.test(p.value))s++;m.style.width=s*33.3+'%';m.style.background=['#d62828','#e9a100','#2a9d8f'][Math.max(s-1,0)]};</script>
<?php endif; ?></div>
<?php bottom();
