<?php

namespace app\life_tools\model\service;


use app\life_tools\model\db\EmployeeCard;
use app\merchant\model\db\MerchantStoreStaff;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use token\Token;

class StoreStaffService
{
    public $merchantStoreStaffModel = null;

    public function __construct()
    {
        $this->merchantStoreStaffModel = new MerchantStoreStaff();
    }
    
    public function login($params)
    {
        if(empty($params['username'])){
            throw new \think\Exception('请输入店员账号！', 1002);
        }
        if(empty($params['password'])){
            throw new \think\Exception('请输入密码！', 1002);
        }

        $condition = [];
        $condition[] = ['username', '=', $params['username']];
        $staffUser = $this->merchantStoreStaffModel->where($condition)->find();
        if(!$staffUser) {
            throw new \think\Exception("帐号不存在", 2001);
        }

        if($staffUser->password != md5($params['password'])) {
            throw new \think\Exception("账号或密码错误", 2001);
        }

         // 判断店铺状态
         $where = [
            'store_id' => $staffUser['store_id']
        ];
        $store = (new MerchantStoreService())->getOne($where);
        if(empty($store) || $store['status'] != 1){
            throw new \think\Exception("店铺状态不正常", 2002);
        }

        // 判断商家状态
        $where = [
            'mer_id' => $store['mer_id']
        ];
        $merchant = (new MerchantService())->getOne($where);

        if($merchant['status'] == 4){
            throw new \think\Exception("商户已被删除", 2002);
        }

        if($merchant['status'] != 1){
            throw new \think\Exception("商户状态不正常", 2002);
        }

        $staffUser->last_time = time();
        $staffUser->save();
 

        $ticket = Token::createToken($staffUser->id);
        return [
            'v20_ticket' => $ticket,
            'expiration_time' => time() + 7200
        ];
    }
}