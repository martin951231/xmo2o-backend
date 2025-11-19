<?php
/**
 * 交易记录service
 * @date 2021-12-21 
 */

namespace app\employee\model\service;

use app\common\model\db\MerchantStore;
use app\employee\model\db\EmployeeCardCouponRefund;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardUser;
use think\facade\Db;
class EmployeeCardLogService
{
    public $employeeCardLogModel = null;
    public $merchantStoreModel = null;
    public $employeeCardCouponRefundModel = null;
    public $employeeCardUserModel = null;
    public function __construct()
    {
        $this->employeeCardLogModel = new EmployeeCardLog();
        $this->merchantStoreModel = new MerchantStore();
        $this->employeeCardCouponRefundModel = new EmployeeCardCouponRefund();
        $this->employeeCardUserModel = new EmployeeCardUser();
    }
    
    /**
     *插入一条数据
     * @param array $data 
     * @return int|bool
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['add_time'] = time();

        $id = $this->employeeCardLogModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = $this->employeeCardLogModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->employeeCardLogModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }  
    
    /**
    *获取多条条数据
    * @param array $where 
    * @return array
    */
   public function getSomeAndPage($where = [], $field = true,$order=true,$page=0,$limit=0){
       $result = $this->employeeCardLogModel->getSomeAndPage($where,$field, $order, $page,$limit);
       if(empty($result)) return [];
       return $result->toArray();
   }

    /**
    * 数据统计
    */
    public function dataStatistics($params)
    {
        $dateCondition = [];
        if(!empty($params['start_date']) && !empty($params['end_date'])){
            if($params['start_date'] > $params['end_date']){
                throw new \think\Exception('开始日期不能大于结束日期！');
            }
            $dateCondition[] = ['add_time', 'between', [strtotime($params['start_date']), strtotime($params['end_date'] . ' 23:59:59')]];
        }
        $todayCondition = [];
        $todayCondition[] = ['add_time', 'between', [strtotime(date('Ymd')), strtotime(date('Ymd') . ' 235959')]];
        //总金额
        $condition = $conditionOr = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['change_type', '=', 'success'];
        $condition[] = ['is_refund', '=', 0]; 
        if(!empty($params['staff_id']) && !empty($params['staff']) && $params['staff']['type'] != 2){
            $condition[] = ['operate_id', '=', $params['staff_id']]; 
        }
        $conditionOr[] = ['type', '=', 'coupon'];
        $conditionOr[] = ['type', '=', 'overdue'];
        // $totalPrice = $this->employeeCardLogModel->where(array_merge($condition, $dateCondition))->where(function($query) use($conditionOr){
        //                 $query->whereOr($conditionOr);
        //             })->sum('coupon_price');
                    
        // //今日金额 
        // $todayPrice = $this->employeeCardLogModel->where(array_merge($condition, $todayCondition))->where(function($query) use($conditionOr){
        //                 $query->whereOr($conditionOr);
        //             })->sum('coupon_price');

        
        //总补贴
        $totalGrant = $this->employeeCardLogModel->where(array_merge($condition, $dateCondition))->where(function($query) use($conditionOr){
                        $query->whereOr($conditionOr);
                    })->sum('grant_price');

        //今日补贴
        $todayGrant = $this->employeeCardLogModel->where(array_merge($condition, $todayCondition))->where(function($query) use($conditionOr){
                        $query->whereOr($conditionOr);
                    })->sum('grant_price');

        //余额总消费
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']]; 
        $condition[] = ['is_refund', '=', 0];  
        if(!empty($params['staff_id']) && !empty($params['staff']) && $params['staff']['type'] != 2){
            $condition[] = ['operate_id', '=', $params['staff_id']]; 
        }
        $totalMoney = $this->employeeCardLogModel->where(array_merge($condition, $dateCondition))->where(function($query) use($conditionOr){
                        $query->whereOr(function($query){
                            $condition = [];
                            $condition[] = ['type', '=', 'money'];
                            $condition[] = ['change_type', '=', 'success']; 
                            $query->where($condition);
                        })->whereOr(function($query){
                            $conditionOr = [];
                            $conditionOr[] = ['type', '=', 'coupon'];
                            $conditionOr[] = ['change_type', '=', 'success'];
                            $query->where($conditionOr);
                        });
                    })->sum('num'); 

        //今日余额消费
        $todayMoney = $this->employeeCardLogModel->where(array_merge($condition, $todayCondition))->where(function($query) use($conditionOr){
            $query->whereOr(function($query){
                $condition = [];
                $condition[] = ['type', '=', 'money'];
                $condition[] = ['change_type', '=', 'success']; 
                $query->where($condition);
            })->whereOr(function($query){
                $conditionOr = [];
                $conditionOr[] = ['type', '=', 'coupon'];
                $conditionOr[] = ['change_type', '=', 'success'];
                $query->where($conditionOr);
            });
        })->sum('num'); 

        //积分总消费
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']]; 
        $condition[] = ['is_refund', '=', 0];  
        $condition[] = ['type', '=', 'score'];
        $condition[] = ['change_type', '=', 'success'];
        if(!empty($params['staff_id']) && !empty($params['staff']) && $params['staff']['type'] != 2){
            $condition[] = ['operate_id', '=', $params['staff_id']]; 
        }
        $totalScore = $this->employeeCardLogModel->where(array_merge($condition, $dateCondition))->sum('num');
        //今日积分消费
        $todayScore = $this->employeeCardLogModel->where(array_merge($condition, $todayCondition))->sum('num');

        // $return['total_price'] = $totalPrice;
        // $return['today_price'] = $todayPrice;
        // $return['price_list'] = $this->employeeCardLogModel->getDataCount($params, 'coupon_price');
        $return['total_grant'] = $totalGrant;
        $return['today_grant'] = $todayGrant;
        $return['grant_list'] = $this->employeeCardLogModel->getDataCount($params, 'grant_price');
        $return['total_money'] = $totalMoney;
        $return['today_money'] = $todayMoney;
        $return['money_list'] = $this->employeeCardLogModel->getDataCount($params, 'money');
        $return['total_score'] = $totalScore;
        $return['today_score'] = $todayScore;
        $return['score_list'] = $this->employeeCardLogModel->getDataCount($params, 'score');

        $return['total_price'] = formatNumber($totalGrant + $totalMoney + $totalScore);
        $return['today_price'] = formatNumber($todayGrant + $todayMoney + $todayScore);

        foreach($return['grant_list'] as $key => $val){
            $return['money_list'][$key]['y'] = (float)$return['money_list'][$key]['y'];
            $return['score_list'][$key]['y'] = (float)$return['score_list'][$key]['y'];
            $return['grant_list'][$key]['y'] = (float)$return['grant_list'][$key]['y'];
            $return['price_list'][$key]['x'] = $val['x'];
            $return['price_list'][$key]['y'] = (float)formatNumber($val['y'] + ($return['money_list'][$key]['y'] ?? 0) + ($return['score_list'][$key]['y'] ?? 0));
        }  
        return $return;
    }

    /**
     * 店铺消费列表
     */
    public function getStoreConsumerList($params)
    {
        return $this->employeeCardLogModel->getStoreConsumerList($params);
    }

    /**
     * 导出数据
     */
    public function getBillExportData($params)
    {
        $data = $this->employeeCardLogModel->getBillExportData($params);
        $return = [];
        foreach($data as $val){
            $val['total'] = formatNumber($val['grants'] + $val['money'] + $val['score']);
            if($val['total'] == 0){
                continue;
            }
            $return[] = $val; 
        }
        return array_reverse($return);
    }

    /**
     * 退款
     */
    public function employeeCouponRefund($params)
    {
        if(empty($params['mer_id'])){
            throw new \think\Exception('请登录！');
        }
        if(empty($params['pigcms_id'])){
            throw new \think\Exception('log_id不能为空！');
        }
        if(empty($params['refund_remark'])){
            throw new \think\Exception('refund_remark不能为空！');
        }
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $cardLog = $this->employeeCardLogModel->with(['coupon_send'])->where($condition)->find();
 
        if(!$cardLog || (($cardLog->type == 'coupon' || $cardLog->type == 'overdue') && !$cardLog->coupon_send)){
            throw new \think\Exception('消费记录不存在！');
        }
        if($cardLog->is_refund == 1){
            throw new \think\Exception('此消费记录已退款，请勿重复操作！');
        }
        $user = $this->employeeCardUserModel->where('user_id', $cardLog->user_id)->find();
        if(!$user){
            throw new \think\Exception('退款失败，此员工已被删除！');
        }
        $staff_id = 0;
        if(!empty($params['staff_id'])){
            $staff_id = $params['staff_id'];
        }
        if($cardLog->change_type == 'success'){

            Db::startTrans();
            try {
  
                switch($cardLog->type){
                    case 'coupon': //消费券核销
                        $money = $cardLog->num;
                        $desc = "消费券消费退款";
                        $user_desc = "您在" . date('Y-m-d H:i:s') . "，成功退款消费券，退还余额{$money}元";
                        (new EmployeeCardUserService())->addMoney($cardLog->user_id, $money, $desc, $user_desc, 'merchant');
                    
                        //发一张新券
                        $data = [];
                        $data['card_id'] = $cardLog->card_id;
                        $data['mer_id'] = $cardLog->mer_id;
                        $data['coupon_id'] = $cardLog->coupon_send->coupon_id;
                        $data['user_id'] = $cardLog->user_id;
                        $new_coupon_id = (new EmployeeCardCouponService)->sendCoupon($data); 
                        $this->employeeCardCouponRefundModel->addOne($cardLog->pigcms_id, $cardLog->mer_id, $cardLog->user_id, $cardLog->uid, $money, 0, $new_coupon_id, $params['refund_remark'], $staff_id);
                        
                        $cardLog->is_refund = 1;
                        $cardLog->refund_remark = $params['refund_remark'];
                        $cardLog->refund_time = time();
                        $cardLog->save();  
                    
                        break;
                    case 'overdue': //消费券自动核销
                    case 'to_score': //手动转积分

                        $money = $cardLog->deduct_money;
                        $score = $cardLog->add_score_num  * -1;
                        if($cardLog->type == 'overdue'){
                            $desc = "消费券自动核销退款";
                            $user_desc = "您在" . date('Y-m-d H:i:s') . "，成功退款自动核销消费券，退还余额{$money}元";
                        }else{
                            $desc = "手动转积分退款";
                            $user_desc = "您在" . date('Y-m-d H:i:s') . "，手动转积分退款成功，退还余额{$money}元";
                        }
                        
                        (new EmployeeCardUserService())->addMoney($cardLog->user_id, $money, $desc, $user_desc, 'merchant');
                        (new EmployeeCardUserService())->addScore($cardLog->user_id, $score, $desc, $user_desc, 'merchant');

                        //发一张新券
                        $data = [];
                        $data['card_id'] = $cardLog->card_id;
                        $data['mer_id'] = $cardLog->mer_id;
                        $data['coupon_id'] = $cardLog->coupon_send->coupon_id;
                        $data['user_id'] = $cardLog->user_id;
                        $new_coupon_id = (new EmployeeCardCouponService)->sendCoupon($data); 
                        $this->employeeCardCouponRefundModel->addOne($cardLog->pigcms_id, $cardLog->mer_id, $cardLog->user_id, $cardLog->uid, $money, $score, $new_coupon_id, $params['refund_remark'], $staff_id);
                        
                        $cardLog->is_refund = 1;
                        $cardLog->refund_remark = $params['refund_remark'];
                        $cardLog->refund_time = time();
                        $cardLog->save();  
                        break;
                    case 'score': //积分消费
                        $score = $cardLog->num;
                        $desc = "积分消费退款";
                        $user_desc = "您在" . date('Y-m-d H:i:s') . "，成功退款{$score}积分";
                        (new EmployeeCardUserService())->addScore($cardLog->user_id, $score, $desc, $user_desc, 'merchant');

                        $this->employeeCardCouponRefundModel->addOne($cardLog->pigcms_id, $cardLog->mer_id, $cardLog->user_id, $cardLog->uid, 0, $score, 0, $params['refund_remark'], $staff_id);

                        $cardLog->is_refund = 1;
                        $cardLog->refund_time = time();
                        $cardLog->refund_remark = $params['refund_remark'];
                        $cardLog->save();

                        break;
                    case 'money': //余额消费
                        $money = $cardLog->num;
                        $desc = "余额消费退款";
                        $user_desc = "您在" . date('Y-m-d H:i:s') . "，成功退款{$money}元";
                        (new EmployeeCardUserService())->addMoney($cardLog->user_id, $money, $desc, $user_desc, 'merchant');
                        $this->employeeCardCouponRefundModel->addOne($cardLog->pigcms_id, $cardLog->mer_id, $cardLog->user_id, $cardLog->uid, $money, 0, 0, $params['refund_remark'], $staff_id);
                        $cardLog->is_refund = 1;
                        $cardLog->refund_time = time();
                        $cardLog->refund_remark = $params['refund_remark'];
                        $cardLog->save();
                        break;
                } 

                Db::commit();
            } catch (\Exception $e) { 
                Db::rollback();
                throw new \think\Exception($e->getMessage(), 1003);
            }
        }
        return true;
 
    }

}