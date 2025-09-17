<?php


namespace app\mall\model\db;
use think\Model;

class MallNewGroupUser extends Model
{
    //获取拼主列表
  public function getUserList($groupid,$orderid,$uid){
      $prefix = env('DATABASE_PREFIX');
      $field = "o.uid as member_id,o.avatar";
      $where[]=["s.tid","=",$groupid];
      $return=$this ->alias('s')
          ->join($prefix.'user'.' o','o.uid = s.user_id')
          ->field($field)
          ->where($where)
          ->order('s.id asc')
          ->select()
          ->toArray();
      if(!empty($return)){
          foreach ($return as $key=>$val){
              if($val['member_id']==$uid){
                  $return[$key]['member_status']=1;
              }else{
                  $return[$key]['member_status']=0;
              }
          }
      }else{
          $return=[];
      }
      $return['now_num']=count($return);
      return $return;
  }

  //加入平团队伍成员表
    public function addTeamUser($data){
        $arr= $this->insert($data);
        return $arr;
    }
}