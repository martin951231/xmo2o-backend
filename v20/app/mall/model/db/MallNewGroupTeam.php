<?php


namespace app\mall\model\db;
use think\Model;
class MallNewGroupTeam extends Model
{
    public function getOne($treamid){
        $where = [
            ['id','=',$treamid],
        ];
        $return =$this->where($where)->find();
        if(!empty($return)){
            $return=$return->toArray();
        }
        return $return;
    }

    //建立团队
    public function insertGroup($data){
        $arr=(new MallNewGroupAct())->getBase($data['act_id']);
        $data['start_time']=time();
        //结束时间加上拼团活动的活动时长
        $data['end_time']=time()+$arr['affect_time'];
        $data['complete_num']=$arr['complete_num'];
        $arr= $this->insertGetId($data);
        return $arr;
    }

    //建立团队
    public function insertGroupGetStoreMsg($data){
       /* $where[]=['s.act_id','=',$data['act_id']];*/
        //$prefix = config('database.connections.mysql.prefix');
        $where[] = ['g.id','=',$data['act_id']];
        $where[] = ['m.type','=','group'];
        $arr=(new MallNewGroupAct())->getInfo($where,$field="*");
        if(empty($arr)){
            return false;
        }
        $data['start_time']=time();
        //结束时间加上拼团活动的活动时长
        $data['end_time']=time()+$arr['affect_time'];
        $data['store_id']=$arr['store_id'];
        $data['mer_id']=$arr['mer_id'];
        $data['complete_num']=$arr['complete_num'];
        $result= $this->insertGetId($data);
        return $result;
    }

    /**
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 成功拼团队伍数
     */
    public function getGroupNumSucess($condition){
        //成功拼团队伍数
        $nums = $this ->where($condition)->count();
        return $nums;
    }

    /**
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 成功拼团人数
     */
    public function getGroupNumSucessMan($condition,$sum){
        //成功拼团队伍数
        $nums = $this ->where($condition)->sum($sum);
        return $nums;
    }

    /**
     * @return mixed
     * @author mrdeng
     * 拼团列表
     */
    public function groupList($condition){
        $prefix = config('database.connections.mysql.prefix');
        $condition1[]=$condition;
        if(empty($condition)){
            $result = $this ->alias('s')
                ->join($prefix.'user'.' m','s.user_id = m.uid')
                ->where($condition1)
                ->select();
            if(!empty($result)){
                $result=$result->toArray();
            }
            return $result;
        }else{
            $field='m.nickname,m.avatar,s.complete_num,s.num,s.start_time,s.end_time,s.id,(s.complete_num-s.num) as left_num';
            $result = $this ->alias('s')
                ->join($prefix.'user'.' m','s.user_id = m.uid')
                ->join($prefix.'mall_new_group_sku'.' g','g.act_id = s.act_id')
                ->where($condition1)
                ->field($field)
                ->group('s.id')
                ->order('left_num asc')
                ->select();
            if(!empty($result)){
                $result=$result->toArray();
            }
            return $result;
        }
    }

    /**
     * @param $where
     * @param $data
     * @return MallNewGroupTeam
     * 改变团队数据
     */
    public function updateGroupStatus($where,$data){
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 查询团队列表
     */
    public function getTeamList($where,$field){
        $result = $this->field($field)->where($where)->select()->toArray();
        return $result;
    }

    /**
     * @return mixed
     * 拼团列表
     */
    public function getTeamOrderList($where,$field='',$page=0,$pageSize=10){
        if(!$field){
            $field='t.id,u.nickname,u.avatar,t.complete_num,t.num,t.start_time,t.end_time,t.id,(t.complete_num-t.num) as left_num,t.status,o.order_no';
        }
        
        $query = $this ->alias('t')
            ->join('user u','u.uid = t.user_id')
            ->join('mall_new_group_order go','t.id = go.tid and t.user_id = go.uid')
            ->join('mall_order o','go.order_id = o.order_id')
            ->where($where)
            ->field($field)
            ->order('id desc');
        if($page>0){
            $result = $query->paginate($pageSize)->toArray();
        }else{
            $result = $query->select()->toArray();
        }
        return $result;
    }
}