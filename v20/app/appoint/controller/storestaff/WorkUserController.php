<?php /** @noinspection PhpUnused */

declare (strict_types=1);

namespace app\appoint\controller\storestaff;

use app\appoint\model\db\AppointSupply;
use app\appoint\model\service\MerchantWorkService;
use app\appoint\validate\WorkUser as WorkUserValidate;
use think\helper\Arr;

class WorkUserController extends AuthBaseController
{
    const STATUS_OK = 1000;//状态码 正常
    public function initialize()
    {
        $this->noCheckAction = [
            'login'
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
      
        $key = 'work_user_' . $params['account'];
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
}