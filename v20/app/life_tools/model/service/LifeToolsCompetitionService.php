<?php


namespace app\life_tools\model\service;


use app\common\model\db\Area;
use app\common\model\service\AreaService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\CustomService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\order\SystemOrderService;
use app\life_tools\model\db\LifeToolsCompetition;
use app\life_tools\model\db\LifeToolsCompetitionAudit;
use app\life_tools\model\db\LifeToolsCompetitionAuditAdmin;
use app\life_tools\model\db\LifeToolsCompetitionJoinOrder;
use app\pay\model\service\PayService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
class LifeToolsCompetitionService
{
    /**
     * 赛事列表
     */
    public function getList($param)
    {
        $where = [['is_del', '=', 0]];
        if (!empty($param['title'])) {
            array_push($where, ['title', 'like', '%' . $param['title'] . '%']);
        }

        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            if($param['start_time']!=$param['end_time']){
                array_push($where, ['start_time', '>', strtotime($param['start_time'])]);
                array_push($where, ['end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }else{
                array_push($where, ['start_time', '>', strtotime($param['start_time']." 00:00:00")]);
                array_push($where, ['end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }
        }

        $list['list']=(new LifeToolsCompetition())->getList($where);
        $list['total']=(new LifeToolsCompetition())->getCount($where);
        return $list;
    }


    /**
     * 赛事列表
     */
    public function getLimitList($param)
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $where = [['is_del', '=', 0]];
        if (!empty($param['title'])) {
            array_push($where, ['title', 'like', '%' . $param['title'] . '%']);
        }

        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            if($param['start_time']!=$param['end_time']){
                array_push($where, ['start_time', '>', strtotime($param['start_time'])]);
                array_push($where, ['end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }else{
                array_push($where, ['start_time', '>', strtotime($param['start_time']." 00:00:00")]);
                array_push($where, ['end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }
        }

        // 状态
        if(isset($param['status'])){
            array_push($where, ['status', '=', $param['status']]);
        }

        // 排除id
        if(isset($param['competition_ids_not']) && $param['competition_ids_not']){
            array_push($where, ['competition_id', 'not in', $param['competition_ids_not']]);
        }

        $list['data']=(new LifeToolsCompetition())->getList($where, true, 'competition_id desc', $page, $pageSize);
        $list['total']=(new LifeToolsCompetition())->getCount($where);
        return $list;
    }

    /**
     * 赛事活动-我的赛事列表
     */
    public function myCompetList($param)
    {
        $where=[['j.uid','=',$param['uid']],['j.status','=',1]];
        $list=(new LifeToolsCompetitionJoinOrder())->myCompetList($where,'c.start_time,c.end_time,c.competition_id,c.title,c.city_id,c.address,j.name,j.phone,j.price,j.status,j.pigcms_id as order_id','j.pigcms_id asc',$param['page'],$param['pageSize']);
        if(!empty($list['data'])){
           foreach ($list['data'] as $k=>$v){
               $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$v['city_id']]);
               $list['data'][$k]['address']=$city_name.$v['address'];
               $list['data'][$k]['start_time']=date("Y.m.d H:i:s",$v['start_time']);
               if(time()<$v['start_time']){//未开始
                   $list['data'][$k]['compet_status']=1;
                   $list['data'][$k]['compet_status_txt']="未开始";
                   $list['data'][$k]['compet_text_color']="#1A86F9";
               }elseif ($v['start_time']<=time() && $v['end_time']>time()){//进行中
                   $list['data'][$k]['compet_status']=2;
                   $list['data'][$k]['compet_status_txt']="正在进行中";
                   $list['data'][$k]['compet_text_color']="#19C9AE";
               }else{
                   $list['data'][$k]['compet_status']=3;
                   $list['data'][$k]['compet_status_txt']="已结束";
                   $list['data'][$k]['compet_text_color']="#999999";
               }
           }
        }
        return $list;
    }
    /**
     * @return \json
     * 赛事活动-我的报名详情
     */
    public function orderDetail($param)
    {
        $where=[['j.pigcms_id','=',$param['pigcms_id']],['j.status','=',1]];
        $list=(new LifeToolsCompetitionJoinOrder())->orderDetail($where,'c.title,c.start_time,c.end_time,c.competition_id,c.province_id,c.city_id,c.area_id,c.long,c.lat,c.address,c.phone,j.price,j.status,j.pigcms_id as order_id,j.name,j.phone as tel_phone,j.audit_status,c.is_audit');
        if(!empty($list)){
            if(time()<$list['start_time']){//未开始
                $list['compet_status']=1;
                $list['compet_status_txt']="未开始";
                $list['compet_text_color']="#1A86F9";
            }elseif ($list['start_time']<=time() && $list['end_time']>time()){//进行中
                $list['compet_status']=2;
                $list['compet_status_txt']="正在进行中";
                $list['compet_text_color']="#19C9AE";
            }else{
                $list['compet_status']=3;
                $list['compet_status_txt']="已结束";
                $list['compet_text_color']="#999999";
            }
            $list['start_time']=date("Y.m.d H:i",$list['start_time']);
            $list['end_time']=date("Y.m.d H:i",$list['end_time']);
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['province_id']]);
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['city_id']]);
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['area_id']]);
            $list['address']=$pro_name.$city_name.$area_name.$list['address'];
        }
        $list['audit_list'] = (new LifeToolsCompetitionAudit)->field(['name','status', 'remark', 'audit_time'])->where('order_id', $list['order_id'])->order('sort DESC')->select();
        if($list['is_audit']){
            $auditStatusMap = ['待审核', '审核中', '报名成功', '审核不通过'];
            $list['audit_status_text'] = $auditStatusMap[$list['audit_status']] ?? '';
        }else{
            $auditStatusMap = ['待支付', '报名成功', '报名失败'];
            $list['audit_status_text'] = $auditStatusMap[$list['status']];
        }
        return $list;
    }
    /**
     * 用户端赛事列表
     */
    public function competitionList($param)
    {
        $time = time();
        $order='competition_id desc';
        if($param['type']==1){
            $order='limit_num desc';
        }
        $where = [['status', '=', 1], ['is_del', '=', 0]];
        $where[] = [Db::raw('`start_time`-`send_notice_days`*86400'), '<', $time];
        $field = 'competition_id,image_big,start_time,end_time';
        $list=(new LifeToolsCompetition())->competitionList($where, $field,$order,$param['page'],$param['pageSize']);
        if(!empty($list['data'])){
           foreach ($list['data'] as $key=>$value){
               $list['data'][$key]['image_big']=empty($value['image_big'])?'':replace_file_domain($value['image_big']);
                if($time < $value['start_time']){//未开始
                    $list['data'][$key]['status_txt'] = '未开始';
                    $list['data'][$key]['status_txt_color'] = '#f26331';
                }elseif ($value['start_time'] <= $time && $value['end_time'] > $time){//
                    $list['data'][$key]['status_txt'] = '进行中';
                    $list['data'][$key]['status_txt_color'] = '#18be41';
                }else{
                    $list['data'][$key]['status_txt'] = '已结束';
                    $list['data'][$key]['status_txt_color'] = '#9a9999';
                }
           }
        }
        return $list;
    }

    /**
     * 赛事详情
     */
    public function competDetail($param)
    {
        $where=[['competition_id','=',$param['competition_id']]];
        $detail=(new LifeToolsCompetition())->getToolCompetitionMsg($where);
        if(!empty($detail)){
            if($detail['start_time']<time() && $detail['end_time']>time()){
                $detail['act_status']=1;//进行中
            }elseif($detail['end_time']<time()){
                $detail['act_status']=2;//已经结束
            }elseif($detail['start_time']>time()) {
                $detail['act_status']=0;//未开始
            }
            if(!empty($detail) && $detail['limit_type']==1 && ($detail['limit_num']<=$detail['join_num'])){
                $detail['act_status']=3;
            }
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['province_id']]);
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['city_id']]);
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['area_id']]);
            $detail['address']=$pro_name.$city_name.$area_name.$detail['address'];
            $detail['content']=empty($detail['content'])?"":replace_file_domain_content_img(htmlspecialchars_decode($detail['content']));//规则
            $detail['countdown']=$detail['start_time']-time()<0?0:$detail['start_time']-time();
            $detail['image_big']=empty($detail['image_big'])?[]:[['image'=>replace_file_domain($detail['image_big'])]];
            $detail['image_small']=empty($detail['image_small'])?"":replace_file_domain($detail['image_small']);
            $detail['start_time']=empty($detail['start_time'])?"":date('Y-m-d H:i',$detail['start_time']);
            $detail['end_time']=empty($detail['end_time'])?"":date('Y-m-d H:i',$detail['end_time']);

        }
        if($detail['limit_type'] == 0){
            $detail['limit_num'] = 0;
        }
        $detail['custom_form'] = (new CustomService)->formatOfUser($detail['custom_form']);
        return $detail;
    }

    /**
     * 赛事活动-报名提交订单
     */
    public function saveOrder($param)
    {
            // Db::startTrans();
            // if (!preg_match('/^[0-9]{11}$/', $param['phone'])) {
            //     $res = ['status' => 0, 'msg' => L_('请输入有效的手机号')];
            //     return $res;
            // }
            $lifeToolsCompetitionJoinOrder = new LifeToolsCompetitionJoinOrder();
            $where = [['competition_id', '=', $param['competition_id']], ['status', '=', 1], ['is_del', '=', 0]];
            $detail = (new LifeToolsCompetition())->getToolCompetitionMsg($where);
            if (empty($detail)) {
                throw new \think\Exception('此赛事状态异常');
            }
            
            if($detail['end_time'] <= time()){
                throw new \think\Exception('赛事活动已结束');
            }

            if($detail['is_custom'] && !empty($detail['custom_form'])){
                // $is_pay_order=$lifeToolsCompetitionJoinOrder->getOneDetail(['custom_form'=>json_encode($param['custom_form'])]);
                // if(!empty($is_pay_order)){
                //     throw new \think\Exception('您已经报名过该活动了,不可重复报名!');
                // }
                $data['custom_form'] = (new CustomService())->checkUserCommit($param['custom_form']);
            }else{
                $is_pay_order=$lifeToolsCompetitionJoinOrder->getOneDetail(['competition_id'=>$param['competition_id'],'phone'=>$param['phone'],'paid'=>1,'status'=>1]);
                if(!empty($is_pay_order)){
                    throw new \think\Exception('您已经报名过该活动了,不可重复报名!');
                }
            }
            // if($detail['start_time']<time() && $detail['end_time']>time()){
            //     $res = ['status' => 0, 'msg' => L_('已经开始的赛事活动不可报名')];
            //     return $res;
            // }
           
            $data['phone'] = $param['phone'];
            $data['name'] = $param['name'];
            $data['competition_id'] = $detail['competition_id'];
            $data['price'] = $detail['price'];
            $data['paid'] = 0;
            $data['status'] = 0;
            $data['is_del'] = 0;
            $data['uid'] = $param['uid'];
            $data['real_orderid'] = (new PayService())->createOrderNo();
            $data['add_time'] = time();
            if($detail['price'] > 0 && $param['coupon_id']){
                $step5=0;
                $SystemCouponService = new SystemCouponService;
                $current_sys_coupons = $SystemCouponService->formatDiscount($SystemCouponService->getAvailableCoupon($param['uid'], ['can_coupon_money'=>$detail['price'], 'business' => 'life_tools_competition_join'], true));
                if($current_sys_coupons){
                    foreach ($current_sys_coupons as $sys_coupon) {
                        if($param['coupon_id'] == $sys_coupon['id']){//已选择的
                            $step5 = $sys_coupon['discount_money'];
                            $data['coupon_id']=$sys_coupon['id'];
                        }
                    }
                }
                if($step5){
                    //$data['pay_money']=get_number_format($data['price']-$step5);
                    $data['coupon_price']=$step5;
                }
            }
            $ret_order_id = $lifeToolsCompetitionJoinOrder->add($data);
            if (!$ret_order_id) {
                // Db::rollback();
                $res = ['status' => 0, 'msg' => L_('报名失败')];
            } else {
                $where1=[['j.competition_id','=',$param['competition_id']],['j.pigcms_id','=',$ret_order_id]];
                $ret = $this->addSystemOrder($where1, 'j.*');

                $lifeToolsCompetitionAuditAdmin = new LifeToolsCompetitionAuditAdmin();
                $lifeToolsCompetitionAudit = new LifeToolsCompetitionAudit();

                $auditAdminList = $lifeToolsCompetitionAuditAdmin->where('competition_id', $param['competition_id'])->order('sort DESC')->select();
                $time = time();
                $insertAll = [];
                foreach($auditAdminList as $key => $val){
                    $insert = [];
                    $insert['competition_id'] = $param['competition_id'];
                    $insert['admin_id'] = $val['admin_id'];
                    $insert['name'] = $val['name'];
                    $insert['sort'] = $val['sort'];
                    $insert['uid'] =  $param['uid'];
                    $insert['order_id'] =  $ret_order_id;
                    $insert['nickname'] =  $param['userInfo']['nickname'];
                    $insert['phone'] =  $param['userInfo']['phone'];
                    if($detail['is_custom']){
                        $insert['audit_info'] = $data['custom_form'] ?? '';
                    }else{
                        $insert['audit_info'] = [
                            [
                                'title' =>  '姓名',
                                'show_value'    =>  $param['name']
                            ],
                            [
                                'title' =>  '手机号',
                                'show_value'    =>  $param['phone']
                            ]
                        ];
                    }
                    $insert['status'] = 0;
                    $insert['is_show'] = 0;
                    $insert['add_time'] = $time;
                    $insertAll[] = $insert;
                }
                if($insertAll){
                    $lifeToolsCompetitionAudit->insertAll($insertAll);
                }

                if (!$ret) {
                    // Db::rollback();
                    $res = ['status' => 0, 'msg' => L_('报名失败')];
                }else{
                    //提交事务
                    // Db::commit();
                    $res = ['status' => 1, 'msg' => L_('报名成功'),'order_id'=>$ret_order_id,'order_type'=>'life_tools_competition_join'];
                }
            }
           return $res;
    }

    /**
     * 写入平台总订单
     * @param $tableId int 桌台id
     * @return array
     */
    public function addSystemOrder($where = [],$field)
    {
        if (!$where) return false;

        $nowOrder = (new LifeToolsCompetitionJoinOrder())->orderDetail($where,$field);

        $systemOrderService = new SystemOrderService();
        $business = 'life_tools_competition_join';
        $businessOrderId = $nowOrder['pigcms_id'];
        // system_status  0-待支付 2-完成订单 待评价 3-完成订单 5-取消订单
        $saveData['store_id'] = 0;
        $saveData['real_orderid'] = 0;

        $saveData['price'] = get_number_format($nowOrder['price']-$nowOrder['coupon_price']);
        $saveData['goods_price'] = $nowOrder['price'];
        $saveData['total_price'] = $nowOrder['price'];
        $saveData['mer_id'] = 0;
        $saveData['store_id'] = 0;
        $saveData['num'] = 1;
        // 添加系统总订单
        // 待支付
        $saveData['system_status'] = 0;

        $systemOrderService->saveOrder($business, $businessOrderId, $nowOrder['uid'], $saveData);

        return true;
    }
    /**
     * @return \json
     * 删除赛事
     */
    public function delSport($param){
        $where=[['competition_id','=',$param['competition_id']]];
        $data['is_del']=1;
        $ret=(new LifeToolsCompetition())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 关闭赛事
     */
    public function closeCompetition($param)
    {
        $where=[['competition_id','=',$param['competition_id']]];
        $data['status']=$param['status'];
        $ret=(new LifeToolsCompetition())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 用户报名信息
     */
    public function lookCompetitionUser($param)
    {
        $condition = [];
        $condition[] = ['competition_id', '=', $param['competition_id']];
        $condition[] = ['is_del', '=',0];
        $condition[] = ['paid', '=', 1];
        if(!empty($param['keywords'])){
            switch($param['search_type']){
                case 1: //姓名
                    $condition[] = ['u.nickname', 'like', "%{$param['keywords']}%"];
                    break;
                case 2: //手机号
                    $condition[] = ['u.phone', 'like', "%{$param['keywords']}%"];
                    break;
            }
        }
        if(!is_null($param['status'])){
            if($param['status'] == 1){//报名成功
                $condition[] = ['o.status', '=', $param['status']];
                $condition[] = ['o.audit_status', '=', 2];
            }else if($param['status'] == 10){ //已支付
                $condition[] = ['o.paid', '=', 1];
                $condition[] = ['o.audit_status', 'in', [0, 1]];
            }else if($param['status'] == 2){ //报名失败
                $condition[] = ['o.audit_status', '=', 3];
            }
        }
        if(!is_null($param['audit_status'])){
            $condition[] = ['audit_status', '=', $param['audit_status']];
        }
        $data = (new LifeToolsCompetitionJoinOrder())->alias('o')
            ->with(['audit_list'])
            ->join('user u', 'o.uid=u.uid')
            ->where($condition)
            ->paginate($param['page_size'])
            ->each(function($item, $key){
                $item->append(['need_pay','pay_time_text','audit_status_text']);
                $item->name = $item->nickname ?? $item->name;
                if($item->status == 1 && $item->audit_status == 2){
                    $item->status_text = '报名成功';
                }else if($item->paid == 1 && in_array($item->audit_status, [0, 1])){
                    $item->status_text = '已支付';
                }else if($item->status == 2 || $item->audit_status == 3){
                    $item->status_text = '报名失败';
                }
            });
        return $data;
    }

    /**
     * 获取赛事信息
     */
    public function getToolCompetitionMsg($param)
    {
        if($param['competition_id']) {
            $where = [['competition_id', '=', $param['competition_id']]];
            $msg = (new LifeToolsCompetition())->getToolCompetitionMsg($where);
            if (!empty($msg)) {
                $msg['start_time'] = date("Y-m-d H:i:s", $msg['start_time']);
                $msg['end_time'] = date("Y-m-d H:i:s", $msg['end_time']);
                $msg['image_big'] = empty($msg['image_big']) ? "" : replace_file_domain($msg['image_big']);
                $msg['image_small'] = empty($msg['image_small']) ? "" : replace_file_domain($msg['image_small']);
                $msg['certificate_bgimg'] = empty($msg['certificate_bgimg']) ? "" : replace_file_domain($msg['certificate_bgimg']);
            }
        }
        $msg['areas'] = (new AreaService())->getAllArea(2, "area_type,area_id,area_pid,area_id as value,area_name as label");
        $msg['audit_user'] = (new LifeToolsCompetitionAuditAdmin())->field(['admin_id','sort'])->where('competition_id', $param['competition_id'])->order('sort DESC')->select();
        return $msg;
    }
    /**
     * 保存赛事活动
     */
    public function saveToolCompetition($param)
    {
        $param['start_time']=strtotime($param['start_time']);
        $param['end_time']=strtotime($param['end_time']);
        $param['price']=get_format_number($param['price']);
        $param['image_big']=replace_file_domain($param['image_big']);
        $param['image_small']=replace_file_domain($param['image_small']); 
        $audit_user = $param['audit_user'];
        unset($param['audit_user']);

        if(empty($param['competition_id'])){
            unset($param['competition_id']);
            $param['status']=0;
            $ret=(new LifeToolsCompetition())->add($param);
            $param['competition_id'] = $ret;
        }else{
            $where=[['competition_id','=',$param['competition_id']]];
            $msg = (new LifeToolsCompetition())->getToolCompetitionMsg($where);
            if($msg['join_num']>$param['limit_num'] && $msg['limit_type']==1 && $param['limit_type']==1){
                throw new \think\Exception(L_("参与人数不可小于已经报名的人数,已报名的人数是:".$msg['join_num']), 1003);
            }
            $ret=(new LifeToolsCompetition())->updateThis($where,$param);
            if($ret!==false){
                $ret=true;
            }else{
                $ret=false;
            }
        }
        $lifeToolsCompetitionAuditAdmin = new LifeToolsCompetitionAuditAdmin();

        $time = time();
        if(is_array($audit_user)){
            $lifeToolsCompetitionAuditAdmin->where('competition_id', $param['competition_id'])->delete();
            
            $paramAdminIds =  array_column($audit_user, 'admin_id');
            $adminInfo = Db::name('admin')->where('id', 'in', $paramAdminIds)->column('realname', 'id');

             
            foreach($audit_user as $key => $val){
                $lifeToolsCompetitionAuditAdmin->insert([
                    'competition_id'    =>  $param['competition_id'],
                    'admin_id'      =>  $val['admin_id'],
                    'name'      =>  $adminInfo[$val['admin_id']],
                    'sort'      =>  $val['sort'],
                    'add_time'  =>  $time
                ]);
            } 
             
        }
        return $ret;
    }
    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function addOrderExport($param, $systemUser = [], $merchantUser = [])
    {
        $title = '赛事活动报名信息';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\life_tools\model\service\LifeToolsCompetitionService';
        $param['service_name'] = 'orderExportPhpSpreadsheet';
        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $param['page_size'] = 100000;
        // $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        $result = $this->orderExportPhpSpreadsheet($param);
        return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function orderExportPhpSpreadsheet($param)
    {
        $orderList = ($this->lookCompetitionUser($param))->toArray()['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '姓名');
        $worksheet->setCellValueByColumnAndRow(2, 1, '手机号');
        $worksheet->setCellValueByColumnAndRow(3, 1, '报名费用');
        $worksheet->setCellValueByColumnAndRow(4, 1, '状态');
        $worksheet->setCellValueByColumnAndRow(5, 1, '是否需要报名费');
        $worksheet->setCellValueByColumnAndRow(6, 1, '审核状态');
        $worksheet->setCellValueByColumnAndRow(7, 1, '支付时间');
        //设置单元格样式
        $worksheet->getStyle('A1:G1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:G')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $len = count($orderList);
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $orderList[$key]['status'] = $val['status_text'];
                $orderList[$key]['price']=get_number_format($val['price']);

                if($val['price']==0){
                    $orderList[$key]['need_pay']="不需要";
                }else{
                    $orderList[$key]['need_pay']="需要";
                }

                $orderList[$key]['pay_time']=empty($val['pay_time'])?"":date("Y-m-d H:i:s",$val['pay_time']);
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['name']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(3, $j, '¥' .$orderList[$key]['price']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['status']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['need_pay']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['audit_status_text']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['pay_time']);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:G' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 管理员列表
     */
    public function getAuditAdminList()
    {
        return Db::name('admin')->field(['id as admin_id','realname as name'])->where('status', 1)->where('realname', '<>', '')->select();
    }

    /**
     * 我的审核
     */
    public function getMyAuditList($params)
    {
        $condition = [];
        $condition[] = ['admin_id', '=', $params['admin_id']];
        $condition[] = ['is_show', '=', 1];
        
        if(!empty($params['keywords'])){
            switch ($params['search_type']) {
                case 1: //名称
                    $condition[] = ['a.nickname', 'like', "%{$params['keywords']}%"];
                    break;
                case 2: //手机号
                    $condition[] = ['a.phone', 'like', "%{$params['keywords']}%"];
                    break;
                case 3: //赛事名称
                    $condition[] = ['c.title', 'like', "%{$params['keywords']}%"];
                    break;
            }
        }

        if(!is_null($params['status'])){
            $condition[] = ['a.status', '=', $params['status']];
        }

        $data = (new LifeToolsCompetitionAudit())
                ->alias('a')
                ->field(['a.*', 'c.title'])
                ->join('life_tools_competition c', 'a.competition_id = c.competition_id')
                ->where($condition)
                ->order('add_time ASC')
                ->append(['submit_time', 'status_text'])
                ->paginate($params['page_size'])
                ->each(function($item, $key){
                    $audit_info_str = '';
                    if(!empty($item->audit_info) && is_array($item->audit_info)){
                        foreach ($item->audit_info as $k => $v) {
                            if(!isset($v['type']) || $v['type'] != 'image'){
                                $audit_info_str .= $v['title'] . '：' . $v['show_value'] . '；';
                            }
                        }
                    }
                    $item->audit_info = $audit_info_str;
                    $item->submit_time = date('Y-m-d H:i:s', $item->add_time);
                });
        return $data;
    }

    /**
     * 审核数量统计
     */
    public function getMyAuditCount($admin_id)
    {
        $putout = [
            'wait_audit'    =>  0,//待审核
            'audit_success' =>  0,//审核通过
            'audit_error'   =>  0,//审核不通过
        ];
        $LifeToolsCompetitionAudit = new LifeToolsCompetitionAudit();
       
        $commonCondition = [];
        $commonCondition[] = ['admin_id', '=', $admin_id];
        $commonCondition[] = ['is_show', '=', 1];
        $putout['wait_audit'] = $LifeToolsCompetitionAudit->where($commonCondition)->where('status', 0)->count();
        $putout['audit_success'] = $LifeToolsCompetitionAudit->where($commonCondition)->where('status', 1)->count();
        $putout['audit_error'] = $LifeToolsCompetitionAudit->where($commonCondition)->where('status', 2)->count();
        return $putout;
    }

    /**
     * 审核详情
     */
    public function getMyAuditInfo($params)
    {
        $condition = [];
        $condition[] = ['admin_id', '=', $params['admin_id']];
        $condition[] = ['is_show', '=', 1];
        $data = (new LifeToolsCompetitionAudit())->alias('a')
                ->field(['a.*', 'o.*', 'o.audit_status as o_audit_status', 'a.status as audit_status'])
                ->join('life_tools_competition_join_order o', 'a.order_id = o.pigcms_id')
                ->with(['competition'])
                ->where('id', $params['id'])
                ->find();
        if(!$data){
            throw new \think\Exception('内容不存在！');
        }
        if($data['admin_id'] != $params['admin_id']){
            throw new \think\Exception('无权访问！');
        }
        $data = $data->toArray();
        if(!empty($data['competition']['is_custom'])){
            $data['custom_form'] = json_decode($data['custom_form']);
        }else{
            $custom_form = [];
            $custom_form[] = [
                'title' =>  '姓名',
                'show_value' =>  $data['name'],
            ];
            $custom_form[] = [
                'title' =>  '手机号',
                'show_value' =>  $data['phone'],
            ];
            $data['custom_form'] = $custom_form;
        }
        $data['paid_time_text'] = date('Y-m-d H:i:s', $data['pay_time']);
        $data['add_time_text'] = date('Y-m-d H:i:s', $data['add_time']);

        if(!empty($data['competition']['start_time']) && !empty($data['competition']['end_time'])){
            $data['competition']['date'] = date('Y-m-d H:i:s', $data['competition']['start_time']) . ' ~ ' . date('Y-m-d H:i:s', $data['competition']['end_time']);
        }
        if($data['o_audit_status'] == 2){
            $statusMap = ['待支付', '报名成功', '报名失败'];
            $data['status_text'] = $statusMap[$data['status']] ?? '';
        }else{
            $statusMap = ['待审核', '审核中', '审核通过', '审核失败'];
            $data['status_text'] = $statusMap[$data['o_audit_status']] ?? '';
        }
        return $data;
    }


    /**
     * 审核
     */
    public function audit($params)
    {
        $lifeToolsCompetitionAudit = new LifeToolsCompetitionAudit();
        $lifeToolsCompetitionJoinOrder = new LifeToolsCompetitionJoinOrder();
        $audit = $lifeToolsCompetitionAudit->find($params['id']);
        if(!$audit){
            throw new \think\Exception('内容不存在！');
        }
        if($audit['admin_id'] != $params['admin_id']){
            throw new \think\Exception('无权访问！');
        }
        if(!in_array($params['status'], [1,2])){
            throw new \think\Exception('请选择是否通过审核！');
        }
        if($params['status'] == 2 && empty($params['remark'])){
            throw new \think\Exception('请输入审核理由！');
        }
        $audit->status = $params['status'];
        $audit->remark = $params['remark'];
        $audit->audit_time = $time = time();
        $audit->save();

        //审核通过
        if($params['status'] == 1){
            $condition = [];
            $condition[] = ['order_id', '=', $audit->order_id];
            $condition[] = ['status', '=', 0];
            $waitAuditCount = $lifeToolsCompetitionAudit->where($condition)->count();

            //存在管理员未审核
            if($waitAuditCount){
                $condition = [];
                $condition[] = ['order_id', '=', $audit->order_id];
                $condition[] = ['is_show', '=', 0];
                $waitAudit = $lifeToolsCompetitionAudit->where($condition)->order('sort DESC')->find();
                if(!$waitAudit){
                    throw new \think\Exception('未知错误！');
                }
                $waitAudit->is_show = 1;
                $waitAudit->save();
    
                //审核中
                $lifeToolsCompetitionJoinOrder->where('pigcms_id', $audit->order_id)->update([
                    'audit_status'  =>  1,
                    'audit_time'    =>  $time
                ]);
                
            }else {
                //全部审核了，审核通过  
                $lifeToolsCompetitionJoinOrder->where('pigcms_id', $audit->order_id)->update([
                    'audit_status'  =>  2,
                    'audit_time'    =>  $time
                ]);
            }
        }else{
            //审核失败
            $lifeToolsCompetitionJoinOrder->where('pigcms_id', $audit->order_id)->update([
                'audit_status'  =>  3,
                'audit_time'    =>  $time
            ]);
        }
         
        return true;

    }
}