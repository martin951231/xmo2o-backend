<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\MerchantService;
use app\api\model\service\building\PlatformService;
use app\api\model\service\building\ShoppingMallService;
use app\api\model\service\building\StoreService;
use app\api\model\service\building\UserService;
use app\building\service\OrderService;
use app\building\service\ShoppingMallService as ServiceShoppingMallService;

//楼宇对接接口
class BuildingController extends BaseController
{
    //入口
    public function index()
    {
        $method = request()->post('method', '', 'trim');
        if (empty($method)) {
            return $this->apiOutput(1001, [], 'method不能为空');
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
                case 'open.platform.getIframeUrl': //获取O2O系统后台无菜单iframe地址
                    $hideMenu = $params['hide_menu'] ?? 1;
                    $account = $params['account'] ?? '';
                    $res = (new PlatformService())->getIframeUrl($params['menu_id'], $hideMenu, $account);
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
                case 'open.user.getAccountInfo': //获取用户账户信息
                    $res = (new UserService())->getAccountInfo($params['phone']);
                    break;
                case 'open.user.changeScore': //变更用户积分
                    $res = (new UserService())->changeScore($params);
                    break;
                case 'open.user.parkingCoupon': //用户会员等级停车优惠
                    $res = (new UserService())->parkingCoupon($params);
                    break;
                case 'open.pay.createOrder': //订单创建
                    $res = (new OrderService())->createOrder($params);
                    break;
                case 'open.pay.queryOrder': //订单查询
                    $res = (new OrderService())->queryOrder($params);
                    break;
                case 'open.pay.refundOrder': //订单退款
                    $res = (new OrderService())->refundOrder($params);
                    break;
                case 'open.pay.queryRefund': //退款查询
                    $res = (new OrderService())->queryRefund($params['refund_no']);
                    break;
                default:
                    return $this->apiOutput(1001, [], 'method参数错误');
                    break;
            }

            //楼宇机构id变化了，需要更新
            $orgId = request()->post('org_id', 0, 'intval');
            if ($orgId > 0 && $orgId != cfg('building_org_id')) {
                (new \app\common\model\service\ConfigService())->saveConfigData(['name' => 'building_org_id', 'value' => $orgId]);
                (new \app\common\model\service\CacheSqlService())->clearCache();
            }

            return $this->apiOutput(1000, $res, 'ok');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //获取用户最大可使用积分数量
    public function scoreRule()
    {
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['cost_type'] = $this->request->param('cost_type', '', 'trim');
        $params['amount'] = $this->request->param('amount', '', 'trim');
        try {
            $res = (new ServiceShoppingMallService())->getScoreRule($params);
            return $this->apiOutput(1000, $res, 'ok');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }
}
