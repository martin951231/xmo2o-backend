<?php

namespace app\api\model\service\building;

use app\building\model\ShoppingMallPark;
use app\building\model\ShoppingMallParkOrder;
use app\building\model\ShoppingMallParkOrderRefund;
use app\pay\model\service\PayService;
use think\facade\Db;

//停车管理
class ParkService
{
    protected $parkMod;

    protected $orderMod;

    protected $refundMod;

    public function __construct()
    {
        $this->parkMod = new ShoppingMallPark();
        $this->orderMod = new ShoppingMallParkOrder();
        $this->refundMod = new ShoppingMallParkOrderRefund();
    }

    //获取商场列表
    public function getLists()
    {
        return [];
    }


    //添加停车场
    public function savePark($params)
    {
        $rules = [
            'mall_id' => 'require|number|gt:0',
            'park_type' => 'require|in:1',
            'merchant_id' => 'require|number',
            'api_key' => 'require',
            'park_name' => 'requireIf:park_type,1',
            'park_id' => 'requireIf:park_type,1',
        ];
        if (validate($rules)->check($params)) {
            $mall = Db::name('shopping_mall')->where('id', $params['mall_id'])->find();
            if (empty($mall)) {
                throw new \Exception('请选择商场');
            }

            $id = $params['id'] ?? 0;
            if ($id > 0) {
                //修改
                unset($params['id']);
                //判断park_id是否已经添加过
                $isFind = $this->parkMod
                    ->where('mall_id', $params['mall_id'])
                    ->where('park_type', $params['park_type'])
                    ->where('merchant_id', $params['merchant_id'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($isFind) {
                    throw new \Exception('【' . $params['park_name'] . '】停车场已存在,请勿重复添加');
                }
                $this->parkMod->where('id', $id)->update($params);
            } else {
                //新增
                //判断park_id是否已经添加过
                $isFind = $this->parkMod
                    ->where('mall_id', $params['mall_id'])
                    ->where('park_type', $params['park_type'])
                    ->where('merchant_id', $params['merchant_id'])
                    ->find();
                if ($isFind) {
                    throw new \Exception('【' . $params['park_name'] . '】停车场已存在,请勿重复添加');
                }
                $id = $this->parkMod->insertGetId($params);
            }
            return $id;
        } else {
            throw new \Exception('参数错误');
        }
    }

    //获取楼宇停车场信息
    public function getInfo($merchantId, $apiKey)
    {
        if (empty($merchantId) || empty($apiKey)) {
            throw new \Exception('参数错误');
        }

        $path = '/work_park/api/park/info';
        $businessData = [
            'merchant_id' => $merchantId,
            'api_key' => $apiKey
        ];
        $data = (new BuildingApiService())->invoke($path, $businessData);
        $rs = [
            'org_id' => $data['info']['org_id'] ?? 0,
            'park_id' => $data['info']['id'] ?? 0,
            'park_name' => $data['info']['park_name'] ?? '',
            'recycle_url' => cfg('site_url') . '/v20/public/index.php/api/coupon/recycle?merchant_id=' . $merchantId
        ];
        return $rs;
    }

    //删除停车场
    public function deleteParkById($id)
    {
        if (empty($id)) {
            throw new \Exception('参数错误');
        }
        $park = (new ShoppingMallPark())->where('id', $id)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        } else {
            return $park->delete();
        }
    }

    //获取停车场关联的楼宇优惠券
    public function coupon($id)
    {
        if (empty($id)) {
            throw new \Exception('参数错误');
        }
        $park = $this->parkMod->where('id', $id)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        }

        $path = '/work_park/api/merchant/coupon/list';
        $businessData = [
            'merchant_id' => $park->merchant_id,
            'api_key' => $park->api_key,
            'park_id' => $park->park_id,
        ];
        $data = (new BuildingApiService())->invoke($path, $businessData);
        return $data;
    }


    //积分兑换停车券设置
    public function couponSetting($id, $scoreCouponSetting)
    {
        if (empty($id)) {
            throw new \Exception('参数错误');
        }
        $park = $this->parkMod->where('id', $id)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        }
        $park->score_coupon_setting = $scoreCouponSetting ? json_encode($scoreCouponSetting, JSON_UNESCAPED_UNICODE) : '[]';
        $park->save();
        return true;
    }

    //获取车场列表
    public function lists($params)
    {
        $mallId = $params['mall_id'] ?? 0;
        $parkType = $params['park_type'] ?? 0;
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $where = [];
        $fields = 'p.*,m.name as mall_name';
        if ($mallId > 0) {
            $where[] = ['p.mall_id', '=', $mallId];
        }
        if ($parkType > 0) {
            $where[] = ['p.park_type', '=', $parkType];
        }

        $result = $this->parkMod->alias('p')
            ->join('shopping_mall m', 'm.id = p.mall_id')
            ->where($where)
            ->field($fields)
            ->paginate(['list_rows' => $pageSize, 'page' => $page])
            ->each(function ($item) {
                $item['score_coupon_setting'] = $item['score_coupon_setting'] ? json_decode($item['score_coupon_setting'], true) : [];
                $item['recycle_url'] = cfg('site_url') . '/v20/public/index.php/api/coupon/recycle?merchant_id=' . $item['merchant_id'];
                return $item;
            });
        return $result;
    }

    //停车订单列表
    public function orderLists($params)
    {
        $mallId = $params['mall_id'] ?? 0;
        $parkId = $params['park_id'] ?? 0;
        $carNumber = $params['car_number'] ?? '';
        $orderNo = $params['order_no'] ?? '';
        $status = $params['status'] ?? -1;
        $payStartTime = $params['pay_start_time'] ?? '';
        $payEndTime = $params['pay_end_time'] ?? '';
        $paidExtra = $params['paid_extra'] ?? '';
        $payType = $params['pay_type'] ?? '';
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $where = [];
        if ($mallId > 0) {
            $where[] = ['o.mall_id', '=', $mallId];
        }
        if ($parkId > 0) {
            $where[] = ['o.park_id', '=', $parkId];
        }

        if ($carNumber) {
            $where[] = ['o.car_number', '=', $carNumber];
        }
        if ($orderNo) {
            $where[] = ['o.order_no', '=', $orderNo];
        }
        if ($status >= 0) {
            $where[] = ['o.status', '=', $status];
        }
        if ($payStartTime) {
            $where[] = ['o.pay_time', '>=', strtotime($payStartTime)];
        }
        if ($payEndTime) {
            $where[] = ['o.pay_time', '<=', strtotime($payEndTime)];
        }
        if ($paidExtra) {
            $where[] = ['o.paid_extra', '=', $paidExtra];
        }
        if ($payType) {
            switch ($payType) {
                case 'balance':
                    $where[] = ['o.money_system_balance', '>', 0];
                    break;
                case 'score':
                    $where[] = ['o.money_score_reduce', '>', 0];
                    break;
                default:
                    $where[] = ['o.pay_type', '=', $payType];
                    break;
            }
        }

        $payService = new PayService();
        $result = (new ShoppingMallParkOrder())->alias('o')
            ->join('shopping_mall m', 'm.id = o.mall_id', 'left')
            ->field('o.*,m.name as mall_name')
            ->append(['status_text'])
            ->where($where)
            ->order('o.order_id DESC')
            ->paginate(['list_rows' => $pageSize, 'page' => $page])
            ->each(function ($item) use ($payService) {
                $item['pay_time'] = $item['pay_time'] > 0 ? date('Y-m-d H:i:s', $item['pay_time']) : '-';
                $item['pay_type_text'] = $item['pay_time'] > 0 ? $payService->getPayTypeText(
                    $item['pay_type'],
                    [
                        'money_system_balance' => $item['money_system_balance'],
                        'money_score' => $item['money_score_reduce'],
                    ]
                ) : '-';
                $item['refund_records'] = (new ShoppingMallParkOrderRefund())->where('order_id', $item['order_id'])->order('refund_id ASC')->select()->each(function ($item) {
                    $item['refund_time'] = $item['refund_time'] > 0 ? date('Y-m-d H:i:s', $item['refund_time']) : '-';
                    return $item;
                })->toArray();
                return $item;
            })->toArray();

        $result['total_fee_real'] = $this->orderMod->alias('o')->where($where)->where('pay_time', '>', 0)->sum('fee_real');
        $result['total_fee_refund'] = $this->orderMod->alias('o')->where($where)->where('pay_time', '>', 0)->sum('refund_money');
        $result['total_fee_real_without_refund'] = getFormatNumber($result['total_fee_real'] - $result['total_fee_refund']);
        return $result;
    }
}
