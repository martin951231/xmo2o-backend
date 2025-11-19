<?php    
/*
 * 批量同步有赞云商品 获得商品列表 加入子计划任务
 * @author hengtingmei
 * @date 2021/08/18
*/
class plan_sub_youzanyun_synchronous_goods_list extends plan_base{
	public function runTask($param=null){
        if(empty($param)){
            return true;
        }

        $page = $param['page'];
        $pageSize = $param['pageSize'];
        $id = $param['id'];// 有赞云商品分组id

        if(empty($page) || empty($id) || empty($pageSize)){
            return true;
        }

        // 获得分类信息
        $sortDetail = M('Shop_goods_sort')->where(['youzanyun_sort_id'=>$id])->find();
        if(!$sortDetail){
            return true;
        }

        // 店铺和有赞云配置
        $now_store = M('Merchant_store_shop')->where(['store_id' => $sortDetail['store_id']])->find();
        if(!$now_store || empty($now_store['youzanyun_client_id']) || empty($now_store['youzanyun_client_secret']) || empty($now_store['youzanyun_grant_id'])){
            return true;
        }

        // 同步商品
        import('@.ORG.youzanyun.SynchronousGoods');
        $synchronousGoods = new SynchronousGoods($now_store['youzanyun_client_id'], $now_store['youzanyun_client_secret'], $now_store['youzanyun_grant_id'], $now_store['youzanyun_store_id']);

        $goodsList = $synchronousGoods->synchronousGoodsList($id, $page, $pageSize);

        foreach($goodsList['list'] as $_goods){

            // 加入子计划任务
            $now_time = time();
            $arr= array();
            $arr['param'] = serialize(array('id'=> $id,'goods_id' => $_goods['id']));
            $arr['plan_time'] = in_array($id, [113106078,113106077]) ? 2 : 1;// 最热最新两个分类几乎包含所有商品，最后执行防止阻塞其他商品
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_youzanyun_synchronous_goods_save';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'sub_youzanyun_synchronous_goods_save'.$id.$_goods['id'];
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