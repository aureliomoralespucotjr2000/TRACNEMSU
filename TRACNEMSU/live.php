<?php require 'db.php'; need('teacher'); header('Content-Type: application/json');
$rows=todayRows($pdo); $c=todayCounts($rows);
echo json_encode(['resp'=>respHtml($pdo),'cnt'=>countersHtml($c),'pie'=>pieHtml($c),'srows'=>rowsHtml($rows)]);
