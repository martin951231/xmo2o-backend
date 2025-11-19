<?php
/**
 * 外卖订单详情推荐商品组
 */

namespace app\shop\model\db;
use think\Model;
use think\facade\Env;
class ShopRecommend extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function getList($where,$field=true,$pageSize=0)
    {
        $data = $this->where($where)->field($field)->order('id desc')->paginate($pageSize)->toArray();
        return $data;
    }
    public function getDetail($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        $data = $data ? $data->toArray() : $data;
        return $data;
    }
}