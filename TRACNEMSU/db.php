<?php
session_start();
require_once __DIR__.'/mail.php';
date_default_timezone_set('Asia/Manila');
$pdo = new PDO('mysql:host=localhost;dbname=tracnemsu;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
try{ $pdo->query("SELECT status,email,contact,course,year_level,section,parent_name,parent_phone FROM users LIMIT 1"); }catch(Exception $e){
  foreach(["status ENUM('active','pending') DEFAULT 'active'","email VARCHAR(100) NULL","contact VARCHAR(30) NULL","course VARCHAR(60) NULL","year_level VARCHAR(20) NULL","section VARCHAR(20) NULL","parent_name VARCHAR(100) NULL","parent_phone VARCHAR(30) NULL"] as $c){ try{ $pdo->exec("ALTER TABLE users ADD $c"); }catch(Exception $x){} } }
try{ $pdo->exec("UPDATE users SET name='Teacher' WHERE username='teacher' AND name='Teacher Demo'"); }catch(Exception $e){}
try{ $pdo->exec("CREATE TABLE IF NOT EXISTS att_posts(id INT AUTO_INCREMENT PRIMARY KEY,post_date DATE,message VARCHAR(200) NULL,opens DATETIME,closes DATETIME,closed TINYINT DEFAULT 0)"); }catch(Exception $e){}
// ---- v2 upgrade (Admin, Branches, Complaints). Runs by itself, safe to repeat. ----
try{ $pdo->query("SELECT branch_id FROM users LIMIT 1"); }catch(Exception $e){ try{ $pdo->exec("ALTER TABLE users ADD branch_id INT NULL"); }catch(Exception $x){} }
try{ $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','teacher','student') NOT NULL"); }catch(Exception $e){}
try{ $pdo->exec("CREATE TABLE IF NOT EXISTS branches(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) UNIQUE,address VARCHAR(200) NULL,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
try{ $pdo->exec("CREATE TABLE IF NOT EXISTS complaints(id INT AUTO_INCREMENT PRIMARY KEY,student_id INT,subject VARCHAR(120),message TEXT,status ENUM('pending','in_review','resolved') DEFAULT 'pending',response VARCHAR(500) NULL,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
try{ if(!$pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn()) $pdo->exec("INSERT INTO branches(name,address) VALUES('Main Campus','Tandag City, Surigao del Sur'),('Cantilan Campus','Cantilan, Surigao del Sur')"); }catch(Exception $e){}
try{ if(!$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) $pdo->prepare("INSERT INTO users(code,name,username,password,role,status) VALUES(NULL,'Administrator','admin',?,'admin','active')")->execute([password_hash('admin123',PASSWORD_DEFAULT)]); }catch(Exception $e){}
try{ $pdo->query("SELECT parent_email FROM users LIMIT 1"); }catch(Exception $e){ try{ $pdo->exec("ALTER TABLE users ADD parent_email VARCHAR(100) NULL"); }catch(Exception $x){} }
const SCHOOL = 'North Eastern Mindanao State University'; // change this
const APP = 'TRACNEMSU'; // system name
const POST_MINUTES = 30;
// ---- Gmail alerts (free). Use a Gmail App Password, NOT your normal password. ----
const MAIL_ENABLED = false;            // change to true after filling the two lines below
const MAIL_USER = 'yourname@gmail.com';
const MAIL_PASS = 'xxxxxxxxxxxxxxxx';  // 16-character App Password
const MAIL_NAME = 'TRACNEMSU';
const LATE_AFTER_MIN = 0; // 0 = clicking Present while the post is open is always PRESENT. Example: 15 = late after 15 minutes // how long an attendance post stays open
const LATE_TIME = '08:00:00'; // arrive after this = Late
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }
function need($role){ $r=$_SESSION['u']['role']??''; // 'teacher' pages are also open to the admin
  if($r==='' || ($r!==$role && !($role==='teacher' && $r==='admin'))){ header('Location: login.php'); exit; } }
function isStaff(){ return in_array($_SESSION['u']['role']??'',['teacher','admin'],true); }
function home(){ $r=$_SESSION['u']['role']??''; return $r=='admin'?'admin.php':($r=='teacher'?'teacher.php':($r=='student'?'student.php':'login.php')); }
function checkin($pdo,$sid,$post=null){
  $now=date('H:i:s'); $st=$now<=LATE_TIME?'present':'late';
  if($post) $st=(LATE_AFTER_MIN>0 && time()-strtotime($post['opens'])>LATE_AFTER_MIN*60)?'late':'present'; // student clicked Present on an attendance post
  $q=$pdo->prepare("INSERT INTO attendance(student_id,att_date,time_in,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE time_in=IF(status='absent',VALUES(time_in),time_in),status=IF(status='absent',VALUES(status),status)");
  $q->execute([$sid,date('Y-m-d'),$now,$st]);
  return $q->rowCount() ? $st : 'already';
}
function top($title,$cls=''){ echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' | '.APP.'</title><link rel="stylesheet" href="style.css?v='.filemtime(__DIR__.'/style.css').'"><link rel="icon" href="assets/logo.png"><script>document.documentElement.dataset.theme=localStorage.getItem("theme")||"light";function tg(){var t=document.documentElement.dataset.theme=="dark"?"light":"dark";document.documentElement.dataset.theme=t;localStorage.setItem("theme",t)}function tm(){var l=document.querySelector(".layout");l.classList.toggle("mopen");localStorage.setItem("menu",l.classList.contains("mopen")?"1":"0")}document.addEventListener("DOMContentLoaded",function(){var l=document.querySelector(".layout");if(l&&document.getElementById("mp")&&localStorage.getItem("menu")=="1")l.classList.add("mopen")})</script></head><body class="'.$cls.'"><div class="bar"><b><img src="assets/logo.png" class="logo">'.h(SCHOOL).'</b><span>'.(isset($_SESSION['u'])?h($_SESSION['u']['name']).' ':'').'<button class="b" style="background:#ffffff33" onclick="tg()">🌓</button>'.(isset($_SESSION['u'])?' <a href="logout.php">Logout</a>':'').'</span></div>'.side().'<div class="layout solo">'.side(true).'<div class="wrap">'; }
function bottom(){ echo '</div></div></body></html>'; }

function side($panel=false){ global $pdo; if(empty($_SESSION['u'])) return ''; $r=$_SESSION['u']['role']; $cur=basename($_SERVER['SCRIPT_NAME']); $bd=[];
  if($r=='teacher' || $r=='admin'){ $bd['leaves.php']=(int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status='pending'")->fetchColumn(); $bd['students.php']=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND status='pending'")->fetchColumn(); $bd['complaints.php']=(int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn(); $bd['resets.php']=(int)$pdo->query("SELECT COUNT(*) FROM pw_resets WHERE status='pending'")->fetchColumn();
    $l=[]; if($r=='admin') $l+=['admin.php'=>'🛡 Admin Home','branches.php'=>'🏫 Branches','staff.php'=>'👨‍🏫 Staff'];
    $l+=['teacher.php'=>'📊 Dashboard','attendance.php'=>'🗓 Attendance Records','leaves.php'=>'📝 Leaves','announcements.php'=>'📣 Announcements','classes.php'=>'📚 Classes','complaints.php'=>'💬 Complaints','resets.php'=>'🔑 Password Resets','report.php'=>'📈 Report','students.php'=>'👥 Students','notifications.php'=>'🔔 Parent Log','demo.php'=>'🧪 Data']; }
  else { $bd['inbox.php']=unreadCount($pdo,$_SESSION['u']['id']); $l=['student.php'=>'🏠 Home','announcements.php'=>'📣 Announcements','inbox.php'=>'🔔 Notifications','leaves.php'=>'📝 Leave Request','complaints.php'=>'💬 Complaint']; }
  $l['password.php']='🔑 Change Password';
  if(!$panel) return '<nav class="menu noprint"><button type="button" class="menubtn" onclick="tm()">☰ Menu'.(array_sum($bd)?' <span class="badge">'.array_sum($bd).'</span>':'').'</button></nav>';
  $s='<aside id="mp" class="mpanel noprint">'; foreach($l as $k=>$v) $s.='<a href="'.$k.'"'.($k==$cur?' class="on"':'').'>'.$v.(!empty($bd[$k])?' <span class="badge">'.$bd[$k].'</span>':'').'</a>';
  return $s.'</aside>'; }
function markStatus($pdo,$sid,$date,$status,$time=null){
  if(!in_array($status,['present','late','excused','absent'],true)) return;
  $o=$pdo->prepare("SELECT status FROM attendance WHERE student_id=? AND att_date=?"); $o->execute([$sid,$date]); $old=$o->fetchColumn();
  $pdo->prepare("INSERT INTO attendance(student_id,att_date,time_in,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)")->execute([$sid,$date,$time,$status]);
  if($status=='absent' && $old!='absent'){ $n=$pdo->prepare("SELECT name,parent_phone,parent_email FROM users WHERE id=?"); $n->execute([$sid]); $s=$n->fetch();
    if(!empty($s['parent_email'])) sendMail($s['parent_email'],'Absence alert: '.$s['name'],"Good day. Your child {$s['name']} was marked ABSENT on $date.\n\n".SCHOOL);
    $pdo->prepare("INSERT INTO parent_log(student_id,message) VALUES(?,?)")->execute([$sid,"To ".($s['parent_phone']?:'(no number)').": Your child {$s['name']} was marked ABSENT on $date."]); } }

// Today's attendance post (or null). 'left' = seconds remaining, 'open' = students can still click Present.
function curPost($pdo){ $q=$pdo->prepare("SELECT * FROM att_posts WHERE post_date=? ORDER BY id DESC LIMIT 1"); $q->execute([date('Y-m-d')]); $p=$q->fetch(); if(!$p) return null;
  $p['left']=$p['closed']?0:max(0,strtotime($p['closes'])-time()); $p['open']=$p['left']>0; return $p; }
function mmss($s){ return sprintf('%02d:%02d',intdiv($s,60),$s%60); }
function countdownJs(){ return '<script>(function(){var e=document.querySelectorAll(".cd");if(!e.length)return;var end=[];e.forEach(function(x,i){end[i]=Date.now()+x.dataset.left*1000});
function p(n){return ("0"+n).slice(-2)}var t=setInterval(function(){var d=false;e.forEach(function(x,i){var s=Math.max(0,Math.round((end[i]-Date.now())/1000));x.textContent=p(Math.floor(s/60))+":"+p(s%60);if(s<=0)d=true});
if(d){clearInterval(t);setTimeout(function(){location.reload()},800)}},1000)})();</script>'; }
// Live list of who answered today's attendance (used by teacher.php and live.php)
function respHtml($pdo){ $r=$pdo->prepare("SELECT u.name,a.status,a.time_in FROM users u LEFT JOIN attendance a ON a.student_id=u.id AND a.att_date=? WHERE u.role='student' AND u.status='active' ORDER BY (a.time_in IS NULL),a.time_in DESC,u.name"); $r->execute([date('Y-m-d')]); $rows=$r->fetchAll();
  $in=0; foreach($rows as $x) if($x['status'] && $x['status']!='absent') $in++; $tot=count($rows);
  $s='<p><b>'.$in.' / '.$tot.'</b> students responded</p><div style="height:8px;background:#8883;border-radius:6px"><div style="height:8px;border-radius:6px;background:#2a9d8f;width:'.($tot?round($in/$tot*100):0).'%;transition:width .4s"></div></div><table><tr><th>Student</th><th>Time</th><th>Status</th></tr>';
  foreach($rows as $x) $s.='<tr><td>'.h($x['name']).'</td><td>'.($x['time_in']?date('g:i:s A',strtotime($x['time_in'])):'-').'</td><td>'.($x['status']?'<span class="tag '.$x['status'].'">'.$x['status'].'</span>':'<span class="tag" style="background:#888">waiting</span>').'</td></tr>';
  return $s.'</table>'; }
// ---- Today's dashboard pieces (used by teacher.php and live.php) ----
function todayRows($pdo){ $q=$pdo->prepare("SELECT u.id,u.code,u.name,a.status,a.time_in FROM users u LEFT JOIN attendance a ON a.student_id=u.id AND a.att_date=? WHERE u.role='student' AND u.status='active' ORDER BY u.name"); $q->execute([date('Y-m-d')]); return $q->fetchAll(); }
function todayCounts($rows){ $c=['present'=>0,'late'=>0,'excused'=>0,'absent'=>0,'none'=>0]; foreach($rows as $r) $c[$r['status']?:'none']++; return $c; }
function countersHtml($c){ $t=max(1,array_sum($c)); $s='<div class="stats2">';
  foreach([['present','Present','✅','#2a9d8f'],['late','Late','⏰','#e9a100'],['excused','Excused','📝','#457b9d'],['absent','Absent','❌','#d62828'],['none','Not yet','⏳','#7b8794']] as $x)
    $s.='<div class="sc" style="--c:'.$x[3].'"><div class="sc-h"><span>'.$x[1].'</span><i>'.$x[2].'</i></div><b>'.$c[$x[0]].'</b><em><u style="width:'.round($c[$x[0]]/$t*100).'%"></u></em><small>'.round($c[$x[0]]/$t*100).'% of class</small></div>';
  return $s.'</div>'; }
function pieHtml($c){ $cols=['present'=>'#2a9d8f','late'=>'#e9a100','excused'=>'#457b9d','absent'=>'#d62828','none'=>'#999']; $lab=['present'=>'Present','late'=>'Late','excused'=>'Excused','absent'=>'Absent','none'=>'Not yet'];
  $tot=max(1,array_sum($c)); $acc=0; $seg=[]; foreach($cols as $k=>$cl){ $s0=$acc; $acc+=$c[$k]/$tot*100; $seg[]="$cl {$s0}% {$acc}%"; }
  $s='<div class="pie" style="background:conic-gradient('.implode(',',$seg).')"></div><div style="margin-top:12px;font-size:13px">';
  foreach($lab as $k=>$l) $s.='<div><span class="dot" style="background:'.$cols[$k].'"></span>'.$l.': <b>'.$c[$k].'</b></div>';
  return $s.'</div>'; }
function rowsHtml($rows){ $s='<table><tr><th>Name</th><th>Code</th><th>Status</th><th>Time</th><th>Mark</th></tr>';
  foreach($rows as $r){ $s.='<tr><td>'.h($r['name']).'</td><td>'.h($r['code']).'</td><td>'.($r['status']?'<span class="tag '.$r['status'].'">'.$r['status'].'</span>':'-').'</td><td>'.h($r['time_in']).'</td><td><form method="post" style="display:inline"><input type="hidden" name="sid" value="'.$r['id'].'">';
    foreach(['present','late','excused','absent'] as $k) $s.='<button name="status" value="'.$k.'" class="b '.$k.'">'.ucfirst($k[0]).'</button>';
    $s.='</form></td></tr>'; }
  return $s.'</table>'; }
// Small key that changes when a post opens/closes or the student's record changes (student page polls it)
function stateKey($pdo,$sid){ $p=curPost($pdo); $q=$pdo->prepare("SELECT status FROM attendance WHERE student_id=? AND att_date=?"); $q->execute([$sid,date('Y-m-d')]);
  return ($p?$p['id'].($p['open']?'o':'c'):'none').'|'.($q->fetchColumn()?:'none'); }
function pollJs($key){ return '<script>setInterval(function(){fetch("poll.php").then(function(r){return r.text()}).then(function(t){if(t!=='.json_encode($key).')location.reload()})},5000)</script>'; }
function greet(){ $h=(int)date('G'); return $h<12?'Good morning':($h<18?'Good afternoon':'Good evening'); }
function tip(){ return '<p class="tip">'.date('l, M d').' · Click Present on your dashboard when your teacher opens attendance.</p>'; }
require_once __DIR__.'/extras.php';
