<?php
/**
 * 判断用户登录
 * Author: 衡婷妹
 */
namespace app\merchant\controller\merchant;

use app\common\controller\CommonBaseController;
use app\merchant\model\service\MerchantService as MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\common\model\db\ConfigData;

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
        
//        $merId = 1;
        // 获得用户信息
        $merchantService = new MerchantService();
        $merchant = $merchantService->getMerchantByMerId($merId);
        if(empty($merchant)){
    		throw new \think\Exception("未登录", 1002);    		
    	}

        // 用户id
        $this->merId = $merId;

        // 用户信息
        $this->merchantUser = $merchant;

        //商家子账号登录信息
        $logExtends = $this->request->log_extends;
        if (isset($logExtends['mer_subaccount_id'])) {
            $this->subAccountId = intval($logExtends['mer_subaccount_id']);
            $this->subAccountUser = (new \app\merchant\model\db\MerchantUserAccount())
                ->where(['id' => $this->subAccountId, 'mer_id' => $this->merId, 'is_del' => 0, 'status' => 1])
                ->withoutField('password')
                ->findOrEmpty()
                ->toArray();
            if (empty($this->subAccountUser)) {
                throw new \think\Exception("未登录", 1002);
            }
        }
    

        // 验证店铺
        $this->checkStore();

        //签约验证
        $allow_url = [
            '/v20/public/index.php/merchant/merchant.user.user/userInfo',
            '/v20/public/index.php/merchant/merchant.system.merchantMenu/menuList',
            '/v20/public/index.php/warn/merchant.Notice/unreadNum',
        ];
        if(!in_array($_SERVER['REQUEST_URI'],$allow_url)){
            $configDataModel = new ConfigData();
            $mustSign = $configDataModel->where(['name' => 'contract_must_sign'])->value('value');
            if($mustSign){
                //看下是否开启续费
                $canRenewed = $configDataModel->where(['name' => 'contract_can_renewed'])->value('value');
                if($this->merchantUser['is_sign_contract']==0){
                    throw new \think\Exception(L_('您已成功入驻平台，现需在线签署合同后才可正常使用商家功能哦~'), 1001);
                }elseif($this->merchantUser['is_sign_contract']==2){
                    throw new \think\Exception(L_('功能无法使用，需要重新签署合同~'), 1001);
                }elseif($this->merchantUser['is_sign_contract']==1&&$canRenewed!=1&&$this->merchantUser['contract_end_time']&&$this->merchantUser['contract_end_time']<time()){
                    throw new \think\Exception(L_('功能无法使用，合同已到期请联系管理员~'), 1001);
                }
            }
        }
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin(){
    	$log_uid = request()->log_uid ?? 0;
    	if(empty($log_uid)){
    		throw new \think\Exception("未登录", 1002);
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
                throw new \think\Exception("店铺不存在", 1003);    		
            }		
    	}
    }
}