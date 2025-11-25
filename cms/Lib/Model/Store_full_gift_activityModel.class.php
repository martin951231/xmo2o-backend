<?php

class Store_full_gift_activityModel extends Model
{
    /**
     * 每天最多享受几单
     */
    const MAX_TIME_PER_DAY = 2;

    public function getCurrentActivityByStoreId($storeId)
    {
        $tm = time();
        $where = [
            'store_id' => $storeId,
            'is_open' => 1,
            'status' => 1,
            'is_del' => 0,
            'begin_time' => ['lt', $tm],
            'end_time' => ['gt', $tm]
        ];
        $fullActivity = M('Store_full_gift_activity')
            ->where($where)
            ->find();
        return $fullActivity ?: [];
    }

    public function getSettingByActivityId($id, $sort = 's.price ASC')
    {
        $setting = M('Store_full_gift_activity_setting')->alias('s')
            ->join(C('DB_PREFIX') . 'shop_goods AS g ON s.goods_id = g.goods_id')
            ->where(['s.aid' => $id])
            ->field('s.id AS setting_id,s.aid,s.price AS full_price,g.*')
            ->order($sort)
            ->select();
        //如果商品都没库存了，则活动结束
        foreach ($setting as $k => $v) {
            if ($v['stock_num'] != -1 && $v['stock_num'] <= 0) {
                unset($setting[$k]);
            }
        }
        return $setting ? array_values($setting) : [];
    }

    public function getFullGiveGood($storeId, $money, $uid)
    {
        $orderWhere = [
            'paid' => 1,
            'create_time' => ['gt', strtotime(date('Y-m-d'))],
            'is_give' => 1,
            'uid' => $uid
        ];
        if (M('Shop_order')->where($orderWhere)->count() >= self::MAX_TIME_PER_DAY) {
            return [];
        }
        $fullGive = [];
        $fullActivity = D('Store_full_gift_activity')->getCurrentActivityByStoreId($storeId);
        if ($fullActivity) {
            $setting = D('Store_full_gift_activity')->getSettingByActivityId($fullActivity['id'],'s.price DESC');
            foreach ($setting as $s) {
                if ($s['full_price'] <= $money && ($s['stock_num'] == -1 || $s['stock_num'] > 0)) {
                    $fullGive = [
                        'goods_id'=>$s['goods_id'],
                        'name' => $s['name'],
                        'number' => $s['number'],
                        'num' => 1,
                        'unit' => $s['unit'],
                        'price' => 0,
                        'image'=>$s['image'],
                        'sort_id'=>$s['sort_id'],
                        'sell_type'=>$s['sell_type'],
                        'weight'=>$s['weight'],
                        'setting_id'=>$s['setting_id'],
                        'aid'=>$s['aid']
                    ];
                    break;
                }
            }
        }
        return $fullGive;
    }

    // 验证商品是否参与满赠活动
    public function checkFullActivity($goods_id)
    {
        $where['g.goods_id'] = $goods_id;
        $where['f.begin_time'] = ['lt',time()];
        $where['f.end_time'] = ['gt',time()];
        $res = $this->alias('f')->join(' inner join '.C('DB_PREFIX').'store_full_gift_activity_setting as g on g.aid=f.id')->where($where)->find();

        if($res){
            return true;
        }
        return false;
    }

    public function get_list($search, $page = 1, $page_size = 10)
    {
        $where = $search;
        // if ($search['store_id']) {
        //     $where['store_id'] = $search['store_id'];
        // }
        // if (isset($search['is_del'])) {
        //     $where['is_del'] = $search['is_del'];
        // }
        $lists = $this->where($where)->limit(($page - 1) * $page_size . ',' . $page_size)->select();
        $tm = time();
        $shop_order_model = D('Shop_order');
        $setting_mod = M('Store_full_gift_activity_setting');
        $goods_image_class = new goods_image();
        foreach ($lists as &$l) {
            $l['setting'] = $setting_mod->alias('s')
                ->join(C('DB_PREFIX') . 'shop_goods AS g ON s.goods_id = g.goods_id')
                ->field('s.*,g.stock_num,g.name AS goods_name,g.image')
                ->where(['s.aid' => $l['id']])
                ->select();
            $l['setting'] = $l['setting'] ? $l['setting'] : [];
            if ($l['setting']) {
                if ($l['setting'][0]['image']) {
                    $tmp_pic_arr = explode(';', $l['setting'][0]['image']);
                    foreach ($tmp_pic_arr as $k => $v) {
                        $rl['pic_arr'][$k]['title'] = $v;
                        $rl['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                        $k==0 && $l['image_url'] = $rl['pic_arr'][$k]['url']['s_image'];
                    } 
                }else{
                    if (C('config.is_open_goods_default_image')&&C('config.goods_default_image')) {
                        $l['image_url'] = C('config.goods_default_image');
                    }else{
                        $l['image_url'] = C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
                    }
                }
            }
            if ($l['end_time'] <= $tm) {
                $l['status_str'] = '<font style="color:red">已过期</font>';
            } else {
                $l['status_str'] = $l['status'] == 1 ? '正常' : '失效';
            }
            $l['begin_time'] = date('Y-m-d H:i', $l['begin_time']);
            $l['end_time'] = date('Y-m-d H:i', $l['end_time']);

            $data = $shop_order_model->where(['is_give' => 1, 'full_gift_aid' => $l['id'], 'paid' => 1])->field('count(order_id) AS order_count,count(distinct uid) AS user_count,sum(price) AS total_price')->find();
            $l['order_count'] = isset($data['order_count']) ? $data['order_count'] : 0;
            $l['user_count'] = isset($data['user_count']) ? $data['user_count'] : 0;
            $l['total_price'] = isset($data['total_price']) ? $data['total_price'] : 0;
        }
        return $lists;
    }

    /**
     * 添加店铺满赠
     */
    public function full_gift_add($post)
    {
        if (empty($post['store_id'])) {
            throw new \Exception(L_('店铺不存在'));
        }
        if (empty(trim($post['name']))) {
            throw new \Exception(L_('活动名称不能为空'));
        }
        if (empty($post['sdate']) || empty($post['fdate'])) {
            throw new \Exception(L_('活动时间不能为空'));
        }
        $setting = [];
        foreach ($post['goods_price'] as $key => $value) {
            if (!is_numeric($value) || $value < 0 || empty($post['give_goods_id'][$key])) {
                continue;
            }
            $setting[] = [
                'price' => $value,
                'goods_id' => $post['give_goods_id'][$key]
            ];
        }
        if (empty($setting)) {
            throw new \Exception(L_('优惠配置不能为空'));
        }
        if (count($setting) > 3) {
            throw new \Exception(L_('优惠配置最多三个等级优惠'));
        }
        $activity = [
            'name' => trim($post['name']),
            'begin_time' => strtotime($post['sdate']),
            'end_time' => strtotime($post['fdate']),
            'status' => 1,
            'store_id' => $post['store_id'],
            'is_open' => isset($post['is_open']) ? $post['is_open'] : 0
        ];
        if ($aid = M('Store_full_gift_activity')->add($activity)) {
            foreach ($setting as &$s) {
                $s['aid'] = $aid;
            }
            M('Store_full_gift_activity_setting')->addAll($setting);
            //开启一个，则其他关闭
            if ($activity['is_open'] == 1) {
                M('Store_full_gift_activity')->where(['store_id' => $post['store_id'], 'id' => ['NEQ', $aid]])->save(['is_open' => 0]);
            }
            return true;
        } else {
            throw new \Exception(L_('新增失败'));
        }
    }

    /**
     * 编辑店铺满赠
     */
    public function full_gift_edit($post)
    {
        if (empty($post['id'])) {
            throw new \Exception(L_('请选择一条记录'));
        }
        if (empty($post['store_id'])) {
            throw new \Exception(L_('店铺不存在'));
        }
        $id = intval($post['id']);
        if (!M('Store_full_gift_activity')->where(['id' => $id, 'store_id' => $post['store_id']])->find()) {
            throw new \Exception(L_('活动不存在'));
        }

        if (empty(trim($post['name']))) {
            throw new \Exception(L_('活动名称不能为空'));
        }
        if (empty($post['sdate']) || empty($post['fdate'])) {
            throw new \Exception(L_('活动时间不能为空'));
        }
        $setting = [];
        foreach ($post['goods_price'] as $key => $value) {
            if (!is_numeric($value) || $value < 0 || empty($post['give_goods_id'][$key])) {
                continue;
            }
            $setting[] = [
                'price' => $value,
                'goods_id' => $post['give_goods_id'][$key]
            ];
        }
        if (empty($setting)) {
            throw new \Exception(L_('优惠配置不能为空'));
        }
        if (count($setting) > 3) {
            throw new \Exception(L_('优惠配置最多三个等级优惠'));
        }
        $activity = [
            'name' => trim($post['name']),
            'begin_time' => strtotime($post['sdate']),
            'end_time' => strtotime($post['fdate']),
            'status' => 1,
            'store_id' => $post['store_id'],
            'is_open' => isset($post['is_open']) ? $post['is_open'] : 0
        ];
        if (false !== M('Store_full_gift_activity')->where(['id' => $id])->save($activity)) {
            M('Store_full_gift_activity_setting')->where(['aid' => $id])->delete();
            foreach ($setting as &$s) {
                $s['aid'] = $id;
            }
            M('Store_full_gift_activity_setting')->addAll($setting);
            //开启一个，则其他关闭
            if ($activity['is_open'] == 1) {
                M('Store_full_gift_activity')->where(['store_id' => $post['store_id'], 'id' => ['NEQ', $id]])->save(['is_open' => 0]);
            }
            return true;
        } else {
            throw new \Exception(L_('编辑失败'));
        }
    }
}