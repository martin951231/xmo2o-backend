<?php

/**
 *  平台活动推荐
 */

namespace app\mall\model\service;

use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallGoods as MallGoodsModel;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewBargainAct;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallSixAdverGoods;
use think\facade\Db;


class MallActivityRecommendService
{

    public $MallGoodsModel = null;

    public function __construct()
    {
        $this->MallGoodsModel = new MallGoodsModel();
    }


    /**
     * 获取秒杀活动推荐列表
     * User: chenxiang
     * Date: 2020/11/16 10:42
     */
    public function getLimitedRecommendList($param,$order='sk.is_first DESC,a.id DESC')
    {

        try {
            $page = isset($param['page']) ? $param['page'] : 1;
            $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
            $goods_name = isset($param['keywords']) ? trim($param['keywords'], '') : '';
            $is_recommmend = isset($param['isRecommend']) ? $param['isRecommend'] : 2; //推荐状态 1推荐 2未推荐

            $merList = isset($param['merList']) ? $param['merList'] : '';
            $storeList = isset($param['storeList']) ? $param['storeList'] : '';

            $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
            $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

            if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
                throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
            }

            //搜索条件
            $where = [];
            if ($goods_name) {
                $where[] = ['g.name', 'like', '%' . $goods_name . '%'];
            }

            if (!empty($is_recommmend)){
                if ($is_recommmend == 2) {
                    $where[] = ['sk.is_recommend', '<>', 1];
                } else {
                    $where[] = ['sk.is_recommend', '=', $is_recommmend];
                }
            }

            if ($startTime && $endTime) {
                /*  $where[] = ['a.start_time', 'between', [$startTime, $endTime]];
                  $where[] = ['a.end_time', 'between', [$startTime, $endTime]];*/
                $where[] = ['a.start_time', 'exp', Db::raw('between ' . $startTime . ' and ' . $endTime . ' OR a.end_time between ' . $startTime . ' and ' . $endTime)];
            } elseif ($startTime) {
                $where[] = ['a.start_time', '>=', $startTime];
            } elseif ($endTime) {
                $where[] = ['a.end_time', '<=', $endTime];
            }

            if(isset($param['unrelated']) && !empty($param['unrelated'])){
                $where[] = ['g.goods_id', 'not in', $param['unrelated']];
            }
            if(isset($param['related']) && !empty($param['related'])){
                $where[] = ['g.goods_id', 'in', $param['related']];
            }

            //商家筛选
            if (!empty($merList)) {
                $where[] = ['a.mer_id', 'in', $merList];
            }
            //店铺筛选
            if (!empty($storeList)) {
                $where[] = ['a.store_id', 'in', $storeList];
            }

            $where[] = ['a.is_del', '=', 0];
            $where[] = ['a.type', '=', 'limited'];
            $where[] = ['a.status', 'in', [0, 1]];
            $where[] = ['a.end_time', '>', time()];
            //goods_type 值 sku  spu
            $prefix = config('database.connections.mysql.prefix');
            $mallActivityModel = new MallActivityModel();
            $fields = 'a.*,mer.name as mer_name,s.name as store_name,g.name as goods_name,g.image,g.price,g.min_price,g.max_price,g.goods_type,sk.act_price,sk.goods_id as act_goods_id,sk.is_recommend,sk.recommend_start_time,sk.recommend_end_time,sk.is_first,sk.sort,min(sk.act_price) as min_act_price, max(sk.act_price) as max_act_price';
            $list1 = $mallActivityModel->alias('a')
                ->join([$prefix . 'mall_limited_act' => 'l'], 'a.act_id=l.id', 'LEFT')
                ->join([$prefix . 'mall_limited_sku' => 'sk'], 'sk.act_id=l.id', 'LEFT')
                ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=sk.goods_id', 'LEFT')
                ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
                ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->group('sk.goods_id');
            
            $list2 = $mallActivityModel->alias('a')
                ->join([$prefix . 'mall_limited_act' => 'l'], 'a.act_id=l.id', 'LEFT')
                ->join([$prefix . 'mall_limited_sku' => 'sk'], 'sk.act_id=l.id', 'LEFT')
                ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=sk.goods_id', 'LEFT')
                ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
                ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
                ->where($where)
                ->group('sk.goods_id');
            $count = $list2->count();
            $list = $list1
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            //需要优化
            if ($list) {
                foreach ($list as &$value) {
                    $value['image'] = replace_file_domain($value['image']);
                    if (($value['start_time'] < time() && $value['end_time'] < time()) || $value['status'] == 2) {
                        $value['status'] = 2;
                    } else if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                        $value['status'] = 1;
                    } else if ($value['start_time'] > time() && $value['end_time'] > time() && $value['status'] != 2) {
                        $value['status'] = 0;
                    }
                    $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                    $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                    $value['act_price'] = get_format_number($value['act_price']);
                    $value['price'] = get_format_number($value['price']);

                    $value['max_act_price'] = get_format_number($value['max_act_price']);
                    $value['min_act_price'] = get_format_number($value['min_act_price']);
                    $value['max_price'] = get_format_number($value['min_price']);
                    $value['min_price'] = get_format_number($value['min_price']);

                    if ($value['is_recommend'] == 1) {
                        $value['recommend_start_time'] = date('Y-m-d H:i:s', $value['recommend_start_time']);
                        $value['recommend_end_time'] = date('Y-m-d H:i:s', $value['recommend_end_time']);
                    }

                    $condition_where = [];
                    $condition_where[] = ['o.store_id', '=', $value['store_id']];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('limited',o.activity_type)")];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['id']},o.activity_id)")];
                    $condition_where[] = ['o.status', '>=', 10];
                    $condition_where[] = ['o.status', '<=', 49];
                    $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                    $real_income = array_sum(array_column($total, 'money_real'));
                    $value['real_money'] = $real_income;//实收金额

                }
            }
            $res['list'] = $list;
            $res['pageSize'] = $pageSize;
            $res['count'] = $count;
            $res['page'] = $page;
            return $res;
        } catch (\Exception $e) {
            dd($e);
        }
    }


    /**
     * 推荐至首页操作 / 批量
     * User: chenxiang
     * Date: 2020/11/16 16:17
     * @param $param
     * @return MallLimitedSku
     */
    public function editLimitedRecommend($param)
    {

        if (is_array($param['activity_id'])) {

            foreach ($param['activity_id'] as $key => $value) {
                $where = [];
                $where[] = ['id', '=', $value];
                $where[] = ['is_del', '=', 0];
                $where[] = ['type', '=', 'limited'];
                $where[] = ['status', 'in', [0, 1]];

                $limited_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

                $data = [];
                $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;

                if ($param['is_recommend'] == 1) { //置顶
                    if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                        $data['recommend_start_time'] = $limited_info['start_time'];
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', $limited_info['end_time']) . ' 23:59:59');
                    } else {
                        $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                    }
                } else { //取消置顶
                    $data['recommend_start_time'] = '';
                    $data['recommend_end_time'] = '';
                }

                (new MallLimitedSku())->where(['act_id' => $limited_info['act_id'], 'goods_id' => $param['goods_id'][$key]])->update($data);
                (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id'][$key]]);
            }
            return true;
        } else {
            $where = [];
            $where[] = ['id', '=', $param['activity_id']];
            $where[] = ['is_del', '=', 0];
            $where[] = ['type', '=', 'limited'];
            $where[] = ['status', 'in', [0, 1]];

            $limited_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

            $data = [];
            $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;

            if ($param['is_recommend'] == 1) { //置顶
                if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                    $data['recommend_start_time'] = $limited_info['start_time'];
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', $limited_info['end_time']) . ' 23:59:59');
                } else {
                    $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                }
            } else { //取消置顶
                $data['recommend_start_time'] = '';
                $data['recommend_end_time'] = '';
                //删除首页的关联商品
                (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id']]);
            }

            (new MallLimitedSku())->where(['act_id' => $limited_info['act_id'], 'goods_id' => $param['goods_id']])->update($data);
            (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id']]);
            return true;
        }
    }

    /**
     * 秒杀商品-设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:24
     */
    public function setFirstLimited($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'limited'];
        $where[] = ['status', 'in', [0, 1]];

        $limited_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        //检测是否已有置顶商品
        $check = (new MallLimitedSku())->field('act_id, goods_id')->where(['is_first' => 1])->find();
        if ($check['act_id'] != $param['act_id'] || $check['goods_id'] != $param['goods_id']) { //取消其他商品置顶
            (new MallLimitedSku())->where(['is_first' => 1])->update(['is_first' => 0]);
        }

        $res = (new MallLimitedSku())->where(['act_id' => $limited_info['act_id'], 'goods_id' => $param['goods_id']])->update(['is_first' => $param['is_first']]);

        return $res;
    }

    /**
     * 秒杀商品-设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:57
     * @param $param
     * @return MallLimitedSku
     */
    public function setSortLimited($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'limited'];
        $where[] = ['status', 'in', [0, 1]];

        $limited_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        $res = (new MallLimitedSku())->where(['act_id' => $limited_info['act_id'], 'goods_id' => $param['goods_id']])->update(['sort' => $param['sort']]);

        return $res;
    }

    /**
     * 获取拼团 活动推荐列表
     * User: chenxiang
     * Date: 2020/11/16 10:42
     */
    public function getGroupRecommendList($param,$order='gr.is_first DESC,a.id DESC')
    {

        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $goods_name = isset($param['keywords']) ? trim($param['keywords'], '') : '';
        $is_recommmend = isset($param['isRecommend']) ? $param['isRecommend'] : 2; //推荐状态 1推荐 2未推荐

        $merList = isset($param['merList']) ? $param['merList'] : '';
        $storeList = isset($param['storeList']) ? $param['storeList'] : '';

        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        //搜索条件
        $where = [];
        if ($goods_name) {
            $where[] = ['g.name', 'like', '%' . $goods_name . '%'];
        }

        if ($is_recommmend) {
            $where[] = ['gr.is_recommend', '=', $is_recommmend];
        }

        if ($startTime && $endTime) {
            /*     $where[] = ['a.start_time', 'between', [$startTime, $endTime]];
                 $where[] = ['a.end_time', 'between', [$startTime, $endTime]];*/
            $where[] = ['a.start_time', 'exp', Db::raw('between ' . $startTime . ' and ' . $endTime . ' OR a.end_time between ' . $startTime . ' and ' . $endTime)];

        } elseif ($startTime) {
            $where[] = ['a.start_time', '>=', $startTime];
        } elseif ($endTime) {
            $where[] = ['a.end_time', '<=', $endTime];
        }

        if(isset($param['unrelated']) && !empty($param['unrelated'])){
            $where[] = ['g.goods_id', 'not in', $param['unrelated']];
        }
        if(isset($param['related']) && !empty($param['related'])){
            $where[] = ['g.goods_id', 'in', $param['related']];
        }

        //商家筛选
        if (!empty($merList)) {
            $where[] = ['a.mer_id', 'in', $merList];
        }
        //店铺筛选
        if (!empty($storeList)) {
            $where[] = ['a.store_id', 'in', $storeList];
        }

        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.type', '=', 'group'];
        $where[] = ['a.status', 'in', [0, 1]];
        $where[] = ['a.end_time', '>', time()];

        $prefix = config('database.connections.mysql.prefix');
        $mallActivityModel = new MallActivityModel();

        $count = $mallActivityModel->alias('a')
            ->join([$prefix . 'mall_new_group_act' => 'gr'], 'a.act_id=gr.id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=gr.goods_id', 'LEFT')
            ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
            ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
            ->where($where)
            ->count();
//            var_dump($mallActivityModel->getLastSql());exit();
        if ($count < 1) {
            $list = [];
        } else {
            //goods_type 值 sku  spu
            $fields = 'a.*,mer.name as mer_name,s.name as store_name,g.name as goods_name,g.image,g.price,g.min_price,g.max_price,g.goods_type,gr.goods_id as act_goods_id,gr.is_recommend,gr.recommend_start_time,gr.recommend_end_time,gr.is_first,gr.sort';
            //min(sk.act_price) as min_act_price, max(sk.act_price) as max_act_price,
            //a.start_time,a.end_time,a.status,

            $list = $mallActivityModel->alias('a')
                ->join([$prefix . 'mall_new_group_act' => 'gr'], 'a.act_id=gr.id', 'LEFT')
                ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=gr.goods_id', 'LEFT')
                ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
                ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            //需要优化
            if (!empty($list)) {
                foreach ($list as &$value) {
                    $value['image'] = replace_file_domain($value['image']);
                    $value['price'] = get_format_number($value['price']);
                    if ($value['goods_type'] == 'sku') {
                        $where = ['goods_id' => $value['act_goods_id'], 'act_id' => $value['act_id']];
                        $min_act_price = (new MallNewGroupSku())->getPice($where) ? number_format((new MallNewGroupSku())->getPice($where), 2) : 0;
                        $max_act_price = (new MallNewGroupSku())->getMaxPice($where) ? number_format((new MallNewGroupSku())->getMaxPice($where), 2) : 0;
                        $value['min_act_price'] = get_format_number($min_act_price);
                        $value['max_act_price'] = get_format_number($max_act_price);
                    } else {
                        $where = ['goods_id' => $value['act_goods_id'], 'act_id' => $value['act_id']];
                        $price = (new MallNewGroupSku())->getPice($where);
                        $value['act_price'] = get_format_number($price);
                    }
                    if (($value['start_time'] < time() && $value['end_time'] < time()) || $value['status'] == 2) {
                        $value['status'] = 2;
                    } else if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                        $value['status'] = 1;
                    } else if ($value['start_time'] > time() && $value['end_time'] > time() && $value['status'] != 2) {
                        $value['status'] = 0;
                    }

                    $value['max_price'] = get_format_number($value['max_price'] * 1);
                    $value['min_price'] = get_format_number($value['min_price'] * 1);
                    $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                    $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);

                    // $value['act_price'] = empty($value['act_price'])?number_format($value['act_price'], 2):0.00;

                    if ($value['is_recommend'] == 1) {
                        $value['recommend_start_time'] = date('Y-m-d', $value['recommend_start_time']);
                        $value['recommend_end_time'] = date('Y-m-d', $value['recommend_end_time']);
                    }

                    $condition_where = [];
                    $condition_where[] = ['o.store_id', '=', $value['store_id']];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('group',o.activity_type)")];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['id']},o.activity_id)")];
                    $condition_where[] = ['o.status', '>=', 10];
                    $condition_where[] = ['o.status', '<=', 49];
                    $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                    $real_income = array_sum(array_column($total, 'money_real'));
                    $value['real_money'] = $real_income;//实收金额

                }
            }
        }

        $res['list'] = $list;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;

    }


    /**
     * 拼团-推荐至首页操作
     * User: chenxiang
     * Date: 2020/11/17 16:57
     * @param $param
     * @return MallNewGroupAct
     */
    public function editGroupRecommend($param)
    {
        if (is_array($param['activity_id'])) {
            foreach ($param['activity_id'] as $key => $value) {
                $where = [];
                $where[] = ['id', '=', $value];
                $where[] = ['is_del', '=', 0];
                $where[] = ['type', '=', 'group'];
                $where[] = ['status', 'in', [0, 1]];

                $group_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

                $data = [];
                $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;
                if ($param['is_recommend'] == 1) { //推荐
                    if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                        $data['recommend_start_time'] = $group_info['start_time'];
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', $group_info['end_time']) . ' 23:59:59');
                    } else {
                        $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                    }
                } else {
                    $data['recommend_start_time'] = '';
                    $data['recommend_end_time'] = '';
                }

                (new MallNewGroupAct())->where(['id' => $group_info['act_id'], 'goods_id' => $param['goods_id'][$key]])->update($data);
                (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id'][$key]]);
            }
            return true;
        } else {
            $where = [];
            $where[] = ['id', '=', $param['activity_id']];
            $where[] = ['is_del', '=', 0];
            $where[] = ['type', '=', 'group'];
            $where[] = ['status', 'in', [0, 1]];

            $group_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

            $data = [];
            $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;
            if ($param['is_recommend'] == 1) { //推荐
                if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                    $data['recommend_start_time'] = $group_info['start_time'];
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', $group_info['end_time']) . ' 23:59:59');
                } else {
                    $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                }
            } else {
                $data['recommend_start_time'] = '';
                $data['recommend_end_time'] = '';
            }
            (new MallNewGroupAct())->where(['id' => $group_info['act_id'], 'goods_id' => $param['goods_id']])->update($data);
            (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id']]);
            return true;
        }

    }

    /**
     * 拼团商品-设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:24
     */
    public function setFirstGroup($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'group'];
        $where[] = ['status', 'in', [0, 1]];

        $group_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        //检测是否已有置顶商品
        $check = (new MallNewGroupAct())->field('id, goods_id')->where(['is_first' => 1])->find();
        if ($check['id'] != $param['act_id'] && $check['goods_id'] != $param['goods_id']) { //取消其他商品置顶
            (new MallNewGroupAct())->where(['is_first' => 1])->update(['is_first' => 0]);
        }

        $res = (new MallNewGroupAct())->where(['id' => $group_info['act_id'], 'goods_id' => $param['goods_id']])->update(['is_first' => $param['is_first']]);

        return $res;
    }

    /**
     * 拼团商品-设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:57
     * @param $param
     * @return MallNewGroupAct
     */
    public function setSortGroup($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'group'];
        $where[] = ['status', 'in', [0, 1]];

        $group_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        $res = (new MallNewGroupAct())->where(['id' => $group_info['act_id'], 'goods_id' => $param['goods_id']])->update(['sort' => $param['sort']]);

        return $res;
    }


    /**
     * 获取砍价 活动推荐列表
     * User: chenxiang
     * Date: 2020/11/16 10:42
     */
    public function getBargainRecommendList($param,$order='b.is_first DESC,a.id DESC')
    {

        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $goods_name = isset($param['keywords']) ? trim($param['keywords'], '') : '';
        $is_recommmend = isset($param['isRecommend']) ? $param['isRecommend'] : 2; //推荐状态 1推荐 2未推荐

        $merList = isset($param['merList']) ? $param['merList'] : '';
        $storeList = isset($param['storeList']) ? $param['storeList'] : '';

        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        //搜索条件
        $where = [];
        if ($goods_name) {
            $where[] = ['g.name', 'like', '%' . $goods_name . '%'];
        }

        if ($is_recommmend) {
            $where[] = ['b.is_recommend', '=', $is_recommmend];
        }

        if ($startTime && $endTime) {
            /* $where[] = ['a.start_time', 'between', [$startTime, $endTime]];
             $where[] = ['a.end_time', 'between', [$startTime, $endTime]];*/
            $where[] = ['a.start_time', 'exp', Db::raw('between ' . $startTime . ' and ' . $endTime . ' OR a.end_time between ' . $startTime . ' and ' . $endTime)];
        } elseif ($startTime) {
            $where[] = ['a.start_time', '>=', $startTime];
        } elseif ($endTime) {
            $where[] = ['a.end_time', '<=', $endTime];
        }

        if(isset($param['unrelated']) && !empty($param['unrelated'])){
            $where[] = ['g.goods_id', 'not in', $param['unrelated']];
        }
        if(isset($param['related']) && !empty($param['related'])){
            $where[] = ['g.goods_id', 'in', $param['related']];
        }

        //商家筛选
        if (!empty($merList)) {
            $where[] = ['a.mer_id', 'in', $merList];
        }

        //店铺筛选
        if (!empty($storeList)) {
            $where[] = ['a.store_id', 'in', $storeList];
        }

        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.type', '=', 'bargain'];
        $where[] = ['a.status', 'in', [0, 1]];
        $where[] = ['a.end_time', '>', time()];

        $prefix = config('database.connections.mysql.prefix');
        $mallActivityModel = new MallActivityModel();

        $count = $mallActivityModel->alias('a')
            ->join([$prefix . 'mall_new_bargain_act' => 'b'], 'a.act_id=b.id', 'LEFT')
            ->join([$prefix . 'mall_new_bargain_sku' => 'sk'], 'a.act_id=sk.act_id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=b.goods_id', 'LEFT')
            ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
            ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
            ->where($where)
            ->count();

        if ($count < 1) {
            $list = [];
        } else {
            //goods_type 值 sku  spu
            $fields = 'a.*,mer.name as mer_name,s.name as store_name,g.name as goods_name,g.image,g.price,g.min_price,g.max_price,g.goods_type,sk.act_price,b.goods_id as act_goods_id,b.is_recommend,b.recommend_start_time,b.recommend_end_time,b.is_first,b.sort';
            //min(sk.act_price) as min_act_price, max(sk.act_price) as max_act_price,
            //a.start_time,a.end_time,a.status,

            $list = $mallActivityModel->alias('a')
                ->join([$prefix . 'mall_new_bargain_act' => 'b'], 'a.act_id=b.id', 'LEFT')
                ->join([$prefix . 'mall_new_bargain_sku' => 'sk'], 'a.act_id=sk.act_id', 'LEFT')
                ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=b.goods_id', 'LEFT')
                ->join([$prefix . 'merchant' => 'mer'], 'mer.mer_id=a.mer_id', 'LEFT')
                ->join([$prefix . 'merchant_store' => 's'], 's.store_id=a.store_id', 'LEFT')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            //需要优化
            foreach ($list as &$value) {
                $value['image'] = replace_file_domain($value['image']);
                if (($value['start_time'] < time() && $value['end_time'] < time()) || $value['status'] == 2) {
                    $value['status'] = 2;
                } else if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                } else if ($value['start_time'] > time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 0;
                }
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                $value['price'] = get_format_number($value['price'] * 1);
                $value['act_price'] = get_format_number($value['act_price'] * 1);
                if ($value['is_recommend'] == 1) {
                    $value['recommend_start_time'] = date('Y-m-d', $value['recommend_start_time']);
                    $value['recommend_end_time'] = date('Y-m-d', $value['recommend_end_time']);
                }
                if ($value['goods_type'] == 'sku') {
                    $value['goods_type'] = 'spu';
                }

                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $value['store_id']];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('bargain',o.activity_type)")];
                $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['id']},o.activity_id)")];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
                $value['real_money'] = get_format_number($real_income);//实收金额

            }
        }

        $res['list'] = $list;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;

    }


    /**
     * 砍价-推荐至首页操作
     * User: chenxiang
     * Date: 2020/11/17 16:57
     * @param $param
     * @return MallNewBargainAct
     */
    public function editBargainRecommend($param)
    {
        if (is_array($param['activity_id'])) {
            foreach ($param['activity_id'] as $key => $value) {
                $where = [];
                $where[] = ['id', '=', $value];
                $where[] = ['is_del', '=', 0];
                $where[] = ['type', '=', 'bargain'];
                $where[] = ['status', 'in', [0, 1]];

                $bargain_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

                $data = [];
                $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;
                if ($param['is_recommend'] == 1) { //置顶
                    if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                        $data['recommend_start_time'] = $bargain_info['start_time'];
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', $bargain_info['end_time']) . ' 23:59:59');
                    } else {
                        $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                        $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                    }
                } else { //取消置顶
                    $data['recommend_start_time'] = '';
                    $data['recommend_end_time'] = '';
                }
                (new MallNewBargainAct())->where(['id' => $bargain_info['act_id'], 'goods_id' => $param['goods_id'][$key]])->update($data);
                (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id'][$key]]);
            }
            return true;
        } else {
            $where = [];
            $where[] = ['id', '=', $param['activity_id']];
            $where[] = ['is_del', '=', 0];
            $where[] = ['type', '=', 'bargain'];
            $where[] = ['status', 'in', [0, 1]];

            $bargain_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where($where)->find();

            $data = [];
            $data['is_recommend'] = isset($param['is_recommend']) ? $param['is_recommend'] : 2;
            if ($param['is_recommend'] == 1) { //置顶
                if (empty($param['rec_start_time']) || empty($param['rec_end_time'])) {//为空时 默认活动时间
                    $data['recommend_start_time'] = $bargain_info['start_time'];
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', $bargain_info['end_time']) . ' 23:59:59');
                } else {
                    $data['recommend_start_time'] = strtotime($param['rec_start_time']);
                    $data['recommend_end_time'] = strtotime(date('Y-m-d', strtotime($param['rec_end_time'])) . ' 23:59:59');
                }
            } else { //取消置顶
                $data['recommend_start_time'] = '';
                $data['recommend_end_time'] = '';
            }

            (new MallNewBargainAct())->where(['id' => $bargain_info['act_id'], 'goods_id' => $param['goods_id']])->update($data);
            (new MallSixAdverGoods())->delSome(['goods_id' => $param['goods_id']]);
            return true;
        }
    }

    /**
     * 砍价商品-设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:24
     */
    public function setFirstBargain($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'bargain'];
        $where[] = ['status', 'in', [0, 1]];

        $bargain_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        //检测是否已有置顶商品
        $check = (new MallNewBargainAct())->field('id, goods_id')->where(['is_first' => 1])->find();
        if ($check['id'] != $param['act_id'] && $check['goods_id'] != $param['goods_id']) { //取消其他商品置顶
            (new MallNewBargainAct())->where(['is_first' => 1])->update(['is_first' => 0]);
        }

        $res = (new MallNewBargainAct())->where(['id' => $bargain_info['act_id'], 'goods_id' => $param['goods_id']])->update(['is_first' => $param['is_first']]);

        return $res;
    }

    /**
     * 砍价商品-设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:57
     * @param $param
     * @return MallNewBargainAct
     */
    public function setSortBargain($param)
    {
        $where = [];
        $where[] = ['id', '=', $param['activity_id']];
        $where[] = ['is_del', '=', 0];
        $where[] = ['type', '=', 'bargain'];
        $where[] = ['status', 'in', [0, 1]];

        $bargain_info = (new MallActivityModel())->field('id,act_id')->where($where)->find();

        $res = (new MallNewBargainAct())->where(['id' => $bargain_info['act_id'], 'goods_id' => $param['goods_id']])->update(['sort' => $param['sort']]);

        return $res;
    }

}