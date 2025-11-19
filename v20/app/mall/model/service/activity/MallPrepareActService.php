<?php


namespace app\mall\model\service\activity;

use app\mall\model\db\MallPrepareOrder;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\activity\MallPrepareOrderService;
use app\mall\model\db\MallPrepareAct;
use app\mall\model\db\MallOrder;

use think\facade\Db;

class MallPrepareActService
{
    /**预售活动列表
     * @param $store_id
     * @param $param
     */
    public function getPrepareActList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $goods_name = !empty($param['goods_name']) ? trim($param['goods_name'], '') : '';
        $status = $param['status'];//活动状态 0未开始 1进行中 2已失效 3全部活动
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];

        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.type', '=', 'prepare'];
        $where[] = ['a.is_del', '=', 0];
        if ($goods_name) {
            $where[] = ['a.name', 'like', '%' . $goods_name . '%'];
        }

        //活动状态   //0未开始 1进行中 2已失效 3全部活动
        /*$stime = strtotime(date("Y-m-d 00:00:00", time()));
        $etime = strtotime(date("Y-m-d 23:59:59", time()));
        if ($status == 0) {
            $where[] = ['a.start_time', '>', $stime];
            $where[] = ['a.status', '<>', 2];
        } elseif ($status == 1) {
            $where[] = ['a.start_time', '<=', $stime];
            $where[] = ['a.end_time', '>=', $etime];
            $where[] = ['a.status', '<>', 2];
        } elseif ($status == 2) {
            $where[] = ['a.end_time', '<', $etime];
        }

        if (!empty($startTime) && !empty($endTime)) {
            $where[] = ['s.bargain_start_time', '>=', $startTime];
            $where[] = ['s.bargain_end_time', '<=', $endTime];
        } elseif ($startTime) {
            $where[] = ['s.bargain_start_time', '>=', $startTime];
        } elseif ($endTime) {
            $where[] = ['s.bargain_end_time', '<=', $endTime];
        }*/
        if($startTime && $endTime){
            $where[] = ['a.start_time', '<', $startTime];
            $where[] = ['a.end_time', '>', $endTime];
        }elseif($startTime){
            $where[] = ['a.start_time', '>=', $startTime];
        }elseif($endTime){
            $where[] = ['a.end_time', '<=', $endTime];
        }

        $whereOr=[];
        if(intval($status)==0 && empty($startTime) && empty($endTime)){
            $where[] = ['a.start_time', '>', time()];
        }elseif (intval($status)==1 && empty($startTime) && empty($endTime)){
            $where[] = ['a.start_time', '<=', time()];
            $where[] = ['a.end_time', '>', time()];
            $where[] = ['a.status', 'not in', [0,2]];
        }elseif (intval($status)==2 && empty($startTime) && empty($endTime)){
            $where[] = ['a.end_time', '<', time()];
            $whereOr[] = ['a.status', '=', 2];
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'prepare'];
            $whereOr[] = ['a.store_id', '=', $store_id];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }

        $prefix = config('database.connections.mysql.prefix');
        $mallPrepareActModel = new MallPrepareAct();
        /*$count = $mallPrepareActModel->alias('s')
            ->join([$prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'left')
            ->where($where)
            ->count();*/
        /*if ($count < 1) {
            $lists = [];
        } else {*/
        $fields = 's.id,s.bargain_start_time,s.bargain_end_time,a.`status`,a.`name` as goods_name';
        $lists = $mallPrepareActModel->alias('s')
            ->join([$prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'left')
            ->field($fields)
            ->where($where);
        if(!empty($whereOr) && $status==2){
            $lists = $lists->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $count=$lists->count();
        $lists=$lists->order('a.sort ASC,a.id DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select()
                ->toArray();
            foreach ($lists as &$value) {
                if ($value['bargain_start_time'] < time() && $value['bargain_end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['bargain_start_time'] < time() && $value['bargain_end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                $value['bargain_start_time'] = date('Y-m-d H:i:s', $value['bargain_start_time']);
                $value['bargain_end_time'] = date('Y-m-d H:i:s', $value['bargain_end_time']);

                $condition_where = [];
                $condition_where[] = ['act_id', '=', $value['id']];
                $condition_where[] = ['pay_status', '<>', 0];
                $value['bargain_sale_num'] = 0;//支付定金单数
                $value['rest_sale_num'] = 0;//支付尾款单数
                $value['real_income'] = 0;//实收金额
                $order = (new MallPrepareOrderService())->getPrepareOrderList($condition_where, $fields = 'bargain_price,rest_price');
                foreach ($order as $o) {
                    if (!empty($o['bargain_price'])) $value['bargain_sale_num'] = $value['bargain_sale_num'] + 1;
                    if (!empty($o['rest_price'])) $value['rest_sale_num'] = $value['rest_sale_num'] + 1;
                    $value['real_income'] = number_format(($o['bargain_price'] + $o['rest_price']), 2);
                }
            }
        /*}*/
        $res['list'] = $lists;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }


    /**
     * 商家后台添加 预售活动
     * User: 钱大双
     * Date: 2020-10-21
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function addPrepareAct($data)
    {
        //商城预售活动信息
        $prepare = [];
        $prepare['bargain_start_time'] = strtotime($data['bargain_start_time']);
        $prepare['bargain_end_time'] = strtotime($data['bargain_end_time']);
        $prepare['rest_type'] = $data['rest_type'];
        $prepare['rest_start_time'] = $data['rest_type'] == 0 ? strtotime($data['rest_start_time']) : $data['rest_start_time'];
        $prepare['rest_end_time'] = $data['rest_type'] == 0 ? strtotime($data['rest_end_time']) : $data['rest_end_time'];
        $prepare['send_goods_type'] = $data['send_goods_type'];
        $prepare['send_goods_days'] = $data['send_goods_days'];
        $prepare['send_goods_date'] = $data['send_goods_type'] == 1 ? strtotime($data['send_goods_date']) : '';
        $prepare['is_discount_share'] = $data['is_discount_share'];
        $prepare['discount_card'] = $data['discount_card'];
        $prepare['discount_coupon'] = $data['discount_coupon'];
        $prepare['limit_num'] = $data['limit_num'];

        //商品活动信息
        $activity = [];
        $start_time = strtotime($data['bargain_start_time']);
        $end_time = strtotime($data['bargain_end_time']);
        $activity['start_time'] = $start_time;
        $activity['end_time'] = $end_time;
        $activity['desc'] = $data['desc'];
        $activity['name'] = $data['goods_sku'][0]['goods_name'];
        $activity['sort'] = $data['sort'];
        if ($start_time > time()) {
            $activity['status'] = 0;
        } elseif ($start_time < time() && $end_time > time()) {
            $activity['status'] = 1;
        } elseif ($start_time < time() && $end_time < time()) {
            $activity['status'] = 2;
        }

        $goods_sku = $data['goods_sku'];
        if ($data['id'] > 0) {//编辑
            Db::startTrans();
            try {
                $act_id = $data['id'];
                //获取活动信息
                $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $act_id, 'type' => $data['type']], $field = 'id,start_time,end_time');
                $activity_id = $activity_info[0]['id'];
                $old_goods = (new MallActivityDetailService())->getGoodsInAct($where = ['activity_id' => $activity_id], $field = 'goods_id');
                $old_bargain_start_time = date('Y-m-d', $activity_info[0]['start_time']);
                $old_bargain_end_time = date('Y-m-d', $activity_info[0]['end_time']);

                //编辑活动时间或商品发生变化
                if (($old_goods[0]['goods_id'] != $data['goods_sku'][0]['goods_id']) || ($data['bargain_start_time'] != $old_bargain_start_time) || ($data['bargain_end_time'] != $old_bargain_end_time)) {
                    $check = $this->_check_activity_goods($data);
                    if ($check['status'] == 0) {
                        (new MallPrepareAct())->updatePrepare($prepare, $where = ['id' => $act_id]);
                        (new MallPrepareActSkuService())->delPrepareActGoods($where = ['act_id' => $act_id]);
                        (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                        $this->update_prepare_active($goods_sku, $act_id, $activity_id);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '编辑成功'];
                    } else {
                        $res = ['status' => 1, 'msg' => $check['msg']];
                    }
                } else {
                    (new MallPrepareAct())->updatePrepare($prepare, $where = ['id' => $act_id]);
                    (new MallPrepareActSkuService())->delPrepareActGoods($where = ['act_id' => $act_id]);
                    (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                    (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                    $this->update_prepare_active($goods_sku, $act_id, $activity_id);
                    //提交事务
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '编辑成功'];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        } else {//添加
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $act_id = (new MallPrepareAct())->addPrepare($prepare);
                    $activity['act_id'] = $act_id;
                    $activity['mer_id'] = $data['mer_id'];
                    $activity['store_id'] = $data['store_id'];
                    $activity['type'] = $data['type'];
                    $activity['create_time'] = time();
                    $activity_id = (new MallActivityService())->addActive($activity);
                    if ($act_id && $activity_id) {
                        $this->update_prepare_active($goods_sku, $act_id, $activity_id);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '添加成功'];
                    } else {
                        //回滚事务
                        Db::rollback();
                        $res = ['status' => 1, 'msg' => '添加失败'];
                    }
                } else {
                    $res = ['status' => 1, 'msg' => $check['msg']];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        }
        return $res;
    }

    /**修改活动状态
     * @param $data
     * @param $id
     */
    public function changeState($data, $id)
    {
        $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $id, 'type' => 'prepare'], $field = 'id');
        $activity_id = $activity_info[0]['id'];
        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
        $where = ['act_id' => $id, 'type' => 'prepare'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**软删除预售活动
     * @param $data
     * @param $id
     */
    public function del($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'prepare'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**预售活动基本信息
     * @param $data
     * @param $id
     */
    public function getInfoById($id)
    {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'prepare'];
        $fields = 's.*,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';
        $res = (new MallPrepareAct())->getInfo($where, $fields);
        if ($res['bargain_start_time'] < time() && $res['bargain_end_time'] < time()) {
            $res['status'] = 2;
        }
        $res['bargain_start_time'] = date("Y-m-d H:i:s", $res['bargain_start_time']);
        $res['bargain_end_time'] = date("Y-m-d H:i:s", $res['bargain_end_time']);
        $res['rest_start_time'] = $res['rest_type'] == 0 ? date("Y-m-d H:i:s", $res['rest_start_time']) : $res['rest_start_time'];
        $res['rest_end_time'] = $res['rest_type'] == 0 ? date("Y-m-d H:i:s", $res['rest_end_time']) : $res['rest_end_time'];
        $res['send_goods_date'] = $res['send_goods_type'] == 1 ? date("Y-m-d", $res['send_goods_date']) : '';

        $prepare_act_sku = (new MallPrepareActSkuService())->getPrepareActSkuByActId($id);
        $goods_id = [];
        $sku_id = [];
        $temp = [];
        foreach ($prepare_act_sku as $value) {
            $goods_id[] = $value['goods_id'];
            $sku_id[$value['goods_id']][] = $value['sku_id'];
            $temp[$value['goods_id'] . '-' . $value['sku_id']] = $value;
        }

        $param = [];
        $param['goods_id'] = $goods_id;
        $param['sku_id'] = $sku_id;
        $param['type'] = 'prepare';
        $param['mer_id'] = $res['mer_id'];
        $param['store_id'] = $res['store_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;
        $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);
        $goods_info['list'] = is_object($goods_info['list']) ? $goods_info['list']->toArray() : $goods_info['list'];
        foreach ($goods_info['list'] as $key => $val) {
            foreach ($val['sku_info'] as $k => $info) {
                $goods_info['list'][$key]['sku_info'][$k]['discount_price'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['discount_price'];
                $goods_info['list'][$key]['sku_info'][$k]['bargain_price'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['bargain_price'];
                $goods_info['list'][$key]['sku_info'][$k]['rest_price'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['rest_price'];
            }
        }
        $res['goods_info'] = $goods_info['list'];
        return $res;
    }

    /**更新预售活动相关信息
     * @param $goods_sku  商品信息
     * @param int $act_id
     * @param int $activity_id
     */
    private function update_prepare_active($goods_sku, $act_id = 0, $activity_id = 0)
    {
        foreach ($goods_sku as $val) {
            $good['act_id'] = $act_id;
            $good['sku_id'] = $val['sku_id'];
            $good['goods_id'] = $val['goods_id'];
            $good['act_stock_num'] = $val['act_stock_num'];
            $good['bargain_price'] = !empty($val['bargain_price']) ? $val['bargain_price'] : 0;
            $good['discount_price'] = !empty($val['discount_price']) ? $val['discount_price'] : 0;
            $good['rest_price'] = $val['rest_price'];
            $goods[] = $good;

            //单个商品多个sku
            $activity['activity_id'] = $activity_id;
            $activity['goods_id'] = $val['goods_id'];
            $activity_detail[$val['goods_id']] = $activity;
        }

        if (!empty($goods)) {
            (new MallPrepareActSkuService())->addAllPrepareActGoods($goods);
        }

        if (!empty($activity_detail)) {
            (new MallActivityDetailService())->addActiveDatailAll($activity_detail);
        }
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        //正在参加周期购的活动的商品
        $mallGoodsService = new MallGoodsService();
        $spu_periodic = array_column($mallGoodsService->getPeriodicList($data['mer_id']), NULL, 'goods_id');
        //预售活动商品信息
        $goods_sku = $data['goods_sku'][0];//单个商品多sku属性
        $periodic_goods_id = $goods_sku['goods_id'];
        $res = ['status' => 0];
        if (!empty($spu_periodic) && isset($spu_periodic[$periodic_goods_id])) {
            $msg = '商品：' . $goods_sku['goods_name'] . '正在参与周期购活动，请重新添加活动商品';
            $res = ['status' => 1, 'msg' => $msg];
            return $res;
        }
        $types = [
            'group' => '拼团',
            'limited' => '秒杀',
            'bargain' => '砍价',
            'prepare' => '预售',
            'reached' => 'N元N价'
        ];
        $curr_time = time();
        $spu_where = [
            ['a.end_time', '>', $curr_time],
            ['a.status', '<>', 2],
            ['a.is_del', '=', 0],
            ['a.act_id', '<>', $data['id']],//预售传的是act_id
            ['a.type', 'in', array_keys($types)],
            ['a.mer_id', '=', $data['mer_id']],
            ['a.store_id', '=', $data['store_id']],
            ['d.goods_id', 'in', [$periodic_goods_id]]
        ];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //所有正在参加活动的商品（不含周期购）
        $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        $bargain_start_time = strtotime($data['bargain_start_time'] . " 00:00:00");
        $bargain_end_time = strtotime($data['bargain_end_time'] . " 23:59:59");
        foreach ($spu as $value) {
            $flag = is_time_cross($bargain_start_time, $bargain_end_time, $value['start_time'], $value['end_time']);
            if ($flag) {
                $start_time = date('Y-m-d', $value['start_time']);
                $end_time = date('Y-m-d', $value['end_time']);
                $msg = '商品：' . $goods_sku['goods_name'] . '正在参与' . $start_time . '~' . $end_time . $types[$value['type']] . '活动，请重新添加活动商品';
                $res = ['status' => 1, 'msg' => $msg];
                break;
            }
        }
        return $res;
    }

    /**
     * @param $order_id 订单id
     * @return bool
     * @throws \think\Exception
     * 预售防止与计划任务冲突,最后几秒点击支付尾款保存一个状态
     */
    public function secondPayUpdateOrder($order_id)
    {
        if (empty($order_id)) {//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where = [['order_id', '=', $order_id]];
        $msg = (new MallPrepareOrder())->getOne($where, $field = "*");
        if (empty($msg)) {
            throw new \think\Exception(L_("没有此订单信息"), 1001);
        } else {
            if ($msg['pay_status'] == 1 && $msg['second_pay'] == 0) {
                $data['second_pay_time'] = time();
                $data['second_pay'] = 1;
                $where = [['order_id', '=', $order_id]];
                $ret = (new MallPrepareOrder())->updatePrepare($data, $where);
                if ($ret !== false) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
}