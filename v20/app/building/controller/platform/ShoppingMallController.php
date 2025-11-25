<?php

declare(strict_types=1);

namespace app\building\controller\platform;

use app\building\model\ShoppingMall;
use app\building\model\ShoppingMallAdver;
use app\building\model\ShoppingMallBindAdmin;
use app\building\model\ShoppingMallLikeSetting;
use app\building\model\ShoppingMallMembershipBenefits;
use app\building\model\ShoppingMallParkingSetting;
use app\building\model\ShoppingMallWifi;
use app\building\model\ShoppingTicketPointApply;
use app\building\service\ShoppingMallService;
use app\building\validate\ShoppingMall as ShoppingMallValidate;
use app\building\service\ShoppingTicketPointApplyService;
use app\common\controller\platform\AuthBaseController;
use app\common\model\db\Admin;
use think\response\Json;

class ShoppingMallController extends AuthBaseController
{
	public function initialize()
	{
		$this->validateClass = ShoppingMallValidate::class;
		parent::initialize();
	}
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    //获取列表
    public function getLists()
    {
        $params['name'] = $this->request->param('title', '', 'trim');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
		if($this->systemUser['level'] != Admin::LEVEL_SUPER) {
			$params['mall_ids'] = ShoppingMallBindAdmin::where('admin_id', $this->_uid)->column('mall_id') ?? [0];
		}
	    $lists = (new ShoppingMall())->getLists($params);
        return api_output(0, $lists);
    }

    //切换商场状态
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = (new ShoppingMall())->changeStatus($id);
        return api_output(0, $result);
    }

    //商场详情
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = (new ShoppingMall())->getDetail($id);
        return api_output(0, $result);
    }

    //商场绑定的店铺
    public function getBindStore()
    {
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['store_name'] = $this->request->param('store_name', '', 'trim');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $lists = (new ShoppingMall())->getBindStoreLists($params);
        return api_output(0, $lists);
    }

    //获取管理员列表
    public function getBindAdminUser()
    {
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $lists = (new ShoppingMall())->getBindAdminUserLists($params);
        return api_output(0, $lists);
    }

    //切换绑定状态
    public function changeBindStatus()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $adminId = $this->request->param('admin_id', 0, 'intval');
        $lists = (new ShoppingMall())->changeBindStatus($mallId, $adminId);
        return api_output(0, $lists);
    }

    //获取装修数据
    public function decorate()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $mall = (new ShoppingMall())->where('id', $mallId)->field('name,short_name,footer_title_color,footer_focus_title_color,banner_type')->find();
        if (empty($mall)) {
            return api_output_error(1003, '商场不存在');
        }
        $mall['url'] = get_base_url('pages/shoppingMall/index?mall_id=' . $mallId);
        return api_output(0, $mall);
    }

    //商场广告轮播图
    public function getAdverLists()
    {
        $catKey = $this->request->param('cat_key', 0, 'trim');
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $pageSize = $this->request->param('page_size', 10, 'intval');
        try {
            $arr = (new ShoppingMallAdver())->getList($mallId, $catKey, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //获取广告信息
    public function getAdver()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr['now_adver'] = (new ShoppingMallAdver())->getAdver($id);

            //小程序列表
            $wxappList = (new \app\mall\model\service\WxappOtherService())->getAll();
            $arr['wxapp_list'] = $wxappList;
            //ios列表
            $appList = (new \app\mall\model\service\AppOtherService())->getAll();
            $arr['app_list'] = $appList;
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    //保存商场广告信息
    public function saveAdver()
    {
        try {
            $arr = (new ShoppingMallAdver())->saveAdver($this->request->param());
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //删除
    public function delAdver()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new ShoppingMallAdver())->delAdver($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //设置底部导航文字颜色
    public function setMallConfig()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $name = $this->request->param('config_name', '', 'trim');
        $value = $this->request->param('config_value', '', 'trim');

        try {
            if ($mallId < 0 || empty($name) || empty($value)) {
                return api_output_error(1003, '参数错误');
            }
            $arr = (new ShoppingMall())->where('id', $mallId)->update([$name => $value]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getGuessYouLike()
    {
        try {
            $result = (new ShoppingMallLikeSetting())->getGuessYouLike($this->request->param('mall_id', 0, 'intval'));
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //设置猜你喜欢
    public function setGuessYouLike()
    {
        try {
            (new ShoppingMallLikeSetting())->setGuessYouLike($this->request->param());
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //拍照积分基础设置配置
    public function saveTicketPointConfig()
    {
        $params = [
            'open_ticket_point' => $this->request->param('open_ticket_point', 0, 'intval'),
            'ticket_point_type' => $this->request->param('ticket_point_type', '', 'trim'),
            'ticket_point_score_number' => $this->request->param('ticket_point_score_number', '', 'trim'),
            'ticket_point_rule' => $this->request->param('ticket_point_rule', '', 'trim'),
        ];
        try {
            (new \app\common\model\service\ConfigDataService())->amend($params);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getTicketPointConfig()
    {
        try {
            $where = [
                ['name', 'IN', ['open_ticket_point', 'ticket_point_type', 'ticket_point_score_number', 'ticket_point_rule']]
            ];
            $result = (new \app\common\model\db\ConfigData())
                ->where($where)
                ->column('value', 'name');
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //获取积分拍照申请列表
    public function getTicketPointLists()
    {
        $params['nickname'] = $this->request->param('nickname', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['real_orderid'] = $this->request->param('real_orderid', '', 'trim');
        $params['status'] = $this->request->param('status', 0, 'intval');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $params['mall_id'] = $this->request->param('mall_id', null, 'intval');

        try {
            $result = (new ShoppingTicketPointApply())->getLists($params);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //获取积分拍照申请详情
    public function getTicketPointDetail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = (new ShoppingTicketPointApplyService())->getTicketPointDetail($id);
        return api_output(0, $result, 'success');
    }

    //根据金额计算积分
    public function calTicketGetScore()
    {
        $money = $this->request->param('money', 0, 'floatval');
        $result = (new ShoppingTicketPointApplyService())->calTicketGetScoreCount($money);
        return api_output(0, ['score' => $result], 'success');
    }

    //审核积分拍照
    public function handleTicketPoint()
    {
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $reason =  $this->request->param('reason', '', 'trim');
        $money  = $this->request->param('money', 0, 'floatval');
        $adminAccount = $this->systemUser['account'];
        $cacheKey = 'shopping_ticket_point_apply_' . $id;
        try {
            if (cache($cacheKey)) {
                return api_output_error(1003, '请勿重复提交审核');
            }
            cache($cacheKey, 1, 10);
            $result = (new ShoppingTicketPointApplyService())->handleTicketPoint($id, $status, $reason, $money, $adminAccount);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //审核记录
    public function handleTicketPointLogs()
    {
        $applyId = $this->request->param('apply_id', 0, 'intval');
        $logs = (new ShoppingTicketPointApplyService())->handleTicketPointLogs($applyId);
        return api_output(0, $logs, 'success');
    }


    //获取WIFI列表
    public function getWifiLists()
    {
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'intval');
        $lists = (new ShoppingMallWifi())->getLists($params);
        return api_output(0, $lists);
    }

    //添加/编辑WIFI
    public function saveWifi()
    {
        $params['id'] = $this->request->param('id', 0, 'intval');
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['ssid'] = $this->request->param('ssid', '', 'trim');
        $params['password'] = $this->request->param('password', '', 'trim');
        $params['status'] = $this->request->param('status', 0, 'intval');
        try {
            $lists = (new ShoppingMallWifi())->saveWifi($params);
            return api_output(0, $lists);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //删除WIFI
    public function deleteWifi()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new ShoppingMallWifi())->deleteWifi($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //切换WiFi状态
    public function changeWifiStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new ShoppingMallWifi())->changeWifiStatus($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //用户等级列表
    public function userLevels()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        try {
            $levels = (new ShoppingMallMembershipBenefits())->userLevels($mallId);
            return api_output(0, $levels, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //保存商场用户权益设置
    public function saveParkingDiscount()
    {
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['level'] = $this->request->param('level', 0, 'intval');
        $params['parking_config'] = $this->request->param('parking_config', []);
        $params['benefits'] = $this->request->param('benefits', []);
        $params['uid'] = $this->_uid;
        try {
            (new ShoppingMallMembershipBenefits())->saveMembershipBenefits($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

	public function saveShoppingMall(): Json
	{
		$params = input('post.');
		$params['status'] = input('status', 0, 'intval');
		$this->validateParameter($params);
		try {
			$result = (new ShoppingMallService())->saveShoppingMall($params);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
		return api_output(0, $result, 'success');
	}
	
	public function getCanBindAdminUser(): Json
	{
		try {
			$result = (new ShoppingMallService())->getCanBindAdminUser();
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
		return api_output(0, $result, 'success');
	}

    /**
     * 获取楼宇优惠券列表
     * @return Json
     */
    public function getBuildingSystemCoupon()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        try {
            $result['coupon_list'] = (new ShoppingMallService())->getBuildingSystemCoupon($mallId);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $result, 'success');
    }
}
