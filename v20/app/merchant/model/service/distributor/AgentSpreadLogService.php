<?php
/**
 * 商家推广记录ervice
 * Created by phpStorm.
 * Author: hengtingmei
 * Date Time: 2020/07/03 13:21
 */

namespace app\merchant\model\service\distributor;
use app\merchant\model\db\AgentSpreadLog as AgentSpreadLogModel;
//use app\merchant\model\service\MerchantService;
class AgentSpreadLogService {
    public $agentSpreadLogModel = null;
    public function __construct()
    {
        $this->agentSpreadLogModel = new AgentSpreadLogModel();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        
        $data['time'] = time();
        $result = $this->agentSpreadLogModel->add($data);
        if(!$result) {
            return false;
        }
        return $this->agentSpreadLogModel->id;
        
    }

    /**
     * 根据条件获取一条记录
     * @param $where array
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $detail = $this->agentSpreadLogModel->getOne($where);
        if(!$detail) {
            return [];
        }
        
        return $detail->toArray();
    }

}