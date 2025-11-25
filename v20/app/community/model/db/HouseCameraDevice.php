<?php


namespace app\community\model\db;

use think\Model;
class HouseCameraDevice extends Model
{
    /**
     * Notes: 获取数量
     * @author: weili
     * @datetime: 2020/8/3 15:51
     * @param array $where
     * @param string $whereRaw
     * @return int
     */
    public function getCount($where=[],$whereRaw='')
    {
        $sql = $this->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $count = $sql->count();
        return $count;
    }

    /**
     * 获取单条数据
     * @author: zhubaodi
     * @date : 2021/11/29
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->order('camera_id DESC')->find();
        return $info;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/14 13:59
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page 分页
     * @param int $limit
     * @param string $order 排序
     * @param string $whereRaw 字符串查询条件
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field =true,$page=1,$limit=15,$order='camera_id DESC',$whereRaw='')
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 添加视频监控
     * @author lijie
     * @date_time 2021/01/08
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        return $this->insertGetId($data);
    }

    /**
     * 修改监控视频
     * @author lijie
     * @date_time 2021/01/08
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        return $this->where($where)->save($data);
    }


    /**
     * 查询特定字段返回组
     * @param string $whereRaw
     * @param bool $field
     * @return array
     */
    public function getColumnByRaw($whereRaw,$field =true){
        $column = $this->whereRaw($whereRaw)->column($field);
        return $column;
    }

    public function getListLeftJoinChannel($where, $field = 'a.*', $page = 1, $limit = 15, $order='a.camera_id DESC')
    {
        $sql = $this->alias('a')
            ->leftJoin('house_device_channel b',"a.camera_id = b.device_id AND b.device_type='house_camera_device' AND b.isDel=0")
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getListLeftJoinChannelCount($where)
    {
        return $this->alias('a')
            ->leftJoin('house_device_channel b',"a.camera_id = b.device_id AND b.device_type='house_camera_device' AND b.isDel=0")
            ->where($where)->count();
    }
}