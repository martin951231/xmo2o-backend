<?php

declare (strict_types=1);

namespace app\appoint\controller\worker;

use app\appoint\model\db\AppointSupply;
use app\appoint\model\service\MerchantWorkService;
use app\appoint\validate\WorkUser as WorkUserValidate;
use map\Coordinate;
use think\helper\Arr;
use think\response\Json;

class WorkUserController extends AuthBaseController
{
    const STATUS_OK = 1000;//状态码 正常
	
	
    public function initialize()
    {
        $this->noCheckAction = [
            'login','config'
        ];
        $this->validateClass = WorkUserValidate::class;

        parent::initialize();
    }

    /**
     * 登录
     * @return \think\response\Json
     */
    public function login()
    {
        $params = $this->validateParameter(Arr::only(input(),['account', 'password']));
      
        $key = 'work_user_' . $params['account'].request()->ip();
        $loginFailMessage = function ($message = '') use ($key) {
            $value = [
                'num'  => 1,
                'time' => time()
            ];
            $expireTime   = 60 * 30;
            $cacheAccount = cache($key);
            if (empty($cacheAccount)) {
                cache($key, $value, $expireTime);
            } else if ($cacheAccount['num'] >= 5) {
                $cacheAccount['num'] = 5;
                cache($key, $cacheAccount, $expireTime);
                $message = L_('您已连续登录5次，请稍后再试或者联系商家管理员！');
            } else if (time() - $cacheAccount['time'] < $expireTime) {
                $cacheAccount['num']++;
                cache($key, $cacheAccount, $expireTime);
            } else {
                cache($key, $value, $expireTime);
            }

            return $message;
        };
        $cacheAccount = cache($key);
        if ($cacheAccount && $cacheAccount['num'] >= 5) {
            throw_exception($loginFailMessage());
        }
        try {
            $result = (new MerchantWorkService())->login($params);
           
            return api_output(self::STATUS_OK, $result, "登录成功");
        } catch (\Exception $e) {
            return api_output_error(-1, $loginFailMessage($e->getMessage()));
        }
    }

    /**
     * 修改密码
     * @return \think\response\Json
     */
    public function changePwd()
    {
        $params = $this->validateParameter(Arr::only(input(),['old_password', 'password']));
        $data = (new MerchantWorkService())->changePwd($this->workUser['merchant_worker_id'], $params['old_password'], $params['password']);
        return api_output(self::STATUS_OK, $data);
    }
    /**
     * 首页
     * @return \think\response\Json
     */
    public function userInfo()
    {
        $workUser = $this->workUser;
        // 1等待接单 2接单 3完成
        $workUser['wait_total'] = (new MerchantWorkService())->appointSupplyCount(0, $workUser['merchant_store_id'],AppointSupply::WAIT_RECEIVE);
        $workUser['receive_total'] = (new MerchantWorkService())->appointSupplyCount($workUser['merchant_worker_id'],$workUser['merchant_store_id'], AppointSupply::RECEIVE);
        $workUser['finish_total'] = (new MerchantWorkService())->appointSupplyCount($workUser['merchant_worker_id'], $workUser['merchant_store_id'],AppointSupply::COMPLETE);

        $workUser['today_info'] = (new MerchantWorkService())->workTodaySupplyInfo($workUser['merchant_worker_id']);
        $workUser['privacy_policy'] = cfg('store_privacy_policy');
                
        return api_output(self::STATUS_OK, $workUser);
    }

    /**
     * 抢单大厅
     * @return \think\response\Json
     */
    public function grapList()
    {
        $params = array_merge(input(),[
            'status' => 1,//等待接单
            'worker_id' => 0,  
        ]);
        $data = (new MerchantWorkService())->appointSupplyList($this->workUser['merchant_store_id'], $params);
        return api_output(self::STATUS_OK, $data);
    }

	/**
	 * 查询是否有新订单
	 * @return Json
	 */
	public function newOrderNotice(): Json
	{
		$data = (new MerchantWorkService())->newOrder(
			(int) $this->workUser['merchant_store_id'],
			(int) $this->workUser['merchant_worker_id']
		);
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 判断服务人员是否到达地点
	 * @return Json
	 */
	public function workerArrive(): Json
	{
		$this->validate($params = input(), [
			'supply_id' => 'require',
			'lat'       => 'require',
			'lng'       => 'require',
		]);

        $coordinate_type = $this->request->param('coordinate_type', '');
        //非百度坐标系的统一转换成百度经纬度给到服务
        if ($coordinate_type != '' && $coordinate_type != 'bd09') {
            $action = $coordinate_type . 'ToBd09';
            list($params['lng'], $params['lat']) = Coordinate::$action($params['lng'], $params['lat']);
            unset($params['coordinate_type']);
        }

		$data = (new MerchantWorkService())->workerArrive(
			(int) $this->workUser['merchant_worker_id'],
			(int) $params['supply_id'], 
			$params['lat'], 
			$params['lng']
		);
		return api_output(self::STATUS_OK, $data);
	}

	/*
	 * 技师上报位置或服务报告
	 */
	public function recordServiceInfo(): Json
	{
		$params = input();
		$this->validate($params, [
			'supply_id|服务ID' => 'require',
		]);
		if(!empty($params['push_position_info'])) {
			$this->validate($params['push_position_info'], [
				'lat' => 'require',
				'lng' => 'require',
			]);
			$params['push_position_info']['record_time'] = date('Y-m-d H:i:s');
		}

        $coordinate_type = $this->request->param('coordinate_type', '');
        //非百度坐标系的统一转换成百度经纬度给到服务
        if ($coordinate_type != '' && $coordinate_type != 'bd09') {
            $action = $coordinate_type . 'ToBd09';
            list($params['push_position_info']['lng'], $params['push_position_info']['lat']) = Coordinate::$action($params['push_position_info']['lng'], $params['push_position_info']['lat']);
            unset($params['coordinate_type']);
        }
		
		$data = (new MerchantWorkService())->recordServiceInfo(
			(int) $this->workUser['merchant_worker_id'],
			(int) $params['supply_id'],
			$params
		);
		return api_output(self::STATUS_OK, $data);
	}
	
    /**
     * 服务订单详情
     * @return \think\response\Json
     */
    public function serviceDetail()
    {
        $data = (new MerchantWorkService())->serviceDetail($this->workUser['merchant_worker_id'], input());
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 订单列表
     * @return \think\response\Json
     */
    public function appointSupplyList()
    {
        $params = $this->validateParameter();
        $params['worker_id'] = $this->workUser['merchant_worker_id'];
        
        $data = (new MerchantWorkService())->appointSupplyList($this->workUser['merchant_store_id'], $params);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 抢单
     * @return \think\response\Json
     */
    public function grapOrder()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->grapOrder($this->workUser['merchant_worker_id'], $params['supply_id']);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 验证服务
     * @return \think\response\Json
     */
    public function completeOrder()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->completeOrder($this->workUser['merchant_worker_id'], $params['supply_id']);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 评价列表
     * @return \think\response\Json
     */
    public function commentList()
    {
        $data = (new MerchantWorkService())->commentList($this->workUser['merchant_worker_id']);
        unset($this->workUser['info'], $this->workUser['desc']);
        return api_output(self::STATUS_OK, [
            'worker' => $this->workUser,
            'comment_list' => $data 
        ]);
    }
    
    /**
     * 服务时间
     * @return \think\response\Json
     */
    public function workTime()
    {
        $data = (new MerchantWorkService())->workTime($this->workUser);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 数据统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->statistics($this->workUser['merchant_worker_id'], $params['start_time'], $params['end_time']);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 一键求救
     * @return \think\response\Json
     */
    public function oneClickHelp()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->oneClickHelp($this->workUser, $params);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 排行榜
     * @return \think\response\Json
     */
    public function rankList()
    {
        $params = $this->validateParameter();
        $data = (new MerchantWorkService())->rankList($this->workUser, $params['type']);
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 修改订单总价
     * @return \think\response\Json
     */
    public function modifyOrderPrice()
    {
        $data = (new MerchantWorkService())->modifyOrderPrice($this->workUser['merchant_worker_id'], input());
        return api_output(self::STATUS_OK, $data);
    }

    public function workerAppointList(){
        
        $data['appoint_list'] = (new MerchantWorkService())->workerAppointList($this->workUser['merchant_worker_id'],[0]);
        
        return api_output(self::STATUS_OK, $data);
    }

    //技师配置接口
    public function config()
    {
        $rs = [
            'worker_logo' => cfg('worker_logo') ? replace_file_domain(cfg('worker_logo')) : '',
            'system_admin_logo' => cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : '',
            'appoint_worker_name' => cfg('appoint_worker_name'),
            'Currency_txt' => cfg('Currency_txt'),
            'Currency_symbol' => cfg('Currency_symbol'),
            'grab_new_order' => replace_file_domain(cfg('appoint_grab_new_order')),
            'new_order' => replace_file_domain(cfg('appoint_new_order')),
            'visit_must_sign' => cfg('appoint_visit_must_sign'),
            'visit_sign' => cfg('appoint_visit_sign'),
            'new_order_interval' => MerchantWorkService::CHECK_TIME,  //新订单轮询时间
	        'tianditu_web_key' => cfg('tianditu_web_key') ? cfg('tianditu_web_key') : '',
	        'map_config' => cfg('map_config') ? cfg('map_config') : 'baidu',
        ];
        return api_output(self::STATUS_OK, $rs);
    }
}