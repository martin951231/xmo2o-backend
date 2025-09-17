<?php
/**
 * 街道社区管理员
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;


class AreaStreet extends Model{

    /**
     * 获取单个街道社区管理员数据信息
     * @author: wanziyang
     * @date_time: 2020/4/24 13:15
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true,$orderby='area_id desc'){
        $info = $this->field($field)->where($where)->order($orderby)->find();
        return $info;
    }


    /**
     * 获取街道小区相关人数相关数量
     * @author: wanziyang
     * @date_time: 2020/5/22 16:24
     * @param array $where 查询条件
     * @param int $bind_type
     * @return array|null|Model
     */
    public function getStreetVillageUserNum($where,$bind_type=0){
        $count = $this->alias('a');
        if($bind_type){
            $count = $count->leftJoin('house_village b', 'b.community_id=a.area_id')
                ->leftJoin('house_village_user_bind c', 'c.village_id=b.village_id')
                ->where($where)
                ->count();
        }else{
            $count = $count->leftJoin('house_village b', 'b.street_id=a.area_id')
                ->leftJoin('house_village_user_bind c', 'c.village_id=b.village_id')
                ->where($where)
                ->count();
        }
        return $count;
    }

    /**
     * 获取社区小区相关人数相关数量
     * @author: wanziyang
     * @date_time: 2020/5/22 16:24
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getCommunityVillageUserNum($where){
        $count = $this->alias('a')
            ->leftJoin('house_village b', 'b.community_id=a.area_id')
            ->leftJoin('house_village_user_bind c', 'c.village_id=b.village_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 获取街道下社区数量
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @return int
     */
    public function getStreetCommunityNum($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取街道下小区数量
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @param $bind_type
     * @return mixed
     */
    public function getStreetVillageNum($where,$bind_type)
    {
        $count = $this->alias('a');
        if($bind_type){
            $count = $count->leftJoin('house_village b', 'b.community_id=a.area_id')
                ->where($where)
                ->count();
        }else{
            $count = $count->leftJoin('house_village b', 'b.street_id=a.area_id')
                ->where($where)
                ->count();
        }
        return $count;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/22 17:08
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/22 17:10
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }

    /**
     * 获取街道列表
     * @author lijie
     * @date_time 2020/09/09 10:24
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
    public function getLists($where,$field=true,$page=1,$limit=20,$order='area_id DESC')
    {
        if($page) {
            $res = $this->where($where)->field($field)->order($order)->page($page, $limit)->select();
            return $res;
        }else{
            $res = $this->where($where)->field($field)->order($order)->select();

            return $res;
        }
    }

    /**
     * 获取某个字段
     * @author: liukezhu
     * @date : 2021/11/8
     * @param $where
     * @param $column
     * @return mixed
     */
    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }
}