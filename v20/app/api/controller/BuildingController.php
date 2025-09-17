<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\MerchantService;
use app\api\model\service\building\PlatformService;
use app\api\model\service\building\ShoppingMallService;
use app\api\model\service\building\StoreService;
use app\api\model\service\building\UserService;

//楼宇对接接口
class BuildingController
{
    public static function apiOutput($code = 0, $data = [], $msg = '')
    {
        $output = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        return json($output)->code(200);
    }

    //入口
    public function index()
    {
        $method = request()->post('method', '', 'trim');
        $appkey = request()->post('appkey', '', 'trim');
        if (empty($appkey)) {
            return self::apiOutput(1001, [], 'appkey不能为空');
        }
        if (empty(cfg('o2o_appkey')) || $appkey != cfg('o2o_appkey')) {
            return self::apiOutput(1001, [], 'appkey校验失败');
        }
        if (empty($method)) {
            return self::apiOutput(1001, [], 'method不能为空');
        }

        $params = request()->post();
        try {
            switch ($method) {
                case 'open.merchant.create':  //自动创建商家
                    $res = (new MerchantService())->create($params);
                    break;
                case 'open.merchant.getLoginUrl': //免登录进入020商家后台
                    $res = (new MerchantService())->getLoginUrl($params);
                    break;
                case 'open.platform.getLoginUrl': //免登录进入 020 总后台
                    $res = (new PlatformService())->getLoginUrl($params['account']);
                    break;
                case 'open.store.create': //自动创建店铺
                    $res = (new StoreService())->create($params);
                    break;
                case 'open.shoppingMall.create': //自动创建商场
                    $res = (new ShoppingMallService())->create($params);
                    break;
                case 'open.store.bindShoppingMall': //店铺绑定/解绑商场
                    $res = (new StoreService())->bindShoppingMall($params['store_id'], $params['mall_id']);
                    break;
                case 'open.store.dailyReport': //店铺每天收支报表
                    $res = (new StoreService())->dailyReport($params['store_id'], $params['date']);
                    break;
                case 'open.user.getInfo': //获取用户登录信息
                    $res = (new UserService())->getInfo($params['code']);
                    break;
                default:
                    return self::apiOutput(1001, [], 'method参数错误');
                    break;
            }

            //楼宇机构id变化了，需要更新
            $orgId = request()->post('org_id', 0, 'intval');
            if ($orgId > 0 && $orgId != cfg('building_org_id')) {
                (new \app\common\model\service\ConfigService())->saveConfigData(['name' => 'building_org_id', 'value' => $orgId]);
                (new \app\common\model\service\CacheSqlService())->clearCache();
            }

            return self::apiOutput(1000, $res, 'ok');
        } catch (\Exception $e) {
            return self::apiOutput(1001, [], $e->getMessage());
        }
    }
}
