<?php


namespace app\mall\model\db;

use think\Model;
use think\facade\Config;
class MallNewBargainTeamUser extends Model
{
    /**
     * @param $where1
     * @param $goods_price 该团商品原价
     * @param $nums 砍价人数
     * @param $bar_total_price 砍掉总价
     * @param $floor_price  活动底价
     * @return \json
     */
    public function getTeamUserExist($where1){
       $user=$this->where($where1)->select();
        if(!empty($user)){
            $user=$user->toArray();
        }
       return $user;
   }

    /**
     * @param $where1
     * @return mixed
     * 查询数据
     */
    public function getOne($where1){
        $user=$this->where($where1)->find();
        if(!empty($user)){
            $user=$user->toArray();
        }
        return $user;
    }
    /**
     * @param $where1
     * @param $field
     * @return mixed
     * @author mrdeng
     * 求和
     */
    public function getSumBargainPer($where1,$field){
       return $this->where($where1)->sum($field);
    }

    /**
     * @param $tid
     * @return mixed
     * 商品详情页好友助力列表
     */
    public function helpList($tid)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field1 = 'm.avatar as user_logo,m.nickname,s.bar_price,s.bar_time';
        $condition[] = ['s.tid', '=', $tid];
        $condition[] = ['s.is_start', '=', 0];
        $result1 = $this->alias('s')
            ->field($field1)
            ->join($prefix . 'user' . ' m', 'm.uid = s.user_id')
            ->where($condition)
            ->order('s.bar_price asc')
            ->select()->toArray();
        return $result1;
    }

    public function getSumBargainUser($where){
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('b')
            ->join($prefix . 'mall_new_bargain_team bg', 'b.tid=bg.id')
            ->where($where)
            ->count();
        return $arr;
    }

    /**
     * @param $where
     * @return mixed
     * 条件关联队伍表是否满足信息
     */
    public function getBargainUser($where){
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('b')
            ->join($prefix . 'mall_new_bargain_team bg', 'b.tid=bg.id')
            ->where($where)
            ->select()
            ->toArray();
        return $arr;
    }


    /**
     * @param $condition1
     * @param $team
     * 根据条件保存数据
     * @author mrdeng
     */

    public function saveData($condition1,$team){
        return $this->where($condition1)->save($team);
    }


    /**
     * 添加数据 获取插入的数据id
     * User: chenxiang
     * Date: 2020/10/13 16:38
     * @param $data
     * @return int|string
     */
    public function addData($data) {
        $id=$this->insertGetId($data);
        return $id;
    }
}