<?php
require dirname(__DIR__, 2) . '/app/social_activity/model/service/SocialActivityRefundPolicy.php';
require dirname(__DIR__, 2) . '/app/social_activity/model/service/SocialActivityRefundGateway.php';
use app\social_activity\model\service\SocialActivityRefundPolicy as Policy;
use app\social_activity\model\service\SocialActivityRefundGateway as Gateway;
function cfg($key) { return $GLOBALS['refund_test_config'][$key] ?? ''; }
function check($value, $message) { if (!$value) { throw new RuntimeException($message); } }
function rejects(callable $fn, $message) { try { $fn(); } catch (RuntimeException $e) { return; } throw new RuntimeException($message); }
foreach ([
    [0, '', 1], [1, '', 1], [2, '', 2], [2, 'old error', 2],
    [3, 'private failure detail', 3], [4, 'manual review', 3], [1, 'transport error', 3]
] as $case) {
    $display = Policy::userResult(['id' => 1, 'status' => $case[0], 'failure_reason' => $case[1]]);
    check($display['refund_status'] === $case[2], 'refund presentation status');
    check(!isset($display['failure_reason']), 'failure details stay in admin only');
    if ($case[2] === 3) { check($display['status_text'] === '退款失败，请联系工作人员', 'uniform failure message'); }
}
$now = strtotime('2026-09-14 12:00:00');
$order = ['uid' => 10, 'amount' => '12.34', 'paid' => 1, 'status' => 10, 'is_sign' => 0, 'refund_status' => 0,
    'ticket_start_time' => '2026-09-14 12:01:00', 'pay_time' => '2026-09-14 11:55:00'];
Policy::validate($order, 10, $now);
foreach (['uid' => 11, 'amount' => '0.00', 'paid' => 0, 'status' => 20, 'is_sign' => 1, 'refund_status' => 1] as $key => $value) {
    rejects(function () use ($order, $key, $value, $now) { Policy::validate(array_replace($order, [$key => $value]), 10, $now); }, $key . ' must reject');
}
foreach ([
    ['ticket_start_time' => '2026-09-14 12:00:00'],
    ['ticket_start_time' => '2026-09-13 12:00:00', 'ticket_end_time' => '2026-09-13 13:00:00'],
    ['pay_time' => '2026-09-14 12:00:00'],
    ['pay_time' => '', 'ticket_start_time' => '']
] as $times) {
    Policy::validate(array_replace($order, $times), 10, $now);
}
rejects(function () use ($order, $now) { Policy::validate(array_replace($order, ['refund_status' => 2]), 10, $now); }, 'refunded order must reject');
rejects(function () use ($order, $now) { Policy::validate(array_replace($order, ['status' => 0]), 10, $now); }, 'unpaid status must reject');
check(Policy::cents('12.34') === 1234 && Policy::cents('0.01') === 1 && Policy::cents('1') === 100, 'exact cents');
rejects(function () { Policy::cents('1.001'); }, 'rounding must not hide invalid amount');
check(Gateway::canonical(['z' => 'a+b&c', 'a' => '0', 'empty' => '', 'sign' => 'x']) === 'a=0&z=a+b&c', 'literal signed values');
$opensslConfig = ['private_key_bits' => 2048];
$localConfig = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
if (is_file($localConfig)) { $opensslConfig['config'] = $localConfig; }
$key = openssl_pkey_new($opensslConfig);
check($key !== false, 'RSA fixture key generation');
openssl_pkey_export($key, $private, null, $opensslConfig);
$public = openssl_pkey_get_details($key)['key'];
$gateway = new Gateway(['cusid' => 'merchant', 'appid' => 'app', 'private_key' => $private, 'platform_public_key' => $public]);
$gateway->assertReady();
$data = ['cusid' => 'merchant', 'appid' => 'app', 'reqsn' => 'SAR123', 'trxamt' => '1234', 'trxstatus' => '0000'];
openssl_sign(Gateway::canonical($data), $sig, $private, OPENSSL_ALGO_SHA1);
$data['sign'] = base64_encode($sig);
$gateway->verify($data);
rejects(function () use ($gateway, $data) { $gateway->verify(array_replace($data, ['trxamt' => '1235'])); }, 'tampered amount');
$wrong = array_replace($data, ['cusid' => 'other']);
openssl_sign(Gateway::canonical($wrong), $sig, $private, OPENSSL_ALGO_SHA1);
$wrong['sign'] = base64_encode($sig);
rejects(function () use ($gateway, $wrong) { $gateway->verify($wrong); }, 'signed wrong merchant');
class ResponseTestGateway extends Gateway
{
    public function parseFixture($body, $http, $no) { return $this->parseHttpsResponse($body, $http, $no); }
}
$privateOnly = new ResponseTestGateway(['cusid' => 'merchant', 'appid' => 'app', 'private_key' => $private]);
$privateOnly->assertReady();
$response = ['retcode' => 'SUCCESS', 'cusid' => 'merchant', 'appid' => 'app', 'reqsn' => 'SAR123', 'trxamt' => '1234', 'trxstatus' => '0000'];
check($privateOnly->parseFixture(json_encode($response), 200, 'SAR123')['trxstatus'] === '0000', 'private-only HTTPS integration');
rejects(function () use ($privateOnly, $response) { $privateOnly->parseFixture(json_encode($response), 200, 'OTHER'); }, 'wrong refund number');
rejects(function () use ($privateOnly, $response) { $privateOnly->parseFixture(json_encode(array_replace($response, ['cusid' => 'other'])), 200, 'SAR123'); }, 'wrong HTTPS merchant');
rejects(function () use ($privateOnly, $response) { $privateOnly->parseFixture(json_encode($response), 500, 'SAR123'); }, 'HTTP failure');
rejects(function () use ($privateOnly) { $privateOnly->parseFixture('{', 200, 'SAR123'); }, 'invalid JSON');
rejects(function () use ($privateOnly, $response) { $privateOnly->parseFixture(json_encode(array_replace($response, ['retcode' => 'FAIL'])), 200, 'SAR123'); }, 'communication failure');
rejects(function () use ($privateOnly, $response) { $privateOnly->verify($response); }, 'untrusted callback cannot pass strict verification without key');
$GLOBALS['refund_test_config'] = ['pay_allinpay_public_key' => $public];
$existingConfig = new ResponseTestGateway(['cusid' => 'merchant', 'appid' => 'app', 'private_key' => $private]);
$existingConfig->assertReady();
$existingConfig->verify($data);
rejects(function () use ($existingConfig, $data) { $existingConfig->verify(array_replace($data, ['trxamt' => '1235'])); }, 'existing system key is reused');
$GLOBALS['refund_test_config'] = [];
echo "PASS: eligibility, cents, optional existing RSA verification, private-only HTTPS response checks\n";
