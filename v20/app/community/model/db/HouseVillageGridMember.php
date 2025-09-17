<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageGridMember extends Model
{
    /**
     * 获取网格员列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/12/19
     */
    public function getList($where, $field = true, $page = 1, $limit = 15, $order = 'id DESC')
    {
        if($page)
            $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 获取网格员数量
     * @param $where
     * @return int
     * @author lijie
     * @date_time 2020/12/19
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    /**
     * 添加网格员
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/12/19
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 更新网格员
     * @param $where
     * @param $data
     * @return bool
     * @author lijie
     * @date_time 2020/12/19
     */
    public function saveOne($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 查看网格员详情
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where, $field = true,$order='id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * 删除网格员
     * @author lijie
     * @date_time 2021/02/22
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }


    public function getLists($where,$field='*',$order='g.id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('g')
            ->leftJoin('area_street_workers w','w.worker_id = g.workers_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCounts($where)
    {
        $sql = $this->alias('g')
            ->leftJoin('area_street_workers w','w.worker_id = g.workers_id')
            ->where($where)->count();
        return $sql;
    }

    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }


    public function getGridMember($where,$field='*',$order='g.id desc')
    {
        $list = $this->alias('g')
            ->leftJoin('house_worker w','w.phone = g.phone')
            ->leftJoin('wisdom_qrcode_person p','p.uid = w.wid')
            ->leftJoin('wisdom_qrcode q','q.cate_id = p.cate_id')
            ->where($where)->field($field)->group('g.phone,w.wid,q.cate_id')->order($order)->page(1,100)->select();
        return $list;
    }


    public function getGridMemberWorkers($where,$field='*',$order='g.id desc')
    {
        $list = $this->alias('g')
            ->leftJoin('area_street_workers w','w.worker_id = g.workers_id')
            ->where($where)->field($field)->order($order)->find();
        return $list;
    }

}