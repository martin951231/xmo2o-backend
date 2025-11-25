<?php
//停车优惠券
class House_village_park_couponsModel extends Model{
    //商家列表
    public function couponsShopList($where=[],$order='m_id desc',$pageSize = 20)
    {
        $where['status'] = ['neq',-1];
        import('@.ORG.merchant_page');
        $count = D('Park_coupons_shop')->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $list = D('Park_coupons_shop')->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
            if(!$val['remark']){
                $val['remark'] = '--';
            }
        }
        $data['list'] = $list;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //获取平台店铺
    public function searchShop($where,$village_id,$order='store_id desc')
    {
        $bind_m_id = D('Park_coupons_shop')->where(['village_id'=>$village_id,'status'=>['neq',-1],'type'=>1])->getField('bind_m_id',true);
        if($bind_m_id){
            $where['store_id'] = ['not in',$bind_m_id];
        }
        $where['status'] = 1;
        $list = D('Merchant_store')->where($where)->field('store_id,name')->order($order)->select();
        return $list;
    }
    //添加/修改/店铺
    public function subShop($data,$id='')
    {
        if($id){
            $where['m_name'] = $data['m_name'];
            $where['m_id'] = ['neq',$id];
            $info = D('House_village_park_coupons')->getShop($where);
            if($info){
                throw new Exception("店铺已存在!");
            }
            $where = [];
            $where['m_id'] = $id;
            $infos = D('House_village_park_coupons')->getShop($where);
//            if(!$infos['turn_url']){
                $url = C('config.site_url').D('House_village')->base_url.'pages/village/smartPark/shopCoupon?village_id='.$infos['village_id'].'&m_id='.$id;
                $data['turn_url']=$url;
//            }
            $res = D('Park_coupons_shop')->where($where)->data($data)->save();
            return $res;
        }else {
            $res = D('Park_coupons_shop')->add($data);
            $url = C('config.site_url').D('House_village')->base_url.'pages/village/smartPark/shopCoupon?village_id='.$data['village_id'].'&m_id='.$res;
            D('Park_coupons_shop')->where(['m_id'=>$res])->data(['turn_url'=>$url])->save();
            return $res;
        }
    }

    //获取店铺
    public function getShop($where,$field=true,$order='m_id desc')
    {
        $info = D('Park_coupons_shop')->where($where)->field($field)->order($order)->find();
        return $info;
    }
    //删除店铺
    public function delShop($where)
    {
        $res = D('Park_coupons_shop')->where($where)->data(['status'=>-1])->save();
        //删除商家下所有优惠券

        return $res;
    }
    //优惠券列表  --------------------------------------------------------------------
    public function getPackCouponsList($where=[],$order='c_id desc',$pageSize = 20)
    {
        $where['status'] = ['neq',-1];
        import('@.ORG.merchant_page');
        $count = D('Park_coupons')->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $list = D('Park_coupons')->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
            if(!$val['remark']){
                $val['remark'] = '--';
            }
        }
        $data['list'] = $list;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //添加修改优惠券
    public function subPackCoupons($data,$id='')
    {
        if($id){
            $data['edit_time'] = time();
            $res = D('Park_coupons')->where(['c_id'=>$id])->data($data)->save();
            return $res;
        }else{
            $data['add_time'] = time();
            $res = D('Park_coupons')->add($data);
            return $res;
        }
    }
    //获取优惠券详情
    public function getCouponsFind($where,$field='*',$order = 'c_id desc')
    {
        $data = D('Park_coupons')->where($where)->field($field)->order($order)->find();
        return $data;
    }
    //获取优惠券列表
    public function getCouponsList($where,$order='c_id desc')
    {
        $list = D('Park_coupons')->where($where)->order($order)->select();
        return $list;
    }
    //获取商家优惠券列表   ---------------------------商家优惠券------------------------------------
    public function getPackShopCouponsList($where=[],$order='id desc',$pageSize = 20)
    {
        $where['mc.status'] = ['neq',-1];
        import('@.ORG.merchant_page');
        $count = D('Park_shop_coupons')->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $left_join = "as mc left join ".C('DB_PREFIX').'park_coupons as c on `mc`.`cid`=`c`.`c_id`';
        $list = D('Park_shop_coupons')->join($left_join)->field('mc.*,c.c_title')->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
            if($val['status'] == 1){
                $val['qrcode_url'] = C('config.site_url').'/static/qrcode/park_qrcode/'.$val['id'].'.png';
//                $val['qrcode_url'] = 'http://www.weihubao.com/static/qrcode/park_qrcode/'.$val['id'].'.png';
            }else{
                $val['qrcode_url'] = '';
            }
        }
        $data['list'] = $list;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //添加商家优惠券
    public function subShopCoupons($data)
    {
        $where['village_id']= $data['village_id'];
        $where['cid']= $data['cid'];
        $where['mid']= $data['mid'];
        $where['status']= $data['status'];
        $info = $this->getShopCouponsFind($where);

        if($info){
            $data['residue_num'] = $info['residue_num']+$data['num'];
            $data['num'] = $info['num']+$data['num'];
            if(!$info['random_code']){
                $data['random_code'] = md5(time().rand(11111,99999));
            }
            if($data['status'] == 1){//静态码
                $url = C('config.site_url').D('House_village')->base_url.'pages/village/smartPark/parkingFee?coupons_id='.$info['id'].'&status='.$data['status'];
                $this->create_code($info['id'],$url);
            }
            $res = D('Park_shop_coupons')->where(['id'=>$info['id']])->data($data)->save();
            return $res;
        }else{
            $data['residue_num'] = $data['num'];
            $data['add_time'] = time();
            $data['random_code'] = md5(time().rand(11111,99999));
            $res = D('Park_shop_coupons')->add($data);
            if($data['status'] == 1){//静态码
                $url = C('config.site_url').D('House_village')->base_url.'pages/village/smartPark/parkingFee?coupons_id='.$res.'&status='.$data['status'].'&village_id='.$data['village_id'];
                $this->create_code($res,$url);
            }
            return $res;
        }
    }
    //获取商家优惠券详情
    public function getShopCouponsFind($where,$field='*',$order='id desc')
    {
        $shop_coupons = D('Park_shop_coupons')->where($where)->field($field)->order($order)->find();
        return $shop_coupons;
    }
    //购买记录
    public function payRecordList($where=[],$order='c_id desc',$pageSize = 20)
    {
        $where['mc.status'] = ['neq',-1];
        import('@.ORG.merchant_page');
        $left_join = "as mc left join ".C('DB_PREFIX').'park_coupons as c on `mc`.`cid`=`c`.`c_id`'."left join ".C('DB_PREFIX').'park_coupons_shop as m on `mc`.`mid`=`m`.`m_id`';
        $count = D('Park_shop_coupons')->join($left_join)->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $list = D('Park_shop_coupons')->join($left_join)->field('mc.*,c.c_title,m.m_name')->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
        }
        $map['village_id'] = $where['mc.village_id'];

        $total_pay_num = D('Park_shop_coupons')->where($map)->sum('num');
        $total_receivable_money = D('Park_shop_coupons')->where($map)->sum('receivable_money');
        $total_paid_money = D('Park_shop_coupons')->where($map)->sum('paid_money');
        $data['record'] = [
            'total_pay_num'=>$total_pay_num,
            'total_receivable_money'=>$total_receivable_money,
            'total_paid_money'=>$total_paid_money,
        ];
        $data['list'] = $list;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //进出场车辆   --------------------------------------------------------------------------------
    public function inOutCarList($where,$order='i.id desc',$pageSize = 20)
    {
        import('@.ORG.merchant_page');
//        $left_join = "as i left join ".C('DB_PREFIX').'house_village_parking_car as c on `i`.`car_number`=`c`.`province`.`c`.`car_number` left join '.C('DB_PREFIX').'house_village_parking_position as po on c.car_position_id=po.position_id left join '.C('DB_PREFIX').'out_park as o on i.car_number=o.car_number';
//        $field = 'i.*,c.car_position_id,c.car_user_name,c.car_user_phone,c.car_stop_num,po.position_num,o.out_time,o.in_time as oin_time';
        $left_join = "as i left join ".C('DB_PREFIX').'out_park as o on (i.order_id=o.order_id AND i.car_number=o.car_number)';
        $field = 'i.*,o.out_time as out_time,o.in_time as oin_time';
        if (isset($where['_string'])) {
            $where['_string'] .= "(o.order_id IS NULL OR o.in_time>0 OR o.out_time>0)";
        } else {
            $where['_string'] = "(o.order_id IS NULL OR o.in_time>0 OR o.out_time>0)";
        }
        $in_park_data = D('In_park')->join($left_join)->field($field)->where($where)->order($order)->group('i.order_id')->select();
        fdump_api([D('In_park')->_sql()],'inOutCarList_0526',1);
        $count = count($in_park_data);
        $p = new Page($count,$pageSize,'page');
        $in_park_data = D('In_park')->join($left_join)->field($field)->where($where)->order($order)->group('i.order_id')->limit($p->firstRow.','.$p->listRows)->select();
        if($in_park_data){
            foreach ($in_park_data as &$val){
                $maps = [];
                $maps['_string'] = "concat(`province`,'',`car_number`)='{$val['car_number']}'";
                $infos_car_info = D('House_village_parking_car')->where($maps)->field('car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
                $val['car_position_id'] = $infos_car_info['car_position_id'];
                $val['car_user_name'] = $infos_car_info['car_user_name'];
                $val['car_user_phone'] = $infos_car_info['car_user_phone'];
                $val['car_stop_num'] = $infos_car_info['car_stop_num'];
                $position_num = D('House_village_parking_position')->where(['position_id'=>$infos_car_info['car_position_id']])->getField('position_num');
                $val['position_num'] = $position_num;
                if($val['is_out'] == 1){
                    $standing_time_val = intval($val['out_time']-$val['in_time']);
                    if (!$standing_time_val) {
                        $standing_time = '无';
                    } elseif ($standing_time_val>0 && $standing_time_val<60) {
                        $standing_time = $standing_time_val . '秒';
                    } elseif ($standing_time_val>0 && $standing_time_val<3600) {
                        $standing_time = gmdate('i分钟s秒',$standing_time_val);
                    } elseif ($standing_time_val>0 && $standing_time_val<86400) {
                        $standing_time = gmdate('H小时i分钟s秒',$standing_time_val);
                    } elseif ($standing_time_val>0 && $standing_time_val>86400) {
                        $day = floor($standing_time_val/86400);
                        $standing_time_val = $standing_time_val - $day * 86400;
                        $standing_time = gmdate('H小时i分钟s秒',$standing_time_val);
                    } else {
                        $standing_time = round_number($standing_time_val/3600,2).'小时';
                    }
                    if($standing_time_val <= 0){
                        $standing_time = '无';
                    }
                    $val['standing_time'] = $standing_time;
                }else{
                    $val['standing_time'] = '--';
                }
                if($val['out_time']){
                    $val['out_time'] = date('Y-m-d H:i:s',$val['out_time']);
                }else{
                    $val['out_time'] = '--';
                }
                if($val['in_time']){
                    $val['in_time'] = date('Y-m-d H:i:s',$val['in_time']);
                }else{
                    $val['in_time'] = '--';
                }
                if($val['is_out'] == 1){
                    $val['in_out_time'] = $val['out_time'];
                    if ($val['in_time'] && $val['in_time']!='--' && $val['out_time'] && $val['out_time']!='--') {
                        $val['is_out_text'] = '进场-出场';
                    } elseif ($val['in_time'] && $val['in_time']!='--' && (!$val['out_time'] || $val['out_time']=='--')) {
                        $val['is_out_text'] = '进场-出场';
                        $val['out_time'] = "二次进场记录更替";
                    } else {
                        $val['is_out_text'] = '出场';
                    }
                }else{
                    $val['in_out_time'] = $val['in_time'];
                    $val['is_out_text'] = '进场';
                }
            }
        }
        $village_id = $where['i.park_id'];
        $s_time = strtotime(date('Y-m-d',time()));
        $e_time = strtotime(date('Y-m-d 23:59:59',time()));
        $in_where=[];
        $in_where['i.in_time'] = [['gt',$s_time],['lt',$e_time]];
        $in_where['i.park_id'] = $village_id;
        $in_where['i.is_out'] = 0;
        $in_count = D('In_park')->join($left_join)->where($in_where)->count();//今日进场

        $out_where=[];
        $out_where['o.out_time'] = [['gt',$s_time],['lt',$e_time]];
        $out_where['i.park_id'] = $village_id;
        $out_where['i.is_out'] = 1;
        $out_count = D('In_park')->join($left_join)->where($out_where)->count();//今日出场
        $where = [];
        $where['i.park_id'] = $village_id;
        $where['i.is_out'] = 0;
        $in_car_num = D('In_park')->join($left_join)->where($where)->count();//在场所有车辆
        $data['in_count'] = $in_count?$in_count:0;
        $data['out_count'] = $out_count?$out_count:0;
        $data['in_car_num'] = $in_car_num?$in_car_num:0;
        $data['list'] = $in_park_data;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //导出
    public function inOutCarExport($where,$village_name,$order='i.id desc')
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $left_join = "as i left join ".C('DB_PREFIX').'out_park as o on (i.order_id=o.order_id AND i.car_number=o.car_number)';
        $field = 'i.*,o.out_time as out_time,o.in_time as oin_time';
        if (isset($where['_string'])) {
            $where['_string'] .= "(o.order_id IS NULL OR o.in_time>0 OR o.out_time>0)";
        } else {
            $where['_string'] = "(o.order_id IS NULL OR o.in_time>0 OR o.out_time>0)";
        }
        $in_park_data = D('In_park')->join($left_join)->field($field)->where($where)->order($order)->group('i.order_id')->select();
        if(!$in_park_data){
            throw new Exception("暂无数据导出!");
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title =  $village_name. '车辆进出统计表格';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($in_park_data)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G','H','I'
        ];
        $pValue_arr = [
            '车辆号码',
            '车位号',
            '车主姓名',
            '车主手机号',
            '进出场动作',
            '进场时间',
            '出场时间',
            '所在场内时间',
            '当前订单',
        ];
        $key_length = count($pValue_arr) - 1;
        $carArrs = [];
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($in_park_data) . '条数据');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($in_park_data)) {
                $index = 2;
                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                foreach ($in_park_data as $value) {
                    if (isset($carArrs[$value['car_number']]) && $carArrs[$value['car_number']]) {
                        $infos_car_info = $carArrs[$value['car_number']];
                    } else {
                        $maps = [];
                        $maps['_string'] = "concat(`province`,'',`car_number`)='{$value['car_number']}'";
                        $infos_car_info = D('House_village_parking_car')->where($maps)->field('car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
                        if ($infos_car_info && isset($infos_car_info['car_position_id'])) {
                            $carArrs[$value['car_number']] = $infos_car_info;
                        }
                    }
                    $value['car_position_id'] = $infos_car_info['car_position_id']?$infos_car_info['car_position_id']:0;
                    $value['car_user_name'] = $infos_car_info['car_user_name']?$infos_car_info['car_user_name']:'';
                    $value['car_user_phone'] = $infos_car_info['car_user_phone']?$infos_car_info['car_user_phone']:'';
                    $value['car_stop_num'] = $infos_car_info['car_stop_num']?$infos_car_info['car_stop_num']:'';
                    $position_num = D('House_village_parking_position')->where(['position_id'=>$value['car_position_id']])->getField('position_num');
                    $value['position_num'] = $position_num?$position_num:'';
                    if($value['is_out'] == 1){
                        $standing_time_val = intval($value['out_time']-$value['in_time']);
                        if (!$standing_time_val) {
                            $standing_time = '无';
                        } elseif ($standing_time_val>0 && $standing_time_val<60) {
                            $standing_time = $standing_time_val . '秒';
                        } elseif ($standing_time_val>0 && $standing_time_val<3600) {
                            $standing_time = gmdate('i分钟s秒',$standing_time_val);
                        } elseif ($standing_time_val>0 && $standing_time_val<86400) {
                            $standing_time = gmdate('H小时i分钟s秒',$standing_time_val);
                        } elseif ($standing_time_val>0 && $standing_time_val>86400) {
                            $day = floor($standing_time_val/86400);
                            $standing_time_val = $standing_time_val - $day * 86400;
                            $standing_time = gmdate('H小时i分钟s秒',$standing_time_val);
                        } else {
                            $standing_time = round_number($standing_time_val/3600,2).'小时';
                        }
                        if($standing_time_val <= 0){
                            $standing_time = '无';
                        }
                        $value['standing_time'] = $standing_time;
                    }else{
                        $value['standing_time'] = '--';
                    }
                    if($value['out_time']){
                        $value['out_time'] = date('Y-m-d H:i:s',$value['out_time']);
                    }else{
                        $value['out_time'] = '--';
                    }
                    if($value['in_time']){
                        $value['in_time'] = date('Y-m-d H:i:s',$value['in_time']);
                    }else{
                        $value['in_time'] = '--';
                    }
                    if($value['is_out'] == 1){
                        $value['in_out_time'] = $value['out_time'];
                        if ($value['in_time'] && $value['in_time']!='--' && $value['out_time'] && $value['out_time']!='--') {
                            $value['is_out_text'] = '进场-出场';
                        } elseif ($value['in_time'] && $value['in_time']!='--' && (!$value['out_time'] || $value['out_time']=='--')) {
                            $value['is_out_text'] = '进场-出场';
                            $value['out_time'] = "二次进场记录更替";
                        } else {
                            $value['is_out_text'] = '出场';
                        }
                    }else{
                        $value['in_out_time'] = $value['in_time'];
                        $value['is_out_text'] = '进场';
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['car_number']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $value['position_num']);
                    if($value['car_user_phone']) {
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $value['car_user_name']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, '--');
                    }
                    if($value['car_user_phone']){
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['car_user_phone']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, '--');
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['is_out_text']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['in_time']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['out_time']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $standing_time);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['order_id']);

                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }
    //停车费用统计
    public function getParkCostList($where,$order='o.order_id desc',$pageSize = 20)
    {
        import('@.ORG.merchant_page');
        $left_join = "as o left join ".C('DB_PREFIX').'house_village_parking_car as c on `o`.`car_id`=`c`.`car_id` left join '.C('DB_PREFIX').'out_park as op on op.car_number=concat(`c`.`province`,\'\',`c`.`car_number`)';
        $field = 'o.order_id,o.money,o.cashier_id,o.car_type,o.pay_time,o.pay_type,c.car_position_id,c.car_user_name,c.car_user_phone,c.car_stop_num,c.car_number,op.order_id as order_num';
        $count = D('House_village_pay_order')->join($left_join)->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $pay_order = D('House_village_pay_order')->join($left_join)->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        if($pay_order){
            foreach ($pay_order as &$val){
                if($val['pay_time']){
                    $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
                }else{
                    $val['pay_time'] = '--';
                }
                if ($val['cashier_id'] && $val['pay_type']) {
                    $condition_table  = array(C('DB_PREFIX').'house_village_pay_cashier_order'=>'o',C('DB_PREFIX').'house_village_pay_type'=>'t');
                    $condition_where = " `o`.`village_id` = `t`.`village_id`  AND `o`.`pay_type` = `t`.`id`  AND `o`.`cashier_id` =".$val['cashier_id'];
                    $condition_field = '`t`.`name`';
                    $pay_type = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
                    $val['pay_type_name'] = $pay_type['name'] ? $pay_type['name'] : '';
                }
                if($val['pay_type'] == 0){
                    if ($val['fid']) {
                        $business_id = $val['fid'];
                    } else {
                        $business_id = $val['cashier_id'] ? $val['cashier_id'] : $val['order_id'];
                    }
                    $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_type'=> 'house_village_pay','business_id'=>$business_id))->find();
                    $val['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'],$online_pay_type['is_mobile_pay'],$online_pay_type['paid']);

                    //$val['pay_type_text'] = '扫码支付';
                }
//                else{
//                    $val['pay_type_text'] = '现金支付';
//                }
                switch($val['car_type']){
                    case 'temporary_type':
                        $val['car_type_text'] = '临时车缴费';
                        break;
                    case 'month_type':
                        $val['car_type_text'] = '月租车充值';
                        break;
                    case 'stored_type':
                        $val['car_type_text'] = '停车卡储值';
                        break;
                }
                if(!$val['order_num']){
                    $val['order_num'] = $val['order_id'];
                }
                if(!$val['car_user_name']){
                    $val['car_user_name'] = '--';
                }
                if(!$val['car_user_name']){
                    $val['car_user_phone'] = '--';
                }
            }
        }
        $map = [];
        $map['o.village_id'] = $where['o.village_id'];
        $map['o.order_type'] = 'park';
        $map['o.paid'] = 1;
        $money = D('House_village_pay_order')->join($left_join)->where($map)->sum('money');
        $data['money'] = $money;
        $data['list'] = $pay_order;
        $data['pagebar'] = $p->show();
        return $data;
    }

    //停车费用统计(修改)
    public function getParkCostList1($where,$order='o.order_id desc',$pageSize = 20)
    {
        if(!isset($where['_string']) || empty($where['_string'])){
            $where[] .=' o.car_id!="" or o.car_number!=""';
        }
        import('@.ORG.merchant_page');
        $left_join = "as o left join ".C('DB_PREFIX').'house_village_parking_car as c on `o`.`car_id`=`c`.`car_id` ';
        $count = D('House_village_pay_order')->join($left_join)->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $field='o.*';
        $pay_order = D('House_village_pay_order')->join($left_join)->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        if($pay_order){
            foreach ($pay_order as &$val){
                if($val['pay_time']){
                    $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
                }else{
                    $val['pay_time'] = '--';
                }
                if ($val['cashier_id'] && $val['pay_type']) {
                    $condition_table  = array(C('DB_PREFIX').'house_village_pay_cashier_order'=>'o',C('DB_PREFIX').'house_village_pay_type'=>'t');
                    $condition_where = " `o`.`village_id` = `t`.`village_id`  AND `o`.`pay_type` = `t`.`id`  AND `o`.`cashier_id` =".$val['cashier_id'];
                    $condition_field = '`t`.`name`';
                    $pay_type = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
                    $val['pay_type_name'] = $pay_type['name'] ? $pay_type['name'] : '';
                }
                if($val['pay_type'] == 0){
                    if ($val['fid']) {
                        $business_id = $val['fid'];
                    } else {
                        $business_id = $val['cashier_id'] ? $val['cashier_id'] : $val['order_id'];
                    }
                    $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_type'=> 'house_village_pay','business_id'=>$business_id))->find();
                    $val['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'],$online_pay_type['is_mobile_pay'],$online_pay_type['paid']);

                    //$val['pay_type_text'] = '扫码支付';
                }
//                else{
//                    $val['pay_type_text'] = '现金支付';
//                }
                $val['stored_balance']='--';
                switch($val['car_type']){
                    case 'temporary_type':
                        $val['car_type_text'] = '临时车缴费';
                        break;
                    case 'month_type':
                        $val['car_type_text'] = '月租车充值';
                        break;
                    case 'stored_type':
                        $val['stored_balance'] = D('House_village_car_access_record')->field('car_number,pay_order_id,stored_balance')->where(array('pay_order_id'=> $val['order_id'],'car_number'=>$val['car_number'],'accessType'=>2))->find();
                        $val['car_type_text'] = '停车卡储值';
                        break;
                }
                if(!$val['order_num']){
                    $val['order_num'] = $val['order_id'];
                }
                if(!$val['car_user_name']){
                    $val['car_user_name'] = '--';
                }
                if(!$val['car_user_name']){
                    $val['car_user_phone'] = '--';
                }
            }
        }
        $where2=' o.car_id!="" or o.car_number!=""';
        $map = [];
        $map['o.village_id'] = $where['o.village_id'];
        $map['o.order_type'] = 'park';
        $map['o.paid'] = 1;
        $money = D('House_village_pay_order')->join($left_join)->where($where2)->where($map)->sum('money');
        $data['money'] = $money;

        $map1 = [];
        $map1 = $map;
        if ($where['o.car_type']){
            $map1['o.car_type'] = $where['o.car_type'];
        }
        if($where['o.pay_type']){
            $map1['o.pay_type'] = $where['o.pay_type'];
        }
        if( $where['o.pay_time']){
            $map1['o.pay_time']=$where['o.pay_time'];
        }
        $money1 = D('House_village_pay_order')->join($left_join)->where($where2)->where($map1)->sum('money');
        $data['money1'] = $money1;
        $data['list'] = $pay_order;
        $data['pagebar'] = $p->show();
        return $data;
    }
    //停车统计导出
    public function parkCostExport($where,$village_name,$order='o.order_id desc')
    {
        $left_join = "as o left join ".C('DB_PREFIX').'house_village_parking_car as c on `o`.`car_id`=`c`.`car_id` left join '.C('DB_PREFIX').'out_park as op on c.car_number=op.car_number';
        $field = 'o.order_id,o.money,o.car_type,o.pay_time,o.pay_type,c.car_position_id,c.car_user_name,c.car_user_phone,c.car_stop_num,c.car_number,op.order_id as order_num';

        $pay_order = D('House_village_pay_order')->join($left_join)->where($where)->field($field)->order($order)->select();
        if(!$pay_order){
            throw new Exception("暂无数据导出!");
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $village_name . '车辆停车费用统计表格';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($pay_order)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G','H',
        ];
        $pValue_arr = [
            '订单编号',
            '车辆号码',
            '缴费金额',
            '缴费类型',
            '支付方式',
            '支付时间',
            '业主名',
            '联系方式',
        ];
        $key_length = count($pValue_arr) - 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($pay_order) . '条数据');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($pay_order)) {
                $index = 2;
                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                foreach ($pay_order as $value) {
                    if($value['pay_time']){
                        $value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
                    }else{
                        $value['pay_time'] = '--';
                    }
                    if($value['pay_type'] == 0){
                        $value['pay_type_text'] = '扫码支付';
                    }else{
                        $value['pay_type_text'] = '现金支付';
                    }
                    switch($value['car_type']){
                        case 'temporary_type':
                            $value['car_type_text'] = '临时车缴费';
                            break;
                        case 'month_type':
                            $value['car_type_text'] = '月租车充值';
                            break;
                        case 'stored_type':
                            $value['car_type_text'] = '停车卡储值';
                            break;
                    }
                    if(!$value['order_num']){
                        $value['order_num'] = $value['order_id'];
                    }
                    if(!$value['car_user_name']){
                        $value['car_user_name'] = '--';
                    }
                    if(!$value['car_user_name']){
                        $value['car_user_phone'] = '--';
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['order_num']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $value['car_number']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $value['money']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['car_type_text']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['pay_type_text']);

                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['pay_time']);
                    if($value['car_user_phone']) {
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['car_user_name']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, '--');
                    }
                    if($value['car_user_phone']){
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['car_user_phone']);
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, '--');
                    }
                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }
    //获取停车优惠券列表（api）
    public function getParkCarCouponsList($post)
    {
        $where['sc.village_id'] = $post['village_id'];
        $where['sc.mid'] = $post['m_id'];
        $where['sc.status'] = ['neq',-1];
        $where['sc.residue_num'] = ['gt',0];
        $left_join = 'as sc left join '.C('DB_PREFIX')."park_coupons as p on sc.cid=p.c_id";
        $field = 'sc.mid,sc.id,sc.random_code,sc.residue_num,sc.status,sc.village_id,p.c_title';
        $list = D('Park_shop_coupons')->join($left_join)->field($field)->where($where)->select();
       //  print_r(D('Park_shop_coupons')->_sql());die;
        $count = count($list);
        $total_count = 0;
        foreach ($list as &$val){
            if($val['status'] == 2){//动态码
                $url = C('config.site_url').D('House_village')->base_url.'pages/village/smartPark/parkingFee?coupons_id='.$val['id'].'&random_code='.$val['random_code'].'&status='.$val['status'].'&village_id='.$val['village_id'];
                $val['qrcode_url'] = $this->create_code($val['random_code'],$url);
            }else{
                $val['qrcode_url'] = C('config.site_url').'/upload/qrcode/park_qrcode/'.$val['id'].'.png';
            }
            $total_count += $val['residue_num'];
        }
        $data['count'] = $count;
        $data['total_count'] = $total_count;
        $data['list'] = $list;
        return $data;
    }
    //
    public function receiveParkCar($coupons_id)
    {
        $info = D('Park_shop_coupons')->where(['id'=>$coupons_id])->find();
        if($info['status'] == 2){
            $data['random_code'] = md5(time().rand(11111,99999));
        }
        $data['residue_num'] = $info['residue_num']-1;	//剩余数减1
        $data['buy_num'] = $info['buy_num']+1;	//售出数加1
        $res = D('Park_shop_coupons')->where(['id'=>$coupons_id])->data($data)->save();
        if($info['status'] == 2)
        {
            unlink('./upload/qrcode/park_qrcode/'.$info['random_code'].'.png');
            $url = C('config.site_url') . D('House_village')->base_url.'pages/village/smartPark/parkingFee?coupons_id=' . $info['id'] . '&random_code=' . $data['random_code'] . '&status=' . $info['status'].'&village_id='.$info['village_id'];
            $qrcode_url = $this->create_code($data['random_code'], $url);
        }
        return $res;
    }
    //循环获取动态二维码
    public function getParkCarCoupon($id,$random_code)
    {
        $data= [];
        $info = D('Park_shop_coupons')->where(['id'=>$id])->find();
        if($info){
            if($info['random_code'] != $random_code) {
                $url = C('config.site_url') . D('House_village')->base_url.'pages/village/smartPark/parkingFee?coupons_id=' . $info['id'] . '&random_code=' . $info['random_code'] . '&status=' . $info['status'].'&village_id='.$info['village_id'];
                $qrcode_url = $this->create_code($info['random_code'], $url);
            }else{
                $qrcode_url = C('config.site_url').'/upload/qrcode/park_qrcode/'.$random_code.'.png';
            }
            $data['qrcode_url'] = $qrcode_url;
        }
        return $data;
    }
    //生成二维码
    public function create_code($code_no,$url)
    {
        import('@.ORG.phpqrcode');
        $filename = 'upload/qrcode/park_qrcode/';
        if(!file_exists($filename)){
            mkdir($filename,0777,true);
        }
        $errorLevel = "L";
        $size = "8";
        $filename = $filename.$code_no.'.png';
        QRcode::png($url, $filename, $errorLevel, $size);
        $QR = $filename;        //已经生成的原始二维码图片文件
        $data =  C('config.site_url').'/'.$QR;
        return $data;
    }
}
