<?php

/**
 * @Author: jjc
 * @Date:   2020-06-22 15:36:51
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-22 16:42:31
 */
namespace app\mall\model\service;
use app\mall\model\db\MallActivity as  MallActivityModel;
use app\mall\model\service\MallGoodsService as MallGoodsService;

class MallActivityService{

	public $MallActivityModel = null;

    public function __construct()
    {
        $this->MallActivityModel = new MallActivityModel();
    }

	public function getAllList(){
		$where = [
			['is_del','=',0]
		];
		$list = $this->MallActivityModel->getAll($where);
		if($list){
			$list = $this->dealList($list);
		}
		return $list;
	}

	public function dealList($list){
		if($list){
			$MallGoodsService = new MallGoodsService();
			foreach ($list as $key => $val) {
				if($val['goods_ids']){
					$goods = explode(',', $val['goods_ids']);
					$where = [
						['goods_id','in',$goods],
					];
					$field = 'goods_id,name as goods_name,min_price as price,origin_price,image';
					$list[$key]['goods_list'] = $MallGoodsService->getSome($where,$field);
				}
			}
		}
		return $list;
	}

	/**
     * @author 朱梦群
     * 根据条件获取活动总表的信息
     * @param $where
     * @param $field
     */
    public function getActInfo($where, $field)
    {
        $arr = $this->MallActivityModel->getActInfo($where, $field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

}