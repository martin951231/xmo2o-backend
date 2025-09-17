<?php
/**
 * 系统优惠券
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 17:36
 */

namespace app\common\model\db;

use think\Model;

class SystemCoupon extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取优惠券信息
     * User: chenxiang
     * Date: 2020/5/29 17:50
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getCouponData($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }

    /**
     * 更新优惠券信息
     * User: chenxiang
     * Date: 2020/6/1 15:41
     * @param array $where
     * @param string $field
     * @param string $value
     * @return SystemCoupon|bool
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->where($where)->update([$field => $value]);
        return $result;
    }

    public function getSum($where,$field){
        $data = $this->where($where)->sum($field);
        return $data;
    }

    /**
     * 更新优惠券信息
     * User: chenxiang
     * Date: 2020/6/1 15:40
     * @param array $where
     * @param bool $field
     * @return \think\Collection
     */
    public function getCouponListByIds($where = [], $field = true) {
        $result = $this->field($field)->where($where)->select();
        return $result;
    }

    /**
     * 优惠券累增
     * User: chenxiang
     * Date: 2020/6/1 19:55
     * @param array $where
     * @param string $field
     * @param string $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = '') {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
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
            'all' => '全品类通用',
            'group' => '团购',
            'meal' => '餐饮',
            'appoint' => '预约',
            'shop' => '外卖',
            'mall' => '商城',
            'village_group' => '社区拼团',
            'store' => '快速买单'
        ];
    }


}