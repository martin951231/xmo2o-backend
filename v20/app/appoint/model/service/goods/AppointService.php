<?php
/**
 * 预约商品
 * Author: 衡婷妹
 * Date Time: 2021/04/28
 */

namespace app\appoint\model\service\goods;

use app\appoint\model\db\Appoint;
use think\facade\Db;

class AppointService
{
    public $appointModel = null;

    public function __construct()
    {
        $this->appointModel = new Appoint();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->appointModel->insertGetId($data);
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
            $result = $this->appointModel->insertAll($data);
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

        $result = $this->appointModel->where($where)->update($data);
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

        $result = $this->appointModel->getOne($where, $order);
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
        $result = $this->appointModel->getSome($where, $field, $order, $start, $limit);
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
        $count = $this->appointModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}