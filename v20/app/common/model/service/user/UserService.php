<?php


namespace app\common\model\service\user;


use app\common\model\db\User;
use app\common\model\db\UserLabel;
use Model;

class UserService
{
    public  function  check_new($phone,$cate_name){
        $field='uid';
        $where=[['phone','=',$phone]];
        $user =(new User())->getUser($field,$where);
        //$this->field('uid')->where(array('phone'=>$phone))->find();
        if(empty($user)){
            $where=[['uid','=',$phone]];
            //$user = $this->field('uid')->where(array('uid'=>$phone))->find();
            $user =(new User())->getUser($field,$where);
        }
        $m = new Model();

        $db = $this->get_db();
        $count = 0;
        switch($cate_name){
            case 'all':
                foreach($db as  $v){
                    $new_db = $v['db'];
                    $where = array();
                    $where['uid']=$user['uid'];
                    $where[$v['name']] = $v['condition'];
                    $count += $m->table($new_db)->where($where)->count('order_id');
                }
                break;
            case 'group':
                $new_db = $db['group'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'meal':
                $new_db = $db['meal'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'appoint':
                $new_db = $db['appoint'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'shop':
                $new_db = $db['shop'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $where['order_from'] = ['neq',1];//不是商城的订单
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'foodshop':
                $new_db = $db['foodshop'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'village_group':
                $new_db = $db['village_group'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
            case 'all':
                $new_db = $db['store'];
                $where = array();
                $where['uid']=$user['uid'];
                $where[$new_db['name']] = $new_db['condition'];
                $count  = $m->table($new_db['db'])->where($where)->count('order_id');
                break;
        }

        if($count>0){
            return 0;
        }else{
            return 1;
        }
    }

    //获取不同类型订单状态（存在这种订单状态的才算不是新用户）
    protected function get_db(){
        return array(
            'group' => array(
                'db' =>C('DB_PREFIX').'group_order',
                'name'=>'status',
                'condition'=>array('between',array('1','6'))
            ),
            'meal' => array(
                'db'=>C('DB_PREFIX').'meal_order_log',
                'name'=>'status',
                'condition'=>array('eq',2),
            ),
            'appoint' => array(
                'db'=>C('DB_PREFIX').'appoint_order',
                'name'=>'service_status',
                'condition'=>array('between',array('1','2'))
            ),
            'shop' => array(
                'db'=>C('DB_PREFIX').'shop_order',
                'name'=>'status',
                'condition'=>array('between',array('2','4')),
            ),
            'foodshop' => array(
                'db'=>C('DB_PREFIX').'foodshop_order',
                'name'=>'status',
                'condition'=>array('between',array('3','4'))
            ),
            'store' =>array(
                'db'=>C('DB_PREFIX').'store_order',
                'name'=>'paid',
                'condition'=>array('eq',1)
            ),
            'village_group' =>array(
                'db'=>C('DB_PREFIX').'village_group_order',
                'name'=>'status',
                'condition'=>array('in',[1,2,3,5,6,7,8,13])
            ),
        );
    }

    public function userLabel(){
        $where=[['is_del','=',0]];
        $list=(new UserLabel())->getSome($where)->toArray();
        return $list;
    }
}