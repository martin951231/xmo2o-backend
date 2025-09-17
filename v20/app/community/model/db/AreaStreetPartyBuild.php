<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetPartyBuild extends Model
{
    /**
     * 获取党建资讯列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=20,$order='is_hot,build_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        return $data;
    }

    /**
     * 党建资讯详情
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 编辑党内资讯
     * @author lijie
     * @date_time 2020/10/15
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
     * 添加党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 删除党内资讯
     * @author lijie
     * @date_time 2020/10/15
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
     * 增加党建资讯阅读量
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @return mixed
     */
    public function incReadNum($where)
    {
        $res = $this->where($where)->inc('read_sum',1)->update();
        return $res;
    }

    /**
     * 获取党建资讯数量
     * @author lijie
     * @date_time 2020/10/14
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}