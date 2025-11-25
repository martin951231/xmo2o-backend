<?php
class Merchant_withdraw_chinaumsModel extends Model{
    /**
     * 查询银联商户可提现金额
     */
    public function queryChinaumsWithdraw($merId)
    {
        $chinaumsMerNo = D("Merchant")->where(['mer_id'=>$merId])->getField("chinaums_withdraw_merno");
        $data = [
            'code' => 0,
            'msg' => '',
            'money' => 0,
            'chinaums_merno' => $chinaumsMerNo?:''
        ];
        //银联云闪付通道
        if(C('config.open_chinaums_without_asset')==1&&$chinaumsMerNo){
            try{
                import('@.ORG.pay.Chinaums');
                $chinaums = new Chinaums();

                $money = $chinaums->queryWithdrawBalance($chinaumsMerNo);
                $data['money'] = $money?($money/100):0;
            } catch(Exception $e){
                $data['code'] = 1;
                $data['msg'] = $e->getMessage();
            }
        }else{
            $data['chinaums_merno'] = '';
        }
        return $data;
    }

    /**
     * 提现操作
     */
    public function applyWithdraw($params){
        $money = $params['money']??0;
        $info = $params['info']??'';
        $mer_id = $params['mer_id'];
        $company_least_money =   C('config.min_withdraw_money')?C('config.min_withdraw_money'):C('config.company_least_money');
        if(!is_integer($money)&&$money<$company_least_money){
            return ['code'=>1,'msg'=>L_('提现金额最少'.$company_least_money.'元')];
        }
        $mer_info = D("Merchant")->where(['mer_id'=>$mer_id])->find();
        if(!$mer_info||!$mer_info['chinaums_withdraw_merno']){
            return ['code'=>1,'msg'=>L_('当前商家未配置子商户银联商户号！')];
        }
        $r_reply = $this->where(['status'=>10,['mer_id'=>$mer_id]])->find();
        if($r_reply){
            return ['code'=>1,'msg'=>L_('已有申请！')];
        }
        $return = [
            'code' => 0,
            'msg' => '',
            'data' => [],
        ];
        $data = [
            'chinaumsMerNo' => $mer_info['chinaums_withdraw_merno'],
            'money' => $money,
            'remark' => $info?:'',
            'sysOrderId' => date('YmdHis') .createRandNumber(6)
        ];
        try{
            import('@.ORG.pay.Chinaums');
            $chinaums = new Chinaums();

            $res = $chinaums->withdraw($data);
            $save_withdraw = [
                'mer_id' => $mer_id,
                'real_orderid' => $data['sysOrderId'],
                'money' => $money*100,
                'account' => $data['chinaumsMerNo'],
                'name' => $mer_info['name'],
                'status' => 10,
                'remark' => $data['remark'],
                'withdraw_time' => time(),
                'from_type' => $params['from_type']??0,
                'third_id' => $res['ssn'], //第三方流水号
                'info' => $res?json_encode(['withdraw_send'=>$res]):'',
            ];
            $this->add($save_withdraw);
        } catch(Exception $e){
            $return['code'] = 1;
            $return['msg'] = $e->getMessage();
        }
        return $return;
    }

    /**
     * 查询体现状态
     */
    public function queryWithdrawResults($limit = 20){
        $list = $this->where(['status'=>10])->limit($limit)->select();
        import('@.ORG.pay.Chinaums');
        $chinaums = new Chinaums();
        foreach ($list as $item){
            $item['transDate'] = $item['withdraw_time']?date('Ymd',$item['withdraw_time']):'';
            $item['chinaumsMerNo'] = $item['account'];
            if(empty($item['chinaumsMerNo'])||empty($item['transDate'])||empty($item['real_orderid'])){
                fdump_api([$item,'数据有问题'],'chinaums/withdraw_error',1);
                continue;
            }
            $save_data = [
                'update_time' => time(),
            ];
            try{
                $res = $chinaums->queryWithdrawStatus($item);
                if($res['respCode']=='000000'){
                    $save_data['cmms_amt'] = $res['fee']??0;
                    if($res['status']==3){
                        $save_data['status'] = 3;
                    }elseif(in_array($res['status'],[-1,5])){
                        $save_data['status'] = 20;
                        $save_data['fail_reason'] = $res['desc'];
                    }
                }else{
                    $save_data['status'] = 20;
                    $save_data['fail_reason'] = $res['respDesc'];
                }
                $save_data['third_status'] = $res['status'];
                $info = $item['info']?json_decode($item['info'],true):[];
                $info['withdraw_end'] = isset($info['withdraw_end'])?array_merge($info['withdraw_end'],[$res]):[$res];
                $save_data['info'] = $info?json_encode($info):'';
                $this->where(['id'=>$item['id']])->save($save_data);
            } catch(Exception $e){
                fdump_api([$item,$e->getMessage()],'chinaums/withdraw_error',1);
            }
        }
    }
}