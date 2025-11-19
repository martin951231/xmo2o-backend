<?php

/***                              _
 *  _._ _..._ .-',     _.._(`))
 * '-. `     '  /-._.-'    ',/
 *    )         \            '.
 *   / _    _    |             \
 *  |  a    a    /   pigo2o     |
 *  \   .-.                     ;
 *   '-('' ).-'       ,'       ;
 *      '-;           |      .'
 *         \           \    /
 *         | 7  .__  _.-\   \
 *         | |  |  ``/  /`  /
 *        /,_|  |   /,_/   /
 *           /,_/      '`-'
 */

class Village_groupAction extends BaseAction{
    public function index(){
        if(empty($_GET['begin_time'])){
            $_GET['begin_time'] = date('Y-m-d',strtotime(date('Y-m')));
        }

        if(empty($_GET['end_time'])){
            $_GET['end_time'] = date('Y-m-d');
        }
        if($_GET['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
            $where['d.goods_id'] = $_GET['goods_id'];
        }
        $_GET['keyword'] = htmlspecialchars(trim($_GET['keyword']));
        if($_GET['searchtype'] &&  $_GET['keyword']!=''){
            switch($_GET['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $_GET['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = array('like',"%".$_GET['keyword']."%");
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $_GET['keyword'];
                    break;
            }
        }


        $where['o.status'] =array('in','1,2,3,5,6,7,8,13');
        $where['g.mer_id'] =$this->merchant_session['mer_id'];
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            $begin_time = strtotime($_GET['begin_time']." 00:00:00");
            $end_time = strtotime($_GET['end_time']." 23:59:59");
            if ($begin_time>$end_time) {
                $this->error(L_("结束时间应大于开始时间"));
            }

            $period = array($begin_time,$end_time);
            $where['_string']= $where['_string']?$where['_string']." AND (o.add_time BETWEEN ".$period[0].' AND '.$period[1].")":"(o.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
        }
        $where['date_num']= '1';
        $where['is_delay']= '0';
        $count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count,20);

        $start_list = M('Village_group_order_detail')->field('d.village_group_type,d.name,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,o.total_money as all_price,d.goods_id,d.price,d.purchase_price,d.num,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time')
            ->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
            ->where($where)->order('o.order_id DESC,d.name desc,o.pay_time DESC')->limit($p->firstRow.','.$p->listRows)->select();

        if ($start_list) {
            // 单独查询一下 团长信息
            $m_village_group_header = D('Village_group');

            foreach ($start_list as &$val) {
                $header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid'],true);
                $val['header_info'] = $header_tmp['msg'];
                // 查询一下团购购买数量
                $val['all_num'] = $val['num'];
                $increase_price = $val['increase_price']>0?$val['increase_price']:0;
                $val['all_purchase_price'] = $val['purchase_price']*$val['num'];
            }
        }
        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_status',D('Village_group')->order_status);
        $this->assign('start_list',$start_list);
        $this->assign('now_goods',$now_goods);
        $this->assign('pagebar',$p->show());
        $this->display();
    }
    public function join_order_detail(){

        $order =D('Village_group')->get_order_detail($_GET['order_id']);
        $user = D('User')->get_user($order['uid']);
        $this->assign('user',$user);
        $this->assign('order',$order);

        $this->assign('order_detail',$order['goods_detail']);

        // 处理周期购信息获取
        if ($order['village_group_type'] && $order['village_group_type']==1){
            $cycle_order_detail=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id']))->select();
            $goods_all_log = array();
            if ($cycle_order_detail) {
                foreach ($cycle_order_detail as $val) {
                    $goods_all_log[$val['id']] = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>$val['goods_id'],'date_num'=>$val['date_num']))->select();

                    $status_arr = array();
                    foreach ($goods_all_log[$val['id']] as $key => $_log) {
                        if (!in_array($_log['deliver_status'], $status_arr)) {
                            $status_arr[] = $_log['deliver_status'];
                        }else{
                            unset($goods_all_log[$val['id']][$key]);
                        }
                    }
                }
            }
            $cycle_order_count=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id'],'status'=>array('in', array(1,2,3,4,5))))->count();
            $this->assign('goods_all_log',$goods_all_log);
            $this->assign('cycle_order_count',$cycle_order_count);
            $this->assign('cycle_order_detail',$cycle_order_detail);
        }
        $this->display();
    }


    public function send_list(){

        if(empty($_GET['begin_time'])){
            $_GET['begin_time'] = '';
        }

        if(empty($_GET['end_time'])){
            $_GET['end_time'] = '';
        }
        if($_GET['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
            $where['d.goods_id'] = $_GET['goods_id'];
        }

        if($_GET['searchtype'] &&  $_GET['keyword']!=''){
            switch($_GET['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $_GET['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $_GET['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $_GET['keyword'];
                    break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$_GET['keyword']}%"),'p.status'=>1,'h.status'=>1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid']  = array('in',$start_uid);

                    break;
            }
        }

        // 处理省市区
        // if ($_GET['province_idss'] || $_GET['city_idss'] || $_GET['area_id']) {
        //     $where_header = array(
        //         'header_uid' => array('gt',0)
        //     );
        //     if ($_GET['province_id']) {
        //         $where_header['province_id'] = $_GET['province_id'];
        //     }
        //     if ($_GET['city']) {
        //         $where_header['city_id'] = $_GET['city'];
        //     }
        //     if ($_GET['area_id']) {
        //         $where_header['area_id'] = $_GET['area_id'];
        //     }
        //     $pick_address = M('Village_group_pick_address')->where($where_header)->select();
        //     $header_arr = array();
        //     foreach ($pick_address as $val) {
        //         $header_arr[]=$val['header_uid'];
        //     }
        //     $where['start_uid'] = array('in', $header_arr);
        // }

//        $where['d.status'] = 0;
        $where['o.village_group_type'] = array('neq',2);
        $where['o.status'] =array('in','1,2,3,5,6,7,8');
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])&& !empty($_GET['searchtimetype'])){
            $begin_time = strtotime($_GET['begin_time'].":00");
            $end_time = strtotime($_GET['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($_GET['searchtimetype'] == 'pay_time'){
                $where['o.'.$_GET['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$_GET['searchtimetype']] = ['between', $period];
            }
            // $where['_string']= "(g.village_group_type=1 AND (d.send_time  BETWEEN ".$period[0].' AND '.$period[1].") AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND ((g.send_time BETWEEN ".$period[0].' AND '.$period[1].") OR (g.send_time=0 AND o.add_time BETWEEN ".($period[0]-86400).' AND '.($period[1]-86400).")))";
        }
        $where['d.is_merchant_send'] = 1;
        $where['_string'] = 'd.date_num = o.newest_delivery_periods AND d.is_delay=0';
        $where['d.mer_id'] = $this->merchant_session['mer_id'];
        $count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,20);

        $start_list = M('Village_group_order_detail')->field('o.delivery_periods,o.newest_delivery_periods,o.pick_addr,d.increase_price,d.area_price,d.village_group_type,d.name,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,o.note,d.goods_id,d.price,d.purchase_price,d.num,g.mer_id,m.name as merchant_name,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time,d.arrive_cutting_time,d.arrive_time')
                ->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON d.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
                ->where($where)->order('o.order_id DESC,d.name desc,d.send_time DESC')->limit($p->firstRow.','.$p->listRows)->select();

        if ($start_list) {
            // 单独查询一下 团长信息
            $m_village_group_header = D('Village_group');

            foreach ($start_list as &$val) {
                $header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid'],true);
                $val['header_info'] = $header_tmp['msg'];
                // 查询一下团购购买数量
                $val['all_num'] = $val['num'];
                $increase_price = $val['increase_price']>0?$val['increase_price']:0;
                if ($val['area_price']>0) {
                    $val['all_price'] = ($val['area_price']+$increase_price)*$val['num'];
                } else {
                    $val['all_price'] = ($val['price']+$increase_price)*$val['num'];
                }
                $val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                if ($val['delivery_periods']>1) {
                    $val['all_price'] = round_number($val['all_price']/$val['delivery_periods'],2);
                    $val['all_purchase_price'] = round_number($val['all_purchase_price']/$val['delivery_periods'],2);
                }

            }
        }

        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_status',D('Village_group')->order_status);
        $this->assign('start_list',$start_list);
        $this->assign('now_goods',$now_goods);
        $this->assign('pagebar',$p->show());


        $this->display();
    }

    public function send_export()
    {
        $_POST['is_merchant_send'] = 1;
        $_POST['mer_id'] = $this->merchant_session['mer_id'];
        
        $param = $_POST;
        $param['type'] = 'village_group_send';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['system_session']['area_id'] = $this->system_session['area_id'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => L_('操作导出计划成功'), 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => L_('导出失败')));
        }
    }

    //发货改变订单状态
    public function send(){
        if($_POST['send_all']==1){
            $order_arr =  explode(',',$_POST['order_id']);
            $where['order_id'] = array('in',$order_arr);
        }else{
            $order_arr[] =$_GET['order_id'];
            $where['order_id'] = $_GET['order_id'];
        }

        if($_GET['goods_id']){
            $where['goods_id'] = $_GET['goods_id'];
            $order_id = $_GET['order_id'];
            $info_order = M('Village_group_order')->where(array('order_id' =>$order_id))->find();
            if ($info_order['newest_delivery_periods']) {
                $date_num=$where['date_num'] = $info_order['newest_delivery_periods'];
                $where['is_delay'] = 0;
            } else {
                $date_num=$where['date_num'] = 1;
                $where['is_delay'] = 0;
            }
            if(M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save()) {
                foreach ($order_arr as $value) {
                    $where['order_id'] = $value;
                    if (M('Village_group_order_detail')->where(array('order_id' => $value, 'status' => 0))->find()) {
                        $order_status = 7;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    } else {
                        $order_status = 5;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    }

                    $param['goods_id'] = $_GET['goods_id'];
                    $param['from_type'] = 3;
                    $param['deliver_status'] = 1;
                    $param['note'] = L_('商家管理员点击商品发货');
                    $param['name'] = $this->system_session['account'];
                    $param['date_num'] = $date_num;
                    $param['order_id'] = $value;
                    D('Village_group')->add_log($param);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = $order_status;
                    D('Village_group')->add_log($param);
                }

                $this->success(L_('商品发货状态改变成功'));

            }else{
                $this->error(L_('商品发货状态改变失败'));
            }
        }else{
            $order = M('Village_group_order')->where($where)->where(array('status'=>1))->select();
            $order_detail = M('Village_group_order_detail')->where($where)->where(array('status'=>0))->select();
            if($order && $order_detail){
                $order_info_arr = M('Village_group_order_detail')->where($where)->where(array('status'=>0))->select();
                $new_order_id = array();
                $cycle_order_id = array();
                $order_info = array();
                foreach ($order_info_arr as $val) {
                    if ($val && $val['village_group_type']==1) {
                        $cycle_order_id[]=$val['order_id'];
                        $order_info[$val['order_id']]=$val;
                    } else {
                        $new_order_id[]=$val['order_id'];
                    }
                }
                if ($cycle_order_id) {
                    $where['order_id'] = array('in',$new_order_id);
                }
                if(M('Village_group_order')->where($where)->where(array('status'=>1))->setField('status',5)) {
                    foreach ($order_arr as $item) {
                        $param = array();
                        $where['order_id'] = $item;
                        M('Village_group_order_detail')->where($where)->where(array('status'=>0))->setField('status', 1);
                        $param['order_id'] = $item;
                        $param['from_type'] = 3;
                        $param['status'] = 5;
                        $param['note'] = L_('商家管理员点击订单发货');
                        $param['name'] = $this->system_session['account'];
                        D('Village_group')->add_log($param);
                        unset($param['status']);
                        $param['note'] = L_('商家管理员点击订单发货，批量更新订单商品发货');
                        $param['deliver_status'] = 1;
                        D('Village_group')->add_log($param);
                    }
                    // 处理下周期购
                    if ($cycle_order_id) {
                        foreach ($cycle_order_id as $val) {
                            $newest_delivery_periods = $order_info[$val]['newest_delivery_periods'];
                            if ($newest_delivery_periods) {
                                $date_num = $where['date_num'] = $newest_delivery_periods;
                            } else {
                                $date_num = $where['date_num'] = 1;
                            }
                            $where['order_id'] = $val;
                            $goods_id = M('Village_group_order_detail')->where($where)->getField('goods_id');
                            M('Village_group_order_detail')->where($where)->where(array('status'=>0))->data(array('status'=>1,'real_send_time' => time()))->save();
                            $param['order_id'] = $val;
                            $param['goods_id'] = $goods_id;
                            $param['from_type'] = 3;
                            $param['deliver_status'] = 1;
                            $param['note'] = L_('商家管理员点击订单发货');
                            $param['name'] = $this->system_session['account'];
                            $param['date_num'] = $date_num;
                            D('Village_group')->add_log($param);
                            if ($date_num>=$order_info[$val]['delivery_periods']) {
                                M('Village_group_order')->where(array('order_id' => $val))->where(array('status'=>1))->setField('status',5);
                            }
                        }
                    }
                    $this->success(L_('订单发货状态改变成功'));
                }else{

                    $this->error(L_('订单发货状态改变失败'));
                }
            }

        }

    }

    /**
     * 根据条件查询全部的发货信息
     */
    public function getSendList($param)
    {
        if($this->merchant_session['mer_id']){
            $where['d.mer_id'] = $this->merchant_session['mer_id'];
        }else{
            return [];
        }

        if(empty($param['begin_time'])){
            $param['begin_time'] = '';
        }

        if(empty($param['end_time'])){
            $param['end_time'] = '';
        }
        if($param['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id'=>$param['goods_id']))->find();
            $where['d.goods_id'] = $param['goods_id'];
        }

        $start_uid = array();
        if($param['searchtype'] &&  $param['keyword']!=''){
            switch($param['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $param['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $param['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $param['keyword'];
                    break;
                case 'mer_id':
                    $where['g.mer_id'] = $param['keyword'];
                    break;
                case 'mer_name':
                    $where['m.name'] = ['like', '%'.$param['keyword'].'%'];
                    break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$param['keyword']}%"),'p.status'=>1,'h.status'=>1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid']  = array('in',$start_uid);

                    break;
            }
        }

        $where_header = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
//                die('区域管理员无法管理用户');
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where_header[$area_index] = $this->system_session['area_id'];
        }
        // 处理省市区
        if ($param['province_idss'] || $param['city_idss'] || $param['area_id'] || !empty($where_header)) {
            $where_header['header_uid'] = array('gt',0);
            if ($param['province_idss']) {
                $where_header['province_id'] = $param['province_idss'];
            }
            if ($param['city_idss']) {
                $where_header['city_id'] = $param['city_idss'];
            }
            if ($param['area_id']) {
                $where_header['area_id'] = $param['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid) {
                // $header_arr = array_merge($header_arr, $start_uid);
                $header_arr = array_intersect($header_arr,$start_uid); // 不是合并条件而是查询比对出同样的团长id
                $header_arr = array_values($header_arr); // 重新排序
            }
            $where['o.start_uid'] = array('in', $header_arr);
        }

        $where['o.village_group_type'] = array('neq',2);
//        $where['o.status'] =array('in','1,2,3,5,6,7,8');
        if(!empty($param['begin_time'])&&!empty($param['end_time'])&& !empty($param['searchtimetype'])){
            $begin_time = strtotime($param['begin_time'].":00");
            $end_time = strtotime($param['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($param['searchtimetype'] == 'pay_time'){
                $where['o.'.$param['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$param['searchtimetype']] = ['between', $period];
            }
        }
        $where['d.is_merchant_send'] = 1;
        $where['_string'] = 'd.date_num = o.newest_delivery_periods';

        if($param['type'] == 'send_selected'){
            $where['d.status'] = 0;
            $where['o.status'] = [['lt',10],['egt',1],'and'];
            $where['d.is_delay'] = ['eq',0];
        }
        if($param['type'] == 'get_selected'){
            $where['d.status'] = 1;
            $where['d.is_delay'] = ['eq',0];
        }

        $start_list = M('Village_group_order_detail')->field('d.goods_id,o.order_id')
            ->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
            ->where($where)->order('o.order_id DESC,d.name desc,d.send_time DESC')->select();
        foreach ($start_list as &$v){
            $v = $v['order_id'].'='.$v['goods_id'];
        }
        return $start_list;
    }

    //选择性发货
    public function send_selected(){
        if($_POST['send_list'] || $_POST['checkAll']==1){
            $checkAll = $_POST['checkAll'];
            if($checkAll==1){//全部发货
                $param = $_POST;
                $param['type'] = 'send_selected';
                $send_list = $this->getSendList($param);
            }else{
                $send_list = explode(',', $_POST['send_list']);
            }
            foreach ($send_list as $value) {
                $temp = explode('=', $value);
                $where['goods_id'] = $temp[1];
                $where['order_id'] = $temp[0];
                $order_id = $temp[0];
                $info_order = M('Village_group_order')->where(array('order_id' =>$order_id))->find();
                if($info_order['status'] == 11 || $info_order['status'] == 12){
                    $this->error('订单已取消，无需发货');
                }

                if ($info_order['newest_delivery_periods']) {
                    $date_num=$where['date_num'] = $info_order['newest_delivery_periods'];
                    $where['is_delay'] = 0;
                } else {
                    $date_num=$where['date_num'] = 1;
                    $where['is_delay'] = 0;
                }
                if(M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save()) {
                    $where['order_id'] = $order_id;
                    if (M('Village_group_order_detail')->where(array('order_id' => $order_id, 'status' => 0))->find()) {
                        $order_status = 7;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    } else {
                        $order_status = 5;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    }

                    $param['goods_id'] = $temp[1];
                    $param['from_type'] = 1;
                    $param['deliver_status'] = 1;
                    $param['note'] = '系统管理员点击商品发货';
                    $param['name'] = $this->system_session['account'];
                    $param['order_id'] = $order_id;
                    $param['date_num'] = $date_num;
//                    D('Village_group')->add_log($param);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = $order_status;
                    D('Village_group')->add_log($param);
                    D("System_log")->add_log("发货了".C('config.village_group_alias')."订单，订单ID：".$temp[0]."，商品ID".$temp[1],C('config.village_group_alias'));
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'village_group';
                    $params['order_id'] = $order_id;
                    $params['title'] = '商品已发货';
                    $params['content'] = '您的商品已经发货，商品将尽快为你送达';
                    $params['uid'] = $info_order['uid'];
                    invoke_v20_service($url, [$params]);

                }
                D('System_order')->save_order_status('village_group',$temp[0],1);//发货
            }
            $this->success('商品发货状态改变成功');
        }
        else{
            $this->error('订单发货状态改变失败');
        }
    }

    //选择性到货
    public function get_selected(){
        if($_POST['send_list'] || $_POST['checkAll']==1){
            $checkAll = $_POST['checkAll'];
            if($checkAll==1){//全部发货
                $param = $_POST;
                $param['type'] = 'get_selected';
                $send_list = $this->getSendList($param);
            }else{
                $send_list = explode(',', $_POST['send_list']);
            }
            foreach ($send_list as $value) {
                $temp = explode('=', $value);
                $where['goods_id'] = $temp[1];
                $order_id = $temp[0];
                $where['order_id'] = $order_id;
                $order = M('Village_group_order')->where(array('order_id' => $order_id))->find();
                if ($order['village_group_type']==1) {
                    $date_num=$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
                    $where['date_num']=$date_num;
                    $where['is_delay']=0;
                } else {
                    $date_num=1;
                }
                $order_detail_info = M('Village_group_order_detail')->where($where)->find();
                if($order_detail_info['status']>3){
                    continue;
                }
                if(M('Village_group_order_detail')->where($where)->setField('status',2)) {
                    $where['order_id'] = $order_id;
                    if (M('Village_group_order_detail')->where(array('order_id' => $order_id, 'status' => array('lt',2)))->find()) {
                        $order_status = 8;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    } else {
                        $order_status = 6;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    }

                    $param['goods_id'] = $temp[1];
                    $param['from_type'] = 1;
                    $param['deliver_status'] = 2;
                    $param['note'] = '系统管理员点击商品收货';
                    $param['name'] = $this->system_session['account'];

                    $param['date_num'] = $date_num;
                    $param['order_id'] = $order_id;
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'village_group';
                    $params['order_id'] = $order_id;
                    $params['title'] = '商品已经到达自提点';
                    $params['content'] = '您的商品已经到达团长自提点，请前往团长处领取商品。';
                    invoke_v20_service($url, [$params]);
                    D('Village_group')->add_log($param);
                    D("System_order")->save_order_status('village_group', $order_id, 1);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = $order_status;
                    D('Village_group')->add_log($param);


                }
            }
            $this->success('商品收货状态改变成功');
        }
        else{
            $this->error('商品收货状态改变失败');
        }
    }

    public function get_order(){
        if($_POST['get_all']==1){
            $order_arr =  explode(',',$_POST['order_id']);
            $where['order_id'] = array('in',$order_arr);
        }else{
            $order_arr[] =$_GET['order_id'];
            $where['order_id'] = $_GET['order_id'];
        }
        if($_GET['goods_id']){ // 单个商品团长收货
            $where['goods_id'] = $_GET['goods_id'];
            if (empty($_GET['order_id'])) {
                $order_id = M('Village_group_order_detail')->where(array('goods_id' => $_GET['goods_id']))->getField('order_id');
            } else {
                $order_id = $_GET['order_id'];
            }
            $order = M('Village_group_order')->where(array('order_id' => $order_id))->find();
            if ($order['village_group_type']==1) {
                $date_num=$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
                $where['date_num']=$date_num;
                $where['is_delay']=0;
            } else {
                $date_num=1;
            }
            if(M('Village_group_order_detail')->where($where)->setField('status',2)) {
                foreach ($order_arr as $value) {
                    $where['order_id'] = $value;
                    if (M('Village_group_order_detail')->where(array('order_id' => $value, 'status' => array('lt',2)))->find()) {
                        $order_status = 8;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    } else {
                        $order_status = 6;
                        $res = M('Village_group_order')->where($where)->setField('status', $order_status);
                    }

                    $param['goods_id'] = $_GET['goods_id'];
                    $param['from_type'] = 3;
                    $param['deliver_status'] = 2;
                    $param['note'] = L_('商家管理员点击商品收货');
                    $param['name'] = $this->system_session['account'];

                    $param['date_num'] = $date_num;
                    $param['order_id'] = $value;
                    D('Village_group')->add_log($param);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = $order_status;
                    D('Village_group')->add_log($param);
                }

                $this->success(L_('商品收货状态改变成功'));

            }else{
                $this->error(L_('商品收货状态改变失败'));
            }
        }else{
            $order_info_arr = M('Village_group_order_detail')->where($where)->select();
            $new_order_id = array();
            $cycle_order_id = array();
            $order_info = array();
            foreach ($order_info_arr as $val) {
                if ($val && $val['village_group_type']==1) {
                    $cycle_order_id[]=$val['order_id'];
                    $order_info[$val['order_id']]=$val;
                } else {
                    $new_order_id[]=$val['order_id'];
                }
            }
            if ($cycle_order_id) {
                $where['order_id'] = array('in',$new_order_id);
            }
            if(M('Village_group_order')->where($where)->setField('status',6)) {
                foreach ($order_arr as $item) {
                    $param = array();
                    $where['order_id'] = $item;
                    M('Village_group_order_detail')->where($where)->setField('status',2);
                    $param['order_id'] = $item;
                    $param['from_type'] = 3;
                    $param['status'] = 6;
                    $param['note'] = L_('商家管理员点击订单批量收货');
                    $param['name'] = $this->system_session['account'];
                    D('Village_group')->add_log($param);
                    unset($param['status']);
                    $param['note'] = L_('商家管理员点击订单收货，批量更新订单商品收货');
                    $param['deliver_status'] = 2;
                    D('Village_group')->add_log($param);
                }
                // 处理下周期购
                if ($cycle_order_id) {
                    foreach ($cycle_order_id as $val) {
                        $newest_delivery_periods = $order_info[$val]['newest_delivery_periods'];
                        if ($newest_delivery_periods) {
                            $date_num = $where['date_num'] = $newest_delivery_periods;
                        } else {
                            $date_num = $where['date_num'] = 1;
                        }
                        $where['order_id'] = $val;
                        $goods_id = M('Village_group_order_detail')->where($where)->getField('goods_id');
                        M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save();
                        $param['order_id'] = $val;
                        $param['goods_id'] = $goods_id;
                        $param['from_type'] = 1;
                        $param['deliver_status'] = 2;
                        $param['note'] = L_('商家管理员点击商品收货');
                        $param['name'] = $this->system_session['account'];
                        $param['date_num'] = $date_num;
                        D('Village_group')->add_log($param);
                        if ($date_num>=$order_info[$val]['delivery_periods']) {
                            M('Village_group_order')->where(array('order_id' => $val))->setField('status',5);
                        }
                    }
                }
                $this->success(L_('订单收货状态改变成功'));
            }else{
                $this->error(L_('订单收货状态改变失败'));
            }
        }

    }

    public function pre_print(){
        if(empty($_GET['begin_time'])){
            $_GET['begin_time'] = date('Y-m-d H:i');
        }

        if(empty($_GET['end_time'])){
            $_GET['end_time'] = date('Y-m-d H:i');
        }
        if($_GET['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
            $where['d.goods_id'] = $_GET['goods_id'];
        }

        if($_GET['searchtype'] &&  $_GET['keyword']!=''){
            switch($_GET['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $_GET['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $_GET['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $_GET['keyword'];
                    break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$_GET['village_name']}%"),'p.status'=>1,'h.status'=>1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid']  = array('in',$start_uid);

                    break;
            }
        }
        // 处理省市区
        if ($_GET['province_id']>0 || $_GET['city_id']>0 || $_GET['area_id']>0) {
            $where_header = array(
                'header_uid' => array('gt',0)
            );
            if ($_GET['province_idss']) {
                $where_header['province_id'] = $_GET['province_idss'];
            }
            if ($_GET['city_idss']) {
                $where_header['city_id'] = $_GET['city_idss'];
            }
            if ($_GET['area_id']) {
                $where_header['area_id'] = $_GET['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid) {
                $header_arr = array_merge($header_arr, $start_uid);
            }
            $where['o.start_uid'] = array('in', $header_arr);
        }

        $where['d.status'] = 0;
        $where['o.status'] =array('in','1,2,3,5,6,7,8');
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])&& !empty($_GET['searchtimetype'])){
            $begin_time = strtotime($_GET['begin_time'].":00");
            $end_time = strtotime($_GET['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($_GET['searchtimetype'] == 'pay_time'){
                $where['o.'.$_GET['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$_GET['searchtimetype']] = ['between', $period];
            }
            // $where['_string']= "(g.village_group_type=1 AND (d.send_time  BETWEEN ".$period[0].' AND '.$period[1].") AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND ((g.send_time BETWEEN ".$period[0].' AND '.$period[1].") OR (g.send_time=0 AND o.add_time BETWEEN ".($period[0]-86400).' AND '.($period[1]-86400).")))";
        }
        $where['d.is_merchant_send'] = 1;
        $where['g.mer_id'] = $this->merchant_session['mer_id'];
        $count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,20);

        $start_list = M('Village_group_order_detail')->field('o.delivery_periods,o.newest_delivery_periods,o.pick_addr,o.pick_name,o.pick_phone,d.increase_price,d.area_price,d.village_group_type,d.name,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,g.mer_id,m.name as merchant_name,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time,d.arrive_cutting_time,d.arrive_time')
                ->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
                ->where($where)->order('o.order_id DESC,d.name desc')->limit($p->firstRow.','.$p->listRows)->select();
        if ($start_list) {
            // 单独查询一下 团长信息
            $m_village_group_header = D('Village_group');

            foreach ($start_list as &$val) {
                $header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid'],true);
                $val['header_info'] = $header_tmp['msg'];
                // 查询一下团购购买数量
                $val['all_num'] = $val['num'];
                $increase_price = $val['increase_price']>0?$val['increase_price']:0;
                if ($val['area_price']>0) {
                    $val['all_price'] = ($val['area_price']+$increase_price)*$val['num'];
                } else {
                    $val['all_price'] = ($val['price']+$increase_price)*$val['num'];
                }
                $val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                if ($val['delivery_periods']>1) {
                    $val['all_price'] = round_number($val['all_price']/$val['delivery_periods'],2);
                    $val['all_purchase_price'] = round_number($val['all_purchase_price']/$val['delivery_periods'],2);
                }
                $arr[$val['order_id']][] = $val;
            }
        }

        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_status',D('Village_group')->order_status);
        $this->assign('start_list',$arr);
        $this->assign('now_goods',$now_goods);
        $this->assign('pagebar',$p->show());
        $this->display();
    }
}
