<?php


namespace app\life_tools\model\service\appoint;


use app\common\model\db\Area;
use app\common\model\db\SystemOrder;
use app\common\model\service\AreaService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\order\SystemOrderService;
use app\employee\model\db\EmployeeCardUser;
use app\life_tools\model\db\LifeToolsAppoint;
use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\life_tools\model\db\LifeToolsAppointSeat;
use app\life_tools\model\db\LifeToolsAppointSeatDetail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use app\life_tools\model\db\LifeToolsAppointSku;
use app\life_tools\model\db\LifeToolsAppointSpec;
use app\life_tools\model\db\LifeToolsAppointSpecVal;
use app\life_tools\model\db\LifeToolsAppointVerify;
use app\life_tools\model\db\User;
use app\merchant\model\db\MerchantStoreStaff;

class LifeToolsAppointService
{
    /**
     * 预约列表
     */
    public function getList($param)
    {
        $where = [['a.is_del', '=', 0]];
        if (!empty($param['title'])) {
            array_push($where, ['a.title', 'like', '%' . $param['title'] . '%']);
        }

        if (isset($param['mer_name']) && !empty($param['mer_name'])) {
            array_push($where, ['m.name', 'like', '%' . $param['mer_name'] . '%']);
        }

        if (isset($param['mer_id']) && !empty($param['mer_id'])) {
            array_push($where, ['a.mer_id', '=', $param['mer_id']]);
        }

        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            if($param['start_time']!=$param['end_time']){
                array_push($where, ['a.start_time', '>', strtotime($param['start_time'])]);
                array_push($where, ['a.end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }else{
                array_push($where, ['a.start_time', '>', strtotime($param['start_time']." 00:00:00")]);
                array_push($where, ['a.end_time', '<', strtotime($param['end_time']." 23:59:59")]);
            }
        }

        // 排除的id
        if(isset($param['appoint_ids_not']) && $param['appoint_ids_not']){
            $where[] = ['a.appoint_id' ,'not in', $param['appoint_ids_not']];  
        }

        // 状态
        if(isset($param['status']) ){
            $where[] = ['a.status' ,'=', $param['status']];  
        }
        $list = (new LifeToolsAppoint())->getListAndMer($where, 'a.*,m.name as mer_name', 'a.appoint_id desc', $param['page'], $param['pageSize']);

        if($list['list']){
            foreach($list['list'] as $key => $value){
                $list['list'][$key]['activity_time']=date("Y-m-d H:i:s",$value['start_time']).'-'.date("Y-m-d H:i:s",$value['end_time']);

            }

        }
        return $list;
    }

    /**
     * 预约活动-我的预约列表
     */
    public function myCompetList($param)
    {
        $where=[['j.uid','=',$param['uid']],['j.status','=',1]];
        $list=(new LifeToolsAppointJoinOrder())->myCompetList($where,'c.start_time,c.end_time,c.appoint_id,c.title,c.city_id,c.address,j.name,j.phone,j.price,j.status,j.pigcms_id as order_id','j.pigcms_id asc',$param['page'],$param['pageSize']);
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
     * 预约活动-我的报名详情
     */
    public function orderDetail($param)
    {
        $where=[['j.pigcms_id','=',$param['pigcms_id']],['j.status','=',1]];
        $list=(new LifeToolsAppointJoinOrder())->orderDetail($where,'c.title,c.start_time,c.end_time,c.appoint_id,c.province_id,c.city_id,c.area_id,c.long,c.lat,c.address,c.phone,j.price,j.status,j.pigcms_id as order_id,j.name,j.phone as tel_phone');
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
            $list['start_time']=date("Y.m.d H:i:s",$list['start_time']);
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['province_id']]);
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['city_id']]);
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$list['area_id']]);
            $list['address']=$pro_name.$city_name.$area_name.$list['address'];
        }

        return $list;
    }
    /**
     * 用户端预约列表
     */
    public function appointList($param, $type = '',$uid=0)
    {
        $order='appoint_id desc';
        if(isset($param['type']) && $param['type']==1){
            $order='limit_num desc';
        }
        $where=[['status','=',1],['is_del','=',0]];

        $distance = '0 as distance';
        if (isset($param['long']) && !empty($param['long']) && isset($param['lat']) && !empty($param['lat'])) {
            $distance = '(st_distance(point(`long`, `lat`), point('.$param['long'].', '.$param['lat'].') ) * 111195 / 1000) AS distance';
        }
        
        $cardUserList=(new EmployeeCardUser())->getColumn(['uid'=>$uid,'status'=>1],'mer_id');
        if(count($cardUserList)){
            $where[] = ['people_type', 'exp', Db::raw('= 0 OR (people_type = 1 AND mer_id IN('.implode(',', $cardUserList).'))')];
        }else{
            $where[] = ['people_type', '=', 0];
        }
//        $whereColumn = "`start_time`<=`send_notice_days`*24*60*60+".time();//加上提前显示的时间判断
//        $list=(new LifeToolsAppoint())->appointList($where, '*,'.$distance,$order,$param['page'],$param['pageSize'],$whereColumn);
        
        $list=(new LifeToolsAppoint())->appointList($where, '*,'.$distance,$order,$param['page'],$param['pageSize']);
        if(!empty($list['data'])){
            $time = time();
            foreach ($list['data'] as $key=>$value){
               $list['data'][$key]['image_big']=empty($value['image_big'])?'':replace_file_domain($value['image_big']);

               $activity_status = 0;
               $activity_status_text = '';
               if($value['appoint_start_time'] < $time){
                   $activity_status = 1;
                   $activity_status_text = '报名中';
               }else{
                    $activity_status = 4;
                   $activity_status_text = '报名未开始';
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
               $list['data'][$key]['activity_status'] = $activity_status; 
               $list['data'][$key]['activity_status_text'] = $activity_status_text; 
           }

           if($type == 'ticketBook'){
                // 门票预约首页列表
                $list['data'] = $this->fomartTicketBookList($list['data']);
            }
          
        }
        
        return $list;
    }

    public function fomartTicketBookList($data)
    {
        $returnArr = [];
        foreach ($data as $key=>$value){
            $tempData = [];
            $tempData['tools_id'] = $value['appoint_id'];
            $tempData['mer_id'] = $value['mer_id'];
            $tempData['title'] = $value['title'];
            $tempData['desc'] = $value['desc'];// 简介
            $tempData['address'] = $value['address'];// 详细地址
            $tempData['image'] = $tempData['cover_image'] = $value['image_small'] ? replace_file_domain($value['image_small']) : '';
            $tempData['sale_count'] = $value['join_num'];
            $tempData['type'] = 'appoint';
            $tempData['money'] = get_format_number($value['price']);
            $tempData['distance'] = !empty($value['distance']) ? get_format_number($value['distance']) : 0;
            $tempData['url']      = get_base_url() . 'pages/lifeTools/appointment/detail?id=' . $tempData['tools_id'];
            $tempData['start_time'] = date("Y-m-d H:i:s",$value['start_time']);
            $tempData['activity_status'] = $value['activity_status'];
            $tempData['activity_status_text'] = $value['activity_status_text'];
            $tempData['is_sku'] = $value['is_sku'] ?? 0;
            $returnArr[] = $tempData;
        }
        return $returnArr;
    }

    /**
     * 预约详情
     */
    public function competDetail($param)
    {
        $where=[['appoint_id','=',$param['appoint_id']]];
        $detail=(new LifeToolsAppoint())->getToolAppointMsg($where);
        if(!empty($detail)){
            
            if($detail['start_time']>time()) {
                $detail['act_status']=0;//未开始
            }
            if(!empty($detail) && $detail['limit_type']==1 && ($detail['limit_num']<=$detail['join_num'])){
                $detail['act_status']=3;
            }
            if($detail['start_time']<time() && $detail['end_time']>time()){
                $detail['act_status']=1;//进行中
            }
            if($detail['end_time']<time()){
                $detail['act_status']=2;//已经结束
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
            $detail['appoint_start_time']=empty($detail['appoint_start_time'])?"":date('Y-m-d H:i',$detail['appoint_start_time']);
            $detail['appoint_end_time']=empty($detail['appoint_end_time'])?"":date('Y-m-d H:i',$detail['appoint_end_time']);
        }
        //座位信息
        if($detail['is_select_seat'] == 1){
            $seatData = (new LifeToolsAppointSeat())->getDataByAppointId($param['appoint_id'], 1);
            $detail['seat_data'] = $this->getSeatData($seatData);
        }

        //多规格
        if ($detail['is_sku']){
            // $data['ticket'][$tk]['limit_num'] =$data['ticket'][$tk]['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$tv['ticket_id'],'is_del'=>0],'stock_num');
            $spec = $sku = $skuList = [];
            $specList = (object)[];
            $specList->tree = [];
            $specList->list = [];
            $spec = (new LifeToolsAppointSpec())
                ->field(['name','spec_id'])
                ->with(['values'=>function($query){
                    $query->field(['id', 'name', 'spec_id']);
                }])
                ->where('appoint_id', $param['appoint_id'])
                ->where('is_del', 0)
                ->select();

            $sku = (new LifeToolsAppointSku())
                ->field(['sku_id','price','sku_info', 'stock_num','sku_str', 'original_num'])
                ->where('appoint_id', $param['appoint_id'])
                ->where('is_del', 0)
                ->select();
            foreach ($spec as $k => $val) {
                $val->k_id = 's' . ($k+1);
                unset($val->spec_id);
                $specList->tree[] = $val;
            }

            // $detail['limit_num'] = 0;
            $skuIds = [];
            foreach ($sku as $val)
            {
                $skuIds[] = $val->sku_id;
                $val->id = $val->sku_id;
                $val->sku_info = trim($val->sku_info, '|');
                $val->sku_str = trim($val->sku_str, ',');
                $skuList[$val->sku_info] = $val;
                $skuInfos = explode('|', $val->sku_info);
                foreach ($skuInfos as $k => $v) {
                    list($sid, $vid) = explode(':', $v);
                    $sname = 's' . ($k + 1);
                    $val->$sname = $vid;
                }
                unset($val->sku_info);
                $specList->list[] = $val;
                // $detail['limit_num'] += $val['stock_num'];
            }
            $detail['specList'] = $specList;
            $detail['skuList'] = $skuList;


            //统计已售
            $condition = [];
            $condition[] = ['sku_id', 'in', $skuIds];
            $condition[] = ['appoint_id', '=', $param['appoint_id']];
            $condition[] = ['status', 'in', [1, 2, 3]];
            $condition[] = ['paid', '=', 1];
            $sellNum = (new LifeToolsAppointJoinOrder())->where($condition)->count();
            $detail['join_num'] = $sellNum;
            if($sellNum > $detail['limit_num']){
                // $detail['limit_num'] = $sellNum;
            }
        }

        //自定义表单
        if($detail['custom_form'] && count($detail['custom_form'])){
            foreach($detail['custom_form'] as $key => $val){
                if($val['type'] == 'select'){
                    $content = explode(',', $val['content']);
                    $detail['custom_form'][$key]['content'] = [];
                    foreach($content as $k => $v){
                        $detail['custom_form'][$key]['content'][] = [
                            'label'=> $v,
                            'value'=> $k
                        ];
                    }
                }
            }
        }


        return $detail;
    }

    /**
     * 预约活动-报名提交订单
     */
    public function saveOrder($param)
    {
            Db::startTrans();
            $where = [['appoint_id', '=', $param['appoint_id']], ['status', '=', 1], ['is_del', '=', 0]];
            $detail = (new LifeToolsAppoint())->getToolAppointMsg($where);
            if (empty($detail)) {
                throw new \Exception("此预约状态异常");
            }
//            if($detail['start_time']<time() && $detail['end_time']>time()){
//                throw new \Exception("已经开始的预约活动不可报名");
//            }
            if($detail['appoint_start_time']>time() || $detail['appoint_end_time']<time()){
                throw new \Exception("不在预约时间段内，不可报名！");
            }
            // $is_pay_order=(new LifeToolsAppointJoinOrder())->getOneDetail(['appoint_id'=>$param['appoint_id'],'uid'=>$param['uid'],'paid'=>1,'status'=>1]);
            $pay_num = (new LifeToolsAppointJoinOrder())->where(['appoint_id'=>$param['appoint_id'],'uid'=>$param['uid'],'paid'=>1])->count();
            if($detail['limit'] <= $pay_num){
                // throw new \Exception("您已经报名过该活动了,不可重复报名!");
                throw new \Exception("每个账号只能报名{$detail['limit']}次!");
            }
            $time = time();
            $data['phone'] = $param['phone'];
            $data['name'] = $param['name'];
            $data['appoint_id'] = $param['appoint_id'];
            $data['price'] = $detail['price'];
            $data['paid'] = 0;
            $data['status'] = 0;
            $data['is_del'] = 0;
            $data['mer_id'] =$detail['mer_id'];
            $data['uid'] = $param['uid'];
            $data['real_orderid'] = build_real_orderid($param['uid']);
            $data['add_time'] = $time;
            $data['remark']=$param['remark'];
            // if($detail['need_verify']==1){//生成核销码码
                $data['verify_code']=createRandomStr(16);
                $data['need_verify'] = $detail['need_verify'];
            // }
            if($detail['people_type']==1){//员工卡
                $userCard=(new EmployeeCardUser())->getUser(['uid'=>$param['uid'],'mer_id'=>$detail['mer_id']]);
                if(empty($userCard)){
                    throw new \Exception("您不是此商家员工,此门票只有商家员工才能预约!");
                }
            }
            //选座
            if($detail['is_select_seat'] == 1){
                $data['price'] = 0;
                if(!is_array($param['seat_num']) || !count($param['seat_num'])){
                    throw new \think\Exception("请选择座位号!");
                }
                $param['seat_num'] = array_filter($param['seat_num']);
                $seatData = (new LifeToolsAppointSeat())->getDataByAppointId($detail['appoint_id'], 1); 
                $seatMap = array_column($seatData, 'seat_num');
                
                $seatDetail = [];
                foreach($param['seat_num'] as $k => $v){
                    if(!in_array($v, $seatMap)){
                        throw new \think\Exception("座位（{$v}）不存在!");
                    }
                    foreach ($seatData as $key => $val) {
                        if($val['seat_num'] == $v){
                            if($val['is_buy'] == 0){
                                throw new \think\Exception("座位（{$v}）不可购买!");
                            }
                            if($val['is_select'] == 1){
                                throw new \think\Exception("座位（{$v}）已销售!");
                            }

                            $data['price'] += $val['seat_price'];
                            $tmp = [];
                            $tmp['appoint_id'] = $detail['appoint_id'];
                            $tmp['seat_num'] = $val['seat_num'];
                            $tmp['seat_price'] = $val['seat_price'];
                            $tmp['seat_title'] = $val['seat_title'];
                            //生成多核销码
                            if($detail['is_multi_code'] == 1){
                                if($detail['need_verify']==1){ //是否需要核销
                                    $tmp['code'] = createRandomStr(16);
                                }else{
                                    $tmp['code'] = '';
                                }
                            }else{
                                $tmp['code'] = $data['verify_code'];
                            }
                            $tmp['add_time'] = $time;
                            $seatDetail[] = $tmp;
                        }
                    }
                }
 
            }

            $data['sku_id'] = 0;
            //多规格
            if ($param['sku_id']) {
                $sku = (new LifeToolsAppointSku())->getOne(['sku_id' => $param['sku_id'], 'appoint_id' => $param['appoint_id'], 'is_del' => 0]);
                if (empty($sku)) {
                    throw new \think\Exception('数据繁忙,请重新选择规格下单！');
                } else {
                    $sku = $sku->toArray();
                    if($sku['stock_num'] <= 0){
                        throw new \think\Exception('库存不足！');
                    }else{
                        //改为支付减库存
                        // (new LifeToolsAppointSku())->setDec(['sku_id'=>$sku['sku_id'], 'appoint_id'=>$sku['appoint_id']],'stock_num', 1);
                    }
                    $condition = [];
                    $condition[] = ['sku_id', '=', $param['sku_id']];
                    $condition[] = ['appoint_id', '=', $param['appoint_id']];
                    $condition[] = ['status', 'in', [1, 2, 3]];
                    $condition[] = ['paid', '=', 1];
                    $sellNum = (new LifeToolsAppointJoinOrder())->where($condition)->count();
                    if($sellNum >= $sku['original_num']){
                        throw new \think\Exception('库存不足！');
                    } 

                    $data['sku_id'] = $sku['sku_id'];
                    $data['price'] = $sku['price'];
                }
            }

            //自定义表单
            if($detail['is_custom_form'] == 1){

                foreach($param['custom_form'] as $k => $item){
                    if(empty($item['value']) && $item['is_must'] == 1){
                        throw new \think\Exception($item['title'] . '参数不能为空');
                    }
                    //图片
                    if($item['type'] == 'image'){
                        if(!is_array($item['value'])){
                            throw new \think\Exception('value格式有误！');
                        }
                        if(count($item['value']) > $item['image_max_num']){
                            throw new \think\Exception($item['title'] . '最多上传' . $item['image_max_num'] . '张图片');
                        }
    
                        $item['value'] = array_filter($item['value']);
                    }
                    //身份证
                    if($item['type'] == 'idcard'){
                        if($item['is_must'] == 1 && !is_idcard($item['value'])){
                            throw new \think\Exception('身份证号码填写不正确!');
                        }
                    }
                    //选择
                    if($item['type'] == 'select'){
                        if(!empty($item['value']) && is_array($item['value']) && isset($item['value'][0]['value'])){
                            $item['value'] = $item['value'][0]['value'];
                        }
                    }
                    //手机号
                    if($item['type'] == 'phone'){
                        if($item['is_must'] == 1 && !preg_match('/^1\d{10}$/ims' ,$item['value'])){
                            throw new \think\Exception('手机号填写不正确!');
                        }
                    }
                    //邮箱
                    if($item['type'] == 'email'){
                        if($item['is_must'] == 1 && !preg_match('/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims' ,$item['value'])){
                            throw new \think\Exception('邮箱填写不正确!');
                        }
                    }
                    $param['custom_form'][$k] = $item;
                } 
            }
            
            $data['custom_form'] = $param['custom_form'];
            
            if($detail['price'] > 0 && isset($param['coupon_id']) && $param['coupon_id']){
                $step5=0;
                $SystemCouponService = new SystemCouponService;
                $current_sys_coupons = $SystemCouponService->formatDiscount($SystemCouponService->getAvailableCoupon($param['uid'], ['can_coupon_money'=>$detail['price'], 'business' => 'life_tools_appoint_join'], true));
                if($current_sys_coupons){
                    foreach ($current_sys_coupons as $sys_coupon) {
                        if($param['coupon_id'] == $sys_coupon['id']){//已选择的
                            $step5 = $sys_coupon['discount_money'];
                            $data['coupon_id']=$sys_coupon['id'];
                        }
                    }
                }
                if($step5){
                    $data['coupon_price']=$step5;
                }
            }
           
            $ret_order_id = (new LifeToolsAppointJoinOrder())->add($data);
            if (!$ret_order_id) {
                Db::rollback();
                throw new \Exception("报名失败");
            } else {
                $where1=[['j.appoint_id','=',$param['appoint_id']],['j.pigcms_id','=',$ret_order_id]];
                $ret = $this->addSystemOrder($where1, 'j.*', $detail['mer_id']);
                $res = true;
                if(isset($seatDetail) && count($seatDetail)){
                    foreach($seatDetail as $key => $val){
                        $seatDetail[$key]['order_id'] = $ret_order_id;
                    }
                    $res = (new LifeToolsAppointSeatDetail())->addAll($seatDetail);
                }
                if (!$ret || !$res) {
                    Db::rollback();
                    throw new \Exception("报名失败");
                }else{
                    //提交事务
                    Db::commit();
                    $res = ['status' => 1, 'msg' => L_('报名成功'),'order_id'=>$ret_order_id,'order_type'=>'life_tools_appoint_join'];
                    return $res;
                }
            }
    }

    /**
     * 写入平台总订单
     * @param $tableId int 桌台id
     * @return array
     */
    public function addSystemOrder($where = [],$field,$mer_id)
    {
        if (!$where) return false;

        $nowOrder = (new LifeToolsAppointJoinOrder())->orderDetail($where,$field);

        $systemOrderService = new SystemOrderService();
        $business = 'life_tools_appoint_join';
        $businessOrderId = $nowOrder['pigcms_id'];
        // system_status  0-待支付 2-完成订单 待评价 3-完成订单 5-取消订单
        $saveData['store_id'] = 0;
        $saveData['real_orderid'] = 0;

        $saveData['price'] = get_number_format($nowOrder['price']-$nowOrder['coupon_price']);
        $saveData['goods_price'] = $nowOrder['price'];
        $saveData['total_price'] = $nowOrder['price'];
        $saveData['mer_id'] = $mer_id;
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
     * 删除预约
     */
    public function delSport($param){
        $where=[['appoint_id','=',$param['appoint_id']]];
        if(isset($param['mer_id']) && $param['mer_id']){
            $where[] = ['mer_id','=',$param['mer_id']];
        }
        $data['is_del']=1;
        $ret=(new LifeToolsAppoint())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 关闭预约
     */
    public function closeAppoint($param)
    {
        $where=[['appoint_id','=',$param['appoint_id']]];
        if(isset($param['mer_id']) && $param['mer_id']){
            $where[] = ['mer_id','=',$param['mer_id']];
        }
        $data['status']=$param['status'];
        $ret=(new LifeToolsAppoint())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 用户报名信息
     */
    public function lookAppointUser($param)
    {
        // $where = [['appoint_id', '=', $param['appoint_id']],['is_del', '=', 0]];
        // if(isset($param['mer_id']) && $param['mer_id']){
        //     $where[] = ['mer_id','=',$param['mer_id']];
        // }
        // $list['list']=(new LifeToolsAppointJoinOrder())->getSome($where)->toArray();
 
        
        $field = 'a.title,a.start_time,a.end_time,a.address,a.image_small,a.desc,a.is_suspend,a.need_verify,a.can_refund,a.refund_hours
            ,o.status,o.pigcms_id as order_id,o.custom_form,o.verify_code,o.pay_time,o.verify_time,o.paid,o.real_orderid,o.add_time,o.is_apply_refund,o.price,o.apply_refund_reason,o.sku_id
            ,u.phone,u.nickname';
        $condition = [];
        $condition[] = ['o.appoint_id', '=', $param['appoint_id']];
        $condition[] = ['o.is_del', '=', 0];
        $condition[] = ['o.paid', '=', 1];

        if(isset($param['mer_id']) && $param['mer_id']){
            $condition[] = ['o.mer_id', '=', $param['mer_id']];
        }

        //时间筛选
        if(!empty($param['date_start']) && $param['date_end']){
            //核销日期
            if($param['date_type'] == 1){
                $condition[] = ['o.verify_time', 'between', [strtotime($param['date_start']), strtotime($param['date_start'] . ' 23:59:59')]];
            }else{
                //报名日期
                $condition[] = ['o.add_time', 'between', [strtotime($param['date_start']), strtotime($param['date_start'] . ' 23:59:59')]];
            }
        }

        //状态筛选
        if(!empty($param['status'])){
            $condition[] = ['o.status', '=', $param['status']];
        }

        //关键词
        if(!empty($param['keywords'])){
            switch($param['date_type']){
                case 1:
                    $condition[] = ['o.real_orderid', 'like', "%{$param['keywords']}%"];
                    break;
                case 2:
                    $condition[] = ['u.nickname', 'like', "%{$param['keywords']}%"];
                    break;
                case 3:
                    $condition[] = ['u.phone', 'like', "%{$param['keywords']}%"];
                    break;
            }

        }


        $list = (new LifeToolsAppointJoinOrder())->getOrderList($condition, $field, $param['page_size']);
 

        foreach($list['data'] as $key => &$_order){
            $_order['pay_time'] = $_order['pay_time'] ? date('Y-m-d H:i:s', $_order['pay_time']) : '';
            $_order['verify_time'] = $_order['verify_time'] ? date('Y-m-d H:i:s', $_order['verify_time']) : '';
            $_order['add_time'] = $_order['add_time'] ? date('Y.m.d H:i', $_order['add_time']) : '';
            $_order['need_pay'] = $_order['price'] > 0 ? true : false;
             // 0-待支付 1-报名成功 2-报名失败 3-已核销 4—已过期 5-已退款',
            switch($_order['status']){
                case 0:
                    $_order['status_txt'] = '待支付';
                    break;
                case 1:
                    $_order['status_txt'] = '未核销';
                    break;
                case 2:
                    $_order['status_txt'] = '报名失败';
                    break;
                case 3:
                    $_order['status_txt'] = '已核销';
                    break;
                case 4:
                    $_order['status_txt'] = '已过期';
                    break;
                case 5:
                    $_order['status_txt'] = '已退款';
                    break;
            }


            $is_refund_btn = 0;
            $is_verify_btn = 0;
            // if($_order['status'] != 5 && ($_order['can_refund'] == 2 || ($_order['can_refund'] == 1 && time() < ($_order['start_time']-$_order['refund_hours']*3600)))){
            if($_order['status'] != 5){
                $is_refund_btn = 1;
            }
            if($_order['status'] == 1){
                $is_verify_btn = 1;
            }
            $_order['is_refund_btn'] = $is_refund_btn;
            $_order['is_verify_btn'] = $is_verify_btn;

            $_order['pigcms_id'] = $_order['order_id'];

            //多规格
            $list['data'][$key]['sku_str'] = '';
            if($_order['sku_id']){
                $sku = (new LifeToolsAppointSku())->where('sku_id', $_order['sku_id'])->find();
                $list['data'][$key]['sku_str'] = $sku['sku_str'] ?? null;
            }
        } 

        $condition[] = ['o.status', '=', 3];
        $list['verify_num'] = (new LifeToolsAppointJoinOrder())->getCount($condition);


        $list['list'] = $list['data'];
        unset($list['data']);
        return $list;
    }

    /**
     * 获取预约订单详情
     */
    public function getAppointOrderDetail($order_id)
    {
        $field = 'c.title,c.start_time,c.end_time,c.address,c.image_small,c.desc,c.is_suspend,c.need_verify
        ,j.status,j.pigcms_id as order_id,j.custom_form,j.verify_code,j.pay_time,j.verify_time,j.paid,j.real_orderid,j.add_time,j.system_score,j.coupon_price,j.card_price,j.merchant_balance_give,j.uid,j.price,j.apply_refund_reason,j.refund_money,j.refund_time,j.sku_id';
        $condition = [];
        $condition[] = ['j.pigcms_id', '=', $order_id];
        $detail = (new LifeToolsAppointJoinOrder())->orderDetail($condition, $field);
        $detail['add_time'] = $detail['add_time'] ? date('Y-m-d H:i:s', $detail['add_time']) : '-';
        $detail['pay_time'] = $detail['pay_time'] ? date('Y-m-d H:i:s', $detail['pay_time']) : '-';
        $detail['verify_time'] = $detail['verify_time'] ? date('Y-m-d H:i:s', $detail['verify_time']) : '-';
        $detail['activity_time'] = ($detail['start_time'] && $detail['end_time']) ? (date('Y-m-d H:i:s', $detail['start_time']) . ' ~ ' . date('Y-m-d H:i:s', $detail['end_time'])) : '-';
        $detail['start_time'] = date('Y-m-d H:i:s', $detail['start_time']);
        $detail['end_time'] = date('Y-m-d H:i:s', $detail['end_time']);
        $detail['refund_time'] = $detail['refund_time'] == 0 ? '' : date('Y-m-d H:i:s', $detail['refund_time']);
        $detail['need_pay'] = $detail['price'] > 0 ? true : false;
        $order_status_val = '';
        // 0-待支付 1-报名成功 2-报名失败 3-已核销 4—已过期 5-已退款',
        switch($detail['status']){
            case 0:
                $order_status_val = '待支付';
                break;
            case 1:
                $order_status_val = '报名成功';
                break;
            case 2:
                $order_status_val = '报名失败';
                break;
            case 3:
                $order_status_val = '已核销';
                break;
            case 4:
                $order_status_val = '已过期';
                break;
            case 5:
                $order_status_val = '已退款';
                break;
        }
        $detail['order_status_val'] = $order_status_val;

        //下单用户
        $userInfo = (new User())->field('nickname,phone')->where('uid', $detail['uid'])->find();
        $detail['user'] = $userInfo;

        //核销详情
        $appointVerify = (new LifeToolsAppointVerify())->where('appoint_order_id', $order_id)->find();
        $staff_name = '';
        if($appointVerify && $appointVerify->staff_id){
            $staff_name = (new MerchantStoreStaff())->where('id', $appointVerify->staff_id)->value('name');
        }
        $detail['staff_name'] = $staff_name;

        //多规格
        if($detail['sku_id']){
            $sku = (new lifeToolsAppointSku())->where('sku_id', $detail['sku_id'])->find();
            $detail['sku_str'] = $sku['sku_str'] ?? '';
        }

        return $detail;
    }

     /**
     * 自定义暂停信息
     */
    public function suspend($params)
    {
        $condition = [];
        $condition[] = ['appoint_id', '=', $params['appoint_id']];
        $condition[] = ['is_del', '=', 0];
        $appoint = (new LifeToolsAppoint)->where($condition)->find();
        if(!$appoint){
            throw new \think\Exception('活动不存在！');
        }
        $appoint->is_suspend = $params['is_suspend'] == 1 ? $params['is_suspend'] : 0;
        $appoint->suspend_msg = $params['suspend_msg'];
        $appoint->suspend_msg = $params['suspend_msg'];
        return $appoint->save();
    }

    /**
     * 获取预约信息
     */
    public function getToolAppointMsg($param)
    {
        if($param['appoint_id']) {
            $where = [['appoint_id', '=', $param['appoint_id']]];
            if(isset($param['mer_id']) && $param['mer_id']){
                $where[] = ['mer_id','=',$param['mer_id']];
            }
            $msg = (new LifeToolsAppoint())->getToolAppointMsg($where);
            if (!empty($msg)) {
                $msg['start_time'] = date("Y-m-d H:i:s", $msg['start_time']);
                $msg['end_time'] = date("Y-m-d H:i:s", $msg['end_time']);
                $msg['appoint_start_time'] = $msg['appoint_start_time'] ? date("Y-m-d H:i:s", $msg['appoint_start_time']) : '';
                $msg['appoint_end_time'] = $msg['appoint_end_time'] ? date("Y-m-d H:i:s", $msg['appoint_end_time']) : '';
                $msg['image_big'] = empty($msg['image_big']) ? "" : replace_file_domain($msg['image_big']);
                $msg['image_small'] = empty($msg['image_small']) ? "" : replace_file_domain($msg['image_small']);
            }

    

            //获取sku信息
            $sku_list  = [];
            $spec_list = [];
            if ($msg['is_sku'] == 1) {
                // $tools=(new LifeTools())->getDetail(['tools_id'=>$detail['tools_id']],'type');
                $sku_info = (new LifeToolsAppointSku())->getSome(['appoint_id' => $param['appoint_id'], 'is_del' => 0],true,'sku_id asc')->toArray();
                if (!empty($sku_info)) {
                    foreach ($sku_info as $key => $val) {
                        $sku     = explode('|', $val['sku_info']);
                        $sku_str = explode(',', $val['sku_str']);
                        if (!empty($sku)) {
                            $sku_info[$key]['spec_val_id'] = '';
                            foreach ($sku as $k => $v) {
                                $ids = explode(':', $v);
                                if (!empty($ids) && !empty($sku_str)) {
                                    if (isset($ids[1]) && isset($ids[0])) {

                                        $name = $sku_str[$k];
                                        $sku_info[$key]['specid:' . $ids[0]] = $name;
                                        $sku_info[$key]['spec_val_id'] .= empty($sku_info[$key]['spec_val_id']) ? $ids[1] : '_' . $ids[1];
                                    }
                                }
                            }
                        }
                        unset($sku_info[$key]['sku_info'],
                            $sku_info[$key]['sku_str'],
                            $sku_info[$key]['store_id'],
                            $sku_info[$key]['create_time'],
                            $sku_info[$key]['is_del']);
                            $sku_list[] = $sku_info[$key];
                    }

                    $spec_list = (new LifeToolsAppointSpec())->with(['values'=>function($query){
                        $query->field(['id','spec_id','name']);
                    }])->where('appoint_id', $param['appoint_id'])->where('is_del', 0)->select()->toArray();
                    if($spec_list){
                        foreach($spec_list as $key => $val){
                            $spec_list[$key]['list'] = $val['values'];
                            $spec_list[$key]['id'] = $val['spec_id'];
                            unset($spec_list[$key]['values'], $spec_list[$key]['spec_id']);
                        }
                    }
                }
            }

            // dd($spec_list);
            $msg['sku_list'] = $sku_list;
            $msg['spec_list'] = $spec_list;


        }
        $msg['areas'] = (new AreaService())->getAllArea(2, "area_type,area_id,area_pid,area_id as value,area_name as label");

        $seatData = (new LifeToolsAppointSeat())->getDataByAppointId($param['appoint_id']);
        $msg['seat_data'] = $this->getSeatData($seatData);
        return $msg;
    }


    /**
     * 获取座位分布数据
     * @param array seatData 数据库源数据
     * @return array seatArr 座位分布信息数据
     */
    private function getSeatData($seatData)
    {
        $seatArr = [];
        if($seatData){
            $rowArr = [];
            foreach($seatData as $item){
                if(!in_array($item['row'], $rowArr)){
                    $rowArr[] = $item['row'];
                }
            }

            foreach($rowArr as $row){
                $tmp = [];
                $tmp['row'] = $row;
                foreach($seatData as $item){
                    
                    if($item['row'] == $row){
                        if(isset($item['is_select'])){

                            $tmp['list'][] = [
                                'col'       =>  $item['col'],
                                'seat_num'  =>  $item['seat_num'],
                                'seat_title'=>  $item['seat_title'],
                                'is_buy'    =>  $item['is_buy'],
                                'seat_price'=>  $item['seat_price'],
                                'is_select' =>  $item['is_select']
                            ];
                        }else{
                            
                            $tmp['list'][] = [
                                'col'       =>  $item['col'],
                                'seat_num'  =>  $item['seat_num'],
                                'seat_title'=>  $item['seat_title'],
                                'is_buy'    =>  $item['is_buy'],
                                'seat_price'=>  $item['seat_price']
                            ];
                        }
                    }
                }
                
                $seatArr[] = $tmp;
            }
        }
        return $seatArr;
    }


    /**
     * 保存预约活动
     */
    public function saveToolAppoint($param)
    {
        if(!empty($param['appoint_id'])){
            $where=[['appoint_id','=',$param['appoint_id']]];
            $msg = (new LifeToolsAppoint())->getToolAppointMsg($where);
        }
        $time = time();
        $param['start_time']=strtotime($param['start_time']);
        $param['end_time']=strtotime($param['end_time']);
        $param['appoint_start_time']=strtotime($param['appoint_start_time']);
        $param['appoint_end_time']=strtotime($param['appoint_end_time']);
        $param['price']=get_format_number($param['price']);
        $param['image_big']=replace_file_domain($param['image_big']);
        $param['image_small']=replace_file_domain($param['image_small']);
        $param['add_time'] = $time;

        //开启选座
        $seatData = [];
        if($param['is_select_seat'] == 1){
            if(empty($param['seat_row']) || empty($param['seat_col']) || empty($param['seat_data'])){
                throw new \think\Exception('请填写座位信息!');
            }
            // 生成座位数据
            foreach($param['seat_data'] as $key => $val){
                
                foreach($val['list'] as $k => $v){
                    $tmp = [];
                    $tmp['mer_id'] = $param['mer_id'];
                    $tmp['row'] = $val['row'];
                    $tmp['seat_num'] = $v['seat_num'];
                    $tmp['seat_title'] = $v['seat_title'];
                    $tmp['col'] = $v['col'];
                    $tmp['is_buy'] = $v['is_buy'];
                    $tmp['seat_price'] = $v['seat_price'];
                    $tmp['add_time'] = $time;
                    $tmp['is_show'] = 1;
                    $seatData[] = $tmp;
                }
            }
        }
        unset($param['seat_data']);

        
        //多规格
        $data['is_sku'] = $param['is_sku'];
        //库存计算
        if (!empty($param['sku_list']) && $param['is_sku'] == 1) {//多规格

            $price = array_column($param['sku_list'], 'price');
            $param['max_price'] = max($price);
            $param['min_price'] = min($price);
            //该商品是sku时price字段存入min_price的值
            $param['is_sku'] = 1;
            $param['price']      = $param['min_price'];
            $param['limit_type'] = 1;

            $is_edit = false;
            if(empty($param['appoint_id'])){
                $is_edit = true;      
            }

            //× 添加活动的时候计算多规格总数，或者单规格改成多规格的时候计算多规格总数
            // if((empty($param['appoint_id']) || empty($msg['is_sku'])) && $param['limit_type']==1){
                $limit_num = 0;
                foreach ($param['sku_list'] as $sk => $val) {
                    $limit_num += $val['original_num'];

                    //添加多规格预约的时候，同步当前规格
                    if(!$is_edit){
                        $param['sku_list'][$sk]['stock_num'] = $val['original_num'];
                    }

                }
                $param['limit_num'] = $limit_num;
            // }

            

        } else {
            //单规格最高价和最低价都存入price
            $param['max_price']  = $param['price'];
            $param['min_price']  = $param['price'];
            $param['is_sku'] = 0;
        }
        $spec_list = $param['spec_list'];
        $list      = $param['sku_list'];
        unset($param['sku_list']);
        unset($param['spec_list']);
        if(!empty($spec_list) && $param['is_sku'] == 1){
            foreach ($spec_list as $sk => $val) {
                if (empty($val['list'])) {
                    throw new \think\Exception(L_('不能上传空规格'), 1001);
                }
            }
        }

        

        //人数限制
        if($param['limit_type'] == 0){
            $param['limit_num'] = 0;
        }

        $seatModel = new LifeToolsAppointSeat();
        if(empty($param['appoint_id'])){
            unset($param['appoint_id']);
            $param['status']=0;
            $ret=(new LifeToolsAppoint())->add($param);
            $param['appoint_id'] = $ret;
            if($ret && $param['is_select_seat'] == 1 && count($seatData) > 0){
                //保存座位信息
                foreach ($seatData as $key => $val) {
                    $seatData[$key]['appoint_id'] = $ret;
                }
                $seatModel->addAll($seatData);
            }
            
        }else{
            if($msg['join_num']>$param['limit_num'] && $msg['limit_type']==1 && $param['limit_type']==1){
                throw new \think\Exception(L_("参与人数不可小于已经报名的人数,已报名的人数是:".$msg['join_num']), 1003);
            }
            unset($param['mer_id']);
            $ret=(new LifeToolsAppoint())->updateThis($where,$param);
            if($ret!==false){
                if($param['is_select_seat'] == 1 && count($seatData) > 0){
                    $seatModel->where('appoint_id', $param['appoint_id'])->delete();
                    foreach ($seatData as $key => $val) {
                        $seatData[$key]['appoint_id'] = $param['appoint_id'];
                    }
                    $seatModel->addAll($seatData);
                }
                $ret=true;
            }else{
                $ret=false;
            }
        }
        //添加sku
        $this->dealSkuAndSpec($spec_list, $list, $param);

        return $ret;
    }


    /**
     * @throws Exception
     * 处理sku 和 spec
     */
    public function dealSkuAndSpec($spec_list, $list, $param)
    {
        $specIds = (new LifeToolsAppointSpec())->where(['appoint_id' => $param['appoint_id']])->column('spec_id');
        if ($specIds) {
            (new LifeToolsAppointSpec())->updateThis([['spec_id', 'in', $specIds]], ['is_del' => 1]);//删除规格
            (new LifeToolsAppointSpecVal())->updateThis([['spec_id', 'in', $specIds]], ['is_del' => 1]);//删除规格值
        }
        (new LifeToolsAppointSku())->updateThis(['appoint_id' => $param['appoint_id']], ['is_del' => 1]);//删除sku
        if ($param['is_sku'] == 1) {//多规格
            $sku_arr   = [];
            $spec_info = [];
            if (!empty($spec_list)) {
                foreach ($spec_list as $sk => $val) {
                    if ($val['id'] == 0) {
                        $val['id'] = (new LifeToolsAppointSpec())->add([
                            'name'        => $val['name'],
                            'appoint_id'    => $param['appoint_id'],
                            'create_time' => time()
                        ]);
                    } else {
                        (new LifeToolsAppointSpec())->updateThis(['spec_id' => $val['id']], ['name' => $val['name'], 'is_del' => 0]);
                    }
                    foreach ($val['list'] as $kk => $vv) {
                        $specValId = $vv['id'];
                        if ($vv['id'] == 0) {
                            $specValId = $kk;
                            $vv['id']  = (new LifeToolsAppointSpecVal())->add([
                                'name'        => $vv['name'],
                                'spec_id'     => $val['id'],
                                'create_time' => time()
                            ]);
                        } else {
                            (new LifeToolsAppointSpecVal())->updateThis(['id' => $vv['id']], ['name' => $vv['name'], 'is_del' => 0]);
                        }
                        $spec_info[$sk][] = $val['id'] . ':' . $vv['id'] . ';' . $vv['name'] . ';' . $specValId;
                    }
                }
                $sku_info = $this->combination($spec_info);
                foreach ($sku_info as $sv) {
                    $sku1 = '';
                    $sku2 = '';
                    $sku3 = '';
                    foreach ($sv as $svv) {
                        $skuvv = explode(';', $svv);
                        $sku1 .= $sku1 === '' ? $skuvv[2] : '_' . $skuvv[2];
                        $sku2 .= $sku2 === '' ? $skuvv[0] : '|' . $skuvv[0];
                        $sku3 .= $sku3 === '' ? $skuvv[1] : ',' . $skuvv[1];
                    }
                    $sku_arr[$sku1]['sku_info'] = $sku2;
                    $sku_arr[$sku1]['sku_str']  = $sku3;
                }
            }
            if (!empty($spec_list) && !empty($list)) {
                foreach ($list as $v) {
                    $skuData = !empty($sku_arr[$v['index']]['sku_info']) ? (new LifeToolsAppointSku())->getOne([
                        'sku_info'     => $sku_arr[$v['index']]['sku_info'],
                        'appoint_id' => $param['appoint_id'],
                        'is_del'       => 1
                    ]) : [];
                    if ($skuData) {
                        $data = [
                            'price'        => $v['price'],
                            'is_del'       => 0,
                            'stock_num'    => $v['stock_num'] ?? 0,
                            'original_num'    => $v['original_num'] ?? 0,
                            'sku_str'      => $sku_arr[$v['index']]['sku_str'],
                        ];
                        (new LifeToolsAppointSku())->updateThis(['sku_id' => $skuData['sku_id']], $data);
                        $new_skuId=$skuData['sku_id'];
                    } else {
                        $data = [
                            'price'        => $v['price'],
                            'is_del'       => 0,
                            'stock_num'    => $v['stock_num'] ?? 0,
                            'original_num'    => $v['original_num'] ?? 0,
                            'sku_str'      => $sku_arr[$v['index']]['sku_str'],
                        ];
                        $data['appoint_id'] = $param['appoint_id'];
                        $data['sku_info'] = $sku_arr[$v['index']]['sku_info'];
                        $data['create_time']  = time();
                        $new_skuId=(new LifeToolsAppointSku())->add($data);
                    }
                }
            }
        }
        return true;
    }


    /**
     * 获取多维数组所有组合
     */
    public function combination($options = []) {
        $rows = [];
        foreach ($options as $option => $items) {
            if (count($rows) > 0) {
                $clone = $rows;// 2、将第一列作为模板
                $rows  = [];// 3、置空当前列表，因为只有第一列的数据，组合是不完整的
                foreach ($items as $item) {// 4、遍历当前列，追加到模板中，使模板中的组合变得完整
                    $tmp = $clone;
                    foreach ($tmp as $index => $value) {
                        $value[$option] = $item;
                        $tmp[$index]    = $value;
                    }
                    $rows = array_merge($rows, $tmp);// 5、将完整的组合拼回原列表中
                }
            } else {// 1、先计算出第一列
                foreach ($items as $item) {
                    $rows[][$option] = $item;
                }
            }
        }
        return $rows;
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
        $title = '预约活动报名信息';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\life_tools\model\service\LifeToolsAppointService';
        $param['service_name'] = 'orderExportPhpSpreadsheet';
        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $param['page'] = 1;
        $param['page_size'] = 100000;
        // $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        // return $result;
        return $this->orderExportPhpSpreadsheet($param);
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function orderExportPhpSpreadsheet($param)
    {
        $orderList = ($this->lookAppointUser($param))['list'];
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '姓名');
        $worksheet->setCellValueByColumnAndRow(2, 1, '手机号');
        $worksheet->setCellValueByColumnAndRow(3, 1, '报名费用');
        $worksheet->setCellValueByColumnAndRow(4, 1, '报名状态');
        $worksheet->setCellValueByColumnAndRow(5, 1, '是否需要报名费');
        $worksheet->setCellValueByColumnAndRow(6, 1, '是否支付');
        $worksheet->setCellValueByColumnAndRow(7, 1, '支付时间');
        $worksheet->setCellValueByColumnAndRow(8, 1, '是否需要核销');
        $worksheet->setCellValueByColumnAndRow(9, 1, '核销时间');
        //设置单元格样式
        $worksheet->getStyle('A1:I1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:I')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $len = count($orderList);
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                if($val['status']==0){
                    $orderList[$key]['status']="待支付";
                }elseif ($val['status']==1){
                    $orderList[$key]['status']="报名成功";
                }elseif ($val['status']==3){
                    $orderList[$key]['status']="已核销";
                }elseif ($val['status']==5){
                    $orderList[$key]['status']="已退款";
                }elseif ($val['status']==4){
                    $orderList[$key]['status']="已过期";
                }elseif($val['status']==2){
                    $orderList[$key]['status']="报名失败";
                }
                $orderList[$key]['price']=get_number_format($val['price']);

                if($val['price']>0){
                    $orderList[$key]['need_pay']="需要";
                }else{
                    $orderList[$key]['need_pay']="不需要";
                }

                if($val['paid']==0){
                    $orderList[$key]['paid']="未支付";
                }else{
                    $orderList[$key]['paid']="已支付";
                }
                // $orderList[$key]['pay_time']=empty($val['pay_time'])?"":date("Y-m-d H:i:s",$val['pay_time']);
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['nickname']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(3, $j, '¥' .$orderList[$key]['price']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['status']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['need_pay']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['paid']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['pay_time']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['need_verify'] == 1 ? '需要' : '不需要');
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['verify_time']);
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
        $worksheet->getStyle('A1:I' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }


    /**
     * 座位分布列表
     */
    public function getSeatMap($params)
    {
        if(!empty($params['appoint_id'])){
            $seatData = (new LifeToolsAppointSeat())->getDataByAppointId($params['appoint_id']);
            $data = $this->getSeatData($seatData);
        }else{
            $rowName = $AZ = range('A', 'Z');
            if($params['row'] > 26){
                $az = $AZ;
                $num = 26;
                foreach ($AZ as $key => $val) {
                    foreach($az as $k => $v){
                        if($params['row'] <= $num){
                            break 2;
                        }
                        $rowName[] = $val.$v;
                        $num ++;
                    }
                }
                
            } 
            if(empty($params['row']) || empty($params['col'])){
                throw new \think\Exception('row,col不能为空');
            }
     
            //生成数据
            $data = [];
            for($row = 0; $row < $params['row']; $row ++){
                $tmp = [];
                $tmp['row'] = $row;
                for($col = 0; $col < $params['col']; $col ++){
                    $tmp['list'][] = [
                        'col'          =>   $col,
                        'seat_num'     =>   $rowName[$row] . ($col + 1),
                        'seat_title'   =>   $rowName[$row] . ($col + 1),
                        'is_buy'       =>   1,
                        'seat_price'   =>   0
                    ];
                }
                $data[] = $tmp;
            }

        }
       
        return $data;

    }

    /**
     * 取消订单
     */
    public function cancelOrder($order_id, $uid)
    {
        $lifeToolsAppointJoinOrder = new LifeToolsAppointJoinOrder();

        $condition = [];
        $condition[] = ['pigcms_id', '=', $order_id];
        $condition[] = ['uid', '=', $uid];
        $order = $lifeToolsAppointJoinOrder->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在');
        }
        if($order['status'] != 0){
            throw new \think\Exception('订单状态不支持取消操作');
        }
        $appoint = (new LifeToolsAppoint())->where('appoint_id', $order['appoint_id'])->find();
        if(!$appoint){
            throw new \think\Exception('活动不存在');
        }
        $res = $lifeToolsAppointJoinOrder->where('pigcms_id', $order_id)->update([
            'status'    =>  6,
        ]);
        if($res){
            $condition = [];
            $condition[] = ['type', '=', 'life_tools_appoint_join'];
            $condition[] = ['order_id', '=', $order_id];
            (new SystemOrder())->where($condition)->update([
                'system_status'     =>  5,
                'status'            =>  6,
                'last_time'         =>  time()
            ]);
        }
    }
}