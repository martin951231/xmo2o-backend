<?php


namespace app\common\model\service;

use app\common\model\db\CombineCouponList;
use think\Exception;
use think\facade\Db;

/**
 * 领券中心品牌精选
 * @author: 张涛
 * @date: 2020/11/24
 */
class CombineCouponListService
{
    public $combineCouponListMod = null;

    public function __construct()
    {
        $this->combineCouponListMod = new CombineCouponList();
    }

    /**
     * 获取品牌精选优惠券
     * @author: 张涛
     * @date: 2020/11/24
     */
    public function getBrandSelectCoupon($param)
    {
        $sysCouponFields = (new \app\common\model\db\SystemCoupon())->getTableFields();
        $merCouponFields = (new \app\common\model\db\CardNewCoupon())->getTableFields();
        $sysCatNames = (new \app\common\model\db\SystemCoupon())->getCatName();
        $merCatNames = (new \app\common\model\db\CardNewCoupon())->getCatName();
        $fields = ['b.*'];

        $whereOr = [];
        if ($param['keyword']) {
            $whereOr[] = ['sc.name', 'like', '%' . $param['keyword'] . '%'];
            $whereOr[] = ['mc.name', 'like', '%' . $param['keyword'] . '%'];
        }
        $total = $this->combineCouponListMod->alias('b')
            ->leftJoin('system_coupon sc', 'sc.coupon_id = b.coupon_id AND coupon_type="system"')
            ->leftJoin('card_new_coupon mc', 'mc.coupon_id = b.coupon_id AND coupon_type="merchant"')
            ->whereOr($whereOr)->count();
        $sysCouponFields = array_map(function ($r) {
            return 'sc.' . $r . ' AS s_' . $r;
        }, $sysCouponFields);
        $merCouponFields = array_map(function ($r) {
            return 'mc.' . $r . ' AS m_' . $r;
        }, $merCouponFields);
        $fields = array_merge($fields, $sysCouponFields, $merCouponFields);

        if ($total > 0) {
            $lists = $this->combineCouponListMod->alias('b')
                ->leftJoin('system_coupon sc', 'sc.coupon_id = b.coupon_id AND coupon_type="system"')
                ->leftJoin('card_new_coupon mc', 'mc.coupon_id = b.coupon_id AND coupon_type="merchant"')
                ->whereOr($whereOr)
                ->field($fields)
                ->page($param['page'], $param['pageSize'])
                ->order('b.id', 'asc')
                ->select()->toArray();
        } else {
            $lists = [];
        }
        $retval = [];
        if ($lists) {
            foreach ($lists as $l) {
                if ($l['coupon_type'] == 'system') {
                    //平台券
                    $retval[] = [
                        'id' => $l['id'],
                        'coupon_id' => $l['coupon_id'],
                        'coupon_type' => $l['coupon_type'],
                        'coupon_type_name' => '平台券',
                        'name' => $l['s_name'],
                        'cate_name' => $sysCatNames[$l['s_cate_name']] ?? '--',
                        'discount' => $l['s_is_discount'] == 1 ? $l['s_discount_value'] . '折' : $l['s_discount'],
                        'order_money' => $l['s_order_money'],
                        'expire_date' => date('Y-m-d', $l['s_start_time']) . '至' . date('Y-m-d', $l['s_end_time']),
                        'stock_str' => sprintf('剩余%d 销售%d', ($l['s_num'] - $l['s_had_pull']), $l['s_had_pull'])
                    ];
                } else if ($l['coupon_type'] == 'merchant') {
                    //商家券
                    $retval[] = [
                        'id' => $l['id'],
                        'coupon_id' => $l['coupon_id'],
                        'coupon_type' => $l['coupon_type'],
                        'coupon_type_name' => '商家券',
                        'name' => $l['m_name'],
                        'cate_name' => $merCatNames[$l['m_cate_name']] ?? '--',
                        'discount' => $l['m_is_discount'] == 1 ? $l['m_discount_value'] . '折' : $l['m_discount'],
                        'order_money' => $l['m_order_money'],
                        'expire_date' => date('Y-m-d', $l['m_start_time']) . '至' . date('Y-m-d', $l['m_end_time']),
                        'stock_str' => sprintf('剩余%d 销售%d', ($l['m_num'] - $l['m_had_pull']), $l['m_had_pull'])
                    ];
                }
            }
        }
        return ['list' => $retval, 'total' => $total];
    }


    /**
     * 获取可筛选优惠券
     * @author: 张涛
     * @date: 2020/11/24
     */
    public function chooseBrandSelectCoupon($param)
    {
        $where = [['end_time', '>', time()]];
        if ($param['coupon_type'] == 'system') {
            //系统优惠券
            $mod = new \app\common\model\db\SystemCoupon();
            $catNames = $mod->getCatName();
            $where[] = ['is_privileged', '=', 0];
            $where[] = ['is_hide', '=', 0];
            $where[] = ['allow_sign', '=', 0];
            $where[] = ['allow_gift', '=', 0];
            $where[] = ['allow_im_get', '=', 0];
            $where[] = ['is_vip_level', '=', 0];
            $where[] = ['is_partition', '=', 0];
            $where[] = ['status', '=', 1];
            $couponType = 'system';
        } else {
            //商家优惠券
            $mod = new \app\common\model\db\CardNewCoupon();
            $catNames = $mod->getCatName();
            $where[] = ['allow_im_get', '=', 0];
            $where[] = ['is_receive_full_reduction', '=', 0];
            $where[] = ['is_live', '=', 0];
            $where[] = ['status', '=', 1];
            $where[] = ['only_assign', '=', 0];
            $couponType = 'merchant';
        }
        if ($param['keyword']) {
            $where[] = ['name', 'like', '%' . $param['keyword'] . '%'];
        }

        $total = $mod->where($where)->count();
        if ($total > 0) {
            $lists = $mod->where($where)->page($param['page'], $param['pageSize'])->order('coupon_id', 'desc')->select()->toArray();
        } else {
            $lists = [];
        }
        $retval = [];
        if ($lists) {
            foreach ($lists as $l) {
                $retval[] = [
                    'id' => $l['coupon_id'],
                    'coupon_id' => $l['coupon_id'],
                    'coupon_type' => $couponType,
                    'name' => $l['name'],
                    'is_choose' => $this->isBrandCoupon($l['coupon_id'], $couponType),
                    'cate_name' => $catNames[$l['cate_name']] ?? '--',
                    'discount' => $l['is_discount'] == 1 ? $l['discount_value'] . '折' : $l['discount'],
                    'order_money' => $l['order_money'],
                    'expire_date' => date('Y-m-d', $l['start_time']) . '至' . date('Y-m-d', $l['end_time']),
                    'stock_str' => sprintf('剩余%d 销售%d', ($l['num'] - $l['had_pull']), $l['had_pull'])
                ];
            }
        }
        return ['list' => $retval, 'total' => $total];
    }

    /**
     * 是否品牌精选
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function isBrandCoupon($couponId, $couponType)
    {
        $record = $this->combineCouponListMod->where(['coupon_id' => $couponId, 'coupon_type' => $couponType])->findOrEmpty()->toArray();
        return $record ? true : false;
    }

    /**
     * 添加品牌精选优惠券
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function addBrandCoupon($param)
    {
        $couponId = $param['coupon_id'] ?? 0;
        $couponType = $param['coupon_type'] ?? '';
        if (empty($couponId)) {
            throw new Exception('请选择优惠券');
        }
        if (!in_array($couponType, ['system', 'merchant'])) {
            throw new Exception('优惠券类型参数有误');
        }

        $record = $this->combineCouponListMod->where(['coupon_id' => $couponId, 'coupon_type' => $couponType])->findOrEmpty()->toArray();
        if (!$record) {
            $this->combineCouponListMod->insert(['coupon_id' => $couponId, 'coupon_type' => $couponType, 'create_time' => time()]);
        }
        return true;
    }


    /**
     * 根据id删除品牌精选优惠券
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function delBrandCouponByIds($ids)
    {
        $ids = is_array($ids) ? $ids : [];
        if (empty($ids)) {
            throw new Exception('请选择删除记录');
        }
        $this->combineCouponListMod->whereIn('id', $ids)->delete();
        return true;
    }

    /**
     * 根据优惠券id删除
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function delBrandCouponByCouponId($param)
    {
        $couponId = $param['coupon_id'] ?? 0;
        $couponType = $param['coupon_type'] ?? '';
        if (empty($couponId)) {
            throw new Exception('请选择优惠券');
        }
        if (!in_array($couponType, ['system', 'merchant'])) {
            throw new Exception('优惠券类型参数有误');
        }
        $this->combineCouponListMod->where(['coupon_id' => $couponId, 'coupon_type' => $couponType])->delete();
        return true;
    }
}
