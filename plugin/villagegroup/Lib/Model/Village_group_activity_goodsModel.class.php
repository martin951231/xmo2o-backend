<?php

class Village_group_activity_goodsModel extends Model
{
	//通过商品ID，获取商品的活动信息
	public function getActivityInfo($goods_ids, $type = 2){
		if(empty($goods_ids)) return [];
		$where = [
			'g.is_del' => 0,
			'g.goods_id' => is_array($goods_ids) ? ['in', $goods_ids] : $goods_ids,
			'a.status' => 1,
			'a.type' => $type,
			'a.start_time' => ['elt', time()],
			'a.end_time' => ['gt', time()],
		];
		if(is_array($goods_ids)){
			$info = $this->alias("g")->join("left join ".C("DB_PREFIX")."village_group_activity as a on a.activity_id=g.activity_id")->where($where)->field("a.*,g.buy_price,g.goods_id,g.buy_limit")->select();
		}
		else{
			$info = $this->alias("g")->join("left join ".C("DB_PREFIX")."village_group_activity as a on a.activity_id=g.activity_id")->where($where)->field("a.*,g.buy_price,g.goods_id,g.buy_limit")->find();	
		}
		
		return $info;
	}
    
    //通过商品ID，获取未开始的活动
	public function getNoStartActivityInfo($goods_id, $type = 2){
		if(empty($goods_id)) return [];
		$where = [
			'g.is_del' => 0,
			'g.goods_id' => $goods_id,
			'a.status' => 1,
			'a.type' => $type,
			'a.start_time' => ['between', [time(), strtotime(date('Y-m-d 23:59:59'))]],
		];
		$info = $this->alias("g")->join("left join ".C("DB_PREFIX")."village_group_activity as a on a.activity_id=g.activity_id")->where($where)->field("a.*")->order("a.start_time asc")->find();	
		return $info;
	}
}