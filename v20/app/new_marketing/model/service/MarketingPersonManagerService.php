<?php
/**
 * 业务经理子表
 * User: 衡婷妹
 * Date: 2021/9/03
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonManager;

class MarketingPersonManagerService
{	
	public $newMarketingPersonManagerModel = null;


    public function __construct()
    {
        $this->newMarketingPersonManagerModel = new NewMarketingPersonManager;
    }

    /**
     * 增加提成金额
     * @return bool
     */
    public function incCommission($where, $money, $field){
		if(empty($money) || empty($where) || empty($field)){
			return false;
		}
        $res = $this->newMarketingPersonManagerModel->where($where)->inc($field, $money)->update();
        if(!$res) {
            return false;
        }
        return $res;
    }
	
    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $list = $this->newMarketingPersonManagerModel->getOne($where,$field,$order);
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
        $list = $this->newMarketingPersonManagerModel->getSome($where,$field,$order,$start,$limit);
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
        $id = $this->newMarketingPersonManagerModel->add($data);
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
        $res = $this->newMarketingPersonManagerModel->updateThis($where, $data);
        if(!$res) {
            return false;
        }
        return $res;
    }

    public function getPersonSome($where,$field){
		return (new NewMarketingPerson())->getSomePerson($where, $field,'',2);
	}
}