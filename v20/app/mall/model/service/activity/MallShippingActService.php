<?php


namespace app\mall\model\service\activity;

use app\mall\model\db\MallActivity;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallShippingActGoods;
use app\mall\model\service\MallGoodsService;

use app\mall\model\db\MallShippingAct;
use app\mall\model\db\MallOrder;

use think\facade\Db;

class MallShippingActService
{
    /**
     * @param $act_id
     * @return bool
     * 满包邮活动详情及商品列表
     */
    public function getShippingGoodsList($act_id,$uid=0)
    {
        $where[] = [//条件
            ['act_id', '=', $act_id],
            ['status', '=', 1],
            ['start_time', '<', time()],
            ['end_time', '>=', time()],
            ['type', '=', 'shipping'],
        ];
        //先查询门店是否有活动，再根据活动找出商品，取出商品的交集看是否满足当前活动要求，返回信息
        $return1 = (new MallActivity())->getOne($where);
        if (!empty($return1)) {//查询出不为空是全店的活动
            $condition1[] = ['id', '=', $return1['act_id']];
            $result1 = (new MallShippingAct())->getOneAct($condition1);
            if (empty($result1)) {//活动信息查不出
                $result['goods_list'] = [];
                $result['rule_msg'] = [];
                $result['left_time'] = 0;
            } else {
                if ($result1['full_type'] == 0) {//活动类型0 满XX元包邮
                    $arr['msg'] = '满' . get_format_number($result1['nums']) . '元包邮';
                    $result['rule_msg'][] = $arr;
                } else {//1满XX件包邮
                    $arr['msg'] = '满' . get_format_number($result1['nums']) . '件包邮';
                    $result['rule_msg'][] = $arr;
                }
                if ($return1['act_type']) {//全店的活动
                    $result['goods_list'] = (new MallGoods())->getGoodsList($return1['store_id'],$uid);
                } else {//不是全店的活动
                    $result['goods_list'] = (new MallShippingActGoods())->getGoodsList($act_id,$uid);//商品列表
                }
                $result['left_time'] = $return1['end_time'] - time();//剩余时间
            }
        } else {
            $result['goods_list'] = [];
            $result['rule_msg'] = [];
            $result['left_time'] = 0;
        }
        return $result;
    }

    /**满包邮活动列表
     * @param $store_id
     * @param $param
     */
    public function getShippingActList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = !empty($param['name']) ? trim($param['name'], '') : '';
        $status = $param['status'];//活动状态 0未开始 1进行中 2已失效
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];

        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.type', '=', 'shipping'];
        $where[] = ['a.is_del', '=', 0];
        if ($name) {
            $where[] = ['a.name', 'like', '%' . $name . '%'];
        }

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
            $whereOr[] = ['a.type', '=', 'shipping'];
            $whereOr[] = ['a.store_id', '=', $store_id];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }

        $prefix = config('database.connections.mysql.prefix');
        $mallShippingActModel = new MallShippingAct();
        $fields = 's.id,a.`name`,a.store_id,a.start_time,a.end_time,a.`status`,a.desc,a.act_id';
        $lists = $mallShippingActModel->alias('s')
            ->join([$prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'LEFT')
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
        if(!empty($lists)){
            foreach ($lists as &$value) {
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                $value['start_time'] = date('Y-m-d', $value['start_time']);
                $value['end_time'] = date('Y-m-d', $value['end_time']);

                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('shipping',o.activity_type)")];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['act_id']},o.activity_id)")];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
                $value['pay_order_num'] = count($total);//支付单数
                $value['real_income'] = number_format($real_income, 2);//实收金额
                $total = array_column($total, NULL, 'uid');
                $value['pay_order_people'] = count($total);//支付人数
            }
        }
        $res['list'] = $lists;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**
     * 商家后台添加 包邮活动
     * User: 钱大双
     * Date: 2020-10-19
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function addShippingAct($data)
    {
        if ($data['start_time'] > $data['end_time']) {
            throw new \think\Exception(L_('活动开始时间不能大于结束时间'), 1003);
        }

        //商城包邮活动信息
        $shipping = [];
        $shipping['full_type'] = $data['full_type'];
        $shipping['nums'] = $data['nums'];
        $shipping['join_max_num'] = $data['join_max_num'];
        $shipping['is_discount_share'] = $data['is_discount_share'];
        $shipping['discount_card'] = $data['discount_card'];
        $shipping['discount_coupon'] = $data['discount_coupon'];

        //商品活动信息
        $activity = [];
        $start_time = strtotime($data['start_time'] . " 00:00:00");
        $end_time = strtotime($data['end_time'] . " 23:59:59");
        $activity['name'] = $data['name'];
        $activity['act_type'] = $data['act_type'];
        $activity['start_time'] = $start_time;
        $activity['end_time'] = $end_time;
        $activity['desc'] = $data['desc'];
        $activity['sort'] = $data['sort'];

        if ($start_time > time()) {
            $activity['status'] = 0;
        } elseif ($start_time < time() && $end_time > time()) {
            $activity['status'] = 1;
        } elseif ($start_time < time() && $end_time < time()) {
            $activity['status'] = 2;
        }

        if ($data['id'] > 0) {//编辑
            Db::startTrans();
            try {
                $act_id = $data['id'];
                //编辑前的活动商品信息
                $old_activity_goods = (new MallShippingActGoodsService())->getShippingActGoodsByActId($act_id);
                $old_goods_id = array_column($old_activity_goods, 'goods_id');
                $new_goods_id = array_column($data['goods_info'], 'goods_id');
                //获取活动信息
                $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $act_id, 'type' => $data['type']], $field = 'id,start_time,end_time');
                $old_start_time = date('Y-m-d', $activity_info[0]['start_time']);
                $old_end_time = date('Y-m-d', $activity_info[0]['end_time']);
                $diff = array_diff($old_goods_id, $new_goods_id);
                if (!empty($diff) || ($old_start_time !== $data['start_time']) || ($old_end_time !== $data['end_time'])) {
                    $check = $this->_check_activity_goods($data);
                    if ($check['status'] == 0) {
                        (new MallShippingAct())->updateShipping($shipping, $where = ['id' => $act_id]);
                        (new MallShippingActGoodsService())->delShippingAct($where = ['act_id' => $act_id]);

                        $activity_id = $activity_info[0]['id'];
                        (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                        if ($data['act_type'] == 0) {//部分商品参与活动
                            $goods_info = $data['goods_info'];
                            $this->update_shipping_active($goods_info, $act_id, $activity_id);
                        }
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '编辑成功'];
                    } else {
                        $res = ['status' => 1, 'msg' => $check['msg']];
                    }
                } else {
                    (new MallShippingAct())->updateShipping($shipping, $where = ['id' => $act_id]);
                    (new MallShippingActGoodsService())->delShippingAct($where = ['act_id' => $act_id]);

                    $activity_id = $activity_info[0]['id'];
                    (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                    (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                    if ($data['act_type'] == 0) {//部分商品参与活动
                        $goods_info = $data['goods_info'];
                        $this->update_shipping_active($goods_info, $act_id, $activity_id);
                    }
                    //提交事务
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '编辑成功'];
                }
            } catch
            (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        } else {//添加
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $act_id = (new MallShippingAct())->addShipping($shipping);
                    $activity['act_id'] = $act_id;
                    $activity['mer_id'] = $data['mer_id'];
                    $activity['store_id'] = $data['store_id'];
                    $activity['type'] = $data['type'];
                    $activity['create_time'] = time();
                    $activity_id = (new MallActivityService())->addActive($activity);
                    if ($act_id && $activity_id) {
                        if ($data['act_type'] == 0) {//部分商品参与活动
                            $goods_info = $data['goods_info'];
                            $this->update_shipping_active($goods_info, $act_id, $activity_id);
                        }
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
        $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $id, 'type' => 'shipping'], $field = 'id');
        $activity_id = $activity_info[0]['id'];
        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
        $where = ['act_id' => $id, 'type' => 'shipping'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**软删除活动
     * @param $data
     * @param $id
     */
    public function del($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'shipping'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**活动基本信息
     * @param $data
     * @param $id
     */
    public function getInfoById($id)
    {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'shipping'];
        $fields = 's.*,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';
        $res = (new MallShippingAct())->getInfo($where, $fields);
        if ($res['start_time'] < time() && $res['end_time'] < time()) {
            $res['status'] = 2;
        }
        $res['start_time'] = date("Y-m-d", $res['start_time']);
        $res['end_time'] = date("Y-m-d", $res['end_time']);
        if ($res['act_type'] == 0) {//部分商品参与活动
            $goods = (new MallShippingActGoodsService())->getShippingActGoodsByActId($id);
            $goods_id = array_column($goods, 'goods_id');
            $param = [];
            $param['goods_id'] = $goods_id;
            $param['type'] = 'shipping';
            $param['mer_id'] = $res['mer_id'];
            $param['store_id'] = $res['store_id'];
            $param['page'] = 1;
            $param['pageSize'] = 100;
            $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);
            $res['goods_info'] = $goods_info['list'];
        } else {
            $res['goods_info'] = [];
        }

        return $res;
    }

    /**更新包邮活动相关信息
     * @param $goods_info  商品信息
     * @param int $act_id
     * @param int $activity_id
     */
    private function update_shipping_active($goods_info, $act_id = 0, $activity_id = 0)
    {
        $goods = $activity_detail = [];
        foreach ($goods_info as $val) {
            $good['act_id'] = $act_id;
            $good['goods_id'] = $val['goods_id'];
            $goods[] = $good;


            $activity['activity_id'] = $activity_id;
            $activity['goods_id'] = $val['goods_id'];
            $activity_detail[] = $activity;
        }

        if (!empty($goods)) {
            (new MallShippingActGoodsService())->addAllShippingActGoods($goods);
        }

        if (!empty($activity_detail)) {
            (new MallActivityDetailService())->addActiveDatailAll($activity_detail);
        }
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        //满包邮有效活动商品信息
        $curr_time = time();
        $spu_where = [
            ['a.end_time', '>', $curr_time],
            ['a.status', '<>', 2],
            ['a.is_del', '=', 0],
            ['a.act_id', '<>', $data['id']],
            ['a.mer_id', '=', $data['mer_id']],
            ['a.store_id', '=', $data['store_id']],
            ['a.type', '=', 'shipping'],
        ];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time,a.act_type';
        $res = ['status' => 0];
        $spu = (new MallActivityService())->getGoodsInAct($spu_where, $spu_field);

        if (!empty($spu)) {
            $spu = sortArr($spu, 'act_type', SORT_DESC);
            $start_time = strtotime($data['start_time'] . " 00:00:00");
            $end_time = strtotime($data['end_time'] . " 23:59:59");
            foreach ($spu as $value) {
                $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
                if ($flag) {
                    $stime = date('Y-m-d', $value['start_time']);
                    $etime = date('Y-m-d', $value['end_time']);
                    if ($data['act_type'] == 1) {
                        $msg = $stime . '~' . $etime . '已有部分商品参与' . $value['act_name'] . '满包邮活动';
                        $res = ['status' => 1, 'msg' => $msg];
                        break;
                    } else {
                        if ($value['act_type'] == 1) {
                            $msg = $stime . '~' . $etime . '已有部分商品参与' . $value['act_name'] . '满包邮活动';
                            $res = ['status' => 1, 'msg' => $msg];
                            break;
                        } else {
                            if (empty($value['goods_id'])) continue;
                            $goods_info = array_column($data['goods_info'], NULL, 'goods_id');
                            if (isset($goods_info[$value['goods_id']])) {
                                $msg = $goods_info[$value['goods_id']]['name'] . '已参与' . $stime . '~' . $etime . '的' . $value['act_name'] . '满包邮活动';
                                $res = ['status' => 1, 'msg' => $msg];
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

}