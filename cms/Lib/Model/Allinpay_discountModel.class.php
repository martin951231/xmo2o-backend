<?php
class Allinpay_discountModel extends Model{
    public function __construct() {
        parent::__construct();
    }

    public function getDiscounts($merId)
    {
        //use_type 0:全部商家，1：指定商家
        //use_area 0:全区域，1：指定区域
        $data = array();
        $now_time = time();
        //1:全区域，全商家
        $sql = "SELECT * FROM " . C('DB_PREFIX') . "allinpay_discount WHERE  `status`=1 AND `use_area`=0 AND `use_type`=0 AND (". $now_time ." > `startdate` AND ".$now_time." < `enddate`)";
        $result = $this->query($sql);
        foreach ($result as $row) {
            $data[] = $row;
        }

        //2.全区域，指定商家
        //4.指定区域，指定商家
        $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "allinpay_discount AS d INNER JOIN " . C('DB_PREFIX') . "allinpay_discount_merchant AS m ON d.id=m.did WHERE `status`=1 AND use_type=1 AND m.mer_id=" . $merId ." AND (". $now_time ." > `startdate` AND ".$now_time." < `enddate`)";
        $result = $this->query($sql);
        foreach ($result as $row) {
            $data[] = $row;
        }

        //3.指定区域，全部商家
        $merchant = D('Merchant')->field('province_id, city_id, area_id')->where(array('mer_id' => $merId))->find();
        $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "allinpay_discount AS d INNER JOIN " . C('DB_PREFIX') . "allinpay_discount_area AS a ON a.did=d.id WHERE `status`=1 AND `use_area`=1 AND use_type=0 AND a.aid IN (" . $merchant['province_id'] . ',' . $merchant['city_id'] . ',' . $merchant['area_id'] . ') AND ('.$now_time." > `startdate` AND ".$now_time." < `enddate`)" ;
        $result = $this->query($sql);
        foreach ($result as $row) {
            $data[] = $row;
        }
        //去除优惠
        foreach($data as $key => $da){
            $where['did'] = $da['id'];
            $where['is_paid'] = 1;
            //商家名额超出
            if($da['p_num']>0){
                //0商家平均名额  1商家共享名额
                if($da['p_type']==0){
                    $where['mer_id'] = $merId;
                    $count = M('Allinpay_order')->where($where)->count();
                    if($count >= $da['p_num']){
                        unset($data[$key]);
                    }
                    unset($where['mer_id']);
                }else{
                    $count = M('Allinpay_order')->where($where)->count();
                    if($count >= $da['p_num']){
                        unset($data[$key]);
                    }
                }
            }
            //超出总优惠金额
            if($da['max_money']>0){
                $totalMoney = M('Allinpay_order')->where($where)->sum('discount_money');
                if($totalMoney && $totalMoney >= $da['max_money']){
                    unset($data[$key]);
                }
            }

        }
        return $data;
    }
    public function getCycleTime($type){
       // 1当月 2当周 3当日
        if($type==1){
            $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
            $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
            return $beginThismonth .','.$endThismonth;
        }elseif($type==2){
            $beginThisweek = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
            $endThisweek = mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
            return $beginThisweek .','.$endThisweek;
        }elseif ($type==3){
            $beginThisday = mktime(0,0,0,date("m"),date('d'),date('Y'));
            $endThisday = mktime(23,59,59,date("m"),date("d"),date("Y"));
            return $beginThisday .','.$endThisday;
        }
    }
    public  function get_alias_name(){
        $c_name = array(
            'all'=>L_('选择分类'),
            'group'=>C('config.group_alias_name'),
            'shop'=>C('config.shop_alias_name'),
            'shop_offline'=>C('config.shop_alias_name').L_('线下零售'),
            'meal'=>C('config.meal_alias_name'),
            'appoint'=>C('config.appoint_alias_name'),
            'waimai'=>L_('外卖'),
            'store'=>L_('优惠买单'),
            'cash'=>L_('到店支付'),
            'weidian'=>L_('微店'),
            'wxapp'=>L_('营销'),
            'withdraw'=>L_('提现'),
            'coupon'=>L_('优惠券'),
            'activity'=>L_('平台活动'),
            'spread'=>L_('商家推广'),
            'market_order'=>L_('批发'),
            'sub_card'=>L_('免单套餐'),
        );
        return $c_name;
    }
}
?>