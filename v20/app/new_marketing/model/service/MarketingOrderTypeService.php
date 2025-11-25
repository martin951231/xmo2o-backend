<?php
/**
 * 订单类型表
 * User: 衡婷妹
 * Date: 2021/9/03
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingOrderType;

class MarketingOrderTypeService
{	
	public $newMarketingOrderTypeModel = null;


    public function __construct()
    {
        $this->newMarketingOrderTypeModel = new NewMarketingOrderType;
    }
	
    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $list = $this->newMarketingOrderTypeModel->getOne($where,$field,$order);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 获得多条数据
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
		$start = ($page - 1) * $limit;
		$start = max($start, 0);
        $list = $this->newMarketingOrderTypeModel->getSome($where,$field,$order,$start,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 添加一条数据
     * @return int|bool
     */
    public function add($data){
		if(empty($data)){
			return false;
		}
        $id = $this->newMarketingOrderTypeModel->add($data);
        if(!$id) {
            return false;
        }
        return $id;
    }


    /**
     * 更新一条数据
     * @return bool
     */
    public function updateThis($where, $data){
		if(empty($data) || empty($where)){
			return false;
		}
        $res = $this->newMarketingOrderTypeModel->updateThis($where, $data);
        if(!$res) {
            return false;
        }
        return $res;
    }
}