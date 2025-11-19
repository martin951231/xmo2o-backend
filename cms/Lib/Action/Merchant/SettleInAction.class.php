<?php

class SettleInAction extends BaseAction
{
	public function index()
	{  
        if(C('customization.merchant_settle_in') != 1){
            $this->error(L_('无权访问！'));
        }
        if(IS_POST){
            $post = I('post.'); 
            if(!in_array($post['type'], ['recharge', 'refund'])){
                $this->error(L_('非法请求！'));
            }
            switch ($post['type']) {
                case 'recharge':
                    $this->toRecharge($post['recharge_money'], $post['recharge_remark']);
                    break;
                case 'refund':
                    $this->toRefund($post['refund_money'], $post['refund_remark']);
                    break; 
            }
        } else{
            
            $mer_id = $this->merchant_session['mer_id'];
            $merchant_info = D('Merchant')->get_info($mer_id); 
            $condition = [];
            $condition['mer_id'] = $mer_id;  
            $condition['status'] = ['in', [1,20,21,22]];  
    
            $data = M('Merchant_settle_in_pay')->where($condition)->order('create_time desc')->select(); 
            $list = ['recharge' => [], 'refund' => []];
            foreach ($data as $key => $item) {
                $item['type_text'] = $item['type'] == 1 ? '充值' : '退款'; 
                $item['pay_type'] = $this->formatPayType($item['pay_type']);
                $item['recharge_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '-';
                $item['refund_time'] = $item['refund_time'] ? date('Y-m-d H:i:s', $item['refund_time']) : '-';
                switch ($item['status']) {
                    case 0:
                        $item['status_text'] = '<span style="color: #999999">未支付</span>';
                        break;
                    case 1:
                        $item['status_text'] = '<span style="color: #009800">已支付</span>';
                        break;
                    case 20:
                        $item['status_text'] = '<span style="color: ##ff9b2c">退款审核中</span>';
                        break;
                    case 21:
                        $item['status_text'] = '<span style="color: #009800">退款成功</span>';
                        break;
                    case 22:
                        $item['status_text'] = '<span style="color: ##ff2800">退款失败</span>';
                        break;
                }
                if($item['type'] == 1){
                    $list['recharge'][] = $item;
                }else{
                    $list['refund'][] = $item;
                }
            }
    
            
    
    
            $this->assign('merchant_info', $merchant_info);
            $this->assign('list', $list); 
            $this->display(); 

        }
 
    } 


    /**
     * 充值
     */
    private function toRecharge($money, $remark)
    {
        $mer_id = $this->merchant_session['mer_id'];
        if(empty($mer_id)){
            $this->error(L_('请先登录！'));
        }
        if(empty($money) || !is_numeric($money)){
            $this->error(L_('请正确填写金额！'));
        }  
        $money = floatval($money);

        $data = [];
        $data['mer_id'] = $mer_id; 
        $data['money'] = $money; 
        $data['type'] = 1; 
        $data['status'] = 0; 
        $data['remark'] = $remark; 
        $data['create_time'] = time();  
        if ($order_id = M('Merchant_settle_in_pay')->data($data)->add()) { 
            redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'settlein')));
        } else {
            $this->error(L_('订单创建失败，请重试。'));
        }
        
    }

    /**
     * 退款
     */
    private function toRefund($money, $remark)
    {
        $money = floatval($money);
        $mer_id = $this->merchant_session['mer_id'];
        $merchant_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_id)){
            $this->error(L_('请先登录！'));
        }
        if($merchant_info['settle_in_money'] < $money){
            $this->error(L_('可退款金额不足！'));
        }

        $nowtime = date("ymdHis");
        $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant_session['mer_id']);

        $data = [];
        $data['mer_id'] = $mer_id; 
        $data['orderid'] = $orderid; 
        $data['money'] = $money; 
        $data['type'] = 2; 
        $data['status'] = 20; 
        $data['remark'] = $remark; 
        $data['create_time'] = time();  
        if (M('Merchant_settle_in_pay')->data($data)->add()) { 
            $settle_in_money = D('Merchant_settle_in_pay')->calcMoney($mer_id);
            M('Merchant')->where(['mer_id'=>$mer_id])->save(['settle_in_money'=>$settle_in_money]);
        } else {
            $this->error(L_('订单创建失败，请重试。'));
        }
        $this->success('退款申请提交成功！', U('SettleIn/index'));
        die;
    }

    private function formatPayType($type)
    {
        $payTypeMap = [
            'weixin'        =>  '微信'
        ];
        if(!isset($payTypeMap[$type])){
            return '-';
        }
        return $payTypeMap[$type];
    }


    // public function calc()
    // {
    //     $aa = D('Merchant_settle_in_pay')->calcMoney(964);
    //     dump($aa);
    // }
}