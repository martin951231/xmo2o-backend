<?php
/**
 * 商家会员寄存-用户寄存记录
 * Author: hengtingmei
 * Date Time: 2021/11/04 11:49
 */

namespace app\merchant\model\service\card;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\service\MerchantStoreService;
use think\facade\Db;

class CardNewDepositGoodsBindUserService {
    public $cardNewDepositGoodsBindUserModel = null;
    public function __construct()
    {
        $this->cardNewDepositGoodsBindUserModel = new CardNewDepositGoodsBindUser();
    }

    /**
     * 根据条件获取用户会员卡
     * @param $where array 
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $res = $this->cardNewDepositGoodsBindUserModel->getOne($where);
        if(!$res) {
            return [];
        }
        
        return $res->toArray(); 
    }

    /**
     *
     * 获取会员卡信息
     * @param where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0) {
        if(empty($where)){
            return [];
        }

        $card = $this->cardNewDepositGoodsBindUserModel->getSome($where, $field,$order,$page,$limit);
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }

    /**
     * 获得总数
     * @param array $where 
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
           return [];
        }

        $res = $this->cardNewDepositGoodsBindUserModel->getCount($where);
        if(!$res) {
            return 0;
        }
        
        return $res; 
    }


    /**
     * 获得总数
     * @param array $where 
     * @param string $field 要统计的字段
     * @return array
     */
    public function getSumCount($where, $field='num') {
        if(empty($where)){
           return [];
        }

        $res = $this->cardNewDepositGoodsBindUserModel->where($where)->sum($field);
        if(!$res) {
            return 0;
        }
        
        return $res; 
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){        
        $result = $this->cardNewDepositGoodsBindUserModel->add($data);
        if(!$result) {
            return false;
        }
        return $result;        
    }
    
    /**
     * 更新数据
     * @param $data array 数据
     * @return array
     */
    public function updateByCondition($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->cardNewDepositGoodsBindUserModel->updateByCondition($where, $data);
        if(!$result) {
            return false;
        }
        return $result;
        
    } 

}