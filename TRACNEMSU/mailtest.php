<?php require 'db.php'; need('admin'); $m='';
if($_POST){ $ok=sendMail(trim($_POST['to']),'TRACNEMSU test email','This is a test email from TRACNEMSU. Gmail is working.'); $m=$ok?'Sent! Check the inbox (and spam).':'Failed. Check MAIL_USER / MAIL_PASS in db.php, App Password, and that MAIL_ENABLED is true.'; }
top('Email test'); ?><div class="card" style="max-width:480px"><h3>📧 Test Gmail</h3><?php if($m) echo '<p><b>'.h($m).'</b></p>'; ?>
<form method="post"><input type="email" name="to" placeholder="Send test to (email)" required><button class="b present" style="padding:10px 16px">Send test</button></form></div><?php bottom();
