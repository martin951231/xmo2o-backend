<?php


namespace app\mall\model\service\activity;

use app\mall\model\service\MallGoodsService;
use app\mall\model\db\MallNewPeriodicPurchase;


use app\mall\model\db\MallOrder;
use think\facade\Db;

class MallNewPeriodicPurchaseService
{
    /**周期购活动列表
     * @param $store_id
     * @param $param
     */
    public function getPeriodicActList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $goods_name = !empty($param['goods_name']) ? trim($param['goods_name'], '') : '';
        $status = $param['status'];//活动状态 0未开始 1进行中 2已失效


        // 搜索条件
        $where = [];

        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.type', '=', 'periodic'];
        $where[] = ['a.is_del', '=', 0];
        if ($goods_name) {
            $where[] = ['g.name', 'like', '%' . $goods_name . '%'];
        }

        if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
            $where[] = ['a.status', '=', $status];
        }

        $prefix = config('database.connections.mysql.prefix');
        $mallNewPeriodicPurchaseModel = new MallNewPeriodicPurchase();
        $count = $mallNewPeriodicPurchaseModel->alias('s')
            ->join([$prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'g'], 's.goods_id = g.goods_id', 'LEFT')
            ->where($where)
            ->count();
        if ($count < 1) {
            $lists = [];
        } else {
            $fields = 's.id,s.goods_id,s.periodic_type,s.periodic_count,s.delay_limit,g.`name` as goods_name,g.image,a.`status`,a.act_id';
            $lists = $mallNewPeriodicPurchaseModel->alias('s')
                ->join([$prefix . 'mall_activity' => 'a'], 's.id = a.act_id', 'LEFT')
                ->join([$prefix . 'mall_goods' => 'g'], 's.goods_id = g.goods_id', 'LEFT')
                ->field($fields)
                ->where($where)
                ->order('a.sort ASC,a.id DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select()
                ->toArray();
            foreach ($lists as &$value) {
                $value['image'] = replace_file_domain($value['image']);
                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('periodic',o.goods_activity_type)")];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['act_id']},o.goods_activity_id)")];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
                // $total = array_column($total, NULL, 'uid');
                $value['pay_order_num'] = count($total);//支付单数
                $value['real_income'] = number_format($real_income, 2);//实收金额
                // $value['pay_order_people'] = count($total);//支付人数
            }
        }
        $res['list'] = $lists;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**
     * 商家后台添加 周期购活动
     * User: 钱大双
     * Date: 2020-10-22
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function addPeriodicAct($data)
    {
        //商城周期购活动信息
        $periodic = [];
        $periodic['goods_id'] = $data['goods_id'];
        $periodic['periodic_type'] = $data['periodic_type'];
        $periodic['periodic_date'] = $data['periodic_date'];
        $periodic['periodic_count'] = $data['periodic_count'];
        $periodic['forward_day'] = $data['forward_day'];
        $periodic['forward_hour'] = $data['forward_hour'];
        $periodic['delay_limit'] = $data['delay_limit'];
        $periodic['freight_type'] = $data['freight_type'];
        $periodic['buy_limit'] = $data['buy_limit'];
        $periodic['is_discount_share'] = $data['is_discount_share'];
        $periodic['discount_card'] = $data['discount_card'];
        $periodic['discount_coupon'] = $data['discount_coupon'];

        //商品活动信息
        $activity = [];
        $activity['name'] = $data['goods_name'].'周期购活动';
        $activity['start_time'] = time();
        $activity['end_time'] = time();
        $activity['desc'] = $data['desc'];
        $activity['sort'] = $data['sort'];

        if ($data['id'] > 0) {//编辑
            Db::startTrans();
            try {
                $act_id = $data['id'];
                (new MallNewPeriodicPurchase())->updatePeriodic($periodic, $where = ['id' => $act_id]);
                //获取活动信息
                $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $act_id, 'type' => $data['type']], $field = 'id');
                $activity_id = $activity_info[0]['id'];
                (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                $this->update_periodic_active($data['goods_id'], $activity_id);
                //提交事务
                Db::commit();
                $res = ['status' => 0, 'msg' => '编辑成功'];
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
            
        } else {//添加
            Db::startTrans();
            try {
                $act_id = (new MallNewPeriodicPurchase())->addPeriodic($periodic);
                $activity['act_id'] = $act_id;
                $activity['mer_id'] = $data['mer_id'];
                $activity['store_id'] = $data['store_id'];
                $activity['type'] = $data['type'];
                $activity['status'] = 1;
                $activity['create_time'] = time();
                $activity_id = (new MallActivityService())->addActive($activity);
                if ($act_id && $activity_id) {
                    $this->update_periodic_active($data['goods_id'], $activity_id);
                    //提交事务
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '添加成功'];
                } else {
                    //回滚事务
                    Db::rollback();
                    $res = ['status' => 1, 'msg' => '添加失败'];
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
        $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $id, 'type' => 'periodic'], $field = 'id');
        $activity_id = $activity_info[0]['id'];
        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
        $where = ['act_id' => $id, 'type' => 'periodic'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**软删除周期购活动
     * @param $data
     * @param $id
     */
    public function del($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'periodic'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    	
    /**周期购活动基本信息
     * @param $data
     * @param $id
     */
    public function getInfoById($id)
    {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'periodic'];
        $fields = 's.*,m.mer_id,m.store_id,m.status';
        $res = (new MallNewPeriodicPurchase())->getInfo($where,$fields);
        $goods_id = [];
        $goods_id[] = $res['goods_id'];
        $param = [];
        $param['goods_id'] = $goods_id;
        $param['type'] = 'shipping';
        $param['mer_id'] = $res['mer_id'];
        $param['store_id'] = $res['store_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;
        $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);
        $res['goods_info'] = $goods_info['list'];
        unset($res['goods_id']);
        unset($res['pre_delivery_time']);
        unset($res['mer_id']);
        unset($res['store_id']);

        return $res;
    }

    /**更新周期购活动相关信息
     * @param $goods_id  商品id
     * @param int $act_id
     * @param int $activity_id
     */
    private function update_periodic_active($goods_id, $activity_id = 0)
    {
        $activity_detail = [];
        $activity['activity_id'] = $activity_id;
        $activity['goods_id'] = $goods_id;
        $activity_detail[] = $activity;
        (new MallActivityDetailService())->addActiveDatailAll($activity_detail);
    }
}