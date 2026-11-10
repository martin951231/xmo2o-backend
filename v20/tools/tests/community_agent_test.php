<?php
/** Offline workflow tests: real ThinkORM + an in-memory SQLite database.
 * php community_agent_test.php [path/to/v20/vendor]
 * No application bootstrap, credentials, model requests or real business database.
 */
namespace think\facade {
    class Db {
        public static $manager;
        public static $engine = 'InnoDB';
        public static function __callStatic($method, $args) {
            if ($method === 'query' && strpos($args[0], 'information_schema.TABLES') !== false) {
                return [['ENGINE'=>self::$engine]];
            }
            return call_user_func_array([self::$manager, $method], $args);
        }
    }
}
namespace app\community\model\service {
    class CommunityAgentIntentClient {
        public static $result = ['intent'=>'express_pickup', 'action'=>'confirm_intent', 'prefill'=>['pickup_code'=>'8-2034']];
        public function recognize($message) { return self::$result; }
    }
    class HouseVillageService {
        public function getSingleFloorRoom(...$args) { return '测试小区1-1-101'; }
    }
    class ExpressPickupService {
        public static $fail = false;
        public function addPickupApplication($data) {
            $id = \think\facade\Db::name('express_application')->insertGetId($data);
            if (self::$fail) { throw new \RuntimeException('injected failure after insert'); }
            return $id;
        }
    }
}
namespace {
    use think\facade\Db;
    use app\community\model\service\CommunityAgentService;
    use app\community\model\service\CommunityAgentPickupPolicy as Policy;
    use app\community\model\service\CommunityAgentIntentClient as Intent;
    use app\community\model\service\ExpressPickupService as Pickup;

    $vendor = $argv[1] ?? dirname(__DIR__, 2).'/vendor';
    spl_autoload_register(function ($class) use ($vendor) {
        if (strpos($class, 'think\\') !== 0) { return; }
        $relative = str_replace('\\', '/', substr($class, 6)).'.php';
        foreach (['think-orm', 'think-helper', 'think-container'] as $package) {
            $path = $vendor.'/topthink/'.$package.'/src/'.$relative;
            if (is_file($path)) { require $path; return; }
        }
    });
    require dirname(__DIR__, 2).'/app/community/model/service/CommunityAgentPickupPolicy.php';
    require dirname(__DIR__, 2).'/app/community/model/service/CommunityAgentService.php';
    // SQLite has no FOR UPDATE. This suite checks transaction/state semantics;
    // MySQL row-lock concurrency still requires a deployment integration test.
    class SqliteTestQuery extends \think\db\Query {
        public function lock($lock = false) { return $this; }
    }
    Db::$manager = new \think\DbManager();
    Db::setConfig(['default'=>'sqlite', 'connections'=>['sqlite'=>[
        'type'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'fields_strict'=>true, 'query'=>SqliteTestQuery::class,
    ]]]);
    foreach ([
        'community_agent_session'=>'id TEXT PRIMARY KEY, uid INTEGER, village_id INTEGER, bind_id INTEGER, state TEXT, suggested_intent TEXT, intent TEXT, payload TEXT, version INTEGER, application_id INTEGER, created_at INTEGER, updated_at INTEGER, expires_at INTEGER',
        'house_village'=>'village_id INTEGER PRIMARY KEY, express_person_average_entrust INTEGER, express_project_daily_entrust INTEGER, express_project_startDate TEXT, express_project_endDate TEXT',
        'house_village_user_bind'=>'pigcms_id INTEGER PRIMARY KEY, uid INTEGER, village_id INTEGER, status INTEGER, type INTEGER, vacancy_id INTEGER, name TEXT, phone TEXT, single_id INTEGER, floor_id INTEGER, layer_id INTEGER, room_addrss TEXT, authentication_field TEXT',
        'house_village_user_vacancy'=>'pigcms_id INTEGER PRIMARY KEY, village_id INTEGER, single_id INTEGER, floor_id INTEGER, layer_id INTEGER, room TEXT',
        'common_pickup_person'=>'id INTEGER PRIMARY KEY, user_id INTEGER, village_id INTEGER, status INTEGER, is_default INTEGER, recipient_name TEXT, recipient_phone TEXT, location_id INTEGER',
        'express_pick_up_location'=>'id INTEGER PRIMARY KEY, village_id INTEGER, status INTEGER, location TEXT',
        'express_application'=>'id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, village_id INTEGER, status INTEGER, recipient_id INTEGER, recipient_name TEXT, recipient_phone TEXT, pickup_code TEXT, track_num TEXT, location_id INTEGER, remark TEXT, single_id INTEGER, floor_id INTEGER, layer_id INTEGER, room_id INTEGER, room_name TEXT, create_by TEXT, create_time TEXT',
    ] as $table=>$columns) { Db::execute('CREATE TABLE '.$table.' ('.$columns.')'); }
    Db::name('house_village')->insert(['village_id'=>10, 'express_person_average_entrust'=>5, 'express_project_daily_entrust'=>20, 'express_project_startDate'=>'', 'express_project_endDate'=>'']);
    $agreement = serialize(['is_sign_agreement'=>['value'=>'是']]);
    Db::name('house_village_user_bind')->insert(['pigcms_id'=>100, 'uid'=>7, 'village_id'=>10, 'status'=>1, 'type'=>1, 'vacancy_id'=>200, 'name'=>'测试住户', 'phone'=>'13800000000', 'single_id'=>1, 'floor_id'=>1, 'layer_id'=>1, 'room_addrss'=>'101', 'authentication_field'=>$agreement]);
    Db::name('house_village_user_vacancy')->insert(['pigcms_id'=>200, 'village_id'=>10, 'single_id'=>1, 'floor_id'=>1, 'layer_id'=>1, 'room'=>'101']);
    Db::name('express_pick_up_location')->insert(['id'=>1, 'village_id'=>10, 'status'=>1, 'location'=>'测试取件点']);
    Db::name('express_pick_up_location')->insert(['id'=>2, 'village_id'=>11, 'status'=>1, 'location'=>'其他小区']);
    Db::name('common_pickup_person')->insert(['id'=>1, 'user_id'=>7, 'village_id'=>10, 'status'=>1, 'is_default'=>1, 'recipient_name'=>'默认收件人', 'recipient_phone'=>'13900000000', 'location_id'=>1]);
    $checks = 0;
    function check($condition, $message) {
        global $checks;
        if (!$condition) { throw new \RuntimeException('FAIL: '.$message); }
        $checks++;
    }
    function rejects(callable $fn, $contains) {
        try { $fn(); } catch (\Throwable $e) { check(strpos($e->getMessage(), $contains) !== false, $e->getMessage()); return; }
        throw new \RuntimeException('Expected rejection: '.$contains);
    }
    $service = new CommunityAgentService();
    $s = $service->start(7, 10, 100, '取快递');
    check($s['ui_action'] === 'confirm_intent', 'recognition asks yes/no');
    check(Db::name('express_application')->count() === 0, 'recognition never writes business');
    rejects(function () use ($service, $s) { $service->submit(7, $s['session_id'], 1, true); }, '请先确认需求');
    rejects(function () use ($service, $s) { $service->detail(8, $s['session_id']); }, '不存在');
    rejects(function () use ($service) { $service->start(8, 10, 100, '取快递'); }, '住户身份');
    $s = $service->choose(7, $s['session_id'], 1, 'reject', '');
    check($s['ui_action'] === 'select_intent', 'no shows dropdown');
    rejects(function () use ($service, $s) { $service->choose(7, $s['session_id'], $s['version'], 'manual', 'repair'); }, '暂未接入');
    $s = $service->choose(7, $s['session_id'], $s['version'], 'manual', 'express_pickup');
    check($s['ui_action'] === 'open_form' && $s['state'] === 'editing', 'selection only opens modal');
    check($s['form']['recipient_name'] === '默认收件人' && $s['form']['pickup_code'] === '8-2034', 'defaults and literal prefill');
    check(Db::name('express_application')->count() === 0, 'choice never submits');
    $form = $s['form'];
    rejects(function () use ($service, $s, $form) { $service->saveDraft(7, $s['session_id'], 1, $form); }, '信息已更新');
    $foreign = array_merge($form, ['location_id'=>2]);
    rejects(function () use ($service, $s, $foreign) { $service->saveDraft(7, $s['session_id'], $s['version'], $foreign); }, '不属于');
    $s = $service->saveDraft(7, $s['session_id'], $s['version'], array_merge($form, ['pickup_code'=>'']));
    check($s['missing_fields'] === ['pickup_code'] && !$s['can_submit'], 'missing data remains in modal');
    $s = $service->saveDraft(7, $s['session_id'], $s['version'], $form);
    check($s['can_submit'], 'complete draft ready');
    rejects(function () use ($service, $s) { $service->submit(7, $s['session_id'], $s['version'], false); }, '请确认提交');
    Db::name('house_village_user_bind')->where('pigcms_id', 100)->update(['authentication_field'=>'']);
    rejects(function () use ($service, $s) { $service->submit(7, $s['session_id'], $s['version'], true); }, '签署');
    Db::name('house_village_user_bind')->where('pigcms_id', 100)->update(['authentication_field'=>$agreement]);
    Db::name('express_pick_up_location')->where('id', 1)->update(['status'=>0]);
    rejects(function () use ($service, $s) { $service->submit(7, $s['session_id'], $s['version'], true); }, '已停用');
    Db::name('express_pick_up_location')->where('id', 1)->update(['status'=>1]);
    Pickup::$fail = true;
    rejects(function () use ($service, $s) { $service->submit(7, $s['session_id'], $s['version'], true); }, 'injected failure');
    check(Db::name('express_application')->count() === 0, 'transaction rolls back business insertion');
    check($service->detail(7, $s['session_id'])['version'] === $s['version'], 'failed submit keeps draft version');
    Pickup::$fail = false;
    $result = $service->submit(7, $s['session_id'], $s['version'], true);
    $repeat = $service->submit(7, $s['session_id'], $s['version'], true);
    check($result['application_id'] === $repeat['application_id'] && Db::name('express_application')->count() === 1, 'duplicate request returns same application');
    rejects(function () use ($service, $result, $form) { $service->saveDraft(7, $result['session_id'], $result['version'], $form); }, '已结束');
    check((int)Db::name('common_pickup_person')->where('id', 1)->value('is_default') === 1, 'submission does not alter default contacts');
    Intent::$result = ['intent'=>'unknown', 'action'=>'select_intent', 'prefill'=>[]];
    $manual = $service->start(7, 10, 100, '你好');
    check($manual['ui_action'] === 'select_intent', 'unrecognized message shows dropdown directly');
    $cancelled = $service->cancel(7, $manual['session_id'], 1);
    check($cancelled['ui_action'] === 'close_modal', 'cancel closes modal');
    rejects(function () use ($service, $cancelled) { $service->choose(7, $cancelled['session_id'], $cancelled['version'], 'manual', 'express_pickup'); }, '已结束');
    $expired = $service->start(7, 10, 100, '取快递');
    Db::name('community_agent_session')->where('id', $expired['session_id'])->update(['expires_at'=>time()-1]);
    rejects(function () use ($service, $expired) { $service->choose(7, $expired['session_id'], 1, 'manual', 'express_pickup'); }, '已过期');
    rejects(function () { Policy::assertQuota(0, 2, 0, 2); }, '名额已满');
    rejects(function () { Policy::assertQuota(1, 20, 1, 1); }, '每户');
    check(!Policy::blockedTime('', '', '12:00') && Policy::blockedTime('10:00', '14:00', '12:00'), 'time policy matches configured blocked interval');
    Intent::$result = ['intent'=>'express_pickup', 'action'=>'confirm_intent', 'prefill'=>[]];
    $yes = $service->start(7, 10, 100, '帮我拿快递');
    $yes = $service->choose(7, $yes['session_id'], 1, 'accept', 'repair');
    check($yes['intent'] === 'express_pickup' && $yes['state'] === 'editing', 'yes uses server suggestion, ignoring posted intent');
    check(in_array('pickup_code', $yes['missing_fields'], true), 'missing pickup code is never invented');
    check(Db::name('express_application')->count() === 1, 'yes does not create an extra application');
    $ready = $service->saveDraft(7, $yes['session_id'], $yes['version'], array_merge($yes['form'], ['pickup_code'=>'0']));
    check($ready['can_submit'], 'literal zero pickup code is retained');
    Db::name('house_village')->where('village_id', 10)->update(['express_project_daily_entrust'=>1]);
    rejects(function () use ($service, $ready) { $service->submit(7, $ready['session_id'], $ready['version'], true); }, '名额已满');
    Db::name('house_village')->where('village_id', 10)->update(['express_project_daily_entrust'=>20]);
    Db::name('house_village_user_bind')->where('pigcms_id', 100)->update(['status'=>0]);
    rejects(function () use ($service, $ready) { $service->submit(7, $ready['session_id'], $ready['version'], true); }, '住户身份');
    Db::name('house_village_user_bind')->where('pigcms_id', 100)->update(['status'=>1]);
    Db::$engine = 'MyISAM';
    rejects(function () use ($service) { $service->start(7, 10, 100, '取快递'); }, '数据库初始化');
    echo "PASS: $checks workflow checks (SQLite, no real submissions)\n";
}
