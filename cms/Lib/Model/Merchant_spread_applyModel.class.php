<?php

class Merchant_spread_applyModel extends Model
{
    public function getLists($get)
    {
        $where = [];
        if (isset($get['handle_status'])) {
            $where['r.handle_status'] = $get['handle_status'];
        }
        if ($get['type'] > 0) {
            $where['r.type'] = $get['type'];
        }
        if (isset($get['is_del'])) {
            $where['r.is_del'] = $get['is_del'];
        }
        $keyword = trim($get['keyword']);
        $searchtype = trim($get['searchtype']);
        if ($keyword) {
            $where['u.' . $searchtype] = ['LIKE', '%' . $keyword . '%'];
        }
        import('@.ORG.system_page');
        $join = 'LEFT JOIN '.C('DB_PREFIX') . 'user AS u On u.uid=r.uid ' .'LEFT JOIN '.C('DB_PREFIX') . 'merchant_spread_user AS su On su.uid=r.uid';
        $fields = 'r.*,u.nickname,u.phone,su.commission';
        $count = $this->alias('r')->join($join)->where($where)->count();
        $p = new Page($count, 20);
        $lists = $this->alias('r')->join($join)->where($where)->field($fields)->order('r.order_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $userMod = M('Merchant_spread_user');
        $inviteMod = M('Merchant_spread_invite_record');
        foreach ($lists as $k => $v) {
            $lists[$k]['spread_money'] = $userMod->where(['uid' => $v['uid']])->getField('spread_money');
            $lists[$k]['spread_vip_count'] = $inviteMod->where(['uid' => $v['uid'], 'type' => 1])->count();
            $lists[$k]['spread_city_count'] = $inviteMod->where(['uid' => $v['uid'], 'type' => 2])->count();
        }

        $rs = [
            'pagebar' => $p->show(),
            'lists' => $lists
        ];
        return $rs;
    }

    public function getOne($id)
    {
        $join = C('DB_PREFIX') . 'user AS u On u.uid=r.uid';
        $fields = 'r.*,u.nickname,u.phone';
        $info = $this->alias('r')->join($join)->field($fields)->where(['r.id' => $id])->find();
        if ($info['form_data']) {
            $info['form_data'] = $info['form_data'] ? unserialize($info['form_data']) : [];
        }
        !is_array($info['form_data']) && $info['form_data'] = [];
        return $info;
    }

    public function isApplyingVipSpread($uid)
    {
        $apply = $this->where(['uid' => $uid, 'type' => 1, 'handle_status' => 0])->find();
        return $apply ? true : false;
    }

    public function isApplyingCitySpread($uid)
    {
        $apply = $this->where(['uid' => $uid, 'type' => 2, 'handle_status' => 0])->find();
        return $apply ? true : false;
    }


}