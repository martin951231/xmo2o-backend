<?php
/**
 * 团购套餐
 * Author: 衡婷妹
 * Date Time: 2021/04/28 15:41
 */

namespace app\group\model\service;

use app\group\model\db\GroupPackages;
use Exception;

class GroupPackagesService
{
    public $groupPackagesModel = null;

    public function __construct()
    {
        $this->groupPackagesModel = new GroupPackages();
    }
    
    /**
     *返回树状结构
     * @param $where array
     * @return array
     */
    public function getList($merId)
    {
        // 获得所有
        $where = [
            'mer_id' => $merId,
        ];
        $list = $this->getSome($where,true,['id'=>'DESC']);

        // 返回数组
        $returnArr = [];
        $returnArr['list'] = [];
        foreach ($list as $key => $val) {
            $temp = [
                'id' => $val['id'],
                'title' => $val['title'],
            ];
            $returnArr['list'][$key] = $temp;
        }
        
        return $returnArr;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupPackagesModel->insertGetId($data);
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
            $result = $this->groupPackagesModel->insertAll($data);
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

        $result = $this->groupPackagesModel->where($where)->update($data);
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

        $result = $this->groupPackagesModel->getOne($where, $order);
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
        $start = ($page-1)*$limit;
        $result = $this->groupPackagesModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->groupPackagesModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }


    public function groupPackageLists($params, $sort = 'id DESC')
    {
        $where = [];
        if (isset($params['mer_id'])) {
            $where['mer_id'] = intval($params['mer_id']);
        }

        $total = $this->groupPackagesModel->where($where)->count();
        if ($total > 0) {
            $lists = $this->groupPackagesModel->where($where)->page($params['page'], $params['pageSize'])->order($sort)->select()->toArray();
        } else {
            $lists = [];
        }
        return ['list' => $lists, 'total' => $total];
    }

    public function  showGroupPackage($id, $merId)
    {
        return $this->groupPackagesModel->getOne(['mer_id' => $merId, 'id' => $id]);
    }

    public function saveGroupPackage($merId, $params)
    {
        $where = [['mer_id', '=', $merId]];
        $id = $params['id'] ?? 0;
        unset($params['id']);
        if (!isset($params['title']) || empty($params['title'])) {
            throw new Exception(L_('套餐名称不能为空'));
        }

        if ($id) {
            $where[] = ['id', '=', $id];
            return $this->groupPackagesModel->updateThis($where, $params);
        } else {
            $params['mer_id'] = $merId;
            return $this->groupPackagesModel->add($params);
        }
    }

    public function delGroupPackage($merId, $ids)
    {
        if (empty($merId) || empty($ids)) {
            throw new Exception(L_('参数有误'));
        }

        $this->groupPackagesModel->where('mer_id', $merId)->whereIn('id', $ids)->delete();
        (new \app\group\model\db\Group())->where('mer_id', $merId)->whereIn('packageid', $ids)->update(['packageid' => 0, 'tagname' => '']);
        return true;
    }

    public function delPackageBindGroup($where)
    {
        return (new \app\group\model\db\Group())->where($where)->update(['packageid' => 0, 'tagname' => '']);
    }
}