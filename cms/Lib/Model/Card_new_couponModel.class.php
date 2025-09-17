<?php

class Card_new_couponModel extends Model
{
    public function get_qrcode($id)
    {
        $condition_store['coupon_id'] = $id;
        $qrcode_id = M(_view('card_new_coupon'))->field('`coupon_id`,`qrcode_id`')->where($condition_store)->find();
        if (empty($qrcode_id)) {
            return false;
        }
        return $qrcode_id;
    }

    //保存优惠券二维码
    public function save_qrcode($id, $qrcode_id)
    {
        $coupon_where['coupon_id'] = $id;
        $data_coupon['qrcode_id'] = $qrcode_id;
        if ($this->where($coupon_where)->data($data_coupon)->save()) {
            return (array('error_code' => false));
        } else {
            return (array('error_code' => true, 'msg' => L_('保存二维码至平台优惠券失败！请重试。')));
        }
    }

    public function get_coupon($coupon_id,$use_lang = true)
    {
        $m = $use_lang ? _view('card_new_coupon') : 'card_new_coupon';
        $coupon = M($m)->field(true)->where(array('coupon_id' => $coupon_id))->find();
        return $coupon;
    }


    //根据已领表id获取优惠券信息,不筛选时间等条件 链表查询
    public function get_coupon_info($coupon_id)
    {
        $where['h.id'] = $coupon_id;
        $where['h.is_use'] = 0;
        $where['c.status'] = array('in', '1,3');

        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id=c.coupon_id')->field('h.id,c.coupon_id,c.name,c.des,c.des_detial,c.had_pull,c.num,c.limit,c.use_limit,c.order_money,c.discount ,c.discount as price')->where($where)->find();
        return $res;
    }

    //获取该手机号码领取的优惠券数量
    public function get_coupon_count_by_uid($coupon_id, $uid)
    {
        $where['coupon_id'] = $coupon_id;
        $where['uid'] = $uid;
        return M('Card_new_coupon_hadpull')->where($where)->count();

    }

    public function get_coupon_by_uid($uid, $mer_id)
    {
        $where['h.uid'] = $uid;
        $where['c.mer_id'] = $mer_id;
        $where['h.is_use'] = 0;
        $where['c.end_time'] = array('gt', time());
        $where['c.status'] = array('in', '1,3');
        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon as c ON h.coupon_id = c.coupon_id')->where($where)->select();
        return $res;
    }

    public function get_coupon_detail_by_uid($uid)
    {
        $where['uid'] = $uid;
        $res = M('Card_new_coupon_hadpull')->field('c.name,c.discount,c.start_time,c.end_time,c.num,c.des')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon as c ON h.coupon_id = c.coupon_id')->where($where)->select();
        return $res;
    }

    //获取该用户领取的优惠券
    public function get_coupon_category_by_uid($uid)
    {
        $where['uid'] = $uid;
        return M('Card_new_coupon_hadpull')->where($where)->group('coupon_id')->select();

    }

    public function get_coupon_by_id($id)
    {
        $where['c.end_time'] = array('gt', time());
        $where['c.status'] = array('neq', 0);  //状态正常
        $where['h.id'] = $id;
        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id=c.coupon_id')->field('h.id,c.coupon_id,c.end_time,h.is_use ,c.discount as price,c.discount')->where($where)->find();
        return $res;
    }

    //获取适用不同分类的优惠券 接口
    public function get_coupon_list_by_type_merid($type, $mer_id, $cat_id, $limit = 6, $is_new = -1)
    {
        if (!empty($type)) {
            $where['cate_name'] = array(array('eq', $type), array('eq', 'all'), 'or');
        }
        if ($is_new != -1) {
            $where['allow_new'] = $is_new;
        }
        $where['end_time'] = array('gt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 0;
        $where['only_assign'] = 0;
        $where['is_live'] = 0;
        $where['mer_id'] = $mer_id;
        $where['start_time'] = array('lt', time());
        $where['_string'] = 'had_pull<>num';
        $res = M(_view('card_new_coupon'))->where($where)->order('allow_new DESC,discount DESC')->limit($limit)->getField('coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,limit,wx_cardid,jsapi_ticket,wx_ticket_addtime,cardsign');

        foreach ($res as $key => &$v) {
            $v['cate_id'] = unserialize($v['cate_id']);
            if ($cat_id != 0) {
                if ($v['cate_id']['cat_id'] != $cat_id) {
                    unset($res[$key]);
                    continue;
                }
            }
            $v['url'] = C('config.config_site_url') . '/coupon/' . $v['coupon_id'] . '.html';
        }
        return $res;
    }

    //获取可以领取的优惠券种类
    public function get_coupon_list($param = array())
    {
        $where['end_time'] = array('gt', time());
        $where['status'] = 1;
        $where['start_time'] = array('lt', time());
        if ($param) {
            $where = array_merge($where, $param);
        }
        $res = M(_view('card_new_coupon'))->where($where)->order('status ASC,allow_new DESC,discount DESC')
            ->getField('coupon_id,name,img,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new');
        return $res;
    }

    //根据商家id获取可领取的优惠券  $isReceiveFullReduction 是否需要判断“是否只允许满减时使用”，只修改用到的地方为true,其他方法调用保持原来的判断
    public function get_coupon_list_by_merid($mer_id = 0, $type = 0, $isSendByMer = false,$isReceiveFullReduction = false)
    {
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 0;
        if (!$isSendByMer) {
            $where['only_assign'] = 0;
        }
        $where['is_live'] = 0;
        if(!$isReceiveFullReduction){
            $where['is_receive_full_reduction'] = 0;
        }
        $where['send_type'] = array('neq', 2);
        $mer_id && $where['mer_id'] = $mer_id;
        if ($type) {
            $where['_string'] = 'num > had_pull';
        }
        if ($mer_id) {
            $res = M(_view('card_new_coupon'))->where($where)->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
                ->getField('coupon_id,coupon_url,mer_id,name,platform,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,last_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign,limit,store_id');
        } else {
            unset($where['status']);
            $where['c.status'] = 1;
//            $where['cn.auto_get'] = 1;
//            $res = $this->join('as c LEFT JOIN '.C('DB_PREFIX').'merchant as m ON m.mer_id=c.mer_id LEFT JOIN '.C('DB_PREFIX').'card_new cn ON cn.mer_id = m.mer_id')->where($where)->order('c.status ASC,c.allow_new DESC,c.discount DESC,c.add_time DESC')
//                ->getField('c.coupon_id,c.mer_id,c.name,c.platform,c.img,c.had_pull,c.num,c.des,c.cate_name,c.cate_id,c.discount,c.order_money,c.start_time,c.end_time,c.last_time,c.status,c.allow_new,c.wx_cardid,c.wx_ticket_addtime,c.cardsign,c.limit,m.name as merchant_name');
            $res = M(_view('card_new_coupon'))->join('as c LEFT JOIN ' . C('DB_PREFIX') . 'merchant as m ON m.mer_id=c.mer_id AND m.status=1 ')->where($where)->order('c.status ASC,c.allow_new DESC,c.discount DESC,c.add_time DESC')
                ->getField('c.coupon_id,c.coupon_url,c.mer_id,c.name,c.platform,c.img,c.had_pull,c.num,c.des,c.cate_name,c.cate_id,c.discount,c.order_money,c.start_time,c.end_time,c.last_time,c.status,c.allow_new,c.wx_cardid,c.wx_ticket_addtime,c.cardsign,c.limit,m.name as merchant_name,c.store_id');
        }
        return $res;
    }

    //根据优惠券id 获取可领取的优惠券
    public function get_coupon_list_by_coupon_id($coupon_id = '', $mer_id = 0, $type = 0, $where = [])
    {
        if(!empty($coupon_id)) {
            $where['coupon_id'] = $coupon_id;
        }
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 0;
        $where['is_live'] = 0;
        $where['only_assign'] = 0;
        $where['is_receive_full_reduction'] = 0;
        $where['send_type'] = array('neq', 2);
        $mer_id && $where['mer_id'] = $mer_id;
        if ($type) {
            $where['_string'] = 'num > had_pull';
        }
        if ($mer_id) {
            $res = M(_view('card_new_coupon'))->where($where)->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
                ->getField('coupon_id,coupon_url,mer_id,name,platform,img,had_pull,num,des,cate_name,cate_id,is_discount,discount,order_money,start_time,end_time,last_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign,limit,store_id');
        } else {
            unset($where['status']);
            $where['c.status'] = 1;
//            $where['cn.auto_get'] = 1;
//            $res = $this->join('as c LEFT JOIN '.C('DB_PREFIX').'merchant as m ON m.mer_id=c.mer_id LEFT JOIN '.C('DB_PREFIX').'card_new cn ON cn.mer_id = m.mer_id')->where($where)->order('c.status ASC,c.allow_new DESC,c.discount DESC,c.add_time DESC')
//                ->getField('c.coupon_id,c.mer_id,c.name,c.platform,c.img,c.had_pull,c.num,c.des,c.cate_name,c.cate_id,c.discount,c.order_money,c.start_time,c.end_time,c.last_time,c.status,c.allow_new,c.wx_cardid,c.wx_ticket_addtime,c.cardsign,c.limit,m.name as merchant_name');
            $res = M(_view('card_new_coupon'))->join('as c LEFT JOIN ' . C('DB_PREFIX') . 'merchant as m ON m.mer_id=c.mer_id AND m.status=1 ')->where($where)->order('c.status ASC,c.allow_new DESC,c.discount DESC,c.add_time DESC')
                ->getField('c.coupon_id,c.coupon_url,c.mer_id,c.name,c.platform,c.img,c.had_pull,c.num,c.des,c.cate_name,c.cate_id,c.discount,c.order_money,c.start_time,c.end_time,c.last_time,c.status,c.allow_new,c.wx_cardid,c.wx_ticket_addtime,c.cardsign,c.limit,m.name as merchant_name,c.store_id');
        }
        return $res;
    }
	
	//根据商家id获取可领取的优惠券
    public function get_coupon_list_by_merid_page($mer_id,$page = 1,$pagesize = 10)
    {
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        //$where['_string'] = 'num > had_pull';

        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }

        $count = $this->where($where)->count();

        $res = M(_view('card_new_coupon'))
            ->where($where)
            ->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
            ->limit(($page - 1) * $pagesize . ',' . $pagesize)
            ->getField('coupon_id,name,platform,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign');

        return array('coupon_list' => $res, 'coupon_count' => $count);
    }
	
    //根据商家id获取可领取的优惠券
    public function get_coupon_list_by_merid_diypage($mer_id)
    {
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 0;
        $where['is_live'] = 0;
        $where['only_assign'] = 0;
        $where['mer_id'] = $mer_id;
        //$where['_string'] = 'num > had_pull';

        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }

        $count = $this->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 8);

        $res = M(_view('card_new_coupon'))
            ->where($where)
            ->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->getField('coupon_id,name,platform,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign');

        return array('coupon_list' => $res, 'page_bar' => $Page->show(), 'coupon_count' => $count);
    }

    public function get_coupon_list_by_merid_and_auto_get($mer_id, $num = 0)
    {
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = array('neq', 0);
        $where['auto_get'] = 1;
        $where['mer_id'] = $mer_id;
        //$where['_string'] = 'num > had_pull';
        $res = M(_view('card_new_coupon'))->where($where)->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
            ->getField('coupon_id,name,platform,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign');
        return $res;
    }

    public function get_coupon_list_by_ids($ids, $can_hadpull = false)
    {
        $where['coupon_id'] = array('in', $ids);
        $res = M(_view('card_new_coupon'))->where($where)->getField('coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new');

        foreach ($res as $key => &$v) {
            if ($can_hadpull) {
                if ($v['start_time'] > time() || $v['end_time'] < time() || $v['status'] == 0) {
                    unset($res[$key]);
                }
            }
            $v['order_money_txt'] = getFormatNumber($v['order_money']);
            $v['discount_txt'] = getFormatNumber($v['discount']);
            $v['img'] = C('config.site_url') . $v['img'];
            $v['start_time_txt'] = date('Y.m.d', $v['start_time']);
            $v['end_time_txt'] = date('Y.m.d', $v['end_time']);
        }

        return $res;
    }

    public function get_user_coupon_list($uid, $mer_id, $type = '', $order_id = 0)
    {
        //$where['c.end_time'] = array('gt',time());
        $where['h.uid'] = $uid;
        $where['c.mer_id'] = $mer_id;
        $where['c.is_shop'] = array('neq', 2);//仅店内
        $where['_string'] = '(c.status <> 0 AND c.status<>4)';   //状态正常
        // $where['h.is_use'] = 0;
        $n = 1;
        $cate_platform = $this->cate_platform();
        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id=c.coupon_id')->field('h.id,h.is_use,c.cate_name as type,c.*')->order('h.is_use ASC ,c.add_time DESC')->where($where)->select();
        foreach ($res as &$v) {
            if (empty($v['uid'])) {
                M('Card_new_coupon_hadpull')->where(array('id' => $v['id']))->setField('uid', $uid);
            }
            if ($v['is_use'] == 1 && $n == 1) {
                $v['line'] = 1;
                $n++;
            }
            $v['platform'] = unserialize($v['platform']);
            if (!empty($v['cate_id'])) {
                $v['cate_id'] = unserialize($v['cate_id']);
            }
            foreach ($v['platform'] as &$vv) {
                $vv = $cate_platform['platform'][$vv];
            }
            if ($v['cate_name'] != 'all') {
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
            }
            $v['platform'] = trim(implode(',', $v['platform']), ',');
            if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                $v['is_use'] = 2;
            }
        }
        return $res;
    }

    public function get_user_all_coupon_list($uid, $is_use = '', $type = '', $mer_id = 0)
    {
        //$where['c.end_time'] = array('gt',time());
//        $where['c.status'] = array('neq',0);
        $where['h.uid'] = $uid;
        if (in_array($type, ['scenic', 'stadium', 'course'])) {
            $type && $where['c.cate_name'] = ['in', ['all', 'allin', $type]];
            $mer_id && $where['c.mer_id'] = $mer_id;
        } else {
            $type && $where['c.cate_name'] = $type;
        }
        $where['_string'] = '(c.status <> 0 AND c.status<>4)';   //状态正常
        if (!empty($is_use)) {
            if ($is_use == 1) {
                $where['h.is_use'] = 0;
                $where['c.end_time'] = array('gt', time());
            }
        }
        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon as c ON  h.coupon_id = c.coupon_id')
            ->where($where)->order('h.is_use ASC ,c.add_time DESC')->select();
        $n = 1;
        $cate_platform = $this->cate_platform();
        foreach ($res as &$v) {
            if ($v['is_use'] == 1 && $n == 1) {
                $v['line'] = 1;
                $n++;
            }

            //合并商家+平台优惠券
            $v['coupon_type'] = 'mer'; //商家券 标识
            $v['coupon_type_name'] = '商家券'; //标识名称

            $v['type'] = $v['cate_name'];
            $v['platform_s'] = $v['platform'];
            $v['platform'] = unserialize($v['platform']);
            if (!empty($v['cate_id'])) {
                $v['cate_id'] = unserialize($v['cate_id']);
            }
            foreach ($v['platform'] as &$vv) {
                $vv = $cate_platform['platform'][$vv];
            }
            $v['cate_name_s'] = $v['cate_name'];
            if ($v['cate_name'] != 'all') {
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
            }
            $v['platform'] = trim(implode(',', $v['platform']), ',');
            if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                $v['is_use'] = 2;
            }
        }
        return $res;
    }

    //优惠券改版
    public function get_user_all_coupon_list_where($uid, $param = [],$is_use = '', $type = '')
    {

        switch ($param['status']) {
            case 1:
                $where['h.is_use'] = 0;   //未使用
                $where['c.end_time'] = array('gt', time());
                $sort = 'c.end_time ASC,c.coupon_id ASC';
                break;
            case 2:
                $where['h.is_use'] = 1;   //已使用
                $sort = 'h.use_time DESC,c.coupon_id ASC';
                break;
            case 3:
                $where['c.status|h.is_use'] = 2;  //过期或者不能使用
                $where['h.is_use'] = ['neq', 1];
                $sort = 'c.end_time DESC,c.coupon_id ASC';
                break;
            default:
                $where['h.is_use'] = 0;   //未使用
                $where['c.end_time'] = array('gt', time());
                $sort = 'c.end_time ASC,c.coupon_id ASC';
        }

        $param['mer_id'] && $where['c.mer_id'] = $param['mer_id'];
        if(!empty($param['mer_id'])) {
            $where['c.is_shop'] = array('neq', 2);//仅店内
        }

        if($param['hadpull_ids']){
            $where['h.id'] = array('in', $param['hadpull_ids']);
        }

        $where['h.uid'] = $uid;
        $type && $where['c.cate_name'] = $type;
        $where['_string'] = '(c.status <> 0 AND c.status<>4)';   //状态正常

        $res = M('Card_new_coupon_hadpull')->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon as c ON  h.coupon_id = c.coupon_id')
            ->where($where)->order($sort)->select();

        $n = 1;
        $cate_platform = $this->cate_platform();
        foreach ($res as &$v) {
            if ($v['is_use'] == 1 && $n == 1) {
                $v['line'] = 1;
                $n++;
            }

            //合并商家+平台优惠券
            $v['coupon_type'] = 'mer'; //商家券 标识
            $v['coupon_type_name'] = '商家券'; //标识名称

            $v['type'] = $v['cate_name'];
            $v['platform_s'] = $v['platform'];
            $v['platform'] = unserialize($v['platform']);
            if (!empty($v['cate_id'])) {
                $v['cate_id'] = unserialize($v['cate_id']);
            }
            foreach ($v['platform'] as &$vv) {
                $vv = $cate_platform['platform'][$vv];
            }
            $v['cate_name_s'] = $v['cate_name'];
//            if ($v['cate_name'] != 'all') {
//                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
//            }
            $v['platform'] = trim(implode(',', $v['platform']), ',');
            if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                $v['is_use'] = 2;
            }
        }
        return $res;
    }


    //获取当前能用的优惠券列表
    public function get_noworder_coupon_list($now_order, $order_type, $platform, $business_type = '')
    {
        $order_detail_goods = [];
        if($order_type == 'mall' || $order_type == 'shop'){//商城和外卖的订单， 筛选商家优惠券不能用can_discount_money，因为增加了优惠券指定商品功能
            $order_detail_goods = array_column(M("Shop_order_detail")->where(['order_id' => $now_order['order_id']])->field("goods_id,price*num as allprice")->select(), "allprice", 'goods_id');
            $no_total_money = true;
        }
        if (empty($order_detail_goods) && $now_order['goods']) {
            foreach ($now_order['goods'] as $v) {
                if (isset($order_detail_goods[$v['goods_id']])) {
                    $order_detail_goods[$v['goods_id']] += $v['discount_price'] * $v['num'];
                } else {
                    $order_detail_goods[$v['goods_id']] = $v['discount_price'] * $v['num'];
                }
            }
        }

        if ($business_type) {
            $order_type = $business_type;
            $now_order['total_money'] = $now_order['total_money'];
        }
        if ($order_type == 'meal' && $now_order['can_discount_money'] > 0) {
            $now_order['total_money'] = $now_order['can_discount_money'];
        }

//        if(!D('Card_new')->where(array('mer_id'=>$now_order['mer_id'],'status'=>1))->find()){
//            return array();
//        }
        if ($order_type == 'group') {
            $table = 'group';
        } else if ($order_type == 'meal' || $order_type == 'food' || $order_type == 'foodPad' || $order_type == 'takeout' || $order_type == 'foodshop') {
            $table = 'meal';
        } else if ($order_type == 'appoint') {
            $table = 'appoint';
        } else if ($order_type == 'shop') {
            $table = 'shop';
        } else if ($order_type == 'mall') {
            $table = 'mall';
        } else if ($order_type == 'store') {
            $table = 'store';
        } else if ($order_type == 'balance-appoint') {
            $order_type = $table = 'appoint';
            $order_detail_goods[$now_order['appoint_id']] = $now_order['order_total_money'];
        } else {
            return array();
        }

        $where['order_money'] = array('ELT', $now_order['total_money']);
        if(!$no_total_money){
            $where['order_money'] = array('ELT', $now_order['total_money']);
        }
        if ($order_type != 'store' && $order_type != 'mall' && $order_type != 'appoint_new') {
            $order_cate = D(ucfirst($table) . '_order')->get_order_cate($now_order['order_id']);
        } else if ($order_type == 'mall') {
            $order_cate = array('mall');
        } else if ($order_type == 'appoint_new') {
            $order_cate = D('Appoint_category')->field('cat_id,cat_name')->where(array('cat_id'=>$now_order['cat_id']))->find();
        } else {
            $order_cate = array('store');
        }

        $where['c.end_time'] = array('gt', time());
        $where['c.start_time'] = array('lt', time());
        $where['c.status'] = array('in', '1,3');  //状态正常
        $where['h.is_use'] = 0;  //状态正常

        $where['h.uid'] = $now_order['uid'];
        $where['c.mer_id'] = $now_order['mer_id'];

        $card = D('Card_new')->where(array('mer_id' => $now_order['mer_id'], 'status' => 1))->find();
        $usercard = D('Card_userlist')->where(array('mer_id' => $now_order['mer_id'],'uid'=>$now_order['uid'], 'status' => 1 ))->find();
        if ($card && $usercard) {
            $where['l.status'] = 1;  //状态正常
            $where['l.mer_id'] = $now_order['mer_id'];  //状态正常
            $where['_string'] = "((c.cate_name='" . $table . "') OR (c.cate_name ='all')) AND (c.use_with_card=1 OR ( c.use_with_card=0 AND (cn.discount=0 OR cn.discount =10) ))";

        } else {
            $where['_string'] = "((c.cate_name='" . $table . "') OR (c.cate_name ='all')) ";

        }
        if (intval($now_order['store_id'])) {
            $where['_string'] .= " AND (c.store_id ='' OR c.store_id LIKE '%" . intval($now_order['store_id']) . "%')  ";
        }
        if ($card && $usercard) {
            $join_info = ' as h LEFT JOIN '.C('DB_PREFIX').'card_new_coupon as c ON h.coupon_id=c.coupon_id LEFT JOIN '.C('DB_PREFIX').'card_userlist as l ON h.uid = l.uid LEFT JOIN '.C('DB_PREFIX').'card_new as cn ON cn.mer_id = l.mer_id';

            $res = M('Card_new_coupon_hadpull')->join($join_info)->field('h.id,c.*')
                ->where($where)
                ->group('h.id')
                ->order('h.is_use ASC,c.discount DESC ,c.add_time DESC')
                ->select();

        } else {

            $res = M('Card_new_coupon_hadpull')
                ->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id=c.coupon_id')
//                ->join(C('DB_PREFIX').'card_userlist l ON h.uid = l.uid ')
//            ->join( C('DB_PREFIX').'card_new as cn ON cn.mer_id = l.mer_id')
                ->field('h.id,c.*')->where($where)
                ->group('h.id')
                ->order('h.is_use ASC,c.discount DESC ,c.add_time DESC')->select();

        }

        $cate_platform = $this->cate_platform();
        foreach ($res as $key => &$v) {
            $v['cate_name_s'] = $v['cate_name'];

            if($v['point_goodids']){//如果设置了指定商品， 则走指定商品的逻辑
                $point_goodids = unserialize($v['point_goodids']);
                if($point_goodids[$order_type]){
                    $order_money = 0;
                    foreach ($order_detail_goods as $_gk => $_g) {
                        if(in_array($_gk, $point_goodids[$order_type])){
                            $order_money += $_g;
                        }
                    }
                    if($v['order_money'] > $order_money || $order_money == 0){
                        unset($res[$key]);
                        continue;
                    }
                }
                else{
                    //if($v['order_money'] > $now_order['total_money']){
                        unset($res[$key]);
                        continue;
                    //}
                }
            }
            else{
                if($v['order_money'] > $now_order['total_money']){
                    unset($res[$key]);
                    continue;
                }
            }

            $flag = false;
            $v['platform_s'] = $v['platform'];
            $v['platform'] = unserialize($v['platform']);
            $v['cate_id'] = empty($v['cate_id']) ? '0' : unserialize($v['cate_id']);
            $store_arr = explode(',', $v['store_id']);
            if ($v['store_id'] != '' && !in_array($now_order['store_id'], $store_arr)) {
                unset($res[$key]);
                continue;
            }

            // 小程序原生支付不能使用优惠券 临时解决方案
            if ($platform=='wxapp') {
                $platform = 'weixin';
            }

            foreach ($v['platform'] as $vp) {
                if ($vp == $platform) {
                    $flag = true;
                }
            }
            if (!$flag) {
                unset($res[$key]);
                continue;
            }
            if (!empty($v['cate_id'])) {
                $cate_arr1 = array_diff($v['cate_id'], $order_cate);
                $cate_arr2 = array_diff($v['cate_id'], $order_cate);
                if((!empty($cate_arr1) || !empty($cate_arr2) ) && $v['cate_id']['cat_id'] != $order_cate['cat_id']){
                    unset($res[$key]);
                    continue;
                }
            }

            foreach ($v['platform'] as &$vv) {
                $vv = $cate_platform['platform'][$vv];
            }
            if ($v['cate_name'] != 'all') {
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
            }
            $v['platform'] = trim(implode(',', $v['platform']), ',');
        }

        //过滤掉不可用的
        $result = D('System_coupon')->getCanUseCoupon($now_order, $res, $order_type, $platform, 'mer', $order_detail_goods);
        if(empty($result['can_use_coupon_list'])){
            $res = [];
        }else{
            $canUseHadIds = array_column($result['can_use_coupon_list'], 'had_id');
            foreach ($res as $k => $v) {
                if (!in_array($v['id'], $canUseHadIds)) {
                    unset($res[$k]);
                }
            }
        }
        return $res;
    }

    public function cate_platform()
    {
        $category = array(
            'new_user' => L_('新人券'),
            'group' => C('config.group_alias_name').'券',
            'meal' => C('config.meal_alias_name').'券',
            'appoint' => C('config.appoint_alias_name').'券',
            'shop' => C('config.shop_alias_name').'券',
            'store' => C('config.cash_alias_name').'券',
            'mall' => C('config.mall_alias_name').'券',
            'village_group'=>C('config.village_group_alias').'券',
            'course' => '课程券',
            'stadium' => '场馆券',
            'all' => L_('通用券'),
            //'allin' => L_('全部'),
        );

        if(!C('customization.life_tools')){
            unset($category['course']);
            unset($category['stadium']);
        }

        $sys_where['status'] = ['neq', 2];
//        $sys_where['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where['cate_name'] = 'group';
        $sys_res = M('System_coupon')->field(true)->where($sys_where)->find();

//        $mer_where['last_time'] = ['egt',$_SERVER['REQUEST_TIME']-86400];
        $mer_where['cate_name'] = 'group';
        $mer_res = M('Card_new_coupon')->field(true)->where($mer_where)->find();
        if (!C('config.group_alias_name')) {
            unset($category['group']);
        }

        $sys_where_meal['status'] = ['neq', 2];
//        $sys_where_meal['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_meal['cate_name'] = 'meal';
        $sys_res_meal = M('System_coupon')->field(true)->where($sys_where_meal)->find();

//        $mer_where_meal['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_meal['cate_name'] = 'meal';
        $mer_res_meal = M('Card_new_coupon')->field(true)->where($mer_where_meal)->find();
        if (!C('config.meal_alias_name')) {
            unset($category['meal']);
        }

        $sys_where_appoint['status'] = ['neq', 2];
//        $sys_where_appoint['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_appoint['cate_name'] = 'appoint';
        $sys_res_appoint = M('System_coupon')->field(true)->where($sys_where_appoint)->find();

//        $mer_where_appoint['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_appoint['cate_name'] = 'appoint';
        $mer_res_appoint = M('Card_new_coupon')->field(true)->where($mer_where_appoint)->find();
        if (!C('config.appoint_alias_name')) {
            unset($category['appoint']);
        } 

        $sys_where_shop['status'] = ['neq', 2];
//        $sys_where_shop['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_shop['cate_name'] = 'shop';
        $sys_res_shop = M('System_coupon')->field(true)->where($sys_where_shop)->find();

//        $mer_where_shop['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_shop['cate_name'] = 'shop';
        $mer_res_shop = M('Card_new_coupon')->field(true)->where($mer_where_shop)->find();
        if (!C('config.shop_alias_name')) {
            unset($category['shop']);
        }

        $sys_where_store['status'] = ['neq', 2];
//        $sys_where_store['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_store['cate_name'] = 'store';
        $sys_res_store = M('System_coupon')->field(true)->where($sys_where_store)->find();

//        $mer_where_store['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_store['cate_name'] = 'store';
        $mer_res_store = M('Card_new_coupon')->field(true)->where($mer_where_store)->find();
        if (!C('config.cash_alias_name')) {
            unset($category['store']);
        }

        $sys_where_mall['status'] = ['neq', 2];
//        $sys_where_mall['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_mall['cate_name'] = 'mall';
        $sys_res_mall = M('System_coupon')->field(true)->where($sys_where_mall)->find();

//        $mer_where_mall['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_mall['cate_name'] = 'mall';
        $mer_res_mall = M('Card_new_coupon')->field(true)->where($mer_where_mall)->find();
        if (!C('config.mall_alias_name')) {
            unset($category['mall']);
        }


        $sys_where_all['status'] = ['neq', 2];
//        $sys_where_all['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $sys_where_all['cate_name'] = 'all';
        $sys_res_all = M('System_coupon')->field(true)->where($sys_where_all)->find();

//        $mer_where_all['last_time'] = ['egt', $_SERVER['REQUEST_TIME']-86400];
        $mer_where_all['cate_name'] = 'all';
        $mer_res_all = M('Card_new_coupon')->field(true)->where($mer_where_all)->find();
        if (C('config.single_system')) {
            unset($category['all']);
        }


//        if (!C('config.shop_alias_name')) {
//            unset($category['shop']);
//        }
//        if (!C('config.cash_alias_name')) {
//            unset($category['store']);
//        }
//        if (!C('config.mall_alias_name')) {
//            unset($category['mall']);
//        }

        if (C('config.wxapp_url')) {
            $category['wxapp'] = L_('微信营销券');
        }

//        if (C('config.single_system')) {
//            unset($category['all']);
//        }
        $color_list = PD('System_coupon')->color_list();
        $platform = array('wap' => L_('移动网页'), 'app' => 'App', 'weixin' => L_('微信'), 'wxapp' => L_('小程序'));
        //dump($category);exit;

        return array('category' => $category, 'platform' => $platform, 'color_list' => $color_list);
    }

    //检查平台优惠券状态
    public function check_coupon($record_id, $mer_id, $uid, $refund = false)
    {
        $now_merchant = M('Merchant')->field(true)->where(array('mer_id' => $mer_id, 'status' => '1'))->find();
        if (empty($now_merchant)) {
            return array('error_code' => 1, 'msg' => L_('商家暂时歇业'));
        }
        $condition_coupon_record = array('id' => $record_id, 'wecha_id' => $uid);
        if (empty($refund)) {
            $condition_coupon_record['is_use'] = '0';
        } else {
            $condition_coupon_record['is_use'] = '1';
        }
        $now_coupon_record = M("Card_new_coupon_hadpull")->field(true)->where($condition_coupon_record)->find();
        if (empty($now_coupon_record)) {
            return array('error_code' => 1, 'msg' => L_('优惠券不可用'));
        }
        $now = time();
        $now_coupon = M(_view('card_new_coupon'))->field(true)->where(array('coupon_id' => $now_coupon_record['coupon_id'], 'start_time' => array('lt', $now), 'end_time' => array('gt', $now)))->find();
        if (empty($now_coupon_record)) {
            return array('error_code' => 1, 'msg' => L_('优惠券已被商家取消了'));
        }
        return array('error_code' => 0, 'msg' => L_('优惠券可用'), 'coupon' => $now_coupon);
    }

    //消费平台优惠券记录在表中 record_id 是 hadpull 表中的id
    public function user_coupon($record_id, $order_id, $order_type, $mer_id, $uid)
    {
        $result = $this->check_coupon($record_id, $mer_id, $uid);
        if ($result['error_code']) {
            return $result;
        }
        $now = time();
        $result_ = M("Card_new_coupon_hadpull")->where(array('id' => $record_id))->save(array('use_time' => $now, 'is_use' => '1'));
        fdump_sql([$_SERVER['REQUEST_URI'], func_get_args(), M("Card_new_coupon_hadpull")->getLastSql(), M("Card_new_coupon_hadpull")->getDbError(), $result_], 'use_coupon');
        if (empty($result_)) {
            return array('error_code' => 1, 'msg' => L_('优惠券使用失败'));
        }

        $now_coupon_record = M("Card_new_coupon_hadpull")->field(true)->where(array('id' => $record_id))->find();

        if ($result['coupon']['is_wx_card']) {
            import('ORG.Net.Http');
            $mode = D('Access_token_expires');
            $res = $mode->get_access_token();
            $wx_date['code'] = $now_coupon_record['wx_card_code'];
            $return = httpRequest('https://api.weixin.qq.com/card/code/consume?access_token=' . $res['access_token'], 'post', json_encode($wx_date, JSON_UNESCAPED_UNICODE));
            $return = json_decode($return[1], true);

        }
        $arr = array();
        $arr['coupon_id'] = $now_coupon_record['coupon_id'];
        $arr['order_type'] = $order_type;
        $arr['order_id'] = $order_id;
        $arr['hadpull_id'] = $record_id;
        $arr['uid'] = $uid;
        $arr['num'] = 1;
        $arr['use_time'] = $now;

        M('Card_new_coupon_use_list')->add($arr);
        return array('error_code' => 0, 'msg' => L_('优惠券使用成功'));
    }
	
	//订单状态：已支付店员未发货，取消订单会返还给用户优惠券 tapd_story_1001389/2019-11-08
	public function return_coupon($record_id, $order_id, $order_type, $mer_id, $uid){
		$now = time();
        $result_ = M("Card_new_coupon_hadpull")->where(array('id' => $record_id))->save(array('is_use' => '0'));
		$now_coupon_record = M("Card_new_coupon_hadpull")->field(true)->where(array('id' => $record_id))->find();
		$arr = array();
        $arr['coupon_id'] = $now_coupon_record['coupon_id'];
        $arr['order_type'] = $order_type;
        $arr['order_id'] = $order_id;
        $arr['hadpull_id'] = $record_id;
        $arr['uid'] = $uid;
        $arr['num'] = 1;
        $arr['use_time'] = $now;
		$arr['type'] = 1;
        M('Card_new_coupon_use_list')->add($arr);
        return array('error_code' => 0, 'msg' => L_('优惠券返还成功'));
		
	}

    //领取方法 error_code :1=>优惠券不存在 2 =>过期，3 领完了 4只允许新用户 5不能在领取了
    // is_auto 是否无视自动领卡设置
    // is_assign 是否后台手动指派  true:是  false:否
    //领取数量 $reduce_num
    public function had_pull($coupon_id, $uid, $card_code = '', $not_wxapp_hadpull = false, $is_auto = false, $get_all = 0, $muti_hadpull = false, $is_system_send = 0,$is_assign = false,$reduce_num = 1)
    {
        $where['coupon_id'] = $coupon_id;
        $coupon = $this->get_coupon($coupon_id,false);
        $is_new = PD('User')->check_new($uid, $coupon['cate_name']);

        if (empty($coupon)) {
            return array('error_code' => 1, 'coupon' => $coupon);
        } else if ($coupon['allow_new'] && !$is_new) {
            return array('error_code' => 4, 'coupon' => $coupon);
        } else if ($coupon['end_time'] < time()) {
            if ($coupon['status'] != 4) {
                $this->field(true)->where($where)->setField('status', 2);
            }
            return array('error_code' => 2, 'end_time' => $coupon, 'coupon' => $coupon);
        } else if ($coupon['status'] == 0) {
            return array('error_code' => 1, 'coupon' => $coupon);
        } else if ($coupon['status'] == 2) {
            return array('error_code' => 2, 'coupon' => $coupon);
        } else if ($coupon['num'] <= $coupon['had_pull'] || $coupon['status'] == 3) {
            if ($coupon['status'] != 4) {
                $this->field(true)->where($where)->setField('status', 3);
            }
            return array('error_code' => 3, 'coupon' => $coupon);
        } else {
            $hadpull = M('Card_new_coupon_hadpull');
            $hadpull_count = $hadpull->where(array('uid' => $uid, 'coupon_id' => $coupon_id))->count();

            //判断优惠券是否必须手动派发，不能用户主动领取
            if ($coupon['only_assign'] == 1 && !$is_assign) {
                return array('error_code' => 1, 'coupon' => $coupon);
            }

            if ($hadpull_count < $coupon['limit']) {
                if ($not_wxapp_hadpull && $coupon['wx_cardid']) {
                    return array('error_code' => 0, 'coupon' => $coupon);
                }
                //领取全部
                if ($get_all == 1) {
                    //实际可领取的数量
                    $receive_num = $coupon['limit'] - $hadpull_count;
                    //剩余的数量
                    $after_num = $coupon['num'] - $coupon['had_pull'];
                    $reduce_num = min($receive_num, $after_num);
                }

                $where['_string'] = 'had_pull+' . $reduce_num . ' <= num';
                if ($this->where($where)->setInc('had_pull', $reduce_num)) {
                    $this->where($where)->setField('last_time', $_SERVER['REQUEST_TIME']);
                    $data_all = array();
                    for ($i = 0; $i < $reduce_num; $i++) {
                        $data = array();
                        $data['coupon_id'] = $coupon_id;
                        $data['uid'] = $uid;
                        $data['num'] = 1;
                        $data['receive_time'] = time();
                        $data['status'] = 0;
                        if ($card_code) {
                            $data['wx_card_code'] = $card_code;
                        }
                        $data['uid'] = $uid;
                        $data_all[] = $data;
                    }
                    
                    $hadpull_count = $hadpull->where(array('uid' => $uid, 'coupon_id' => $coupon_id))->count();
                    if ($hadpull_count >= $coupon['limit']) {
                        return array('error_code' => 0, 'coupon' => $coupon);
                    }

                    //插入之前先查一遍
                    $begin_coupon = $this->get_coupon($coupon_id,false);
                    if ($begin_coupon['num'] >= $begin_coupon['had_pull'] && $hadpull_id = $hadpull->addAll($data_all)) {
                        if (!M('Card_userlist')->where(array('mer_id' => $begin_coupon['mer_id'], 'uid' => $uid))->find()) {
                            $result = PD('Card_new')->auto_get($uid, $begin_coupon['mer_id'], '', $is_auto);
                            //return array('error_code'=>1,'coupon'=>$coupon);
                        }
                        if (!$muti_hadpull && $now_user = M('User')->where(array('uid' => $uid))->find()) {
                            //微信小程序订阅消息提醒
                            $wxapp_message_send = false;
                            if($now_user['wxapp_openid'] && $is_system_send){
                                $param['page'] = 'pages/coupon/myCoupon';
                                $param['wxapp_openid'] = $now_user['wxapp_openid'];
                                $param['type'] = 'send_coupon';

                                $data_info['name'] = $coupon['name'];
                                $data_info['type'] = $coupon['is_discount'] ? L_('折扣券') : L_('代金券');
                                $data_info['get_time'] = time();
                                $data_info['end_time'] = $coupon['end_time'];
                                $data_info['note'] = L_('商家手动派发券给您，请及时关注~');
                                $data_info['time'] = time();
                                $param['data_info'] = $data_info;
                                $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
                            }

                            if(!$wxapp_message_send) {
                                $tempMsg = M('Tempmsg')->field(true)->where(array('mer_id' => $coupon['mer_id'], 'tempkey' => 'OPENTM200772305'))->find();
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $now_merchant = M('Merchant')->where(array('mer_id' => $coupon['mer_id']))->find();
                                $cate_platform = $this->cate_platform();
                                $res = $model->sendTempMsg('TM00251', array('href' => C('config.site_url') . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $coupon['mer_id'], 'wecha_id' => $now_user['openid'], 'first' => L_('您成功领取了商家【x1】的x2优惠券', array('x1' => $now_merchant['name'], 'x2' => $cate_platform['category'][$coupon['cate_name']])), 'toName' => $now_user['nickname'], 'gift' => L_('优惠券 【x1】，满x2减x3', array('x1' => $coupon['name'], 'x2' => $coupon['order_money'], 'x3' => $coupon['discount'])), 'time' => date("Y年m月d日 H:i"), 'remark' => L_('有效期：x1 至 x2', array('x1' => date("Y-m-d", $coupon['start_time']), 'x2' => date("Y-m-d", $coupon['end_time'])))), (empty($tempMsg) ? 0 : $coupon['mer_id']));
                            }
                        }
                        $coupon['has_get'] = $hadpull_count + $reduce_num;
                        $coupon['reduce_num'] = $reduce_num;
                        return array('error_code' => 0, 'coupon' => $coupon, 'hadpull_id' => $hadpull_id);
                    }
                    elseif($begin_coupon['num'] < $begin_coupon['had_pull']){
                        $this->where($where)->setDec('had_pull', $reduce_num);
                    }
                } else {
                    return array('error_code' => 1, 'coupon' => $coupon);
                }
            } else {
                return array('error_code' => 5, 'coupon' => $coupon);
            }
        }
    }

    //自动领优惠券
    public function auto_get_coupon($mer_id, $uid)
    {
        $coupon_list = $this->get_coupon_list_by_merid_and_auto_get($mer_id);
        $coupon_arr = array();
        foreach ($coupon_list as $item) {
            $this->had_pull($item['coupon_id'], $uid);
        }
        return true;;
    }

    //减少卡券库存
    public function decrease_sku($add, $less, $coupon_id)
    {
        $now_coupon = M(_view('card_new_coupon'))->where(array('coupon_id' => $coupon_id))->find();
        if (!$now_coupon['is_wx_card']) {
            return;
        }
        import('ORG.Net.Http');
        $res = D('Access_token_expires')->get_access_token();
        //修改库存
        $wx_data['card_id'] = $now_coupon['wx_cardid'];
        $wx_data['increase_stock_value'] = $add;
        $wx_data['reduce_stock_value'] = $less;
        $update_wx_card = httpRequest('https://api.weixin.qq.com/card/modifystock?access_token=' . $res['access_token'], 'post', json_encode($wx_data, JSON_UNESCAPED_UNICODE));
        $update_wx_card = json_decode($update_wx_card[1], true);
        $errorms = $update_wx_card['errmsg'];
        return $errorms;
    }

    //根据分类获取优惠券
    public function get_use_coupon_by_params($uid, $mer_id, $cate_name)
    {
        $now_time = time();
        $sql = "SELECT h.*, c.* FROM " . C('DB_PREFIX') . "card_new_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "card_new_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.uid='{$uid}' AND c.mer_id='{$mer_id}' AND h.is_use=0 AND c.use_with_card=1 AND c.status<>0 AND c.end_time>'{$now_time}' AND (c.cate_name='all' OR c.cate_name='{$cate_name}')";
        $res = $this->query($sql);
        $coupon_list = array();
        foreach ($res as $row) {
            $coupon_list[] = array('coupon_id' => $row['id'], 'full_money' => floatval($row['order_money']), 'reduce_money' => floatval($row['discount']), 'end_time' => date('Y-m-d', $row['end_time']));
        }
        return $coupon_list;
    }

    //获取还能领取多少优惠券
    public function get_can_had_coupon_by_params($uid, $mer_id, $cate_name)
    {
        $now_time = time();
        $is_new = D('User')->check_new($this->user_session['uid'], $cate_name);
        $sql = "SELECT c.coupon_id,count(h.id) as had FROM " . C('DB_PREFIX') . "card_new_coupon_hadpull AS h LEFT JOIN " . C('DB_PREFIX') . "card_new_coupon AS c  ON h.coupon_id = c.coupon_id WHERE h.uid='{$uid}' AND c.mer_id='{$mer_id}' AND c.use_with_card=1 AND c.status=1 AND c.end_time>'{$now_time}' AND (c.cate_name='all' OR c.cate_name='{$cate_name}') GROUP BY c.coupon_id";
        $sql2 = "SELECT * FROM  " . C('DB_PREFIX') . "card_new_coupon WHERE mer_id='{$mer_id}' AND use_with_card=1 AND status=1 AND end_time>'{$now_time}' AND (cate_name='all' OR cate_name='{$cate_name}')";
        $had_pull_num = M('')->query($sql);
        $coupon_list = M('')->query($sql2);
        foreach ($had_pull_num as $h) {
            $had[$h['coupon_id']] = $h['had'];
        }
        $n = 0;
        foreach ($coupon_list as $c) {
            if ($c['all_new'] && !$is_new) {
                continue;
            }
            $num = $c['num'] - $c['had_pull'];
            $can_get = $c['limit'] > $num ? $num : $c['limit'];
            $n += empty($had[$c['coupon_id']]) ? $can_get : $can_get - $had[$c['coupon_id']];
        }
        return $n;
    }


    public function send_coupon_by_id($coupon_id, $uid)
    {
        $where['coupon_id'] = $coupon_id;
        $coupon = $this->get_coupon($coupon_id);
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid, $coupon['mer_id']);
        if (empty($card_info)) {
            D('Card_new')->auto_get($uid, $coupon['mer_id']);
        }
        if ($coupon['num'] <= $coupon['had_pull'] || $coupon['status'] == 3) {
            if ($coupon['status'] != 4) {
                $this->field(true)->where($where)->setField('status', 3);
            }
            return array('error' => 3, 'msg' => L_('优惠券已领完'));
        } else {
            $hadpull = M('Card_new_coupon_hadpull');
            if ($this->where($where)->setInc('had_pull')) {
                $this->where($where)->setField('last_time', $_SERVER['REQUEST_TIME']);
                $data['coupon_id'] = $coupon_id;
                $data['num'] = 1;
                $data['receive_time'] = $_SERVER['REQUEST_TIME'];
                $data['status'] = 0;
                $data['uid'] = $uid;
                //$coupon = $this->get_coupon($coupon_id);
                if ($hadpull->add($data)) {
                    if ($now_user = M('User')->where(array('uid' => $uid))->find()) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $cate_platform = $this->cate_platform();
                        $model->sendTempMsg('TM00251', array('href' => C('config.site_url') . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $coupon['mer_id'], 'wecha_id' => $now_user['openid'], 'first' => L_('您成功领取了X1优惠券', $cate_platform['category'][$coupon['cate_name']]), 'toName' => $now_user['nickname'], 'gift' => $coupon['name'], 'time' => date("Y年m月d日 H:i"), 'remark' => L_('有效期：x1 至 x2', array('x1' => date("Y-m-d", $coupon['start_time']), date("Y-m-d", $coupon['end_time'])))), $coupon['mer_id']);
                    }
                    return array('error' => 0, 'msg' => L_('领取优惠券成功,优惠券【X1】', $coupon['name']));
                }
            } else {
                return array('error' => 1, 'msg' => L_('领取失败'));
            }

        }
    }

    public function is_shop_coupon($store_id, $mer_id)
    {
        $res = M(_view('card_new_coupon'))->field('discount, store_id')->where(array('is_shop' => 1, 'mer_id' => $mer_id))->select();
        $tmp = array();
        foreach ($res as $re) {
            $store_ids = explode(',', $re['store_id']);
            if (in_array($store_id, $store_ids)) {
                $tmp[] = $re;
            }
        }
        $min = 0;
        $max = 0;
        $str = '';
        foreach ($tmp as $t) {
            $min = min($min, floatval($t['discount']));
            $max = max($max, floatval($t['discount']));
        }
        if ($min > 0 && $max > 0) {
            if ($min == $max) {
                $str = L_('进店可领X1商家优惠券', $max . C('config.Currency_txt'));
            } else {
                $str = L_('进店可领X1商家优惠券', $min . '-' . $max . C('config.Currency_txt'));
            }
        }
        return $str;
    }

    /**
     * 通过店铺ID，获取IM聊天优惠券列表，带分页功能
     * @param  [type]  $store_id 店铺ID
     * @param  [type]  $page     当前页
     * @param  integer $pagesize 每页条数
     * @return array               
     */
    public function getImCouponByStore($store_id, $page = 1, $pagesize = 10){
        if(empty($store_id)) return [];
        $where = [];
        $where['end_time'] = array('gt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 1;
        $where['start_time'] = array('lt', time());
        $where['_string'] = 'FIND_IN_SET('.$store_id.',`store_id`)';

        $field = "coupon_id, name, img, platform, cate_id, cate_name, num, had_pull,`num`-`had_pull` AS left_num,discount,order_money,start_time,end_time";

        $limit = ($page - 1)*$pagesize.','.$pagesize;
        $totalCount = $this->where($where)->count();
        if($totalCount > 0){
            $data = $this->where($where)->field($field)->limit($limit)->select();
        }else{
            $data = array();
        }
        return array('coupon_count'=>$totalCount,'coupon_list'=>$data);
    }


    // 瓜分券瓜分获得优惠券
    public function partition_coupon_get_coupon($id,$is_all = 0){
        $return = array('error_code'=>0,'msg'=>'优惠券分发成功!');
        $where['status'] = 0;
        if ($is_all == 1) {
            // 瓜分所有 id是发起者的id
            $where['_string'] = '(id='.$id.' OR pid ='.$id.')';
            $partition_list = M('Merchant_partition_coupon_hadpull')->where($where)->select();
            if (!$partition_list) {
                return array('error_code'=>1,'msg'=>'没有需要瓜分数据');
            }

            // 查询已瓜分金额
            $where_had['status'] = 1;
            $where_had['_string'] = '(id='.$id.' OR pid ='.$id.')';
        }else{
            // 瓜分当前
            $where['id'] = $id;
            $now_partition = M('Merchant_partition_coupon_hadpull')->where($where)->find();
            if (!$now_partition) {
                return array('error_code'=>1,'msg'=>'没有需要瓜分数据');
            }
            $partition_list[] = $now_partition;

            // 查询已瓜分金额
            $where_had = array();
            $where_had['status'] = 1;
            if ($now_partition['pid']) {
                $where_had['_string'] = '(id='.$now_partition['pid'].' OR pid ='.$now_partition['pid'].')';   
            }else{
                $where_had['_string'] = '(id='.$id.' OR pid ='.$id.')';  
            }
        }

        // 查询已瓜分金额
        $hadpull_money = M('Merchant_partition_coupon_hadpull')->field('sum(money) as hadpull_money')->where($where_had)->find();
        $hadpull_money = $hadpull_money['hadpull_money'] ? getFormatNumber($hadpull_money['hadpull_money']) : 0;

        // 已瓜分人数
        $hadpull_count = M('Merchant_partition_coupon_hadpull')->where($where_had)->count();

        // 参与人数
        unset($where_had['status']);
        $user_count = M('Merchant_partition_coupon_hadpull')->where($where_had)->count();



        // 瓜分券详情
        $now_detail =  M('Merchant_partition_coupon')->where(array('pigcms_id'=> $partition_list[0]['coupon_id'],'delete'=>0))->find();
        if (!$now_detail) {
            return array('error_code'=>1,'msg'=>'瓜分券不存在');
        }

        if ($user_count<$now_detail['user_num']) {
            return array('error_code'=>1,'msg'=>'瓜分人数不够');
        }

        // 生成优惠券
        // 使用有效期
        if ($now_detail['time_type']==0) {
            $data['start_time'] = $now_detail['use_start_time'];
            $data['end_time'] = $now_detail['use_end_time'];
        }else{
            $data['start_time'] = time();
            $data['end_time'] = time()+$now_detail['use_time']*86400;
        }

        // 使用条件
        if ($now_detail['use_condition']==1) {
            $data['order_money'] = $now_detail['order_money'];
        }else{
            $data['order_money'] =0;
        }
        $data['name'] = '瓜分券【'.$now_detail['name'].'】';
        $data['platform'] = serialize(array('wap','app','weixin'));
        $data['is_partition'] = '1';
        $data['num'] = '1';
        $data['limit'] = '1';
        $data['status'] = '1';
        $data['store_id'] = $now_detail['store_id'];
        $data['mer_id'] = $now_detail['mer_id'];
        $data['cate_name'] = 'all';
        $data['add_time'] = time();

        $now_card = M('Card_new')->where(array('mer_id'=>$now_detail['mer_id']))->find();
        $data['card_id'] = $now_card['card_id'];

        // 剩余瓜分金额
        $total_money = $now_detail['money']-$hadpull_money;
        foreach ($partition_list as $key => $value) {
            if ($now_detail['type']==0) {
                $discount_money = round($now_detail['money']/$user_count,2);
            }else{
                if (($key+1)==($user_count-$hadpull_count)) {
                    $discount_money = $total_money;
                }else{
                    $discount_money = $this->randFloat(0,$total_money);
                    $discount_money = round($discount_money,2);
                    $total_money = $total_money - $discount_money;
                }
            }
            $data['discount'] = getFormatNumber($discount_money);
            if (!$is_all) {
                $return['discount_money'] = getFormatNumber($discount_money);
            }
            $coupon_id = $this->add($data);
            if ($coupon_id) {
                $where = array();
                $where['coupon_id'] = $coupon_id;
                if ($this->where($where)->setInc('had_pull')) {
                    $this->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
                    $coupon_data = array();
                    $coupon_data['coupon_id'] = $coupon_id;
                    $coupon_data['num'] = 1;
                    $coupon_data['receive_time'] =$_SERVER['REQUEST_TIME'];
                    $coupon_data['status'] = 0;
                    $coupon_data['uid']  = $value['uid'];
                    if(M('Card_new_coupon_hadpull')->add($coupon_data)){
                        $update_data = array(
                            'status' => 1,
                            'money' => getFormatNumber($discount_money),
                            'partition_time' => time(),
                        );
                        M('Merchant_partition_coupon_hadpull')->where(array('id'=> $value['id']))->save($update_data);

                        if ($now_user = M('User')->where(array('uid' => $value['uid']))->find()) {
                            $tempMsg = M('Tempmsg')->field(true)->where(array('mer_id' => $data['mer_id'], 'tempkey' => 'OPENTM200772305'))->find();
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $now_merchant = M('Merchant')->where(array('mer_id' => $data['mer_id']))->find();
                            $res = $model->sendTempMsg(
                                'TM00251', 
                                array(
                                    'href' => C('config.site_url') . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $data['mer_id'],
                                    'wecha_id' => $now_user['openid'], 
                                    'first' => '您成功领取了商家【'.$now_merchant['name'].'】的优惠券', 
                                    'toName' => $now_user['nickname'], 
                                    'gift' =>'优惠券 【'.$data['name'].'】，满'.$data['order_money'].'减'.$data['discount'], 
                                    'time' => date("Y年m月d日 H:i"), 
                                    'remark' => '有效期：'.date("Y-m-d", $data['start_time']).' 至 '.date("Y-m-d", $data['end_time']),
                                ), 
                                (empty($tempMsg) ? 0 : $data['mer_id'])
                            );
                        }
                    }
                }
            }
        }
        return $return;
    }

    public function randFloat($min=0, $max=1){
        return $min + mt_rand()/mt_getrandmax() * ($max-$min);
    }

    /**
     * 通过店铺ID获取所有智能聊天发送优惠券列表
     * @param $storeId
     */
    public function getAllImGetCoupon($storeId)
    {
        if (empty($storeId)) {
            return array();
        }
        $tm = time();
        $where = array(
            'allow_im_get' => 1,
            'start_time' => array('lt', $tm),
            'end_time' => array('gt', $tm),
            'status' => 1,
            '_string' => 'FIND_IN_SET(' . $storeId . ',`store_id`)'
        );
        $field = "coupon_id, name, `num`-`had_pull` AS left_num,discount,order_money,start_time,end_time,img AS image,platform";
        return $this->where($where)->field($field)->select();
    }


    /**
     * 通过优惠券ID获取优惠券信息
     * @param $couponIds
     * @return array|mixed
     */
    public function getCouponInfoByIds($couponIds)
    {
        if (empty($couponIds)) {
            return array();
        }
        $tm = time();
        $where = array(
            'start_time' => array('lt', $tm),
            'end_time' => array('gt', $tm),
            'status' => 1,
            'coupon_id' => array('IN', $couponIds)
        );
        $field = "coupon_id, name, discount,order_money,FROM_UNIXTIME(start_time,'%Y-%m-%d') AS start_time,FROM_UNIXTIME(end_time,'%Y-%m-%d')end_time, num, had_pull";
        return $this->where($where)->field($field)->select();
    }

    /**
     * 判断该商家/店铺是否有直播优惠券
     * @param  [type] $mer_id    [description]
     * @param  [type] $store_id  [description]
     * @param  [type] $cate_name [业务代号]
     * @return [int]            [1 = 有  0=无]
     */
    public function check_has_live_coupon($mer_id, $store_id, $cate_name){
        if(empty($mer_id)) return 0;
        $where = [
            'is_live' => 1
        ];
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        if($store_id){
            $where['store_id'] = ['like', '%'.$store_id.'%'];
        }
        $where['_string'] = "(cate_name='all' OR cate_name like '%{$cate_name}%')";

        return $this->where($where)->find() ? 1 : 0;
    }

    /**
     * [获取商家直播券]
     * @param  [type] $mer_id    [description]
     * @param  [type] $store_id  [description]
     * @param  [type] $cate_name [description]
     * @return [type]            [description]
     */
    public function get_live_coupon($mer_id, $store_id, $cate_name){
        if(empty($mer_id)) return [];
        $where = [
            'is_live' => 1
        ];
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        if($store_id){
            $where['store_id'] = ['like', '%'.$store_id.'%'];
        }
        $where['_string'] = "(cate_name='all' OR cate_name like '%{$cate_name}%')";
        $field = "coupon_id, name as title, num, had_pull, order_money, discount";
        $data = $this->field($field)->where($where)->select();
        foreach ($data as $key => $value) {
            $data[$key]['discount_title'] = "满".$value['order_money']."减".$value['discount'];
            $data[$key]['nums'] = $value['num'] - $value['had_pull'] > 0 ? $value['num'] - $value['had_pull'] : 0;
        }
        return $data;
    }

    public function get_live_couponid($mer_id, $store_id, $cate_name){
        if(empty($mer_id)) return [];
        $where = [
            'is_live' => 1
        ];
        $where['mer_id'] = $mer_id;
        if($store_id){
            $where['store_id'] = ['like', '%'.$store_id.'%'];
        }
        $where['_string'] = "(c.cate_name='all' OR c.cate_name like '%{$cate_name}%')";
        $field = "coupon_id";
        $data = $this->field($field)->where($where)->select();
        return array_column($data, 'coupon_id');
    }



    /**
     * [获取商品最优直播券]
     * @param  [type] $mer_id    [description]
     * @param  [type] $store_id  [description]
     * @param  [type] $cate_name [商品类型shop-外卖，mall-商城]
     * @param  [type] $goods_id [商品id]
     * @return [type]            [description]
     */
    public function get_live_best_coupon($mer_id, $store_id, $cate_name , $goods_id=0, $uid=0){
        if(empty($mer_id)) return [];
        $where = [
            'is_live' => 1
        ];
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        if($store_id){
            $where['store_id'] = ['like', '%'.$store_id.'%'];
        }
        $where['_string'] = "(cate_name='all' OR cate_name like '%{$cate_name}%')";
        $field = "coupon_id, name as title, num, had_pull, order_money, discount";
        $data = $this->field($field)->where($where)->select();
        $discount = 0;
        $returnArr = [];
        foreach ($data as $key => $value) {
            if($value['point_goodids'] && $goods_id){// 指定商品
                $goods_ids = unserialize($value['point_goodids']);
                if(!in_array($goods_id, $goods_ids[$cate_name])){
                    continue;
                }
            }

            if($value['discount']>$discount){// 取最优的
                $returnArr = [
                    'coupon_id' => $value['coupon_id'],
                    'is_had' => 0,
                    'discount' => getFormatNumber($value['discount']),
                    'type_name' => L_('限时立减'),
                    'discount_title' => L_('限时立减').C('config.Currency_symbol'). getFormatNumber($value['discount']),
                    'nums' => $value['num'] - $value['had_pull'] > 0 ? $value['num'] - $value['had_pull'] : 0,
                ];
            }
        }

        if($returnArr && $uid){
            // 查看是否已领取
            $where = [
                'coupon_id' => $returnArr['coupon_id'],
                'is_use' => 0,
                'uid' => $uid
            ];
            $had = M('Card_new_coupon_hadpull')->where($where)->find();
            if($had){
                $returnArr['is_had'] = 1;
            }
        }
        return (object)$returnArr;
    }
}
