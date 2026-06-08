<?php

namespace app\social_activity\model\db;

use think\Model;

class SocialActivityCategory extends Model
{
    /**
     * 获取分类列表
     */
    public function getCategoryList($where = [], $field = '*', $order = 'id DESC')
    {
        return $this->where($where)->field($field)->order($order)->select();
    }

    /**
     * 根据ID获取分类
     */
    public function getCategoryById($id)
    {
        return $this->where(['id' => $id])->find();
    }

    /**
     * 添加分类
     */
    public function addCategory($data)
    {
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        return $this->save($data);
    }

    /**
     * 更新分类
     */
    public function updateCategory($id, $data)
    {
        $data['update_time'] = date('Y-m-d H:i:s');
        return $this->where(['id' => $id])->update($data);
    }

    /**
     * 删除分类
     */
    public function deleteCategory($id)
    {
        return $this->where(['id' => $id])->delete();
    }
}