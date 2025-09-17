<?php

namespace app\life_tools\controller\api;

use app\common\controller\CommonBaseController;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use token\Token;

class StaffBaseController extends CommonBaseController{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $staffUser;

    /**
     * 控制器登录用户id
     * @var int
     */
    public $staffId;

    public $merId;

    public function initialize()
    {
        parent::initialize();

        // 验证登录
        // $this->checkLogin();

        $staffId = intval($this->request->log_uid);

        // $staffId = 2;
//        $staffId = 175;
        // 获得用户信息
        $merchantStoreStaffService = new MerchantStoreStaffService();
        $where = [
            'id' => $staffId
        ];
        $staff = $merchantStoreStaffService->getOne($where);

        // 用户id
        $this->staffId = $staffId;

        // 用户信息
        $this->staffUser = $staff;

        request()->staffUser = $staff;

        // 获取新的ticket
        // 生成ticket
//        $newTicket = Token::createToken($staffId); //重新生成ticket会一直提示未登录
//        cfg('staff_ticket', $newTicket);
        if(!empty($staff)){
            $mer=(new MerchantStoreService())->getStoreInfo($staff['store_id']);
            if(!empty($mer)){
                $this->merId = $mer['mer_id'];
                $this->staffUser['mer_id'] = $mer['mer_id'];
            }
        }
    }

    /**
     * 验证登录
     * @var int
     */
    public function checkLogin(){
    	$log_uid = request()->log_uid ?? 0;
    	if(empty($log_uid)){
    		throw new \think\Exception("未登录", 1002);    		
    	}
    }
}