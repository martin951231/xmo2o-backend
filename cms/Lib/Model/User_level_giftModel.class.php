<?php
class User_level_giftModel extends Model
{
    public $status_info = array(
        1 => '开启',
        2 => '关闭',
        3 => '删除',
    );
    // 操作类型 1 待发货 2 已发货
    public $order_status= array(
        0 => '待领取',
        1 => '已领取，<span style="color: red;">待发货</span>',
        2 => '已发货',
    );

	public function level_gift_list($where=array(), $pageSize = 20, $order = 'a.gift_id desc', $field = 'a.*, ul.lname, ul.level')
	{
	    if ($where) {
            if ($where['gift_id']) {
                $list_where['a.gift_id'] = $where['gift_id'];
            }
            if ($where['activation_code']) {
                $list_where['a.activation_code'] = array('LIKE', '%'.$where['activation_code'].'%');
            }
            if ($where['_string']) {
                $list_where['_string'] = $where['_string'];
            }

            // 查询全部 开启 1 禁用 2 删除 3
            if ($where['status'] > 0) {
                $list_where['a.gift_status'] = $where['status'];
            }
	    }
	    // 去除删除的信息
        $list_where['a.gift_status'] = array('neq', 3);

	    // 处理下分页信息
        if(!isset($_POST['page']) && $_GET['page']){
            $_POST['page'] = $_GET['page'];
        } elseif (!isset($_GET['page'])  && $_POST['page']){
            $_GET['page'] = $_POST['page'];
        }
        import('@.ORG.system_page');
        $count = $this->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->where($list_where)->count();

        $p = new Page($count,$pageSize,'page');

        $list = $this->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->field($field)->where($list_where)->order($order)->limit($p->firstRow,$p->listRows)->select();


        $pagebar = $p->show();
        return array('list'=>$list,'pagebar'=>$pagebar, 'status_info' => $this->status_info);
	}


    // 用户使用余额升级的时候默认生成一条待领取的礼包信息，如果对应等级不存在礼包，关联礼包暂时为空
    public function up_level_gift_user($uid, $user_level_info, $level_time=0) {
	    if ($level_time<=0) {
            $level_time = time();
        }
        $user_level_gift_order = array(
            'add_time' => time(),
            'level_time' => $level_time,
            'uid' => $uid,
            'level_id' => $user_level_info['id'],
            'lname' => $user_level_info['lname'],
            'status' => 0
        );
        $gift = $this->where(array('level_id' => $user_level_info['id'], 'gift_status' => 1))->find();
	    if ($gift) {
            $user_level_gift_order['gift_id'] = $gift['gift_id'];
        } else {
            $gift = [];
        }
        $m_user_level_gift_order = M('User_level_gift_order');
        $m_user_level_gift_order->data($user_level_gift_order)->add();
        return $gift;
    }



    public function level_gift_order_list($where=array(), $pageSize = 20, $order = 'a.order_id desc', $field = 'a.*, ul.lname, ul.level')
    {
        $list_where = array();
        if ($where) {
            if ($where['order_id']) {
                $list_where['a.order_id'] = $where['order_id'];
            }
            if ($where['level_id']) {
                $list_where['a.level_id'] = $where['level_id'];
            }
            if ($where['activation_code']) {
                $list_where['a.activation_code'] = array('LIKE', '%'.$where['activation_code'].'%');
            }
            if ($where['_string']) {
                $list_where['_string'] = $where['_string'];
            }

            // 查询 -1 查询全部  -2 查询待领取  1 领取  2 已发货
            if ($where['status'] == -2) {
                $list_where['a.status'] = 0;
            }elseif ($where['status'] > 0) {
                $list_where['a.status'] = $where['status'];
            }
        }

        // 处理下分页信息
        if(!isset($_POST['page']) && $_GET['page']){
            $_POST['page'] = $_GET['page'];
        } elseif (!isset($_GET['page'])  && $_POST['page']){
            $_GET['page'] = $_POST['page'];
        }
        import('@.ORG.system_page');
        $count = M('User_level_gift_order')->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->where($list_where)->count();

        $p = new Page($count,$pageSize,'page');

        $list = M('User_level_gift_order')->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->field($field)->where($list_where)->order($order)->limit($p->firstRow,$p->listRows)->select();


        $pagebar = $p->show();
        return array('list'=>$list,'pagebar'=>$pagebar, 'order_status' => $this->order_status);
    }



}
?>