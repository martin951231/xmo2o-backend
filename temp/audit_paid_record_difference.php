<?php
$c = require __DIR__.'/../conf/db.php';
$db = new PDO('mysql:host='.$c['DB_HOST'].';port='.$c['DB_PORT'].';dbname='.$c['DB_NAME'], $c['DB_USER'], $c['DB_PWD'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$p=$c['DB_PREFIX'];
$queries=[
'orders'=>"SELECT village_id,COUNT(*) n,SUM(pay_money+COALESCE(discount_money,0)) gross,SUM(CASE WHEN is_refund=2 THEN refund_money ELSE 0 END) refund,SUM(score_deducte) score,SUM(deposit_money) deposit,SUM(GREATEST(ROUND(pay_money+COALESCE(discount_money,0)-CASE WHEN is_refund=2 THEN refund_money ELSE 0 END-COALESCE(score_deducte,0)-COALESCE(deposit_money,0),2),0)) net FROM {$p}house_new_pay_order WHERE is_paid=1 AND is_discard=1 GROUP BY village_id",
'records'=>"SELECT village_id,COUNT(*) n,SUM(pay_money) cash,SUM(refund_money) refund FROM {$p}paid_order_record WHERE source_from=1 GROUP BY village_id",
];
foreach($queries as $name=>$sql) echo $name.': '.json_encode($db->query($sql)->fetchAll(PDO::FETCH_ASSOC)).PHP_EOL;
