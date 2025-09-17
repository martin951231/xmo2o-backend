<?php
/**
 * 业务员子表
 * User: 衡婷妹
 * Date: 2021/9/03
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonSalesman;

class MarketingPersonSalesmanService
{	
	public $newMarketingPersonSalesmanModel = null;


    public function __construct()
    {
        $this->newMarketingPersonSalesmanModel = new NewMarketingPersonSalesman;
    }

    /**
     * 增加提成金额
     * @return bool
     */
    public function incCommission($where, $money, $field){
		if(empty($money) || empty($where) || empty($field)){
			return false;
		}
        $res = $this->newMarketingPersonSalesmanModel->where($where)->inc($field, $money)->update();

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
        $list = $this->newMarketingPersonSalesmanModel->getOne($where,$field,$order);
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
        $list = $this->newMarketingPersonSalesmanModel->getSome($where,$field,$order,$start,$limit);
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
        $id = $this->newMarketingPersonSalesmanModel->add($data);
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
        $res = $this->newMarketingPersonSalesmanModel->updateThis($where, $data);
        if(!$res) {
            return false;
        }
        return $res;
    }

    public function getPersonSome($where, $field){
		return (new NewMarketingPerson())->getSomePerson($where, $field,'',1);
	}
}