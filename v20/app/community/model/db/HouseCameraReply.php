<?php


namespace app\community\model\db;

use think\Model;
class HouseCameraReply extends Model
{
    /**
     * 获取视频监控申请信息
     * @author lijie
     * @date_time 2022/01/11
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        return $this->where($where)->field($field)->find();
    }

    /**
     * 添加视频监控申请
     * @author lijie
     * @date_time 2022/01/11
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        return $this->insertGetId($data);
    }

    /**
     * 修改视频监控申请
     * @author lijie
     * @date_time 2022/01/11
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        return $this->where($where)->save($data);
    }

    /**
     * 视频权限申请列表
     * @author lijie
     * @date_time 2022/01/14
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
    public function getList($where=[],$field=true,$page=1,$limit=15,$order='id DESC')
    {
        return $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
    }

    /**
     * 视频权限申请数量
     * @author lijie
     * @date_time 2022/01/14
     * @param array $where
     * @return int
     */
    public function getCount($where=[])
    {
        return $this->where($where)->count();
    }
}