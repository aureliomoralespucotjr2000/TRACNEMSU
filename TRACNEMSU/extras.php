<?php
// ---- New tables (created automatically, safe to repeat) ----
function extrasInit($pdo){
  $q=["CREATE TABLE IF NOT EXISTS announcements(id INT AUTO_INCREMENT PRIMARY KEY,author_id INT,title VARCHAR(120),body TEXT,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
      "CREATE TABLE IF NOT EXISTS inbox(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,message VARCHAR(255),link VARCHAR(100) NULL,is_read TINYINT DEFAULT 0,created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
      "CREATE TABLE IF NOT EXISTS subjects(id INT AUTO_INCREMENT PRIMARY KEY,code VARCHAR(20),name VARCHAR(100),teacher_id INT NULL,branch_id INT NULL)",
      "CREATE TABLE IF NOT EXISTS class_att(id INT AUTO_INCREMENT PRIMARY KEY,subject_id INT,student_id INT,att_date DATE,status ENUM('present','late','excused','absent'),UNIQUE(subject_id,student_id,att_date))",
      "CREATE TABLE IF NOT EXISTS pw_resets(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,token VARCHAR(64),expires DATETIME,status ENUM('pending','done') DEFAULT 'pending',created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"];
  foreach($q as $s){ try{ $pdo->exec($s); }catch(Exception $e){} }
  foreach([["leaves","attachment","VARCHAR(255) NULL"],["complaints","rating","TINYINT NULL"],["complaints","rate_note","VARCHAR(255) NULL"]] as [$t,$c,$d]){
    try{ $pdo->query("SELECT $c FROM $t LIMIT 1"); }catch(Exception $e){ try{ $pdo->exec("ALTER TABLE $t ADD $c $d"); }catch(Exception $x){} } }
}
extrasInit($pdo);

// Send an in-app notification (and an email if the student has one and Gmail is on)
function notify($pdo,$uid,$msg,$link=null,$mail=true){
  $pdo->prepare("INSERT INTO inbox(user_id,message,link) VALUES(?,?,?)")->execute([$uid,mb_substr($msg,0,255),$link]);
  if($mail){ $q=$pdo->prepare("SELECT email FROM users WHERE id=?"); $q->execute([$uid]); $e=$q->fetchColumn(); if($e) sendMail($e,'TRACNEMSU notification',$msg."\n\n".SCHOOL); } }

function unreadCount($pdo,$uid){ $q=$pdo->prepare("SELECT COUNT(*) FROM inbox WHERE user_id=? AND is_read=0"); $q->execute([$uid]); return (int)$q->fetchColumn(); }

// Latest announcements card (student home)
function announceHtml($pdo,$n=3){ $r=$pdo->query("SELECT a.*,u.name author FROM announcements a LEFT JOIN users u ON u.id=a.author_id ORDER BY a.id DESC LIMIT ".(int)$n)->fetchAll();
  $s='<div class="card" style="border-left:5px solid #457b9d"><h3>📣 Announcements</h3>';
  foreach($r as $x) $s.='<div style="padding:8px 0;border-top:1px solid var(--line)"><b>'.h($x['title']).'</b><br><small>'.h($x['author']).' · '.h($x['created']).'</small><p style="white-space:pre-wrap;margin:6px 0 0">'.h($x['body']).'</p></div>';
  if(!$r) $s.='<p>No announcements.</p>'; return $s.'</div>'; }

// Latest notifications card (student home)
function inboxHtml($pdo,$uid){ $q=$pdo->prepare("SELECT * FROM inbox WHERE user_id=? ORDER BY id DESC LIMIT 5"); $q->execute([$uid]); $r=$q->fetchAll(); if(!$r) return '';
  $s='<div class="card" style="border-left:5px solid #e9a100"><h3>🔔 Notifications <a href="inbox.php" style="font-size:13px">see all</a></h3>';
  foreach($r as $x) $s.='<div style="padding:6px 0;border-top:1px solid var(--line)">'.($x['is_read']?'':'<span class="badge" style="float:none">new</span> ').($x['link']?'<a href="'.h($x['link']).'">':'').h($x['message']).($x['link']?'</a>':'').' <small>'.h($x['created']).'</small></div>';
  return $s.'</div>'; }
