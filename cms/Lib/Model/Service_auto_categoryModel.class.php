<?php
include(WEB_PATH.'plugin/paotui/Common/convert.php');
class Service_auto_categoryModel extends Model{
    
    // 获得指定商家列表
    public function getAutoStoreByCid($cid,$isSystem = false,$long=0,$lat=0){
        /*封装类*/
        $convert = new Convert();

        $returnArr = [];
         // 店铺类型 查询表名
        $condition_table = array(C('DB_PREFIX').'service_auto_goods'=>'b',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant_store_shop'=>'p',C('DB_PREFIX').'merchant'=>'m');

        // 店铺类型 查询字段
        $condition_field = "p.delivery_radius,p.delivery_range_type,p.delivery_range_polygon,b.id,b.cid,b.delivery_fee,`m`.`name` AS merchant_name,`m`.`merchant_end_time`,`s`.`store_id`, `s`.`logo`, `s`.`name`, `s`.`adress`, `s`.`kefu_phone`,`s`.`long`,`s`.`lat`, `s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`,`s`.`area_id`,`s`.`city_id`,`s`.`province_id`";

        // 店铺类型 查询条件
        $where = [];
        $where['b.is_del'] = 0;
        $where['s.status'] = 1;
        $where['m.status'] = 1;
        $where['_string'] = "`s`.`mer_id`=`m`.`mer_id` AND `b`.`store_id`=`s`.`store_id` AND `s`.`store_id`=`p`.`store_id`";

        if (!$isSystem) { 
            $where['_string'] .= " AND (`m`.`merchant_end_time` = 0 OR `m`.`merchant_end_time`>".time().")";
        }

        $where['b.cid'] = $cid;
        $store_list = M()->field($condition_field)->table($condition_table)->where($where)->order("`b`.`id` DESC")->select();
        $store_ids = array_column($store_list, 'store_id');
        if(!empty($store_ids)){
            $time_list = PD('Merchant_store_open_time')->get_all_time_by_store($store_ids);
        }

        // 用户查询月售
        $condition_table_count = array(C('DB_PREFIX').'service_offer'=>'so',C('DB_PREFIX').'service_auto_goods'=>'b',C('DB_PREFIX').'service_user_publish'=>'p',C('DB_PREFIX').'service_user_publish_buy'=>'pb');

        // 本月时间范围条件
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $end_time = time();
        $where = [];
        $where['so.add_time'] = ['between',[$beginThismonth,$end_time]];
        $where['_string'] = "`pb`.`publish_id`=`p`.`publish_id` AND `p`.`auto_goods_id`=`b`.`id` AND so.publish_id= p.publish_id";

        $longlatClass = new longlat();
        $returnStoreList = [];
        if($isSystem){
            foreach($store_list as &$value){
                $tmp_store = [];
                $tmp_store['cid'] = $value['cid'];
                $tmp_store['id'] = $value['id'];
                $tmp_store['delivery_fee'] = $value['delivery_fee'];
                $tmp_store['store_id'] = $value['store_id'];
                $tmp_store['name'] = $value['name'];
                $tmp_store['merchant_name'] = $value['merchant_name'];
                $tmp_store['long'] = $value['long'];
                $tmp_store['lat'] = $value['lat'];
                $tmp_store['lng'] = $value['long'];
                $tmp_store['area_id'] = $value['area_id'];
                $tmp_store['city_id'] = $value['city_id'];
                $tmp_store['province_id'] = $value['province_id'];
                $tmp_store['adress'] = $value['adress'];
                $tmp_store['kefu_phone'] = $value['kefu_phone'];
                $tmp_store['logo'] = replace_file_domain($value['logo']);

                if(!$isSystem){
                    // 营业时间 判断是否休息
                    $tmp_store['is_close'] = 1;
                    if (PD('Merchant_store_shop')->checkTime($value)) {
                        $tmp_store['is_close'] = 0;
                    }
                    $tmp_store['is_close'] = $tmp_store['is_close'] ? $tmp_store['is_close'] : $value['is_close'];
                    if ($tmp_store['is_close']) {
                        continue;
                    }
                }

                if($isSystem){
                    $tmp_store['open_time_list'] = $time_list[$value['store_id']];
                    $tmp_store['merchant_end_time'] = $value['merchant_end_time'];
                }

                // 店铺id
                $where['b.store_id'] = intval($tmp_store['store_id']);
                // 查询月售
                $mouth_sale_count = M()->table($condition_table_count)->where($where)->count();
                $tmp_store['mouth_sale_count'] = intval($mouth_sale_count);

                $returnStoreList[] = $tmp_store;
            }
        }
        else{
            $point['lng']=$long;
            $point['lat']=$lat;
            foreach($store_list as &$value){
                $longlat = $longlatClass->baiduToGcj02($value['lat'],$value['long']);
                $formartLng = $longlat['lng'];
                $formartLat = $longlat['lat'];
                /*配送范围的类型（0:半径距离，1：自定义）*/
                if($value['delivery_range_type']==0){
                    if($value['delivery_radius']>0){
                        /*取两个坐标的距离*/
                        $dist= $convert->distance($point['lat'], $point['lng'], $value['lat'], $value['long']);
                        /*配送距离比较*/
                        if($value['delivery_radius']>$dist || $dist == 0){
                            $tmp_store = [];
                            $tmp_store['cid'] = $value['cid'];
                            $tmp_store['id'] = $value['id'];
                            $tmp_store['delivery_fee'] = $value['delivery_fee'];
                            $tmp_store['store_id'] = $value['store_id'];
                            $tmp_store['name'] = $value['name'];
                            $tmp_store['merchant_name'] = $value['merchant_name'];
                            $tmp_store['long'] = $value['long'];
                            $tmp_store['lat'] = $value['lat'];
                            $tmp_store['lng'] = $value['long'];
                            $tmp_store['formartLat'] = $formartLat;
                            $tmp_store['formartLng'] = $formartLng;
                            $tmp_store['area_id'] = $value['area_id'];
                            $tmp_store['city_id'] = $value['city_id'];
                            $tmp_store['province_id'] = $value['province_id'];
                            $tmp_store['adress'] = $value['adress'];
                            $tmp_store['kefu_phone'] = $value['kefu_phone'];
                            $tmp_store['logo'] = replace_file_domain($value['logo']);
                            if(!$isSystem){
                                // 营业时间 判断是否休息
                                $tmp_store['is_close'] = 1;
                                if (PD('Merchant_store_shop')->checkTime($value)) {
                                    $tmp_store['is_close'] = 0;
                                }
                                /* $tmp_store['is_close'] = $tmp_store['is_close'] ? $tmp_store['is_close'] : $value['is_close'];*/
                                $tmp_store['is_close'] = $tmp_store['is_close'] ? $tmp_store['is_close'] : 0;
                                if ($tmp_store['is_close']) {
                                    continue;
                                }
                            }
                            if($isSystem){
                                $tmp_store['open_time_list'] = $time_list[$value['store_id']];
                                $tmp_store['merchant_end_time'] = $value['merchant_end_time'];
                            }

                            // 店铺id
                            $where['b.store_id'] = intval($tmp_store['store_id']);
                            // 查询月售
                            $mouth_sale_count = M()->table($condition_table_count)->where($where)->count();
                            $tmp_store['mouth_sale_count'] = intval($mouth_sale_count);
                        }
                    }
                }
                else{
                    if ($value['delivery_range_polygon'] != '') {
                        $v_delivery_range_polygon = str_replace('POLYGON((', '', $value['delivery_range_polygon']);
                        $v_delivery_range_polygon = str_replace('))', '', $v_delivery_range_polygon);
                        $v_polygon = explode(',', $v_delivery_range_polygon);
                        /*取参数，比较是否在多边形配送区域内*/
                        foreach ($v_polygon as $v_k => $v_po) {
                            $v_arr = explode(' ', $v_po);
                            $v_polygon_arr[$v_k]['lng'] = $v_arr[0];
                            $v_polygon_arr[$v_k]['lat'] = $v_arr[1];
                        }
                        $res=$convert->is_point_in_polygon($point,$v_polygon_arr);
                        if($res){
                            /*在多边形配送区域内*/
                            $tmp_store = [];
                            $tmp_store['cid'] = $value['cid'];
                            $tmp_store['id'] = $value['id'];
                            $tmp_store['delivery_fee'] = $value['delivery_fee'];
                            $tmp_store['store_id'] = $value['store_id'];
                            $tmp_store['name'] = $value['name'];
                            $tmp_store['merchant_name'] = $value['merchant_name'];
                            $tmp_store['long'] = $value['long'];
                            $tmp_store['lat'] = $value['lat'];
                            $tmp_store['lng'] = $value['long'];
                            $tmp_store['formartLat'] = $formartLat;
                            $tmp_store['formartLng'] = $formartLng;
                            $tmp_store['area_id'] = $value['area_id'];
                            $tmp_store['city_id'] = $value['city_id'];
                            $tmp_store['province_id'] = $value['province_id'];
                            $tmp_store['adress'] = $value['adress'];
                            $tmp_store['kefu_phone'] = $value['kefu_phone'];
                            $tmp_store['logo'] = replace_file_domain($value['logo']);

                            if(!$isSystem){
                                // 营业时间 判断是否休息
                                $tmp_store['is_close'] = 1;
                                if (PD('Merchant_store_shop')->checkTime($value)) {
                                    $tmp_store['is_close'] = 0;
                                }
                                $tmp_store['is_close'] = $tmp_store['is_close'] ? $tmp_store['is_close'] : $value['is_close'];
                                if ($tmp_store['is_close']) {
                                    continue;
                                }
                            }

                            if($isSystem){
                                $tmp_store['open_time_list'] = $time_list[$value['store_id']];
                                $tmp_store['merchant_end_time'] = $value['merchant_end_time'];
                            }

                            // 店铺id
                            $where['b.store_id'] = intval($tmp_store['store_id']);
                            // 查询月售
                            $mouth_sale_count = M()->table($condition_table_count)->where($where)->count();
                            $tmp_store['mouth_sale_count'] = intval($mouth_sale_count);
                        }
                    }

                }
                if(!empty($tmp_store)){
                    $returnStoreList[] = $tmp_store;
                }
            }
        }
       // fdump($returnStoreList,"ddeerrs",1);
        $returnArr['store_list'] = $returnStoreList;
        return $returnArr;
    }

    // 获得指定商家列表
    public function getAutoStoreAll($mer_id=0){
        $returnArr = [];
         // 店铺类型 查询表名
        $condition_table = array(C('DB_PREFIX').'service_auto_goods'=>'b',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m');

        // 店铺类型 查询字段
        $condition_field = "b.id,b.cid,b.delivery_fee,`m`.`name` AS merchant_name,`m`.`merchant_end_time`,`s`.`store_id`, `s`.`logo`, `s`.`name`, `s`.`adress`, `s`.`kefu_phone`,`s`.`long`,`s`.`lat`, `s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`";

        // 店铺类型 查询条件
        $where = [];
        $where['b.is_del'] = 0;
        $where['_string'] = "`s`.`mer_id`=`m`.`mer_id` AND `b`.`store_id`=`s`.`store_id`";

        if ($mer_id) {
            $where['s.mer_id'] = $mer_id;
        }

        $store_list = M()->field($condition_field)->table($condition_table)->where($where)->order("`b`.`id` DESC")->select();
       
        $returnStoreList = [];
        foreach($store_list as &$value){
            $tmp_store = [];
            $tmp_store['cid'] = $value['cid'];
            $tmp_store['id'] = $value['id'];
            $tmp_store['delivery_fee'] = $value['delivery_fee'];
            $tmp_store['store_id'] = $value['store_id'];
            $tmp_store['name'] = $value['name'];
            $tmp_store['merchant_name'] = $value['merchant_name'];
            $tmp_store['long'] = $value['long'];
            $tmp_store['lat'] = $value['lat'];
            $tmp_store['adress'] = $value['adress'];
            $tmp_store['kefu_phone'] = $value['kefu_phone'];
            $tmp_store['logo'] = replace_file_domain($value['logo']);
            $tmp_store['merchant_end_time'] = $value['merchant_end_time'];
            
            $returnStoreList[$value['store_id']] = $tmp_store;
        }
        $returnArr['store_list'] = array_values($returnStoreList);
        return $returnArr;
    }

    // 获得指定商家订单列表
    public function getOrderList($where,$isSystem=true,$pageSize = 20){
        $returnArr = [];
         // 店铺类型 查询表名
        $condition_table = array(C('DB_PREFIX').'merchant_store'=>'a',C('DB_PREFIX').'service_offer'=>'so',C('DB_PREFIX').'service_auto_goods'=>'b',C('DB_PREFIX').'service_user_publish'=>'p',C('DB_PREFIX').'service_user_publish_buy'=>'pb', C('DB_PREFIX').'user'=>'u');

        // 店铺类型 查询字段
        $condition_field = "b.id,b.cid,b.store_id,so.*, pb.*,u.nickname,u.phone, p.order_sn as p_order_sn";


        $where['_string'] = "`pb`.`publish_id`=`p`.`publish_id` AND `p`.`auto_goods_id`=`b`.`id` AND so.publish_id= p.publish_id and u.uid = so.uid and a.store_id=b.store_id";

        $count = M()->field($condition_field)->table($condition_table)->where($where)->count();
        //fdump(M()->getLastSql(),"dengyuanhui3333333",1);
        if($isSystem){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        $p = new Page($count, $pageSize);

        $order_list = M()->field($condition_field)->table($condition_table)->where($where)->limit($p->firstRow . ',' . $p->listRows)->order("`p`.`publish_id` DESC")->select();

        if ($order_list) {
            // 统计
            $count_field = 'sum(pb.total_price) as total_price,sum(pb.tip_price) as tip_price,sum(pb.weight_price) as weight_price,sum(pb.basic_distance_price) as basic_distance_price,sum(pb.distance_price) as distance_price,sum(pb.estimate_goods_price) as estimate_goods_price';
            $count_list = M()->field($count_field)->table($condition_table)->where($where)->find();

            $count_list['delivery_fee_total'] = getFormatNumber($count_list['distance_price'] + $count_list['basic_distance_price']);

            // 获得店铺名 商家名
            $store_ids = array_column($order_list, 'store_id');
            $storeNameArray = [];
            if(!empty($store_ids)){
                $condition_table = array(C('DB_PREFIX')._view('merchant_store')=>'s',C('DB_PREFIX')._view('merchant')=>'m');
                $store_where['s.store_id'] =  ['in',$store_ids];
                $store_where['_string'] =  's.mer_id = m.mer_id';
                $store_list = M()->field('s.*,m.*,s.name as store_name,m.name as merchant_name')->table($condition_table)->where($store_where)->select();
                foreach ($store_list as $key => $value) {
                    $storeNameArray[$value['store_id']] = $value;
                }
            }

            foreach($order_list as &$value){
                $value['store_name'] = $storeNameArray[$value['store_id']]['store_name'];
                $value['merchant_name'] = $storeNameArray[$value['store_id']]['merchant_name'];
                $value['delivery_fee_total'] = getFormatNumber($value['basic_distance_price']+$value['distance_price']);
               
            }
        }

        $returnArr['order_list'] = $order_list;
        $returnArr['count_list'] = $count_list;
        $returnArr['pagebar'] = $p->show();
        return $returnArr;
    }


    // 获得指定商家订单列表
    // 批发市场
    public function autoStoreOrderExport(&$objProps,&$objExcel,$param)
    { 
        
        $title = L_('帮我买自定义店铺订单列表');
         
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        //搜索
        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'order_sn') {
                $where['p.order_sn'] = ['like',"%".$param['keyword']."%"];
            }  else if ($param['searchtype'] == 'nickname') {
                $where['u.nickname'] = ['like',"%".$param['keyword']."%"];
            } else if ($param['searchtype'] == 'phone') {
                $where['u.phone'] = ['like',"%".$param['keyword']."%"];
            }
        }

        if(!empty($param['start_time'])&&!empty($param['end_time'])){
            $period = array(strtotime($param['start_time']." 00:00:00"),strtotime($param['end_time']." 23:59:59"));
            $where['so.add_time'] = ['between',[$period[0],$period[1]]];
        }

        // 店铺搜索
        if(!empty($param['store_id'])){
            $where['b.store_id'] = intval($param['store_id']);
        }else{
            if ($param['mer_id']) {
                $store_ids = array_column(M('Merchant_store')->where(['mer_id'=>$param['mer_id']])->select(), 'store_id');
                $where['b.store_id'] = ['in',$store_ids]; // 指定店铺
            }else{
                $where['b.store_id'] = ['gt',0]; // 指定店铺
            }
        }

        // 已完成
        $where['p.status'] = 4;

         // 店铺类型 查询表名
        $condition_table = array(C('DB_PREFIX').'service_offer'=>'so',C('DB_PREFIX').'service_auto_goods'=>'b',C('DB_PREFIX').'service_user_publish'=>'p',C('DB_PREFIX').'service_user_publish_buy'=>'pb', C('DB_PREFIX').'user'=>'u');

        // 店铺类型 查询字段
        $condition_field = "b.id,b.cid,b.store_id,so.*, pb.*,u.nickname,u.phone, p.order_sn as p_order_sn";


        $where['_string'] = "`pb`.`publish_id`=`p`.`publish_id` AND `p`.`auto_goods_id`=`b`.`id` AND so.publish_id= p.publish_id and u.uid = so.uid";

        $count = M()->field($condition_field)->table($condition_table)->where($where)->count();

        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();

            $title_arr = array(
                array(20,L_('订单编号')),
                array(20,L_('用户')),
                array(20,L_('商家/店铺')),
                array(20,L_('总价')),
                array(20,L_('小费')),
                array(20,L_('重量费用')),
                array(20,L_('商家基础运费')),
                array(20,L_('超出基础运费')),
                array(20,L_('预估商品费用')),
                array(20,L_('总运费')),
                array(20,L_('下单时间')),
            );

            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            // 查询数据
            $order_list = M()->field($condition_field)->table($condition_table)->where($where)->limit($i * 1000 . ',1000')->order("`p`.`publish_id` DESC")->select();

            if (!empty($order_list)) {
                $index = 1;
                foreach ($order_list as $value) {
                    $index++;
                    $column = 0;
                    // ID
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['p_order_sn']);
                    // 订单编号
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['nickname']);
                    // 商家名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['merchant_name'].'/'.$value['store_name']);
                    // 商品名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['total_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['tip_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['weight_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['basic_distance_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['distance_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['estimate_goods_price']);
                    $delivery_fee_total = getFormatNumber($value['distance_price']+$value['basic_distance_price']);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $delivery_fee_total);
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, date('Y-m-d H:i:s',$value['add_time']));
                }
            }
            sleep(2);
        }
    } 
}
?>