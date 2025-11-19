<?php
/**
 * 景区团体票订单绑定的用户信息
 */

namespace app\life_tools\model\db;
use think\Model;

class LifeToolsGroupOrderTourists extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // json字段会自动处理
	protected $json = ['tourists_custom_form'];
    //以数组的方式处理
    protected $jsonAssoc = true;

    /**
     * 通过订单id获取团体篇用户信息
     * @author Nd
     * @date 2022/4/24
     * @param $order_id
     */
    public function getUserInfo($where,$field)
    {
        $data = $this->alias('a')
            ->join('user b','a.uid = b.uid')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }
}