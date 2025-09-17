<?php
/**
 * Created by PigCMS.
 * User: jjc.
 * Date: 2019/6/27.
 * Time: 11:02.
 */

/**
 * Mall_goods_packModel 商城商品打包一口价类
 */
class Mall_goods_packModel extends Model{

    //添加商品一口价
    public function add_one($data){
        $data['add_time'] = time();
        return $this->add($data);
    }

    //更新商品一口价
    public function update_one($data,$id){
        return $this->where(array('id'=>$id))->data($data)->save();
    }

    //获取单个活动详情，包括商品列表
    public function get_one($id){
        if(!$id) return array();
        $where = array();
        $where['id'] = $id;
        $where['is_del'] = 0;
        $info = $this->where($where)->find();
        return $this->deal_one($info);
    }

    public function deal_one($data){        
        if($data){
            $get_goods_arr = array();
            //获取打包商品id集合
            $goods_ids = explode(',', $data['goods_str']);
            $data['goods_id_arr'] = $goods_ids;
            $where = array();
            $where['goods_id'] = array('in',$goods_ids);
            $goods_list = M('Shop_goods')->where($where)->field('goods_id,name,price,image,stock_num,image')->select();
            $goods = array();
            foreach ($data['goods_id_arr'] as $k => $v) {
                foreach ($goods_list as $gk => $gv) {
                    if($v==$gv['goods_id']){
                        if(!empty($gv['image'])){
                            $goods_image_class = new goods_image();
                            $tmp_pic_arr = explode(';', $gv['image']);
                            foreach ($tmp_pic_arr as $key => $value) {
                                $now_goods['pic_arr'][$key]['title'] = $value;
                                if('http' === substr($value,0,4)){
                                    $gv['pic_arr'][$key]['url'] = $value;
                                }else{
                                    $gv['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                                }
                            }
                            $gv['image'] = $gv['pic_arr'][0]['url'];
                        }else if(C('config.goods_default_image')){
                            $gv['image'] = C('config.goods_default_image');
                        }
                        $goods[] = $gv;
                    }
                }
            }
            $data['goods_list'] = $goods;
        }
        return $data;
    }

    //获取店铺打包一口价的活动
    public function get_store_pack($store_id,$ing=true,$deal=false){
        if(!$store_id) return array();
        $where = array();
        $where['store_id'] = $store_id;
        $where['is_del'] = 0;
        if($ing){
            $where['begin_time'] = array('elt',time());
            $where['end_time']   = array('egt',time());
        }
        $data = $this->where($where)->select();
        return $deal?$this->deal_store_data($data):$data;
    }

    public function deal_store_data($data){
        if($data){
            foreach ($data as $key => $val) {
                $data[$key]['goods_id_arr'] = explode(',', $val['goods_str']);
            }
        }
        return $data;
    }

    /**
     * @desc 根据商品id，获取一口价活动
     * @param $goods_id 商品id
     * @param $ing 是否活动中
     * @return array 
     */
    public function get_one_by_goood($goods_id,$ing=true){
        if(!$goods_id) return array();
        $where = array();
        $where['is_del'] = 0;
        if($ing){
            $where['begin_time'] = array('elt',time());
            $where['end_time']   = array('egt',time());
        }
        $where['_string'] = "FIND_IN_SET('$goods_id', goods_str)";
        $info = $this->where($where)->find();
        return $info;
    }

    /**
     * @desc 根据用户购买的商品计算出打包总价:
     * @tips:若订单内商品超出活动件数，则按照商品金额高低，默认价高商品参与活动，价低商品原价支付；
     * @tips:如果同价商品超出活动件数，则默认先加购的参与活动；
     * @tips:打包价高于商品原价时，将按照商品原价购买
     * @param $goods_list 商品列表
     * @param $store_id 店铺id
     * @return array 享受到的一口价活动详情
     */
    public function get_pack_price($goods_list,$store_id){
        $act_list = $this->get_store_pack($store_id,1,1);//店铺所有进行中的活动列表

        if(empty($act_list)){
            return false;
        }
        $all_act_goods_id = array();//所有参与活动的商品id集合
        foreach ($act_list as $v) {
            $all_act_goods_id = array_merge($all_act_goods_id,$v['goods_id_arr']);
        }
        
        $extra_price = 0;//不参与活动的商品总价
        foreach ($goods_list as $va) {
            if(!in_array($va['goods_id'], $all_act_goods_id)){
                $extra_price += $va['price']*$va['num'];
            }
        }

        $act_price = 0;//参与活动的商品总价
        $return_act =array();
        foreach ($act_list as $key => $val) {
            $goods_count = 0;
            $buy_goods_list_price  = array();
            foreach ($goods_list as $k => $v) {
                if(in_array($v['goods_id'], $val['goods_id_arr'])){
                    $goods_count += $v['num'];
                    for ($i=0; $i <$v['num'] ; $i++) { 
                        $buy_goods_list_price[] = $v['price'];
                    }
                }
            } 
            $origin_sum = array_sum($buy_goods_list_price);
            $act_list[$key]['goods_count'] = $goods_count;
            arsort($buy_goods_list_price);
            $buy_goods_list_price = array_values($buy_goods_list_price);
            $act_list[$key]['buy_goods_list_price'] = $buy_goods_list_price;
            $pack_price = $this->count_price($val['pack_price'],$val['pack_num'],$buy_goods_list_price);//参与活动的一口价
            $act_price += $pack_price;
            $temp = array();
            $temp['act_id'] = $val['id'];
            $temp['pack_discount_price'] = $origin_sum-$pack_price;
            $temp['pack_num'] = $val['pack_num'];
            $temp['pack_price'] = $val['pack_price'];
            if($origin_sum>$pack_price){
                $return_act[] = $temp;
            }
            if(empty($goods_count)){
                unset($act_list[$key]);
            }
        }

        $return = array();
        $return['pack_total_price'] = $act_price+$extra_price;//按一口价活动规则计算的所有商品的总价
        $return['pack_act_list']    = $return_act;//具体才加的一口价活动的详情

        if(empty($return_act)){
            return false;
        }

        return $return;

    }

    //计算一口价
    public function count_price($pack_price,$pack_num,$price_list){
        $total_price = 0;
        if(count($price_list)<$pack_num){//如果不满足周期购的件数，则直接返回商品价格
            foreach ($price_list as $v) {
                $total_price += $v;
            }
        }else{
            $rank_price_array = array();
            foreach ($price_list as $key => $val) {
                $new_key = floor($key/$pack_num);
                $new_second_key = $key%$pack_num;
                $rank_price_array[$new_key][$new_second_key] = $val;
            }
            foreach ($rank_price_array as $key => $value) {
                if(count($value)<$pack_num){
                    $total_price += array_sum($value);
                }else{
                    $add = array_sum($value)>$pack_price?$pack_price:array_sum($value);
                    $total_price += $add;
                }
            }
        }
        return $total_price;
    }


    /**
     * @desc 获取各个店铺所做的一口价活动，并判断购物车商品是否已满足:
     * @param $cart_list 购物车列表
     * @param $store_id 店铺id
     * @return int 商品总价 
     */
    public function deal_cart_list($cart_list){
        
        $pack_list = $this->get_all();
        foreach ($pack_list as $key => $val) {
            $cart_num = 0;
            foreach ($cart_list as $k => $v) {
                if(in_array($v['productId'], $val['goods_id_arr'])){
                    $cart_num += $v['count'];
                }
            }
            $pack_list[$key]['cart_num'] = $cart_num;    
        }
        foreach ($pack_list as $pk => $pv) {
            if($pv['pack_num']>$pv['cart_num']){
                $pack_list[$pk]['full'] = 0;
                $str = $pv['pack_num']-$pv['cart_num'];
                $pack_list[$pk]['full_text'] = '差'.$str.'件';
            }else{
                $pack_list[$pk]['full'] = 1;
                $pack_list[$pk]['full_text'] = '已满足';
            }

        }
        return $pack_list;
    }

    
    public function get_all($ing=true,$deal=true){
        $where = array();
        $where['is_del'] = 0;
        if($ing){
            $where['begin_time'] = array('elt',time());
            $where['end_time']   = array('egt',time());
        }
        $data = $this->where($where)->field(true)->select();
        return $deal?$this->deal_all_data($data):$data;
    }

    public function deal_all_data($data){
        if($data){
            foreach ($data as $key => $val) {
                $data[$key]['goods_id_arr'] = explode(',', $val['goods_str']);
            }
        }
        return $data;
    }


}
