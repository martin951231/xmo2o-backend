<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsTicketSpec extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 关联属性值表
     */
    public function values()
    {
        return $this->hasMany(\app\life_tools\model\db\LifeToolsTicketSpecVal::class, 'spec_id', 'spec_id');
    }
}