<?php
$dsn='mysql:host=127.0.0.1;dbname=coldkeygmi;charset=utf8';
$user='root'; $pass='';
$pdo=new PDO($dsn,$user,$pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
// find users matching 'said'
$sth = $pdo->prepare("SELECT id, name FROM users WHERE LOWER(name) LIKE ?");
$sth->execute(['%said%']);
$users = $sth->fetchAll(PDO::FETCH_ASSOC);
if (!$users) { echo "NO_USER_MATCH\n"; exit; }
foreach ($users as $u) {
    echo "USER: " . $u['id'] . "\t" . $u['name'] . "\n";
    $b = $pdo->prepare("SELECT id, department_id, year, month, is_current FROM roster_upload_batches WHERE uploaded_by = ? AND year = 2026 AND month IN (6,7) ORDER BY id");
    $b->execute([$u['id']]);
    $batches = $b->fetchAll(PDO::FETCH_ASSOC);
    if (!$batches) { echo "  NO_BATCHES\n"; continue; }
    foreach ($batches as $batch){
        $cnt = $pdo->query("SELECT COUNT(*) FROM roster_entries WHERE batch_id = " . (int)$batch['id'] . " AND roster_date BETWEEN '2026-06-23' AND '2026-07-22'")->fetchColumn();
        echo "  BATCH " . $batch['id'] . " month:" . $batch['month'] . " is_current:" . $batch['is_current'] . " entries:" . $cnt . "\n";
    }
}
