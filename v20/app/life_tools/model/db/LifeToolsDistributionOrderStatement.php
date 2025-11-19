<?php
/**
 * 三级分销业务员表
 */

namespace app\life_tools\model\db;

use app\common\model\db\Merchant;
use \think\Model;

class LifeToolsDistributionOrderStatement extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getStatusTextAttr($value, $data)
    {
        $statusMap = ['待确定', '已确定'];
        return $statusMap[$data['statement_status']] ?? '';
    }

    public function getTimeTextAttr($value, $data)
    {
        return date('Y.m.d H:i', $data['create_time']);
    }

     /**
     * 关联Merchant模型
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'mer_id', 'mer_id');
    }

    /**
     * 获取商家后台结算单列表
     */
    public function getList($where,$field,$order,$pageSize=0)
    {
        $data = $this->alias('a')
            ->join('life_tools_distribution_user b' ,'a.user_id = b.user_id')
            ->join('user c' ,'b.uid = c.uid')
            ->where($where)
            ->field($field)
            ->order($order);
        if($pageSize){
            $data = $data->paginate($pageSize)->toArray();
        }else{
            $data = $data->select();
        }
        return $data;
    }
}