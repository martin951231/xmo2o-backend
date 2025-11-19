<?php
/**
 * 商家优惠券
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class CardNewCoupon extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function get_coupon_list_by_ids($ids)
    {
        $where = [['coupon_id', 'in', $ids]];
        $res = $this->field('coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new')->where($where)->select();
        if (!empty($res)) {
            return $res->toArray();
        } else {
            return [];
        }
    }
    /**
     * 使用类别描述
     * @return array
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getCatName()
    {
        return [
            'all' => '通用券',
            'wxapp' => '微信营销券',
            'new_user' => '新人券',
            'group' => '团购券',
            'meal' => '餐饮券',
            'appoint' => '预约券',
            'shop' => '外卖券',
            'mall' => '商城券',
            'village_group' => '社区拼团券',
            'store' => '快速买单券'
        ];
    }
}