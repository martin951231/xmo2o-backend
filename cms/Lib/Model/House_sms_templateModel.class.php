<?php
class House_sms_templateModel extends Model
{
    /* 短信模板信息列表 */
    public function house_sms_template_page($village_id, $pageSize = 20, $where = array())
    {
        if (!$village_id) {
            return null;
        }
        $return = array();
        $where['village_id'] = $village_id;
        if (!isset($where['status'])) {
            $where['status'] = array('neq', 4);
        }

        $count = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = $this->field(true)->where($where)->order('`add_time` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        if ($list) {
            $return['totalPage'] = ceil($count / $pageSize);
            $return['list_count'] = count($list);
            $return['count_user'] = $count;
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }

        return $return;
    }
}
?>