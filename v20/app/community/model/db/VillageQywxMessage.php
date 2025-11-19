<?php


namespace app\community\model\db;

use think\Model;

class VillageQywxMessage extends Model
{
    /**
     * 企业微信群发详情
     * @author lijie
     * @date_time 2021/03/12
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 企业微信群发列表
     * @author lijie
     * @date_time 2021/03/12
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * 修改企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 添加企业微信群发
     * @author lijie
     * @date_time 2021/03/20
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 删除企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * 企业微信群发数量
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}