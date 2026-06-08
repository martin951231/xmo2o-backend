<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityCategory;

class SocialActivityCategoryService
{
    /**
     * 获取分类列表
     * @param $param
     * @return array
     */
    public function getCategoryList($param)
    {
        $field = 'id, name, create_time, creator, update_time, modifier';
        $where = [];

        $list = (new SocialActivityCategory())
            ->where($where)
            ->field($field)
            ->order('id DESC')
            ->paginate($param['pageSize'] ?? 10)->toArray();

        return $list;
    }

    /**
     * 获取分类详情
     * @param $param
     * @return array
     */
    public function getCategoryDetail($param)
    {
        if (empty($param['id'])) {
            throw new \Exception('分类ID不能为空');
        }

        $info = (new SocialActivityCategory())->getCategoryById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw new \Exception('分类不存在或已被删除');
        }

        return $info->toArray();
    }

    /**
     * 验证分类数据
     * @param $data
     * @throws \Exception
     */
    protected function validateCategoryData($data)
    {
        if (empty($data['name'])) {
            throw new \Exception('分类名称不能为空');
        }

        // 检查名称是否重复
        $where = [
            ['name', '=', $data['name']],
        ];

        if (!empty($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }

        $count = (new SocialActivityCategory())->where($where)->count();
        if ($count > 0) {
            throw new \Exception('分类名称已存在');
        }
    }

    /**
     * 保存分类（新增/编辑）
     * @param $data
     * @param $id
     * @return int
     */
    public function saveCategory($data, $id = 0)
    {
        $this->validateCategoryData($data);

        $saveData = [
            'name' => $data['name'],
            'modifier' => $data['modifier'] ?? '',
            'modifier_uid' => $data['modifier_uid'] ?? 0,
        ];

        if ($id > 0) {
            // 编辑
            (new SocialActivityCategory())->updateCategory($id, $saveData);
            return $id;
        } else {
            // 新增
            $saveData['creator'] = $data['creator'] ?? '';
            $saveData['creator_uid'] = $data['creator_uid'] ?? '';
            return (new SocialActivityCategory())->addCategory($saveData);
        }
    }

    /**
     * 删除分类
     * @param $param
     */
    public function deleteCategory($param)
    {
        if (empty($param['id'])) {
            throw new \Exception('分类ID不能为空');
        }

        $info = (new SocialActivityCategory())->getCategoryById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw new \Exception('分类不存在或已被删除');
        }

        (new SocialActivityCategory())->deleteCategory($param['id']);
    }
}