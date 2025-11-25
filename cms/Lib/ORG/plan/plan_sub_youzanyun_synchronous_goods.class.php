<?php    
/*
 * 批量同步有赞云商品 加入子计划任务
 * @author hengtingmei
 * @date 2021/08/18
*/
class plan_sub_youzanyun_synchronous_goods extends plan_base{
	public function runTask($param=null){
        if(empty($param)){
            return true;
        }

        $itemNum = $param['item_num'];// 分组下商品数量
        $id = $param['id'];// 有赞云商品分组id

        if(empty($itemNum) || empty($id)){
            return true;
        }


        // 加入子计划任务
        $now_time = time();
        $pageSize = 10;
        $pageCount = ceil($itemNum/$pageSize);
        $page = 1;
        while($pageCount > 0){
            $arr= array();
            $arr['param'] = serialize(array('id'=> $id,'page'=>$page,'pageSize'=>$pageSize));
            $arr['plan_time'] = in_array($id, [113106078,113106077]) ? 1 : 0;// 最热最新两个分类几乎包含所有商品，最后执行防止阻塞其他商品
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_youzanyun_synchronous_goods_list';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'sub_youzanyun_synchronous_goods_save'.$id.$page;
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
            $pageCount--;
            $page++;
            $this->keepThread();
        }
		return true;
	}

	


}
?>