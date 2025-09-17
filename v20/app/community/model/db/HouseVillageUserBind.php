<?php
/**
 * 小区业主家属租客信息表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageUserBind extends Model{

    /**
     * 获取相关数量
     * @author: wanziyang
     * @date_time: 2020/4/26 15:28
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getVillageUserNum($where){
        $count = $this->where($where)->count();
        return $count;
    }
    public function getBindUserList($where, $field = '*', $orderby = 'pigcms_id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    /**
     * 获取相关数量
     * @author: lijie
     * @date_time: 2020/14/54
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getVillageUserBindNum($where){
        $count = $this->alias('a')->leftJoin('house_village v','a.village_id = v.village_id')->where($where)->count();
        return $count;
    }

    /**
     * 获取单个社区数据信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:28
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return mixed
     */
    public function getOne($where,$field =true,$order='add_time DESC'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:38
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 添加
     * @author:wanziyang
     * @date_time: 2020/5/9 11:35
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }

    /**
     * 获取小区绑定用户信息
     * @author:wanziyang
     * @date_time: 2020/4/26 11:22
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitRoomList($where,$page=0,$field ='a.*',$order='a.pigcms_id DESC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('house_village_floor hvf', 'hvf.floor_id=a.floor_id AND hvf.village_id=a.village_id')
            ->leftJoin('house_village_single hvs', 'hvs.id=a.single_id')
            ->leftJoin('user c','a.uid = c.uid')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }



    /**
     * 获取小区绑定用户信息
     * @author:wanziyang
     * @date_time: 2020/5/28 11:41
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return mixed|array|null|\think\Model
     */
    public function getLimitUserList($where,$page=0,$field =true,$order='pigcms_id ASC',$page_size=10) {
        $db_list = $this
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }


    /**
     * 删除信息
     * @author: wanziyang
     * @date_time: 2020/5/12 14:57
     * @param array $where 删除内容的条件
     * @return bool
     */
    public function delOne($where) {
        $set_info = $this->where($where)->delete();
        return $set_info;
    }

    /**
     * 搜获符合条件的社区用户
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param $whereOr
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLists($where,$whereOr,$field=true,$page=1,$limit=15)
    {
        $lists = $this->where($where)->whereOr($whereOr)->field($field)->page($page,$limit)->select();
        return $lists;
    }

    /**
     * 获取水电费等总额
     * @author lijie
     * @date_time 2020/08/17 10:49
     * @param $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSumBill($where,$field='sum(water_price)')
    {
        $data = $this->where($where)->field($field)->find()->toArray();
        return $data;
    }
    public function getSumBills($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 用户信息
     * @author lijie
     * @date_time 2020/10/23
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getUserBindInfo($where,$field,$order='hvb.pigcms_id desc')
    {
        $data = $this->alias('hvb')
            ->leftJoin('user u', 'hvb.uid=u.uid')
            ->field($field)
            ->where($where)
            ->order($order)
            ->find();
        return $data;
    }

    /**
     * 街道用户user_bind列表
     * @author lijie
     * @date_time 2020/10/24
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return mixed
     */
    public function getStreetUserBind($where,$field=true,$page=0,$limit=0,$order='hvb.pigcms_id DESC')
    {
        $data = $this->alias('hvb')
            ->leftJoin('house_village v','v.village_id = hvb.village_id')
            ->leftJoin('user u','hvb.uid = u.uid')
            ->field($field)
            ->group('hvb.pigcms_id')
            ->where($where);
        if($page){
            $data = $data->page($page,$limit)
                ->order($order)
                ->select();
        }else{
            $data = $data->order($order)
                ->select();
        }
        return $data;
    }

    /**
     * 街道用户user_bind数量
     * @author lijie
     * @date_time 2020/10/24
     * @param $where
     * @return mixed
     */
    public function getStreetUserBindCount($where)
    {
        $count = $this->alias('hvb')
            ->leftJoin('house_village v','v.village_id = hvb.village_id')
            ->leftJoin('user u','hvb.uid = u.uid')
            ->where($where)
            ->count();
        return $count;
    }


    /**
     * @author: wanziyang
     * @date_time: 2020/11/17 19:57
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string|int $page 页数
     * @param string $order 排序
     * @param int $page_size 每页内容数量
     * @return array|null|Model
     */
    public function getPageList($where,$field='a.*,b.single_name,c.floor_name,c.floor_layer,d.avatar,h.pigcms_id as bind_id, h.name as bind_name.h.phone as bind_phone',$page='',$order='a.pigcms_id desc, b.pigcms_id desc',$page_size=10) {
        $sql =  $this->alias('h')
            ->leftJoin('house_village_user_vacancy a','h.vacancy_id = a.pigcms_id')
            ->leftJoin('house_village_single b','h.single_id = b.id')
            ->leftJoin('house_village_floor c','h.floor_id = c.floor_id')
            ->leftJoin('user d','h.uid = d.uid')
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
     * @author: zhubaodi
     * @date_time: 2022/06/09 10:57
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string|int $page 页数
     * @param string $order 排序
     * @param int $page_size 每页内容数量
     * @return array|null|Model
     */
    public function getuserList($where,$field='a.*,b.single_name,c.floor_name,c.floor_layer,d.avatar,h.pigcms_id as bind_id, h.name as bind_name,h.phone as bind_phone',$page='',$page_size=10,$order='a.pigcms_id desc, h.pigcms_id desc') {
        $sql =  $this->alias('h')
            ->leftJoin('house_village_user_vacancy a','h.vacancy_id = a.pigcms_id')
            ->leftJoin('house_village_single b','h.single_id = b.id')
            ->leftJoin('house_village_floor c','h.floor_id = c.floor_id')
            ->leftJoin('house_village_layer l','h.layer_id = l.id')
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
     * @author: zhubaodi
     * @date_time: 2022/06/09 10:57
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string|int $page 页数
     * @param string $order 排序
     * @param int $page_size 每页内容数量
     * @return array|null|Model
     */
    public function getuserCount($where) {
        $list =  $this->alias('h')
            ->leftJoin('house_village_user_vacancy a','h.vacancy_id = a.pigcms_id')
            ->leftJoin('house_village_single b','h.single_id = b.id')
            ->leftJoin('house_village_floor c','h.floor_id = c.floor_id')
            ->leftJoin('house_village_layer l','h.layer_id = l.id')
            ->where($where)->count();
        return $list;
    }


    

    /**
     * 更新用户费用
     * @author lijie
     * @date_time 2020/11/30
     * @param $where
     * @param $field
     * @param $money
     * @return mixed
     */
    public function updateFee($where,$field,$money)
    {
        $res = $this->where($where)->Inc($field,$money)->update();
        return $res;
    }

    /**
     * 获取用户列表不分页
     * @param $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where, $field = true, $order = '')
    {
        if ($order) {
            $data = $this->where($where)->field($field)->order($order)->select();
        } else {
            $data = $this->where($where)->field($field)->select();
        }
        return $data;
    }

    public function getFind($where,$field,$order='id desc')
    {
        $data = $this->alias('b')
            ->leftJoin('house_village_user_label l','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where)
            ->field($field)
            ->order($order)->find();
        return $data;
    }

    //todo 查询该房间里所有真实用户
    public function getUserColumn($where,$column){
        $where2='';
        if(isset($where['_string']) && !empty($where['_string'])){
            $where2=$where['_string'];
            unset($where['_string']);
        }
        return $this->where($where)->where($where2)->column($column);
    }

    public function getInfo($where=[],$field=true)
    {
        $data = $this->alias('hvb')
            ->leftJoin('user u', 'hvb.uid = u.uid')
            ->leftJoin('house_village_user_vacancy r', 'hvb.vacancy_id = r.pigcms_id')
            ->leftJoin('house_village v', 'hvb.village_id = v.village_id')
            ->leftJoin('house_village_single s', 'r.single_id = s.id')
            ->leftJoin('house_village_floor f', 'r.floor_id = f.floor_id')
            ->leftJoin('house_village_layer l', 'r.layer_id = l.id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }


    /**
     * 查询街道业主列表
     * @author: liukezhu
     * @date : 2021/11/8
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getGroupUserList($where,$field =true,$group,$order='ub.pigcms_id ASC',$page=0,$page_size=10) {
        $db_list = $this->alias('ub')
            ->leftJoin('user u','u.uid = ub.uid')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->group($group)->select();
        return $list;
    }

    /**
     *统计分组数量
     * @author: liukezhu
     * @date : 2021/11/18
     * @param $where
     * @param $group
     * @return mixed
     */
    public function getGroupUserCount($where,$group) {
        $list = $this->alias('ub')->leftJoin('user u','u.uid = ub.uid')->where($where)->group($group)->count();
        return $list;
    }


    public function getUserBindList($where,$field=true,$order='h.pigcms_id desc',$page=0,$page_size=10) {
        $sql = $this->alias('h')
            ->leftJoin('house_village v','v.village_id = h.village_id')
            ->leftJoin('area_street s','s.area_id = v.community_id')
            ->leftJoin('house_village_single b','b.id = h.single_id')
            ->leftJoin('house_village_floor c','c.floor_id = h.floor_id')
            ->leftJoin('house_village_layer y','y.id = h.layer_id')
            ->leftJoin('house_village_user_vacancy a','a.pigcms_id = h.vacancy_id')
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
     * 查询街道业主总数量
     * @author: liukezhu
     * @date : 2021/11/8
     * @param $where
     * @return mixed
     */
    public function getUserBindCount($where)
    {
        $count =  $this->alias('h')
            ->leftJoin('house_village v','v.village_id = h.village_id')
            ->leftJoin('area_street s','s.area_id = v.community_id')
            ->leftJoin('house_village_single b','b.id = h.single_id')
            ->leftJoin('house_village_floor c','c.floor_id = h.floor_id')
            ->leftJoin('house_village_layer y','y.id = h.layer_id')
            ->leftJoin('house_village_user_vacancy a','a.pigcms_id = h.vacancy_id')
            ->where($where)
            ->count();
        return $count;
    }

    public function getRawOne($whereRaw,$field=true)
    {
        $info = $this->field($field)->whereRaw($whereRaw)->find();
        return $info;
    }

    /**
     * 获取社区下业主相关信息
     * @param $where
     * @param bool $field
     * @return array
     */
    public function getStreetUserInfo($where,$field = true)
    {
        $data = $this->alias('vub')
            ->leftJoin('house_village v', 'v.village_id = vub.village_id')
            ->leftJoin('area_street s', 's.area_id = v.community_id')
            ->leftJoin('user u', 'u.uid = vub.uid')
            ->where($where)
            ->field($field)
            ->find();
        if ($data && !$data->isEmpty()) {
            return $data->toArray();
        }
        return [];
    }

    /**
     * 获取单个user_bind数据及小区信息
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVillageUserBind($where,$field){
        $info = $this->alias('a')
            ->leftJoin('house_village v','a.village_id = v.village_id')
            ->field($field)
            ->where($where)
            ->order('a.add_time DESC')
            ->find();
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }

    /**
     * 获取当个用户信息 whereOr
     * User: zhanghan
     * Date: 2022/1/13
     * Time: 10:22
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOneWhereOr($where,$field =true){
        $info = $this->field($field)->whereOr($where)->order('add_time DESC')->find();
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }


    public function getLists($where,$whereRaw,$field=true,$order='pigcms_id desc',$page=0,$limit=20) {
        $db_list = $this->where($where)->whereRaw($whereRaw)->field($field)->order($order);
        if ($page>0) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->select();
        return $list;
    }

    public function getOneColumn($where,$field = 'floor_id',$key=''){
        return $this->where($where)->column($field,$key);
	
    }

    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column, $key);
        return $data;
    }

    public function getUserBindVacancyId($where,$field='') {
        $list = $this->field($field)->where($where)->group('vacancy_id')->select();
        return $list;
    }
    
    public function getUserBindVacancyList($where,$field='*',$order='ub.pigcms_id ASC',$page=0,$page_size=10) {
        $sql = $this->alias('ub')
            ->leftJoin('house_village_user_vacancy uv','ub.vacancy_id = uv.pigcms_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page>0) {
            $sql->page($page, $page_size);
        }
        $list = $sql->select();
        return $list;
    }
    
    public function getFaceList($where,$field,$order,$pageSize,$whereRaw='')
    {
        $list = $this->alias('ub')
//            ->join('house_face_img hfi','hfi.uid = ub.uid')
            ->where($where)->field($field);
        if($whereRaw){
            $list = $list->whereRaw($whereRaw)->group('ub.pigcms_id');
        }
        $list = $list->order($order);
        if($pageSize){
            $list = $list->paginate($pageSize)
                ->toArray();
        }else {
            $list = $list->select();
            $list = $list ? $list->toArray() : [];
        }
        return $list;
    }
    
    /**
     * 获取房间业主、家属、租客总积分
     */
    public function getScoreCount($where,$field)
    {
        $scoreCount = $this->alias('ub')
            ->join('user u','u.uid = ub.uid')
            ->where($where)
            ->value($field);
        return $scoreCount;
    }
    
    /**
     * 获取房间业主、家属、租客信息
     */
    public function getRoomBindUser($where,$field,$order='u.score_count desc')
    {
        $list= $this->alias('ub')
            ->join('user u','u.uid = ub.uid')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select()
            ->toArray();
        return $list;
    }
    public function getUserBind($where,$whereRaw,$field,$order='ub.pigcms_id desc')
    {
        $list = $this->alias('ub')
            ->leftjoin('user us','ub.uid=us.uid')
            ->whereRaw($whereRaw)
            ->where($where)
            ->field($field)
            ->order($order)
            ->select()
            ->toArray();
        return $list;
    }
}