<?php
// Minimal Gmail SMTP sender (no libraries needed). Needs: openssl enabled in XAMPP (default).
function sendMail($to,$subject,$body){
  if(!MAIL_ENABLED || !filter_var($to,FILTER_VALIDATE_EMAIL)) return false;
  $f=@stream_socket_client('ssl://smtp.gmail.com:465',$en,$es,8); if(!$f) return false; stream_set_timeout($f,8);
  $rd=function() use($f){ $r=''; while(($l=fgets($f,515))!==false){ $r.=$l; if(substr($l,3,1)===' ') break; } return $r; };
  $cmd=function($c) use($f,$rd){ fwrite($f,$c."\r\n"); return $rd(); };
  $rd(); $cmd('EHLO localhost');
  $cmd('AUTH LOGIN'); $cmd(base64_encode(MAIL_USER)); $a=$cmd(base64_encode(MAIL_PASS));
  if(substr($a,0,3)!=='235'){ fclose($f); return false; }
  $cmd('MAIL FROM:<'.MAIL_USER.'>'); $cmd('RCPT TO:<'.$to.'>'); $cmd('DATA');
  $msg="From: ".MAIL_NAME." <".MAIL_USER.">\r\nTo: <$to>\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n".str_replace("\n.","\n..",$body)."\r\n.";
  $r=$cmd($msg); $cmd('QUIT'); fclose($f);
  return substr($r,0,3)==='250';
}
