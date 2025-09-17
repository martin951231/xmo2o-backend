<?php
/**
 * 景区团体票配置
 */

namespace app\life_tools\model\db;
use think\Model;

class LifeToolsDistributionSetting extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
 
    /**
     * 获取分销配置信息
     * @author nidan
     * @date 2022/3/22
     */
    public function getSetDetal($merId)
    {
        if(!$merId){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $detail = $this->getOne(['mer_id'=>$merId]);
        if(!$detail){
            return false;
        }
        return $detail;
    }
}
