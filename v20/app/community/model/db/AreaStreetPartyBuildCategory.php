<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetPartyBuildCategory extends Model
{
    /**
     * 获取党建资讯分类列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$order='cat_sort DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 编辑党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
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
     * 党内资讯详情
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param bool $filed
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$filed=true)
    {
        $data = $this->where($where)->field($filed)->find();
        return $data;
    }

    /**
     * 添加党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 删除党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
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
     * 获取党建资讯分类数量
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


    /**
     * 统计最多的前五条分类
     * @author: liukezhu
     * @date : 2022/5/10
     * @param $where
     * @param string $group
     * @param string $order
     * @param bool $field
     * @return mixed
     */
    public function getListByGroup($where,$group='c.cat_id',$order='c.cat_id  desc',$field=true,$limit=5)
    {
        return $this->alias('c')->rightJoin('area_street_party_build t','t.cat_id=c.cat_id')->where($where)->field($field)->group($group)->order($order)->limit($limit)->select();
    }
}