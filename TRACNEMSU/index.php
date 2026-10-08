<?php session_start(); $r=$_SESSION['u']['role']??'';
header('Location: '.($r=='admin'?'admin.php':($r=='teacher'?'teacher.php':($r=='student'?'student.php':'login.php'))));
