<?php
/*社区团购 时间：2018年10月30日15:28:01*/
class Village_groupModel extends Model{
    public $font_url = './static/fonts/apple_lihei_bold.otf';
    public $font_url_sc = './static/fonts/PingFang Regular.ttf';
    public $font_url_mid = './static/fonts/PingFang Medium.ttf';
    public $font_url_bold = './static/fonts/PingFang Bold.ttf';
    public $order_status = array(
        '0'=>'待付款',
        '1'=>'已支付',
        '2'=>'已消费',
        '3'=>'部分消费',
        '4'=>'已退款',
        '5'=>'已发货',
        '6'=>'团长收货 待自提',
        '7'=>'部分商品发货',
        '8'=>'部分商品收货，待自提',
        '10'=>'超时未支付',
        '11'=>'已取消',
        '12'=>'已退款',
        '13'=>'已评价',
        );
    public $group_status = array(
        '0'=>'未成团',
        '1'=>'已成团',
        '2'=>'已超时',
    );

    public $goods_status = array(
        '0'=>'禁止',
        '1'=>'正常',
        '2'=>'已售罄',
        '3'=>'已超时',
        '4'=>'已删除',
        '5'=>'已完成',
    );
    public $deliver_status = array(
        '0'=>'未配送',
        '1'=>'已配送',
        '2'=>'已送达',
        '3'=>'已取货',
        '4'=>'已消费',
        '5'=>'已评价',
    );
    // 相关参数
    public $choose_date = array(
        'week' => array(
            '1' => '周一',
            '2' => '周二',
            '3' => '周三',
            '4' => '周四',
            '5' => '周五',
            '6' => '周六',
            '7' => '周日',
        ),
        'date' => array(
            '1' => 1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,
            '11'=>11,'12'=>12,'13'=>13,'14'=>14,'15'=>15,'16'=>16,'17'=>17,'18'=>18,'19'=>19,'20'=>20,
            '21'=>21,'22'=>22,'23'=>23,'24'=>24,'25'=>25,'26'=>26,'27'=>27,'28'=>28,'29'=>29,'30'=>30
        ),
        'limit_time' => array(
            '0' => '00:00','1'=>'01:00','2'=>'02:00','3'=>'03:00','4'=>'04:00','5'=>'05:00',
            '6'=>'06:00','7' => '07:00','8'=>'08:00','9'=>'09:00','10'=>'10:00','11'=>'11:00',
            '12'=>'12:00','13'=>'13:00','14' => '14:00','15'=>'15:00','16'=>'16:00','17'=>'17:00',
            '18'=>'18:00','19'=>'19:00','20'=>'20:00','21' => '21:00','22'=>'22:00','23'=>'23:00'
        ),
        'date_msg' => array(
            '1' => '1号','2'=>'2号','3'=>'3号','4'=>'4号','5'=>'5号','6'=>'6号','7'=>'7号','8'=>'8号','9'=>'9号','10'=>'10号',
            '11'=>'11号','12'=>'12号','13'=>'13号','14'=>'14号','15'=>'15号','16'=>'16号','17'=>'17号','18'=>'18号','19'=>'19号','20'=>'20号',
            '21'=>'21号','22'=>'22号','23'=>'23号','24'=>'24号','25'=>'25号','26'=>'26号','27'=>'27号','28'=>'28号','29'=>'29号','30'=>'30号'
        )
    );

    public function get_label(){
        $label = M('Village_group_label')->where(array('status'=>1))->getField('id,name,image,color,name,type,color2');

        return $label;
    }

    /**
     * 已迁移  app\villageGroup\model\service\VillageGroupOrderService::addLog($param)
     *
     * @author: zt
     * @date: 2023/02/06
     */
    public function add_log($param){
        if (empty($param['order_id']))
            return false;
        if ($order = M('Village_group_order')->field(true)->where(array('order_id' => $param['order_id']))->find()) {
            $data = array('dateline' => time());
            $data['order_id'] = intval($param['order_id']);
            $data['goods_id'] = intval($param['goods_id']);
            $data['status'] = isset($param['status']) ? intval($param['status']) : 0;
            $data['deliver_status'] = isset($param['deliver_status']) ? intval($param['deliver_status']) : 0;
            $data['phone'] = isset($param['phone']) ? $param['phone'] : '';
            $data['name'] = isset($param['name']) ? $param['name'] : '';
            $data['note'] = isset($param['note']) ? $param['note'] : '';
            $data['from_type'] = isset($param['from_type']) ? intval($param['from_type']) : 0;
            $data['date_num'] = isset($param['date_num']) ? intval($param['date_num']) : 1;

            $now_user = M('User')->field(true)->where(array('uid' => $order['uid']))->find();
            if ($now_user['openid'] && $data['status'] != 0) {
                $status = $this->order_status;
                $href = C('config.site_url') . '/wap.php?c=Village_group&a=orderDetail&order_id=' . $order['order_id'] ;
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' =>  C('config.village_group_alias').'订单',
                    'OrderSn' => $order['real_orderid'],
                    'OrderStatus' => $status[$data['status']],
                    'remark' => date('Y-m-d H:i:s')
                ), $order['mer_id']);
            }

//            if ($data['status'] != 0) {
//                import('@.ORG.Apppush');
//                $order['status'] = $data['status'];
//                $apppush = new Apppush();
//                $apppush->send($order, 'shop');
//            }



//            if (intval($data['status']) == 2 && $order['platform'] == 0) {
//                //店员接单的时候打印，只打印本平台的单子，饿了么，美团的单子不打印
//                $printHaddle = new PrintHaddle();
//                $printHaddle->printit($order['order_id'], 'shop_order', 5);
//            }
            return M('Village_group_order_log')->add($data);
        }
    }

    public function get_category(){
        $cat_fid = M('Village_group_goods')->field('cat_fid')->where(array('status'=>1))->group('cat_fid')->select();
        foreach ($cat_fid as $item) {
            $arr[] = $item['cat_fid'];
        }
        $where['id'] = array('in',$arr);
        $where['status'] =1;
        $cat_name = M('Village_group_category')->where($where)->order('sort DESC')->getField('cat_id,cat_name');

        return $cat_name;

    }

    public function get_one($condition){
        return $this->where($condition)->find();
    }

    /**获取所有的分类信息**/
    protected function get_all_category(){
        $model = M('Village_group_category');
        return $model->select();
    }

    public function get_goods($cat_fid='',$get_all=false,$leader_id=''){
//        var_dump("郭先森");
//        var_dump($leader_id);

        $page = $_POST['page'] ? $_POST['page'] : 1;
        $limit = 0;
        $where['g.status']  = 1;
        if(!$get_all){
            if($cat_fid){
                $where['g.cat_fid']  = $cat_fid;
            }
            $limit = 20;
        }
//        if($_SESSION['leader_id']){
//            $address = M('Village_group_pick_address')->where(array('header_uid'=>$_SESSION['leader_id']))->find();
//           $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
//        }
//        // var_dump($where['_string']);
//        $field = 'g.*,i.increase_price,b.is_add_price,b.min_price,b.max_price';
//        $label = $this->get_label();
//        $condition_table = array(C('DB_PREFIX').'village_group_goods');
//        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$_SESSION['leader_id'];

        //郭先森 //重构
        if($leader_id){
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
            $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
        }
        // var_dump($where['_string']);
        $field = 'g.*,i.increase_price,b.is_add_price,b.min_price,b.max_price';
        $label = $this->get_label();
        $condition_table = array(C('DB_PREFIX').'village_group_goods');
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

        $count = D('Village_group_goods')->field($field)->join($left_join)->where($where)->count();
        $totalPage = ceil($count/10);
        if($limit>0 && $page > $totalPage){
            return array('goods'=>array(),'cat_fid'=> $where['cat_fid']);
        } elseif($limit>0) {
            $limit = (($page-1)*$limit).','.$limit;
        }
        $goods = D('Village_group_goods')->field($field)->join($left_join)->where($where)->limit($limit)->order('`g`.`sort` DESC,`g`.`goods_id` DESC')->select();
        // var_dump(D()->_sql());
        // $goods = M('Village_group_goods')->where($where)->order('`sort` DESC,`goods_id` DESC')->limit($limit)->select();


        $now_date_zero  = strtotime(date('Y-m-d'));

        $all_category = $this->get_all_category();
        foreach ($goods as $now_good) {
            $now_good['wait_hadpull_num'] = M('Village_group_order_detail')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id')->where(array('goods_id'=>$now_good['goods_id'],'_string'=>'d.date_num=o.newest_delivery_periods AND (o.status=1 OR o.status=3 OR o.status=5 OR o.status=6 OR o.status=7 OR o.status=8)'))->sum('num');
            $now_good['wait_hadpull_num'] = intval($now_good['wait_hadpull_num']);
            $now_good['price'] = floatval($now_good['price']);
            $now_good['old_price'] = floatval($now_good['old_price']);
            $now_good['sell_count']  = $now_good['sell_count'] + $now_good['virtual_sell_count'];
            $now_good['stock_num'] = floatval($now_good['stock_num']-$now_good['sell_count']);
            $now_good['image'] = replace_file_domain($now_good['image']);
            $label[$now_good['label']]['image'] = file_domain().$label[$now_good['label']]['image'];
            $now_good['label']  = $label[$now_good['label']];
            if($now_good['get_time']>=$now_date_zero){
                $now_good['open_time']=3;
            }

            if($now_good['get_time']>=$now_date_zero+86400){
                $now_good['open_time']=2;
            }

            if($now_good['get_time']>$now_date_zero+86400*2){
                $now_good['open_time']=1;
            }

            $now_good['cat_show_tag'] = 1;//默认显示
            foreach($all_category as $v){
                if($now_good['cat_fid']==$v['cat_id']){
                    $now_good['cat_show_tag'] = $v['show_tag'];
                }
            }

            $goods_tmp[$now_good['goods_id']] = $now_good;
            if(($now_good['get_time']>0 && $now_good['get_time']+86400 < time()) || ($now_good['end_time']>0 && $now_good['end_time']+86400 < time())){
                $where_goods = [];
                $where_goods['goods_id'] = $now_good['goods_id'];
                $where_goods['status'] = ['neq', 4];
                M('Village_group_goods')->where($where_goods)->setField('status',3);
            }
        }
        // var_dump($goods_tmp);
        return array('goods'=>$goods_tmp,'cat_fid'=> $where['cat_fid']);
    }
    public function get_order_goods($order_id, $start_uid=0,$leader_id=''){

        $where['order_id'] = $order_id;
        $goods_info = M('Village_group_order_detail_temp')->where($where)->field('goods_id')->select();
        if ($goods_info) {
            $goods_arr = array();
            foreach ($goods_info as $val) {
                if ($val['goods_id']) {
                    $goods_arr[]=$val['goods_id'];
                }
            }
            if ($goods_arr) {
                $where['g.goods_id'] = array('in', $goods_arr);
            }
        }
        unset($where['order_id']);





        if($leader_id || $start_uid>0){
            $leader_id = $leader_id>0?$leader_id:$start_uid;
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
            // $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";

        }


        $field = 'g.*,i.increase_price,b.is_add_price,b.min_price,b.max_price';
        $label = $this->get_label();
        $condition_table = array(C('DB_PREFIX').'village_group_goods');
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

        $goods = D('Village_group_goods')->field($field)->join($left_join)->where($where)->order('`g`.`sort` DESC,`g`.`goods_id` DESC')->select();
//var_dump(D('Village_group_goods')->_sql());die;
        $now_date_zero  = strtotime(date('Y-m-d'));
        foreach ($goods as $now_good) {
            $now_good['wait_hadpull_num'] = M('Village_group_order_detail')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_order o ON o.order_id = d.order_id')->where(array('goods_id'=>$now_good['goods_id'],'_string'=>'o.status=1 OR o.status=5 OR o.status=6'))->sum('num');
            $now_good['wait_hadpull_num'] = intval($now_good['wait_hadpull_num']);
            $now_good['price'] = floatval($now_good['price']);
            $now_good['old_price'] = floatval($now_good['old_price']);
            $now_good['stock_num'] = floatval($now_good['stock_num']-$now_good['sell_count']);
            $now_good['image'] = replace_file_domain($now_good['image']);
            $label[$now_good['label']]['image'] = file_domain().$label[$now_good['label']]['image'];
            $now_good['label']  = $label[$now_good['label']];
            $now_good['sell_count']  = $now_good['sell_count'] + $now_good['virtual_sell_count'];
            if($now_good['get_time']>=$now_date_zero){
                $now_good['open_time']=3;
            }

            if($now_good['get_time']>=$now_date_zero+86400){
                $now_good['open_time']=2;
            }

            if($now_good['get_time']>$now_date_zero+86400*2){
                $now_good['open_time']=1;
            }

            if($leader_id){
                // 绑定区域信息
                $goods_bind_area = PD('Village_group_goods_bind_area')->get_area_goods_bind_info($now_good['goods_id'],intval($address['province_id']),intval($address['city_id']));
                $now_good['goods_bind_area'] = $goods_bind_area;
            }

            //获取goods分类信息
            $cat = $this->get_goods_category($now_good['goods_id']);
            $now_good['cat_show_tag'] = $cat['show_tag'];
            // var_dump($now_good['goods_bind_area']);
            $goods_tmp[$now_good['goods_id']] = $now_good;
            if(($now_good['get_time']>0 && $now_good['get_time']+86400 < time()) || ($now_good['end_time'] > 0 && $now_good['end_time']+86400 < time())){
                $where_goods = [];
                $where_goods['goods_id'] = $now_good['goods_id'];
                $where_goods['status'] = ['neq', 4];
                M('Village_group_goods')->where($where_goods)->setField('status',3);
            }



        }
//        var_dump($goods_tmp);
        return array('goods'=>$goods_tmp,'cat_fid'=> $where['cat_fid']);
    }

    protected function get_goods_category($goods_id){
        if(empty($goods_id)) return array();
        $where = array();
        $where['g.goods_id'] = $goods_id;
        $field = 'c.*,g.goods_id';
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_category c ON g.cat_fid = c.cat_id';
        return D('Village_group_goods')->field($field)->join($left_join)->where($where)->find();
    }

    public function get_share_txt($show_image=false,$leader_id=''){

        $where['g.status']  = 1;
        $where['c.status']  = 1;
        $where['g.cat_fid']  = array('gt',0);
//        $leader_id = intval($_SESSION['leader_id']);//郭先森//重构
        if($leader_id){
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
            $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
        }

        $where['g.status']  = 1;
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_category c ON g.cat_fid=c.cat_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

        $goods = D('Village_group_goods')
            ->field('g.*,i.increase_price,b.is_add_price,b.min_price,b.max_price')
            ->join($left_join)
            ->where($where)
            ->group('g.goods_id')
            ->order('`g`.`sort` DESC,`g`.`goods_id` DESC')->select();

//        if($_SESSION['leader_id']){
        //郭先森 重构
        if($leader_id){
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
        }

        if($show_image){
            $txt = '<p>'.C('config.village_group_alias').'｜'.date('m月d日').'团购专场</p><br>';

        }else{

            $txt = ''.C('config.village_group_alias').'｜'.date('m月d日').'团购专场'.PHP_EOL.PHP_EOL;
        }
        $i=0;
        foreach ($goods as $good) {
//            if($_SESSION['leader_id']){
            //郭先森 重构
            if($leader_id){
                // 绑定区域信息
                $goods_bind_area = D('Village_group_goods_bind_area')->get_area_goods_bind_info($good['goods_id'],intval($address['province_id']),intval($address['city_id']));
                if ($goods_bind_area && $goods_bind_area['price']) {
                    $good['price'] = $goods_bind_area['price'];
                    if ($goods_bind_area['is_add_price']==0) { // 没有打开加价
                        $good['increase_price'] = 0;
                    }else{
                        $good['increase_price'] = floatval($good['increase_price']);
                    }
                    $good['price'] += $good['increase_price'];
                }
            }
            $i++;
            if($show_image){
                $txt .='<p>'.$i.'.【'.$good['name'].'】</p>'.PHP_EOL;
                $txt .='<p>&nbsp;&nbsp;&nbsp;&nbsp;秒杀价：'.$good['price'].C('config.Currency_txt').' 市场价：'.$good['old_price'].C('config.Currency_txt').'</p>'.PHP_EOL;
                $txt .= '~~~~~~~~~~~~~~~~~~~~'.PHP_EOL;

            }else{

                $txt .= $i.'.【'.$good['name'].'】'.PHP_EOL;
                $txt .= '   秒杀价：'.$good['price'].C('config.Currency_txt').' 市场价：'.$good['old_price'].C('config.Currency_txt').PHP_EOL;
                $txt .= '~~~~~~~~~~~~~~~~~~~~'.PHP_EOL;
            }
        }
        return $txt;

    }

    public function emoji_to_image($emoji_str){
        import('@.ORG.emoji');
        $emoji = new emoji();
        $emoji_arr = $emoji->get_emoji_base_64();


        $emoji_str_to_arr = explode(" ",trim(str_replace("\u",' ',$emoji_str)));
        $count = count($emoji_str_to_arr);

        $i = 0;
        foreach ($emoji_arr as $er) {
            foreach ($er as $vo) {

                if(strpos($emoji_str,$vo[0])!==false){
                    $emoji_str = str_replace("\u".$vo[0],'<img src="data:image/png;base64,'.$vo[2].'">',$emoji_str,$times);

                    $i++;
                    if($i>=$count || $times==$count){
                        return $emoji_str;
                    }
                }
            }
        }

    }

    public function unicode2utf8_2($str){	//关于unicode编码转化的第二个函数，用于显示emoji表情
        $str = '{"result_str":"'.$str.'"}';	//组合成json格式
        $strarray = json_decode($str,true);	//json转换为数组，利用 JSON 对 \uXXXX 的支持来把转义符恢复为 Unicode 字符（by 梁海）
        return $strarray['result_str'];
    }

    public function get_number_emoji_code($number){
        $arr=array();

        $arr = str_split($number) ;
        $str='';
        $emoji = array(
            '0'=>"\ue225",
            '1'=>"\ue21c",
            '2'=>"\ue21d",
            '3'=>"\ue21e",
            '4'=>"\ue21f",
            '5'=>"\ue220",
            '6'=>"\ue221",
            '7'=>"\ue222",
            '8'=>"\ue223",
            '9'=>"\ue224",
        );
        foreach($arr as $n){
            $str.=$emoji[$n];
        }
        return $str;
    }

    public function  get_unbind_group(){
        return M('Village_group_pick_address')->where(array('status'=>1,'header_uid'=>0))->select();
    }


    public function get_village_group_info($cat_fid='',$in='',$leader_id=''){
        //var_dump($leader_id);
        //$goods = $this->get_goods($cat_fid);
        //郭先森 //重构
        $goods = $this->get_goods($cat_fid,'',$leader_id);

//        $cat_fid = array();
//        foreach ($goods as $g) {
//            if($cat_fid && $g['cat_fid'] != $cat_fid){
//                continue;
//            }
//            $arr[$g['cat_fid']][] = $g;
//            if(!in_array($g['cat_fid'],$cat_fid)){
//                $cat_fid[] = $g['cat_fid'];
//            }
//        }

        return array('goods'=>$goods['goods'],'cat_fid'=>$goods['cat_fid']);
    }

    //获取本期拼团的信息
    public function get_newest_one(){
        $condition['_string'] = "begin_time<{$_SERVER['REQUEST_TIME']} AND end_time+86400> {$_SERVER['REQUEST_TIME']}";
        $condition['status'] = 1;
        $now_group = $this->get_one($condition);

        if(empty($now_group)){
            return array();
        }
        $now_group['goods'] = $this->get_goods($now_group['group_id']);

        return $now_group;
    }

    /**
     * 已迁移  app\villageGroup\model\service\VillageGroupOrderService::getHeaderInfo()
     *
     * @author: zt
     * @date: 2023/02/06
     */
    public function get_header_info($uid=0,$header_id=0,$is_all=false){
        $uid>0 && $condition['uid'] = $uid;
        if($header_id>0){
            unset($condition['uid']);
            $condition['id'] = $header_id;
        }
        if (!$is_all) {
            $condition['status'] =  array('neq',4);
        }

        if($res = M('Village_group_header')->where($condition)->find()) {
            $user = PD('User')->get_user($res['uid']);
            if (!$user['avatar']) {
                $res['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
            } else {
                $res['avatar'] = $user['avatar'];
            }
            $res['header_uid'] = $res['uid'];
			$res['group_count'] = M('Village_group_order')->where(array('start_uid'=>$res['id'],'status'=>array('in','1,2,5,6,7,8')))->count();
            if($address = M('Village_group_pick_address')->where(array('header_uid'=>$res['id']))->find()){
                $res['pick_address'] = $address;
                if($address['status']==1){
                    $res['pick_address_status'] = 1;
                }else{
                    $res['pick_address_status'] = 0 ;
                }

            }
            return array('error_code'=>0,'msg'=>$res);
        }

        return array('error_code'=>2,'msg'=>'没有查找到团长信息');

    }

    public function get_start_group($id=0,$not_check=false){
        if($now_group = M('Village_group_start')->where(array('order_id'=>$id))->find()){
            if($not_check){
                return $now_group;
            }
            if($now_group['status']==1 ){

                return array('error_code'=>1,'msg'=>'当前团购已成团，不能再购买了');
            }

            if($now_group['status']==2){

                return array('error_code'=>1,'msg'=>'团购已结束');
            }
            return array('error_code'=>0,'msg'=>$now_group);

        }else{
            return array('error_code'=>1,'msg'=>'团购不存在');
        }
    }


    //检查购物车商品 ，计算价格
    public function check_cart($start_id,$goods_cart,$leader_id = ''){

        $goods  =$this->get_goods('',true,$leader_id);
        $goods = $goods['goods'];
        $order_money  =  0;
        $order_num  =  0;
        $bill_money  =  0;

        foreach ($goods_cart as $good_id=>&$item) {
            if($goods[$good_id]['start_time'] && $goods[$good_id]['start_time']>$_SERVER['REQUEST_TIME']){
                return array('error_code'=>3,'goods_id'=>$good_id,'msg'=>'活动还未开始，不能购买');
            }

            if($goods[$good_id]['end_time'] && $goods[$good_id]['end_time']+86400<$_SERVER['REQUEST_TIME']){
                return array('error_code'=>3,'goods_id'=>$good_id,'msg'=>'预售时间已过，不能购买');
            }
            if($goods[$good_id]['stock_num'] && $goods[$good_id]['stock_num']<=0){
                return array('error_code'=>3,'goods_id'=>$good_id,'msg'=>'该商品已经售罄，不能购买');
            }
            if($item['num']>$goods[$good_id]['stock_num']){
               $sell_out[] = array('goods_id'=>$good_id);
                continue;
            }
            if ($start_id>0) {
                $item['increase_price'] = M('Village_group_goods_increase_price')->where(array('goods_id' => $good_id,'header_uid' => $start_id))->getField('increase_price');
                if (!$item['increase_price'] || $item['increase_price'] < 0) {
                    $item['increase_price'] = 0;
                }
            } else {
                $item['increase_price'] = 0;
            }

            $item['price'] = $goods[$good_id]['price'];
            $order_money+=$item['num']*($goods[$good_id]['price']+$item['increase_price']);
            $bill_money+=$item['num']*$goods[$good_id]['purchase_price'];
            $order_num+=$item['num'];
        }

        if($sell_out){
            return array('error_code'=>2,'sell_out'=>$sell_out,'msg'=>'库存不足');
        }

        return array('error_code'=>0,'num'=>$order_num,'order_money'=>$order_money,'goods'=>$goods);

    }

    public function save_order_detail($order_id,$goods,$goodsCart,$leader_id=0, $village_group_type=0){
        foreach ($goodsCart as $key=>$item) {
            $data['order_id'] = $order_id;
            $data['goods_id'] = $key;
            $data['mer_id'] =  $goods[$key]['mer_id'];
            $data['mer_group_id'] = $goods[$key]['group_id'];
            $data['name'] = $goods[$key]['name'];
            $data['purchase_price']  = $goods[$key]['purchase_price'];
            $data['price'] = $goods[$key]['price'];
            $data['num']  = $item['num'];
            $data['create_time'] = $_SERVER['REQUEST_TIME'];
            $data['pay_price']  = $goods[$key]['price'];
            $data['old_price']  = $goods[$key]['old_price'];
            $data['is_merchant_send']  = $goods[$key]['is_merchant_send'];
            $data['leader_money']  = $goods[$key]['leader_money']>0?$goods[$key]['leader_money']:0;
            $data['village_group_type']  = $village_group_type>0?$village_group_type:0;

            if ($leader_id>0) {
                $data['increase_price'] = M('Village_group_goods_increase_price')->where(array('goods_id' => $key,'header_uid' => $leader_id))->getField('increase_price');
                if (!$data['increase_price'] || $data['increase_price'] < 0) {
                    $data['increase_price'] = 0;
                }
            }

            M('Village_group_order_detail')->add($data);
        }
    }

    public function get_pick_address_by_id($pick_id){
        $condition['id'] =$pick_id;
        return  M('Village_group_pick_address')->where($condition)->find();
    }

    public function get_pick_address_list(){
        $condition['status'] =1;
        $condition['header_uid'] =array('neq','');
        return M('Village_group_pick_address')->where($condition)->select();
    }

    public function get_start_list($param){
        $order_list = M('Village_group_start')->where($param)->field('o.*,g.effective_time,g.name as group_name,g.share_image,g.share_desc')
            ->join('as o LEFT JOIN '.C('DB_PREFIX')."village_group g ON g.group_id = o.group_id")->select();

        foreach ($order_list as &$item) {
            $item['status_txt']  = $this->group_status[$item['status']];
            $item['deliver_status_txt']  = $this->delivery_status[$item['deliver_status']];

        }
        return $order_list;
    }

    public function get_order_list($param){
        $order_list = M('Village_group_order')->where($param)->field('o.*,p.orderid,`p`.`business_type`,`p`.`business_id`,`p`.`business_param`,`p`.`order_name`,`p`.`total_money`,`p`.`system_balance`,`p`.`system_coupon_id`,`p`.`system_coupon_price`,`p`.`system_score`,`p`.`system_score_money`,`p`.`wx_cheap`,`p`.`merchant_balance_pay`,`p`.`merchant_balance_give`,`p`.`merchant_discount`,`p`.`merchant_discount_money`,`p`.`merchant_coupon_id`,`p`.`merchant_coupon_price`,`p`.`pay_money`,`p`.`add_time`,`p`.`third_id`,`p`.`is_mobile_pay`,`p`.`is_own`,`p`.`paid`')
            ->join('as o LEFT JOIN '.C('DB_PREFIX')."plat_order p ON p.business_id = o.order_id AND p.business_type='village_group'")->select();

        foreach ($order_list as &$item) {
            $item['status_txt']  = $this->group_status[$item['status']];
            $item['score_detucte']  =$item['system_score'];
            $item['score_used_count']  =$item['system_score_money'];
            $item['merchant_balance']  =$item['merchant_balance_pay']+$item['merchant_balance_give'];
            $item['balance_pay']  =$item['system_balance'];
            $item['payment_money']  =$item['pay_money'];
            $item['user'] = D('User')->get_user($item['uid']);
        }
        return $order_list;
    }

    public function get_order_detail($order_id,$verify=0){
        $where['order_id'] = $order_id;
        $now_order = M('Village_group_order')->where($where)->find();
        if($now_order['status']==0 && $now_order['add_time']+1800<time()){
            M('Village_group_order')->where(array('order_id'=>$order_id))->save(array('status'=>10,'last_time'=>time()));
            D("System_order")->save_order_status('village_group', $order_id, 5);
            $now_order['status'] = 10;
        }
        $param['business_id'] = $now_order['order_id'];
        $param['business_type'] = 'village_group';
        $now_order['pay_info'] = D('Plat_order')->get_order_by_business_id($param);
//        $now_start =  $this->get_start_group($now_order['start_id'],1);
        $now_order['status_txt'] = $this->order_status[$now_order['status']];
//        $now_order['start_status_txt'] = $this->group_status[$now_start['status']];
//        $now_order['score_deducte']  =$now_order['pay_info']['system_score'];
//        $now_order['score_used_count']  =$now_order['pay_info']['system_score_money'];
        $now_order['merchant_balance']  =$now_order['pay_info']['merchant_balance_pay']+$now_order['pay_info']['merchant_balance_give'];
        $now_order['balance_pay']  =$now_order['pay_info']['system_balance'];
        $now_order['payment_money']  =$now_order['pay_info']['pay_money'];
        $newest_delivery_periods = $now_order['newest_delivery_periods']>0?$now_order['newest_delivery_periods']:1;
        $now_order['goods_detail'] = $this->get_order_goods_detail($order_id,$newest_delivery_periods,$verify);
        $is_mobile_pay = $now_order['pay_info']['is_mobile_pay'] ? $now_order['pay_info']['is_mobile_pay'] : 1;
        $now_order['pay_type'] = D('Pay')->get_pay_name($now_order['pay_type'],$is_mobile_pay,$now_order['pay_info']['paid']);
        $now_user =   D('User')->get_user($now_order['uid']);
        $now_order['nickname'] =$now_user['nickname'];

        if ($now_order['discount_detail']) {
            $discount_detail = unserialize($now_order['discount_detail']);
            $good_discount = array();
            foreach ($discount_detail as $val) {
                if ($val['goods_id']) {
                    $good_discount[$val['goods_id']]=$val;
                }
            }
            $now_order['good_discount']=$good_discount;
        }

        $level_off_system = M(_view('User_level'))->where(array('level' => $now_order['user_level']))->find();
        $now_order['level_off_system']=$level_off_system;
        // 获取优惠券信息
        $system_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
        $now_order['system_coupon']=$system_coupon;

        // 计算实际支付金额
        $now_order['actual_discount_money'] = $now_order['discount_price'] - $now_order['score_deducte'];
        $now_order['actual_discount_money'] = round_number($now_order['actual_discount_money'],2);


        return $now_order;
    }

    public function get_order_goods_detail($order_id,$newest_delivery_periods=1,$verify){
        $where = array('order_id'=>$order_id);
        if ($newest_delivery_periods>0 && $verify<=0) {
            $where['d.date_num'] = $newest_delivery_periods;
        } elseif ($newest_delivery_periods>0 && $verify>=1) {
            $where['d.date_num'] = array('elt', $newest_delivery_periods);
        }
        if ($newest_delivery_periods>0) {
            $where['d.status'] = array('neq', 6);
            $where['d.is_delay'] = array('eq', 0);
        }
        $info = M('Village_group_order_detail')->field('g.*,d.mer_id,d.id,d.date_num,d.increase_price,d.area_price,d.purchase_price,d.price,d.num,d.pay_price,d.status as deliver_status,d.leader_money as order_leader_money,d.name')->join('as d LEFT JOIN '.C('DB_PREFIX').'village_group_goods as g on d.goods_id=g.goods_id')->where($where)->select();
        return  $info;
    }


    public function add_header_money($header,$money){
        $date['type']=1;
        $date['uid']=$header['uid'];
        $date['money']=floatval($money);
        $date['time']=time();
        $date['desc']='拼团验证消费，增加团长分佣';
        M('Header_money_list')->add();
    }

    public function get_code($uid){
        $where['uid'] = $uid;
        $where['status']  =1;
        $today_zero = strtotime(date('Y-m-d'));

        $where['_string'] = " last_time >{$today_zero} AND last_time < ".($today_zero+86400);

        if($res = M('Village_group_user_code')->where($where)->find()){
            return  $res['code'];
        }else{

            M('Village_group_user_code')->where(array('uid'=>$where['uid']))->setField('status',2);
            $data['code'] = $this->get_rand_code();
            $data['status'] = 1;
            $data['uid'] = $uid;
            $data['last_time'] = time();

            M('Village_group_user_code')->add($data);
            return $data['code'];
        }
    }

    public function get_rand_code()
    {
        $code  = createRandNumber(6);
        $where['code'] = $code;
        $where['status'] = 1;
        if($res = M('Village_group_user_code')->where($where)->find()){
            return $this->get_rand_code();
        }
        return $code;
    }

    public function config(){
        redirect(U('Config/index',array('galias'=>'village_group','header'=>'Village_group/header')));
    }


    public function createPoster($config=array(),$filename="",$type=""){
        //如果要看报什么错，可以先注释调这个header
        if(empty($filename)) header("content-type: image/png");
        $imageDefault = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>100,
            'height'=>100,
            'opacity'=>100
        );
        $textDefault = array(
            'text'=>'',
            'left'=>0,
            'top'=>0,
            'fontSize'=>32,       //字号
            'fontColor'=>'255,255,255', //字体颜色
            'angle'=>0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
	
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
        //处理了图片
        if(!empty($config['image'])){
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault,$val);

                //建立画板 ，缩放图片至指定尺寸
                if($val['radius']>0){
                    $val['url'] = $this->radius_img($val['url'],$val['radius']);
                }
				
                $info = getimagesize($val['url']);
				
                $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                if ($val['stream']) {   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }

                $res = $function($val['url']);


                $resWidth = $info[0];
                $resHeight = $info[1];

                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                if ($val['is_unhyaline']) {
                    $c = imagecolorallocate($canvas, 245 ,245 ,245);
                }else{
                    $c = imagecolorallocate($canvas, 255, 255, 255);
                }
                $color = imagecolortransparent($canvas,$c);
                imagefill($canvas, 0, 0, $color);

                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
                //放置图像
                // if($val['radius']>0){
                    // imagecopy($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height']);
                // }else{
                    imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
                // }
            }
        }
        //处理文字
        if(!empty($config['text'])){
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault,$val);
                $fontWidth = intval($val['fontSize']);//获取文字宽度
                $word_length = mb_strlen($val['text']);
                // $val['fontPath'] = $this->font_url;
                $val['fontPath'] = realpath($this->font_url_sc);

                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];

                if($val['type']=='title' && $val['center']){
                    $bbox = imagettfbbox($val['fontSize'], 0, $val['fontPath'], $val['text']);
                    $textWidth = $bbox[2]-$bbox[0];
                    $x         = ceil(($backgroundWidth - $textWidth) / 2);
                    $val['left'] = $x;

                    $fontColors = imagecolorallocate($imageRes, 233,30,99);
                    imageline($imageRes, $val['left']-10,$val['top'],$val['left']-10,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']-11,$val['top'],$val['left']-11,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']-12,$val['top'],$val['left']-12,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']-20,$val['top'],$val['left']-20,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']-21,$val['top'],$val['left']-21,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']-22,$val['top'],$val['left']-22,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']-30,$val['top'],$val['left']-30,$val['top']-$fontWidth+15, $fontColors);//画线
                    imageline($imageRes, $val['left']-31,$val['top'],$val['left']-31,$val['top']-$fontWidth+15, $fontColors);//画线
                    imageline($imageRes, $val['left']-32,$val['top'],$val['left']-32,$val['top']-$fontWidth+15, $fontColors);//画线


                    imageline($imageRes, $val['left']+10+$textWidth,$val['top'],$val['left']+10+$textWidth,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']+11+$textWidth,$val['top'],$val['left']+11+$textWidth,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']+12+$textWidth,$val['top'],$val['left']+12+$textWidth,$val['top']-$fontWidth, $fontColors);//画线
                    imageline($imageRes, $val['left']+20+$textWidth,$val['top'],$val['left']+20+$textWidth,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']+21+$textWidth,$val['top'],$val['left']+21+$textWidth,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']+22+$textWidth,$val['top'],$val['left']+22+$textWidth,$val['top']-$fontWidth+10, $fontColors);//画线
                    imageline($imageRes, $val['left']+30+$textWidth,$val['top'],$val['left']+30+$textWidth,$val['top']-$fontWidth+15, $fontColors);//画线
                    imageline($imageRes, $val['left']+31+$textWidth,$val['top'],$val['left']+31+$textWidth,$val['top']-$fontWidth+15, $fontColors);//画线
                    imageline($imageRes, $val['left']+32+$textWidth,$val['top'],$val['left']+32+$textWidth,$val['top']-$fontWidth+15, $fontColors);//画线
                }
               // if($val['type']=='goods_name'){
               //     $val['text'] = wordwrap_utf8($val['text'],ceil(120/$fontWidth),"\n");
               // }

                imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);

                if($val['line']==true){
                    $line_height = $val['fontSize']/2;
					
					$bbox = imagettfbbox($val['fontSize'], 0, $val['fontPath'], $val['text']);
                    $textWidth = $bbox[2]-$bbox[0];
                    imageline($imageRes, $val['left'],$val['top']-$line_height,$val['left']+$textWidth+10,$val['top']-$line_height, $fontColor);//画线
                }
            }
        }
        //生成图片
        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }

    public function wordwrap_utf8($str, $length = 75, $break = '<br />') {
        $len = mb_strlen($str,'utf-8');
        if($len <= $length) return $str;
        $temp = array();
        $num = ceil($len/$length);
        for($i = 0; $i < $num; $i++) {
            if($i<2){
                array_push($temp,
					mb_substr($str, $length*$i, $length, 'utf-8')
				);
            }else{
				break;
			}
        }
        return implode($break, $temp);
    }

    public function radius_img($imgpath, $radius = 15) {
        $src_img = null;
        $info = getimagesize($imgpath);
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
       // if ($val['stream']) {   //如果传的是字符串图像流
       //     $info = getimagesizefromstring($val['url']);
       //     $function = 'imagecreatefromstring';
       // }
        $src_img = $function($imgpath);
        $w = $info[0];
        $h = $info[1];
        // $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
		 if(!file_exists('./runtime/circle_image')){
            mkdir('./runtime/circle_image',0777,true);
        }
        $md5_title = md5(time().rand(0,100));
        imagepng($img,'./runtime/circle_image/qrcode_'.$md5_title.'.png');
        return './runtime/circle_image/qrcode_'.$md5_title.'.png';
    }

    public function set_goods_image($goods,$i,$type=""){
        if ($type==4) {
            $left_start = 65;
            $left_end = -65;
            $top_start = 200;
            $row = $i ; $col = $i%2;
            $left  = $col==1?$left_end:$left_start;
            $res = $this->image_center_crop(replace_file_domain($goods['image']));
            return array(
                'url'=> $res,     //二维码资源
                'stream'=>0,
                'left'=>$left,
                'top'=>$top_start+$row*190,
                'right'=>0,
                'bottom'=>0,
                'width'=>190,
                'height'=>190,
                'opacity'=>100,
                'radius'=>0,
                'is_unhyaline' => true
            );
        }else{
            $left_start = 45;
            $left_end = 390;
            $top_start = 200;
            $row = intval($i/2) ; 
            $col = $i%2;
            $left  = $col==1?$left_end:$left_start;
            $res = $this->image_center_crop(replace_file_domain($goods['image']));
            return array(
                'url'=> $res,     //二维码资源
                'stream'=>0,
                'left'=>$left,
                'top'=>$top_start+$row*175,
                'right'=>0,
                'bottom'=>0,
                'width'=>140,
                'height'=>140,
                'opacity'=>100,
                'radius'=>0,
                'is_unhyaline' => true
            );
        }
        // $left_start = 45;
        // $left_end = 390;
        // $top_start = 200;
        // $height =150 ;
        // $row = intval($i/2) ;

       
    }

    function image_center_crop($source)
    {
        $info = getimagesize($source);
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);

        $image = $function($source);
        if (!isset($image)) return false;
        /* 获取图像尺寸信息 */
        $target_w = $info[1];
        $target_h = $info[1];
        $source_w = imagesx($image);
        $source_h = imagesy($image);
        /* 计算裁剪宽度和高度 */
        $judge = (($source_w / $source_h) > ($target_w / $target_h));
        $resize_w = $judge ? ($source_w * $target_h) / $source_h : $target_w;
        $resize_h = !$judge ? ($source_h * $target_w) / $source_w : $target_h;
        $start_x = $judge ? ($resize_w - $target_w) / 2 : 0;
        $start_y = !$judge ? ($resize_h - $target_h) / 2 : 0;
        /* 绘制居中缩放图像 */
        $resize_img = imagecreatetruecolor($resize_w, $resize_h);
        imagecopyresampled($resize_img, $image, 0, 0, 0, 0, $resize_w, $resize_h, $source_w, $source_h);
        $target_img = imagecreatetruecolor($target_w, $target_h);
        imagecopy($target_img, $resize_img, 0, 0, $start_x, $start_y, $resize_w, $resize_h);
        /* 将图片保存至文件 */

        if(!file_exists('./runtime/circle_image')){
            mkdir('./runtime/circle_image',0777,true);
        }
        $md5_title = md5(time().rand(0,100));
        imagepng($target_img,'./runtime/circle_image/qrcode_'.$md5_title.'.png');
        return './runtime/circle_image/qrcode_'.$md5_title.'.png';


        if (!file_exists(dirname($target))) mkdir(dirname($target), 0777, true);
        switch (exif_imagetype($source)) {
            case IMAGETYPE_JPEG:
                imagejpeg($target_img, $target);
                break;
            case IMAGETYPE_PNG:
                imagepng($target_img, $target);
                break;
            case IMAGETYPE_GIF:
                imagegif($target_img, $target);
                break;
        }
        return boolval(file_exists($target));
    }

    public function set_goods_txt($goods,$i,$type=''){
        $arr= [];
        if ($type == 4) { 
            $left_start = 270;
            $left_end = 65;
            $top_start = 200;
            $height = 190 ;
            $row = $i ;
        }else{
            $left_start = 200;
            $left_end = 535;
            $top_start = 200;
            $height =175 ;
            $row = intval($i/2) ;
        }
        // $left_start = 200;
        // $left_end = 535;
        // $top_start = 200;
        // $height =175 ;
        // $row = intval($i/2) ;

        $col = $i%2;
        $left  = $col==1?$left_end:$left_start;
        if ($type == 4) {
			$nameWrap = $this->wordwrap_utf8($goods['name'],17,"\n");
            $arr[] =array(
                'text'=>$nameWrap,
                'left'=>$left,
                'top'=>260+$height*$row,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'20',
                'type'=>'goods_name',
                'center'=>false,
                'fontColor'=>'10,10,10',
            );
            $arr[] =array(
                    'text'=>$this->wordwrap_utf8($goods['des'],17,"\n"),
                    'left'=>$left,
                    'top'=>260+$height*$row+40+40*(substr_count($nameWrap,"\n")),
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'18',
                    'type'=>'goods_name',
                    'center'=>false,
                    'fontColor'=>'105,105,105',
                );
        }else{ 
            $arr[] =array(
                'text'=>$this->wordwrap_utf8($goods['name'],7,"\n"),
                'left'=>$left,
                'top'=>230+$height*$row,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'18',
                'type'=>'goods_name',
                'center'=>false,
                'fontColor'=>'10,10,10',
            );
            if($goods['old_price']>0&&$goods['old_price']>$goods['price']){
                $arr[] =array(
                    'text'=>C('config.Currency_symbol').$goods['old_price'],
                    'left'=>$left,
                    'top'=>mb_strlen($goods['name'],'utf8')<=7 ? 285+$height*$row : 295+$height*$row,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'16',
                    'type'=>'old_price',
                    'line'=>true,
                    'center'=>false,
                    'fontColor'=>$type == 3 ? '139 26 26':'105,105,105',
                );
            }
            $arr[] = array(
                'text'=>C('config.Currency_symbol').$goods['price'],
                'left'=>$left,
                'top'=>mb_strlen($goods['name'],'utf8')<=7 ? 310+$height*$row : 320+$height*$row,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'20',
                'type'=>'price',
                'center'=>false,
                'fontColor'=>$type == 3 ? '205 38 38':'233,30,99',
            );
        }
        fdump('999999999999999999999999999999999999999999999999999999','set_goods_txt');
        fdump($arr,'set_goods_txt',true);
        return $arr;
    }

    public function get_wxapp_qrcode($path,$width){
        $wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
        $wxapp_access_token = $wxapp_access_token['access_token'];

        import('ORG.Net.Http');
        $http = new Http();
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$wxapp_access_token;
        $postData = array(
            'is_hyaline'=>false,
            'path'=>$path,
            'width'=>$width
        );
        $qrcode = $http->curlPostOwn($url,json_encode($postData));

        return $qrcode;
    }


    /*
     * 获得分享图片
     * */
    public function get_share_image($length,$header,$type=''){

        $leader_id = intval($header['id']);
        $where['g.status']  = 1;
        if($leader_id){
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
            $where['_string'] = "(b.city_id IS NUll OR (ga.area_id = {$address['province_id']}) OR (ga.area_id = {$address['city_id']}) OR (ga.area_id = {$address['area_id']})) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND locate({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND locate({$address['id']} , b.address_list)=0))";
        }
        $left_join = 'as g LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON g.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = g.goods_id AND i.header_uid='.$leader_id;

        $goods = D('Village_group_goods')->field('g.*')->join($left_join)->where($where)->limit($length)->order('`g`.`sort` DESC,`g`.`goods_id` DESC')->select();


        if(count($goods)<=8){
            $length = 8;
        }
        if(count($goods)<=6){
            $length = 6;
        }
        if ($type == 4) {
            $length = 4;
        }
        //换短链
        $link_id = M('Short_link')->data(['link_url'=>'/wap.php?c=Village_group&a=index&leader_id='.$header['id']])->add();
		if(!$link_id){
			$this->error('小程序码生成失败');
		}
		//小程序路径
		$wxapp_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/short_'.$link_id);
        fdump($wxapp_path, 'qrcode222',1);
        $qrcode = $this->get_wxapp_qrcode($wxapp_path,280);
        if(strpos($qrcode,'{"') === 0){
            $qrcodeArr = json_decode($qrcode,true);
            if($qrcodeArr && $qrcodeArr['errcode']){
				die('小程序二维码生成失败。'.var_export($qrcodeArr, true));
                // $this->error_tips('小程序二维码生成失败。'.$qrcodeArr['errmsg']);
            }
        }
        if(!file_exists('./runtime/tempQrcode')){
            mkdir('./runtime/tempQrcode',0777,true);
        }
        $qrcode_filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
        file_put_contents($qrcode_filePath,$qrcode);
		
		 if($_SESSION['avatar_image'] && is_file($_SESSION['avatar_image']))
		 {
		 	$avatar_filePath =$_SESSION['avatar_image'];
		 }else{

			$avatar_file = file_get_contents($header['avatar']);
			 if(!file_exists('./runtime/avatarImage')){
				mkdir('./runtime/avatarImage',0777,true);
			}
			$avatar_filePath = './runtime/avatarImage/'.uniqid().'.jpg';

			file_put_contents($avatar_filePath,$avatar_file);
			$_SESSION['avatar_image']  = $avatar_filePath;
		 }

        $config = array(
            'image'=>array(
                array(
                    'url'=>$qrcode_filePath,     //二维码资源
                    'stream'=>0,
                    'left'=>510,
                    'top'=>-50,
                    'right'=>0,
                    'bottom'=>0,
                    'width'=>200,
                    'height'=>200,
                    'opacity'=>100,
                    'radius'=>140,
                    'is_unhyaline' => true
                ),
                array(
                    'url'=>$avatar_filePath,     //二维码资源
                    'stream'=>0,
                    'left'=>150,
                    'top'=>-235,
                    'right'=>0,
                    'bottom'=>0,
                    'width'=>31,
                    'height'=>31,
                    'opacity'=>100,
                    'radius'=>55
                ),
            ),
                'background'=>$type ? './static/qrcode/wxapp/share_'.$type.'_bg'.$length.'.png' : './static/qrcode/wxapp/share_bg'.$length.'.png',          //背景图

                'text'=>array(
                    array(
                        'text'=>date('m月d日').'爆款',
                        'left'=>375,
                        'top'=>165,
                        'right'=>0,
                        'bottom'=>0,
                        'fontSize'=>'28',
                        'type'=>'title',
                        'center'=>true,
                        'fontColor'=>'0,0,0',

                    ),
                    array(
                        'text'=>'团      长 ：     '.$header['name'],
                        'left'=>40,
                        'top'=>-240,
                        'right'=>0,
                        'bottom'=>0,
                        'fontSize'=>'18',
                        'type'=>'goods_leader',
                        'center'=>false,
                        'fontColor'=>$type == 2 ? '10,10,10':'255,255,255',

                    ),
                    array(
                        'text'=>'已团人数 ：'.intval(M('Village_group_order')->where(array('status'=>array('in','1,2,5,6,7,8')))->count()),
                        'left'=>40,
                        'top'=>-190,
                        'right'=>0,
                        'bottom'=>0,
                        'fontSize'=>'18',
                        'type'=>'goods_leader',
                        'center'=>false,
                        'fontColor'=>$type == 2 ? '10,10,10':'255,255,255',

                    ),
                    array(
                        'text'=>'自提地址 ：',
                        'left'=>40,
                        'top'=>-140,
                        'right'=>0,
                        'bottom'=>0,
                        'fontSize'=>'18',
                        'type'=>'goods_leader',
                        'center'=>false,
                        'fontColor'=>$type == 2 ? '10,10,10':'255,255,255',

                    ),

                    array(
                        'text'=>$this->wordwrap_utf8($header['pick_address']['pick_addr'],round(270/16),"\n"),
                        'left'=>150,
                        'top'=>-140,
                        'right'=>0,
                        'bottom'=>0,
                        'fontSize'=>'18',
                        'type'=>'goods_leader',
                        'center'=>false,
                        'fontColor'=>$type == 2 ? '10,10,10':'255,255,255',

                    ),
                )

        );
        
        if ($type == 4) {
            $config['image'][0]['left'] = 55;
            $config['image'][0]['top'] = -55;
            $config['image'][1]['left'] = 385;
            $config['image'][1]['top'] = -192;

            $config['text'][0]['left'] = 375;
            $config['text'][0]['top'] = 165;
            $config['text'][1]['left'] = 275;
            $config['text'][1]['top'] = -200;
            $config['text'][2]['left'] = 275;
            $config['text'][2]['top'] = -150;
            $config['text'][3]['left'] = 275;
            $config['text'][3]['top'] = -100;
            $config['text'][4]['left'] = 385;
            $config['text'][4]['top'] = -100;
        }elseif ($type==2||$type==3) { // 二维码位置
            $config['image'][0]['left'] = 490;
            $config['image'][0]['top'] = -65;
            $config['image'][1]['left'] = 178;
            $config['image'][1]['top'] = -212;
            $config['text'][1]['left'] = 50;
            $config['text'][1]['top'] = -220;
            $config['text'][2]['left'] = 50;
            $config['text'][2]['top'] = -170;
            $config['text'][3]['left'] = 50;
            $config['text'][3]['top'] = -120;
            $config['text'][4]['left'] = 160;
            $config['text'][4]['top'] = -120;
        }

        if (C('config.village_group_hide_detail')==1) {
            // 隐藏团长信息
            unset($config['image'][1]);
            unset($config['text'][1]);
            unset($config['text'][3]);
            unset($config['text'][4]);
        }
        fdump($config['text'],'set_goods_txt',true);
        $i=0;

        foreach($goods as $good){
		
           $config['image'][]=$this->set_goods_image($good,$i,$type);
            $config['text']= array_merge($config['text'],$this->set_goods_txt($good,$i,$type));
            $i++;
        }
        $md5_title = $type ? 'first_'.$header['id'].'_'.$type.'_'.date('ymd') : 'first_'.$header['id'].'_'.date('ymd');
        if(!file_exists('./runtime/tmpshareImage')){
            mkdir('./runtime/tmpshareImage',0777,true);
        }
        return $this->createPoster($config,'./runtime/tmpshareImage/'.$md5_title.'.jpg',$type);
//        return $this->createPoster($config);
    }

    public function get_share_goods($goods_id,$header,$shareUid = ''){
        $goods = M('Village_group_goods')->where(array('goods_id'=>$goods_id))->find();
        //活动价
        $act_where = [
            'g.is_del' => 0,
            'g.goods_id' => $goods_id,
            'a.type' => 2,
            'a.status' => 1,
            'a.start_time' => ['elt', time()],
            'a.end_time' => ['gt', time()],
        ];
        $actinfo = M("Village_group_activity_goods")->alias("g")->join("left join ".C("DB_PREFIX")."village_group_activity as a on a.activity_id=g.activity_id")->where($act_where)->field("g.buy_price")->find();  
        if($actinfo && $actinfo['buy_price'] > 0){
            $goods['price'] = $actinfo['buy_price'];
        }
        $goods['price'] = getFormatNumber($goods['price']);
        // ====>郭先森
        // pigcms_village_group_goods_bind_area
        $village_group_goods_bind_area = M('Village_group_goods_bind_area')->where(array('goods_id'=>$goods_id,'buy_user_type'=>0))->find();

        if($village_group_goods_bind_area){
            //团长加价的价格
            $villageGroupGoodsIncreasePrice = M('Village_group_goods_increase_price')->where(array('goods_id'=>$goods_id,'header_uid'=>$header['id']))->find();
            if($villageGroupGoodsIncreasePrice){
                // $leaderPlusPrice =
                $increase_price = $village_group_goods_bind_area['price']+$villageGroupGoodsIncreasePrice['increase_price'];

            }else{
                $increase_price = $village_group_goods_bind_area['price'];
            }
        }else{
            $increase_price = $goods['price'];


        }

        // header('content-type:image/png');
        $backgroundWidth = 864;
        $backgroundHeight = 1224;
        $imageRes=imagecreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes,255,255,255); //分配一个颜色

        $val = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>864,
            'height'=>580,
            'opacity'=>100
        );

        //填充背景
        $goods['image'] = $goods['image']?replace_file_domain($goods['image']):'';
        imagefill($imageRes,0,0, $color );//填充背景
        $info = getimagesize($goods['image']);

        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $res = $function($goods['image']);

        $resWidth = $info[0];
        $resHeight = $info[1];

        // 正方形图
        $real_height = $resHeight/$resWidth*864;
        if ($real_height >= 864) {
           $val['height'] = 864;
        }

        $canvas = imagecreatetruecolor($val['width'], $val['height']);
        $c = imagecolorallocate($canvas, 255, 255, 255);
        $color = imagecolortransparent($canvas,$c);

        imagefill($canvas, 0, 0, $color);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0,$val['width'], $val['height'],$resWidth,$resHeight);
        $val['left'] = ($backgroundWidth-$val['width'])/2;
        $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
        //放置图像

        imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
        $linkUrl = '/wap.php?c=Village_group&a=goods&goods_id='.$goods_id.'&leader_id='.$header['id'];
        
        if(!empty($shareUid)){
            $linkUrl = $linkUrl.'&share_uid='.$shareUid;
        }
        //换短链
        $link_id = M('Short_link')->data(['link_url' => $linkUrl])->add();
		if(!$link_id){
			$this->error('小程序码生成失败');
		}
		//小程序路径
		$wxapp_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/short_'.$link_id);
        fdump($wxapp_path, 'qrcode111',1);
        //$qrcode = $this->get_wxapp_qrcode($wxapp_path,280);
        //使用不限量二维码
        $wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
        import('ORG.Net.Http');
        $http = new Http();
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $wxapp_access_token['access_token'];
        $postData = array(
            'scene' => 'short_' . $link_id,
            'page' => 'pages/webview/webview',
            'width' => 280,
            'is_hyaline' => true
        );
        $qrcode = $http->curlPostOwn($url, json_encode($postData));

        if(strpos($qrcode,'{"') === 0){
            $qrcodeArr = json_decode($qrcode,true);
            if($qrcodeArr && $qrcodeArr['errcode']){
				die('小程序二维码生成失败。'.var_export($qrcodeArr, true));
                // $this->error_tips('小程序二维码生成失败。'.$qrcodeArr['errmsg']);
            }
        }
        if(!file_exists('./runtime/tempQrcode')){
            mkdir('./runtime/tempQrcode',0777,true);
        }
        $qrcode_filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
        file_put_contents($qrcode_filePath,$qrcode);


        $val = array(
            'url'=>$qrcode_filePath,
            'left'=>90,
            'top'=>960,
            'right'=>0,
            'bottom'=>0,
            'width'=>180,
            'height'=>180,
            'opacity'=>100,
            'radius'=>177
        );

        if($val['radius']>0){
            // $val['url'] = $this->radius_img($val['url'],$val['radius']);
        }

        // 正方形图
        if ($real_height >= 864) {
            $val['left'] = 600;
            $val['top'] = 930;
            $val['width'] = 230;
            $val['height'] = 230;
        }


        $info = getimagesize($val['url']);

        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);

        $res = $function($val['url']);
        $resWidth = $info[0];
        $resHeight = $info[1];

        $canvas = imagecreatetruecolor($val['width'], $val['height']);
        $c = imagecolorallocate($canvas, 255, 255, 255);
        $color = imagecolortransparent($canvas,$c);
        imagefill($canvas, 0, 0, $color);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);

        //放置图像
        imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);

        $fontColors = imagecolorallocate($imageRes, 200, 200, 200);

        if ($real_height < 864) {
            $len = 864/15;
            imageline($imageRes, 0,905, 7.5,905, $fontColors);//画线
            for($i=1;$i<$len;$i++){
                imageline($imageRes, $i*30-7.5,905, $i*30+7.5,905, $fontColors);//画线
            }
        }

        $text['price'] =   array(
            // 'text'=>C('config.Currency_symbol').floatval($goods['price']), //原来的
            'text'=>C('config.Currency_symbol').$increase_price, //郭先森
            'left'=>25,
            'top'=>785,
            'right'=>0,
            'bottom'=>0,
            'fontSize'=>'60',
            'line'=>false,
            'center'=>true,
            'fontColor'=>'255,0,0',
            'fontPath'=>$this->font_url_bold,
        );
        if($goods['old_price']>0 && $goods['old_price']>$goods['price']){
            $text['old_price'] =
                array(
                    'text'=>C('config.Currency_symbol').floatval($goods['old_price']),
                    'left'=>125,
                    'top'=>809,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'36',
                    'line'=>true,
                    'fontColor'=>'200,200,200',
                    'fontPath'=>$this->font_url_mid,
                );
        }
        $text['name'] =
            array(
                'text'=>msubstr(trim($goods['name']),0,16),
                'left'=>125,
                'top'=>630,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'40',
                'center'=>true,
                'fontColor'=>'0,0,0',
                'fontPath'=>$this->font_url_sc,
            );



        $text['wxapp'] =
            array(
                'text'=>'长按识别小程序立即购买',
                'left'=>300,
                'top'=>1010,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'30',

                'fontColor'=>'0,0,0',
            );

        if (!C('config.village_group_hide_detail')) {
            // 隐藏团长信息
            $text['wxapp_sub_title'] =
            array(
                'text'=>$header['pick_address']['pick_addr'],
                'left'=>300,
                'top'=>1080,
                'right'=>0,
                'bottom'=>0,
                'fontSize'=>'24',

                'fontColor'=>'200,200,200',
            );
        }
       
        // 正方形图
        if ($real_height >= 864) {
            $text['price']['left'] = 40;
            $text['price']['top'] =  920;
            $text['price']['center'] = false;
            $text['old_price']['left'] = 50 + mb_strlen($text['price']['text'],'utf8')*55;
            $text['old_price']['top'] =  940;
            $text['name']['text'] = $this->wordwrap_utf8($goods['name'],ceil(150/14),"\n");
            $text['name']['left'] = 40;
            $text['name']['top'] =  1015;
            $text['name']['center'] = false;
            $text['name']['fontSize'] = 35;
            unset($text['wxapp']);
            if ($text['wxapp_sub_title']) {
                if (mb_strlen($text['name']['text'],'utf8')<=11) {
                    $text['wxapp_sub_title']['top'] =  1085;
                }else{
                    $text['wxapp_sub_title']['top'] =  1155;
                }
                $text['wxapp_sub_title']['left'] = 40;
                $text['wxapp_sub_title']['text'] = msubstr(trim($text['wxapp_sub_title']['text']),0,16);
            }
        }

        fdump('-----------------------------------------------------------','====================',true);
        fdump($text,'====================',true);
        $old_price_left = '';

        foreach ($text as $key=>$val) {
            $fontWidth = intval($val['fontSize']);//获取文字宽度
            !$val['fontPath'] && $val['fontPath'] = $this->font_url_sc;
            $val['fontPath'] = realpath($val['fontPath']);
            list($R,$G,$B) = explode(',', $val['fontColor']);
            $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
            $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
            if($old_price_left){
                $val['left'] = $old_price_left;
            }
            $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
            if($val['center']){
                $bbox = imagettfbbox($val['fontSize'], 0, $val['fontPath'], $val['text']);

               // $textWidth = $fontWidth * mb_strlen($val['text']);
                $textWidth = $bbox[2]-$bbox[0];
                $x         = ceil(($backgroundWidth - $textWidth) / 2);
                $val['left'] = $x;

                if($key=='price' && $text['old_price']){
                    $old_price_left = $x+$textWidth+20;;
                }
            }else{
                $old_price_left='';
            }



            imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top']+$val['fontSize'],$fontColor,$val['fontPath'],$val['text']);

            if($val['line']==true){
                $bbox = imagettfbbox($val['fontSize'], 0, $val['fontPath'], $val['text']);

               // $textWidth = $fontWidth * mb_strlen($val['text']);
                $textWidth = $bbox[2]-$bbox[0];
                $line_height = $val['fontSize']/2;
                imageline($imageRes, $val['left']+5,$val['top']-$line_height+$val['fontSize'],$val['left']+$textWidth+20,$val['top']-$line_height+$val['fontSize'], $fontColor);//画线
            }
        }

        if(!file_exists('./runtime/tmpshareImage')){
            mkdir('./runtime/tmpshareImage',0777,true);
        }
        $image_name = $header['id'].'_'.$goods_id;
        $filename = './runtime/tmpshareImage/goods_'.$image_name.'.jpg';

        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }

    }


    public function get_share_wx_goods($goods_id,$leader_id){
        $goods = M('Village_group_goods')->where(array('goods_id'=>$goods_id))->find();
        header('content-type:image/png');
        // $backgroundWidth = 500;
        // $backgroundHeight = 400;
        // $imageRes=imagecreatetruecolor($backgroundWidth,$backgroundHeight);
        // $color = imagecolorallocate($imageRes,255,255,255); //分配一个颜色

        $background = './static/wxapp/wxapp_friend_bg.png';//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);

        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 255, 255, 255);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));

        $val = array(
            'left'=>100,
            'top'=>50,
            'right'=>0,
            'bottom'=>0,
            'width'=>468,
            'height'=>260,
            'opacity'=>100
        );

        //填充背景
        imagefill($imageRes,0,0, $color );//填充背景
        $info = getimagesize($goods['image']);

        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $res = $function($goods['image']);

        $resWidth = $info[0];
        $resHeight = $info[1];

        // 正方形图
        $val = [];
        if ($resWidth <= $resHeight) {
           $val['height'] = 400;
           $val['width'] = 400;
           $val['top'] = 0;
           $val['left'] = 50;
        }

        $canvas = imagecreatetruecolor($val['width'], $val['height']);
        $c = imagecolorallocate($canvas, 255, 255, 255);
        $color = imagecolortransparent($canvas,$c);

        imagefill($canvas, 0, 0, $color);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0,$val['width'], $val['height'],$resWidth,$resHeight);
        $val['left'] = ($backgroundWidth-$val['width'])/2;
        $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
        //放置图像

        imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度

        // if($goods['old_price']>0&&$goods['old_price']>$goods['price']){

        //     $text[] =
        //         array(
        //             'text'=>C('config.Currency_symbol').$goods['old_price'],
        //             'left'=>135,
        //             'top'=>-25,
        //             'right'=>0,
        //             'bottom'=>0,
        //             'fontSize'=>'14',
        //             'line'=>true,
        //             'center'=>false,
        //             'fontColor'=>'200,200,200',

        //         );
        // }

        // $text[] =   array(
        //     'text'=>C('config.Currency_symbol').$goods['price'],
        //     'left'=>25,
        //     'top'=>-25,
        //     'right'=>0,
        //     'bottom'=>0,
        //     'fontSize'=>'18',
        //     'line'=>false,
        //     'center'=>false,
        //     'fontColor'=>'233,30,90',


        // );
        foreach ($text as $val) {
            $fontWidth = intval($val['fontSize']);//获取文字宽度
            $val['fontPath'] =$this->font_url;
            list($R,$G,$B) = explode(',', $val['fontColor']);
            $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
            $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
            $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];

            imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);

            if($val['line']==true){
                if($val['line']==true){
                    $bbox = imagettfbbox($val['fontSize'], 0, $val['fontPath'], $val['text']);
                    $textWidth = $bbox[2]-$bbox[0];

                    $line_height = $val['fontSize']/2;
                    imageline($imageRes, $val['left'],$val['top']-$line_height,$val['left']+$textWidth+5,$val['top']-$line_height, $fontColor);//画线
                }
                $line_height = $val['fontSize']/2;
                imageline($imageRes, $val['left'],$val['top']-$line_height,$val['left']+$fontWidth*(mb_strlen($val['text'])+0.5),$val['top']-$line_height, $fontColor);//画线
            }
        }

        if(!file_exists('./runtime/tmpshareImage')){
            mkdir('./runtime/tmpshareImage',0777,true);
        }
        $image_name = $leader_id.'_'.$goods_id;
        $filename = './runtime/tmpshareImage/goodswx_'.$image_name.'.jpg';

        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }

    }


    //获取本期拼团的信息
    public function hide_address($address){
        if ((strlen($address) + mb_strlen($address,'UTF8'))/2 > 3) {
            $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
        }

        return $now_group;
    }


    // 获取对应拼团商品的最近一期的配送时间  每日不用处理   每周 传值 1-7   每月传值1-30  周期数 2 即 多加一天或者多加一周或者多加一个月
    public function lately_cycle_time($goods_id, $time=0, $is_time_stamp=false, $periods=1,$now_time=0, $first_time=0){
        if (!$goods_id) {
            return 0;
        }
        $periods = $periods>1?($periods-1):0;
        $cycle_info = M('Village_group_cycle')->where(array('goods_id'=>$goods_id))->find();
        if (empty($cycle_info)) {
            return 0;
        }
        if ($cycle_info['delivery_date']) {
            $cycle_info['delivery_date_arr'] = explode(',',$cycle_info['delivery_date']);
        }
        if ($now_time<=0) {
            $now_time = strtotime(date('Y-m-d'));
        }
        $limit_days = $cycle_info['limit_days']>0?intval($cycle_info['limit_days']):0;
        if ($cycle_info['limit_time'] && $periods==0) {
            $limit_time_str = strtotime(date('Y-m-d') . $cycle_info['limit_time']);
            if (time() > $limit_time_str) {
                $limit_days += 1;
            }
        }
        // 根据周期类型 处理时间问题
        $choose_date=$this->choose_date;
        $lately_time = strtotime("+".$limit_days." day", $now_time);
        if ($cycle_info['cycle_type'] == 1) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            }
            // 每日送
            if (!$is_time_stamp) {
                $lately_time = date('Y-m-d',$lately_time);
                if ($limit_days == 0) {
                    $lately_time .= '(当天)';
                }
            }
        } elseif ($cycle_info['cycle_type'] == 2) {
            if ($periods>0) {
                $week_periods = $periods * 7;
                $lately_time = strtotime("+".$week_periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            }
            // 每周送
            $week = $choose_date['week'];
            $w = date("w", $lately_time);
            if ($w == 0) {
                $w = 7;
            }
            if ($time >0 && $time<=7 && $time > $w) {
                $time_days = $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            } elseif ($time >0 && $time<=7 && $w > $time) {
                $time_days = 7 + $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            }
            if ($cycle_info['is_all_date']==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif ($cycle_info['delivery_date_arr'] && in_array($w, $cycle_info['delivery_date_arr'])) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif (empty($cycle_info['delivery_date_arr'])) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
        } elseif ($cycle_info['cycle_type'] == 3) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." month", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            }
            // 每月送
            $day = intval(date("d", $lately_time));
            // 计算当前月份第一天
            $first_day = date("Y-m-01", $lately_time);
            // 计算当前月份最后一天
            $last_time = strtotime("$first_day +1 month -1 day");
            $last_day = date('d', $last_time);
            if ($time>$last_day) {
                // 不满足30天的按照最大天数算 比如2月份
                $time = $last_day;
            }
            if ($time >0 && $time<=30 && $time > $day) {
                $time_days = $time - $day;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $day = $time;
            } elseif ($time >0 && $time<=30 && $day > $time) {
                // 这里需要计算下一个月的所选日期
                $time_days = $day - $time;
                $lately_time = strtotime("+1 month -".$time_days." day", $lately_time);
                $day = $time;
            }

            $to_day = date('Y-m-d');
            if ($cycle_info['is_all_date']==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif ($cycle_info['delivery_date_arr'] && in_array($day, $cycle_info['delivery_date_arr'])) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif (empty($cycle_info['delivery_date_arr'])) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
            if ($lately_time==$to_day) {
                $lately_time .= '(当天)';
            }
        }

        return $lately_time;
    }
    // 获取对应拼团商品的最近一期的配送时间  每日不用处理   每周 传值 1-7   每月传值1-30  周期数 2 即 多加一天或者多加一周或者多加一个月
    public function order_lately_cycle_time($order_info = array(), $goods_id,$time=0, $is_time_stamp=false, $periods=1,$now_time=0, $first_time=0){
        if (!$goods_id) {
            return 0;
        }
        $periods = $periods>1?($periods-1):0;
        if (empty($order_info)) {
            return 0;
        }
        $cycle_info = M('Village_group_cycle')->where(array('goods_id'=>$goods_id))->find();
        if ($order_info['delivery_date_info']) {
            $delivery_date = $order_info['delivery_date_info'];
        } else {
            $delivery_date = $cycle_info['delivery_date'];
        }
        if ($order_info['is_all_date']) {
            $is_all_date = $order_info['is_all_date'];
        } else {
            $is_all_date = $cycle_info['is_all_date'];
        }
        if ($order_info['limit_days'] != '') {
            $limit_days = $order_info['limit_days'];
        } else {
            $limit_days = $cycle_info['limit_days'];
        }
        if ($order_info['limit_time'] != '') {
            $limit_time = $order_info['limit_time'];
        } else {
            $limit_time = $cycle_info['limit_time'];
        }
        $delivery_date_arr = array();
        if ($delivery_date) {
            $delivery_date_arr = explode(',',$delivery_date);
        }
        if ($now_time<=0) {
            $now_time = strtotime(date('Y-m-d'));
        }
        $limit_days = $limit_days>0?intval($limit_days):0;
        if ($order_info['limit_time'] && $periods==0) {
            $limit_time_str = strtotime(date('Y-m-d') . $limit_time);
            if (time() > $limit_time_str) {
                $limit_days += 1;
            }
        }
        // 根据周期类型 处理时间问题
        $choose_date=$this->choose_date;
        if ($now_time > time() + $limit_days*86400) {
            // 处理延期是时，最后一期时间；
            $lately_time = $now_time;
        }else{
            $lately_time = strtotime("+".$limit_days." day", $now_time);
        }
        if ($order_info['cycle_type'] == 1) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            }
            // 每日送
            if (!$is_time_stamp) {
                $lately_time = date('Y-m-d',$lately_time);
                if ($limit_days == 0) {
                    $lately_time .= '(当天)';
                }
            }
        } elseif ($order_info['cycle_type'] == 2) {
            if ($periods>0) {
                $week_periods = $periods * 7;
                $lately_time = strtotime("+".$week_periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            }
            // 每周送
            $week = $choose_date['week'];
            $w = date("w", $lately_time);
            if ($w == 0) {
                $w = 7;
            }
            if ($time >0 && $time<=7 && $time > $w) {
                $time_days = $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            } elseif ($time >0 && $time<=7 && $w > $time) {
                $time_days = 7 + $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            }
            if ($is_all_date==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif ($delivery_date_arr && in_array($w, $delivery_date_arr)) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif (empty($delivery_date_arr)) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
        } elseif ($order_info['cycle_type'] == 3) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." month", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            }
            // 每月送
            $day = intval(date("d", $lately_time));
            // 计算当前月份第一天
            $first_day = date("Y-m-01", $lately_time);
            // 计算当前月份最后一天
            $last_time = strtotime("$first_day +1 month -1 day");
            $last_day = date('d', $last_time);
            if ($time>$last_day) {
                // 不满足30天的按照最大天数算 比如2月份
                $time = $last_day;
            }
            if ($time >0 && $time<=30 && $time > $day) {
                $time_days = $time - $day;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $day = $time;
            } elseif ($time >0 && $time<=30 && $day > $time) {
                // 这里需要计算下一个月的所选日期
                $time_days = $day - $time;
                $lately_time = strtotime("+1 month -".$time_days." day", $lately_time);
                $day = $time;
            }

            $to_day = date('Y-m-d');
            if ($is_all_date==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif ($delivery_date_arr && in_array($day, $delivery_date_arr)) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif (empty($delivery_date_arr)) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
            if ($lately_time==$to_day) {
                $lately_time .= '(当天)';
            }
        }

        return $lately_time;
    }


    // 处理配送相关信息
    public function village_deliver_fee() {
        $deliver_info = $this->village_deliver_info();

        $delivery_fee = $deliver_info['delivery_fee'];
        $per_km_price = $deliver_info['per_km_price'];
        $basic_distance = $deliver_info['basic_distance'];
        $basic_weight = $deliver_info['basic_weight'];
        $per_weight_price = $deliver_info['per_weight_price'];
        $delivery_fee2 = $deliver_info['delivery_fee2'];
        $per_km_price2 = $deliver_info['per_km_price2'];
        $basic_distance2 = $deliver_info['basic_distance2'];
        $basic_weight2 = $deliver_info['basic_weight2'];
        $per_weight_price2 = $deliver_info['per_weight_price2'];

        $delivery_fee3 = $deliver_info['delivery_fee3'];
        $per_km_price3 = $deliver_info['per_km_price3'];
        $basic_distance3 = $deliver_info['basic_distance3'];
        $basic_weight3 = $deliver_info['basic_weight3'];
        $per_weight_price3 = $deliver_info['per_weight_price3'];
        //时段起送价
        $start_time = $deliver_info['delivertime_start'];
        $stop_time = $deliver_info['delivertime_stop'];

        $start_time2 = $deliver_info['delivertime_start2'];
        $stop_time2 = $deliver_info['delivertime_stop2'];

        $start_time3 = $deliver_info['delivertime_start3'];
        $stop_time3 = $deliver_info['delivertime_stop3'];

        $village['delivertime_start'] = $deliver_info['delivertime_start'];
        $village['delivertime_stop'] = $deliver_info['delivertime_stop'];
        $village['delivertime_start2'] = $deliver_info['delivertime_start2'];
        $village['delivertime_stop2'] = $deliver_info['delivertime_stop2'];
        $village['delivertime_start3'] = $deliver_info['delivertime_start3'];
        $village['delivertime_stop3'] = $deliver_info['delivertime_stop3'];

        $store['basic_price'] = floatval(C('config.basic_price'));
        $store['extra_price'] = floatval(C('config.extra_price'));

        $time = time();
        $selectDeliverTime = 0;
        if (!($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (!($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);

                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $village['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $village['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $village['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (!($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $village['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $village['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $village['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        } else {
            $selectDeliverTime = 1;
        }

        if ($selectDeliverTime == 1) {
            $village['basic_price'] = floatval(C('config.basic_price'));//起送价
        } elseif ($selectDeliverTime == 2) {
            $village['basic_price'] = floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : floatval(C('config.basic_price'));//起送价
        } elseif ($selectDeliverTime == 3) {
            $village['basic_price'] = floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : floatval(C('config.basic_price'));//起送价
        } else {
            $village['basic_price'] = floatval(C('config.basic_price'));//起送价
        }

        $data = array('error_code' => false);
        $data['delivery_fee'] = $delivery_fee;//起步配送费
        $data['village'] = $village;

        $data['basic_distance'] = $basic_distance;//起步距离
        $data['per_km_price'] = $per_km_price;//超出起步距离部分的距离每公里的单价
        $data['basic_weight'] = $basic_weight ; //起步重量
        $data['per_weight_price'] = $per_weight_price; //超出起步重量部分每千克的单价
        $data['delivery_fee2'] = $delivery_fee2;//起步配送费
        $data['basic_distance2'] = $basic_distance2;//起步距离
        $data['per_km_price2'] = $per_km_price2;//超出起步距离部分的距离每公里的单价
        $data['basic_weight2'] = $basic_weight2 ; //起步重量
        $data['per_weight_price2'] = $per_weight_price2; //超出起步重量部分每千克的单价
        $data['delivery_fee3'] = $delivery_fee3;//起步配送费
        $data['basic_distance3'] = $basic_distance3;//起步距离
        $data['per_km_price3'] = $per_km_price3;//超出起步距离部分的距离每公里的单价
        $data['basic_weight3'] = $basic_weight3 ; //起步重量
        $data['per_weight_price3'] = $per_weight_price3; //超出起步重量部分每千克的单价
        $data['delivery_fee_reduce'] = 0;//配送费减免的金额
        return $data;

    }



    public function village_deliver_info() {
        $delivery_fee = 0;
        $per_km_price = 0;
        $basic_distance = 0;
        $basic_weight = 0;
        $per_weight_price = 0;

        $delivery_fee2 = 0;
        $per_km_price2 = 0;
        $basic_distance2 = 0;
        $basic_weight2 = 0;
        $per_weight_price2 = 0;

        $delivery_fee3 = 0;
        $per_km_price3 = 0;
        $basic_distance3 = 0;
        $basic_weight3 = 0;
        $per_weight_price3 = 0;
        
        $delivertime_start = '00:00';
        $delivertime_stop = '00:00';
        $delivertime_start2 = '00:00';
        $delivertime_stop2 = '00:00';
        $delivertime_start3 = '00:00';
        $delivertime_stop3 = '00:00';
        if(C('config.village_group_deliver_custom')==1){
            $deliver_config = C('config.village_group_deliver_custom_time');
            $deliver_config = $deliver_config?json_decode($deliver_config,true):[];
            foreach ($deliver_config as $key=>$item){
                if($key==0){
                    $delivery_fee = $item['delivery_fee'];
                    $per_km_price = $item['per_km_fee'];
                    $basic_distance = $item['delivery_distance'];
                    $basic_weight = $item['delivery_weight'];
                    $per_weight_price = $item['per_weight_fee'];
                    $delivertime_start = $item['start_time'].':00';
                    $delivertime_stop = $item['end_time'].':00';
                }elseif($key==1){
                    $delivery_fee2 = $item['delivery_fee'];
                    $per_km_price2 = $item['per_km_fee'];
                    $basic_distance2 = $item['delivery_distance'];
                    $basic_weight2 = $item['delivery_weight'];
                    $per_weight_price2 = $item['per_weight_fee'];
                    $delivertime_start2 = $item['start_time'].':00';
                    $delivertime_stop2 = $item['end_time'].':00';
                }elseif($key==2){
                    $delivery_fee3 = $item['delivery_fee'];
                    $per_km_price3 = $item['per_km_fee'];
                    $basic_distance3 = $item['delivery_distance'];
                    $basic_weight3 = $item['delivery_weight'];
                    $per_weight_price3 = $item['per_weight_fee'];
                    $delivertime_start3 = $item['start_time'].':00';
                    $delivertime_stop3 = $item['end_time'].':00';
                }
            }
        }else{
            $delivery_fee = C('config.delivery_fee');
            $per_km_price = C('config.per_km_price');
            $basic_distance = C('config.basic_distance');

            $delivery_fee2 = C('config.delivery_fee2');
            $per_km_price2 = C('config.per_km_price2');
            $basic_distance2 = C('config.basic_distance2');

            $delivery_fee3 = C('config.delivery_fee3');
            $per_km_price3 = C('config.per_km_price3');
            $basic_distance3 = C('config.basic_distance3');

            $delivery_times = explode('-', C('config.delivery_time'));
            $delivertime_start = $delivery_times[0] . ':00';
            $delivertime_stop = $delivery_times[1] . ':00';
            $delivery_times2 = explode('-', C('config.delivery_time2'));
            $delivertime_start2 = $delivery_times2[0] . ':00';
            $delivertime_stop2 = $delivery_times2[1] . ':00';
            $delivery_times3 = explode('-', C('config.delivery_time3'));
            $delivertime_start3 = $delivery_times3[0] . ':00';
            $delivertime_stop3 = $delivery_times3[1] . ':00';
        }
        $deliver_info = array(
            'delivery_fee' => $delivery_fee,
            'basic_distance' => $basic_distance,
            'per_km_price' => $per_km_price,
            'delivertime_start' => $delivertime_start,
            'delivertime_stop' => $delivertime_stop,
            'basic_weight' => $basic_weight,
            'per_weight_price' => $per_weight_price,
            'delivery_fee2' => $delivery_fee2,
            'basic_distance2' => $basic_distance2,
            'per_km_price2' => $per_km_price2,
            'delivertime_start2' => $delivertime_start2,
            'delivertime_stop2' => $delivertime_stop2,
            'basic_weight2' => $basic_weight2,
            'per_weight_price2' => $per_weight_price2,
            'delivery_fee3' => $delivery_fee3,
            'basic_distance3' => $basic_distance3,
            'per_km_price3' => $per_km_price3,
            'delivertime_start3' => $delivertime_start3,
            'delivertime_stop3' => $delivertime_stop3,
            'basic_weight3' => $basic_weight3,
            'per_weight_price3' => $per_weight_price3,
        );
        return $deliver_info;
    }


    public function getSelectTime($return, $paramTime = null)
    {
        if ($paramTime === null) {
            $paramTime = time();
        }
        //预订的天数
        $advance_day = 3;

        //配送时间段
        $start_time = $return['village']['delivertime_start'];
        $stop_time = $return['village']['delivertime_stop'];
        $start_time2 = $return['village']['delivertime_start2'];
        $stop_time2 = $return['village']['delivertime_stop2'];

        $start_time3 = $return['village']['delivertime_start3'];
        $stop_time3 = $return['village']['delivertime_stop3'];
        if ($start_time == $stop_time && $start_time == '00:00:00') {//时间段一，24小时
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
        }
        if ($start_time2 == $stop_time2 && $start_time2 == '00:00:00') {//没有时间段二
            $stop_time2 = $start_time2 = 0;
        }
        if (($start_time3 == $stop_time3 && $start_time3 == '00:00:00') || ($start_time3 == $stop_time3 && $start_time3 == ':00')) {//没有时间段三
            $stop_time3 = $start_time3 = 0;
        }
        $village_group_delivery_base_time = C('config.village_group_delivery_base_time') ? intval(C('config.village_group_delivery_base_time')) : 30; //默认30分钟
        $nowTime = $paramTime + $village_group_delivery_base_time * 60;//默认的期望送达时间


        $startTime1 = strtotime(date('Y-m-d ' . $start_time));
        $stopTime1 = strtotime(date('Y-m-d ' . $stop_time));
        $deliverTimeList = array();
        if ($startTime1 > $stopTime1) {
            $deliverTimeList[] = array(
                's' => strtotime(date('Y-m-d 00:00:00')),
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1 + 86400,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        } else {
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }
        if ($start_time2 != '0' && $stop_time2 != '0') {
            $startTime2 = strtotime(date('Y-m-d ' . $start_time2));
            $stopTime2 = strtotime(date('Y-m-d ' . $stop_time2));
            if ($startTime2 > $stopTime2) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2 + 86400,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
            }
        }

        if ($start_time3 != '0' && $stop_time3 != '0') {
            $startTime3 = strtotime(date('Y-m-d ' . $start_time3));
            $stopTime3 = strtotime(date('Y-m-d ' . $stop_time3));
            if ($startTime3 > $stopTime3) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3 + 86400,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
            }
        }

        if ($stopTime1 - $startTime2 > 86330 && $stopTime1 - $startTime2 < 86400) {//时间段1和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
            );
        }
        if ($stopTime2 - $startTime1 > 86330 && $stopTime2 - $startTime1 < 86400) {//时间段2和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }

        if ($stopTime1 - $startTime3 > 86330 && $stopTime1 - $startTime3 < 86400) {//时间段1和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
            );
        }
        if ($stopTime3 - $startTime1 > 86330 && $stopTime3 - $startTime1 < 86400) {//时间段3和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }

        if ($stopTime2 - $startTime3 > 86330 && $stopTime2 - $startTime3 < 86400) {//时间段2和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
            );
        }
        if ($stopTime3 - $startTime2 > 86330 && $stopTime3 - $startTime2 < 86400) {//时间段3和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
            );
        }
        $newD = array(); // 配送时间
        for ($day = 0; $day <= $advance_day; $day ++) {

            foreach ($deliverTimeList as $dtime) {
                $dtime['s'] += $day * 86400;
                $dtime['e'] += $day * 86400;
                $newD[] = $dtime;
            }
        }

//        foreach ($deliverTimeList as $dtime) {
//            $dtime['s'] += ($advance_day + 1) * 86400;
//            $dtime['e'] += ($advance_day + 1) * 86400;
//            $newD[] = $dtime;
//        }

        $dateList = array();
        $first_day = '';
        foreach ($newD as $row) {
            $stime = $row['s'];
            $etime = $row['e'];
            $_m_00 = strtotime(date('Y-m-d H:00', $stime));
            $_m_15 = strtotime(date('Y-m-d H:15', $stime));
            $_m_30 = strtotime(date('Y-m-d H:30', $stime));
            $_m_45 = strtotime(date('Y-m-d H:45', $stime));
            if ($_m_00 >= $stime) {
                $stime = $_m_00;
            } elseif ($_m_15 >= $stime) {
                $stime = $_m_15;
            } elseif ($_m_30 >= $stime) {
                $stime = $_m_30;
            } elseif ($_m_45 >= $stime) {
                $stime = $_m_45;
            } else {
                $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
            }

            if ($row['s'] <= $nowTime && $nowTime < $row['e']) {
                $dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)] = array(
                    'hour_minute' => date('H:i', $nowTime),
                    'time_select' => $row['select'],
                    'delivery_fee_old' => $row['delivery_fee_old'],
                    'delivery_fee' => $row['delivery_fee']
                );
            }
            for ($nowDate = $stime; $nowDate <= $etime;) {
                if ($nowDate>$nowTime) {
                    $dateList[date('Y-m-d', $nowDate)][date('H:i', $nowDate)] = array(
                        'hour_minute' => date('H:i', $nowDate),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee']
                    );
                }
                $nowDate += 900;
            }
        }
        return $dateList;
    }


    // 验证核销
    public function verify_notice($order_detail,$order,$my_header,$from_type){
        if ($order['status'] == 2) {
            return array('error'=>1,'msg'=>'已验证消费');
        }

        // 验证消费的商品
        $order_info_arr = array();

        // 验证消费的单个商品佣金总和
        $leader_money = 0;

        // 验证消费的 、设置了单个商品佣金的商品总价
        $goods_leader_money_total = 0;

        // 验证消费商品id
        $bill_goods_ids = array();
        // 未验证消费商品id
        $un_bill_goods_ids = array();
        // 未自提的商品总价
        $un_bill_leader_money_total = 0;
        // 自提的商品总价
        $bill_leader_money_total = 0;

        // 处理优惠信息
        if ($order_detail['good_discount']) {
            $good_discount = $order_detail['good_discount'];
        } else {
            $good_discount = array();
        }
        // 取订单期数，如果没有默认为1
        $delivery_periods = $order['delivery_periods']>0?intval($order['delivery_periods']):1;
        // 加价金额总计
        $increase_price_total = 0;
        foreach ($order_detail['goods_detail'] as $v) {
            if ($v['area_price']>0) {
                $v['price'] = $v['area_price'];
            }
            if ($good_discount && $good_discount[$v['goods_id']] && $good_discount[$v['goods_id']]['level_discount'] > 0) {
                $level_discount_increase = $good_discount[$v['goods_id']]['level_discount_increase']?intval($good_discount[$v['goods_id']]['level_discount_increase']):0;
                $v['discount_price'] = $v['price'] - $good_discount[$v['goods_id']]['level_discount']-$level_discount_increase;
                $v['discount_price'] = $v['discount_price']>0?round_number($v['discount_price'],2):0;
            } else {
                $v['discount_price'] = $v['price'];
            }
            if ($delivery_periods>0) {
                $v['discount_price'] = $v['discount_price']/$delivery_periods;
                $v['purchase_price'] = $v['purchase_price']/$delivery_periods;
            }
            if ($v['deliver_status'] == 3) { // 已自提 已自提的才能核销 单个商品核销
                $order_info_arr[$v['mer_id']]['total_money'] += $v['purchase_price']*$v['num'];
                $order_info_arr[$v['mer_id']]['total_pay_money'] += $v['discount_price']*$v['num'];

                $order_info_arr[$v['mer_id']]['group_id'] .= $v['group_id'] . ',';
                if($v['leader_money']>0){
                    $goods_leader_money_total += $v['discount_price']*$v['num'];
                    // 下单支付成功时商品的佣金比例计算
                    $leader_money += ($v['discount_price']*$v['num'])*$v['order_leader_money']/100;
                }
                $bill_goods_ids[] = $v['id'];
                $bill_leader_money_total += $v['discount_price']*$v['num'];
                if ($v['increase_price']>0) {
                    $increase_price_total += $v['increase_price']*$v['num'];
                }
            }else if ($v['deliver_status'] != 4 && $v['deliver_status'] != 5 ){
                $un_bill_leader_money_total += $v['discount_price']*$v['num'];
                $un_bill_goods_ids[] = $v['id'];
            }
        }
        if ($delivery_periods>0) {
            $increase_price_total = $increase_price_total/$delivery_periods;
        }

        // 没有设置单个商品佣金的按团长佣金计算
        // 统一使用百分比进行抽成
        // 处理佣金比率问题
        $percent = floatval(str_replace('%','',$my_header['money']));
        if ($percent<=0 && $my_header['pick_address']) {
            if ($my_header['pick_address']['province_id']>0 || $my_header['pick_address']['city_id']>0 || $my_header['pick_address']['area_id']>0){
                $sale_num = D('Village_group_order')->sum_header_sale_num($my_header['id']);
                // 如果存在
                $province_id = $my_header['pick_address']['province_id'];
                $city_id = $my_header['pick_address']['city_id'];
                $area_id = $my_header['pick_address']['area_id'];
                $leader_money_percent = 0;
                if ($area_id > 0) {
                    $leader_money_percent = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
                        ->getField('leader_money');
                    if (!$leader_money_percent && $sale_num>0) {
                        $leader_money_percent = M('Village_group_leader_area_level')
                            ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_num)))
                            ->order('level DESC')
                            ->getField('leader_money');
                    }
                }
                if ($city_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
                    $leader_money_percent = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
                        ->getField('leader_money');
                    if (!$leader_money_percent && $sale_num>0) {
                        $leader_money_percent = M('Village_group_leader_area_level')
                            ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_num)))
                            ->order('level DESC')
                            ->getField('leader_money');
                    }
                }
                if ($province_id > 0 && (!$leader_money_percent || $leader_money_percent<=0)) {
                    $leader_money_percent = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num), 'max_sale_num' => array('egt', $sale_num)))
                        ->getField('leader_money');
                    if (!$leader_money_percent && $sale_num>0) {
                        $leader_money_percent = M('Village_group_leader_area_level')
                            ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_num)))
                            ->order('level DESC')
                            ->getField('leader_money');
                    }
                }
                if ($leader_money_percent) {
                    $percent = floatval(str_replace('%','',$leader_money_percent));
                }

            }
        }
        $goods_leader_money_total = round_number($goods_leader_money_total,2);
//            $un_bill_leader_money_total = round_number($un_bill_leader_money_total,2);
        $header_money = ($bill_leader_money_total-$goods_leader_money_total)*$percent/100+$leader_money+$increase_price_total;
        $header_money = round_number($header_money,2);

        // 改变已核销商品状态
        $update_status = M('Village_group_order_detail')->where(array('id'=>array('in',$bill_goods_ids)))->setField('status',4);
        if (!$update_status) {
            return array('error'=>1,'msg'=>'改变商品状态失败');
        }

        // 增加商家余额
        foreach ($order_info_arr as $mer_id => $vv) {
            $order_info = array();
            $order_info['total_money'] = $vv['total_money'];
            $order_info['order_type'] = 'village_group';
            $order_info['order_id'] = $order['order_id'];
            $order_info['desc'] = "用户购买".$this->config['village_group_alias']."记入收入";
            $order_info['mer_id'] = $mer_id;
            $order_info['is_own'] = 0;
            D('Merchant_money_list')->add_money($order_info);
        }

        //拼团验证消费，增加团长分佣
        D('User')->add_money($my_header['uid'],$header_money,'拼团验证消费，增加团长分佣');
        M('Village_group_header')->where(array('id'=>$my_header['id']))->setInc('header_money',$header_money);

        // 改变订单状态
        if (empty($un_bill_goods_ids) && ($order['village_group_type']!=1 || $order['newest_delivery_periods']>=$order['delivery_periods'])) { // 全部商品都核销
            $set_data = array(
                'bill_time' => time(),
                'status' => 2
            );
            M('Village_group_order')->where(array('order_id'=>$order['order_id']))->save($set_data);
        }
        // 改变对应周期购最新期数
        if ($order['village_group_type']==1 && $order['newest_delivery_periods']<$order['delivery_periods']){
            M('Village_group_order')->where(array('order_id'=>$order['order_id']))->setInc('newest_delivery_periods');
        }

        // 用户信息
        $now_user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();

        // 订单状态改变记录
        $param['order_id'] = $order['order_id'];
        $param['from_type'] = $from_type;
        $param['name'] =$now_user['nickname'];
        $param['phone'] =$now_user['phone'];
        if ((!empty($un_bill_goods_ids) && $order['village_group_type']!=1) || $order['newest_delivery_periods']<$order['delivery_periods']) {
            // 部分商品都核销
            $param['status'] = 3;
            $param['note'] = '用户部分商品已提货，增加团长佣金';
        }else{
            // 全部商品都核销
            $param['status'] = 2;
            $param['note'] = '用户已全部提货，完成消费，增加团长佣金';
        }
        D('Village_group')->add_log($param);

        // 给用户对应添加积分
        if($this->config['open_score_get_percent']==1){
            $score_get = $this->config['score_get_percent']/100;
        }else{
            $score_get = $this->config['user_score_get'];
        }
        // 全部验证消费后获得积分
        if ($score_get > 0 && (empty($un_bill_goods_ids) && ($order['village_group_type']!=1 || $order['newest_delivery_periods']>=$order['delivery_periods']))) {
            // 这里需要处理需要计算的订单总额
            $total_money = $order['total_money'];
            if ($order['discount_price'] > 0) {
                // 如果优惠后金额大于0 以此计算
                $total_money = $order['discount_price'];
            }
            D('User')->add_score($order['uid'], round($total_money * $score_get), '购买 ' . $order['order_name'] . ' 消费' . floatval($total_money) . $this->config['Currency_txt'].' 获得'.$this->config['score_name']);
            D('Scroll_msg')->add_msg('village_group',$now_user['uid'],'用户'.str_replace_name($now_user['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'购买'.$this->config['group_alias_name'].'拼团成功并消费获得'.$this->config['score_name']);
        }
        return array('error'=>0,'msg'=>'验证消费成功');
    }
}

?>