<?php


namespace app\community\model\db;
use app\community\model\service\Car\CarHandleGetInfoService;
use think\Model;

class HouseVillageVisitor extends Model
{
    /**
     * 添加访客记录
     * @author lijie
     * @date_time 2020/07/13
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        if (isset($data['village_id']) && intval($data['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearVisitorInfo($data['village_id']);
        }
        return $res;
    }

    /**
     * 根据条件获取访客列表
     * @author lijie
     * @date_time 2020/07/13
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * 更改访客记录状态
     * @author lijie
     * @date_time 2020/07/13
     * @param $where
     * @param $save
     * @return bool
     */
    public function saveOne($where,$save)
    {
        $set_info = $this->where($where)->save($save);
        $visitorInfo = $this->where($where)->find();
        if (isset($visitorInfo['village_id']) && intval($visitorInfo['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearVisitorInfo($visitorInfo['village_id']);
        }
        return $set_info;
    }

    /**
     * 获取访客和主人信息
     * @author lijie
     * @date_time 2020/09/02 9:40
     * @param $where
     * @param string $field
     * @return mixed
     */
    public function getOne($where,$field='v.visitor_name,v.visitor_phone,v.owner_name,v.owner_phone,v.owner_address,hvb.type')
    {
        $data = $this->alias('v')
            ->leftJoin('house_village_user_bind hvb','v.owner_uid = hvb.uid')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }

    /**
     * 获取访客和主人信息
     * @author lijie
     * @date_time 2020/09/02 9:40
     * @param $where
     * @param string $field
     * @return mixed
     */
    public function get_one($where,$field=true)
    {
        $data = $this->field($field)->where($where)->order('id DESC')->find();
        return $data;
    }

    /**
     * Notes:获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/10/15 17:38
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getVisitorCount($where)
    {
        $count = $this->alias('a')->leftJoin('house_village v','a.village_id = v.village_id')->where($where)->count();
        return $count;
    }

    /**
     * 删除访客
     * @author lijie
     * @date_time 2020/11/16
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $visitorInfo = $this->where($where)->find();
        $res = $this->where($where)->delete();
        if (isset($visitorInfo['village_id']) && intval($visitorInfo['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearVisitorInfo($visitorInfo['village_id']);
        }
        return $res;
    }
}