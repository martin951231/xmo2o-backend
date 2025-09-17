<?php
/**
 * 小区物业
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 15:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseProperty extends Model{

    /**
     * 获取单个物业数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:48
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true,$orderby='id desc'){
        $info = $this->field($field)->where($where)->order($orderby)->find();
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
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 插入数据并获取插入id
     * @author : weili
     * @datetime: 2020/7/7 9:52
     * @paramarray $data
    **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

    public function getPropertyBindVillage($where,$field =true) {
        $data=$this->alias('hp')
            ->leftJoin('house_village hv','hp.id=hv.property_id')
            ->leftJoin('house_village_user_vacancy hvu','hv.village_id=hvu.village_id')
            ->where($where)->count();
        return $data;
    }

    public function getColumn($where,$field, $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}
