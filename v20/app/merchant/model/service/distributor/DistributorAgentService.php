<?php
/**
 * 商家推广service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/15 10:14
 */

namespace app\merchant\model\service\distributor;
use app\common\model\service\UserService as UserService;
use app\merchant\model\db\DistributorAgent as DistributorAgentModel;
use app\merchant\model\service\MerchantService;
class DistributorAgentService {
    public $distributorAgentModel = null;
    public function __construct()
    {
        $this->distributorAgentModel = new DistributorAgentModel();
    }
  
        
    /**
     * 添加分销员的佣金
     * Created by subline.
     * Author: hengtingmei
     * Date Time: 2020/06/15 10:14
     */
	public function addMoney($merId,$money,$orderId){
		$userService = new UserService();
		$nowMerchant = (new MerchantService())->getMerchantByMerId($merId);
        $spreadCode = $nowMerchant['spread_code'];
        
        $spreadUser = $userService->getUser($spreadCode,'spread_code');
        if(empty($spreadUser)){
            return false;
        }
        
		$res = $this->getEffective($spreadUser['uid'],2);

		if($res){
			$spreadMoney = round($money*cfg('agent_percent')/100,2);

			if($spreadMoney>0){
                $desc = L_("用户在商家【{X1】消费，代理商获得佣金X2X3",['X1'=>$nowMerchant['name'],'X2'=>$spreadMoney,'X3'=>cfg('Currency_txt')]);
                $userService->addMoney($spreadUser['uid'],$spreadMoney,$desc);
                $this->addRow($spreadUser['uid'],$nowMerchant['mer_id'],1,$spreadMoney,$orderId,$desc);
			}
		}

		return true;
    }
    
    //获取代理商/分销员的有效性
    public function getEffective($uid, $type=1){
        $where['uid'] = $uid;
        $where['type'] = $type;
        $distributorAgent = $this->getOne($where);
        if(!$distributorAgent){
            return false;
        }
        if( $distributorAgent['start_time']< time() && $distributorAgent['end_time']>time()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加推广关系数据
     * @param $merId int 商家id
     * @return boolean
     */
    public function agentSpreadLog($merId){

        $date['mer_id'] = $merId;
        $date['add_time'] = time();
        // 商家信息
        $nowMerchant = (new MerchantService())->getMerchantByMerId($merId);

        //推广用户
        $spreadCode = $nowMerchant['spread_code'];
        $spreadUser = (new UserService())->getUser($spreadCode,'spread_code');

        // 推广数量
        $where = [
            'spread_code' => $spreadCode
        ];
        $spreadCount = (new MerchantService())->getCount($where);
        if($spreadCount>cfg('agent_spread_num')-1 && cfg('agent_spread_num')>0){
            throw new \think\Exception("推广数量超过限制", 1003);
        }
        
        // 该商家被推广记录
        $where = [];
        $where['mer_id'] = $merId;
        if((new AgentSpreadLogService())->getOne($where)){
            throw new \think\Exception("登该商家已经被推广了", 1003);
        }

        // 添加推广记录
        $date['uid'] = $spreadUser['uid'];
        $date['des'] = "【{$nowMerchant['name']}】成功入驻平台";
        if(!(new AgentSpreadLogService())->add($date)){
            throw new \think\Exception("推广记录添加失败", 1003);
        }
        return true;
    }

    public function addRow($uid,$mer_id,$income=1,$money,$orderId,$msg ){
        $time = time();
        $data['uid'] = $uid;
        $data['mer_id'] = $mer_id;
        $data['order_id'] = $orderId;
        $data['income'] = $income;
        $data['money'] = $money;
        $data['des'] = $msg;
        $data['add_time'] = $time;
        if(M('Agent_spread_money_list')->data($data)->add()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        
        $data['time'] = time();
        $result = $this->distributorAgentModel->add($data);
        if(!$result) {
            return false;
        }
        return $this->distributorAgentModel->id;
        
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

        $card = $this->distributorAgentModel->getOne($where);
        if(!$card) {
            return [];
        }
        
        return $card->toArray(); 
    }

}