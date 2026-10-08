<?php require 'db.php'; if(empty($_SESSION['u'])){header('Location: login.php');exit;}
$t=isStaff(); $err='';
if($_SERVER['REQUEST_METHOD']=='POST' && $t){
  if(($_POST['act']??'')=='del'){ $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([(int)$_POST['id']]); }
  else { $ti=trim($_POST['title']??''); $bo=trim($_POST['body']??'');
    if($ti===''||$bo==='') $err='Title and message are required.';
    else $pdo->prepare("INSERT INTO announcements(author_id,title,body) VALUES(?,?,?)")->execute([$_SESSION['u']['id'],mb_substr($ti,0,120),$bo]); }
  if(!$err){ header('Location: announcements.php'); exit; } }
$rows=$pdo->query("SELECT a.*,u.name author FROM announcements a LEFT JOIN users u ON u.id=a.author_id ORDER BY a.id DESC LIMIT 100")->fetchAll();
top('Announcements'); if($t): ?>
<div class="card"><h3>📣 Post an Announcement</h3><?php if($err): ?><p style="color:red"><?=h($err)?></p><?php endif; ?>
<form method="post"><input name="title" placeholder="Title (e.g. Class suspended tomorrow)" maxlength="120" required><textarea name="body" rows="3" placeholder="Message" required></textarea><button class="b present" style="padding:10px 16px">Post</button></form></div>
<?php endif; ?>
<div class="card"><h3>Announcements</h3>
<?php foreach($rows as $x): ?><div style="padding:10px 0;border-top:1px solid var(--line)"><b><?=h($x['title'])?></b> <small>· <?=h($x['author'])?> · <?=h($x['created'])?></small>
<p style="white-space:pre-wrap;margin:6px 0"><?=h($x['body'])?></p>
<?php if($t): ?><form method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?')"><input type="hidden" name="id" value="<?=$x['id']?>"><button name="act" value="del" class="b absent">🗑 Delete</button></form><?php endif; ?></div>
<?php endforeach; if(!$rows) echo '<p>No announcements yet.</p>'; ?></div>
<?php bottom();
