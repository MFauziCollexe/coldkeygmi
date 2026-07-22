<?php
$dsn='mysql:host=127.0.0.1;dbname=coldkeygmi;charset=utf8';
$user='root'; $pass='';
try{
    $pdo=new PDO($dsn,$user,$pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}catch(Exception $e){
    echo "CONNECTION_ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
$q = $pdo->query("SELECT id, uploaded_by, department_id, year, month, is_current, (SELECT name FROM users u WHERE u.id = roster_upload_batches.uploaded_by) AS uploader_name FROM roster_upload_batches WHERE department_id = 13 AND year = 2026 AND month IN (6,7) ORDER BY id;");
$rows=$q->fetchAll(PDO::FETCH_ASSOC);
echo "BATCHES\n";
foreach($rows as $r){
    echo implode("\t", [$r['id'],$r['uploaded_by'],$r['department_id'],$r['year'],$r['month'],$r['is_current'],$r['uploader_name']]) . "\n";
}
echo "\n";
if (count($rows)==0) exit;
$saidUploadedBy = null;
foreach($rows as $r){ if (stripos($r['uploader_name'],'SAID') !== false){ $saidUploadedBy = $r['uploaded_by']; break; } }
echo "SAID_UPLOADER_ID=".($saidUploadedBy ?? '') . "\n";
if ($saidUploadedBy){
   $ids = [];
   foreach ($rows as $r) if ($r['uploaded_by']==$saidUploadedBy) $ids[] = $r['id'];
   if (count($ids)){
       $in = implode(',', $ids);
       $q2 = $pdo->query("SELECT employee_key, roster_date, shift_code, batch_id FROM roster_entries WHERE batch_id IN ($in) AND roster_date BETWEEN '2026-06-23' AND '2026-07-22' ORDER BY roster_date;");
       $e = $q2->fetchAll(PDO::FETCH_ASSOC);
       echo "ROSTER_ENTRIES_FOR_SAID\n";
       foreach($e as $row) echo implode("\t", [$row['employee_key'],$row['roster_date'],$row['shift_code'],$row['batch_id']]) . "\n";
   }
}
