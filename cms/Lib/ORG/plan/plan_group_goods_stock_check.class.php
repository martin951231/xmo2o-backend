<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/19.
 * Time: 15:31.
 */

class plan_group_goods_stock_check extends plan_base{
	
    //查询所有待处理的记录
    public function runTask(){
		
        $where = array();
        $where['status'] = 0;
		
		$num = 5;
		
		$process = true;
		while($process){
			
			$list = M('Group_goods_stock_check')->field(true)->where($where)->limit($num)->select();
			
			if(empty($list)){
				
				return true;
				
			}else{
				
				foreach($list as $val){
					D('Group')->check_group_stock($val['id']);
				}
			}
			
		}
        return true;
    }
}
?>