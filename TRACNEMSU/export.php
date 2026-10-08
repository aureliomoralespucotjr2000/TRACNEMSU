<?php require 'db.php'; need('teacher');
header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=attendance_'.date('Y-m').'.csv');
$o=fopen('php://output','w'); fputcsv($o,['Date','Code','Name','Time In','Status']);
$q=$pdo->prepare("SELECT a.att_date,u.code,u.name,a.time_in,a.status FROM attendance a JOIN users u ON u.id=a.student_id WHERE DATE_FORMAT(a.att_date,'%Y-%m')=? ORDER BY a.att_date,u.name");
$q->execute([date('Y-m')]); while($r=$q->fetch(PDO::FETCH_NUM)) fputcsv($o,$r);
