<?php
/**
 * 线上业务记录列表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageRepairList extends Model{

    /**
     * 查询对应条件下线上报修数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param $where
     * @param $group
     * @return int|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_repair_list_num($where,$group='') {
        if($group)
            $count = $this->field('type,count(*) as count')->where($where)->select();
        else
            $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 查询对应条件下线上报修数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param $where
     * @param $group
     * @return int|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_repair_sum($where,$field=true) {
            $count = $this->field($field)->where($where)->select();
        return $count;
    }

    /**
     * 获取各个区域报修数量
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @return mixed
     */
    public function getAreaRepairCount($where)
    {
        $count = $this->alias('r')
            ->leftJoin('house_village v','r.village_id = v.village_id')
            ->leftJoin('area a','a.area_id = v.city_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 查询对应条件下线上报修数量
     * @author  lijie
     * @date_time: 2020/12/04
     * @param $where
     * @param string $area_id
     * @return mixed
     */
    public function getRepairCountGroupByAreaId($where,$area_id='area_id')
    {
        $count = $this->alias('r')
            ->leftJoin('house_village v','r.village_id = v.village_id')
            ->leftJoin('area a','v.area_id = a.area_id')
            ->where($where)
            ->field('a.area_name',count('*'))
            ->group('v.area_id')
            ->count();
        return $count;
    }

    /**
     * 获取所有数据
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author weili
     * @datetime 2020/07/14 9:20
     */
    public function getListLimit($where,$field=true,$page,$limit,$order)
    {
        $list = $this->alias('r')->leftJoin('house_village_user_bind u','r.village_id=u.village_id and r.bind_id=u.pigcms_id')
            ->field($field)
            ->where($where)
            ->limit($page,$limit)
            ->order($order)
            ->select();
        fdump_api($this->getLastSql().print_r($where,1),'00list',1);
        return $list;
    }
    public function getListCount($where,$field=true)
    {
        $count = $this->alias('r')->leftJoin('house_village_user_bind u','r.village_id=u.village_id  and r.bind_id=u.pigcms_id')
            ->field($field)
            ->where($where)
            ->count();
        return $count;
    }
    /**
     * 获取一条数据
     * @author weili
     * @datetime 2020/07/14 10:13
     * @param array $where
     * @param string $field
     **@return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     */
    public function getFind($where,$field=true)
    {
        $info = $this->alias('r')->leftJoin('house_village_user_bind u','r.village_id=u.village_id and r.uid=u.uid and r.bind_id=u.pigcms_id')
            ->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 编辑数据
     * @param array $where
     * @param array $data
     * @return HouseVillageRepairList
     * @author weili
     * @datetime 2020/07/14 17:13
     */
    public function updateInfo($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}
