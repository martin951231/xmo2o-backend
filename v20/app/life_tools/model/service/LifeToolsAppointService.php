<?php


namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsAppoint;
use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\life_tools\model\db\LifeToolsAppointSeatDetail;
use app\life_tools\model\db\LifeToolsAppointSku;
use app\life_tools\model\db\LifeToolsAppointVerify;
use app\merchant\model\db\MerchantStoreStaff;
use think\facade\Db;
class LifeToolsAppointService
{
    public $lifeToolsAppointJoinOrderModel = null;
    public $lifeToolsAppointVerifyModel = null;
    public $lifeToolsAppointModel = null;
    public $lifeToolsAppointSeatDetailModel = null;
    public $merchantStoreStaffModel = null;

    public function __construct()
    {
        $this->lifeToolsAppointJoinOrderModel = new LifeToolsAppointJoinOrder();
        $this->lifeToolsAppointVerifyModel = new LifeToolsAppointVerify();
        $this->lifeToolsAppointModel = new LifeToolsAppoint();
        $this->lifeToolsAppointSeatDetailModel = new LifeToolsAppointSeatDetail();
        $this->merchantStoreStaffModel = new MerchantStoreStaff();
    }

    /**
     * 活动预约核销
     */
    public function verification($params)
    {
        if(empty($params['code'])){
            throw new \think\Exception('code不能为空！');
        }
        if(empty($params['staff_id']) || empty($params['mer_id'])){
            throw new \think\Exception('请登录！');
        }
        if($params['staff']['can_verify_activity_appoint'] == 0){
            throw new \think\Exception('不可核销商家活动！');
        }
        $field = 'c.title,c.start_time,c.end_time,c.address,c.image_small,c.desc,c.is_suspend,c.need_verify
        ,j.status,j.pigcms_id as order_id,j.custom_form,j.verify_code,j.paid,j.pay_time,j.verify_time,j.mer_id';
        $condition = [];
        $condition[] = ['j.verify_code', '=', $params['code']];
        $detail = (new LifeToolsAppointJoinOrder())->orderDetail($condition, $field);
        if(!$detail){
            throw new \think\Exception('核销码不存在！');
        }

        if($detail['need_verify'] == 0){
            throw new \think\Exception('无需核销！');
        }
        if($detail['paid'] != 1 || $detail['pay_time'] == 0){
            throw new \think\Exception('订单未支付！');
        }
        if($detail['verify_time'] != 0){
            throw new \think\Exception('请勿重复核销！');
        }
        if($detail['status'] != 1){
            throw new \think\Exception('该订单状态不支持核销！');
        }
        if($detail['mer_id'] != $params['mer_id']){
            throw new \think\Exception('无操作权限！');
        }
        $newTime = time();
        if($detail['start_time'] > $newTime){
            throw new \think\Exception('活动未开始！');
        }
        if($detail['end_time'] < $newTime){
            throw new \think\Exception('活动已结束！');
        }
        // 选座位先屏蔽
        // $staff = $this->merchantStoreStaffModel->where('id', $params['staff_id'])->find();
        // if(!$staff || $staff->can_verify_activity_appoint != 1){
        //     throw new \think\Exception('无权操作');
        // }

        // $condition = [];
        // $condition[] = ['code', '=', $params['code']];
        // $orderDetail = $this->lifeToolsAppointSeatDetailModel->where($condition)->find();
        // if(!$orderDetail){
        //     throw new \think\Exception('订单不存在！');
        // }
        
        // $condition = [];
        // // $condition[] = ['pigcms_id', '=', $orderDetail->order_id];
        // $condition[] = ['verify_code', '=', $params['code']];
        // $condition[] = ['is_del', '=', 0];
        // $Order = $this->lifeToolsAppointJoinOrderModel->with(['appoint'])->where($condition)->find();
        // if(!$Order){
        //     throw new \think\Exception('订单不存在！');
        // }
        // if($Order->need_verify == 0){
        //     throw new \think\Exception('无需核销！');
        // }
        // if($Order->paid != 1 || $Order->pay_time == 0){
        //     throw new \think\Exception('订单未支付！');
        // }
        // if($Order->verify_time != 0){
        //     throw new \think\Exception('请勿重复核销！');
        // }
        // if($Order->status != 1){
        //     throw new \think\Exception('该订单状态不支持核销！');
        // }
        // if($Order->mer_id != $params['mer_id']){
        //     throw new \think\Exception('无操作权限！');
        // }
        // if(is_null($Order->appoint)){
        //     throw new \think\Exception('活动不存在！');
        // }
        // $newTime = time();
        // if($Order->appoint->start_time > $newTime){
        //     throw new \think\Exception('活动不未开始！');
        // }
        // if($Order->appoint->end_time < $newTime){
        //     throw new \think\Exception('活动已结束！');
        // }
        
        $this->doVerify($detail['order_id'], $params['staff_id']);
        return true;
    }


    /**
     * 执行核销
     */
    public function doVerify($order_id, $staff_id = 0, $msg='核销成功')
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $order_id]; 
        $Order = $this->lifeToolsAppointJoinOrderModel->with(['appoint'])->where($condition)->find();

        $Verify = $this->lifeToolsAppointVerifyModel;
        Db::startTrans();
        try {
            $Order->verify_time = time();
            $Order->status = 3;
            $Order->save();

            $Verify->appoint_order_id = $Order->pigcms_id;
            $Verify->uid = $Order->uid;
            $Verify->mer_id = $Order->mer_id;
            $Verify->appoint_id = $Order->appoint_id;
            $Verify->verify_code = $Order->verify_code;
            $Verify->status = 1;
            $Verify->msg = $msg;
            $Verify->verify_type = 1;
            $Verify->staff_id = $staff_id;
            $Verify->add_time = time();
            $Verify->save();
 
            Db::commit();
        } catch (\Exception $e) { 
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }


    /**
     * 核销记录
     */
    public function verifyList($params)
    {
        if(empty($params['staff_id']) || empty($params['mer_id'])){
            throw new \think\Exception('请登录！');
        }

        $condition = [];
        if(!empty($params['keywords'])){
            switch ($params['search_by']) {
                case 1: 
                    $condition[] = ['appoint_order_id', 'like', "%{$params['keywords']}%"];
                    break;
                case 2:
                    $conditionAppoint = [];
                    $conditionAppoint[] = ['title', 'like', "%{$params['keywords']}%"];
                    $appoint_ids = $this->lifeToolsAppointModel->where($conditionAppoint)->column('appoint_id');
                    $condition[] = ['appoint_order_id', 'in', $appoint_ids];
                    break;
            }
        }

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $condition[] = ['add_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
        }
        
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['staff_id', '=', $params['staff_id']]; 
        $query = $this->lifeToolsAppointVerifyModel
                ->with(['order','appoint'=>function($query){
                    $query->append(['start_time_text', 'end_time_text']);
                }])
                ->where($condition)
                ->order('add_time DESC')
                ->append(['add_time_text']); 
        if($params['from'] == 'v20'){
            return $query->paginate($params['page_size']);
        }else{
            return $query->page($params['page'], $params['page_size'])->select();
        }
    }


    /**
     * 活动结束给商家结算
     */
    public function settlement()
    { 
        $nowTime = time();
        $condition = [];
        //获取过期要处理的列表
        $OverdueList = $this->lifeToolsAppointJoinOrderModel->getOverdueList($condition);


        $condition = [];
        $condition[] = ['a.is_del', '=', 0];
        $condition[] = ['a.status', '=', 1];
        $condition[] = ['a.end_time', '<', $nowTime];
        //获取要结算的列表
        $CanSettlementList = $this->lifeToolsAppointJoinOrderModel->getCanSettlementList($condition);

    }

    /**
     * 通过核销码查看预约详情
     */
    public function getAppointDetailByCode($code)
    {
        $field = 'c.title,c.start_time,c.end_time,c.address,c.image_small,c.desc,c.is_suspend,c.limit_type,c.limit_num,c.join_num
        ,j.status,j.pigcms_id as order_id,j.custom_form,j.verify_code,j.sku_id,j.real_orderid,j.add_time,j.price
        ,u.phone,u.nickname';
        $condition = [];
        $condition[] = ['j.verify_code', '=', $code];
        $detail = (new LifeToolsAppointJoinOrder())->orderDetail($condition, $field);
        // $data = $this->lifeToolsAppointJoinOrderModel->where($condition)->find();
        if($detail){
            if($detail['start_time'] < time() && $detail['end_time'] > time()){
                $detail['act_status'] = 1;//进行中
                $detail['act_status_txt'] = '进行中';//进行中
            }elseif($detail['end_time'] < time()){
                $detail['act_status'] = 2;//已经结束
                $detail['act_status_txt'] = '已结束';
            }elseif($detail['start_time'] > time()) {
                $detail['act_status'] = 0;//未开始
                $detail['act_status_txt'] = '报名中';
                
                if($detail['limit_type'] == 1 && $detail['limit_num'] <= $detail['join_num']){
                    $detail['act_status'] = 3;//已暂停
                    $detail['act_status_txt'] = '人数已满';
                }
                if($detail['is_suspend'] == 1){
                    $detail['act_status'] = 3;//已暂停
                    $detail['act_status_txt'] = '已暂停';
                }
            }
            
            $detail['add_time_text'] = date('Y.m.d H:i:s', $detail['add_time']);

            $extra = [];
            $extra[] = [
                'title'   =>    '订单号',
                'type'    =>    'text',
                'value'   =>    [$detail['real_orderid']]
            ];
            $extra[] = [
                'title'   =>    '注册昵称',
                'type'    =>    'text',
                'value'   =>    [$detail['nickname']]
            ];
            $extra[] = [
                'title'   =>    '注册手机号',
                'type'    =>    'text',
                'value'   =>    [$detail['phone']]
            ];
            $extra[] = [
                'title'   =>    '下单时间',
                'type'    =>    'text',
                'value'   =>    [$detail['add_time_text']]
            ];

            $custom_form = $detail['custom_form'];
            if($custom_form && is_array($custom_form) && count($custom_form)){
                $userInput = [];
                $imageArr = [];
                foreach($custom_form as $key => $val){
                    if(in_array($val['type'], ['text', 'idcard', 'phone', 'email'])){
                        $value = [];
                        if($val['value'] != ''){
                            $value = [$val['value']];
                        }
                        $userInput[] = [
                            'title' =>  $val['title'],
                            'type'  =>  $val['type'],
                            'value' =>  $value
                        ];
                    }else if($val['type'] == 'area'){
                        $value = '';
                        foreach($val['value'] as $k => $v){
                            $value .= $v['label'] . ' ';
                        }
                        $userInput[] = [
                            'title' =>  $val['title'],
                            'type'  =>  $val['type'],
                            'value' =>  [rtrim($value, ' ')]
                        ];
                    }else if($val['type'] == 'image'){
                        $imageArr[] = [
                            'title' =>  $val['title'],
                            'type'  =>  $val['type'],
                            'value' =>  $val['show_value']
                        ];
                    }else if($val['type'] == 'select'){
                        $userInput[] = [
                            'title' =>  $val['title'],
                            'type'  =>  $val['type'],
                            'value' =>  [$val['show_value']]
                        ];
                    }
                }
                $userInput = array_merge($userInput, $imageArr);
                $detail['custom_form'] = $userInput;
            }
            if(isset($detail['custom_form'])){
                $detail['custom_form'] = array_merge($extra, $detail['custom_form']);
            }else{
                $detail['custom_form'] = $extra;
            }

            $detail['sku_str'] = null;
            //多规格
            if($detail['sku_id']){
                $sku = (new LifeToolsAppointSku())->where('sku_id', $detail['sku_id'])->find();
                $detail['sku_str'] = $sku['sku_str'] ?? null;
            }

            $detail['image_small'] = replace_file_domain($detail['image_small']);
            $detail['start_time'] = date('Y.m.d H:i', $detail['start_time']);
            unset($detail['end_time'], $detail['status'], $detail['is_suspend']);
        }else{
            throw new \think\Exception('核销码不存在！');
        }
        return $detail;
    }

    /**
     * 活动列表
     */
    public function getAppointList($params)
    {
        $condition = [];
        $condition[] = ['a.is_del', '=', 0];
        // $condition[] = ['a.status', '=', 1];
        $condition[] = ['a.mer_id', '=', $params['mer_id']];
        if (!empty($params['keywords'])) {
            $condition[] = ['a.title', 'like', '%' . $params['keywords'] . '%'];
        }

 
        $time = time();
        if(!empty($params['status'])){
            switch($params['status']){
                case 1: //报名中
                    $condition[] = ['a.is_suspend', '=', 0];
                    $condition[] = ['a.start_time', '>', $time];
                    break;
                case 2: //已开始
                    $condition[] = ['a.start_time', '<', $time];
                    $condition[] = ['a.end_time', '>', $time];
                    break;
                case 3: //已结束
                    $condition[] = ['a.end_time', '<', $time];
                    break;
            } 
        }


        $list = (new LifeToolsAppoint())->getListAndMer($condition, 'a.*,m.name as mer_name', 'a.appoint_id desc',$params['page'], $params['page_size']);

        if($list['list']){
            foreach($list['list'] as $key => $value){
                $list['list'][$key]['start_time'] = date("Y.m.d H:i", $value['start_time']);
                $list['list'][$key]['image_small'] = replace_file_domain($list['list'][$key]['image_small']);
                $activity_status = 0;
                $activity_status_text = '';
                if($value['start_time'] > $time){
                    $activity_status = 1;
                    $activity_status_text = '报名中';
                }
                
                
                if($value['limit_type'] == 1 && $value['limit_num'] <= $value['join_num']){
                        $activity_status = 2;
                        $activity_status_text = '报名已满';
                    }

                    if($value['start_time'] < $time && $value['end_time'] > $time){
                        $activity_status = 3;
                        $activity_status_text = '活动进行中';
                    }
    

                if($value['end_time'] < $time){
                    $activity_status = 4;
                    $activity_status_text = '活动已结束';
                }
                if($value['is_suspend'] == 1){
                        $activity_status = 4;
                        $activity_status_text = '活动已暂停';
                }
                $list['list'][$key]['activity_status'] = $activity_status; 
                $list['list'][$key]['activity_status_text'] = $activity_status_text; 
            }

        }
        return $list;
    }
}