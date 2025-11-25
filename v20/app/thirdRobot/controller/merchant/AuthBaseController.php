<?php

namespace app\thirdRobot\controller\merchant;

use app\common\controller\CommonBaseController;
use app\merchant\model\db\MerchantUserAccount;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use think\Exception;

class AuthBaseController extends CommonBaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $merchantUser;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $merId;

    public $subAccountId = 0;

    public $subAccountUser = [];

    public function initialize()
    {
        parent::initialize();

        // 验证登录
        $this->checkLogin();

        $merId = intval($this->request->log_uid);

        // 获得用户信息
        $merchantService = new MerchantService();
        $merchant = $merchantService->getMerchantByMerId($merId);
        if(empty($merchant)){
    		throw new Exception("未登录", 1002);    		
    	}

        // 用户id
        $this->merId = $merId;

        // 用户信息
        $this->merchantUser = $merchant;
        //商家子账号登录信息
        $logExtends = $this->request->log_extends;
        if (isset($logExtends['mer_subaccount_id'])) {
            $this->subAccountId = intval($logExtends['mer_subaccount_id']);
            $this->subAccountUser = (new MerchantUserAccount())
                ->where(['id' => $this->subAccountId, 'mer_id' => $this->merId, 'is_del' => 0, 'status' => 1])
                ->withoutField('password')
                ->findOrEmpty()
                ->toArray();
            if (empty($this->subAccountUser)) {
                throw new Exception("未登录", 1002);
            }
        }
    

        // 验证店铺
        $this->checkStore();
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin(){
    	$log_uid = request()->log_uid ?? 0;
    	if(empty($log_uid)){
    		throw new Exception("未登录", 1002);
    	}
    }

    /**
     * 验证店铺
     */
    private function checkStore(){
    	$storeId = $this->request->param('store_id', '0', 'intval');
    	if($storeId){
            $where['mer_id'] = $this->merId;
            $where['store_id'] = $storeId;
            $store = (new MerchantStoreService)->getOne($where);
            if(empty($store)){
                throw new Exception("店铺不存在", 1003);    		
            }		
    	}
    }
}