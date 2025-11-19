<?php
/**
 * 渠道活码分类
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/12 16:31
 */
namespace app\community\model\db;
use think\Model;
use think\facade\Db;

class VillageQywxChannelGroup extends Model
{

    /**
     * Notes: 获取分类列表
     * @param $where
     * @param string $whereRaw
     * @param bool|string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/12 16:36
     */
    public function getList($where,$whereRaw='',$field=true,$order='id desc',$page=0,$limit=10)
    {
        if (!empty($whereRaw)) {
            $sql = $this->whereRaw($whereRaw)->field($field)->order($order);
        } else {
            $sql = $this->where($where)->field($field)->order($order);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes:获取渠道活码某个字段
     * @param array $where
     * @param string $whereRaw
     * @param string $column
     * @return float
     * @author: wanzy
     * @date_time: 2021/3/12 16:35
     */
    public function getSum($where=[],$whereRaw='',$column='number')
    {
        if (!empty($whereRaw)) {
            $sum = $this->whereRaw($whereRaw)->sum($column);
        } else {
            $sum = $this->where($where)->sum($column);
        }
        return $sum;
    }

    /**
     * Notes: 获取详情
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/12 19:27
     */
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 添加一条数据
     * @param $data
     * @return int|string
     * @author: wanzy
     * @date_time: 2021/3/12 19:15
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
     * @return VillageQywxChannelGroup
     * @author: wanzy
     * @date_time: 2021/3/12 19:15
     */
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 增加对应分组下渠道活码数量
     * @param $where
     * @param $num
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/26 17:05
     */
    public function incCodeNum($where, $num = 1)
    {
        $res = $this->where($where)->inc('number',$num)->update();
        return $res;
    }

    /**
     * Notes: 减少对应分组下渠道活码数量
     * @param array $where
     * @param int $num
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/26 17:08
     */
    public function decCodeNum($where = [], $num = 1) {
        $res = $this->where($where)->dec('number', $num)->update();
        return $res;
    }
}