<?php
/**
 * 团购频道页子分类页热搜词service
 * Author: 钱大双
 * Date Time: 2021年1月25日13:48:46
 */

namespace app\group\model\service;

use app\group\model\db\GroupSearchHot as GroupSearchHotModel;

class GroupSearchHotService
{
    public $groupSearchHotModel = null;

    public function __construct()
    {
        $this->groupSearchHotModel = new GroupSearchHotModel();
    }

    /**获得团购频道页子分类页热搜词列表
     * @param $param
     */
    public function getList($param)
    {
        $cat_id = $param['cat_id'] ?? 0;

        // 排序
        $order = [
            'sort' => 'DESC',
        ];

        $where = [];

        $where[] = ['cat_id', '=', $cat_id];

        $list = $this->getSome($where, true, $order);

        $returnArr = [];
        $returnArr['list'] = $list;
        return $returnArr;
    }

    /**编辑团购频道页子分类页热搜词
     * @param array $param
     * @return string
     */
    public function addGroupSearchHot($param = [])
    {
        $id = $param['id'] ?? 0;
        $name = $param['name'] ?? '';
        $cat_id = $param['cat_id'] ?? 0;
        $sort = $param['sort'] ?? 0;

        $data = [];
        $data['name'] = $name;
        $data['sort'] = $sort;

        if ($id > 0) {//编辑
            $where = [
                ['name', '=', $name],
                ['cat_id', '=', $cat_id],
                ['id', '<>', $id],
            ];
            $count = $this->getCount($where);
            if ($count > 0) {
                throw new \think\Exception('热搜词已存在');
            }
            $where = ['id' => $id];
            $rs = $this->updateThis($where, $data);
            if ($rs) {
                $msg = '编辑成功';
            } else {
                $msg = '编辑失败';
            }
        } else {
            //新增
            $where = [
                ['name', '=', $name],
                ['cat_id', '=', $cat_id],
            ];
            $count = $this->getCount($where);
            if ($count > 0) {
                throw new \think\Exception('热搜词已存在');
            }
            $data['cat_id'] = $cat_id;
            $data['add_time'] = time();
            $rs = $this->add($data);
            if ($rs) {
                $msg = '添加成功';
            } else {
                $msg = '添加失败';
            }
        }
        return $msg;
    }

    /**删除团购频道页子分类页热搜词
     * @param array $param
     * @return string
     */
    public function delSearchHot($param = [])
    {
        $id = $param['id'] ?? 0;
        $where = ['id' => $id];
        $this->groupSearchHotModel->del($where);
        return true;
    }

    /**团购频道页子分类页热搜词基本信息
     * @param array $param
     * @return string
     */
    public function getInfo($param = [])
    {
        $id = $param['id'] ?? 0;
        $where = ['id' => $id];
        $info = $this->getOne($where);
        $returnArr['detail'] = $info;
        return $returnArr;
    }

    /**编辑排序
     * @param $data
     */
    public function saveSort($param)
    {
        $id = $param['id'] ?? 0;
        $sort = $param['sort'] ?? 0;
        $where = ['id' => $id];
        $data = ['sort' => $sort];
        $res = $this->updateThis($where, $data);
        return $res;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupSearchHotModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }


    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupSearchHotModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        try {
            $result = $this->groupSearchHotModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupSearchHotModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->groupSearchHotModel->getSome($where, $field, $order, $page, $limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**获取总数
     * @param $where
     * @return mixed
     */
    public function getCount($where)
    {
        $count = $this->groupSearchHotModel->getCount($where);
        return $count;
    }
}