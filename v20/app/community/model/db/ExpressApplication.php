<?php


namespace app\community\model\db;

use think\Model;

class ExpressApplication extends Model
{
    /**
     * 获取申请详情
     * @param $where
     * @param bool $field
     * @return mixed
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function getOne($where, $field = true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 获取申请列表
     * @param $where
     * @param bool $field
     * @return mixed
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function getList($where, $field, $order, $page, $limit)
    {
        $sql = $this->alias('p')
            ->leftJoin('express_pick_up_location e', 'p.location_id = e.id')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        //Cache::tag($this->cacheTag)->set($cachKey,$list,7200-mt_rand(10,30));
        return $list;
    }

    /**
     * 查询总数
     * @param array $data
     * @return int|string
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function getCount($where)
    {
        $count = $this->alias('p')
            ->leftJoin('express_pick_up_location e', 'p.location_id = e.id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 查询总数2
     * @param array $data
     * @return int|string
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function cc($where)
    {
        $count = $this
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 添加申请
     * @param array $data
     * @return int|string
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function addOne($data = [])
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * 修改申请
     * @param array $where
     * @param array $data
     * @return bool
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function saveOne($where = [], $data = [])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 修改申请
     * @param array $where
     * @param array $data
     * @return bool
     * @author mashuaifei
     * @date_time 2025/09/17
     */
    public function updateAll($where = [], $data = [])
    {
        if (empty($where) || empty($data)) {
            return false; // 条件或数据为空时返回失败
        }
        $res = $this->where($where)->save($data);
        return $res;
    }

}