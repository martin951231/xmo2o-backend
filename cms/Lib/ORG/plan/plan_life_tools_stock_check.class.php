<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/19.
 * Time: 15:31.
 */

class plan_life_tools_stock_check extends plan_base{
	
    //查询所有待处理的记录
    public function runTask(){

    	fdump('plan_life_tools_stock_check', 'plan_life_tools_stock_check',1);
		
        $where = array();
        $where['status'] = 0;
		
		$num = 5;
		
		$process = true;
		// while($process){
			
			$list = M('Life_tools_stock_check')->field(true)->where($where)->limit($num)->select();

			if(empty($list)){
				return true;
			}else{
				
				foreach($list as $val){
					$db = M('Life_tools_stock_check');
			        $goods = json_decode($val['goods'],true);
		      
        			// 购买日期
        			$todaySaleCount = 0;
        			if($goods['date']){
        				// 查询当日销量
        				$where = [
        					'ticket_id' => $goods['ticket_id'],
        					'ticket_time' => $goods['date'],
        					'order_status' => ['not in','50,60'],
        					'is_give' => 0,
        				];
        				$todaySaleCount = M('Life_tools_order')->where($where)->sum('num');
        			}

        			// 当日总库存
        			$todayStockNumTotal = M('Life_tools_ticket')->where(['ticket_id' => $goods['ticket_id']])->find();

        			// 当日剩余库存
        			$todayStockNum = $todayStockNumTotal['stock_num'] - $todaySaleCount;
        			if($goods['type']=='scenic'){
                        if($todayStockNum <= 0 || $todayStockNum < $goods['num']){
                            //库存不充足
                            $save['status'] = 2;
                            $db->where($where)->data($save)->save();
                            return true;
                        }
                        // 查询活动总库存
                        // $cache_key = 'life_tools_limit_act_stock'.$goods['act_id'].'_'.$goods['ticket_id'];
                        // $cache_stock = cache_sql($cache_key);
                        // if($cache_stock===false){//没有设置缓存
                        $nowSku = M('life_scenic_limited_sku')->where(['act_id'=>$goods['act_id'], 'ticket_id'=>$goods['ticket_id']])->find();
                        $cache_stock = $nowSku['act_stock_num'];
                        // 	cache_sql($cache_key, $nowSku['act_stock_num'], 10*60);    //由于商品最低可设置10分钟回滚库存，设置为10分钟缓存。
                        // }


                        // 活动今日剩余库存
                        $actStockNum = min($cache_stock,$todayStockNum);

                        if($todayStockNum <= 0 || $todayStockNum < $goods['num']){
                            //库存不充足
                            $save['status'] = 2;
                            $db->where($where)->data($save)->save();
                            return true;
                        }else{//库存充足
                            $save['status'] = 1;
                            $db->where($where)->data($save)->save();
                            return true;
                        }
                    }

                    if($goods['type']=='stadium' || $goods['type']=='course') {
                        $nowSku = M('life_tools_sports_seconds_kill_ticket_sku')->where(['act_id'=>$goods['act_id'], 'ticket_id'=>$goods['ticket_id']])->find();
                        if($goods['stock_type']==1 && $nowSku['act_stock_num']<=0){//总库存
                             //库存不充足
                            $save['status'] = 2;
                            $db->where($where)->data($save)->save();
                            return true;
                        }elseif ($goods['stock_type']==2 && $nowSku['day_stock_num']<=0){//每日库存
                            //库存不充足
                            $save['status'] = 2;
                            $db->where($where)->data($save)->save();
                            return true;
                        }else{//库存充足
                            $save['status'] = 1;
                            $db->where($where)->data($save)->save();
                            return true;
                        }
                    }
				}
			}
			
		// }
        return true;
    }
}
?>