<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/16 14:31
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillageLabel extends Model{

    /**
     * 获取单个数据信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 插入数据并获取插入id
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @paramarray $data
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

    /**
     * Notes:获取全部
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     */
    public function getList($where,$field=true,$page=0,$limit=10,$order='l.id desc',$where1=[])
    {
        if (!empty($where1)){
            $sql = $this->alias('l')
                ->leftJoin('house_village_label_cat c','c.cat_id = l.cat_id')
                ->where($where)->whereOr($where1)->field($field)->order($order);
        }else{
            $sql = $this->alias('l')
                ->leftJoin('house_village_label_cat c','c.cat_id = l.cat_id')
                ->where($where)->field($field)->order($order);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }


    public function getLists($where, $field = true, $page = 0, $limit = 10, $order = 'id desc')
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/27 13:54
     */
    public function getCounts($where,$where1=[])
    {
        if (!empty($where1)){
            $count = $this->alias('l')->leftJoin('house_village_label_cat c','c.cat_id = l.cat_id')->where($where)->whereOr($where1)->count();

        }else{
            $count = $this->alias('l')->leftJoin('house_village_label_cat c','c.cat_id = l.cat_id')->where($where)->count();

        }
        return $count;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/27 13:54
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }


    /**
     * 获取标签列表
     * @param $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseVillageLabel($where,$field = '*',$order = 'id DESC',$page = 0,$limit = 10){
        if(empty($where)){
            return [];
        }
        $data = [];
        if($page){
            $house_village_label = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $house_village_label = $this->where($where)->field($field)->order($order)->select();
        }
        if(!empty($house_village_label)){
            $data = $house_village_label->toArray();
        }
        return $data;
    }

    /**
     * 获取标签总数
     * @param $where
     * @return int
     */
    public function getHouseVillageLabelCount($where){
        return $this->where($where)->count();
    }

    /**
     * 新增、编辑标签
     * @param $where
     * @param $data
     * @return bool
     */
    public function changeHouseVillageLabel($type,$where,$data){
        if(($type != 'add' && empty($where)) || empty($data)){
            return  false;
        }
        if($type == 'add'){
            return $this->insert($data);
        }
        return $this->where($where)->save($data);
    }

    /**
     * 获取标签详情
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseVillageLabelInfo($where,$field = '*'){
        if(empty($where)){
            return [];
        }
        $data = $this->where($where)->field($field)->find();
        if(!empty($data)){
            return $data->toArray();
        }
        return [];
    }

}
