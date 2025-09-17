<?php


namespace app\community\model\db;

use think\Model;

class HouseNewChargeStandardBind extends Model
{
    /**
     * 添加
     * @param array $data
     * @return int|string
     * @author lijie
     * @date_time 2021/06/16
     */
    public function addOne($data = [])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 获取列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */

    public function getBindList($where, $field = true, $page = 0, $limit = 15, $order = 'b.id DESC')
    {
        $data = $this->alias('b')
            ->leftJoin('house_village_parking_garage pg', 'b.garage_id = pg.garage_id')
            ->leftJoin('house_village_parking_position pp', 'pp.position_id = b.position_id')
            ->where($where)->field($field);
        if (!$page) {
            $data = $data->order($order)
                ->select();
        } else {
            $data = $data->page($page, $limit)
                ->order($order)
                ->select();
        }
      //  print_r($this->getLastSql());exit;
        return $data;
    }


    /**
     * 获取列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */

    public function getBindCount($where)
    {
        $data = $this->alias('b')
            ->leftJoin('house_village_parking_garage pg', 'b.garage_id = pg.garage_id')
            ->leftJoin('house_village_parking_position pp', 'pp.position_id = b.position_id')
            ->where($where)->count();
       // print_r($this->getLastSql());exit;
        return $data;
    }


    /**
     * 房间/车位绑定消费标准列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     */
    public function getList($where=[],$field=true,$page=0,$limit=6,$order='b.id DESC')
    {
        $data = $this->alias('b')
            ->leftJoin('house_new_charge_rule r','b.rule_id = r.id')
            ->leftJoin('house_new_charge_project p','b.project_id = p.id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)
            ->field($field);
        if($page){
            $data = $data ->page($page,$limit)
                ->order($order)
                ->select();
        }else{
            $data = $data->order($order)->select();
        }
        return $data;
    }

    public function getListCount($where)
    {
        $data = $this->alias('b')
            ->leftJoin('house_new_charge_rule r','b.rule_id = r.id')
            ->leftJoin('house_new_charge_project p','b.project_id = p.id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)->count();
        return $data;
    }

    /**
     * 获取列表
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getLists($where,$field=true,$page=0,$limit=15,$order='b.id DESC')
    {
        $data = $this->alias('b')
            ->leftJoin('house_village_user_vacancy v','b.vacancy_id = v.pigcms_id')
            ->leftJoin('house_village_layer l','l.id = b.layer_id')
            ->leftJoin('house_village_floor f','f.floor_id = b.floor_id')
            ->leftJoin('house_village_single s','s.id = b.single_id')
            ->where($where)->field($field);
        if(!$page){
            $data = $data->order($order)
                ->select();
        }else{
            $data = $data->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }


    /**
     * 统计车场绑定的数量
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:48
     * @param $where
     * @return int
     */
    public function getCount_garage($where)
    {
        $count = $this->alias('b')
            ->leftJoin('house_village_parking_garage pg', 'b.garage_id = pg.garage_id')
            ->leftJoin('house_village_parking_position pp', 'pp.position_id = b.position_id')->where($where)->count();
        return $count;
    }

    /**
     * 统计房间绑定的数量
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:48
     * @param $where
     * @return int
     */
    public function getCount_vecancy($where)
    {
        $count = $this->alias('b')
            ->leftJoin('house_village_user_vacancy v','b.vacancy_id = v.pigcms_id')
            ->leftJoin('house_village_layer l','l.id = b.layer_id')
            ->leftJoin('house_village_floor f','f.floor_id = b.floor_id')
            ->leftJoin('house_village_single s','s.id = b.single_id')->where($where)->count();
        return $count;
    }

    public function get_bind_vecancy_count($where)
    {
        $count = $this->alias('b')->leftJoin('house_village_user_vacancy v','b.vacancy_id = v.pigcms_id')->where($where)->count();
        return $count;
    }

    public function get_bind_vacancy_sum($where,$field='v.housesize')
    {
        $sumData = $this->alias('b')->leftJoin('house_village_user_vacancy v','b.vacancy_id = v.pigcms_id')->where($where)->sum($field);
        return $sumData>0 ? $sumData:0;
    }
    /**
     * 获取详情
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('id DESC')->find();
        return $data;
    }

    /**
     * 编辑
     * @author:zhubaodi
     * @date_time: 2021/6/17 9:48
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }


    /**
     * 房间/车位绑定消费标准数量
     * @author lijie
     * @date_time 2021/06/17
     * @param $where
     * @param array $whereOr
     * @return mixed
     */
    public function getCount($where,$whereOr=[])
    {
        if($whereOr)
            $count = $this->alias('b')->leftJoin('house_new_charge_rule r','b.rule_id = r.id')->where($where)->whereOr($whereOr)->count();
        else
            $count = $this->alias('b')->leftJoin('house_new_charge_rule r','b.rule_id = r.id')->where($where)->count();
        return $count;
    }

    /**
     * 删除
     * @author lijie
     * @date_time 2021/06/17
     * @param array $where
     * @return bool
     */
    public function delOne($where=[])
    {
        $res = $this->where($where)->save(['is_del' => 4]);
        return $res;
    }
    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return WorkMsgAuditInfo
     */
    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }


    public function getLists1($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getLists2($where,$field='*',$order='r.id desc',$page=0,$limit=20)
    {
        $whereRaw='';
        if (isset($where['_string'])){
            $whereRaw=$where['_string'];
            unset($where['_string']);
        }
        $sql = $this->alias('b')->leftJoin('house_new_charge_rule r','b.rule_id = r.id')->where($where)->field($field)->order($order);
        if($whereRaw){
            $sql = $sql->whereRaw($whereRaw);
        }

        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
    
    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }


    public function getBindProject($where=[],$field=true,$order='b.id DESC')
    {
        $data = $this->alias('b')
            ->leftJoin('house_new_charge_rule r','b.rule_id = r.id')
            ->leftJoin('house_new_charge_project p','b.project_id = p.id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)
            ->field($field)->order($order)->find();
        return $data;
    }

}