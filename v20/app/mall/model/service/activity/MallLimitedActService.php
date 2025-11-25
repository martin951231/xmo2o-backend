<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallGoodsSku;
use app\mall\model\db\MallLimitedAct as MallLimitedActModel;
use app\mall\model\db\MallLimitedActNotice;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallLimitedSku as MallLimitedSkuModel;
use app\mall\model\db\MallActivityDetail as MallActivityDetailModel;
use app\mall\model\db\MallSixAdverGoods;
use app\mall\model\service\MallGoodsService;
use think\facade\Db;


class MallLimitedActService
{
    public $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
        $this->mallLimitedActModel = new MallLimitedActModel();
        $this->mallActivityModel = new MallActivityModel();
    }

    //限时优惠列表
    public function activityList($cate_id, $page, $uid = 0,$pageSize=10,$group="k.goods_id",$source='',$goods_id=0)
    {
        //print_r($cate_id);exit;
        return $this->mallLimitedActModel->getLimitedList($cate_id, $page, $uid,$pageSize,$group,$source,$goods_id);
    }

    /**
     * @param $data
     * @return int|string
     * 去提醒
     */
    public function addLimitedNotice($data)
    {
        return (new MallLimitedActNotice())->add($data);
    }

    /**
     * 商家后台-限时优惠列表
     * User: chenxiang
     * Date: 2020/10/26 13:31
     * @param $store_id
     * @param $param
     * @return mixed
     */
    public function getLimitedList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = !empty($param['name']) ? trim($param['name'], '') : '';
        $status = isset($param['status']) ? $param['status'] : '';//活动状态 0未开始 1进行中 2已失效
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];

        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.type', '=', 'limited'];
        $where[] = ['a.is_del', '=', 0];
        if ($name) {
            $where[] = ['a.name', 'like', '%' . $name . '%'];
        }

        //活动状态   //0未开始 1进行中 2已失效 3全部活动
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
            $where[] = ['a.status', 'not in', [2]];
        }elseif (intval($status)==1 && empty($startTime) && empty($endTime)){
            $where[] = ['a.start_time', '<=', time()];
            $where[] = ['a.end_time', '>', time()];
            $where[] = ['a.status', 'not in', [2]];
        }elseif (intval($status)==2 && empty($startTime) && empty($endTime)){
            $whereOr[] = ['a.store_id', '=', $store_id];
            $where[] = ['a.end_time', '<', time()];
            $whereOr[] = ['a.status', '=', 2];
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'limited'];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }
        $fields = 's.id,s.time_type,s.cycle_start_time,s.cycle_end_time,a.`name`,a.store_id,a.start_time,a.end_time,a.`status`,a.desc,a.act_id';
        $list = $this->mallLimitedActModel->alias('s')
            ->join([$this->prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'LEFT')
            ->field($fields)
            ->where($where);
        if(!empty($whereOr) && $status==2){
            $list = $list->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $count=$list->count();
        $list=$list->order('a.sort ASC,a.id DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();
        if(!empty($list)){
            foreach ($list as &$value) {
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                if ($value['time_type'] == 1) {
                    $value['start_time'] = date('Y-m-d H:i', $value['start_time']);
                    $value['end_time'] = date('Y-m-d H:i', $value['end_time']);
                } else {
                    $stime = strtotime(date('Y-m-d 00:00:00', $value['start_time'])) + $value['cycle_start_time'];
                    $etime = strtotime(date('Y-m-d 00:00:00', $value['end_time'])) + $value['cycle_end_time'];
                    $value['start_time'] = date('Y-m-d H:i', $stime);
                    $value['end_time'] = date('Y-m-d H:i', $etime);
                }
                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['goods_activity_type', '=', 'limited'];
                $condition_where[] = ['goods_activity_id', '=', $value['act_id']];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
                $value['pay_order_num'] = count($total);//支付单数
                $value['real_income'] = number_format($real_income, 2);//实收金额
                $total = array_column($total, NULL, 'uid');
                $value['pay_order_people'] = count($total);//支付人数
            }
        }else{
            $list=[];
        }
        $res['list'] = $list;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }


    /**
     * 商家后台--限时优惠  添加 / 编辑
     * User: chenxiang
     * Date: 2020/10/26 14:17
     * @param $data
     * @param string $type
     * @return bool
     */
    public function addLimitedAct($data, $type = '')
    {
        //商品活动信息
        $common_data = [];
        $common_data['mer_id'] = $data['mer_id'];
        $common_data['store_id'] = $data['store_id'];
        $common_data['name'] = $data['name'];
        $common_data['sort'] = $data['sort'];
        if ($data['time_type'] == 1) {
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
        } else {
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
        }
        $common_data['start_time'] = $start_time;
        $common_data['end_time'] = $end_time;
        $common_data['type'] = 'limited';

        if ($start_time > time()) {
            $common_data['status'] = 0;
        } elseif ($start_time < time() && $end_time > time()) {
            $common_data['status'] = 1;
        } elseif ($start_time < time() && $end_time < time()) {
            $common_data['status'] = 2;
        }

        $add_data = [];
        $add_data['time_type'] = intval($data['time_type']);  //秒杀类型   1.按固定时间 2.按周期
        $add_data['cycle_type'] = intval($data['cycle_type']);    //周期类型 1每日 2每周 3每月
        $add_data['cycle_date'] = $data['cycle_date']; //每日类型为空，每周类型表示周几（周日用0表示），每月类型表示几号\r\n周期所选日期（以英文逗号隔开的数字字符串）：每日类型为空，每周类型表示周几（周日用0表示），每月类型表示几号
        $add_data['cycle_start_time'] = $data['time_type'] == 1 ? 0 : time_to_second($data['cycle_start_time']);//周期-开始时间 单位s
        $add_data['cycle_end_time'] = $data['time_type'] == 1 ? 0 : time_to_second($data['cycle_end_time']);//周期-开始时间 单位s
        $add_data['remove_type'] = intval($data['remove_type']); //抹零类型 1.抹去角和分 2.抹去分
        $add_data['buy_limit'] = intval($data['buy_limit']); //限购数量 0则表示不限购 范围0-999


        $add_data['is_discount_share'] = intval($data['is_discount_share']); //	是否优惠同享 1开启 2关闭
        $add_data['discount_card'] = intval($data['discount_card']); //优惠同享类型 商家会员卡
        $add_data['discount_coupon'] = intval($data['discount_coupon']); //	优惠同享类型 商家优惠券
        $add_data['notice_type'] = intval($data['notice_type']);
        $add_data['notice_time'] = $data['notice_type'] == 1 ? 0 : intval($data['notice_time']);

        $data['goods_sku'] = json_decode($data['goods_sku'], true); //商品相关信息 json

        if ($type == 'add') { //添加
            $common_data['create_time'] = $_SERVER['REQUEST_TIME'];
            //启动事务
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $insert_id = $this->mallLimitedActModel->addOne($add_data);
                    $common_data['act_id'] = $insert_id;
                    $activity_id = $this->mallActivityModel->insertGetId($common_data);
                    if ($insert_id && $activity_id) {
                        $this->update_limited_sku($data['goods_sku'], $insert_id, $activity_id);
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
        } else { //编辑
            if (empty($data['id'])) {
                throw new \think\Exception('活动ID不存在');
            }

            //启动事务
            Db::startTrans();
            try {
                $act_id = $data['id'];
                //获取活动信息
                $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $act_id, 'type' => $data['type']], $field = 'id,start_time,end_time');
                $old_start_time = date('Y-m-d', $activity_info[0]['start_time']);
                $old_end_time = date('Y-m-d', $activity_info[0]['end_time']);
                $activity_id = $activity_info[0]['id'];
                //编辑前的活动商品信息
                $old_activity_goods = (new MallLimitedSkuModel())->getListBySkuId($where = ['act_id' => $act_id]);
                $old_data = [];
                foreach ($old_activity_goods as $old) {
                    $old_key = $old['goods_id'] . '-' . $old['sku_id'];
                    $arr['is_recommend'] = $old['is_recommend'];
                    $arr['recommend_start_time'] = $old['recommend_start_time'];
                    $arr['recommend_end_time'] = $old['recommend_end_time'];
                    $arr['is_first'] = $old['is_first'];
                    $arr['sort'] = $old['sort'];
                    $old_data[$old_key] = $arr;
                }
                $old_goods_id = array_column($old_activity_goods, 'goods_id');
                $new_goods_id = array_column($data['goods_sku'], 'goods_id');
                $diff = array_diff($old_goods_id, $new_goods_id);
                if (!empty($diff) || ($old_start_time !== $data['start_time']) || ($old_end_time !== $data['end_time'])) {
                    $check = $this->_check_activity_goods($data);
                    if ($check['status'] == 0) {
                        $this->mallLimitedActModel->update($add_data, ['id' => $act_id]);
                        (new MallLimitedSku())->where(['act_id' => $act_id])->delete();
                        $this->mallActivityModel->where(['id' => $activity_id])->data($common_data)->update();
                        (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $activity_id]);
                        //处理商品sku
                        $this->update_limited_sku($data['goods_sku'], $data['id'], $activity_id, $old_data);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '编辑成功'];
                    } else {
                        $res = ['status' => 1, 'msg' => $check['msg']];
                    }
                } else {
                    $this->mallLimitedActModel->update($add_data, ['id' => $act_id]);
                    (new MallLimitedSku())->where(['act_id' => $act_id])->delete();
                    $this->mallActivityModel->where(['id' => $activity_id])->data($common_data)->update();
                    (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $activity_id]);
                    //处理商品sku
                    $this->update_limited_sku($data['goods_sku'], $data['id'], $activity_id, $old_data);
                    //提交事务
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '编辑成功'];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
            }
        }
        return $res;
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        $goods_sku = array_column($data['goods_sku'], NULL, 'goods_id');
        $limited_goods_id = array_column($goods_sku, 'goods_id');
        $res = ['status' => 0];
        $status = 1;
        //正在参加周期购活动的商品
        $mallGoodsService = new MallGoodsService();
        $spu_periodic = array_column($mallGoodsService->getPeriodicList($data['mer_id']), NULL, 'goods_id');
        if (!empty($spu_periodic)) {
            foreach ($goods_sku as $goods) {
                if (isset($spu_periodic[$goods['goods_id']])) {
                    $msg = $goods['name'] . '已参与周期购活动，请重新添加活动商品';
                    $status = 2;
                    $res = ['status' => 1, 'msg' => $msg];
                    break;
                }
            }
        }

        if ($status == 1) {
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
                ['a.act_id', '<>', $data['id']],//秒杀传的是act_id
                ['a.mer_id', '=', $data['mer_id']],
                ['a.store_id', '=', $data['store_id']],
                ['a.type', 'in', array_keys($types)],
                ['d.goods_id', 'in', $limited_goods_id]
            ];

            $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
            //所有正在参加活动的商品（不含周期购）
            $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
            if (!empty($spu)) {
                $start_time = strtotime($data['start_time'] . " 00:00:00");
                $end_time = strtotime($data['end_time'] . " 23:59:59");
                foreach ($spu as $value) {
                    $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
                    if ($flag) {
                        $stime = date('Y-m-d', $value['start_time']);
                        $etime = date('Y-m-d', $value['end_time']);
                        if (in_array($value['goods_id'], $limited_goods_id)) {
                            $msg = $goods_sku[$value['goods_id']]['name'] . '已参与' . $stime . '~' . $etime . '的' . $value['act_name'] . '活动';
                            $res = ['status' => 1, 'msg' => $msg];
                            break;
                        }
                    }
                }
            }

        }

        return $res;

    }


    public function update_limited_sku($goods_sku, $act_id = 0, $activity_id = 0, $old_data = array())
    {
        $activity_detail = $goods = [];
        foreach ($goods_sku as $val) {
            //单个商品多个sku
            $activity['activity_id'] = $activity_id;
            $activity['goods_id'] = $val['goods_id'];
            $activity_detail[$val['goods_id']] = $activity;
            if (!empty($val['sku_info'])) {
                foreach ($val['sku_info'] as $v) {
                    $good['act_id'] = $act_id;
                    $good['sku_id'] = $v['sku_id'];
                    $good['goods_id'] = $val['goods_id'];
                    $good['act_stock_num'] = $v['act_stock_num'];
                    $good['act_price'] = $v['act_price'];
                    $good['discount_rate'] = $v['discount_rate'];
                    $good['reduce_money'] = $v['reduce_money'];

                    if (!empty($old_data)) {
                        $key = $val['goods_id'] . '-' . $v['sku_id'];
                        $good['is_recommend'] = isset($old_data[$key]['is_recommend']) ? $old_data[$key]['is_recommend'] : '';
                        $good['recommend_start_time'] = isset($old_data[$key]['recommend_start_time']) ? $old_data[$key]['recommend_start_time'] : '';
                        $good['recommend_end_time'] = isset($old_data[$key]['recommend_end_time']) ? $old_data[$key]['recommend_end_time'] : '';
                        $good['is_first'] = isset($old_data[$key]['is_first']) ? $old_data[$key]['is_first'] : '';
                        $good['sort'] = isset($old_data[$key]['sort']) ? $old_data[$key]['sort'] : '';
                    }
                    $goods[] = $good;
                }
            }
        }

        if (!empty($goods)) {
            (new MallLimitedSkuModel())->insertAll($goods);
        }

        if (!empty($activity_detail)) {
            (new MallActivityDetailModel())->insertAll($activity_detail);
        }
    }

    /**
     * 获取编辑页面信息
     * User: chenxiang
     * Date: 2020/10/26 16:14
     * @param $id
     * @return array
     */
    public function getInfoById($id)
    {

        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'limited'];
        $fields = 's.*,m.name,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';
        $res = $this->mallLimitedActModel->getInfo($where, $fields);
        if ($res['start_time'] < time() && $res['end_time'] < time()) {
            $res['status'] = 2;
        }

        if ($res['time_type'] == 1) {
            $res['start_time'] = date("Y-m-d H:i:s", $res['start_time']);
            $res['end_time'] = date("Y-m-d H:i:s", $res['end_time']);
            $res['cycle_start_time'] = '00:00';
            $res['cycle_end_time'] = '00:00';
        } else {
            $res['start_time'] = date("Y-m-d", $res['start_time']);
            $res['end_time'] = date("Y-m-d", $res['end_time']);
            $res['cycle_start_time'] = second_to_time($res['cycle_start_time']);
            $res['cycle_end_time'] = second_to_time($res['cycle_end_time']);
        }
        $res['notice_time'] = $res['notice_type'] == 1 ? 0 : $res['notice_time'];
        $limited_act_sku = (new MallLimitedSku())->where(['act_id' => $id])->select();
        if (!empty($limited_act_sku)) {
            $limited_act_sku = $limited_act_sku->toArray();
        }
        $goods_id = [];
        $sku_id = [];
        $temp = [];
        foreach ($limited_act_sku as $value) {
            $goods_id[] = $value['goods_id'];
            $sku_id[$value['goods_id']][] = $value['sku_id'];
            $temp[$value['goods_id'] . '-' . $value['sku_id']] = $value;
        }

        $param = [];
        $param['goods_id'] = $goods_id;
        $param['sku_id'] = $sku_id;
        $param['type'] = 'limited';
        $param['mer_id'] = $res['mer_id'];
        $param['store_id'] = $res['store_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;

        $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);
        
        !empty($goods_info['list']) && $goods_info['list'] = $goods_info['list']->toArray();

        if(!is_array($goods_info['list'])){
            $goods_info['list'] = $goods_info['list']->toArray();
        }
        foreach ($goods_info['list'] as $key => $val) {
            foreach ($val['sku_info'] as $k => $info) {
                $goods_info['list'][$key]['sku_info'][$k]['act_stock_num'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['act_stock_num'];
                $goods_info['list'][$key]['sku_info'][$k]['act_price'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['act_price'];
                $goods_info['list'][$key]['sku_info'][$k]['discount_rate'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['discount_rate'];
                $goods_info['list'][$key]['sku_info'][$k]['reduce_money'] = $temp[$val['goods_id'] . '-' . $info['sku_id']]['reduce_money'];
                if($info['sku_id']){
                    $where=[['sku_id','=',$info['sku_id']]];
                    $arr=(new MallGoodsSku())->getOne($where);
                    $goods_info['list'][$key]['sku_info'][$k]['image'] =empty($arr['image'])?$goods_info['list'][$key]['image']:replace_file_domain($arr['image']);
                }else{
                    $goods_info['list'][$key]['sku_info'][$k]['image'] =$goods_info['list'][$key]['image'];
                }
            }
        }
        $res['goods_info'] = $goods_info['list'];
        return $res;
    }

    /**
     * 商家后台--状态修改
     * User: chenxiang
     * Date: 2020/10/26 14:18
     * @param $data
     * @param $id
     * @return MallActivityModel
     */
    public function changeState($data, $id)
    {
        $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $id, 'type' => 'limited'], $field = 'id');
        $activity_id = $activity_info[0]['id'];
        $field = 'd.goods_id';
        $detailIds = isset((new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $activity_id], $field)[0]) ? (new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $activity_id], $field)[0] : [];
        //如果该活动被装修，失效时删除对应的装修关联商品
        (new MallSixAdverGoods())->delSome([['goods_id', 'in', $detailIds]]);
        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
        $where = ['act_id' => $id, 'type' => 'limited'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;

    }


    /**软删除活动
     * @param $data
     * @param $id
     */
    public function del($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'limited'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**
     * @return mixed
     * 查找分类列表
     */
    public function getCategoryList()
    {
        return (new MallLimitedSku())->getCategoryList();
    }


}