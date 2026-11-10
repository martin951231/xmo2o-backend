<?php
// Run with PHP 7.3+ and --isolated against the dedicated local test server.
if (!in_array('--isolated', $argv, true)) { fwrite(STDERR, "Use --isolated (test MySQL on localhost:13308 only).\n"); exit(1); }
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . '/');
$app->initialize();

use think\facade\Db;
use think\facade\Config;
use app\community\model\service\VisitorRegisterService;
use app\community\model\service\VisitorRegisterValidation;
use app\community\model\service\VisitorRegisterWechatService;

$settings = Config::get('visitor_register');
$settings['encryption_key'] = base64_encode(random_bytes(32));
Config::set($settings, 'visitor_register');
if (in_array('--isolated', $argv, true)) {
    $database = Config::get('database');
    $database['connections']['mysql'] = array_merge($database['connections']['mysql'], [
        'hostname' => '127.0.0.1', 'hostport' => '13308', 'database' => 'visitor_register_test',
        'username' => 'root', 'password' => '', 'prefix' => 'pigcms_', 'deploy' => 0, 'rw_separate' => false,
    ]);
    Config::set($database, 'database');
}
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) { throw new \RuntimeException('FAIL: ' . $message); } $checks++; echo 'PASS ' . $message . PHP_EOL; }
function rejects($call, $message) { try { $call(); } catch (\InvalidArgumentException $e) { check(true, $message); return; } throw new \RuntimeException('FAIL: ' . $message); }
class VisitorWechatMock extends VisitorRegisterWechatService {
    public $responses = [];
    public $calls = [];
    public $user = 'mock-openid-a';
    protected function get($url) { return json_encode(['openid' => $this->user, 'session_key' => 'not-returned']); }
    protected function post($url, $body) {
        $this->calls[] = ['url' => explode('?', $url)[0], 'body' => array_diff_key($body, ['secret' => true])];
        if (!$this->responses) { throw new \RuntimeException('Missing mock response'); }
        return array_shift($this->responses);
    }
    public function token($config, $force = false) { return 'mock-token'; }
    public function clear($config) {}
}
class VisitorTokenMock extends VisitorRegisterWechatService {
    public $response;
    public $callCount = 0;
    protected function post($url, $body) { $this->callCount++; return $this->response; }
}
class MobileVisitorControllerMock extends \app\community\controller\manage_api\v1\VisitorRegisterController {
    public $loginResult = [];
    public function __construct($village, $auth = [900], $role = 5) {
        $this->login_info = ['village_id' => $village];
        $this->auth = $auth;
        $this->login_role = $role;
        $this->request = new class {
            public function isPost() { return true; }
            // Malicious client village must never override the authenticated village.
            public function post() { return ['village_id' => 987654, 'page' => 1, 'page_size' => 20]; }
        };
    }
    public function getLoginInfo($footer = true) { return $this->loginResult; }
}

$files = [];
$migrationMenu = 'visitor_register_test_menu_' . getmypid();
try {
    $sql = file_get_contents(dirname(__DIR__, 3) . '/sqls/package/release/2026-10-12_visitor_register_new.sql');
    $sql .= "\n" . file_get_contents(dirname(__DIR__, 3) . '/sqls/package/release/2026-10-13_visitor_register_qrcode_storage.sql');
    $sql .= "\n" . file_get_contents(dirname(__DIR__, 3) . '/sqls/package/release/2026-10-20_visitor_register_identity.sql');
    $sql .= "\n" . file_get_contents(dirname(__DIR__, 3) . '/sqls/package/release/2026-10-20_visitor_register_record_profile.sql');
    // Fail closed before running any statement that could touch real tables.
    preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)`\s*\(.*?ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', $sql, $matches);
    check(count($matches[0]) === 8, 'migration defines eight independent tables including per-record nickname');
    foreach ($matches[0] as $i => $statement) {
        check(strpos($matches[1][$i], 'pigcms_visitor_register_') === 0, 'temporary table scope');
        Db::execute(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $statement));
    }
    Db::execute('CREATE TEMPORARY TABLE pigcms_house_village (village_id int PRIMARY KEY)');
    Db::execute('INSERT INTO pigcms_house_village VALUES (987654),(987655)');
    Db::execute('CREATE TEMPORARY TABLE pigcms_house_village_info (village_id int PRIMARY KEY, index_header_bg_color varchar(20), index_header_bg_image varchar(255), index_title_color varchar(20))');
    Db::execute("INSERT INTO pigcms_house_village_info VALUES (987654,'#123456','','#FFFFFF'),(987655,'','','#000000')");
    Db::execute('CREATE TEMPORARY TABLE pigcms_config (name varchar(100) PRIMARY KEY, value text)');
    Db::execute("INSERT INTO pigcms_config VALUES ('mobile_head_color','#ABCDEF')");
    Db::execute('CREATE TEMPORARY TABLE pigcms_house_menu_new (id int PRIMARY KEY, component varchar(100), status int)');
    Db::execute("INSERT INTO pigcms_house_menu_new VALUES (900,'visitorRegisterNew',1)");
    $menuColumns = ['name','module','action','select_module','select_action','choose_page','icon','jump_url','application_txt','component','path'];
    // MySQL 5.7 cannot read a temporary table twice in INSERT ... NOT EXISTS.
    // Only this disposable table is persistent, in the isolated test database.
    $menuSql = 'CREATE TABLE `' . $migrationMenu . '` (id int AUTO_INCREMENT PRIMARY KEY, fid int, sort int, `show` int, status int, type int, jump_type int, application_id int, is_hide int';
    foreach ($menuColumns as $column) { $menuSql .= ',`' . $column . '` varchar(255)'; }
    Db::execute($menuSql . ')');
    // Test initialization and menu inserts twice against temporary stand-ins only.
    preg_match_all('/INSERT (?:IGNORE )?INTO `pigcms_(?:visitor_register_form|house_menu_new)`.*?;/s', $sql, $inserts);
    check(count($inserts[0]) === 3, 'migration seed statements recognized');
    for ($round = 0; $round < 2; $round++) { foreach ($inserts[0] as $statement) { Db::execute(str_replace('pigcms_house_menu_new', $migrationMenu, $statement)); } }
    check(Db::name('visitor_register_form')->count() === 4, 'migration form seeds are idempotent');
    check(Db::table($migrationMenu)->count() === 1 && intval(Db::table($migrationMenu)->value('fid')) === 194, 'migration menu is unique under My Applications');
    check(!VisitorRegisterValidation::hasMenu(['village_id' => 1, 'menus' => ''], 401, [7], 123), 'empty ordinary permissions denied');
    check(!VisitorRegisterValidation::hasMenu(['village_id' => 1, 'normal_admin_id' => 9, 'menus' => ''], 7, [7], 123), 'delegated admin cannot inherit superuser bypass');
    check(VisitorRegisterValidation::hasMenu(['village_id' => 1, 'menus' => '123,456'], 401, [7], 123), 'explicit menu grant accepted');
    check(!VisitorRegisterValidation::hasMenu(['village_id' => 1, 'menus' => '456'], 401, [7], 123), 'different menu grant denied');
    check(VisitorRegisterValidation::hasMenu(['village_id' => 1], 7, [7], 123), 'existing superuser role accepted');
    $wechat = new VisitorWechatMock();
    $service = new VisitorRegisterService($wechat, 'https://example.invalid');
    check($service->publicStatus(987654, 'visitor')['theme']['background_color'] === '#123456', 'QR village selects its own homepage background');
    check($service->publicStatus(987655, 'visitor')['theme']['background_color'] === '#ABCDEF', 'unset village color falls back to system homepage color');
    check($service->publicStatus(987654, 'visitor')['theme']['title_color'] === '#FFFFFF', 'homepage title color preserved even before publishing');
    Db::name('house_village_info')->where('village_id', 987654)->update(['index_header_bg_color' => '#654321']);
    check($service->publicStatus(987654, 'visitor')['theme']['background_color'] === '#654321', 'updated village theme is read without stale cache');
    $person = ['visit_time' => '2026-10-12 10:30:00', 'visitor_name' => '测试访客', 'organization_or_id' => '测试单位', 'phone' => '13800000000', 'visit_reason' => '业务拜访', 'host_name_room' => '测试人，3号楼1201', 'remark' => ''];
    $vehicle = ['plate_number' => '晋E12345', 'entry_time' => '2026-10-12 10:30:00', 'exit_time' => '', 'destination' => '3号楼', 'phone' => '13800000000'];
    rejects(function () { VisitorRegisterValidation::type('other'); }, 'only two form types');
    rejects(function () use ($person) { $person['visit_time'] = '2026-02-30 10:00:00'; VisitorRegisterValidation::data('visitor', $person); }, 'invalid calendar date rejected');
    rejects(function () use ($person) { $person['phone'] = '123'; VisitorRegisterValidation::data('visitor', $person); }, 'invalid phone rejected');
    rejects(function () use ($person) { $person['visitor_name'] = '<script>'; VisitorRegisterValidation::data('visitor', $person); }, 'markup input rejected');
    rejects(function () use ($person) { $person['visitor_name'] = str_repeat('测', 61); VisitorRegisterValidation::data('visitor', $person); }, 'field length enforced');
    check(VisitorRegisterValidation::data('vehicle', $vehicle)['status'] === 'entered', 'empty exit accepted');
    $newEnergy = $vehicle; $newEnergy['plate_number'] = '粤BD12345';
    check(VisitorRegisterValidation::data('vehicle', $newEnergy)['plate_number'] === '粤BD12345', 'new energy plate accepted');
    rejects(function () use ($vehicle) { $vehicle['exit_time'] = '2026-10-11 10:00:00'; VisitorRegisterValidation::data('vehicle', $vehicle); }, 'exit before entry rejected');
    $secret = str_repeat('a', 32);
    $encrypted = $wechat->encrypt($secret);
    check($encrypted !== $secret && $wechat->decrypt($encrypted) === $secret, 'AES-GCM roundtrip');
    $tampered = base64_decode($encrypted); $tampered[28] = chr(ord($tampered[28]) ^ 1);
    rejects(function () use ($wechat, $tampered) { $wechat->decrypt(base64_encode($tampered)); }, 'tampered ciphertext rejected');
    $tokenMock = new VisitorTokenMock();
    $tokenConfig = ['village_id' => 987656, 'version' => time(), 'appid' => 'wx0123456789abcdef', 'secret_encrypted' => $encrypted];
    $tokenMock->response = '{"errcode":40125,"errmsg":"credential MUST_NOT_LEAK"}';
    try { $tokenMock->token($tokenConfig, true); throw new \RuntimeException('Expected token error'); }
    catch (\InvalidArgumentException $e) { check(strpos($e->getMessage(), '40125') !== false && strpos($e->getMessage(), 'MUST_NOT_LEAK') === false, 'WeChat token error sanitized'); }
    $tokenMock->response = '{"access_token":"mock_cached_token","expires_in":7200}';
    check($tokenMock->token($tokenConfig, true) === 'mock_cached_token', 'stable token accepted');
    $calls = $tokenMock->callCount;
    check($tokenMock->token($tokenConfig) === 'mock_cached_token' && $tokenMock->callCount === $calls, 'token cache reused');
    $tokenMock->clear($tokenConfig);
    $tokenMock->token($tokenConfig);
    check($tokenMock->callCount === $calls + 1, 'token cache explicitly invalidated');
    $tokenMock->clear($tokenConfig);
    $service->forms(987654);
    rejects(function () use ($service) { $service->generateQr(987654, 'visitor'); }, 'missing WeChat config blocks QR');
    rejects(function () use ($service) { $service->changeStatus(987654, 'visitor', 'published'); }, 'missing QR blocks publishing');
    $service->saveConfig(987654, ['appid' => 'wx0123456789abcdef', 'appsecret' => $secret]);
    $info = $service->configInfo(987654);
    check($info['has_secret'] && count($info) === 2 && !isset($info['appsecret']), 'secret never returned to frontend');
    $wechat->responses[] = '{"errcode":41030,"errmsg":"invalid page"}';
    rejects(function () use ($service) { $service->generateQr(987654, 'visitor'); }, 'WeChat JSON error does not create QR');
    check(!$service->forms(987654)[0]['qrcode_valid'], 'failed QR is not saved');
    $wechat->responses[] = 'not an image';
    rejects(function () use ($service) { $service->generateQr(987654, 'visitor'); }, 'invalid binary rejected');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    $wechat->responses[] = $png;
    $service->generateQr(987654, 'visitor');
    $path = Db::name('visitor_register_form')->where(['village_id' => 987654, 'form_type' => 'visitor'])->value('qrcode_path'); $files[] = $path;
    check(end($wechat->calls)['body']['page'] === 'pages/visitorRegister/person', 'person fixed page used');
    check(end($wechat->calls)['body']['scene'] === 'v=987654&t=visitor', 'scene binds village and type');
    $originalQr = $service->forms(987654)[0]['qrcode_url'];
    check(strpos($originalQr, 'data:image/png;base64,') === 0, 'preview uses permanent image bytes');
    // Simulate legacy images: remove only the backup, then read to backfill it.
    Db::name('visitor_register_qrcode')->where('qrcode_path', $path)->delete();
    check($service->forms(987654)[0]['qrcode_url'] === $originalQr && Db::name('visitor_register_qrcode')->where('qrcode_path', $path)->count() === 1, 'legacy local image automatically backed up');
    unlink(dirname(__DIR__, 3) . $path);
    Db::name('visitor_register_form')->where(['village_id' => 987654, 'form_type' => 'visitor'])->update(['qrcode_generated_at' => 1]);
    $agedForm = $service->forms(987654)[0];
    check($agedForm['qrcode_valid'] && $agedForm['qrcode_url'] === $originalQr, 'old QR remains identical and valid after local file loss');
    $qrCalls = count($wechat->calls);
    $service->generateQr(987654, 'visitor');
    check(count($wechat->calls) === $qrCalls, 'existing QR reused');
    $service->changeStatus(987654, 'visitor', 'published');
    check($service->publicStatus(987654, 'visitor')['status'] === 'published' && $service->publicStatus(987654, 'vehicle')['status'] === 'draft', 'form states independent');
    $login = $service->login(987654, 'visitor', ['code' => 'login-code']);
    check(!$login['prefill'] && !$login['phone'] && !isset($login['openid']), 'first login exposes no private identifiers or historic unbound data');
    rejects(function () use ($service, $login) { $service->submit(987654, 'visitor', ['auth_token' => $login['auth_token']]); }, 'phone authorization required');
    rejects(function () use ($service, $login) { $service->authorizePhone(987654, 'visitor', ['auth_token' => $login['auth_token'], 'code' => 'phone-code']); }, 'phone collection requires consent');
    $wechat->responses[] = json_encode(['phone_info' => ['purePhoneNumber' => '13800000000', 'watermark' => ['appid' => 'wx0123456789abcdef']]]);
    $authorized = $service->authorizePhone(987654, 'visitor', ['auth_token' => $login['auth_token'], 'code' => 'phone-code', 'privacy_consent' => 1]);
    check($authorized['phone'] === '13800000000', 'WeChat phone code exchanged on server');
    $wechat->responses[] = json_encode(['phone_info' => ['purePhoneNumber' => '13800000000', 'watermark' => ['appid' => 'other-app']]]);
    rejects(function () use ($service, $login) { $service->authorizePhone(987654, 'visitor', ['auth_token' => $login['auth_token'], 'code' => 'phone-code', 'privacy_consent' => 1]); }, 'phone watermark must match configured app');
    $params = ['data' => $person, 'request_id' => 'test_person_request_0001', 'privacy_consent' => 1, 'auth_token' => $authorized['auth_token'], 'nickname' => '测试昵称'];
    $forged = $params; $forged['auth_token'] = 'forged';
    rejects(function () use ($service, $forged) { $service->submit(987654, 'visitor', $forged); }, 'forged identity rejected');
    $expired = json_decode($wechat->decrypt($authorized['auth_token']), true);
    $expired['expires'] = time() - 1;
    $forged['auth_token'] = $wechat->encrypt(json_encode($expired));
    rejects(function () use ($service, $forged) { $service->submit(987654, 'visitor', $forged); }, 'expired identity rejected');
    $params['data']['phone'] = '13999999999';
    $first = $service->submit(987654, 'visitor', $params);
    check(Db::name('visitor_register_person')->value('phone') === '13800000000', 'client cannot forge verified phone');
    $again = $service->login(987654, 'visitor', ['code' => 'new-login-code']);
    check($again['nickname'] === '测试昵称' && $again['phone'] === '13800000000' && $again['prefill']['host_name_room'] === $person['host_name_room'], 'same-day own information restored');
    check(!isset($again['prefill']['visit_time']) && !isset($again['prefill']['id']), 'prefill excludes previous visit times and internal fields');
    $wechat->user = 'different-user';
    check(!$service->login(987654, 'visitor', ['code' => 'login-code'])['prefill'], 'different WeChat user cannot read another record');
    $wechat->user = 'mock-openid-a';
    Db::name('visitor_register_identity')->where('village_id', 987654)->update(['record_date' => '2000-01-01']);
    check(!$service->login(987654, 'visitor', ['code' => 'login-code'])['prefill'], 'previous-day information not returned');
    Db::name('visitor_register_identity')->where('village_id', 987654)->update(['record_date' => (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d')]);
    rejects(function () use ($service, $params) { $params['data']['visitor_name'] = '不同访客'; $service->submit(987654, 'visitor', $params); }, 'request ID cannot be reused for different payload');
    rejects(function () use ($service, $params) { $params['privacy_consent'] = 0; $service->submit(987654, 'visitor', $params); }, 'privacy consent required');
    check($service->submit(987654, 'visitor', $params) === $first && Db::name('visitor_register_person')->count() === 1, 'same request ID is idempotent');
    $params['request_id'] = 'test_person_request_0002';
    check($service->submit(987654, 'visitor', $params) === $first, 'fresh ID duplicate suppressed');
    $rows = $service->records(987654, 'visitor', []);
    check($rows['list'][0]['phone'] === '138****0000' && $rows['list'][0]['organization_or_id'] !== $person['organization_or_id'], 'list masks personal information');
    check($service->records(987655, 'visitor', [])['total'] === 0, 'cross-village list isolated');
    rejects(function () use ($service, $rows) { $service->detail(987655, 'visitor', $rows['list'][0]['id']); }, 'cross-village detail rejected');
    $service->changeStatus(987654, 'visitor', 'disabled');
    rejects(function () use ($service, $params) { $service->submit(987654, 'visitor', $params); }, 'disabled form blocks submission, including retry');
    check($service->forms(987654)[0]['qrcode_valid'], 'disable retains QR');
    $service->changeStatus(987654, 'visitor', 'published');
    $wechat->responses[] = $png;
    array_unshift($wechat->responses, '{"errcode":42001,"errmsg":"expired"}');
    $service->generateQr(987654, 'vehicle');
    $files[] = Db::name('visitor_register_form')->where(['village_id' => 987654, 'form_type' => 'vehicle'])->value('qrcode_path');
    check(end($wechat->calls)['body']['page'] === 'pages/visitorRegister/vehicle', 'vehicle fixed page used');
    check(count($wechat->responses) === 0, 'expired QR token retried once');
    $service->changeStatus(987654, 'vehicle', 'published');
    rejects(function () use ($service, $params) { $service->submit(987654, 'vehicle', $params); }, 'identity cannot cross form types');
    $carLogin = $service->login(987654, 'vehicle', ['code' => 'login-code']);
    check(!$carLogin['prefill'], 'person record never prefills vehicle form');
    $wechat->responses[] = json_encode(['phone_info' => ['purePhoneNumber' => '13800000000', 'watermark' => ['appid' => 'wx0123456789abcdef']]]);
    $carAuth = $service->authorizePhone(987654, 'vehicle', ['auth_token' => $carLogin['auth_token'], 'code' => 'phone-code', 'privacy_consent' => 1]);
    $service->submit(987654, 'vehicle', ['data' => $vehicle, 'request_id' => 'test_vehicle_request_001', 'privacy_consent' => 1, 'auth_token' => $carAuth['auth_token'], 'nickname' => '车主']);
    $carAgain = $service->login(987654, 'vehicle', ['code' => 'login-code']);
    check($carAgain['prefill']['plate_number'] === $vehicle['plate_number'] && !isset($carAgain['prefill']['exit_time']), 'vehicle fields restored without previous exit time');
    $id = Db::name('visitor_register_vehicle')->value('id');
    rejects(function () use ($service, $id) { $service->registerExit(987654, $id, '2026-10-11 00:00:00'); }, 'admin exit before entry rejected');
    $service->registerExit(987654, $id, '2026-10-12 11:00:00');
    check($service->detail(987654, 'vehicle', $id)['status'] === 'exited', 'admin exit updates state');
    rejects(function () use ($service, $id) { $service->registerExit(987654, $id, '2026-10-12 12:00:00'); }, 'duplicate exit rejected');
    $params['request_id'] = 'test_person_request_latest';
    $params['data']['host_name_room'] = '2栋202';
    $params['nickname'] = '新昵称';
    $service->submit(987654, 'visitor', $params);
    check($service->login(987654, 'visitor', ['code' => 'login-code'])['prefill']['host_name_room'] === '2栋202', 'most recent same-day submission replaces earlier prefill');
    $mobile = $service->mobileVisitorRecords(987654, ['page_size' => 1]);
    check($mobile['total'] === 2 && $mobile['has_more'] && $mobile['list'][0]['nickname'] === $person['visitor_name'], 'mobile records prefer entered visitor name with correct pagination');
    $older = $service->mobileVisitorRecords(987654, ['page' => 2, 'page_size' => 1]);
    check(!$older['has_more'] && $older['list'][0]['nickname'] === $person['visitor_name'], 'historical visitor name takes priority over nickname');
    $latestNo = $mobile['list'][0]['registration_no'];
    $mobileDetail = $service->mobileVisitorDetail(987654, $latestNo);
    check($mobileDetail['organization_or_id'] === $person['organization_or_id'] && $mobileDetail['host_name_room'] === '2栋202' && $mobileDetail['phone'] === '13800000000', 'mobile detail includes complete registration content');
    check(count($mobileDetail) === 9 && !isset($mobileDetail['payload_hash']) && !isset($mobileDetail['request_id']), 'mobile detail excludes internal identity and submission fields');
    rejects(function () use ($service, $latestNo) { $service->mobileVisitorDetail(987655, $latestNo); }, 'mobile detail rejects other village record');
    rejects(function () use ($service, $latestNo) { $service->mobileVisitorDetail(0, $latestNo); }, 'mobile detail requires login village');
    rejects(function () use ($service) { $service->mobileVisitorDetail(987654, 'invalid'); }, 'invalid mobile record reference rejected');
    Db::name('visitor_register_person')->where('registration_no', $latestNo)->update(['visitor_name' => '   ']);
    check($service->mobileVisitorRecords(987654, ['page_size' => 1])['list'][0]['nickname'] === '新昵称', 'blank visitor name falls back to stored nickname');
    Db::name('visitor_register_record_profile')->where('registration_no', $latestNo)->update(['nickname' => '']);
    check($service->mobileVisitorRecords(987654, ['page_size' => 1])['list'][0]['nickname'] === '未提供昵称', 'empty visitor name and nickname keep existing placeholder');
    Db::name('visitor_register_person')->where('registration_no', $latestNo)->update(['visitor_name' => $person['visitor_name']]);
    Db::name('visitor_register_record_profile')->where('registration_no', $latestNo)->update(['nickname' => '新昵称']);
    check(array_keys($mobile['list'][0]) === ['registration_no', 'phone', 'nickname', 'registration_time'], 'mobile API exposes only row key and requested fields');
    check($mobile['list'][0]['phone'] === '13800000000' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $mobile['list'][0]['registration_time']), 'mobile shows phone and formatted submission time');
    $allowed = (new MobileVisitorControllerMock(987654))->records()->getData();
    check($allowed['status'] === 1000 && $allowed['data']['total'] === 2, 'authorized village administrator can view records');
    $isolated = (new MobileVisitorControllerMock(987655))->records()->getData();
    check($isolated['status'] === 1000 && $isolated['data']['total'] === 0, 'forged request village cannot bypass login village');
    check((new MobileVisitorControllerMock(987654, []))->records()->getData()['status'] === 1000, 'login without menu permission can view records');
    check((new MobileVisitorControllerMock(987654, [900], 6))->records()->getData()['status'] === 1000, 'logged-in worker can view records');
    $loggedOut = new MobileVisitorControllerMock(987654);
    $loggedOut->loginResult = ['status' => 1002, 'msg' => '登录失效'];
    check($loggedOut->records()->getData()['status'] === 1002, 'expired mobile login is denied');
    $service->saveConfig(987655, ['appid' => 'wx0123456789abcdef', 'appsecret' => $secret]);
    Db::name('visitor_register_form')->where(['village_id' => 987655, 'form_type' => 'visitor'])->update(['status' => 'published']);
    check(!$service->login(987655, 'visitor', ['code' => 'login-code'])['prefill'], 'same WeChat account cannot restore across villages');
    rejects(function () use ($service, $params) { $service->submit(987655, 'visitor', $params); }, 'token cannot be used in a different village');
    $stale = json_decode($wechat->decrypt($authorized['auth_token']), true);
    $stale['day'] = '2000-01-01';
    $staleParams = $params;
    $staleParams['auth_token'] = $wechat->encrypt(json_encode($stale));
    rejects(function () use ($service, $staleParams) { $service->submit(987654, 'visitor', $staleParams); }, 'previous-day session cannot submit');
    $noNickname = $params;
    unset($noNickname['nickname']);
    $noNickname['request_id'] = 'test_without_nickname_001';
    $service->submit(987654, 'visitor', $noNickname);
    check(Db::name('visitor_register_person')->count() === 3, 'registration accepts phone authorization without nickname');
    $dayStart = (new \DateTimeImmutable('2026-10-22 00:00:00', new \DateTimeZone('Asia/Shanghai')))->getTimestamp();
    Db::name('visitor_register_person')->where('village_id', 987654)->update(['create_time' => $dayStart - 1]);
    Db::name('visitor_register_person')->where('registration_no', $latestNo)->update(['create_time' => $dayStart]);
    check($service->mobileVisitorRecords(987654, ['date' => '2026-10-22'])['total'] === 1, 'date filter includes midnight and excludes previous day');
    Db::name('visitor_register_person')->where('registration_no', $latestNo)->update(['create_time' => $dayStart + 86400]);
    check($service->mobileVisitorRecords(987654, ['date' => '2026-10-22'])['total'] === 0, 'date filter excludes next midnight');
    rejects(function () use ($service) { $service->mobileVisitorRecords(987654, ['date' => '2026-02-30']); }, 'invalid filter date rejected');
    $export = $service->export(987654, 'visitor', []);
    check(substr(base64_decode($export['content']), 0, 2) === 'PK', 'valid XLSX generated without public download file');
    for ($i = 0; $i < 10; $i++) { $service->rateLimit('test-visitor-' . getmypid(), true); }
    rejects(function () use ($service) { $service->rateLimit('test-visitor-' . getmypid(), true); }, 'atomic public rate limit enforced');
    $service->rateLimit('test-visitor-' . getmypid(), true, 'login');
    check(true, 'login uses a separate quota from submissions');
    $service->saveConfig(987654, ['appid' => 'wxfedcba9876543210', 'appsecret' => $secret]);
    foreach ($service->forms(987654) as $form) { check($form['status'] === 'draft' && !$form['qrcode_valid'], 'AppID change invalidates each QR and publication'); }
    echo 'ALL ' . $checks . ' CHECKS PASSED (mock WeChat; isolated test database)' . PHP_EOL;
} finally {
    Db::execute('DROP TABLE IF EXISTS `' . $migrationMenu . '`');
    foreach ($files as $path) {
        if (preg_match('#^/upload/visitor_register/[a-f0-9]{32}\.(png|jpg)$#D', $path)) { @unlink(dirname(__DIR__, 3) . $path); }
    }
}
