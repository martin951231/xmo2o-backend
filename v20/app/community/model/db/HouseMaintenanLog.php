<?php
/**
 * @author : liukezhu
 * @date : 2021/11/1
 */
namespace app\community\model\db;

use think\Model;
use think\Db;
class HouseMaintenanLog extends Model{

    public function getList($where,$field=true,$order='id DESC',$page=0,$limit=10) {
        $db_list = $this->field($field)->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取单个数据信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true,$order='id DESC'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
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
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @paramarray $data
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

}