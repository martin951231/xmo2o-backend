<?php
/*平台管理商城控制器*/
class MallAction extends BaseAction
{
    const GOODS_SORT_LEVEL = 3;

    public function order()
    {
        $this->redirect('Shop/order', array('from_type' => 2));
    }

    public function discount()
    {
        $this->redirect('Shop/discount', array('shop_type' => 2));
    }

    /* 菜品管理 */

    public function goods_list()
    {

        /*if ($_GET['sort_id']) {
            $now_sort = $this->check_sort($_GET['sort_id']);
        }

        $f_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$now_sort['fid']])->find();
        if ($f_sort) {
            $ff_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$f_sort['fid']])->find();
            if (!$ff_sort) {
                $ff_sort['sort_id'] = $f_sort['sort_id'];
                $f_sort['sort_id'] = $_GET['sort_id'];
            }
        }else{
            $f_sort['sort_id'] = $_GET['sort_id'];
            $ff_sort['sort_id'] = $_GET['sort_id'];
        }*/

        // 商品分类
        $sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);
         /*goods_type条件商城外卖*/
        // 条件查询去掉没有商品的分类
       // $condition_goods_all['shop_type']=1;

        // 商城
        $condition_goods_all['cat_fid']=array('gt',0);

        if (isset($_GET['status']) || isset($_GET['stock_num'])) {
            /*if (isset($_GET['status'])) {
                $condition_goods['status'] = $_GET['status'];
                $condition_goods_all['status'] = $_GET['status'];
            }

            if (isset($_GET['stock_num'])) {
                $condition_goods['stock_num'] = $_GET['stock_num'];
                $condition_goods_all['stock_num'] = $_GET['stock_num'];
            }*/

            $goods_list_all = M('Shop_goods')->field('goods_id,sort_id')->where($condition_goods_all)->group('sort_id')->select();
            $goods_list_all = array_column($goods_list_all, 'goods_id','sort_id');

            $result = array();
            $index = 0;
            foreach ($sort_list as $l) {
                $temp = array();
                $temp['sort_id'] = $l['sort_id'];
                $temp['cat_id'] = $l['sort_id'];
                $temp['store_id'] = $l['store_id'];
                $temp['cat_name'] = $l['sort_name'];
                $temp['sort_name'] = $l['sort_name'];
                $temp['level'] = $l['level'];
                $temp['week'] = $l['week'];
                $temp['index'] = $index;
                $temp['findex'] = 0;
                $temp['fid'] = 0;
                $temp['operation_type'] = $l['operation_type'];

                if (isset($goods_list_all[$l['sort_id']])) {
                    $temp['goods_count'] = count($goods_list_all[$l['sort_id']]);
                    $temp['son_list'] = array();
                } elseif ($l['son_list']) {
                    $temp['product_list'] = array();
                    $son_list = $this->formatList($l['son_list'], $goods_list_all, $index, $l['sort_id'],true);
                    if ($son_list) {
                        $temp['son_list'] = $son_list;
                    } else {
                        $temp = null;
                    }
                } else {
                    $temp = null;
                }
                if (!empty($temp)) {
                    $index ++;
                    $result[] = $temp;
                }
            }
            $sort_list = $result;
        }


        $first_sort_id = 0;
        $sort_list_tmp = array();
        $i=0;
        foreach ($sort_list as $key => $value) {
            $tmp = array();
            $tmp['sort_id'] = $value['sort_id'];
            $tmp['sort_name'] = $value['sort_name'];
            $tmp['operation_type'] = $value['operation_type'];
            /*if ($value['sort_id']==$ff_sort['sort_id']) {
                $tmp['selected'] = 1;
            }else{
                $tmp['selected'] = 0;
            }
            if(!$_GET['sort_id']&&$i==0){
                $first_sort_id = $tmp['sort_id'];
                $tmp['selected'] = 1;
            }*/
            if ($value['son_list']) {
                $j=0;
                foreach ($value['son_list'] as $son_k => $_son) {
                    $_son_tmp = array();
                    $_son_tmp['sort_id'] = $_son['sort_id'];
                    $_son_tmp['sort_name'] = $_son['sort_name'];
                    $_son_tmp['operation_type'] = $_son['operation_type'];

                    if(!$_GET['sort_id']&&$i==0&&$j==0&&$_son['operation_type']!=1){
                        $first_sort_id = $_son['sort_id'];
                        $_son_tmp['selected'] = 1;
                        $tmp['selected'] = 1;
                        $tmp['child_selected'] = 1;
                    }

                    if ($_son['son_list']) {
                        $k=0;
                        foreach ($_son['son_list'] as $son_k2 => $_son2) {
                            $_son_tmp2 = array();
                            $_son_tmp2['sort_id'] = $_son2['sort_id'];
                            $_son_tmp2['sort_name'] = $_son2['sort_name'];
                            $_son_tmp2['operation_type'] = $_son2['operation_type'];
                            if(!$_GET['sort_id']&&$i==0&&$j==0&&$k==0&&$_son2['operation_type']==0){
                                $first_sort_id = $_son2['sort_id'];
                                $_son_tmp2['selected'] = 1;
                                $_son_tmp['selected'] = 1;
                                $_son_tmp['child_selected'] = 1;
                                $tmp['selected'] = 1;
                                $tmp['child_selected'] = 1;
                            }
                            $k++;
                            $_son['son_list'][$son_k2] = $_son_tmp2;
                        };
                        $_son_tmp['son_list'] = $_son['son_list'];
                    }
                    $j++;
                    $tmp['son_list'][$son_k] = $_son_tmp;
                };
            }
            $i++;
            $sort_list_tmp[$key] = $tmp;
        }

        $this->assign('sort_list', $sort_list_tmp);

        $this->display();
    }
    /*选中查询商品*/
    public function find_goods(){
        $where['store_id']=$where1['store_id']=$_POST['store_id'];
        if(!empty($_POST['sort_id'])){
            $where['sort_id']=$_POST['sort_id'];
        }
        $list1= M('Merchant_store')->where($where1)->find();
        if($list1['have_shop']==0){
            $where['cat_fid']=array('gt',0);
        }
        $list= M('Shop_goods')->where($where)->select();
        $default_img=$this->config['goods_default_image'];
        foreach ($list as $key=>$val){
            if(!$val['image']){
               if($default_img){
                   $list[$key]['image']=$default_img;
               }else{
                   $list[$key]['image']=C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
               }
            }
        }
        $total= M('Shop_goods')->where($where)->count();
       // fdump(M('Shop_goods')->getLastSql(),"i8888",1);
        $perPage=15;
        exit(json_encode(array('list'=>$list,'perPage'=>$perPage,'total'=>$total)));
    }

    /*商品名称查找商品*/
    public function name_find_goods(){
        $where[]=$where1[]=array( 'store_id' => $_POST['store_id']);
        if(!empty($_POST['goods_name'])){
            $goods_name=$_POST['goods_name'];
            $where[] = array( 'name' => array('like', '%'. $goods_name .'%') );

            $list1= M('Merchant_store')->where($where1)->find();
            if($list1['have_shop']=0){
                $where['goods_type']=1;
            }
        }
        $list= M('Shop_goods')->where($where)->select();
        $total= M('Shop_goods')->where($where)->count();
        $perPage=15;
        exit(json_encode(array('list'=>$list,'perPage'=>$perPage,'total'=>$total)));
    }

    /*找到店铺*/
    public function find_stores(){
        $where=[];
        /*是否完善店铺标志1完善*/
        //$where['status']=1;
        if(!empty($_POST['store_name'])){
            $store_name=$_POST['store_name'];
            //$where['have_mall'] = array('eq', 1);
            $where['name'] =array('like', '%'. $store_name .'%');
        }
        $list1['have_shop']="";
        if(!empty($_POST['store_id'])){
            $store_id=$_POST['store_id'];
            $where1[]=array( 'store_id' => $store_id);
            $where['store_id'] =array('neq',$store_id) ;
            $list1= M('Merchant_store')->where($where1)->find();
            if($list1['have_shop']==1 && $list1['have_mall']==0){
                /*有快店的店铺复制的商品要匹配有快点的店铺中*/
                //$where['have_shop'] = 1;
                $where['_string'] = 'have_shop= 1 or (have_shop=1 and have_mall=1)';

            }

            if($list1['have_shop']==0 && $list1['have_mall']==1){
                /*有快店的店铺复制的商品要匹配有快点的店铺中*/
                $where['_string'] = 'have_mall= 1 or (have_shop=1 and have_mall=1)';
            }

            if($list1['have_shop']==1 && $list1['have_mall']==1){
                $where['have_mall'] = array('eq', 1);
                $where['have_shop'] = array('eq', 1);
            }
        }

        $list= M('Merchant_store')->where($where)->select();
        foreach ($list as $key=>$val){
            /*快店*/
            if($val['have_shop']==1){
                $list[$key]['sid'] = M('Merchant_store_shop')->where(array('store_id'=>$val['store_id']))->count();
                $list[$key]['has_apply_category'] = M('Shop_category_relation_apply')->where(array('store_id'=>$val['store_id']))->count();
                $list[$key]['has_category'] = M('Shop_category_relation')->where(array('store_id'=>$val['store_id']))->count();
                if($list[$key]['sid']==0 && $list[$key]['has_category']==0){
                    unset($list[$key]);
                }
            }
        }
        $total= M('Merchant_store')->count();
        if($total==0){
            $perPage=15;
            exit(json_encode(array('list'=>$list,'perPage'=>$perPage,'total'=>$total,"status"=>0)));
        }else{
            $perPage=15;
            exit(json_encode(array('list'=>$list,'perPage'=>$perPage,'total'=>$total,"status"=>1)));
        }
    }
    /* 菜品管理 */
    public function ajax_goods_list()
    {

        if ($_GET['sort_id']) {
            $now_sort = $this->check_sort1($_GET['sort_id']);
        }else{
            exit(json_encode(array('goods_list'=>array())));
        }

        if (isset($_GET['status']) || isset($_GET['stock_num'])) {
            if (isset($_GET['status'])) {
                $condition_goods['status'] = $_GET['status'];
            }

            if (isset($_GET['stock_num'])) {
                $condition_goods['stock_num'] = $_GET['stock_num'];
            }
        }

        //包含在所有店铺类型中的商品
        $condition_goods['shop_type']=1;
        $condition_goods['cat_fid']=array('gt',0);

        $condition_goods['sort_id'] = $now_sort['sort_id'];
        $database_goods = M('Shop_goods');
        $count_goods = $database_goods->where($condition_goods)->count();
        // import('@.ORG.merchant_page');
        // import('@.ORG.ajaxpage');
        import('@.ORG.diypage');
        $p = new Page($count_goods, 30);

        $goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $today = date('Ymd');
        $now_time = time();
        $goods_image_class = new goods_image();
        foreach ($goods_list as &$rl) {
            $rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
            if ($rl['sell_day'] != $today) {
                $rl['today_sell_count'] = 0;
            }

            $rl['seckill_status'] = false;
            if($rl['seckill_open_time'] < $now_time && $rl['seckill_close_time'] > $now_time){
                $rl['seckill_status'] = true;
                $rl['stock_num_t'] = $rl['seckill_stock'].L_('（限时优惠）');
            }
            elseif ($rl['stock_num'] == -1) {
                $rl['stock_num_t'] = L_('无限');
            } else {
                $rl['stock_num_t'] = $rl['stock_num'];
            }
            if($rl['cat_fid']>0){
                $rl['shop_type']=1;
            }
            $rl['last_time'] = date('Y-m-d H:i:s',$rl['last_time']);

            // 图片
            if ($rl['image']) {
                $tmp_pic_arr = explode(';', $rl['image']);
                foreach ($tmp_pic_arr as $k => $v) {
                    $rl['pic_arr'][$k]['title'] = $v;
                    $rl['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                    $k==0 && $rl['image_url'] = $rl['pic_arr'][$k]['url']['s_image'];
                }
            }else{
                if (C('config.is_open_goods_default_image')&&C('config.goods_default_image')) {
                    $rl['image_url'] = C('config.goods_default_image');
                }else{
                    $rl['image_url'] = C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
                }
            }
        }

        // 发布批发市场权限控制
        $is_publish_market_goods = $this->merchant_session['is_publish_market_goods']?$this->merchant_session['is_publish_market_goods']:0;

        $merchant_menu = M('New_merchant_menu')->where(['id'=>'10008'])->find();
        $is_fabu = $merchant_menu ? 1 : 0;
        exit(json_encode(array('goods_list'=>$goods_list,'page'=>$p->show(),'is_publish_market_goods'=>$is_publish_market_goods,'is_fabu'=>$is_fabu)));
    }

    /* 检测分类存在 */
    protected function check_sort1($sort_id)
    {
        $database_goods_sort = D('Shop_goods_sort');
        $condition_goods_sort['sort_id'] = $sort_id;
        $now_sort = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->find();
        if (empty($now_sort)) {
            $this->error(L_('分类不存在！'));
        }
        if(!empty($now_sort['image'])){
            $sort_image_class = new goods_sort_image();
            $now_sort['see_image'] = $sort_image_class->get_image_by_path($now_sort['image'],$this->config['site_url'],'s');
        }
        if ($now_sort['week'] != null) {
            $now_sort['week'] = explode(',', $now_sort['week']);
        }
        return $now_sort;
    }

    private function formatList($list, $newList, $findex, $fid, $has_goods=false)
    {
        if (empty($list)) {
            return $list;
        }
        $result = array();
        $index = 0;
        foreach ($list as $l) {
            $temp = array();
            $temp['cat_id'] = $l['sort_id'];
            $temp['sort_id'] = $l['sort_id'];
            $temp['store_id'] = $l['store_id'];
            $temp['level'] = $l['level'];
            $temp['week'] = $l['week'];
            $temp['index'] = $index;
            $temp['findex'] = $findex;
            $temp['fid'] = $fid;
            $temp['sort_name'] = $l['sort_name'];
            $temp['operation_type'] = $l['operation_type'];
            $temp['status'] = $l['status'];
            $temp['is_must'] = $l['is_must'];

            if (isset($newList[$l['sort_id']])) {
                $temp['goods_count'] = count($newList[$l['sort_id']]);
                $temp['son_list'] = array();
            } elseif ($l['son_list']) {
                $temp['goods_count'] = 0;
                $son_list = $this->formatList($l['son_list'], $newList, $index, $l['sort_id'],$has_goods);
                if ($son_list) {
                    $temp['son_list'] = $son_list;
                } else {
                    $temp = null;
                }
            } else {
                if ($has_goods) {
                    $temp = null;
                }else{
                    $temp['goods_count'] = 0;
                    $temp['son_list'] = array();
                }
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
        return $result;
    }

    /* 检测店铺存在，并检测是不是归属于商家 */
    protected function check_store1($store_id)
    {
        if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $store_id) {
            $this->error(L_('您没有这个权限'));
        }

        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $store_id;
        /*$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];*/
        $now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
        if (empty($now_store)) {
            $this->error(L_('店铺不存在！'));
        } else {
            //return $now_store;
            if ($now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find()) {
                if (!empty($now_shop['background'])) {
                    $image_tmp = explode(',', $now_shop['background']);
                    $now_shop['background_image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
                }
                return array_merge($now_store, $now_shop);
            }
            return $now_store;
            $now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find();
            return array_merge($now_store, $now_shop);
        }
    }


    /*选择店铺商品，克隆商品*/
    public function clone_store_get_goods()
    {
        $source_store_id = '444';//要商品的店铺
        $goodids=['53713','54347'];
        $store_id = 440;//选择商品的店铺
        //$this->config("is_open_clone");
        foreach ($goodids as $good_id) {
            $goods = M('Shop_goods')->field(true)->where(array('goods_id' => $good_id))->find();
            $ret=$this->add_goods_sort($goods['sort_id'],$source_store_id);
            if($ret==1){
                $newsort = M('Shop_goods_sort')->field(true)->where(array('source_sort_id' => $goods['sort_id'],'store_id'=>$source_store_id))->find();
                $target_sort_id=$newsort['sort_id'];

                if ($tmp_goods = M('Shop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $source_store_id))->find()) {
                    // fdump("找到相同名字商品跳出","i8888",1);
                    continue;
                }
                else {
                    // $source_goods_id = $goods['goods_id'];
                    unset($goods['goods_id']);
                    $goods['store_id'] = $source_store_id;
                    $goods['sort_id'] = $target_sort_id;
                    $goods['print_id'] = 0;
                    /*fdump("----复制商品","i8888",1);
                    fdump($goods,"i8888",1);
                    fdump("复制商品-----","i8888",1);*/
                    $target_goods_id = M('Shop_goods')->add($goods);
                    //fdump($target_goods_id,"i8888",1);
                    $pro_map = $spec_map = $spec_value_map = array();
                    if ($goods['is_properties']) {
                        $properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $good_id))->select();
                        foreach ($properties as $pro_data) {
                            $source_pro_id = $pro_data['id'];
                            unset($pro_data['id']);
                            $pro_data['goods_id'] = $target_goods_id;
                            /*fdump("----复制属性","i8888",1);
                            fdump($pro_data,"i8888",1);
                            fdump("复制属性-----","i8888",1);*/
                            $pro_map[$source_pro_id] = M('Shop_goods_properties')->add($pro_data);
                            //fdump($pro_map[$source_pro_id],"i8888",1);
                            $lang_model = D('Lang');
                            $pro_data['id'] = $pro_map[$source_pro_id];
                            $lang_model->add_lang_data('Shop_goods_properties', $pro_data);
                        }
                    }
                    if ($goods['spec_value']) {
                        $spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $good_id, 'store_id' => $store_id))->select();
                        foreach ($spec_list as $spec) {
                            $source_spec_id = $spec['id'];
                            unset($spec['id']);

                            $spec['store_id'] = $source_store_id;
                            $spec['goods_id'] = $target_goods_id;
                            /*fdump("----复制规格","i8888",1);
                            fdump($spec,"i8888",1);
                            fdump("复制规格-----","i8888",1);*/
                            if ($new_spec_id = M('Shop_goods_spec')->add($spec)) {
                                //fdump($new_spec_id,"i8888",1);
                                //多语言
                                $lang_model = D('Lang');
                                $spec['id'] = $new_spec_id;
                                $lang_model->add_lang_data('Shop_goods_spec', $spec);
                                $spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    $source_spec_value_id = $spec_value['id'];
                                    unset($spec_value['id']);
                                    $spec_value['sid'] = $new_spec_id;
                                    /*fdump("----复制规格值","i8888",1);
                                    fdump($spec_value,"i8888",1);
                                    fdump("复制规格值-----","i8888",1);*/
                                    $spec_value_map[$source_spec_value_id] = M('Shop_goods_spec_value')->add($spec_value);
                                    //fdump($spec_value_map[$source_spec_value_id],"i8888",1);
                                    //多语言
                                    $lang_model = D('Lang');
                                    $spec_value['id'] = $spec_value_map[$source_spec_value_id];
                                    $lang_model->add_lang_data('Shop_goods_spec_value', $spec_value);
                                }
                            }
                        }

                        //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                        $spec_array = explode('#', $goods['spec_value']);
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $spec_val_ids = explode(':', $row_array[0]);
                            $new_ids = array();
                            foreach ($spec_val_ids as $tid) {
                                $new_ids[] = $spec_value_map[$tid];
                            }
                            $row_array[0] = implode(':', $new_ids);
                            if (count($row_array) > 2 && $row_array[2] && strstr($row_array[2], '=')) {
                                $pro_str_ids = explode(':', $row_array[2]);
                                $new_pro_ids = array();
                                foreach ($pro_str_ids as $pstr) {
                                    $v_k_a = explode('=', $pstr);
                                    $new_pro_ids[] = $pro_map[$v_k_a[0]] . '=' . $v_k_a[1];
                                }
                                $row_array[2] = implode(':', $new_pro_ids);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        M('Shop_goods')->where(array('goods_id' => $target_goods_id))->save(array('spec_value' => implode('#', $target_spec_value_array)));
                    }
                }
            }
        }
        $this->success(L_('克隆完成'));
    }

    /*选择商品到多个店铺*/
    public function clone_goods_to_store(){
        $goodids = $_POST['check_goods'];//复制的商品
        $stores = $_POST['check_stores'];//复制商品到的店铺*/
        fdump("----8888888商品id888888888----start","i8888",1);
        fdump($goodids,"i8888",1);
        fdump("----8888888商品id8888888----end","i8888",1);

        fdump("----999999商家id899999----start","i8888",1);
        fdump($stores,"i8888",1);
        fdump("----999999商家id899999----end","i8888",1);
        foreach ($goodids as $good_id) {
            $goods = M('Shop_goods')->field(true)->where(array('goods_id' => $good_id))->find();
            foreach ($stores as $storeid){
                // fdump("----给商品的店铺id","i8888",1);
                // fdump($storeid,"i8888",1);
                 //fdump("给商品的店铺id-----","i8888",1);
                $target_sort_id=$this->add_goods_sort($goods['sort_id'],$storeid,$goods['store_id']);
                /*插入商品*/
                if($target_sort_id){
                    //$newsort = M('Shop_goods_sort')->field(true)->where(array('source_sort_id' => $goods['sort_id'],'store_id'=>$storeid))->find();
                    if ($tmp_goods = M('Shop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $storeid,'price'=>$goods['price'],'unit'=>$goods['unit']))->find()) {
                        fdump("找到相同商品跳出","i8888",1);
                        continue;
                    }
                    else {
                        //$target_sort_id=$ret;
                        // $source_goods_id = $goods['goods_id'];
                        unset($goods['goods_id']);
                        $goods['store_id'] = $storeid;
                        $goods['sort_id'] = $target_sort_id;
                        $goods['print_id'] = 0;
                        /*fdump("----复制商品","i8888",1);*/
                       // fdump($goods,"i8888",1);
                        $target_goods_id = M('Shop_goods')->add($goods);
                        /*fdump($target_goods_id,"i8888",1);
                        fdump("复制商品-----","i8888",1);*/
                        $pro_map = $spec_map = $spec_value_map = array();
                        if ($goods['is_properties']) {
                            $properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $good_id))->select();
                            foreach ($properties as $pro_data) {
                                $source_pro_id = $pro_data['id'];
                                unset($pro_data['id']);
                                $pro_data['goods_id'] = $target_goods_id;
                                //fdump("----复制属性","i8888",1);
                               // fdump($pro_data,"i8888",1);
                                //fdump("复制属性-----","i8888",1);
                                $pro_map[$source_pro_id] = M('Shop_goods_properties')->add($pro_data);
                                //fdump($pro_map[$source_pro_id],"i8888",1);
                                $lang_model = D('Lang');
                                $pro_data['id'] = $pro_map[$source_pro_id];
                                $lang_model->add_lang_data('Shop_goods_properties', $pro_data);
                            }
                        }
                        if ($goods['spec_value']) {
                            $spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $good_id))->select();
                            foreach ($spec_list as $spec) {
                                $source_spec_id = $spec['id'];
                                unset($spec['id']);

                                $spec['store_id'] = $storeid;
                                $spec['goods_id'] = $target_goods_id;
                                //fdump("----复制规格","i8888",1);
                                //fdump($spec,"i8888",1);
                               // fdump("复制规格-----","i8888",1);
                                if ($new_spec_id = M('Shop_goods_spec')->add($spec)) {
                                   // fdump($new_spec_id,"i8888",1);
                                    //多语言
                                    $lang_model = D('Lang');
                                    $spec['id'] = $new_spec_id;
                                    $lang_model->add_lang_data('Shop_goods_spec', $spec);
                                    $spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
                                    foreach ($spec_value_list as $spec_value) {
                                        $source_spec_value_id = $spec_value['id'];
                                        unset($spec_value['id']);
                                        $spec_value['sid'] = $new_spec_id;
                                       // fdump("----复制规格值","i8888",1);
                                       // fdump($spec_value,"i8888",1);
                                      //  fdump("复制规格值-----","i8888",1);
                                        $spec_value_map[$source_spec_value_id] = M('Shop_goods_spec_value')->add($spec_value);
                                        //fdump($spec_value_map[$source_spec_value_id],"i8888",1);
                                        //多语言
                                        $lang_model = D('Lang');
                                        $spec_value['id'] = $spec_value_map[$source_spec_value_id];
                                        $lang_model->add_lang_data('Shop_goods_spec_value', $spec_value);
                                    }
                                }
                            }

                            //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            $spec_array = explode('#', $goods['spec_value']);
                            $target_spec_value_array = array();
                            foreach ($spec_array as $str) {
                                $row_array = explode('|', $str);
                                $spec_val_ids = explode(':', $row_array[0]);
                                $new_ids = array();
                                foreach ($spec_val_ids as $tid) {
                                    $new_ids[] = $spec_value_map[$tid];
                                }
                                $row_array[0] = implode(':', $new_ids);
                                if (count($row_array) > 2 && $row_array[2] && strstr($row_array[2], '=')) {
                                    $pro_str_ids = explode(':', $row_array[2]);
                                    $new_pro_ids = array();
                                    foreach ($pro_str_ids as $pstr) {
                                        $v_k_a = explode('=', $pstr);
                                        $new_pro_ids[] = $pro_map[$v_k_a[0]] . '=' . $v_k_a[1];
                                    }
                                    $row_array[2] = implode(':', $new_pro_ids);
                                }
                                $target_spec_value_array[] = implode('|', $row_array);
                            }
                            M('Shop_goods')->where(array('goods_id' => $target_goods_id))->save(array('spec_value' => implode('#', $target_spec_value_array)));
                        }
                    }
                }
            }
            if($good_id === end($goodids)) {
                exit(json_encode(array('status'=>0,'msg'=>"克隆完成")));
            }
        }
    }

  /*  public function test(){
        (new MallAction())->add_goods_sort(4121,427,429);
        exit;
    }*/

    /*给店铺加分类*/
    public function add_goods_sort($id,$store_id,$mystoreid){
        //$id为sort_id
        //$mystoreid 复制的店铺
        //$store_id 复制到的店铺
        $three = $sencod = $first = [];
        $thisSort = M('Shop_goods_sort')->field(true)->where(array('sort_id' => $id,"store_id"=>$mystoreid))->find();
        if(empty($thisSort)){
            fdump("没找到分类".$thisSort['level'],"i8888",1);
            /*fdump(M('Shop_goods_sort')->getLastSql(),"i8888",1);*/
            return 1;
        }else{
            $newSortId = 0;
           /* fdump("原商品分类","i8888",1);
            fdump($thisSort,"i8888",1);
            fdump("原商品分类","i8888",1);

            fdump("复制分类等级".$thisSort['level'],"i8888",1);*/

            if($thisSort['level']==1){
                $targetSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $thisSort['sort_name'],'level'=>$thisSort['level'],"store_id"=>$store_id))->find();
                /*fdump("99999一级","i8888",1);
                fdump($targetSort,"i8888",1);
                fdump("一级9999","i8888",1);*/
                if(!$targetSort){
                    $thisSort['source_sort_id']=$id;
                    $thisSort['store_id'] = $store_id;
                    unset($thisSort['sort_id']);
                    $newSortId = M('Shop_goods_sort')->add($thisSort);
                }else{
                    $newSortId = $targetSort['sort_id'];
                }

            }else if($thisSort['level']==2){
                $sourceParent = M('Shop_goods_sort')->field(true)->where(array('sort_id' => $thisSort['fid'],"store_id"=>$mystoreid))->find();
                $targetParentSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $sourceParent['sort_name'],'level'=>$sourceParent['level'],"store_id"=>$store_id))->find();
                if(!$targetParentSort){
                    $sourceParent['store_id'] = $store_id;
                    $sourceParent['source_sort_id']=$sourceParent['sort_id'];
                    unset($sourceParent['sort_id']);
                    $fid = M('Shop_goods_sort')->add($sourceParent);
                }else{
                    $fid = $targetParentSort['sort_id'];
                }

                $targetSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $thisSort['sort_name'],'level'=>$thisSort['level'],"store_id"=>$store_id))->find();
                if(!$targetSort){
                    $thisSort['source_sort_id']=$id;
                    $thisSort['store_id'] = $store_id;
                    $thisSort['fid'] = $fid;
                    unset($thisSort['sort_id']);
                    $newSortId = M('Shop_goods_sort')->add($thisSort);
                }else{
                    $newSortId = $targetSort['sort_id'];
                }
                /*fdump("99999二级","i8888",1);
                fdump($targetSort,"i8888",1);
                fdump("二级9999","i8888",1);*/
            }else{
                $sourceParent = M('Shop_goods_sort')->field(true)->where(array('sort_id' => $thisSort['fid'],"store_id"=>$mystoreid))->find();//二级

                $topSort = M('Shop_goods_sort')->field(true)->where(array('sort_id' => $sourceParent['fid'],"store_id"=>$mystoreid))->find();//一级
                $targetTopSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $topSort['sort_name'],'level'=>$topSort['level'],"store_id"=>$store_id))->find();
                if(!$targetTopSort){
                    $topSort['store_id'] = $store_id;
                    $topSort['source_sort_id'] = $topSort['sort_id'];
                    unset($topSort['sort_id']);
                    $fid = M('Shop_goods_sort')->add($topSort);
                }else{
                    $fid = $targetTopSort['sort_id'];
                }

                $targetParentSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $sourceParent['sort_name'],'level'=>$sourceParent['level'],"store_id"=>$store_id))->find();
                if(!$targetParentSort){
                    $sourceParent['store_id'] = $store_id;
                    $sourceParent['fid'] = $fid;
                    $sourceParent['source_sort_id'] = $sourceParent['sort_id'];
                    unset($sourceParent['sort_id']);
                    $fid1 = M('Shop_goods_sort')->add($sourceParent);
                }else{
                    $fid1 = $targetParentSort['sort_id'];
                }


                $targetSort = M('Shop_goods_sort')->field(true)->where(array('sort_name' => $thisSort['sort_name'],'level'=>$thisSort['level'],"store_id"=>$store_id))->find();
                if(!$targetSort){
                    $thisSort['source_sort_id']=$id;
                    $thisSort['store_id'] = $store_id;
                    $thisSort['fid'] = $fid1;
                    unset($thisSort['sort_id']);
                    $newSortId = M('Shop_goods_sort')->add($thisSort);
                }else{
                    $newSortId = $targetSort['sort_id'];
                }
                /*fdump("99999三级","i8888",1);
                fdump($targetSort,"i8888",1);
                fdump("三级9999","i8888",1);*/
            }
           /* fdump("新增复制分类id".$newSortId,"i8888",1);
            fdump("复制分类-----","i8888",1);*/
            return $newSortId;
        }
    }

    //商城商品列表
	public function mall_goods_list(){
		$where=array();
		/*当没选择商品分类的时候，编辑保存下cat_id等于0下会被过滤掉,但是这是唯一的判断商品是否是商城商品的属性*/
		$where['gd.cat_fid']=array('gt',0);
		//条件筛选
		$keyword=I('keyword','','trim');
		$searchtype=I('searchtype','','trim');
		if($keyword && $searchtype){
			if($searchtype=='goods_id'){
				$where['gd.goods_id']=$keyword;
			}elseif($searchtype=='name'){
				$where['gd.name']=array('like','%'.$keyword.'%');
			}else{
                $where['gd.number']=array('like','%'.$keyword.'%');
            }
		}
		//商家筛选
		$mer_list=I('mer_list','-1','trim');
		$store_list=I('store_list','-1','trim');
		if($store_list!=-1){
			$where['gd.store_id']=array('in',$store_list);
		}elseif($mer_list!=-1){
			$store_arr=M('merchant_store')->where(array('mer_id'=>array('in',$mer_list)))->getField('store_id',true);
			$where['gd.store_id']=array('in',$store_arr);
		}
		//商品分类筛选
		$cat_fid=I('cat_fid','','intval');
		$cat_id=I('cat_id','','intval');
		if($cat_fid>0){
			$where['gd.cat_fid']=$cat_fid;
			if($cat_id>0){
				$where['gd.cat_id']=$cat_id;
			}
		}
		/*商城开启*/
		$where['sh.store_theme']=1;
		
		/*存在快店*/
		//$where['st.have_shop']=1;
		$where['st.status']=1;

		/*商城优选*/
		$is_preference =I('is_preference','-1','trim');
		if($is_preference<>-1){
			$where['gd.is_preference']=$is_preference;
		}

		$page_size = 15;
		import('@.ORG.system_page');
		$model=M('shop_goods');
		$count=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->count(1);
		$page=new page($count,$page_size);
		$list=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->limit($page->firstRow,$page->listRows)->order('index_sort desc,goods_id desc')->field('gd.*')->select();
		//商家列表
		$mer_arr=M('Merchant')->order('mer_id desc')->getField('mer_id,name',true);
		//店铺列表
		$store_arr=M('merchant_store')->order('store_id desc')->getField('store_id,mer_id,name',true);
		//拼团活动商品
		$where=array();
		$where['is_del']=0;
		$group_arr=M('mall_group_act')->where($where)->getField('good_id',true);
		$cut_arr=M('mall_bargain_act')->where($where)->getField('goods_id',true);
		foreach ($list as $k => $v) {
			$v['goods_type']=0;
			if($v['seckill_price']>0){
				$v['goods_type']=1;	//限时购商品
			}elseif(!empty($group_arr) && in_array($v['goods_id'],$group_arr)){
				$v['goods_type']=2;	//拼团活动商品
			}elseif(!empty($cut_arr) && in_array($v['goods_id'],$cut_arr)){
				$v['goods_type']=3;	//砍价活动商品
			}
			$arr=$store_arr[$v['store_id']];
			$v['mer_name']=$mer_arr[$arr['mer_id']];
			$v['store_name']=$arr['name'];
			$list[$k]=$v;
		}
		//获取商城商品分类
		$model=M('Goods_category');
		$category_list=$model->field('id,fid,name')->order('sort desc')->select();
		$this->assign('category_list',$category_list);

		$this->assign('list',$list);
		$this->assign('cat_fid',$cat_fid);
		$this->assign('cat_id',$cat_id);
		$this->assign('mer_arr',$mer_arr);
		$this->assign('store_arr',$store_arr);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//商城的店铺列表页配置 JJC
	public function store_list_page(){
		$where = array();
		//echo $this->config['store_list_page_type'];die;
		$where['type'] = $_GET['type']?$_GET['type']:$this->config['store_list_page_type'];
		$slider_list = M('merchant_store_list_page')->where($where)->order('sort DESC')->select();
		//dump($where);exit;
		$this->assign('slider_list',$slider_list);
        $this->display();
	}

	public function add_store_list_type_img(){
		$model = M('merchant_store_list_page');
		if(IS_POST){
			$where['type'] = $_POST['type'];
			$count = $model->where($where)->count();
			if($_FILES['pic']['error'] != 4){
				$image = D('Image')->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {
					$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
				} else {
					$this->frame_submit_tips(0, $image['message']);
				}
			}
			$data['name'] = $_POST['name'];
			$data['sub_name'] = $_POST['sub_name'];
			$data['url'] = $_POST['url'];
			$data['last_time'] = $_SERVER['REQUEST_TIME'];
			$_FILES['pic']['error'] != 4 && $data['pic']    = $_POST['pic'];
			$data['sort']    = $_POST['sort'];
			$data['type']    = $_POST['type'];

			if($_POST['id']){
				$result = $model->where(array('id'=>$_POST['id']))->save($data);
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."店铺列表页：".$_POST['name'],$this->config['mall_alias_name'],$_POST['id']);
			}else{
				if($count==abs($_POST['type'])){
					$this->error_tips(abs($_POST['type']).'图模式只能添加'.abs($_POST['type']).'个');
				}
				$result = $model->add($data);
				D("System_log")->add_log("增加了".$this->config['mall_alias_name']."店铺列表页：".$_POST['name'],$this->config['mall_alias_name'],$res);
			}

			if($result){
				$this->frame_submit_tips(1,'保存成功！');
			}else{
				$this->frame_submit_tips(0,'保存失败！请重试~');
			}
		}else{
			if($_GET['id']){
				$where['id'] =  $_GET['id'];
				$slider = $model->where($where)->find();
				$this->assign('slider',$slider);
			}
			$this->display();
		}
	}

	//商城首页配置==>guojie
    public function main_page(){
		$where['type'] = $_GET['type']?$_GET['type']:$this->config['mall_main_page_center_type'];
		$slider_list = M('Mall_main_page_pic_slider')->where($where)->order('sort DESC')->select();

		$this->assign('slider_list',$slider_list);
        $this->display();
	}

	//添加商城首页中部导航图文 == guojie
	public function add_group_center_type_img(){

		if(IS_POST){
			$where['type'] = $_POST['type'];
			$count = M('Mall_main_page_pic_slider')->where($where)->count();
			if($_FILES['pic']['error'] != 4){
				$image = D('Image')->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {
					$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
				} else {
					$this->frame_submit_tips(0, $image['message']);
				}
			}
			$data['name'] = $_POST['name'];
			$data['sub_name'] = $_POST['sub_name'];
			$data['url'] = $_POST['url'];
			$data['last_time'] = $_SERVER['REQUEST_TIME'];
			$_FILES['pic']['error'] != 4 && $data['pic']    = $_POST['pic'];
			$data['sort']    = $_POST['sort'];
			$data['type']    = $_POST['type'];
			
			if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
				$data['province_id']	=	0;
				$data['city_id']	=	0;
			}else{
				$data['province_id'] = intval($_POST['province_idss']);
				$data['city_id'] = intval($_POST['city_idss']);
			}
			
			if($_POST['id']){
				$result = M('Mall_main_page_pic_slider')->where(array('id'=>$_POST['id']))->save($data);
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."商城首页中部导航图文：".$_POST['name'],$this->config['mall_alias_name'],$_POST['id']);
			}else{
				if($count==abs($_POST['type'])){
					$this->error_tips(abs($_POST['type']).'图模式只能添加'.abs($_POST['type']).'个');
				}
				$result = M('Mall_main_page_pic_slider')->add($data);
				D("System_log")->add_log("新增了".$this->config['mall_alias_name']."商城首页中部导航图文：".$_POST['name'],$this->config['mall_alias_name'],$result);
			}

			if($result){
				$this->frame_submit_tips(1,'保存成功！');
			}else{
				$this->frame_submit_tips(0,'保存失败！请重试~');
			}
		}else{
			if($_GET['id']){
				$where['id'] =  $_GET['id'];
				$slider = M('Mall_main_page_pic_slider')->where($where)->find();
				$this->assign('slider',$slider);
			}
			$this->assign('user_level', $this->system_session['level']);
            $this->assign('user_area_type', $this->system_session['area_type']);
			$this->display();
		}
	}
	
	public function del_mall_center_type_img(){
        $result = M('Mall_main_page_pic_slider')->where(array('id' => $_GET['id']))->delete();
        if ($result) {
			D("System_log")->add_log("删除了".$this->config['mall_alias_name']."商城首页中部导航图文",$this->config['mall_alias_name'],$_GET['id']);
            $this->frame_submit_tips(1, '删除成功！');
        } else {
            $this->frame_submit_tips(0, '删除失败！请重试~');
        }
    }
	//商城限时购商品列表

	public function mall_seckill_list(){
		$where=array();
		$where['gd.cat_fid']=array('gt',0);
		//条件筛选
		$keyword=I('keyword','','trim');
		$searchtype=I('searchtype','','trim');
		if($keyword && $searchtype){
			if($searchtype=='goods_id'){
				$where['gd.goods_id']=$keyword;
			}elseif($searchtype=='name'){
				$where['gd.name']=array('like','%'.$keyword.'%');
			}
		}
		/*商城开启*/
	/*	$where['sh.store_theme']=1;*/
		/*存在快店*/
/*		$where['st.have_shop']=1;*/
        $where['gd.status']=1;
		$where['st.status']=1;
        $where['tlg.end_date']=array('egt',date('Y-m-d'));
        $where['tlg.stock']=array('neq',0);
        $where['tlg.is_del']=array('eq',0);
		/*商城优选*/
		$is_preference =I('is_preference','-1','trim');
		if($is_preference<>-1){
			$where['gd.is_preference']=$is_preference;
		}

		$page_size = 15;
		import('@.ORG.system_page');
		$model=M('shop_goods');
		/*$where['seckill_price']=array('gt',0);*/
		$count=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
            ->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat(1,"_",gd.goods_id)=concat(pg.type,"_",pg.act_id)')
            ->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
            ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
            ->where($where)->count();
		$list=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
            ->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat(1,"_",gd.goods_id)=concat(pg.type,"_",pg.act_id)')
            ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
            ->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
            ->where($where)->field('gd.*,pg.id promote_id,pg.sort promote_sort,tlg.start_date,tlg.end_date,tlg.limit_price,tlg.is_spec,tlg.id as limit_id')->limit($page->firstRow,$page->listRows)
            ->group('gd.goods_id')->order('promote_sort desc,pg.sort desc,gd.goods_id desc')->select();
        $count=count($list);
        $page=new page($count,$page_size);
	//	fdump(date('Y-m-d'),"j777777777777777",1);
		//商家列表
		$mer_arr=M('Merchant')->getField('mer_id,name',true);
		//店铺列表
		$store_arr=M('merchant_store')->getField('store_id,mer_id,name',true);
		foreach ($list as $k => $v) {
			$arr=$store_arr[$v['store_id']];
			$v['mer_name']=$mer_arr[$arr['mer_id']];
			$v['store_name']=$arr['name'];
            $list[$k]=$v;

			if($list[$k]['is_spec']==1){
                $mytest = D('Time_limited_discount_goods_spec')->where(['limit_id' => $list[$k]['limit_id']])->min('limit_price');
                $list[$k]['limit_price']=$mytest;
            }

		}
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}
	
	//商城拼团活动列表
	public function mall_group_list(){
		$where=array();
		$where['gd.cat_fid']=array('gt',0);
		$where['act.is_del']=0;
		//条件筛选
		$keyword=I('keyword','','trim');
		$searchtype=I('searchtype','','trim');
		if($keyword && $searchtype){
			if($searchtype=='goods_id'){
				$where['gd.goods_id']=$keyword;
			}elseif($searchtype=='name'){
				$where['gd.name']=array('like','%'.$keyword.'%');
			}
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['act.stime']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['act.stime']=array('egt',$stime1);
		}elseif($stime2){			
			$where['act.stime']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['act.ftime']=array('between',array($ftime1,$ftime2));
		}elseif($ftime1){
			$where['act.ftime']=array('egt',$ftime1);
		}elseif($ftime2){			
			$where['act.ftime']=array('elt',$ftime2);
		}
		/*商城开启*/
		$where['sh.store_theme']=1;
		$where['st.status']=1;
		/*存在快店*/
		$where['st.have_shop']=1;

		/*商城优选*/
		$is_preference =I('is_preference','-1','trim');
		if($is_preference<>-1){
			$where['gd.is_preference']=$is_preference;
		}

		$page_size = 15;
		import('@.ORG.system_page');
		$model=M('shop_goods');
		$count=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'mall_group_act act on act.good_id=gd.goods_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->count(1);
		$page=new page($count,$page_size);
		$list=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'mall_group_act act on act.good_id=gd.goods_id')->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat(2,"_",act.id)=concat(pg.type,"_",pg.act_id)')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->field('act.*,gd.name,gd.price,gd.goods_id,gd.is_preference,pg.id promote_id,pg.sort promote_sort')->order('promote_sort desc,pg.sort desc,gd.goods_id desc')->limit($page->firstRow,$page->listRows)->select();
		//商家列表
		$mer_arr=M('Merchant')->getField('mer_id,name',true);
		//店铺列表
		$store_arr=M('merchant_store')->getField('store_id,name',true);
		foreach ($list as $k => $v) {
			$v['mer_name']=$mer_arr[$v['mer_id']];
			$v['store_name']=$store_arr[$v['store_id']];
			$v['affect_time']=$this->reg_date($v['affect_time']);
			$v['state']=$this->reg_status($v['stime'],$v['ftime'],$v['status']);
			$list[$k]=$v;
		}
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//商城砍价活动列表
	public function mall_cut_list(){
		//D("Mall_bargain_act")->get_cut_goods_list();
		$where=array();
		$where['gd.cat_fid']=array('gt',0);
		$where['act.is_del']=0;
		//条件筛选
		$keyword=I('keyword','','trim');
		$searchtype=I('searchtype','','trim');
		if($keyword && $searchtype){
			if($searchtype=='goods_id'){
				$where['gd.goods_id']=$keyword;
			}elseif($searchtype=='name'){
				$where['gd.name']=array('like','%'.$keyword.'%');
			}
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['act.stime']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['act.stime']=array('egt',$stime1);
		}elseif($stime2){			
			$where['act.stime']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['act.ftime']=array('between',array($ftime1,$ftime2));
		}elseif($ftime1){
			$where['act.ftime']=array('egt',$ftime1);
		}elseif($ftime2){			
			$where['act.ftime']=array('elt',$ftime2);
		}
		/*商城开启*/
		$where['sh.store_theme']=1;
		$where['st.status']=1;
		/*存在快店*/
		$where['st.have_shop']=1;

		/*商城优选*/
		$is_preference =I('is_preference','-1','trim');
		if($is_preference<>-1){
			$where['gd.is_preference']=$is_preference;
		}

		$page_size = 15;
		import('@.ORG.system_page');
		$model=M('shop_goods');
		$count=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'mall_bargain_act act on act.goods_id=gd.goods_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->count(1);
		$page=new page($count,$page_size);
		$list=$model->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'mall_bargain_act act on act.goods_id=gd.goods_id')->join('left join '.C('DB_PREFIX').'mall_promote_goods pg on concat(3,"_",act.id)=concat(pg.type,"_",pg.act_id)')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->field('act.*,gd.name,gd.price,gd.is_preference,pg.id promote_id,pg.sort promote_sort')->order('promote_sort desc,pg.sort desc')->limit($page->firstRow,$page->listRows)->select();
		//商家列表
		$mer_arr=M('Merchant')->getField('mer_id,name',true);
		//店铺列表
		$store_arr=M('merchant_store')->getField('store_id,name',true);
		foreach ($list as $k => $v) {
			$v['mer_name']=$mer_arr[$v['mer_id']];
			$v['store_name']=$store_arr[$v['store_id']];
			$v['tprice']=D("Mall_bargain_team")->get_good_price($v['goods_id'],$v['spec_id']);
			$v['affect_time']=$this->reg_date($v['affect_time']);
			$v['state']=$this->reg_status($v['stime'],$v['ftime'],$v['status']);
			$list[$k]=$v;
		}
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//商品设置
	public function adver_goods_edit(){
		$id=I('id','','intval');
		$goods_id=I('goods_id','','intval');
		$act_id=I('act_id','','intval');
		$type=I('type',1,'intval');
		$model=M('mall_promote_goods');
		if(IS_POST){
			$is_promote=I('is_promote','','intval');
			$promote_end_time=I('promote_end_time','','strtotime');
			$sort=I('sort',0,'intval');
			$data=array();
			$data['goods_id']=$goods_id;
			$data['act_id']=$act_id;
			$data['type']=$type;
			$data['is_promote']=$is_promote;
			$data['promote_end_time']=$promote_end_time;
			$data['sort']=$sort;
			if($id>0){
				$data['id']=$id;
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."商品推荐设置",$this->config['mall_alias_name'],$id);
				$res=$model->data($data)->save();
			}else{
				$data['addtime']=time();
				
				$res=$model->data($data)->add();
				D("System_log")->add_log("新增了".$this->config['mall_alias_name']."商品推荐设置",$this->config['mall_alias_name'],$res);
			}
			if($res!==false){
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败，请重试！');
			}
		}else{
			if(!empty($id)){
				$info=$model->where(array('id'=>$id))->find();
				$this->assign('info',$info);
			}
			$this->assign('id',$id);
			$this->assign('goods_id',$goods_id);
			$this->assign('act_id',$act_id);
			$this->assign('type',$type);
			$this->display();	
		}
	}

	//商城排序设置
	public function index_sort_set(){
		$goods_id=I('goods_id','','intval');
		$model=M('Shop_goods');
		fdump($goods_id,2);
		if(IS_POST){
			$index_sort=I('index_sort',0,'intval');
			$data=array();
			$data['goods_id']=$goods_id;
			$data['index_sort']=$index_sort;
			$res=$model->data($data)->save();
			fdump(M()->_sql(),2);
			if($res!==false){
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."商品排序设置",$this->config['mall_alias_name'],$goods_id);
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败，请重试！');
			}
		}else{
			$index_sort=$model->where(array('goods_id'=>$goods_id))->getField('index_sort');
			$this->assign('index_sort',$index_sort);
			$this->assign('goods_id',$goods_id);
			$this->display();
		}
	}

    //商城实名认证
    public function check_id_card(){
        $goods_id = I('goods_id', '', 'intval');
        $model = M('Shop_goods');

        if (IS_POST) {
            $is_open = I('is_open', 0, 'intval');
            $img_check = I('img_check', 0, 'intval');
            $data = array();
            $data['goods_id'] = $goods_id;
            $data['is_open_check'] = $is_open;
            $data['img_check'] = $img_check;
            $res = $model->data($data)->save();

            if ($res !== false) {
                D("System_log")->add_log("编辑了" . $this->config['mall_alias_name'] . "实名认证", $this->config['mall_alias_name'], $goods_id);
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败，请重试！');
            }
        } else {
            $check_config = $model->field('is_open_check, img_check')->where(array('goods_id' => $goods_id))->find();
            $this->assign('check_config', $check_config);
            $this->assign('goods_id', $goods_id);
            $this->display();
        }
    }

	//格式化活动时长
	private function reg_date($time=null){
		$day=floor($time/(24*3600));
		$hour=floor($time%(24*3600)/3600);
		$minute=floor($time%3600/60);
		return $affect_time=$day.'天'.$hour.'时'.$minute.'分';
	}

	//格式化活动状态
	private function reg_status($stime,$ftime,$status){
		$time=time();
		if($status==0){
			$state='<span style="color:#f00;">已失效</span>';
		}elseif($time<$stime){
			$state='<span style="color:#666;">未开始</span>';
		}elseif($time>$ftime){
			$state='<span style="color:#666;">已结束</span>';
		}else{
			$state='<span style="color:#008000;">进行中</span>';
		}
		return $state;

	}

	//商城配置
	public function mall_config(){
		$this->redirect('Config/index',array('galias'=>'mall','header'=>'Mall/mall_header'));
	}

	//商品编辑
	public function goods_edit(){
		$this->assign('static_path','./tpl/Merchant/default/static/');
		$now_goods = $this->check_goods($_GET['goods_id']);
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
//		var_dump($now_store);
//		var_dump(C('config.score_get_percent'));exit();
		if (IS_POST) {
            //fdump($_POST['video_url'],"ddddd9999999999999");
            if(!empty($_POST['video_url'])){
                $file_handle = new file_handle();

                $file_handle->upload($_POST['video_url']);
            }
			if (empty($_POST['name'])) {
				$error_tips .= '商品名称必填！'.'<br/>';
			}
			if (empty($_POST['unit'])) {
				$error_tips .= '商品单位必填！'.'<br/>';
			}
			if ($_POST['price'] === '' && !$this->config['open_extra_price']) {
				$error_tips .= '商品价格可以设置为0，但是必填！'.'<br/>';
			}
			if ($_POST['price'] < 0 && !$this->config['open_extra_price']) {
				$error_tips .= '商品价格必须大于或等于0！'.'<br/>';
			}
			if (empty($_POST['pic'])) {
				$error_tips .= '请至少上传一张照片！'.'<br/>';
			}

			$_POST['des'] = fulltext_filter($_POST['des']);

			$img_mer_id = sprintf("%09d", $now_store['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
			    if(false === strpos($vp,',')){
                    $_POST['pic'][$kp] = $vp;
                }else{
                    $tmp_vp = explode(',', $vp);
                    if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
                    $_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
                }

			}
			$_POST['pic'] = implode(';', $_POST['pic']);
			$_POST['print_id'] = isset($_POST['print_id']) ? intval($_POST['print_id']) : 0;

			if ($_POST['specs']) {
				foreach ($_POST['specs'] as $val) {
					if (empty($val)) {
						$error_tips .= '请给规格取名，若不需要的请删除后重新生成'.'<br/>';
					}
				}
			}

			if ($_POST['spec_val']) {
				foreach ($_POST['spec_val'] as &$rowset) {
					foreach ($rowset as $k=>$val) {
						if (empty($val)) {
							$error_tips .= '请给规格的属性值取名，若不需要的请删除后重新生成'.'<br/>';
						}
                        $rowset[$k] = trim($val);
					}
				}
			}

			if ($_POST['properties']) {
				foreach ($_POST['properties'] as $val) {
					if (empty($val)) {
						$error_tips .= '请给属性取名，若不需要的请删除后重新生成'.'<br/>';
					}
				}
			}

			if ($_POST['properties_val']) {
				foreach ($_POST['properties_val'] as &$rowset) {
					foreach ($rowset as &$val) {
						if (empty($val)) {
							$error_tips .= '请给属性的属性值取名，若不需要的请删除后重新生成'.'<br/>';
						}
                        $val = trim($val);
					}
				}
			}

//			if ($_POST['score_percent']) {
//				if(strpos($_POST['score_percent'],'%')){
//					$tmp_percent = str_replace('%','',$_POST['score_percent']);
//					if(floatval($tmp_percent)>100 ||floatval($tmp_percent)<0 ){
//						$error_tips .= '消费1元获得积分数百分比应在0-100%之间'.'<br/>';
//					}
//				}
//			}

            if($_POST['score_percent_type'] == 0) { //跳过
                $mer_score = M('merchant')->field('score_get_percent')->where(['mer_id'=>$now_store['mer_id']])->find();
//                $_POST['score_percent'] = $mer_score['score_get_percent'];
                $_POST['score_percent'] = -1;
            } else {
                if ($_POST['score_percent']) {
                    if(strpos($_POST['score_percent'],'%')){
                        $tmp_percent = str_replace('%','',$_POST['score_percent']);
                        if(floatval($tmp_percent)>100 ||floatval($tmp_percent)<=0 ){
                            $error_tips .= '消费1元获得积分数百分比应在0-100%之间'.'<br/>';
                        }
                    }
                }
            }

			if ($_POST['score_max']<0) {

				$error_tips .= '积分最大使用数应是大于等于0的整数'.'<br/>';

			}
			$score_max_deducte = bcdiv($_POST['score_max'], $this->config['user_score_use_percent'], 2);
			if ($score_max_deducte>$_POST['price']) {
				$error_tips .= '积分最大使用数抵扣金额超过了商品价格'.'<br/>';
			}

			$sort_id = $now_sort['sort_id'];
			for ($i = 1; $i <= self::GOODS_SORT_LEVEL; $i++) {
			    if (isset($_POST['sort_id_' . $i]) && intval($_POST['sort_id_' . $i])) {
			        $sort_id = intval($_POST['sort_id_' . $i]);
			        unset($_POST['sort_id_' . $i]);
			    }
			}
			$shopGoodsSortDB = M('Shop_goods_sort');
			if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			    if ($fsort = M(_view('Shop_goods_sort'))->field(true)->where(array('fid' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			        $error_tips .= '该分类有子分类，不能直接添加商品'.'<br/>';
			    } elseif ($sort['operation_type'] != 0) {
			        $shopGoodsSortDB->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->save(array('operation_type' => 0));
			    }
			} else {
			    $error_tips .= '商品分类不存在'.'<br/>';
			}

			if ($_POST['seckill_type']) {
			    $_POST['seckill_open_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_open_time'] . ":00");
			    $_POST['seckill_close_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_close_time'] . ":00");
			} else {
			    $_POST['seckill_open_time'] = strtotime($_POST['seckill_open_datetime'] . ":00");
			    $_POST['seckill_close_time'] = strtotime($_POST['seckill_close_datetime'] . ":00");
			}

			//判断是否设置了商品只属于商城
			if($_POST['cat_fid']==0 && $_POST['goods_type']==1){
				$_POST['goods_type']=0;
			}
			if (empty($error_tips)) {
				$_POST['goods_id'] = $now_goods['goods_id'];
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];

				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');
					D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."商品".$_POST['name'],$this->config['mall_alias_name'],$goods_id);
					$this->success('保存成功！', U('Mall/mall_goods_list', array('sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
					die;
					$ok_tips = '保存成功！';
				} else {
					$this->error('保存失败！请重试！', U('Mall/mall_goods_list', array('sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
					die;
					$error_tips = '保存失败！请重试。';
				}
			} else {
				$return = $this->format_data($_POST);
				$_POST['json'] = isset($return['json']) ? json_encode($return['json']) : '';
				$_POST['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
				$_POST['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
				$_POST['list'] = isset($return['list']) ? $return['list'] : '';
				$this->assign('now_goods', $_POST);
			}

			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}

		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as &$l) {
			if ($l['is_main']) {
				$l['name'] .= '(主打印机)';
			} else {
				$l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		$this->assign('print_list', $print_list);
		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list));

		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false);
		$this->assign('sort_list', json_encode($sort_list));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
		$this->assign('now_goods', $now_goods);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $now_store['mer_id']))->select());
		$this->display();
	}

	public function ajax_goods_properties()
	{
		$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
		$goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
		$properties = D('Goods_properties')->field(true)->where(array('status' => 1, 'cat_id' => $cat_id))->select();
		if ($properties) {
			$value_ids = array();
			$relations = D('Goods_properties_relation')->field(true)->where(array('gid' => $goods_id))->select();
			foreach ($relations as $r) {
				$value_ids[] = $r['pid'];
			}
			$pids = array();
			$list = array();
			foreach ($properties as $row) {
				$pids[] = $row['id'];
				$row['value_list'] = null;
				$list[$row['id']] = $row;
			}
			$value_list = D('Goods_properties_value')->field(true)->where(array('pid' => array('in', $pids)))->select();
			foreach ($value_list as $v) {
				if (isset($list[$v['pid']])) {
					if (in_array($v['id'], $value_ids)) {
						$v['checked'] = 1;
					} else {
						$v['checked'] = 0;
					}
					$list[$v['pid']]['value_list'][] = $v;
				}
			}
			$data = array();
			foreach ($list as $row) {
				if (isset($row['value_list']) && $row['value_list']) {
					$data[] = $row;
				}
			}
			exit(json_encode(array('error_code' => false, 'data' => $data)));
		} else {
			exit(json_encode(array('error_code' => true, 'msg' => '没有数据')));
		}
	}

	/* 检测商品存在 */
	protected function check_goods($goods_id)
	{
		$database_shop_goods = D('Shop_goods');
		$condition_goods['goods_id'] = $goods_id;
		$now_goods = $database_shop_goods->field(true)->where($condition_goods)->find();
        //fdump($now_goods,"dengyuanhui888",1);
		if(empty($now_goods)){
			$this->error('商品不存在！');
		}
		//设置标签处理
        if(!empty($now_goods['label_str'])){
            $label = explode(',',$now_goods['label_str']);
            $now_goods['label_arr'] = $label;
        }
		$act_info=D('Shop_goods')->get_good_act_info($goods_id);
		if($act_info){
			if($act_info['is_group']){
				$act_name='拼团';
			}else{
				$act_name='砍价';
			}
			if($is_del){
				$this->error('抱歉，该商品已设置为'.$act_name.'活动商品，不能删除！','',5);
			}else{
				$this->error('抱歉！该商品已经被设置为'.$act_name.'活动，暂不可修改与删除。请先到商城管理--商城店铺列表--'.$act_name.'管理中解禁该商品。','',5);
			}			
			exit;
		}
		if(!empty($now_goods['image'])){
			$goods_image_class = new goods_image();
			$tmp_pic_arr = explode(';', $now_goods['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$now_goods['pic_arr'][$key]['title'] = $value;
					$now_goods['pic_arr'][$key]['url'] = $value;
				}else{
                    $now_goods['pic_arr'][$key]['title'] = $value;
                    $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }

			}
		}

		$return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';

		/*处理图片上传阿里云导致详情中的图片不展示*/
		$src = '<img src="'.file_domain().'/';
		$now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
		$now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
		$now_goods['des']=replace_file_domain($now_goods['des']);
		/*标签显示*/
		if(!empty($now_goods['label_str'])){
          if(strpos($now_goods['label_str'],",")){
              $now_goods['label_arr']=explode(",",$now_goods['label_str']);
          }else{
              $now_goods['label_arr'][0]=$now_goods['label_str'];
          }
        }
		return $now_goods;
	}

	/* 检测分类存在 */
	protected function check_sort($sort_id)
	{
		$database_goods_sort = D('Shop_goods_sort');
		$condition_goods_sort['sort_id'] = $sort_id;
		$now_sort = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->find();
		if (empty($now_sort)) {
			$this->error('分类不存在！');
		}
		if(!empty($now_sort['image'])){
			$sort_image_class = new goods_sort_image();
			$now_sort['see_image'] = $sort_image_class->get_image_by_path($now_sort['image'],$this->config['site_url'],'s');
		}
		if ($now_sort['week'] != null) {
			$now_sort['week'] = explode(',', $now_sort['week']);
		}
		return $now_sort;
	}

	/* 检测店铺存在，并检测是不是归属于商家 */
	protected function check_store($store_id)
	{
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = $store_id;
		//$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
		if (empty($now_store)) {
			$this->error('店铺不存在！');
		} else {
			//return $now_store;
			if ($now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find()) {
				if (!empty($now_shop['background'])) {
					$image_tmp = explode(',', $now_shop['background']);
					$now_shop['background_image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
				}
				return array_merge($now_store, $now_shop);
			}
			return $now_store;
			$now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find();
			return array_merge($now_store, $now_shop);
		}
	}

	public function format_data($data)
	{
		$spec_list = array();
		foreach ($data['spec_id'] as $i => $id) {
			$id = intval($id);
			$t_i = $id ? $id : 'i_' . $i;
			$spec_list[$t_i] = array('id' => $id, 'name' => $data['specs'][$i]);

			foreach ($data['spec_val_id'][$i] as $ii => $vid) {
				$vid = intval($vid);
				$v_i = $vid ? $vid : 'v_' . $ii;
				$spec_list[$t_i]['list'][$v_i] = array('id' => $vid,  'name' => $data['spec_val'][$i][$ii]);
			}
		}

		$properties_list = array();
		foreach ($data['properties_id'] as $pi => $pid) {
			$pid = intval($pid);
			$p_i = $pid ? $pid : 'p_' . $pi;
			$properties_list[$p_i] = array('id' => $pid, 'name' => $data['properties'][$pi], 'val' => $data['properties_val'][$pi]);
		}


		$for_data = array();
		foreach ($data['spec_val_id'] as $di => $dr) {
			foreach ($dr as $d => $id_t) {
				$for_data[$di][$d] = $di . '_' . $d;
			}
		}

		$formart_data = array();
		$this->format_spec($for_data, 0, '', $formart_data);

// 		echo "<Pre/>";
// 		print_r($formart_data);
// 		die;

		$list = array();
		foreach ($formart_data as $fi => $string) {
			$array = explode('_', $string);
			$array = array_chunk($array, 2);
// 		foreach ($data['spec_val_id'] as $k => $rowset) {
			$index = $pre = '';
			$tdata = array();
			foreach ($array as $irow) {
				$k = $irow[0];
				$ki = $irow[1];
				$r = $data['spec_val_id'][$irow[0]][$irow[1]];
// 			foreach ($rowset as $ki => $r) {
				if ($r) {
					$index .= $pre . 'id_' . $r;
				} else {
					$index .= $pre . 'index_' . $ki;
				}
				$pre = '_';
				$tdata[] = array('spec_val_id' => $r, 'spec_val_name' => $data['spec_val'][$k][$ki]);
			}
			$list[$index]['index'] = $index;
			$list[$index]['spec'] = $tdata;
			$list[$index]['old_price'] = $data['old_prices'][$fi];
			$list[$index]['price'] = $data['prices'][$fi];
			$list[$index]['seckill_price'] = $data['seckill_prices'][$fi];
			$list[$index]['stock_num'] = $data['stock_nums'][$fi];
			$list[$index]['max_num'] = $data['max_nums'][$fi];
			$list[$index]['cost_price'] = $data['cost_prices'][$fi];
			$list[$index]['number'] = $data['numbers'][$fi];
			$pt_data = array();
			foreach ($data['properties'] as $pin => $pr) {
				$pt_data[] = array('id' => $data['properties_id'][$pin], 'num' => $data['num' . $fi][$pin], 'name' => $pr);
				$ptdata['num' . $pin . '[]'] = $data['num' . $fi][$pin];
			}
			$list[$index]['properties'] = $pt_data;

			$json[$index] = $ptdata;
			$json[$index]['old_prices[]'] = $data['old_prices'][$fi];
			$json[$index]['prices[]'] = $data['prices'][$fi];
			$json[$index]['seckill_prices[]'] = $data['seckill_prices'][$fi];
			$json[$index]['stock_nums[]'] = $data['stock_nums'][$fi];
			$json[$index]['max_nums[]'] = $data['max_nums'][$fi];
			$json[$index]['cost_prices[]'] = $data['cost_prices'][$fi];
			$json[$index]['numbers[]'] = $data['numbers'][$fi];
		}
// 		echo "<Pre/>";
// 		print_r(array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json));
// 		die;
		return array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json);

	}

	public function ajax_upload_pic()
	{
		if ($_FILES['file']['error'] != 4) {
			$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
			$shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $store_id))->find();
			$mer_id=M('Merchant_store')->where(array('store_id'=>$store_id))->getField('mer_id');
			$store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
			if ($store_theme) {
				$width = '900,450';
				$height = '900,450';
			} else {
				$width = '900,450';
				$height = '500,250';
			}
			$param = array('size' => $this->config['group_pic_size']);
			$param['thumb'] = true;
			$param['imageClassPath'] = 'ORG.Util.Image';
			$param['thumbPrefix'] = 'm_,s_';
			$param['thumbMaxWidth'] = $width;
			$param['thumbMaxHeight'] = $height;
			$param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($mer_id, 'goods', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['message'])));
			} else {
				$title = $image['title']['imgFile']?$image['title']['imgFile']:$image['title']['file'];
				$goods_image_class = new goods_image();
				$url = $goods_image_class->get_image_by_path($title, 'm');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}

	public function ajax_del_pic() {
// 		$group_image_class = new goods_image();
// 		$group_image_class->del_image_by_path($_POST['path']);
	}

	private function format_spec($a, $i, $str, &$return)
	{
		if ($i == 0) {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				if ($ii == count($a)) {
					$return[] = $t . $val;
				} else {
					$this->format_spec($a, $ii, $t . $val, $return);
				}
			}
		} else if ($i == count($a) - 1) {
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				$return[] = $t . $val;
			}
		} else {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				$this->format_spec($a, $ii, $t . $val, $return);
			}
		}
	}

	//视频文件上传
	public function vupload(){
        set_time_limit (0);
        //关闭缓存
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
 
        $filepath='./upload/video/'.date('Y').'/'.date('m');
 		if(!file_exists($filepath)){
			mkdir($filepath,0777,true);
		}
        $ip_path = $filepath;
        $save_path = trim($filepath,'./');
        import('@.ORG.Vupload');
        $uploader =  new Vupload();
        $uploader->set('path',$ip_path);

        //用于断点续传，验证指定分块是否已经存在，避免重复上传
        if(isset($_POST['status'])){
            if($_POST['status'] == 'chunkCheck'){
                $target =  $ip_path.'/'.$_POST['name'].'/'.$_POST['chunkIndex'];
                if(file_exists($target) && filesize($target) == $_POST['size']){
                    die('{"ifExist":1}');
                }
                die('{"ifExist":0}');
 
            }elseif($_POST['status'] == 'md5Check'){
 
                //todo 模拟持久层查询
                $dataArr = array(
                    'b0201e4d41b2eeefc7d3d355a44c6f5a' => 'kazaff2.jpg'
                );
 
                if(isset($dataArr[$_POST['md5']])){
                    die('{"ifExist":1, "path":"'.$dataArr[$_POST['md5']].'"}');
                }
                die('{"ifExist":0}');
            }elseif($_POST['status'] == 'chunksMerge'){
 
                if($path = $uploader->chunksMerge($_POST['name'], $_POST['chunks'], $_POST['ext'])){
                    //todo 把md5签名存入持久层，供未来的秒传验证
                    session('video_path', $save_path.'/'.$path);
                    die('{"status":1, "path": "'.$save_path.'/'.$path.'"}');
                }
                die('{"status":0}');
            }
        }
 
        if(($path = $uploader->upload('file',$_POST))!==false){
            if(!session('video_path')){
                session('video_path', $save_path.'/'.$path);
            }
            die('{"status":1, "path": "'.$save_path.'/'.$path.'"}');
        }
        die('{"status":0}');
    }

	//删除上传的视频文件
    public function del_video_cache(){
    	$uniqueFileName=I('uniqueFileName','','trim');
    	if(empty($uniqueFileName)){
    		$this->error('网络出错，请重试！');
    	}
    	$ip_path = './upload/video';
    	$filepath=$ip_path.'/'.$uniqueFileName;
		if(is_dir($filepath) || !is_file($filepath.'.tmp')){
			$this->deleteDir($filepath);
			unlink($filepath.'.tmp');
		}
		$this->success('删除成功');
    }

    //删除商品视频
    public function del_video(){
    	$goods_id=I('get.goods_id','','intval');
    	$video_url=I('video_url','','trim');
    	if($goods_id>0){
    		$data=array();
    		$data['goods_id']=$goods_id;
    		$data['video_url']='';
    		$res=M('shop_goods')->data($data)->save();
    	}else{
    		$res=1;
    	}
    	if($res!==false){
    		unlink($video_url);
    		$this->success('删除成功！');
    	}else{
    		$this->error('删除失败，请重试！');
    	}
    }

    //删除文件夹
    private function deleteDir($dir){
		if (!$handle = @opendir($dir)) {
			return false;
		}
		while (false !== ($file = readdir($handle))) {
			if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
				$file = $dir . '/' . $file;
				if (is_dir($file)) {
					deleteDir($file);
				} else {
					@unlink($file);
				}             
			}
		}
		@rmdir($dir);
    }

    //商品评论
    public function shop_comment_list(){
    	$goods_id=I('get.goods_id','','intval');
    	import('@.ORG.system_page');
    	$where=array();
    	$where['re.status']=array('lt',2);
    	if($goods_id>0){
    		$where['de.goods_id']=$goods_id;    		
    	}
    	$keyword=I('keyword','','trim,htmlspecialchars');
    	$searchtype=I('searchtype','','trim');
    	if($keyword && $searchtype){
    		switch ($searchtype) {
    			case 'm_name':
    				$mer_ids=M('Merchant')->where(array('name'=>array('like','%'.$keyword.'%')))->getField('mer_id',true);
    				$where['re.mer_id']=array('in',$mer_ids);
    				break;
    			case 's_name':
    				$store_ids=M('Merchant_store')->where(array('name'=>array('like','%'.$keyword.'%')))->getField('store_id',true);
    				$where['re.store_id']=array('in',$store_ids);
    				break;
    			case 'goods_name':
    				$where['de.name']=array('like',"%".$keyword."%");
    				break;
    			case 'nickname':
    				$where['us.nickname']=array('like','%'.$keyword.'%');
    				break;
    			case 'phone':
    				$where['us.phone']=array('like','%'.$keyword.'%');
    				break;
    		}
    	}
    	$count=M('reply')->alias('re')->join('inner join '.C('DB_PREFIX').'shop_order_detail de on re.order_detail_id=de.id')->join('inner join '.C('DB_PREFIX').'user us on re.uid=us.uid')->where($where)->count(1);
    	$page_size=10;
    	$page=new page($count,$page_size);
    	$list=M('reply')->alias('re')->join('inner join '.C('DB_PREFIX').'shop_order_detail de on re.order_detail_id=de.id')->join('inner join '.C('DB_PREFIX').'user us on re.uid=us.uid')->where($where)->field('re.*,de.name as goods_name,us.nickname,us.phone')->limit($page->firstRow,$page->listRows)->order('pigcms_id desc')->select();
    	//商家列表
		$mer_arr=M('Merchant')->getField('mer_id,name',true);
		//店铺列表
		$store_arr=M('merchant_store')->getField('store_id,name',true);
		foreach ($list as $k => $v) {
			$v['mer_name']=$mer_arr[$v['mer_id']];
			$v['store_name']=$store_arr[$v['store_id']];
			$list[$k]=$v;
		}
    	$this->assign('goods_id',$goods_id);
    	$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
    }
	
	// 实名认证
    public function authentication() {


        if($_GET['status'] == 2){
            $where['authentication_status'] =array('neq',1);
            $this->assign('status',2);
        }else{
            $where['authentication_status'] =1;
            $this->assign('status',1);
        }

        $count = D('Mall_authentication')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $authentication_list = D('Mall_authentication')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('authentication_list', $authentication_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function authentication_check(){
        if(IS_POST){
            $data['examine_time'] = time();
            $data['authentication_status'] = $_POST['authentication_status'];
            $info = D('Mall_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->find();
            $res = D('Mall_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->data($data)->save();
            if($res){

                if($_POST['authentication_status'] == 2){
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=authentication';
                        $model->sendTempMsg('OPENTM405462911', 
                            array(
                                'href' => $href, 
                                'wecha_id' => $user_info['openid'], 
                                'first' => '您提交的用户认证，已经通过系统审核啦。\n', 
                                'keyword1' => '认证审核通过', 
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                'keyword4' => $user_info['nickname'], 
                                'remark' => '\n请点击查看详细信息！'
                            )
                        );
                    }
                }elseif ($_POST['authentication_status'] == 3) {
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=authentication';
                        $model->sendTempMsg('OPENTM405462911', 
                            array(
                                'href' => $href, 
                                'wecha_id' => $user_info['openid'], 
                                'first' => '您提交的用户认证，系统审核失败请重新编辑提交。\n', 
                                'keyword1' => '认证审核失败', 
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                'keyword4' => $user_info['nickname'], 
                                'remark' => '\n请点击查看详细信息！'
                            )
                        );
                    }
                }

                $this->success('审核成功！');
            }else{
                $this->error('审核失败！请重试！');
            }
        }else{
            if($_GET['status'] == 1){
                $this->assign('status',$_GET['status']);
            }
            $authenticationInfo = D('Mall_authentication')->where(array('authentication_id'=>$_GET['id']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authenticationInfo',$authenticationInfo);
            $this->display();
        }

    }
    
    // 实名认证
    public function authentication_config() {
        $where = array();
        $where['is_del'] = 0;
        $authentication_config_list = D('Mall_authentication_config')->where($where)->select();
        $this->assign('authentication_config_list',$authentication_config_list);
        $this->display();
    }
    
    // 实名认证
    public function authentication_config_add() {
        if(IS_POST){
            $data = $_POST;
            $data['add_time'] = time();
            $info = D('Mall_authentication_config')->where(array('key'=>$_POST['key'],'is_del'=>0))->find();
            if($info){
                $this->error('添加失败！唯一标识已存在！');
                die;
            }
            $res = D('Mall_authentication_config')->data($data)->add();
            if($res){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试！');
            }
        }else{
            $this->display();
        }

    }
    
    // 实名认证
    public function authentication_config_edit() {
        if(IS_POST){

            $info = D('Mall_authentication_config')->where(array('acid'=>array('neq',$_POST['acid']),'key'=>trim($_POST['key']),'is_del'=>0))->find();
            if($info){
                $this->error('修改失败！唯一标识已存在！');
                die;
            }
            $data = $_POST;
            $res = D('Mall_authentication_config')->where(array('acid'=>$_POST['acid']))->data($data)->save();
            if($res){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试！');
            }
        }else{
            $info = D('Mall_authentication_config')->where(array('acid'=>$_GET['acid']))->find();
            $this->assign('info',$info);
            $this->display();
        }

    }
    
    // 实名认证
    public function authentication_config_del() {
        D('Mall_authentication_config')->where(array('acid'=>$_POST['acid']))->data(array('is_del'=>1))->save();
        $this->success('删除成功！');
    }

	//设置优选
	public function set_perfence(){
		$goods_id = $_POST['goods_id'];
		if(empty($goods_id)){
			exit(json_encode(array('errorcode'=>1,'info'=>'请选择商品！')));
		}
		$db = M('Shop_goods');
		$where = array();
		$where['goods_id'] = $goods_id;
		$goods = $db->where($where)->field('is_preference')->find();
		if(empty($goods)){
			exit(json_encode(array('errorcode'=>1,'info'=>'商品不存在')));
		}
		if($goods['is_preference']==1){//取消优选
			$data = array();
			$data['is_preference'] = 0;
			$result = $db->where($where)->data($data)->save();
			if($result){
				D("System_log")->add_log("取消了".$this->config['mall_alias_name']."商品优选",$this->config['mall_alias_name'],$goods_id);
				exit(json_encode(array('errorcode'=>0,'info'=>'取消优选成功！')));
			}else{
				exit(json_encode(array('errorcode'=>1,'info'=>'取消优选失败！')));
			}
		}else{//设为优选
			$data = array();
			$data['is_preference'] = 1;
			$result = $db->where($where)->data($data)->save();
			if($result){
				D("System_log")->add_log("设置了".$this->config['mall_alias_name']."商品优选",$this->config['mall_alias_name'],$goods_id);
				exit(json_encode(array('errorcode'=>0,'info'=>'设为优选成功！')));
			}else{
				exit(json_encode(array('errorcode'=>1,'info'=>'设为优选失败！')));
			}
		}
	}


	public function shop()
	{
		$where = "s.status=1 AND s.have_shop=1";//array('status' => 1);

		if(!empty($_GET['keyword'])){
			$where .= " AND s.name LIKE '%{$_GET['keyword']}%'";
		}
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where .= " AND s.{$area_index} = '{$this->system_session['area_id']}'";
		}

		$sql_count = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "merchant_store_mall AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where}";
		$mode = new model();
		$count = $mode->query($sql_count);
		$count = isset($count[0]['cnt']) ? $count[0]['cnt'] : 0;
		import('@.ORG.system_page');
		$p = new Page($count, 20);

		$sql = "SELECT `m`.`name` AS merchant_name, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`sh`.`s_is_open_own`,`sh`.`delivery_range_type`,`sh`.`delivery_radius` FROM ";
		$sql .= C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "merchant_store_mall AS sh ON `sh`.`store_id`=`s`.`store_id`";
		$sql .= " WHERE {$where} ORDER BY `sh`.`delivery_radius` ASC LIMIT {$p->firstRow}, {$p->listRows}";
		$order_list = $mode->query($sql);
		$this->assign('order_list', $order_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}

	public function shop_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		$database = D('Merchant_store_shop');
		$where['store_id'] = intval($_GET['store_id']);

		$now_shop = $database->field(true)->where($where)->find();
		
		$now_mall = $this->get_mall_data($now_shop);
		$now_shop = array_merge($now_shop, $now_mall);

		if (empty($now_shop)) {
			$this->frame_error_tips('没有找到该店铺信息！');
		}
		if ($now_shop['delivery_range_polygon']) {
		    $now_shop['delivery_range_polygon'] = substr($now_shop['delivery_range_polygon'], 9, strlen($now_shop['delivery_range_polygon']) - 11);
		    $lngLatData = explode(',', $now_shop['delivery_range_polygon']);
		    array_pop($lngLatData);
		    $lngLats = array();
		    foreach ($lngLatData as $lnglat) {
		        $lng_lat = explode(' ', $lnglat);
		        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
		    }
		    $now_shop['delivery_range_polygon'] = json_encode(array($lngLats));
		}
		
		$is_have_two_time = 0;
		if ($this->config['delivery_time2']) {
		    $delivery_times2 = explode('-', $this->config['delivery_time2']);
		    $start_time2 = $delivery_times2[0];
		    $stop_time2 = $delivery_times2[1];
		    if ($start_time2 != $stop_time2) {
		        $is_have_two_time = 1;
		    }
		}
		
		$is_have_three_time = 0;
		if ($this->config['delivery_time2']) {
		    $delivery_times3 = explode('-', $this->config['delivery_time3']);
		    $start_time3 = $delivery_times3[0];
		    $stop_time3 = $delivery_times3[1];
		    if ($start_time2 != $stop_time2) {
		        $is_have_three_time = 1;
		    }
		}
		
		$customs = D('Deliver_custom')->field(true)->select();
		foreach ($customs as &$value) {
		    $value['delivery_range_polygon'] = substr($value['delivery_range_polygon'], 9, strlen($value['delivery_range_polygon']) - 11);
		    $lngLatData = explode(',', $value['delivery_range_polygon']);
		    array_pop($lngLatData);
		    $lngLats = array();
		    foreach ($lngLatData as $lnglat) {
		        $lng_lat = explode(' ', $lnglat);
// 		        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
		        $lngLats[] = $lng_lat[1] . '-' . $lng_lat[0];
		    }
		    $value['delivery_range_polygon'] = implode('|', $lngLats);
		}
		$this->assign('customs', $customs);
		
		$this->assign('is_have_two_time', $is_have_two_time);
		$this->assign('is_have_three_time', $is_have_three_time);
		$now_store = D('Merchant_store')->field(true)->where($where)->find();
		$now_shop = array_merge($now_shop, $now_store);
		$this->assign('now_shop', $now_shop);
		$this->display();
	}


	private function get_mall_data($now_shop){
		$db = M('Merchant_store_mall');
		$find = $db->where(array('store_id'=>$now_shop['store_id']))->find();
		if($find) return $find;
		$fidld_config = array('store_id','delivery_range_type','custom_id','delivery_radius','delivery_area','delivery_range_polygon','s_send_time','s_basic_price','s_extra_price','s_is_open_own','s_basic_price1','s_free_type','s_delivery_fee','s_basic_distance','s_per_km_price','s_full_money','s_basic_price2','s_free_type2','s_delivery_fee2','s_basic_distance2','s_per_km_price2','s_full_money2','s_basic_price3','s_free_type3','s_delivery_fee3','s_basic_distance3','s_per_km_price3','s_full_money3','s_is_open_virtual','virtual_delivery_fee');
		$data = array();
		foreach ($fidld_config as $key => $value) {
			$data[$value] = $now_shop[$value];
		}
		$db->add($data);
		return $data;
	}

	public function shop_amend()
	{
		if (IS_POST) {
			$where = array('store_id' => $_POST['id']);
			if ($shop = D('Merchant_store_shop')->field(true)->where($where)->find()) {
    			unset($_POST['id']);
    			if ($_POST['delivery_range_type'] == 1) {
    			    if ($_POST['custom_id']) {
    			        $custom = D('Deliver_custom')->field(true)->where(array('id' => intval($_POST['custom_id'])))->find();
    			        if (empty($custom)) {
    			            $this->error('请选择正确的服务范围');
    			        }
    			        $_POST['delivery_range_polygon'] = $custom['delivery_range_polygon'];
    			    } elseif ($_POST['delivery_range_polygon']) {
    			        $latLngArray = explode('|', $_POST['delivery_range_polygon']);
    			        if (count($latLngArray) < 3) {
    			            $this->error('请绘制一个合理的服务范围！');
    			        } else {
    		                $latLngData = array();
    		                foreach ($latLngArray as $row) {
    		                    $latLng = explode('-', $row);
								if(count($latLng) == 3){
									if(empty($latLng[0])){
										$latLng[0] = '-' . $latLng[1];
										$latLng[1] = $latLng[2];
									}
								}
    // 		                    $latLngData[] = array('lat' => $latLng[0], 'lng' => $latLng[1]);
    		                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];//array('lat' => $latLng[0], 'lng' => $latLng[1]);
    		                }
    		                $latLngData[] = $latLngData[0];
    		                $_POST['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
    			        }
    			    } else {
    			        $this->error('请绘制您的服务范围！');
    			    }
    			    unset($_POST['delivery_radius']);
    			} else {
    			    unset($_POST['delivery_range_polygon']);
    			}
    			if ($_POST['s_send_time'] > 0) {
    			    $_POST['send_time'] = $_POST['s_send_time'];
    			} else {
    			    $_POST['send_time'] = $_POST['s_send_time'] = $this->config['deliver_send_time'];
    			}
    			$diffTime = 1;
    			if ($shop['send_time_type'] == 1) {
    			    $diffTime = 60;
    			} elseif ($shop['send_time_type'] == 2) {
    			    $diffTime = 1440;
    			} elseif ($shop['send_time_type'] == 3) {
    			    $diffTime = 1440 * 7;
    			} elseif ($shop['send_time_type'] == 4) {
    			    $diffTime = 1440 * 30;
    			}
    			$_POST['sort_time'] = $shop['work_time'] * $diffTime + $_POST['send_time'];
    			
    			$find = D('Merchant_store_mall')->where($where)->find();
    			if($find){
    			    if($_POST['s_is_open_own']==0){
                        $_POST['s_basic_price1']=0;
                        $_POST['s_free_type']=0;
                        $_POST['s_full_money']=0;
                        $_POST['s_delivery_fee']=0;
                    }
    				$result = D('Merchant_store_mall')->where($where)->save($_POST) !== false ? true : false;
    			}else{
    				$_POST['store_id'] = $where['store_id'];
    				$result =  D('Merchant_store_mall')->add($_POST);
    			}
    			if ($result) {
    				$this->success('编辑成功！');
    			} else {
    				$this->error('编辑失败！请重试~');
    			}
			} else {
			    $this->error('数据不正确');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
	
	public function image_show(){
		$this->display();
	}
}
?>