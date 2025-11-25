<?php
/**
 * 商家会员卡记录service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/15 10:14
 */

namespace app\merchant\model\service;
use app\merchant\model\db\FenrunFreeAwardMoneyList as FenrunFreeAwardMoneyListModel;
use app\common\model\service\UserService as UserService;
use app\common\model\service\coupon\MerchantCouponService;
class FenrunFreeAwardMoneyListService {
    public $fenrunFreeAwardMoneyListModel = null;
    public function __construct()
    {
        $this->fenrunFreeAwardMoneyListModel = new FenrunFreeAwardMoneyListModel();
    }


    /**
     * 解冻根据子用户消费奖励金额
     * @param $uid int 用户id
     * @param $free_money float 解冻金额
     * @param $type  
     * @param $typeId  
     * @return array
     */
    public function freeUserRecommendAwards($uid, $freeMoney,$type,$typeId)
    {
        $userService = new UserService();
        $nowUser   = $userService->getUser($uid);

        $spreadRechargeLimit = $nowUser['frozen_award_money'];
        $rechargeMoney = $freeMoney > $spreadRechargeLimit ? $spreadRechargeLimit : $freeMoney;
        $limitTxt     = L_(',您的还有X1未解冻',($spreadRechargeLimit - $rechargeMoney<0?0:$spreadRechargeLimit - $rechargeMoney) . cfg('Currency_txt'));

        $dateUser['free_award_money'] = $nowUser['free_award_money'] + $rechargeMoney;
        $dateUser['frozen_award_money'] = $nowUser['frozen_award_money'] - $rechargeMoney;

        //用户解冻金额增加，冻结金额减少
        $where = [
            'uid' => $uid
        ];
        (new UserService())->updateThis($where, $dateUser);

        // 增加记录
        $date['uid'] = $uid;
        $date['money'] = $rechargeMoney; //解冻金额
        $date['now_frozen_money'] = $rechargeMoney;//当前冻结金额
        $date['now_free_money']   = $nowUser['free_award_money'] + $rechargeMoney;
        $date['income']   = 1;//收入增加
        $date['type']   = $type;
        $date['type_id']   = $typeId;
        $date['des'] = L_("解冻奖励佣金X1",array("X1" => $rechargeMoney . cfg('Currency_txt'). $limitTxt)); 
        $date['add_time'] = $_SERVER['REQUEST_TIME'];
        //增加记录
        $this->add($date);
    }
    
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        
        $data['time'] = time();
        $result = $this->fenrunFreeAwardMoneyListModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->fenrunFreeAwardMoneyListModel->id;
        
    }

}