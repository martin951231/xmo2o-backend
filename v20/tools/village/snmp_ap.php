<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/19 9:47
 */
require  '../../vendor/autoload.php';
$database_config = include '../../../conf/db.php';
use FreeDSx\Snmp\SnmpClient;

$snmp = new SnmpClient([
    'host' => '116.171.8.229',
    'version' => 2, //版本
    'port' => 161 , //端口
//		'community' => 'public',
    'community' => 'futurecity',
]);

echo '根据需要获取多个OID 并且遍历......' . PHP_EOL;
# Get multiple OIDs and iterate through them as needed...
$oids = $snmp->get('1.3.6.1.4.1.3902.1.1.1.9');

foreach($oids as $oid) {
    echo sprintf("OId = %s == value = %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;
    var_dump($oid->hasStatus(),$oid->getStatus());
}

