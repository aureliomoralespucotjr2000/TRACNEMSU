<?php require 'db.php'; need('teacher'); header('Content-Type: application/json');
$q=$pdo->prepare("SELECT * FROM users WHERE code=? AND role='student' AND status='active'"); $q->execute([trim($_POST['code']??'')]); $s=$q->fetch();
if(!$s){ echo json_encode(['msg'=>'Unknown QR code']); exit; }
$r=checkin($pdo,$s['id']);
echo json_encode(['msg'=>$s['name'].': '.($r=='already'?'already checked in':strtoupper($r))]);
