<?php
class Recruit_energy_packageModel extends Model
{
    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $order = '`sort` DESC,`package_id` DESC';
        $count = $this->where($condition)->count();
        if ($is_wap==1) {
            $_GET['page'] = intval($_GET['page']) ? intval($_GET['page']) : 1;
            $list = $this->field(true)->where($condition)->order($order)->limit(($_GET['page']-1)*$pageSize.','.$pageSize)->select();
        }else{
            import('@.ORG.system_page');
            $p = new Page($count,$pageSize,'page');
            $list = $this->field(true)->where($condition)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            $return['pagebar'] = $p->show();
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_GET['page'];
        $return['list'] = $list ? $list : array();
        return $return;
    }

    public function get_one($package_id)
    {
        $score_package = $this->field(true)->where(array('package_id' => $package_id))->find();
       
        return $score_package;
    }
}