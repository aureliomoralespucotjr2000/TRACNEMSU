<?php
// Run once: http://localhost/TRACNEMSU/setup.php
$p=new PDO('mysql:host=localhost;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$p->exec("CREATE DATABASE IF NOT EXISTS tracnemsu; USE tracnemsu;
CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,code VARCHAR(20) UNIQUE,name VARCHAR(100),username VARCHAR(50) UNIQUE,password VARCHAR(255),role ENUM('teacher','student'));
CREATE TABLE IF NOT EXISTS attendance(id INT AUTO_INCREMENT PRIMARY KEY,student_id INT,att_date DATE,time_in TIME NULL,status ENUM('present','late','excused','absent'),UNIQUE(student_id,att_date),FOREIGN KEY(student_id) REFERENCES users(id));");
$p->exec("USE tracnemsu");
$p->exec("CREATE TABLE IF NOT EXISTS leaves(id INT AUTO_INCREMENT PRIMARY KEY,student_id INT,date_from DATE,date_to DATE,reason VARCHAR(255),status ENUM('pending','approved','rejected') DEFAULT 'pending',created TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS att_posts(id INT AUTO_INCREMENT PRIMARY KEY,post_date DATE,message VARCHAR(200) NULL,opens DATETIME,closes DATETIME,closed TINYINT DEFAULT 0);
CREATE TABLE IF NOT EXISTS parent_log(id INT AUTO_INCREMENT PRIMARY KEY,student_id INT,message VARCHAR(255),created TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
try{ $p->exec("ALTER TABLE users ADD parent_phone VARCHAR(30) NULL"); }catch(Exception $e){}
if(!$p->query("SELECT COUNT(*) FROM users")->fetchColumn()){
  $i=$p->prepare("INSERT INTO users(code,name,username,password,role) VALUES(?,?,?,?,?)");
  $i->execute([null,'Teacher','teacher',password_hash('teacher123',PASSWORD_DEFAULT),'teacher']);
  foreach(['Juan Dela Cruz','Maria Santos','Pedro Reyes','Ana Lopez','Jose Ramos'] as $n=>$name)
    $i->execute([sprintf('STU%03d',$n+1),$name,'student'.($n+1),password_hash('student123',PASSWORD_DEFAULT),'student']);
}
echo "Done! <a href='login.php'>Go to login</a><br>Teacher: teacher / teacher123<br>Students: student1..student5 / student123";
