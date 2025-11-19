<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageUserLabel extends Model
{
    /**
     * 获取党员数量
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @return mixed
     */
    public function getCount($where,$group='')
    {
        $count = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('user u','u.uid = b.uid')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where);
        if(!empty($group)){
            $count=$count->group($group);
        }
        $data = $count->count();
        return $data;
    }

    /**
     * 获取党员列表
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getPartyMemberLists($where,$field=true,$page=0,$limit=0,$order='l.id DESC',$group='')
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('user u','u.uid = b.uid')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where)
            ->field($field)
            ->order($order);
        if(!empty($group)){
            $data=$data->group($group);
        }
        if($page){
            $data = $data->page($page,$limit)->select();
        }else{
            $data = $data->limit(3)->select();
        }
        return $data;
    }

    /**
     * Notes: 获取一条数据
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/16 13:54
     */
    public function getFind($where,$field,$order='id desc')
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where)
            ->field($field)
            ->order($order)->find();
        return $data;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return HouseVillageUserLabel
     * @author: weili
     * @datetime: 2020/9/17 15:03
     */
    public function edit($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * 获取街道居民标签
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return mixed
     */
    public function getStreetUser($where,$field=true,$page,$limit,$order='l.id DESC')
    {
        $data = $this->alias('l')
            ->leftJoin("house_village_user_bind b",'b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->where($where)
            ->field($field)
            ->page($page,$limit)
            ->order($order)
            ->select();
        return $data;
    }


    /**
     * 统计党员数据
     * @author: liukezhu
     * @date : 2022/5/7
     * @param $where
     * @param bool $field
     * @param $group
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getListByGroup($where,$field=true,$group,$order='l.id DESC',$page=0,$limit=0)
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where)
            ->field($field)
            ->order($order)->group($group);
        if($page){
            $data = $data->page($page,$limit)->select();
        }else{
            $data = $data->select();
        }
        return $data;
    }

    /**
     * 统计用户标签
     * @author: liukezhu
     * @date : 2022/5/9
     * @param $where
     * @param $column
     * @return mixed
     */
    public function getStreetUserColumn($where,$column)
    {
        $data = $this->alias('l')
            ->leftJoin("house_village_user_bind b",'b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->where($where)->column($column);
        return $data;
    }

    public function getOne($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    public function addOne($data) {
        $set_info = $this->insertGetId($data);
        return $set_info;
    }


    public function getPartyMemberRoomList($where,$field=true,$page=0,$limit=0,$order='l.id DESC')
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street s','s.area_id = v.street_id')
            ->leftJoin('area_street c','c.area_id = v.community_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $data = $data->page($page,$limit)->select();
        }else{
            $data = $data->select();
        }
        return $data;
    }

    public function getPartyMemberRoomCount($where)
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street s','s.area_id = v.street_id')
            ->leftJoin('area_street c','c.area_id = v.community_id')
            ->where($where)->count();
        return $data;
    }

    public function getPartyMemberRoomCounts($where)
    {
        $data = $this->alias('l')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = l.bind_id')
            ->leftJoin('house_village v','v.village_id = b.village_id')
            ->leftJoin('street_party_bind_user pbu','b.uid = pbu.uid')
            ->leftJoin('area_street_party_branch p','pbu.party_id = p.id')
            ->where($where)->count();
        return $data;
    }

}