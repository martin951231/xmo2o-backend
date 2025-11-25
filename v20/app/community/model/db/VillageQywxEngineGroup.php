<?php
/**
 * 企业微信内容引擎组相关
 */
namespace app\community\model\db;
use think\Model;
use think\facade\Db;
class VillageQywxEngineGroup extends Model
{
    /**
     * Notes: 获取全部
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @datetime: 2021/3/11 9:52
     * @return \think\Collection
     */
    public function getList($where,$field=true,$order='id desc',$page=0,$limit=10,$where_or='')
    {
        if (empty($where) && $where_or) {
            $sql = $this->whereRaw($where_or)->field($field)->order($order);
        } else {
            $sql = $this->where($where)->where($where_or)->field($field)->order($order);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes: 获取一条
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @datetime: 2021/3/11 10:14
     */
    public function getFind($where,$field=true,$order='id desc',$whereRaw='')
    {
        if (!empty($whereRaw)) {
            $info = $this->whereRaw($whereRaw)->field($field)->order($order)->find();
        } else {
            $info = $this->where($where)->field($field)->order($order)->find();
        }
        return $info;
    }

    /**
     * Notes: 添加一条数据
     * @param $data
     * @return int|string
     * @datetime: 2021/3/11 10:15
     */
    public function addFind($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return VillageQywxEngineGroup
     * @datetime: 2021/3/11 10:15
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
    public function getSum($where,$column,$whereRaw='')
    {
        if (!empty($whereRaw)) {
            $sum = $this->whereRaw($whereRaw)->sum($column);
        } else {
            $sum = $this->where($where)->sum($column);
        }
        return $sum;
    }
    public function getColumn($where,$column)
    {
        $sum = $this->where($where)->column($column);
        return $sum;
    }
    /**
     * Notes: 硬删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @datetime: 2021/3/11 10:15
     */
    public function delData($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 更新数量
     * @param $gid
     * @return VillageQywxEngineGroup
     * @author: wanzy
     * @date_time: 2021/4/26 13:41
     */
    public function updateNum($gid) {
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $where = [];
        $where[] = ['gid', '=', $gid];
        $where[] = ['status', '=', 0];
        $count_num = $dbVillageQywxEngineContent->where($where)->count();
        if (!$count_num) $count_num = 0;
        $where_group = [];
        $where_group[] = ['id', '=', $gid];
        $data  = [
            'number' => $count_num,
        ];
        $set = $this->editFind($where_group, $data);
        return $set;
    }
}
