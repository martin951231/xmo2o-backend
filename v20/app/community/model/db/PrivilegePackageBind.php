<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/6 16:36
 */

namespace app\community\model\db;


use think\Model;

class PrivilegePackageBind extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 删除符合条件的信息
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2020/8/6 17:08
     */
    public function delInfo($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 获取关联信息
     * @param $where
     * @param string $field
     * @return mixed
     * @author: wanzy
     * @date_time: 2020/8/6 17:25
     */
    public function getBindInfo($where,$field ='a.*',$order='b.content_sort desc,b.content_id asc') {
        $list = $this->alias('a')
            ->leftjoin('privilege_package_content b', 'a.content_id=b.content_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param array $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/11 15:03
     */
    public function getCount($where = [])
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 获取某个字段值
     * @param $where
     * @param $field
     * @return array
     * @author: weili
     * @datetime: 2020/8/24 15:34
     */
    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }
}