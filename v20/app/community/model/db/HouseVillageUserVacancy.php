<?php
/**
 * 小区房屋表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 14:53
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageUserVacancy extends Model{
    
    public static $houseType = [
        1 => '住宅',
        2 => '商铺',
        3 => '办公'  
    ];
    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/4/29 9:55
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 查询对应条件下房间数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param $where
     * @param string $group
     * @return int
     */
    public function getVillageRoomNum($where,$group='') {
        if($group)
            $count = $this->field('count(*) as count,house_type')->where($where)->group($group)->select();
        else
            $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/9 11:59
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 删除信息
     * @author: wanziyang
     * @date_time: 2020/5/12 18:59
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * 查询对应物业下房间数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where 建议必须条件village_id
     * @return int
     */
    public function get_property_room_num($where) {
        $count = $this->alias('a')
            ->leftJoin('house_village hv','a.village_id = hv.village_id')
            ->leftJoin('house_property hp','hv.property_id = hp.property_id')
            ->where($where)
            ->count();
        return $count;
    }


    /**
     * 房间相关信息列表
     * @author: wanziyang
     * @date_time: 2020/5/6 15:52
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function getList($where,$field = true,$order='pigcms_id desc',$limit=20,$page=0) {
        if (is_array($where) && !isset($where['is_del'])) {
            $where[] = ['is_del','=',0];
        }
        $list = $this->field($field)->where($where)->order($order);
        if($page>0){
            $list =$list->page($page,$limit);
        }
        $list =$list->select();
        return $list;
    }

    /**
     * 房间相关信息列表
     * @author: wanziyang
     * @date_time: 2020/5/6 15:52
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function getList1($where,$field = true,$order='pigcms_id desc') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
    public function discardOrderByRoom($where,$save){
        $ret=$this->alias('v')
            ->leftJoin('house_new_pay_order o','v.pigcms_id = o.room_id')
            ->where($where)->save($save);
        return $ret;
    }
    /**
     * 房间相关用户数量
     * @author: wanziyang
     * @date_time: 2020/5/7 14:11
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getRoomUserNumber($where) {
        if (is_array($where)) {
            $where[] = ['a.is_del','=',0];
        }
        $count = $this->alias('a')
            ->leftJoin('house_village_user_bind b','a.pigcms_id = b.vacancy_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 房间相关用户信息
     * @author: wanziyang
     * @date_time: 2020/5/7 15:51
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @param int $page
     * @param int $limit
     * @return array|null|Model
     */
    public function getRoomUserList($where,$field = 'a.*,b.*,c.nickname,c.avatar',$order='b.pigcms_id desc',$page=0,$limit=15) {
        if (is_array($where)) {
            $where[] = ['a.is_del','=',0];
        }
        $list = $this->alias('a')
            ->leftJoin('house_village_user_bind b','a.pigcms_id = b.vacancy_id')
            ->leftJoin('user c','b.uid = c.uid')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $list->page($page,$limit);
        }
        $list = $list->select();
        return $list;
    }


    /**
     * @author: wanziyang
     * @date_time: 2020/5/12 9:57
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string|int $page 页数
     * @param string $order 排序
     * @param int $page_size 每页内容数量
     * @return array|null|Model
     */
    public function getPageList($where,$field='a.*,b.single_name,c.floor_name,c.floor_layer,d.avatar',$page='',$order='a.pigcms_id desc',$page_size=10) {
        $sql = $this->alias('a')
            ->leftJoin('house_village_single b','a.single_id = b.id')
            ->leftJoin('house_village_floor c','a.floor_id = c.floor_id')
            ->leftJoin('user d','a.uid = d.uid')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $sql->page($page, $page_size);
        }
        $list = $sql->select();
        return $list;
    }

    /**
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string|int $page 页数
     * @param string $order 排序
     * @param int $page_size 每页内容数量
     * @return array|null|Model
     * @author:zhubaodi
     * @date_time: 2021/6/18 16:42
     */
    public function getLists($where,$field='a.*,b.single_name,c.floor_name,d.layer_name',$page=0,$page_size=10,$order='a.pigcms_id desc') {
        $sql = $this->alias('a')
            ->leftJoin('house_village_single b','a.single_id = b.id')
            ->leftJoin('house_village_floor c','a.floor_id = c.floor_id')
            ->leftJoin('house_village_layer d','a.layer_id = d.id')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $sql->page($page, $page_size);
        }
        $list = $sql->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param array $where
     * @return int
     * @author:zhubaodi
     * @date_time: 2021/6/18 16:42
     */
    public function count($where)
    {
        $count = $this->alias('a')
            ->leftJoin('house_village_single b','a.single_id = b.id')
            ->leftJoin('house_village_floor c','a.floor_id = c.floor_id')
            ->leftJoin('house_village_layer d','a.layer_id = d.id')
            ->where($where)->count();
        return $count;
    }
    /**
     * Notes:求和
     * @param $where
     * @param $field
     * @return float
     * @author: weili
     * @datetime: 2020/10/13 18:32
     */
    public function getSum($where,$field)
    {
        $num = $this->where($where)->sum($field);
        return $num;
    }
    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/10/13 18:50
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @param string $key
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/13 18:50
     */
    public function getColumn($where,$field,$key='')
    {
        $data = $this->where($where)->column($field,$key);
        return $data;
    }

    /**
     * 获取详情
     * @param $where
     * @param string $whereRaw
     * @param string $field
     * @return array
     */
    public function getInfo($where,$whereRaw = '',$field='a.*,b.single_name,c.floor_name,d.layer_name') {
        $sql = $this->alias('a')
            ->leftJoin('house_village_single b','a.single_id = b.id')
            ->leftJoin('house_village_floor c','a.floor_id = c.floor_id')
            ->leftJoin('house_village_layer d','a.layer_id = d.id')
            ->where($where)
            ->field($field);
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
        $data = $sql->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }
    //todo 获取房间相关信息
    public function getVacancyInfo($where,$field,$order='a.pigcms_id desc'){
        $where2=[];
        if(isset($where['_string']) && !empty($where['_string'])){
            $where2=$where['_string'];
            unset($where['_string']);
        }
        $list = $this->alias('a')
            ->leftJoin('house_village_user_bind b','a.pigcms_id = b.vacancy_id and (b.type= 0 or b.type= 3) and b.status = 1')
            ->where($where)->where($where2)
            ->field($field)
            ->order($order)->select();
        return $list;
    }


    public function getRawOne($whereRaw,$field=true) {
        $info = $this->field($field)->whereRaw($whereRaw)->find();
        return $info;
    }

    public function addOne($data) {
        $set_info = $this->insertGetId($data);
        return $set_info;
    }

    /**
     * @param array $where
     * @param bool $field
     * @param bool|array $order
     * @param int $page 分页 如果不分页传0
     * @param int $limit 分页条数 如果不分页传0
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomList($where = [], $field = true,$order=true,$page=0,$limit=0){
        $sql = $this->field($field)->where($where)->order($order);
        if($page && $limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }


    /**
     * 查询某个字段最大值
     * @param array      $where 查询条件
     * @param string     $field 字段名
     * @param bool       $force 强制转为数字类型
     * @return mixed
     */
    public function getMax(array $where,string $field, bool $force = true)
    {
        $data = $this->where($where)->max($field, $force);
        return $data;
    }
    
    public function getWhereRawColumn($where, $field = 'pigcms_id', $key = '')
    {
        return $this->whereRaw($where)->column($field, $key);
    }
}