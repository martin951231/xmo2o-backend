<?php
/**
 * 订单关联业务员表
 * User: 衡婷妹
 * Date: 2021/9/03
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingOrderTypePerson;

class MarketingOrderTypePersonService
{	
	public $newMarketingOrderTypePersonModel = null;


    public function __construct()
    {
        $this->newMarketingOrderTypePersonModel = new NewMarketingOrderTypePerson;
    }
	
    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $list = $this->newMarketingOrderTypePersonModel->getOne($where,$field,$order);
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
        $list = $this->newMarketingOrderTypePersonModel->getSome($where,$field,$order,$start,$limit);
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
        $id = $this->newMarketingOrderTypePersonModel->add($data);
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
        $res = $this->newMarketingOrderTypePersonModel->updateThis($where, $data);
        if(!$res) {
            return false;
        }
        return $res;
    }
}