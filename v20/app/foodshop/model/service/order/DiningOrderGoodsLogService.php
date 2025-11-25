<?php
/**
 * 餐饮订单商品日志service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/04 14:19
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderGoodsLog as DiningOrderGoodsLogModel;
class DiningOrderGoodsLogService {
    public $diningOrderGoodsLogModel = null;
    public function __construct()
    {
        $this->diningOrderGoodsLogModel = new DiningOrderGoodsLogModel();
    }

    /**
     *插入数据
     * @param $data array 
     * @return array
     */
    public function addGoodsLog($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();
        try {
            $result = $this->diningOrderGoodsLogModel->save($data);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    /**
     *插入数据获得菜品变更日志
     * @param $logIds array 
     * @return array
     */
    public function getGoodsLog($logIds){
        if(empty($logIds)){
            return [];
        }

        $where[] = [
            'log_id','in',implode(',',$logIds)
        ];

        $goodsLog = $this->getLogListByCondition($where);
        $returnArr = [];
        foreach($goodsLog as $log){

            $returnArr[] = [
                'desc' => ($log['staff_id']? $log['username'] : str_replace_name($log['username'])).L_('点了').msubstr($log['name'],0,8).($log['operate_type'] ? '-' : '+').$log['num'],
                'avatar' => $log['user_avatar']
            ];
        }
        
        return $returnArr;
    }
    
    
    /**
    * 获取日志列表
    * @param $where array 条件
    * @return array
    */
    public function getLogListByCondition($where){
        $logList = $this->diningOrderGoodsLogModel->getLogListByCondition($where);
        if(!$logList) {
            return [];
        }
        return $logList->toArray();
    }
}