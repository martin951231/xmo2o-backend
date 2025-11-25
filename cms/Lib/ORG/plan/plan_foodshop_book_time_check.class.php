<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2020/01/14.
 * Time: 11:17.
 */

// 解决桌台预订并发问题
class plan_foodshop_book_time_check extends plan_base{
	
    //查询所有待处理的记录
    public function runTask(){
        $where = array();
        $where['status'] = 0;
		
		$num = 5;
		
		$process = true;
		while($process){
			
			$list = M('foodshop_book_time_check')->field(true)->where($where)->limit($num)->select();
			
			if(empty($list)){
				
				return true;
				
			}else{
				
				foreach($list as $val){
					$this->keepThread();
					D('Foodshop_order')->foodshopBookTimeCheck($val['id']);
				}
			}
			
		}
        return true;
    }
}
?>