<?php
/**
 * @author : liukezhu
 * @date : 2022/8/4
 */

namespace app\community\model\db;

use think\Model;

class PluginMaterialDiyTemplate extends Model
{


    public function getOne($where, $field, $order = 'template_id desc')
    {
        return $this->field($field)->where($where)->order($order)->find();
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where, $save)
    {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    public function getList($where, $field = '*', $order = 'template_id desc', $page = 0, $limit = 10)
    {
        $sql = $this->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }


    /**
     * 获取查询条件下的总条数
     * @param $where
     * @return int
     */

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function updatePluginMaterialDiyTemplate($where, $data)
    {
        return $this->where($where)->save($data);
    }

    /**
     * 插入数据
     * @param $data
     * @return int|string
     */
    public function addPluginMaterialDiyTemplate($data)
    {
        return $this->insert($data);
    }

}