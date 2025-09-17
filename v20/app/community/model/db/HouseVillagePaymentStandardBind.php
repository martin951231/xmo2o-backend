<?php
/**
 * 缴费相关绑定信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 16:43
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillagePaymentStandardBind extends Model{

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/4/27 20:25
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 缴费相关绑定信息列表
     * @author: wanziyang
     * @date_time: 2020/4/27 16:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field = true,$order='bind_id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/27 20:40
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取自定义缴费费用
     * @author lijie
     * @date_time 2020/08/17 10:50
     * @param $where
     * @return mixed
     */
    public function getSum($where)
    {
        $sum_money = $this->alias('a')
            ->leftJoin('house_village_payment_standard s','a.standard_id= s.standard_id')
            ->where($where)
            ->sum('s.pay_money');
        return $sum_money;
    }

    public function incPaidCycle($where,$cycle)
    {
        $res = $this->where($where)->setInc('paid_cycle',$cycle);
        return $res;
    }

    /**
     * 用户自定义缴费项
     * @author lijie
     * @date_time 2020/11/10
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function paymentList($where,$field=true)
    {
        $data = $this->alias('psb')
            ->leftJoin('house_village_payment_standard ps','ps.standard_id = psb.standard_id')
            ->leftJoin('house_village_payment p','p.payment_id = psb.payment_id')
            ->leftJoin('house_village_user_bind hvb','hvb.pigcms_id = psb.pigcms_id')
            ->field($field)
            ->where($where)
            ->select();
        return $data;
    }
    
    public function getList($where,$whereRaw,$field=true){
        $data = $this->alias('psb')
            ->leftJoin('house_village_payment_standard ps','ps.standard_id = psb.standard_id')
            ->leftJoin('house_village_payment p','p.payment_id = psb.payment_id')
            ->field($field)
            ->where($where)
            ->whereRaw($whereRaw)
            ->select()->toArray();
        return $data;
    }
}