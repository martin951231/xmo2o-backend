<?php
/**
 * 蓝牙门禁列表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/14 13:52
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageDoor extends Model{


    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/5/15 14:14
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/15 10:45
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 添加
     * @author:wanziyang
     * @date_time: 2020/5/15 10:45
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/14 13:59
     * @param array $where 查询条件
     * @param int $page 分页
     * @param string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @param integer $page_size 排序
     * @return array|null|Model
     */
    public function getList($where,$page=0,$field ='a.*,hvf.floor_name,hvf.floor_layer,hvs.single_name,hvs.id as single_id,c.public_area_name',$order='a.door_id DESC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('house_village_floor hvf', 'hvf.floor_id=a.floor_id AND hvf.village_id=a.village_id')
            ->leftJoin('house_village_single hvs', 'hvs.id=hvf.single_id')
            ->leftJoin('house_village_public_area c','a.public_area_id = c.public_area_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/14 13:59
     * @param array $where 查询条件
     * @param int $page 分页
     * @param string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @param integer $page_size 排序
     * @return array|null|Model
     */
    public function getLists($where,$field =true,$order='door_id DESC') {

        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }

    /**
     * 删除信息
     * @author: wanziyang
     * @date_time: 2020/5/14 15:57
     * @param array $where 删除内容的条件
     * @return bool
     */
    public function delOne($where) {
        $set_info = $this->where($where)->delete();
        return $set_info;
    }

    /**
     * Notes: 获取数量
     * @author: weili
     * @datetime: 2020/8/3 16:20
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    
    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}