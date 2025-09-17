<?php


namespace app\shop\model\service\rebate;


use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\ShopOrder;
use app\shop\model\db\ShopGoods;
use app\shop\model\db\ShopOrderDetail;
use app\shop\model\db\ShopRebate;
use app\shop\model\db\ShopRebateUser;
use app\shop\model\db\ShopSubsidiaryPieceGoods;
use think\Db;

class RebateService
{
    /**
     * 集点返券列表
     * @param $param
     * @return array|\think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($param){
        $store_id = $param['store_id']??'';
        $page = $param['page']??0;
        $pageSize = $param['pageSize']??0;
        if(!$store_id){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        $where[] = ['a.is_del','=',0];
        $where[] = ['a.store_id','=',$store_id];
        $list = (new ShopRebate())->getList($where,$page,$pageSize);
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['goods_name'] = '';
            if($item['goods_ids']){
                $goods = (new ShopGoods())->field('name')->where([["goods_id",'in',explode(',',$item['goods_ids'])]])->select()->toArray();
                $goods_names = array_column($goods,'name');
                $list['data'][$key]['goods_name'] = $goods?implode(',',$goods_names):'';
            }
            $list['data'][$key]['activy_time'] = date('Y-m-d H:i:s',$item['start_time']).'至'.date('Y-m-d H:i:s',$item['end_time']);
            $list['data'][$key]['status_txt'] = '';
            if($item['start_time']<=time()&&$item['end_time']>=time()&&$item['status']){
                $list['data'][$key]['status_txt'] = L_('进行中');
            }elseif ($item['start_time']<=time()&&$item['end_time']>=time()&&!$item['status']){
                $list['data'][$key]['status_txt'] = L_('未使用');
            }elseif (!$item['status']){
                $list['data'][$key]['status_txt'] = L_('已失效');
            }elseif ($item['start_time']>time()){
                $list['data'][$key]['status_txt'] = L_('未开始');
            }elseif ($item['end_time']<time()){
                $list['data'][$key]['status_txt'] = L_('已过期');
            }
        }
        return $list;
    }

    /**
     * 修改状态
     * @param $param
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeStatus($param){
        $store_id = $param['store_id']??'';
        $id = $param['id']??0;
        $status = $param['status']??0;
        if(!$store_id){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        if(!$id){
            throw new \think\Exception(L_('请选择要修改的活动！'));
        }
        if(!in_array($status,[0,1])){
            throw new \think\Exception(L_('状态不存在！'));
        }
        $rebate_model = new ShopRebate();
        $rebate_info = $rebate_model->where(['store_id'=>$store_id,'id'=>$id,'is_del'=>0])->find();
        if(empty($rebate_info)||!$rebate_info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($status==1){
            $other_info = $rebate_model->where([['store_id','=',$store_id],['id','<>',$id],['is_del','=',0],['status','=',1]])->find();
            if($other_info){
                throw new \think\Exception(L_('已有正在使用中活动，无法设置成使用中！'));
            }
        }
        
        if($status==$rebate_info['status']){
            return true;
        }
        if($rebate_model->where(['id'=>$id])->save(['status'=>$status])){
            if($status==1){
                $this->supplyAgainCoupon($id);
            }
            return true;
        }else{
            throw new \think\Exception(L_('修改失败！'));
        }
    }

    /**
     * 活动详情
     * @return array|\think\Model|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function showDetail($param){
        $store_id = $param['store_id']??'';
        $id = $param['id']??0;
        $mer_id = $param['mer_id']??0;
        if(!$store_id){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        if(!$id){
            throw new \think\Exception(L_('请选择要修改的活动！'));
        }
        $rebate_model = new ShopRebate();
        $rebate_info = $rebate_model->where(['store_id'=>$store_id,'id'=>$id,'is_del'=>0])->find();
        if(empty($rebate_info)||!$rebate_info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $rebate_info['start_time'] = $rebate_info['start_time']?date('Y-m-d H:i:s',$rebate_info['start_time']):'';
        $rebate_info['end_time'] = $rebate_info['end_time']?date('Y-m-d H:i:s',$rebate_info['end_time']):'';
        //获取优惠券
        $now_coupon = (new CardNewCoupon())->where(['coupon_id'=>$rebate_info['coupon_id']])->find();
        $rebate_info['coupon_name'] = $now_coupon?$now_coupon['name']:'';
        $rebate_info['coupon_id'] = $now_coupon?$now_coupon['coupon_id']:'';
        $rebate_info['goods_ids'] = explode(',',$rebate_info['goods_ids']);
        //获取已选商品
        $rebate_info['ready_goods_list'] = (new ShopGoods())->field('goods_id,name,old_price,stock_num')->where([['status','=',1],['goods_id','in',$rebate_info['goods_ids']]])->select();
        return $rebate_info;
    }

    /**
     * 保存活动信息
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveDate($param){
        $id = $param['id']??0;
        $saveData = [
            'store_id' => $param['store_id']??0,
            'name' => $param['name']??'',
            'start_time' => $param['start_time']??'',
            'end_time' => $param['end_time']??'',
            'note' => $param['note']??'',
            'reset_day' => $param['reset_day']??0,
            'total_order' => $param['total_order']??0,
            'coupon_id' => $param['coupon_id']??'',
            'goods_ids' => $param['goods_ids']??[],
            'mer_id' => $param['mer_id']??'',
        ];
        if(!$saveData['store_id']){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        if(!$saveData['mer_id']){
            throw new \think\Exception(L_('商家不存在！'));
        }
        if(!$saveData['name']){
            throw new \think\Exception(L_('活动名称不能为空！'));
        }
        if(!$saveData['start_time']||!$saveData['end_time']){
            throw new \think\Exception(L_('活动时间不能为空！'));
        }
        $saveData['start_time'] = strtotime($saveData['start_time']);
        $saveData['end_time'] = strtotime($saveData['end_time']);
        if($saveData['start_time']>=$saveData['end_time']){
            throw new \think\Exception(L_('结束时间不能小于开始时间！'));
        }
        if(!$saveData['note']){
            throw new \think\Exception(L_('规则不能为空！'));
        }
        if(!is_numeric($saveData['reset_day'])||$saveData['reset_day']<0){
            throw new \think\Exception(L_('集点重置天数格式不对！'));
        }
        if(!is_numeric($saveData['total_order'])||$saveData['total_order']<1||$saveData['total_order']>10){
            throw new \think\Exception(L_('订单数在1~10之间！'));
        }
        if(!$saveData['coupon_id']){
            throw new \think\Exception(L_('请选择优惠券！'));
        }
        if(!is_array($saveData['goods_ids'])||!$saveData['goods_ids']){
            throw new \think\Exception(L_('请选择商品！'));
        }
        $saveData['goods_ids'] = implode(',',$saveData['goods_ids']);
        $rebate_model = new ShopRebate();
        if($id){
            $rebate_info = $rebate_model->where(['store_id'=>$saveData['store_id'],'id'=>$id,'is_del'=>0])->find();
            if(empty($rebate_info)||!$rebate_info){
                throw new \think\Exception(L_('活动不存在/已删除！'));
            }
            $old_total_order = $rebate_info['total_order'];
            $res = $rebate_model->where(['id'=>$id])->save($saveData);
            if($res||$res===0){
                if($old_total_order>=$saveData['total_order']){
                    $this->supplyAgainCoupon($id);
                }
                return true;
            }else{
                throw new \think\Exception(L_('修改失败！'));
            }
        }else{
            $saveData['status'] = 0;
            $saveData['create_time'] = time();
            if($rebate_model->save($saveData)){
                return true;
            }else{
                throw new \think\Exception(L_('添加失败！'));
            }
        }
    }
    
    public function supplyAgainCoupon($id){
        //获取当前活动
        $rebate_info = (new ShopRebate())->where(['id'=>$id,'status'=>1,'is_del'=>0])->find();
        if($rebate_info){
            //获取符合当前已购订单的数量的人员
            $rebate_user_arr = (new ShopRebateUser())->where([['rid','=',$id],['total','>=',$rebate_info['total_order']],['status','=',0]])->select();
            
            \think\facade\Db::startTrans();
            try {
                foreach ($rebate_user_arr as $item){
                    $data = [
                        'coupon_id' => $rebate_info['coupon_id'],
                        'uid' => $item['uid'],
                        'card_code' => '',
                        'not_wxapp_hadpull' => false,
                        'is_auto' => false,
                        'muti_hadpull' => false,
                        'get_all' => 0,
                        'is_system_send'=>0,
                        'is_assign' => true
                    ];
                    $res_info = invoke_cms_model('Card_new_coupon/had_pull',$data);
                    if($res_info['error_no']!=0){
                        (new ShopRebateUser)->where(['id'=>$item['id']])->save(['note'=>$res_info['error_msg'],'is_get_coupon'=>2,'status'=>1]);
                        fdump_api(['订单id：'.$item['order_ids'].'发优惠券接口失败',$res_info['error_msg']],'rebate/error',true);
                        continue;
                    }
                    $res = $res_info['retval'];
                    if ($res['error_code'] != 0) {
                        switch ($res['error_code']) {
                            case '1':
                                $error_msg = '领取失败';
                                break;
                            case '2':
                                $error_msg = '优惠券已过期';
                                break;
                            case '3':
                                $error_msg = '优惠券已经领完了';
                                break;
                            case '4':
                                $error_msg = '只允许新用户领取';
                                break;
                            case '5':
                                $error_msg = '不能再领取了';
                                break;
                        }
                        (new ShopRebateUser)->where(['id'=>$item['id']])->save(['note'=>$error_msg,'is_get_coupon'=>2,'status'=>1]);
                        fdump_api(['订单id：'.$item['order_ids'].'发优惠券接口未领取成功错误信息',$error_msg],'rebate/error',true);
                        continue;
                    }
                    (new ShopRebateUser)->where(['id'=>$item['id']])->save(['note'=>'领取成功','is_get_coupon'=>1,'status'=>1]);
                    $reduce_num=$res['coupon']['reduce_num'];
                    $reduce_num=$reduce_num>0?$reduce_num:1;
                    invoke_cms_model('System_coupon/decrease_sku',['add'=>0,'less'=>$reduce_num,'coupon_id'=>$rebate_info['coupon_id']]);
                }
                \think\facade\Db::commit();
                return true;
            }catch (\Exception $e){
                fdump_api($e->getMessage(),'rebate/error',true);
                \think\facade\Db::rollBack();
                return false;
            }
        }
    }

    /**
     * 选择商品列表
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function getGoodsList($param){
        $store_id = $param['store_id']??'';
        $keywords = $param['keywords']??'';
        $page = $param['page']??0;
        $pageSize = $param['pageSize']??10;
        if(!$store_id){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        $where[] = ['store_id','=',$store_id];
        // 关键字
        if ($keywords) {
            $where[] = ['name','like', '%' . $keywords . '%'];
        }

        // 过滤主商品
        $ids_list = (new ShopSubsidiaryPieceGoods())->field('host_goods_id')->field('host_goods_id')->group('host_goods_id')->select()->toArray();
        if($ids_list){
            $ids = array_column($ids_list,'host_goods_id');
            $where[] = ['goods_id','not in', $ids];
        }
        
        $goods_list = (new ShopGoods())->field('goods_id,name,old_price,stock_num,spec_value')->where($where)->paginate($pageSize);

        foreach ($goods_list as $value) {
            $value['is_sku'] = $value['spec_value']?1:0;
        }
        return $goods_list;
    }
    
    public function delete($param){
        $store_id = $param['store_id']??'';
        $id = $param['id']??0;
        if(!$store_id){
            throw new \think\Exception(L_('请选择店铺！'));
        }
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的活动！'));
        }
        $rebate_model = new ShopRebate();
        $rebate_info = $rebate_model->where(['store_id'=>$store_id,'id'=>$id,'is_del'=>0])->find();
        if(empty($rebate_info)||!$rebate_info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($rebate_model->where(['store_id'=>$store_id,'id'=>$id])->save(['is_del'=>1])){
            return true;
        }else{
            throw new \think\Exception(L_('删除失败！'));
        }
    }

    /**
     * 获取优惠券
     * @param $param
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponList($param){
        $mer_id = $param['mer_id']??'';
        $where = [['mer_id','=',$mer_id],['end_time','>',time()]];
        $where[] = ['status','<>',4];
        $where[] = ['is_partition','=',0];
        //获取商家优惠券列表
        $coupon_list = (new CardNewCoupon())->field('coupon_id,name')->where($where)->select();
        return $coupon_list;
    }

    /**
     * 添加集单记录
     * @param $orderInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addRebateUser($orderInfo){
        fdump_api($orderInfo,'rebate/error',true);
        $uid = $orderInfo['uid'];
        $order_type = $orderInfo['order_type'];
        $order_id = $orderInfo['order_id'];
        $store_id = $orderInfo['store_id'];
        $mer_id = $orderInfo['mer_id'];
        if(!$uid){
            return false;
        }
        if($order_type!='shop'){
            return false;
        }
        //查询订单是否存在
        $shop_order = (new ShopOrder())->where(['order_id'=>$order_id])->find();
        if(!$shop_order){
            fdump_api('订单id：'.$order_id.'不存在','rebate/error',true);
            return false;
        }
        //获取订单商品详情
        $shop_order_detail = (new ShopOrderDetail())->where(['order_id'=>$order_id])->column('goods_id');
        if(empty($shop_order_detail)||!$shop_order_detail){
            fdump_api('订单id：'.$order_id.'没有商品','rebate/error',true);
            return false;
        }
        //查询店铺是否有活动
        $rebate_info = (new ShopRebate())->where([['store_id','=',$store_id],['status','=',1],['is_del','=',0],['end_time','>',time()],['start_time','<',time()]])->find();
        if(empty($rebate_info)||!$rebate_info){
            fdump_api('订单id：'.$order_id.'该店铺没有正在进行的活动','rebate/error',true);
            return false;
        }
        $goods_arr = explode(',',$rebate_info['goods_ids']);
        $is_add = array_intersect($shop_order_detail,$goods_arr);
        if(!$is_add||empty($is_add)){
            fdump_api('订单id：'.$order_id.'没有参与活动商品','rebate/error',true);
            return false;
        }
        //获取优惠券
        $coupon = (new CardNewCoupon())->where(['coupon_id'=>$rebate_info['coupon_id']])->find();
        if(empty($coupon)||!$coupon){
            fdump_api('订单id：'.$order_id.'优惠券不存在','rebate/error',true);
            return false;
        }
        //查询用户已领取优惠券数量
        $user_coupon_num = (new CardNewCouponHadpull())->where(['uid'=>$uid,'coupon_id'=>$rebate_info['coupon_id']])->sum('num');
        if($user_coupon_num>=$coupon['limit']){
            fdump_api('订单id：'.$order_id.'优惠券最多领取'.$coupon['limit'].'张','rebate/error',true);
            return false;
        }
        //查询用户是否已参与
        $rebate_user = (new ShopRebateUser())->where([['uid','=',$uid],['rid','=',$rebate_info['id']],['is_notice','=',0],['status','=',0],['store_id','=',$store_id]])->find();
        \think\facade\Db::startTrans();
        try {
            if(($rebate_user&&$rebate_info['reset_day']>0&&($rebate_user['create_time']+($rebate_info['reset_day']*24*3600))<time())||empty($rebate_user)||!$rebate_user){
                if($rebate_user&&$rebate_info['reset_day']>0&&($rebate_user['create_time']+($rebate_info['reset_day']*24*3600))<time()){
                    (new ShopRebateUser())->where(['id'=>$rebate_user['id']])->save(['status'=>2]);
                }
                $saveData = [
                    'uid' => $uid,
                    'rid' => $rebate_info['id'],
                    'mer_id' => $mer_id,
                    'store_id' => $store_id,
                    'create_time' => time(),
                    'total' => 1,
                    'is_notice' => 0,
                    'order_ids' => $order_id,
                    'status' => 0
                ];
                if($rebate_info['total_order']==1){
                    $saveData['status'] = 1;
                }
                (new ShopRebateUser)->save($saveData);
                $rebate_user = (new ShopRebateUser)->where($saveData)->find();
            }elseif($rebate_user){
                $saveData = [
                    'status' => 0,
                    'total' => $rebate_user['total'],
                    'order_ids' => $rebate_user['order_ids'],
                ];
                $order_ids = explode(',',$rebate_user['order_ids']);
                if(!in_array($order_id,$order_ids)){
                    $order_ids = array_merge($order_ids,[$order_id]);
                    $saveData['order_ids'] = implode(',',$order_ids);
                }
                if(($rebate_user['total']+1)>=$rebate_info['total_order']){
                    $saveData['total'] = $rebate_info['total_order'];
                    $saveData['status'] = 1;
                }else{
                    $saveData['total'] = $rebate_user['total']+1;
                }
                (new ShopRebateUser)->where(['id'=>$rebate_user['id']])->save($saveData);
            }
            //派发优惠券
            if($saveData&&$saveData['status']==1){
                $data = [
                    'coupon_id' => $rebate_info['coupon_id'],
                    'uid' => $uid,
                    'card_code' => '',
                    'not_wxapp_hadpull' => false,
                    'is_auto' => false,
                    'muti_hadpull' => false,
                    'get_all' => 0
                ];
                $res_info = invoke_cms_model('Card_new_coupon/had_pull',$data);
                if($res_info['error_no']!=0){
                    (new ShopRebateUser)->where(['id'=>$rebate_user['id']])->save(['note'=>$res_info['error_msg'],'is_get_coupon'=>2]);
                    fdump_api(['订单id：'.$order_id.'发优惠券接口失败',$res_info['error_msg']],'rebate/error',true);
                    \think\facade\Db::commit();
                    return false;
                }
                $res = $res_info['retval'];
                if ($res['error_code'] != 0) {
                    switch ($res['error_code']) {
                        case '1':
                            $error_msg = '领取失败';
                            break;
                        case '2':
                            $error_msg = '优惠券已过期';
                            break;
                        case '3':
                            $error_msg = '优惠券已经领完了';
                            break;
                        case '4':
                            $error_msg = '只允许新用户领取';
                            break;
                        case '5':
                            $error_msg = '不能再领取了';
                            break;
                    }
                    (new ShopRebateUser)->where(['id'=>$rebate_user['id']])->save(['note'=>$error_msg,'is_get_coupon'=>2]);
                    fdump_api(['订单id：'.$order_id.'发优惠券接口未领取成功错误信息',$error_msg],'rebate/error',true);
                    \think\facade\Db::commit();
                    return false;
                }
                (new ShopRebateUser)->where(['id'=>$rebate_user['id']])->save(['note'=>'领取成功','is_get_coupon'=>1]);
                $reduce_num=$res['coupon']['reduce_num'];
                $reduce_num=$reduce_num>0?$reduce_num:1;
                invoke_cms_model('System_coupon/decrease_sku',['add'=>0,'less'=>$reduce_num,'coupon_id'=>$rebate_info['coupon_id']]);
            }
            \think\facade\Db::commit();
            return true;
        }catch (\Exception $e){
            fdump_api($e->getMessage(),'rebate/error',true);
            \think\facade\Db::rollBack();
            return false;
        }
    }

    /**
     * 退款返集点/优惠券
     * @param $param
     * @return false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundRebateUser($real_orderid,$type){
        fdump_api([$real_orderid,$type],'rebate/refund',true);
        if($type!='shop'){
            fdump_api('订单编号：'.$real_orderid.'不支持'.$type.'业务','rebate/refund',true);
        }
        $shop_order = (new ShopOrder())->where(['real_orderid'=>$real_orderid])->find();
        if(empty($shop_order)||!$shop_order){
            fdump_api('订单编号：'.$real_orderid.'订单不存在','rebate/refund',true);
            return false;
        }
        //查询用户是否已参与
        $where[] = ['','exp',\think\facade\Db::raw("FIND_IN_SET(".$shop_order['order_id'].",order_ids)")];
        $where[] = ['store_id','=',$shop_order['store_id']];
        $where[] = ['uid','=',$shop_order['uid']];
        $rebate_user = (new ShopRebateUser())->where($where)->find();
        if(empty($rebate_user)||!$rebate_user){
            fdump_api('订单编号：'.$real_orderid.'用户未参与','rebate/refund',true);
            return false;
        }
        //查询店铺是否有活动
        $rebate_info = (new ShopRebate())->where([['id','=',$rebate_user['rid']]])->find();
        if(!$rebate_info){
            fdump_api('订单编号：'.$real_orderid.'店铺没有活动不存在/已删除','rebate/refund',true);
            return false;
        }
        $refund_order_ids = $rebate_user['refund_order_ids']?explode(',',$rebate_user['refund_order_ids']):[];
        $refund_order_ids = array_merge($refund_order_ids,[$shop_order['order_id']]);
        \think\facade\Db::startTrans();
        try {
            $saveData = ['refund_order_ids'=>implode(',',$refund_order_ids),'total'=>($rebate_user['total']-1)];
            $old_status = $rebate_user['status'];
            if($old_status==1){
                $saveData['status'] = 0;
            }
            (new ShopRebateUser())->where(['id'=>$rebate_user['id']])->save($saveData);
            if($old_status==1&&$rebate_user['is_get_coupon']==1){
                //减少用户优惠券
                $user_coupon = (new CardNewCouponHadpull())->where([['coupon_id','=',$rebate_info['coupon_id']],['uid','=',$shop_order['uid']],['is_use','<>',1]])->select()->toArray();
                if(count($user_coupon)>0){
                    //删除用户优惠券
                    (new CardNewCouponHadpull())->where([['id','=',$user_coupon[(count($user_coupon)-1)]['id']]])->delete();
                    //增加商家优惠券
                    (new CardNewCoupon())->where(['coupon_id'=>$rebate_info['coupon_id']])->inc('had_pull',count($user_coupon))->upDate();
                    (new ShopRebateUser())->where($where)->save(['is_get_coupon'=>0]);
                }
            }
            \think\facade\Db::commit();
            return true;
        }catch (\Exception $e){
            \think\facade\Db::rollBack();
            fdump_api($e->getMessage(),'rebate/refund',true);
            return false;
        }
    }
}