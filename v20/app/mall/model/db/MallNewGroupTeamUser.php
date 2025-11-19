<?php


namespace app\mall\model\db;
use think\Exception;
use think\Model;

class MallNewGroupTeamUser extends Model
{
    //获取拼主列表
  public function getUserList($groupid,$orderid,$uid,$act_id){
          $prefix = config('database.connections.mysql.prefix');
          $field = "o.uid as member_id,o.avatar,s.type,r.status as order_status";
          $where=[["r.tid","=",$groupid],["r.act_id","=",$act_id],["r.status","<",2],['s.type','=',0]];
          $return2=$this ->alias('s')
              ->join($prefix.'user'.' o','o.uid = s.user_id')
              ->join($prefix.'mall_new_group_order'.' r','r.uid = s.user_id')
              ->field($field)
              ->where($where)
              ->group('s.user_id')
              ->order('s.id asc')
              ->select()->toArray();
          $field1 = "r.id as member_id,r.avatar,s.type";
          $where1=[["s.tid","=",$groupid],['s.type','=',1]];
          $return1=$this ->alias('s')
              ->join($prefix.'mall_robot_list'.' r','r.id = s.user_id')
              ->field($field1)
              ->where($where1)
              ->order('s.id asc')
              ->select()->toArray();
          $return=array_merge($return2,$return1);
      if(!empty($return)){
          foreach ($return as $key=>$val){
              $return[$key]['pay_status']=1;//默认已支付，因为可能有机器人
              if(isset($val['order_status']) && $val['order_status']==0){
                  $return[$key]['pay_status']=0;//正在支付中
              }elseif(isset($val['order_status']) && $val['order_status']==2){
                  unset($return[$key]);//取消的删掉
                  continue;
              }
              if(empty($val['avatar'])){
                  $return[$key]['avatar'] = cfg('site_url').'/static/images/user_avatar.jpg';
                  $return[$key]['member_icon'] = cfg('site_url').'/static/images/user_avatar.jpg';
              }else{
                  $return[$key]['avatar'] =  $val['avatar'] ? replace_file_domain($val['avatar']) : '';
                  $return[$key]['member_icon'] = $val['avatar'] ? replace_file_domain($val['avatar']) : '';
              }

              if($val['member_id']==$uid && $val['type']==0){
                  $return[$key]['member_status']=1;
              }else{
                  $return[$key]['member_status']=0;
              }
          }
          array_values($return);
          $last_names = array_column($return,'member_status');
          array_multisort($last_names,SORT_DESC,$return);
      }else{
          $return=[];
      }
      return $return;
  }

    //获取成功订单数
    public function getSuccessCount($act_id){
        $prefix = config('database.connections.mysql.prefix');
        $where=[["r.status","=",1],["r.act_id","=",$act_id]];
        $return2=$this ->alias('s')
            ->join($prefix.'mall_new_group_order'.' r','r.order_id = s.order_id')
            ->where($where)
            ->order('s.id asc')
            ->count();
        return $return2;
    }

  //加入平团队伍成员表
    public function addTeamUser($data){
        try {
            $data['add_time'] = time();
          $arr= $this->insertGetId($data);
        }catch (\Exception $e) {
            return false;
        }
        return $arr;
    }

    /**
     * @param $act_id
     * @param $tid
     * @param $uid
     * 判断用户是否有参团
     * @author mrdeng
     */
    public function getUserActStatus($act_id,$tid,$uid){
        $where[]=['t.act_id','=',$act_id];
        $where[]=['t.id','=',$tid];
        $where[]=['s.user_id','=',$uid];
        $where[]=['r.uid','=',$uid];
        $where[]=['r.tid','=',$tid];
        $where[]=['r.act_id','=',$act_id];
        $where[]=['r.status','<',2];
        $where[]=['t.start_time','<',time()];
        $where[]=['t.end_time','>=',time()];
        $field="*";
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('s')
            ->join($prefix . 'mall_new_group_team t', 't.id=s.tid')
            ->join($prefix . 'mall_new_group_order r', 'r.uid=s.user_id')
            ->field($field)
            ->where($where)
            ->find();
        if (!empty($arr)) {
            return $arr=$arr->toArray();
        } else {
            return [];
        }
    }

    /** 修改数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function updatePrepare($data,$where) {
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
     * 获取团队用户参团列表
     */
    public function getTeamUserList($where,$field){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'mall_new_group_team t', 't.id=s.tid')
            ->join($prefix . 'mall_new_group_order r', 'r.order_id=s.order_id')
            ->field($field)->where($where)->count();
            return $result;
    }
}