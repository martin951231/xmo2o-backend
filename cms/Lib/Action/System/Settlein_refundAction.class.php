<?php

class Settlein_refundAction extends BaseAction
{
    /**
     * 待审核
     */
    public function index()
    {
        if(C('customization.merchant_settle_in') != 1){
            $this->frame_error_tips('没有找到该分类信息！');
        } 
        $merchantSettleInPayModel = M('Merchant_settle_in_pay');

        $condition = [];
        $condition['type'] = 2;
        $condition['status'] = 20;
        $mer_ids = $merchantSettleInPayModel->group('mer_id')->where($condition)->getField('mer_id', true);
        $merMap = [];
        if(count($mer_ids)){
            $merList = M('Merchant')->field('mer_id,name')->where(['mer_id'=>['in', $mer_ids]])->select();
            $merMap = array_column($merList, 'name', 'mer_id');
        }
 
        $count = $merchantSettleInPayModel->where($condition)->count('order_id');
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $merchantSettleInPayModel
                ->where($condition)
                ->field(true)
                ->where($condition)
                ->order('create_time desc')
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select(); 

        foreach ($list as $key => $val) {
            $list[$key]['mer_name'] = $merMap[$val['mer_id']] ?? '';
        }

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list', $list);

        $this->display();
    }

    /**
     * 已审核
     */
    public function audit()
    {
        if(C('customization.merchant_settle_in') != 1){
            $this->frame_error_tips('没有找到该分类信息！');
        }

        $merchantSettleInPayModel = M('Merchant_settle_in_pay');

        $condition = [];
        $condition['type'] = 2;
        $condition['status'] = ['in', [21, 22]];
        $mer_ids = $merchantSettleInPayModel->group('mer_id')->where($condition)->getField('mer_id', true);
        $merMap = [];
        if(count($mer_ids)){
            $merList = M('Merchant')->field('mer_id,name')->where(['mer_id'=>['in', $mer_ids]])->select();
            $merMap = array_column($merList, 'name', 'mer_id');
        }
 
        $count = $merchantSettleInPayModel->where($condition)->count('order_id');
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $merchantSettleInPayModel
                ->where($condition)
                ->field(true)
                ->where($condition)
                ->order('create_time desc')
                ->limit($p->firstRow . ',' . $p->listRows)
                ->select(); 

        foreach ($list as $key => $val) {
            $list[$key]['mer_name'] = $merMap[$val['mer_id']] ?? '';
        }

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list', $list);


        $this->display();
    }

    /**
     * 处理申请
     */
    public function handleAuditAjax()
    {
        $type = I('post.type');
        $id = I('post.id');
        $msg = I('post.msg');
        if(empty($type)||empty($id)){
            $this->jsonError('请求参数有误！');
        }
        if($type == 2 && empty($msg)){
            $this->jsonError('请填写驳回理由！');
        }

        $merchantSettleInPayModel = D('Merchant_settle_in_pay');
        $data = $merchantSettleInPayModel->where(['order_id'=>$id])->find();
        if(!$data){
            $this->jsonError('数据不存在！');
        }

        $merchantModel = D('Merchant');

        $saveData = [];
        //同意申请
        if($type == 1){
            $saveData['status'] = 21; 

            $now_merchant = $merchantModel->get_info($data['mer_id']);

            $merchantMoneyData = [];
            $merchantMoneyData['mer_id'] = $data['mer_id'];
            $merchantMoneyData['order_id'] = $data['orderid'];
            $merchantMoneyData['num'] = 1;
            $merchantMoneyData['type'] = 'settlein';
            $merchantMoneyData['money'] = $data['money'];
            $merchantMoneyData['income'] = 1;
            $merchantMoneyData['use_time'] = time();
            $merchantMoneyData['desc'] = '商家入驻缴费退款';
            $merchantMoneyData['now_mer_money'] = $now_merchant['money'] + $data['money'];
            $merchantMoneyData['pay_money'] = $data['money']; 
            if(M('Merchant_money_list')->add($merchantMoneyData)){
                $merchantModel->where(['mer_id'=>$data['mer_id']])->save(['money'=>$merchantMoneyData['now_mer_money']]);
            }

        }else{
            $saveData['status'] = 22;
            $saveData['msg'] = $msg; 
        }
        $saveData['refund_time'] = time();

        if($merchantSettleInPayModel->where(['order_id'=>$data['order_id']])->save($saveData)){
            $merchantSettleInPayModel->calcMoneyChangeMoney($data['mer_id']);
        }

        $this->jsonSuccess([],'操作成功');
        
    }

    private function jsonSuccess($data, $msg = '')
    { 
        $return = json_encode([
            'error' => 0,
            'msg' => $msg,
            'data' => $data
        ]);
        header('content-type:application/json');
        exit($return);
    }
    private function jsonError($msg, $data = [], $code = 1001)
    {
        $return = json_encode([
            'error' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
        header('content-type:application/json');
        exit($return);
    }
}