<?php
/**
 * 电表
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/12
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterAdminElectric extends Model{

    /**
     * 获取单个社区数据信息
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param int $electric_id 电表id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($electric_id,$field =true){
        $info = $this->field($field)->where(array('id'=>$electric_id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->alias('a')
            ->leftJoin('house_meter_electric_group b', 'b.id=a.group_id')
            ->where($where)
            ->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }


    /**
     * 获取电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getListAll($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->where($where)->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }

    /**
     * 获取电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getListReal($where,$field=true,$page=0,$limit=20,$order='a.id DESC',$type=0) {
        $list = $this->alias('a')
            ->leftJoin('house_meter_electric_realtime b', 'b.electric_id=a.id')
            ->where($where)
            ->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }



    /**
     * 统计电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getCount($where) {
        $list = $this->alias('a')
            ->Join('house_meter_electric_group b', 'b.id=a.group_id')
            ->where($where)->count();
        return $list;
    }



    /**
     * 获取电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getLists($where,$field=true,$page,$limit=20,$order='a.id DESC') {

        $list = $this->alias('a')
            ->Join('house_village_user_bind b', 'b.vacancy_id=a.vacancy_id')
            ->where($where)
            ->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;

    }

    /**
     * 添加数据
     * @author zhubaodi
     * @datetime 2021/4/12
     * @param array $data
     * @return integer
    **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
    /**
     * 获取单个数据
     * @author zhubaodi
     * @datetime 2021/4/12
     * @param array $where
     * @param bool $field
     * @return array
    **/
    public function getInfo($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }



    /**
     * 删除电表
     * @author zhubaodi
     * @date_time 2021/4/12
     * @param $where
     * @param bool $field
     * @param string $group
     * @return mixed
     */
    public function deleteInfo($where)
    {
        $data = $this->where($where)->delete();
        return $data;
    }

    /**
     * 根据分组获取数量
     * @author lijie
     * @date_time 2021/05/20
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getCountByField($where=[],$group='city_id')
    {
        $count = $this->where($where)->group($group)->count();
        return $count;
    }

    /**
     * 获取电表数量
     * @author lijie
     * @date_time 2021/05/20
     * @param array $where
     * @return int
     */
    public function getEleCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 根据集中器状态获取对应电表的数量
     * @author lijie
     * @date_time 2021/05/21
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getEleStatus($where=[],$group='')
    {
        if(!$group){
            $count = $this->alias('e')
                ->leftJoin('house_meter_electric_group g','e.group_id = g.id')
                ->where($where)
                ->count();
            return $count;
        }else{
            $data = $this->alias('e')
                ->leftJoin('house_meter_electric_group g','e.group_id = g.id')
                ->leftJoin('house_village v','v.village_id = e.village_id')
                ->field('v.village_name,count("e.id") as all_count,v.village_id')
                ->where($where)
                ->group($group)
                ->select();
            return $data;
        }
    }
}
