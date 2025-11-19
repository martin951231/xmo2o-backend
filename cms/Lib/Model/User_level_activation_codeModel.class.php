<?php
class User_level_activation_codeModel extends Model
{
    public $status_info = array(
        0 => '未激活',
        1 => '已激活',
        2 => '禁用',
        3 => '失效',
        4 => '删除',
    );
    // 操作类型 0 添加激活码 1 批量生成激活码 2 编辑 3 激活 4删除 5失效 6 批量编辑
    public $log_type = array(
        0 => '添加激活码',
        1 => '批量生成激活码',
        2 => '编辑',
        3 => '激活',
        4 => '删除',
        5 => '失效',
        6 => '批量编辑',
    );

	public function activation_code_list($where=array(), $pageSize = 20, $order = 'a.code_id desc', $field = 'a.*, ul.lname')
	{
	    if ($where) {
            if ($where['code_id']) {
                $code_where['a.code_id'] = $where['code_id'];
            }
            if ($where['activation_code']) {
                $code_where['a.activation_code'] = array('LIKE', '%'.$where['activation_code'].'%');
            }
            if ($where['_string']) {
                $code_where['_string'] = $where['_string'];
            }

            // 查询全部 status -1 查询未激活 status -2  激活 1 禁用 2 失效 3
            if ($where['status'] == -2) {
                $code_where['status'] = 0;
            } elseif ($where['status'] > 0) {
                $code_where['status'] = $where['status'];
            }

            if ($where['uid']) {
                $code_where['a.uid'] = $where['uid'];
            }
            if ($where['mer_id']) {
                $code_where['a.mer_id'] = $where['mer_id'];
            }
	    }
	    // 去除删除的信息
        $code_where['a.status'] = array('neq', 4);

	    // 处理下分页信息
        if(!isset($_POST['page']) && $_GET['page']){
            $_POST['page'] = $_GET['page'];
        } elseif (!isset($_GET['page'])  && $_POST['page']){
            $_GET['page'] = $_POST['page'];
        }
        import('@.ORG.system_page');
        $count = $this->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->where($code_where)->count();

        $p = new Page($count,$pageSize,'page');

        $list = $this->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->field($field)->where($code_where)->order($order)->limit($p->firstRow,$p->listRows)->select();

        $pagebar = $p->show();
        return array('list'=>$list,'pagebar'=>$pagebar, 'status_info' => $this->status_info);
	}



    public function activation_code_log($where=array(), $pageSize = 20, $order = 'log_time desc, log_id desc', $field = true)
    {
        $code_where = array();
        if ($where) {
            if ($where['code_id']) {
                $code_where['code_id'] = $where['code_id'];
            }
            if ($where['_string']) {
                $code_where['_string'] = $where['_string'];
            }

            // 查询全部 log_type -1 查询全部 -2 查询添加激活码 1 批量生成激活码 2 编辑 3 激活 4删除 5失效 6 批量编辑
            if ($where['log_type'] == -2) {
                $code_where['log_type'] = 0;
            } elseif ($where['log_type'] > 0) {
                $code_where['log_type'] = $where['log_type'];
            }

            if ($where['uid']) {
                $code_where['uid'] = $where['uid'];
            }
            // 操作对象 0 平台操作 1 用户操作 2 商家操作
            if ($where['type']) {
                $code_where['type'] = $where['type'];
            }
        }

        // 处理下分页信息
        if(!isset($_POST['page']) && $_GET['page']){
            $_POST['page'] = $_GET['page'];
        } elseif (!isset($_GET['page'])  && $_POST['page']){
            $_GET['page'] = $_POST['page'];
        }
        import('@.ORG.system_page');
        $count = M('User_level_activation_code_log')->where($code_where)->count();

        $p = new Page($count,$pageSize,'page');

        $list = M('User_level_activation_code_log')->field($field)->where($code_where)->order($order)->limit($p->firstRow,$p->listRows)->select();

        if ($list) {
            foreach ($list as &$val) {
                if (($val['log_type'] == 6 || $val['log_type'] == 1) && $val['log_info']) {
                    $val['code_info'] = $val['log_info'];
                } elseif ($val['code_id']>0) {
                    $val['code_info'] = $this->where(array('code_id' => $val['code_id']))->getField('activation_code');
                }
            }
        }


        $pagebar = $p->show();
        return array('list'=>$list,'pagebar'=>$pagebar, 'log_type' => $this->log_type);
    }



}
?>