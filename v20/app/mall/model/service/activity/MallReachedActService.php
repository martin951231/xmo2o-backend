<?php
/**
 *  商城营销活动 -- n元n件
 */
namespace app\mall\model\service\activity;

use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallReachedAct as MallReachedActModel;
use app\mall\model\db\MallReachedGoods;
use app\mall\model\service\MallGoodsService;
use Matrix\Exception;
use think\facade\Db;

class MallReachedActService
{
    public $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
        $this->mallReachedActModel = new MallReachedActModel();
        $this->mallReachedGoodsModel = new MallReachedGoods();
        $this->mallActivityModel = new MallActivityModel();
        $this->mallActivityDetailModel = new MallActivityDetail();
    }

    /**
     * @param $act_id
     * @return bool
     * N元N件活动详情及商品列表
     */
    public function getReachedList($act_id,$uid=0){
        return $this->mallReachedActModel->getReachedList($act_id,$uid);
    }


    /**
     * 商家后台-活动列表
     * User: chenxiang
     * Date: 2020/10/22 17:26
     * @param $store_id
     * @param $param
     * @return mixed
     */
    public function getReachedActList($store_id, $param) {
        if(empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = isset($param['name']) ? trim($param['name'], '') : '';
        $status = isset($param['status']) ? $param['status']: 3;//活动状态 0未开始 1进行中 2已失效 3全部
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time']. " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time']. " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];
        $whereLike = [];
        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.type', '=', 'reached'];
        if($name) {
            $whereLike[] = ['a.name','like','%'.$name.'%'];
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
            $whereOr[] = ['a.type', '=', 'reached'];
            $whereOr[] = ['a.store_id', '=', $store_id];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }

            $fields = 'a.id, a.store_id, a.act_id, a.name, a.start_time, a.end_time, a.status';
            $lists = $this->mallActivityModel->alias('a')
                ->join([$this->prefix . 'mall_reached_act' => 'r'], 'a.act_id=r.id', 'LEFT')
                ->field($fields)
                ->where($where)
                ->where($whereLike);
        if(!empty($whereOr) && $status==2){
            $lists = $lists->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $count=$lists->count();
        $lists=$lists->order('a.sort ASC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();
        if(empty($lists)){
            $lists=[];
        }else {
            foreach ($lists as &$value) {
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                switch ($value['status']) { //活动状态 0未开始 1进行中 2已失效
                    case 0 :
                        $value['status_des'] = '未开始';
                        break;
                    case 1 :
                        $value['status_des'] = '进行中';
                        break;
                    case 2 :
                        $value['status_des'] = '已失效';
                        break;
                }
                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('reached',o.activity_type)")];
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
     * n元n件 添加和修改
     * User: chenxiang
     * Date: 2020/10/22 18:33
     * @param $data
     * @param string $type
     * @return bool
     */
    public function addReachedAct($data) {
        $common_data = [];
        $common_data['mer_id'] = $data['mer_id'];
        $common_data['store_id'] = $data['store_id'];
        $common_data['name'] = $data['name'];
        $common_data['start_time'] = strtotime($data['start_time']);
        $common_data['end_time'] = strtotime($data['end_time'] . ' 23:59:59');
        $common_data['type'] = 'reached';
        $common_data['sort'] = $data['sort']; //优先级10最大,其次是20,30,40..
        $common_data['act_type'] = 0;
        $common_data['desc'] = 'reached';
        $common_data['ext'] = $data['goods_info'];

        if (strtotime($data['start_time']) > time()) {
            $common_data['status'] = 0;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time'] . ' 23:59:59') > time()) {
            $common_data['status'] = 1;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time'] . ' 23:59:59') < time()) {
            $common_data['status'] = 2;
        }

        $add_data = [];
        $add_data['money'] = $data['money']; //活动规则 满多少元
        $add_data['nums'] = intval($data['nums']); //活动规则 选多少件
        $add_data['buy_limit'] = empty($data['buy_limit'])?0:$data['buy_limit'];//每人每件商品限购数量  默认0代表不限制件数
        $add_data['is_discount_share'] = intval($data['is_discount_share']); //	是否优惠同享 1开启 2关闭
        if($add_data['is_discount_share'] != 2){
            $add_data['discount_card'] = intval($data['discount_card']); //优惠同享类型 商家会员卡
            $add_data['discount_coupon'] = intval($data['discount_coupon']); //	优惠同享类型 商家优惠券
        }

        $goods_info = json_decode($data['goods_info'], true);//商品的spu集合（json格式）包含图片+名称+价格区(最低~最高)+当前库存(实际剩余库存)

        if(empty($data['id'])) { //添加
            $common_data['create_time'] = $_SERVER['REQUEST_TIME'];
            //启动事务
            Db::startTrans();
            try{
                $check = $this->_check_activity_goods($data);
                if($check['status'] == 0) {
                    $insert_id = $this->mallReachedActModel->insertGetId($add_data);
                    if($insert_id) {
                        $common_data['act_id'] = $insert_id;
                        $activity_id = $this->mallActivityModel->insertGetId($common_data);
                        //加入N元N件活动商品表  mall_reached_goods
                        if(!empty($data['goods_info'])){
                            $goods = $activity_detail = [];
                            foreach ($goods_info as $val) {
                                $goods['act_id'] = $insert_id;
                                $goods['goods_id'] = $val['goods_id'];
                                $goods['act_stock_num'] = $val['stock_num'];
                                $goods['sale_num'] = 0;
                                $this->mallReachedGoodsModel->insert($goods);

                                //加入主活动商品表 mall_activity
                                $activity_detail['activity_id'] = $activity_id;
                                $activity_detail['goods_id'] = $val['goods_id'];
                                $this->mallActivityDetailModel->insert($activity_detail);
                            }
                            //提交事务
                            Db::commit();
                            $res = ['status' => 0, 'msg' => '添加成功'];
                        } else {
                            //回滚事务
                            Db::rollback();
                            $res = ['status' => 1, 'msg' => '添加失败!'];
                        }
                    }
                } else {
                    //提交事务
                    Db::commit();
                    $res = ['status' => 1, 'msg' => $check['msg']];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' =>  '添加失败'];
            }
        } else { //编辑
            if(empty($data['id'])) { //mall_activity表id
                throw new \think\Exception('活动ID不存在');
            }
            $activity_id = $data['id']; //mall_activity表id

            //启动事务
            Db::startTrans();
            try {
                $activity_info = $this->mallActivityModel->field('id, act_id, start_time, end_time')->where(['id' => $activity_id, 'type' => 'reached'])->find();

                $old_goods = (new MallActivityDetailService())->getGoodsInAct(['activity_id' => $activity_id], 'goods_id');

                $check = [];
                $check['status'] = 0;
                $diff = false;
//                var_dump($old_goods);
//                var_dump($goods_info);
                $old_goods_id_arr = array_column($old_goods, 'goods_id');
                $goods_id_arr = array_column($goods_info, 'goods_id');

                //编辑活动时间或商品发生变化
                foreach($goods_id_arr as $value) {
                    $is_diff = in_array($value, $old_goods_id_arr);
                    if($is_diff === false) {
                        $diff = true;
                        break;
                    }
                }

                if($diff || ($common_data['start_time'] != $activity_info['start_time']) || ($common_data['end_time'] != $activity_info['end_time'])) {
                    //检测商品是否合法
                    $check = $this->_check_activity_goods($data);
                }

                if($check['status'] == 0) { //合法
                    $this->mallReachedActModel->where(['id' => $activity_info['act_id']])->data($add_data)->update();
                    $this->mallReachedGoodsModel->where(['act_id' => $activity_info['act_id']])->delete();
                    $this->mallActivityModel->where(['id' => $activity_id])->data($common_data)->update();
                    $this->mallActivityDetailModel->delAll(['activity_id' => $activity_id]);
                    //更新活动相关信息
                    if (!empty($data['goods_info'])) {
                        $goods = $activity_detail = [];
                        foreach ($goods_info as $val) {
                            $goods['act_id'] = $activity_info['act_id'];
                            $goods['goods_id'] = $val['goods_id'];
                            $goods['act_stock_num'] = $val['stock_num'];
                            $goods['sale_num'] = 0;
                            $this->mallReachedGoodsModel->insert($goods);

                            //加入主活动商品表 mall_activity
                            $activity_detail['activity_id'] = $activity_id;
                            $activity_detail['goods_id'] = $val['goods_id'];
                            $this->mallActivityDetailModel->insert($activity_detail);
                        }
                    }
                } else {
                    return $check;
                }
                //提交事务
                Db::commit();
                $res = ['status' => 0, 'msg' => '编辑成功'];
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        }
        return $res;
    }


    /**
     * 检测商品合法性
     * User: chenxiang
     * Date: 2020/11/10 14:19
     * @param $data
     * @return array|int[]
     */
    private function _check_activity_goods($data) {
        //正在参加周期购活动的商品
        $mallGoodsService = new MallGoodsService();
        $spu_periodic = array_column($mallGoodsService->getPeriodicList($data['mer_id']), NULL, 'goods_id');

        //n元n件商品信息
        $goods_spu = json_decode($data['goods_info'], true);

        $res = ['status' => 0];
        foreach ($goods_spu as $key => $value) {
            $periodic_goods_id = $value['goods_id'];

            if(!empty($spu_periodic) && !empty($spu_periodic[$periodic_goods_id])) {
                $msg = '商品' . $value['name'] . '正在参与周期购活动， 请重新添加活动商品';
                $res = ['status' => 1, 'msg' => $msg];
                break;
            } else {
                $types = [
                    'group' => '拼团',
                    'limited' => '秒杀',
                    'bargain' => '砍价',
                    'prepare' => '预售',
                    'reached' => 'N元N价'
                ];

                $curr_time = $_SERVER['REQUEST_TIME'];
                $spu_where = [
                    ['a.end_time', '>', $curr_time],
                    ['a.status', '<>', 2],
                    ['a.is_del', '=', 0],
                    ['a.id', '<>', $data['id']],
                    ['a.type', 'in', array_keys($types)],
                    ['a.mer_id','=',$data['mer_id']],
                    ['a.store_id','=',$data['store_id']],
                    ['d.goods_id', 'in', [$periodic_goods_id]]
                ];

                $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';

                //所有正在参加活动的商品（不含周期购）
                $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);

                $start_time = strtotime($data['start_time']);
                $end_time = strtotime($data['end_time']);
                if($spu) {
                    foreach($spu as $v) {
                        $flag = is_time_cross($start_time, $end_time, $v['start_time'], $v['end_time']);

                        if($flag) {
                            $start_time = date('Y-m-d', $v['start_time']);
                            $end_time = date('Y-m-d', $v['end_time']);
                            $msg = '商品：' . $v['act_name'] . '正在参与' . $start_time . '~' . $end_time . $types[$v['type']] . '活动，请重新添加活动商品';
                            $res = ['status' => 1, 'msg' => $msg];
                            break;
                        }
                    }
                }
            }
        }
        return $res;
    }


    public function getInfoById($id) {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $condition = [];
        $condition['m.type'] = 'reached';
        $condition['m.is_del'] = 0;
        $condition['m.id'] = $id;

        $fields = 'r.*,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';

        $reached_info = $this->mallReachedActModel->getInfo($condition, $fields);

        $reached_info['start_time'] = date("Y-m-d", $reached_info['start_time']);
        $reached_info['end_time'] = date("Y-m-d", $reached_info['end_time']);


        $reached_act_spu = (new MallReachedGoods())->getSpuInfo(['act_id'=>$reached_info['id']], true);

        //var_dump($reached_act_spu);exit();
        $goods_list = [];
        foreach ($reached_act_spu as $key => $value) {
            $param = [];
            $param['goods_id'] = [$value['goods_id']];
            $param['type'] = 'reached';
            $param['mer_id'] = $reached_info['mer_id'];
            $param['store_id'] = $reached_info['store_id'];
            $param['page'] = 1;
            $param['pageSize'] = 100;

            $goods_spu = (new MallGoodsService())->getMallGoodsSelect($param);
            $goods_list[] = $goods_spu['list'][0];
        }

        $reached_info['goods_info'] = $goods_list;
        return $reached_info;
    }


    /**
     * 修改活动状态
     * User: chenxiang
     * Date: 2020/10/22 18:31
     * @param $data
     * @param $id
     * @return mixed
     */
    public function changeState($data, $id) {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'reached';
        (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $id]);
        return $this->mallActivityModel->update($data, $where);
    }

    /**
     * 删除活动
     * User: chenxiang
     * Date: 2020/11/10 18:35
     * @param $data
     * @param $id
     * @return MallActivityModel
     */
    public function del($data, $id) {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'reached';

        return $this->mallActivityModel->update($data, $where);
    }


}