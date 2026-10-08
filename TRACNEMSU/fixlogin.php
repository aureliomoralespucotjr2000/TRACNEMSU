<?php
// Run once: http://localhost/TRACNEMSU/fixlogin.php  (then DELETE this file)
require 'db.php';
$set=$pdo->prepare("INSERT INTO users(code,name,username,password,role,status) VALUES(?,?,?,?,?, 'active')
  ON DUPLICATE KEY UPDATE password=VALUES(password),status='active',role=VALUES(role)");
$set->execute([null,'Teacher','teacher',password_hash('teacher123',PASSWORD_DEFAULT),'teacher']);
$set->execute([null,'Administrator','admin',password_hash('admin123',PASSWORD_DEFAULT),'admin']);
foreach(['Juan Dela Cruz','Maria Santos','Pedro Reyes','Ana Lopez','Jose Ramos'] as $n=>$name)
  $set->execute([sprintf('STU%03d',$n+1),$name,'student'.($n+1),password_hash('student123',PASSWORD_DEFAULT),'student']);
echo "Done! Passwords reset.<br>Admin: admin / admin123<br>Teacher: teacher / teacher123<br>Students: student1..student5 / student123<br><a href='login.php'>Go to login</a><br><b>Now delete fixlogin.php</b>";
