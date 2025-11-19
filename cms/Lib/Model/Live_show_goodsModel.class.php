<?php
class Live_show_goodsModel extends Model
{
	//获取直播商品信息
	public function get_goods_by_liveid($live_id, $order="no asc"){
		if(empty($live_id)) return [];
		$where = [
			'live_id' => $live_id,
			'is_del' => 0
		];
		$data = $this->where($where)->field("id,goods_id,no,sale_nums,recording,record_status,record_result")->order($order)->select();
		return $data;	

	}

	/**
	 * 获取对应业务下的真实的goodsid
	 * @param  [type] $live_id  [description]
	 * @param  [type] $business shop=外卖 mall=商城...
	 * @return [type]           [description]
	 */
	public function get_goods_percent_by_live($live_id, $business){
		if(empty($live_id) || empty($business)) return [];
		$goods = array_column($this->get_goods_by_liveid($live_id),'goods_id');
		$where = [
			'business' => $business,
			'id' => ['in', $goods]
		];
		$data = D("Live_goods")->where($where)->field("business_id as goods_id,percent")->select();
		return ['goodids' => array_column($data, 'goods_id'), 'percent' => array_column($data, 'percent', 'goods_id')];
	}

	/**
	 * 添加销量
	 * @param goods_id 业务商品ID
	 * @param live_id  直播ID
	 * @param $nums 销售个数
	 * @param $type 'shop' => 外卖  'mall' => 商城  'group' => 团购
	 */
	public function add_sale_nums($goods_id, $live_id, $nums, $type){
		if(empty($goods_id) || empty($live_id) || empty($nums) || empty($type)) return ;
		$where = [
			'business' => $type,
			'business_id' => $goods_id,
			'status' => 1
		];
		$live_goods = M("Live_goods")->where($where)->find();
		if($live_goods){
			$where = [
				'live_id' => $live_id,
				'goods_id' => $live_goods['id'],
				'is_del' => 0
			];
			$live_show_goods = $this->where($where)->find();
			if($live_show_goods){
				$this->where($where)->data(['sale_nums'=>($live_show_goods['sale_nums']+$nums)])->save();
				M("Live_goods")->where(['id'=>$live_goods['id']])->data(['show_sales'=>($live_goods['show_sales']+$nums)])->save();
				return true;
			}
		}
		return false;
	}
}