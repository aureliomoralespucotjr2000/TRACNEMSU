<?php require 'db.php'; need('teacher'); $msg=[]; $isAdmin=($_SESSION['u']['role']??'')=='admin';

function cantilanId($pdo){ $id=$pdo->query("SELECT id FROM branches WHERE name='Cantilan Campus'")->fetchColumn();
  if(!$id){ $pdo->exec("INSERT INTO branches(name,address) VALUES('Cantilan Campus','Cantilan, Surigao del Sur')"); $id=$pdo->lastInsertId(); } return (int)$id; }

// 100 sample students: student6..student105, password student123, ID number like 2024-00123, parent name + phone included
function sampleStudents($pdo){ $bid=cantilanId($pdo); mt_srand(7);
  $fm=['Juan','Mark','John','James','Joshua','Christian','Kevin','Jerome','Carlo','Paolo','Rey','Ryan','Joel','Allan','Noel','Dennis','Eric','Mico','Jay','Vincent','Francis','Neil','Bryan','Aldrin','Rommel'];
  $ff=['Maria','Ana','Angel','Jessa','Mary Grace','Kristine','Rhea','Joy','Lyka','Shane','Jasmine','Michelle','Princess','Charlene','Hazel','Rose','Cherry','Alyssa','Bea','Nicole','Camille','Dianne','Trisha','Erika','Joanna'];
  $ln=['Dela Cruz','Santos','Reyes','Lopez','Ramos','Garcia','Mendoza','Bautista','Villanueva','Aquino','Castillo','Navarro','Flores','Torres','Rivera','Gonzales','Cruz','Pacheco','Salazar','Tan','Lim','Abad','Alcantara','Baguio','Cabrera','Dumalag','Estrada','Fabian','Galos','Hilario','Ibarra','Jumawan','Lagunay','Macasero','Namoc','Ocampo','Pagalan','Quirog','Rosales','Sumaylo'];
  $pf=['Maria','Elena','Rosario','Josefina','Roberto','Antonio','Ramon','Eduardo','Teresita','Lourdes'];
  $co=['BS Information Technology','BS Computer Science','BS Education','BS Business Administration']; $yl=['1st Year','2nd Year','3rd Year','4th Year']; $yr=[2026,2025,2024,2023];
  $pick=function($a){ return $a[mt_rand(0,count($a)-1)]; };
  $codes=array_flip($pdo->query("SELECT code FROM users WHERE code IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN));
  $pw=password_hash('student123',PASSWORD_DEFAULT);
  $ins=$pdo->prepare("INSERT IGNORE INTO users(code,name,username,password,role,status,email,contact,course,year_level,section,parent_name,parent_phone,branch_id) VALUES(?,?,?,?,'student','active',?,?,?,?,?,?,?,?)"); $new=0;
  for($i=6;$i<=105;$i++){ $f=mt_rand(0,1)?$pick($ff):$pick($fm); $mi=chr(mt_rand(65,84)); $l=$pick($ln); $c=$pick($co); $y=mt_rand(0,3); $sec=($y+1).$pick(['A','B','C']); $pn=$pick($pf).' '.$l;
    do{ $code=$yr[$y].'-'.sprintf('%05d',mt_rand(100,999)); }while(isset($codes[$code])); $codes[$code]=1;
    $ins->execute([$code,"$f $mi. $l",'student'.$i,$pw,'student'.$i.'@example.com','09170000'.sprintf('%03d',$i),$c,$yl[$y],$sec,$pn,'09180000'.sprintf('%03d',$i),$bid]); $new+=$ins->rowCount(); }
  // proper ID numbers + parent info for the 5 original students
  $o=['Elena Dela Cruz','Rosario Santos','Roberto Reyes','Josefina Lopez','Antonio Ramos'];
  foreach($o as $k=>$p){ $n=$k+1; $pdo->prepare("UPDATE users SET parent_name=?,parent_phone=? WHERE username=? AND role='student' AND (parent_phone IS NULL OR parent_phone='')")->execute([$p,'0918000000'.$n,'student'.$n]);
    $pdo->prepare("UPDATE users SET code=? WHERE username=? AND role='student' AND code LIKE 'STU%'")->execute(['2024-'.sprintf('%05d',$n),'student'.$n]);
    $pdo->prepare("UPDATE users SET branch_id=? WHERE username=? AND role='student' AND branch_id IS NULL")->execute([$bid,'student'.$n]); }
  mt_srand(); return $new; }

// 10 sample staff (teachers): staff1..staff10, password staff123
function sampleStaff($pdo){ $bid=cantilanId($pdo); $pw=password_hash('staff123',PASSWORD_DEFAULT); $new=0;
  $ins=$pdo->prepare("INSERT IGNORE INTO users(code,name,username,password,role,status,email,branch_id) VALUES(NULL,?,?,?,'teacher','active',?,?)");
  foreach(['Maricel Dumalag','Ronaldo Pacheco','Leonora Baguio','Gilbert Namoc','Rosalinda Cabrera','Arnel Jumawan','Teresita Ocampo','Domingo Estrada','Marites Salazar','Federico Aquino'] as $k=>$n){
    $ins->execute([$n,'staff'.($k+1),$pw,'staff'.($k+1).'@example.com',$bid]); $new+=$ins->rowCount(); } return $new; }

// 30 school days of attendance for all active students + a Parent Log alert for every absence
function sampleAttendance($pdo){
  $st=$pdo->query("SELECT id FROM users WHERE role='student' AND status='active'")->fetchAll(PDO::FETCH_COLUMN);
  $i=$pdo->prepare("INSERT IGNORE INTO attendance(student_id,att_date,time_in,status) VALUES(?,?,?,?)"); $n=0;
  for($d=1;$d<=45;$d++){ $t=strtotime("-$d day"); if(date('N',$t)>5) continue; if(++$n>30) break;
    foreach($st as $s){ $r=mt_rand(1,100);
      if($r<=75){$x='present';$tm=sprintf('07:%02d:%02d',mt_rand(10,59),mt_rand(0,59));}
      elseif($r<=88){$x='late';$tm=sprintf('08:%02d:%02d',mt_rand(1,30),mt_rand(0,59));}
      elseif($r<=94){$x='excused';$tm=null;} else {$x='absent';$tm=null;}
      $i->execute([$s,date('Y-m-d',$t),$tm,$x]); } }
  return $pdo->exec("INSERT INTO parent_log(student_id,message,created) SELECT s.id, CONCAT('To ',COALESCE(NULLIF(s.parent_phone,''),'(no number)'),': Your child ',s.name,' was marked ABSENT on ',a.att_date,'.'), CONCAT(a.att_date,' 08:30:00') FROM attendance a JOIN users s ON s.id=a.student_id WHERE a.status='absent' AND s.role='student' AND NOT EXISTS(SELECT 1 FROM parent_log l WHERE l.student_id=s.id AND l.message LIKE CONCAT('%ABSENT on ',a.att_date,'.')) ORDER BY a.att_date"); }

if($_POST){ $a=$_POST['act']??'';
  if($a=='students'||$a=='all') $msg[]='✅ '.sampleStudents($pdo).' sample students added (student6 to student105, password student123).';
  if(($a=='staff'||$a=='all') && $isAdmin) $msg[]='✅ '.sampleStaff($pdo).' sample staff added (staff1 to staff10, password staff123).';
  if($a=='attendance'||$a=='all') $msg[]='✅ 30 school days of attendance added, and '.sampleAttendance($pdo).' absence alerts added to the Parent Log.'; }
top('Data'); ?>
<div class="card"><h3>🧪 Sample Data</h3><p>Creates sample records so the dashboard, charts, reports and the Parent Log are not empty. Nothing is overwritten and you can click again safely.</p>
<?php foreach($msg as $m) echo '<p style="color:#2a9d8f"><b>'.h($m).'</b></p>'; ?>
<form method="post"><button name="act" value="all" class="b present" style="padding:12px 20px;font-size:16px">⚡ Generate everything (recommended)</button></form>
<h4>Or one at a time</h4>
<form method="post" style="display:flex;gap:8px;flex-wrap:wrap">
<button name="act" value="students" class="b excused" style="padding:10px 16px">👥 Add 100 students</button>
<?php if($isAdmin): ?><button name="act" value="staff" class="b excused" style="padding:10px 16px">👨‍🏫 Add 10 staff</button><?php endif; ?>
<button name="act" value="attendance" class="b excused" style="padding:10px 16px">🗓 Attendance + Parent Log</button></form>
<p class="tip">Passwords: students <b>student123</b> · staff <b>staff123</b>. "Generate everything" adds students first, then attendance for all of them.<?=$isAdmin?'':' (Adding staff is for the admin only.)'?></p></div>
<?php bottom();
