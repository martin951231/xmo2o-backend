<?php

namespace app\douyin\model\service;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\MerchantStore;
use app\common\model\service\coupon\MerchantCouponService;
use app\douyin\model\db\DouyinActivity;
use app\douyin\model\db\DouyinActivityShareid;
use app\douyin\model\db\DouyinActivitySourceMaterial;
use app\douyin\model\db\DouyinActivityUserRecord;
use app\merchant\model\service\MerchantStoreOpenTimeService;
use app\merchant\model\service\MerchantStoreService;
use douyin\Douyin;
use Exception;
use think\facade\Db;
use think\Model;

class DouyinActivityService
{

    protected $DouyinActivity = null;

    protected $douyinActivityMaterialMod = null;

    protected $douyinActivityShareidMod = null;

    protected $douyinActivityUserRecordMod = null;

    public function __construct()
    {
        $this->DouyinActivity = new DouyinActivity();
        $this->douyinActivityMaterialMod = new DouyinActivitySourceMaterial();
        $this->douyinActivityShareidMod = new DouyinActivityShareid();
        $this->douyinActivityUserRecordMod = new DouyinActivityUserRecord();
    }

    /**
     * 用户端-活动数据
     */
    public function activityIndex($actId, $uid = 0)
    {
        $rs = [];
        if (empty($actId)) {
            throw new  \think\Exception('参数有误');
        }

        //获取活动基础信息
        $activity = $this->DouyinActivity->where('id', $actId)->where('status', 1)->where('is_del', 0)->findOrEmpty()->toArray();
        if (empty($activity)) {
            throw new  \think\Exception('活动不存在');
        }
        $rs['activity'] = [
            'id' => $actId,
            'name'=>$activity['name'],
            'content' => replace_file_domain_content($activity['content'])
        ];

        //获取店铺基础信息
        $storeId = $activity['store_id'];
        $storeInfo = (new MerchantStoreService())->getStoreInfo($activity['store_id']);
        if (empty($storeInfo)) {
            throw new  \think\Exception('店铺不存在');
        }

        $showAddress = '';
        $areaService = new \app\common\model\service\AreaService();
        $province = $areaService->getOne(['area_id' => $storeInfo['province_id']]);
        $city = $areaService->getOne(['area_id' => $storeInfo['city_id']]);
        $area = $areaService->getOne(['area_id' => $storeInfo['area_id']]);
        $province && $showAddress .= $province['area_name'];
        $city && $showAddress .= $city['area_name'];
        $area && $showAddress .= $area['area_name'];
        $showAddress .= $storeInfo['adress'];

        $businessTime = (new MerchantStoreOpenTimeService())->getShowTimeByStore($storeId);
        $timeArr = [];
        $todayOpenTime = '';
        if (count($businessTime) == 1) {
            $todayOpenTime = ($storeInfo['is_business_open'] == 1) ? $businessTime[0]['time_show'] : '';
        } else {
            foreach ($businessTime as $t) {
                $timeArr[] = $t['week_show'] . ' ' . $t['time_show'];
                if ($t['current']) {
                    $todayOpenTime = $t['time_show'];
                }
            }
        }

        $phones = array_values(array_filter(explode(' ', $storeInfo['phone'])));
        $rs['store_info'] = [
            'store_id' => $storeId,
            'name' => $storeInfo['name'],
            'logo' => $storeInfo['logo'] ? replace_file_domain($storeInfo['logo']) : '',
            'is_business' => $storeInfo['is_business_open'],
            'open_time' => $todayOpenTime,
            'address' => $showAddress,
            'phone' => $phones,
            'lng' => $storeInfo['long'],
            'lat' => $storeInfo['lat'],
            'poi_id' => $activity['poi_id'],
            'store_douyin_id' => $activity['store_douyin_id'],
        ];

        //获取优惠券基础信息
        $current_time = time();
        $where = [
            ['end_time', '>', $current_time],
            ['start_time', '<', $current_time],
            ['status', 'in', [1, 3]],
            ['mer_id', '=', $activity['mer_id']],
            ['only_assign', '=', 0],
            ['coupon_id', 'IN', $activity['coupon_ids']],
        ];
        $coupons = (new MerchantCouponService())->getCouponInfoByCondition($where);
        $coupons = array_map(function ($r) {
            return [
                'coupon_id' => $r['coupon_id'],
                'name' => $r['name'],
                'img' => replace_file_domain($r['img']),
                'limit_date' => $r['limit_date'],
                'discount_title' => $r['discount_title'],
                'discount_des' => $r['discount_des'],
            ];
        }, $coupons);
        $rs['coupons'] = $coupons;

        //提示语
        $userRecord = $this->douyinActivityUserRecordMod->getOne(['activity_id' => $actId, 'uid' => $uid]);
        $rs['tips'] = '';
        $rs['left_time'] = 0;
        if ($userRecord) {
            $rs['left_time'] = $userRecord->total_times > $userRecord->use_times ? $userRecord->total_times - $userRecord->use_times : 0;
            $rs['left_time'] > 0 && $rs['tips'] = sprintf("您有剩余领券次数：%d次",$rs['left_time']);
        }
        return $rs;
    }

    /**
     * 用户端-领取活动优惠券
     */
    public function getActivityCoupon($param)
    {
        if (empty($param['activity_id']) || empty($param['coupon_id'])) {
            throw new  \think\Exception('参数有误');
        }
        $activity = $this->DouyinActivity->where('id', $param['activity_id'])->where('status', 1)->where('is_del', 0)->findOrEmpty()->toArray();
        if (empty($activity)) {
            throw new  \think\Exception('活动不存在');
        }
        $couponIds = explode(',', $activity['coupon_ids']);
        if (!in_array($param['coupon_id'], $couponIds)) {
            throw new  \think\Exception('活动券不存');
        }
        //检查用户剩余次数
        $userRecord = $this->douyinActivityUserRecordMod->getOne(['activity_id' => $param['activity_id'], 'uid' => $param['uid']]);
        if(empty($userRecord) || $userRecord->total_times - $userRecord->use_times <=0 ){
            throw new  \think\Exception('请先点击下方拍摄视频，即送领取优惠券资格');
        }
        $couponRs = (new MerchantCouponService())->receiveCoupon($param['uid'], $param['coupon_id'], 'douyin');

        $receiveLog = [
            'uid' => $param['uid'],
            'coupon_id' => $param['coupon_id'],
            'activity_id' => $param['activity_id'],
            'create_time' => time(),
            'hadpull_id'=>$couponRs['add_id']
        ];
        \think\facade\Db::name('douyin_activity_coupon_receive_record')->insert($receiveLog);
        $this->douyinActivityUserRecordMod->setInc(['activity_id' => $param['activity_id'], 'uid' => $param['uid']],'use_times',1);
        return true;
    }

    /**
     * 活动列表
     */
    public function getActivityList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['is_del', '=', 0]
        ];
        if (!empty($param['name'])) {
            $where[] = ['name', 'like', '%' . $param['keyword'] . '%'];
        }
        if (isset($param['mer_id'])) {
            $where[] = ['mer_id', '=', intval($param['mer_id'])];
        }
        $result = $this->DouyinActivity->getList($where, $limit);
        $publishMod = \think\facade\Db::name('douyin_publish_record');
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : '无';
                $result['data'][$k]['url'] = get_base_url('pages/activity/probeShop/index?id=' . $v['id']);
                $result['data'][$k]['ewm'] = $this->getQrCode($result['data'][$k]['url']);
                $result['data'][$k]['share_count'] = $publishMod->where('activity_id',$v['id'])->count();
            }
        }
        return $result;
    }

    /**
     * 添加/编辑活动
     */
    public function addOrEditActivity($param)
    {
        if (empty($param['name'])) {
            throw new Exception('活动名称不能为空');
        }

        if (is_array($param['coupon_ids'])) {
            $param['coupon_ids'] = implode(',', $param['coupon_ids']);
        }
        if (is_array($param['material_ids'])) {
            $param['material_ids'] = implode(',', $param['material_ids']);
        }
        if (empty($param['id'])) {
            unset($param['id']);
            $param['create_time'] = time();
            $param['id'] = $this->DouyinActivity->add($param);
        } else {
            $this->DouyinActivity->updateThis(['id' => $param['id']], $param);
        }
        return true;
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        require_once '../extend/phpqrcode/phpqrcode.php';
        $qrcode = new \QRcode();
        $file_name = md5($code) . '.png';
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/douyin/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/douyin/' . $file_name;
        if (!file_exists($filename_url)) {
            $qrcode->png($code, $filename_url, $errorLevel, $size);
        }
        $QR = 'runtime/qrcode/douyin/' . $file_name;      //已经生成的原始二维码图片文件
        return cfg('site_url') . '/' . $QR;
    }

    /**
     * 获取分享视频
     * @param $activityId
     * @param $uid
     * @date: 2022/12/02
     */
    public function getShareVideo($activityId, $uid)
    {
        $activity = $this->DouyinActivity->where('id', $activityId)->where('status', 1)->where('is_del', 0)->findOrEmpty()->toArray();
        if (empty($activity)) {
            throw new  \think\Exception('活动不存在');
        }
        $videoIds = explode(',', $activity['material_ids']);
        if (empty($videoIds)) {
            throw new  \think\Exception('活动配置有误');
        }
        $videos = $this->douyinActivityMaterialMod->whereIn('id', $videoIds)->where('is_del', 0)->select()->toArray();
        if (empty($videos)) {
            throw new  \think\Exception('活动配置有误');
        }
        shuffle($videos);
        $shareVideo = end($videos);

        $shareId = (new Douyin())->getShareId();
        $log = [
            'uid' => $uid,
            'activity_id' => $activityId,
            'material_id' => $shareVideo['id'],
            'share_id' => $shareId['share_id'],
            'create_time' => time()
        ];
        if ($this->douyinActivityShareidMod->add($log)) {
            $shareParams = (new Douyin())->getShareParams();
            return [
                'video_data' => [
                    'poi_id' => $activity['poi_id'],
                    'share_desc' => $shareVideo['share_desc'],
                    'topics' => explode(';', $shareVideo['topic']),
                    'video_url' => replace_file_domain($shareVideo['material_url']),
                    'cover' => replace_file_domain($shareVideo['cover']),
                    'share_id' => $shareId['share_id']
                ],
                'share_params' => $shareParams
            ];
        } else {
            throw new  \think\Exception('获取视频失败');
        }
    }

    public function jsSdkConfig($url)
    {
        if (empty($url)) {
            throw new  \think\Exception('回调地址不能为空');
        }
        $nonceStr = create_random_str(16);
        $jsapiTicket = (new Douyin())->getJsTicket();
        $timestamp = time();
        $signature = md5(sprintf("jsapi_ticket=%s&nonce_str=%s&timestamp=%s&url=%s", $jsapiTicket, $nonceStr, $timestamp, $url));
        return [
            'client_key' => cfg('douyin_h5_client_key'),
            'signature' => $signature,
            'timestamp' => strval($timestamp),
            'nonce_str' => $nonceStr,
            'url' => $url
        ];
    }



}