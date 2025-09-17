<?php
/**
 * 三级分销业务员绑定商家表
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsDistributionUserBindMerchant extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';
    // json字段会自动处理
	protected $json = ['custom_form'];
    //以数组的方式处理
    protected $jsonAssoc = true;

    public function myAuthentication($where,$field,$order,$pageSize)
    {
        $data = $this->alias('a')
            ->join('merchant b','a.mer_id = b.mer_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->paginate($pageSize)->toArray();
        return $data;
    }
}