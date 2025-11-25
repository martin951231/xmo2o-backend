<?php
/**
 * 直播商品service
 * add by lumin
 */

namespace app\common\model\service\live;

use app\common\model\db\LiveVideoGoods;
use app\common\model\db\LiveShowGoods;
use app\common\model\db\LiveGoods;

class LiveGoodService{
	public function getGoodsPercentByLive($live_id, $video_id, $business){
		if((empty($live_id) && empty($video_id)) || empty($business)) return [];
		if($live_id > 0){
			$goods = array_column($this->getGoodsByShowId($live_id),'goods_id');
		}
		else{
			$goods = array_column($this->getGoodsByVideoId($video_id),'goods_id');	
		}
		$where = [
			['business', '=', $business],
			['id', 'in', $goods]
		];
		$data = (new LiveGoods)->getSome($where, "business_id as goods_id,percent");
		if(empty($data)) return [];
		$data = $data->toArray();
		return ['goodids' => array_column($data, 'goods_id'), 'percent' => array_column($data, 'percent', 'goods_id')];
	}

	public function getGoodsByShowId($live_id, $order="no asc"){
		if(empty($live_id)) return [];
		$where = [
			['live_id', '=', $live_id],
			['is_del', '=', 0]
		];
		$field = "id,goods_id,no,sale_nums,recording,record_status,record_result";
		$data = (new LiveShowGoods)->getSome($where, $field, $order);
		if(empty($data)) return [];
		return $data->toArray();
	}

	public function getGoodsByVideoId($video_id, $order="no asc"){
		if(empty($video_id)) return [];
		$where = [
			['video_id', '=', $video_id],
			['is_del', '=', 0]
		];
		$field = "id,goods_id,no";
		$data = (new LiveVideoGoods)->getSome($where, $field, $order);
		if(empty($data)) return [];
		return $data->toArray();
	}

	/**
	 * 添加销量
	 * @param goods_id 业务商品ID
	 * @param live_id  直播ID
	 * @param video_id  短视频ID
	 * @param $nums 销售个数
	 * @param $type 'shop' => 外卖  'mall' => 商城  'group' => 团购
	 */
	public function addSaleNums($goods_id, $live_id, $video_id, $nums, $type){
		if(empty($goods_id) || (empty($live_id) && empty($video_id)) || empty($nums) || empty($type)) return ;
		$where = [
			['business', '=', $type],
			['business_id', '=', $goods_id],
			['status', '=', '1']
		];
		$live_goods = (new LiveGoods)->getOne($where);
		if($live_goods){
			$live_goods = $live_goods->toArray();
			$where = [
				['goods_id', '=', $live_goods['id']],
				['is_del', '=', 0]
			];
			if($live_id > 0){
				$where[] = ['live_id', '=', $live_id];
				$live_show_goods = (new LiveShowGoods)->getOne($where);
			}
			elseif($video_id > 0){
				$where[] = ['video_id', '=', $video_id];
				$live_show_goods = (new LiveVideoGoods)->getOne($where);	
			}
			if(isset($live_show_goods) && !empty($live_show_goods)){
				$live_show_goods = $live_show_goods->toArray();
				if($live_id > 0){
					(new LiveShowGoods)->updateThis($where, ['sale_nums'=>($live_show_goods['sale_nums']+$nums)]);
				}
				elseif($video_id > 0){
					(new LiveVideoGoods)->updateThis($where, ['sale_nums'=>($live_show_goods['sale_nums']+$nums)]);	
				}
				(new LiveGoods)->updateThis([['id','=',$live_goods['id']]], ['video_sales'=>($live_goods['video_sales']+$nums)]);
				return true;
			}
		}
		return false;
	}

}