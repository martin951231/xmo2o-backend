<?php
/**
 * 企业微信内容引擎内容相关
 */
namespace app\community\model\db;
use think\Model;
use think\facade\Db;
class VillageQywxEngineContent extends Model
{
    /**
     * Notes:获取全部
     * @datetime: 2021/3/12 10:01
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     */
    public function getList($where,$field='c.*',$order='c.id desc',$page=0,$limit=10,$whereRaw='')
    {
        if ($whereRaw) {
            $sql = $this->alias('c')->leftJoin('village_qywx_engine_group g','c.gid=g.id')
                ->whereRaw($whereRaw)->field($field)->order($order);
        } else {
            $sql = $this->alias('c')->leftJoin('village_qywx_engine_group g','c.gid=g.id')
                ->where($where)->field($field)->order($order);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes:对应查询列表方法获取数量
     * @param $where
     * @param string $where_or
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/22 13:58
     */
    public function getListCount($where,$whereRaw=[])
    {
        if ($whereRaw) {
            $count = $this->alias('c')->leftJoin('village_qywx_engine_group g','c.gid=g.id')
                ->whereRaw($whereRaw)->count();
        } else {
            $count = $this->alias('c')->leftJoin('village_qywx_engine_group g','c.gid=g.id')
                ->where($where)->count();
        }
        return $count;
    }

    /**
     * Notes:获取一条
     * @datetime: 2021/3/12 10:01
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes:添加一条数据
     * @datetime: 2021/3/12 10:00
     * @param $data
     * @return int|string
     */
    public function addFind($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @datetime: 2021/3/12 10:00
     * @param $where
     * @param $data
     * @return VillageQywxEngineContent
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
    public function getSum($where,$column)
    {
        $sum = $this->where($where)->sum($column);
        return $sum;
    }
    /**
     * Notes: 硬删除
     * @datetime: 2021/3/12 10:01
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delData($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}
