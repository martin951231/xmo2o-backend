<?php
/**
 * SearchHotMallNewService.php
 * 商城3.0 热搜词 service
 * Create on 2020/10/16 13:04
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\SearchHotMallNew;

class SearchHotMallNewService
{
    public function __construct()
    {
        $this->searchHotNewModel = new SearchHotMallNew();
    }

    /**
     * 获取热搜列表
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getSearchHotList($page, $pageSize)
    {
        $list = $this->searchHotNewModel->getSearchHotList($page, $pageSize);
        if (!empty($list)) {
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 编辑或添加热搜词
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addOrEditSearchHot($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (!empty($param['id'])) {
            //编辑
            $where = ['id' => $param['id']];
            unset($param['id']);
            $data = $param;
            $res = $this->searchHotNewModel->EditSearchHot($where, $data);
        } else {
            unset($param['id']);
            $data = $param;
            $res = $this->searchHotNewModel->addSearchHot($data);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 获取编辑
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getEditSearchHot($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $arr = $this->searchHotNewModel->getByCondition(true, ['id' => $id])[0];
        return $arr;
    }

    /**
     * 删除热搜词
     * @param $ids
     * @return bool
     * @throws \think\Exception
     */
    public function delSearchHot($ids)
    {
        if (empty($ids)) {
            throw new \think\Exception('缺少id参数');
        }
        foreach ($ids as $id) {
            $res = $this->searchHotNewModel->where(['id' => $id])->delete();
            if ($res === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        return true;
    }

    /**
     * 保存排序
     * @param $sort
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function saveSort($sort, $id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $data = ['sort' => $sort];
        $res = $this->searchHotNewModel->EditSearchHot($where, $data);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 首页搜索热词
     * @return array
     */
    public function getIndexHotSearch()
    {
        $where = ['is_first' => 1];
        $arr = $this->searchHotNewModel->getByCondition('id,name,url,hottest,type', $where);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     *商品搜索页-搜索发现
     * @return array
     */
    public function getSearchFind()
    {
        $arr = $this->searchHotNewModel->getByCondition('id,name,url,hottest,type', []);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取热搜top
     * @return array
     */
    public function getHotRecord($start_time, $end_time)
    {
        $where = [];
        if (!empty($start_time)) {
            array_push($where, ['create_time', '>=', strtotime($start_time)]);
        }
        if (!empty($end_time)) {
            array_push($where, ['create_time', '<=', strtotime($end_time)]);
        }
        $arr = (new MallSearchLogService())->getHotRecord($where);
        return $arr;
    }
}