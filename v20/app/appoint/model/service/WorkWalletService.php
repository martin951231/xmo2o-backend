<?php


namespace app\appoint\model\service;


use app\appoint\model\db\MerchantWorkers;
use app\appoint\model\db\MerchantWorkersMoney;
use app\appoint\model\db\WithdrawList;
use app\common\model\db\Companypay;
use app\common\model\db\User;
use think\facade\Db;

class WorkWalletService
{
    /**
     * 我的钱包首页
     * @param $params
     */
    public function index($params){
        $worker = $params['work'];
        $work_id = $worker['merchant_worker_id'];
        $start_time = strtotime(date('Y-m-d'));
        $end_time = $start_time+3600*24;
        $where = [['merchant_worker_id','=',$work_id],['time','between',[$start_time,$end_time]]];
        $result = $this->getBillListArr($where);
        $worker_user = (new MerchantWorkers())->where(['merchant_worker_id'=>$work_id])->findOrEmpty()->toArray();
        //获取冻结金额
        $freeze_money = (new MerchantWorkersMoney())->where(['type'=>3])->sum('money');
        $result['money'] = getFormatNumber(($worker_user['now_money']+$freeze_money)); //余额（包含打赏/服务金额）
        $result['reward_money'] = getFormatNumber($worker_user['reward_money']); //打赏金额
        $result['withdrawable_money'] = getFormatNumber($worker_user['now_money']); //可提现金额
        $result['company_pay_user_percent'] = cfg('company_pay_user_percent')?:0; //提现服务费率
        return $result;
    }

    /**
     * 帐单列表
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function billList($params){
        $worker = $params['work'];
        $work_id = $worker['merchant_worker_id'];
        $start_time = $params['start_date']?strtotime($params['start_date']):0;
        $end_time = $params['end_date']?(strtotime($params['end_date'])+3600*24):0;
        $from_type = $params['from_type'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['merchant_worker_id','=',$work_id];
        if(in_array($from_type,[1,2,3])){
            $where[] = ['from_type','=',$from_type];
        }
        if($start_time&&$end_time&&$end_time<$start_time){
            throw new \think\Exception(L_('开始时间不能小于结束时间！'));
        }
        if($start_time&&$end_time){
            $where[] = ['time','between',[$start_time,$end_time]];
        }
        $result = $this->getBillListArr($where,'*',$page,$pageSize);
        return $result;
    }

    /**
     * 账单数据处理
     * @param $where
     * @param string $field
     * @param int $page
     * @param int $pageSize
     */
    private function getBillListArr($where,$field='*',$page=0,$pageSize=10){
        $bill_arr = (new MerchantWorkersMoney())->getList($where,$field,$page,$pageSize);
        $today_bill = [];
        $data = $page>0?$bill_arr['data']:$bill_arr;
        foreach ($data as $item){
            $today_bill[] = [
                'type' => $item['type'],
                'money' => $item['money'],
                'desc' => $item['desc'],
                'time' => $item['time']?date('Y.m.d H:i',strtotime($item['time'])):'',
            ];
        }
        $pay_out_money_where = array_merge([['type','=',2]],$where);
        $income_money_where = array_merge([['type','=',1]],$where);
        $pay_out_money = (new MerchantWorkersMoney())->where($pay_out_money_where)->sum('money');
        $income_money = (new MerchantWorkersMoney())->where($income_money_where)->sum('money');
        $arr = [];
        if($page>0){
            $arr = $bill_arr;
            $arr['data'] = $today_bill;
        }else{
            $arr['today_bill'] = $today_bill;
        }
        $arr['income_money'] = getFormatNumber($income_money);
        $arr['pay_out_money'] = getFormatNumber($pay_out_money);
        return $arr;
    }

    /**
     * 提现申请
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function cashOut($params){
        $money = $params['money'];
        $work_id = $params['merchant_worker_id'];
        $truename = $params['truename'];
        $type = $params['type'];
        if($money<=0||!is_numeric($money)){
            throw new \think\Exception(L_('提现金额有误！'));
        }
        if(empty($truename)){
            throw new \think\Exception(L_('真实姓名不能为空！'));
        }
        if($money<cfg('company_least_money')){
            throw new \think\Exception(L_('不能低于最低提款额 '.cfg('company_least_money').cfg('Currency_txt').'！'));
        }
        $worker = (new MerchantWorkers())->where(['merchant_worker_id'=>$work_id])->findOrEmpty()->toArray();
        if(!$worker){
            throw new \think\Exception(L_('技师不存在！'));
        }
        $openid = $worker['openid'];
        if(!$openid){
            throw new \think\Exception(L_('需要先绑定微信！'));
        }
        if($worker['now_money']<$money){
            throw new \think\Exception(L_('提款超出限额，申请失败！'));
        }
        $percent = cfg('company_pay_user_percent');
        $data_companypay['type'] = 'worker';
        $data_companypay['pay_type'] = $type;//0 银行卡，1 支付宝 2微信
        $data_companypay['pay_id'] = $work_id;
        $data_companypay['account'] =  $openid;
        $data_companypay['openid'] =  $openid;
        $data_companypay['name'] = $worker['name']?:$worker['mobile'];
        $data_companypay['truename'] = $truename;
        $data_companypay['remark'] = '';
        $data_companypay['phone'] = $worker['mobile'];
        $data_companypay['money'] = $money*((100-$percent)/100)*100;
        $data_companypay['old_money'] = $money*100;
        $data_companypay['desc'] = "技师提现对账订单|技师ID ".$work_id." |转账 ".$money.cfg('Currency_txt')."" ;
        if(cfg('company_pay_user_percent')>0){
            $data_companypay['desc'] .= '|手续费 '.round((($data_companypay['old_money'] -  $data_companypay['money'])/100),2) .' 比例 '.cfg('company_pay_user_percent').'%';
        }
        $data_companypay['status'] = 0;
        $data_companypay['add_time'] = time();
        $data_work_log = [
            'merchant_worker_id' => $work_id,
            'type' => 2,
            'money' => $money,
            'time' => time(),
            'ip' => get_client_ip(0),
            'desc' => '提现至微信-审核中',
            'order_id' => 0,
            'from_type' => 3
        ];
        $withdrawData = [
            'pay_type' => 'user',
            'pay_id' => $work_id,
            'openid' => $openid,
            'nickname' => $truename,
            'phone' => $worker['mobile'],
            'money' => $money*((100-$percent)/100)*100,
            'desc' => "用户提现{$money}" . cfg('Currency_txt') . "，用户ID: " . $work_id,
            'status' => 0,
            'add_time' => time(),
        ];
        $withdrawData['service_charges'] = $money * 100 - $withdrawData['money'];
        if ($percent > 0) {
            $withdrawData['desc'] .= '|手续费 ' . get_format_number($withdrawData['service_charges'] / 100) . ' 比例 ' . $percent . '%';
        }
        Db::startTrans();
        try {
            $with_id = (new WithdrawList())->insertGetId($data_companypay);
            $withdrawData['withdraw_id'] = $with_id;
            $data_work_log['order_id'] = $with_id;
            $log_id = (new MerchantWorkersMoney())->insertGetId($data_work_log);
            $withdrawData['worker_withdraw_id'] = $log_id;
            (new Companypay())->save($withdrawData);
            (new MerchantWorkers())->where(['merchant_worker_id'=>$work_id])->dec('now_money',$money)->update();
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}