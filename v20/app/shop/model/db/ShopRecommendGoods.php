<?php
/**
 * 外卖订单详情推荐商品组-商品
 */

namespace app\shop\model\db;
use think\Model;
use think\facade\Env;
class ShopRecommendGoods extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    public function getList($where,$field,$pageSize=0)
    {
        $data = $this->where($where)->field($field)->orderRaw('sort desc,FIELD(business,\'shop\',\'mall\',\'group\',\'grow_grass\') asc,id desc');
        if($pageSize){
            $data = $data->paginate($pageSize)->toArray();
        }else{
            $data = $data->select()->toArray();
        }
            
        return $data;
    }
    
    public function getBusinessMsg($business)
    {
        $msg = '';
        switch ($business){
            case 'shop':
                $msg = '外卖';
            break;
            case 'mall':
                $msg = '商城';
            break;
            case 'group':
                $msg = '团购';
            break;
            case 'grow_grass':
                $msg = '种草';
            break;
        }
        return $msg;
    }
    
    /**
     * 获取业务别名
     */
    public function getBusinessAlias($business)
    {
        $msg = '';
        switch ($business){
            case 'shop':
                $msg = cfg('shop_alias_name') ?: '外卖';
            break;
            case 'mall':
                $msg = cfg('mall_alias_name_new') ?:'商城';
            break;
            case 'group':
                $msg = cfg('group_alias_name') ?:'团购';
            break;
            case 'grow_grass':
                $msg = cfg('grow_grass_alias') ?:'种草';
            break;
        }
        return $msg;
    }
}