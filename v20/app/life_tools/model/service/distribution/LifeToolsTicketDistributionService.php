<?php


namespace app\life_tools\model\service\distribution;

use app\life_tools\model\db\LifeToolsDistributionSetting;
use app\life_tools\model\db\LifeToolsTicketDistribution;

class LifeToolsTicketDistributionService
{
    public $lifeToolsTicketDistributionModel = null;

    public function __construct()
    {
        $this->lifeToolsTicketDistributionModel = new LifeToolsTicketDistribution();
    }

    /**
     * 配置门票分销佣金
     * @author nidan
     * @date 2022/4/6
     */
    public function editDistributionPrice($params)
    {
        if(!$params['ticket_id']){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $data['ticket_id'] = $params['ticket_id'] ?? 0;
        $data['secondary_commission'] = $params['secondary_commission'] ?? 0;
        $data['third_commission'] = $params['third_commission'] ?? 0;
        $data['update_time'] = time();
        $result = $this->lifeToolsTicketDistributionModel->getOne(['ticket_id'=>$params['ticket_id']]);
        if($result){
            $res = $this->lifeToolsTicketDistributionModel->updateThis(['ticket_id'=>$params['ticket_id']], $data);
        }else{
            $data['create_time'] = time();
            $res = $this->lifeToolsTicketDistributionModel->add($data);
        }
        if($res === false){
            throw new \think\Exception(L_('保存失败，请稍后重试'), 1003);
        }
        return ['msg' => '保存成功'];
    }
}