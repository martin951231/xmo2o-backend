<?php
/**
 * 社区表格
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/23 11:23
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillage extends Model{

    /**
     * 获取单个社区数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 11:23
     * @param int $village_id 社区id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($village_id,$field =true){
        $info = $this->field($field)->where(array('village_id'=>$village_id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/24 14:39
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取小区列表
     * @author: wanziyang
     * @date_time: 2020/4/24 15:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return mixed
     */
    public function getList($where, $field = true, $page = 1, $limit = 20, $order = 'village_id DESC', $type = 0)
    {
        $list = $this->field($field)->where($where)->order($order);
        if ($type) {
            $listdata = $list->page($page, $limit)->select();
        } else {
            $listdata = $list->select();
        }
        return $listdata;
    }
    public function getVillageList($where,$field=true,$page=0,$limit=20,$order='village_id DESC') {
        $list = $this->field($field)->where($where)->order($order);
        if($page>0){
            $listdata = $list->page($page,$limit)->select();
        }else{
            $listdata = $list->select();
        }
        return $listdata;
    }
    /**
     * 获取小区列表
     * @author: zhubaodi
     * @date_time: 2021/4/9 15:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getMeterList($where,$field=true,$page=0,$limit=20,$order='village_id DESC',$type=0) {
        $list = $this->alias('a')
            ->leftJoin('house_meter_admin_village b', 'b.village_id != a.village_id')
            ->where($where)
            ->field($field);
       //  $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
    /**
     * 添加数据
     * @author weili
     * @datetime 2020/7/7 10:43
     * @param array $data
     * @return integer
    **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
    /**
     * 获取小区单个数据
     * @author weili
     * @datetime 2020/7/7 13:13
     * @param array $where
     * @param bool $field
     * @return array
    **/
    public function getInfo($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }


    /**
     * Notes: 获取对应的数量
     * @param $where
     * @param string $group
     * @return int
     * @author: weili
     * @datetime: 2020/10/13 17:48
     */
    public function getNum($where,$group='')
    {
        $sql = $this->where($where);
        if($group){
            $num = $sql->group($group)->count();
        }else{
            $num = $sql->count();
        }
        return $num;
    }



    /**
     * Notes: 获取对应的数量
     * @param $where
     * @param string $group
     * @return int
     * @author: zhubaodi
     * @datetime: 2020/10/13 17:48
     */
    public function getNum1($where,$group='')
    {
        $sql = $this->alias('a')
            ->leftJoin('house_meter_admin_village b', 'b.village_id != a.village_id')
            ->where($where);
        // $sql = $this->where($where);
        if($group){
            $num = $sql->group($group)->count();
        }else{
            $num = $sql->count();
        }
        return $num;
    }
    /**
     * Notes:获取某个字段
     * @param $where
     * @param string $column 字段名 多个字段用逗号分隔
     * @param string $key   索引
     * @return array
     * @author: weili
     * @datetime: 2020/10/13 18:36
     */
    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }

    /**
     * 计算小区区域数量
     * @author lijie
     * @date_time 2020/12/04
     * @param $where
     * @return int
     */
    public function getAreaNum($where)
    {
        $count = $this->where($where)->count('distinct city_id');
        return $count;
    }

    /**
     * 获取小区下的所有区域
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @param bool $field
     * @param string $group
     * @return mixed
     */
    public function getVillageArea($where ,$field=true,$group='v.area_id')
    {
        $data = $this->alias('v')
            ->leftJoin('area a','a.area_id = v.city_id')
            ->where($where)
            ->field($field)
            ->group($group)
            ->select();
        return $data;
    }

    /**
     * 查询物业下用户所有相关小区信息列表
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     */
    public function getVillageBindList($where,$field,$page = 0,$limit = 10,$order='v.village_id DESC'){
        $sql = $this->alias('v')
            ->leftJoin('house_village_user_bind b','v.village_id = b.village_id')
            ->field($field)
            ->where($where)
            ->group('v.village_id');
        $count_sql = $sql;
        $count = $count_sql->count();
        if($page){
            $data = $sql->page($page,$limit)
                ->order($order)
                ->select();
        }else{
            $data = $sql->order($order)
                ->select();
        }
        if($data && !$data->isEmpty()){
            return ['list' => $data->toArray(),'count' => $count];
        }else{
            return [];
        }
    }


    /**
     * 统计是否虚拟物业的小区
     * @author: liukezhu
     * @date : 2022/5/7
     * @param $where
     * @return mixed
     */
    public function getIsVirtualProperty($where)
    {
        $sql = $this->alias('a')
            ->leftJoin('house_property b', 'b.id = a.property_id')
            ->where($where)->count();
        return $sql;
    }

    public function getOneOrder($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    public function getOneByConfigOrder($where = [], $field = true, $order = []) {
        $result = $this->alias('a')
            ->leftJoin('house_village_config b', 'b.village_id = a.village_id')
            ->field($field)->order($order)
            ->where($where)->find();
        return $result;
    }
    public function getOneByConfigList($where = [], $field = true, $order = [],$page=0,$limit=20) {
        $list = $this->alias('a')
            ->leftJoin('house_village_config b', 'b.village_id = a.village_id')
            ->field($field)->order($order)->where($where);
        if($page) {
            $result = $list->page($page, $limit)->select();
        }else {
            $result = $list->select();
        }
        return $result;
    }

    //更新字段值 加
    public function updateFieldPlusNum($whereArr=array(),$fieldname='',$fieldv=1)
    {
        if(empty($whereArr) || empty($fieldname)){
            return false;
        }
        $ret = $this->where($whereArr)->inc($fieldname,$fieldv)->update();
        return $ret;
    }

    //更新字段数值 减
    public function updateFieldMinusNum($whereArr=array(),$fieldname='',$fieldv=1)
    {
        if(empty($whereArr) || empty($fieldname)){
            return false;
        }
        $ret = $this->where($whereArr)->dec($fieldname,$fieldv)->update();
        return $ret;
    }
    
    /**
     * 设备绑定获取小区列表
     */
    public function getVillageLists($where,$field,$order,$pageSize)
    {
        $data = $this->where($where)->field($field)->order($order)->paginate($pageSize)->toArray();
        return $data;
    }

    public function phone_desensitization_v($phone = '', $village_id = 0, $village_info = array())
    {
        if (empty($phone)) {
            return $phone;
        }
        if ((empty($village_info) || !isset($village_info['desensitization_tel'])) && $village_id > 0) {
            $village_info = $this->field('*')->where(array('village_id' => $village_id))->find();
        }
        if (!empty($village_info) && isset($village_info['desensitization_tel']) && $village_info['desensitization_tel'] < 1) {
            //小区未开启 手机号脱敏
            return $phone;
        }
        return phone_desensitization($phone);
    }

    public function idnum_desensitization_v($id_card = '', $village_id = 0, $village_info = array())
    {
        if (empty($id_card)) {
            return $id_card;
        }
        if ((empty($village_info) || !isset($village_info['desensitization_idnum'])) && $village_id > 0) {
            $village_info = $this->field('*')->where(array('village_id' => $village_id))->find();
        }
        if (!empty($village_info) && isset($village_info['desensitization_idnum']) && $village_info['desensitization_idnum'] < 1) {
            //小区未开启 身份证号脱敏
            return $id_card;
        }
        return idnum_desensitization($id_card);
    }
}
