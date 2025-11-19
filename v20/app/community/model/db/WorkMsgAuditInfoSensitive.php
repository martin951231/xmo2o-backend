<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/7 17:50
 */

namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfoSensitive extends Model
{
    /**
     * 删除信息
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * 获取单个数据信息
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }



    /**
     * 获取列表
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getList($where,$page=0,$field =true,$order='id ASC',$page_size=10) {
        $db_list = $this
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * Notes:获取数量
     * @param $where
     * @return int
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     */
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 添加
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }


    /**
     * 修改
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }
}