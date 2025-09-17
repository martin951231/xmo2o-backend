<?php
class House_propertyModel extends Model{

    /**
     * @var string 关联手机号类型 login 登录
     * 目前用于表 work_weixin_phone 中 type
     */
    const WORK_WEI_XIN_PHONE_LOGIN_TYPE       = 'login';
    /**
     * @var string 记录下关联表 拿表名当类别 house_property 物业信息表
     * 目前用于表 work_weixin_phone 中 from_table
     */
    const WORK_WEI_XIN_PHONE_FROM_PROPERTY    = 'house_property';
    /**
     * @var string 记录下关联表 拿表名当类别 area_street 街道、社列表信息表
     * 目前用于表 work_weixin_phone 中 from_table
     */
    const WORK_WEI_XIN_PHONE_FROM_AREA_STREET = 'area_street';
    
    public function get_room_limit_num($id){

        // 考虑到禁止也有可能开启所以禁止状态也计算数量
        $where = array(
            'hp.id' => $id,
            'hv.status' => array('in', array(1,2)),
            'hvu.status' => array('in', array(0,1,3)),
            'hvu.is_del' => 0,
        );
        $field = 'count(hvu.pigcms_id) as room_num';

        $limit_info = $this->join('as hp LEFT JOIN '.C('DB_PREFIX').'house_village as hv ON hp.id=hv.property_id LEFT JOIN '.C('DB_PREFIX').'house_village_user_vacancy as hvu ON hv.village_id=hvu.village_id')
                     ->where($where)->distinct('hvu.pigcms_id')->field($field)->find();

        if (empty($limit_info)) {
            $limit_info['room_num'] = 0;
        }
        $room_limit_num = $this->where(array('id'=>$id))->getField('room_limit_num');
        $limit_info['room_limit_num'] = $room_limit_num;
        if ($limit_info['room_num']>0) {
            $limit_info['surplus_room_num'] = intval($room_limit_num) - intval($limit_info['room_num']);
        } else {
            $limit_info['surplus_room_num'] = intval($room_limit_num);
        }
        return $limit_info;
    }

    //新创建的物业默认启用新版收费
    public function addNewCharge($property_id){
        if(!$property_id){
            return true;
        }
        $house_new_charge_time=M('House_new_charge_time');
        $time=time();
        $data=$house_new_charge_time->where(['property_id'=>$property_id])->getField('id');
        if($data){
            return true;
        }
        $arr=[
            'property_id'=>$property_id,
            'take_effect_time'=>strtotime(date('Y-m-d 00:00:00',$time)),
            'add_time'=>$time,
            'update_time'=>$time
        ];
        $house_new_charge_time->data($arr)->add();
        return true;
    }

    // 物业都强制走新版收费
    public function propertyAllToNewCharge()
    {
        $where = [
            'status' => ['in', [0, 1]],
        ];
        $nowTime = time();
        $house_new_charge_time = M('House_new_charge_time');
        $where_charge = [
            'take_effect_time' => ['lt', $nowTime]
        ];
        $notPropertyIds =  $house_new_charge_time->where($where_charge)->getField('property_id', true);
        if (!empty($notPropertyIds)) {
            $where['id'] = ['not in', $notPropertyIds];
        }
        $propertyList = $this->where($where)->field('id,property_name')->select();
        $setArr = [];
        $take_effect_time = strtotime(date('Y-m-d 00:00:07', $nowTime));
        foreach ($propertyList as $property) {
            $setId = $house_new_charge_time->where(['property_id' => $property['id']])->getField('id');
            $arr = [
                'property_id' => $property['id'],
                'take_effect_time' => $take_effect_time,
                'update_time' => $nowTime
            ];
            if (!$setId) {
                $arr['add_time'] = $nowTime;
                $house_new_charge_time->data($arr)->add();
            } else {
                $house_new_charge_time->where(['id' => $setId])->data($arr)->save();
            }
            $setArr[] = $arr;
        }
        return $setArr;
    }
}
?>