<?php

class plan_foodshop_date_discount extends plan_base{
    public function runTask(){
        //初始化
        set_time_limit(0);
        $day = C('config.mer_table_discount_appoint');
        $now_time = time();
        $sql = 'SELECT f.store_id,f.other_discount,f.foodshop_tables_discount,m.mer_id,m.other_discount as other_discount_mer,m.foodshop_tables_discount as foodshop_tables_discount_mer  FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = s.mer_id  WHERE  m.status=1 AND s.have_meal=1';
        $store_list = M()->query($sql);

        for($i=0;$i<$day;$i++){
            $this->keepThread(); //保持单进程
            if($i==0){
                $thatday = date('Y-m-d');
            }else{
                $thattime = $now_time + $i * 86400;
                $thatday = date('Y-m-d',$thattime);
            }
            foreach($store_list as $foodshop){
                $this->keepThread(); //保持单进程
                $time_start = strtotime($thatday);
                $time_end = strtotime(date($thatday.' 23:59:59'));
                unset($where);
                $where['book_time'] = array('between',array($time_start,$time_end));
                $where['status'] = array('in',array(1,2,3,4));
                $where['order_from'] = 0;
                $where['store_id'] = $foodshop['store_id'];
                $order_index = D('foodshop_order')->where($where )->count();
                $merchant['foodshop_tables_discount'] = $foodshop['foodshop_tables_discount_mer'];
                $merchant['other_discount'] = $foodshop['other_discount_mer'];
                if($foodshop['foodshop_tables_discount']!=''|| ($foodshop['other_discount']>0 && $foodshop['other_discount']<100)){
                    $merchant['foodshop_tables_discount'] = $foodshop['foodshop_tables_discount'];
                    $merchant['other_discount'] = $foodshop['other_discount'];
                }else{
                    unset($where['store_id']);
                    $where['mer_id'] = $foodshop['mer_id'];
                    $order_index = D('Foodshop_order')->where($where)->count();
                }
                if($merchant['foodshop_tables_discount']!=''){
                    $merchant['foodshop_tables_discount'] = substr($merchant['foodshop_tables_discount'], 0, -1);
                    $discountArr= explode('|', $merchant['foodshop_tables_discount']);
                    $tables = [];
                    foreach($discountArr as $k => $v){
                        $this->keepThread(); //保持单进程
                        $v = explode(':',$v);
                        $tables[$k] = $v;
                    }
                    if(intval($order_index)>=count($tables)){
                        $mer_discount =  $merchant['other_discount'];
                    }else{
                        $mer_discount =  $tables[$order_index][1];
                    }
                }else{
                    $mer_discount =  $merchant['other_discount'];
                }
                $data['store_id'] = $foodshop['store_id'];
                $data['book_date'] = $thatday;
                $data['table_discount'] = $mer_discount / 10;
                $foodshop_tmp = M('Foodshop_date_discount')->where(array('store_id'=>$foodshop['store_id'],'book_date'=>$thatday))->find();
                if($foodshop_tmp){
                    if($foodshop_tmp['table_discount']!=$data['table_discount']){
                        M('Foodshop_date_discount')->where(array('store_id'=>$foodshop['store_id'],'book_date'=>$thatday))->save(array('table_discount'=>$data['table_discount']));
                    }
                }else{
                    M('Foodshop_date_discount')->add($data);
                }

            }
        }
        return true;
    }
}
?>