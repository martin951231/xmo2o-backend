<?php

namespace app\building\controller\api;

use app\building\model\ShoppingMall;
use app\building\model\ShoppingMallFloor;
use app\building\service\ParkService;
use app\building\service\ShoppingMallAdverService;
use app\building\service\ShoppingMallService;
use app\mall\controller\api\ApiBaseController;
use net\Http;
use pinyin\Pinyin;

class HomeController extends ApiBaseController
{
    public function index()
    {
        $uid = $this->request->param('uid');
        //根据经纬度获取距离最近的商场
        $lng = $this->request->param('lng', '', 'trim');
        $lat = $this->request->param('lat', '', 'trim');
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $nearMall = (new ShoppingMallService())->getNearestMall($lng, $lat, $uid, $mallId);
        $rs = [];
        $rs['current_mall'] = [
            'mall_id' => $nearMall['id'],
            'name' => $nearMall['name'],
            'footer_title_color' => $nearMall['footer_title_color'],
            'footer_focus_title_color' => $nearMall['footer_focus_title_color'],
            'banner_type' => $nearMall['banner_type'],
        ];

        $mallAdverService = new ShoppingMallAdverService();

        //获取顶部轮播图
        if ($rs['current_mall']['banner_type'] == 'full_banner') {
            $banner = $mallAdverService->getAdverByKey($nearMall['id'], 'full_banner', 5);
        } else {
            $banner = $mallAdverService->getAdverByKey($nearMall['id'], 'banner', 5);
        }
        if ($banner) {
            $size = $banner[0]['pic'] ? getimagesize($banner[0]['pic']) : [750, 1476];
            if ($size) {
                $banner[0]['width'] = $size[0];
                $banner[0]['height'] = $size[1];
            }
        }

        $rs['banner'] = $banner;

        //获取金刚区导航
        $nav = $mallAdverService->getAdverByKey($nearMall['id'], 'navigate', 10);
        $rs['navigate'] = $nav;

        //获取底部导航
        $footerNav = $mallAdverService->getAdverByKey($nearMall['id'], 'footer_navigate', 10);
        $rs['footer_navigate'] = $footerNav;

        //判断小程序环境和APP环境，上面的url需要替换成小程序或APP的url
        if ($this->request->agent == 'wechat_mini') {
            $rs['banner'] = $mallAdverService->replaceUrlToWxappPage($rs['banner']);
            $rs['navigate'] = $mallAdverService->replaceUrlToWxappPage($rs['navigate']);
            $rs['footer_navigate'] = $mallAdverService->replaceUrlToWxappPage($rs['footer_navigate']);
        } elseif ($this->request->agent == 'iosapp' || $this->request->agent == 'androidapp') {
            $rs['banner'] = $mallAdverService->replaceUrlToAppPage($rs['banner'], $this->request->agent == 'iosapp' ? 1 : 2);
            $rs['navigate'] = $mallAdverService->replaceUrlToAppPage($rs['navigate'], $this->request->agent == 'iosapp' ? 1 : 2);
            $rs['footer_navigate'] = $mallAdverService->replaceUrlToAppPage($rs['footer_navigate'], $this->request->agent == 'iosapp' ? 1 : 2);
        }

        //分享参数
        $shareConfigData = (new \app\common\model\db\ConfigData())
            ->where([['name', 'in', 'index_share_title,index_share_desc,index_share_wxapp_img,index_share_wx_img']])
            ->column('value', 'name');

        $rs['share_data'] = [
            'h5_img' => !empty($shareConfigData['index_share_wx_img']) ? replace_file_domain($shareConfigData['index_share_wx_img']) : replace_file_domain(cfg('wechat_share_img')),
            'h5_title' => !empty($shareConfigData['index_share_title']) ? $shareConfigData['index_share_title'] : cfg('site_name'),
            'h5_content' => !empty($shareConfigData['index_share_desc']) ? $shareConfigData['index_share_desc'] : '',
            'wxapp_title' => !empty($shareConfigData['index_share_title']) ? $shareConfigData['index_share_title'] : cfg('site_name'),
            'wxapp_img' => !empty($shareConfigData['index_share_wxapp_img']) ? replace_file_domain($shareConfigData['index_share_wxapp_img']) : replace_file_domain(cfg('wechat_share_img')),
        ];
        return api_output(0, $rs, 'success');
    }


    //猜你喜欢
    public function guessYouLike()
    {
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 10, 'intval');
        $mallId = $this->request->param('mall_id', 0, 'intval');

        $rs['items'] = (new ShoppingMallService())->getGuessYouLike($mallId, $page, $pageSize);
        $mallAdverService = new ShoppingMallAdverService();
        //获取广告配置
        if ($page == 1) {
            $topAdver = $mallAdverService->getAdverByKey($mallId, 'adver', 10, ['recommend_type' => 1]);
            $rs['top_adver'] = $topAdver;
        } else {
            $rs['top_adver']  = [];
        }

        $insertAdver  = $mallAdverService->getAdverByKey($mallId, 'adver', [($page - 1) * 1, 1], ['recommend_type' => 2]);
        if ($insertAdver) {
            $insertAdver[0]['type'] = 'insert_adver';
            // 假设要在第 2 个位置插入广告（索引为 1）
            $insertIndex = rand(0, count($rs['items']));
            array_splice($rs['items'], $insertIndex, 0, [$insertAdver[0]]);
        }
        return api_output(0, $rs, 'success');
    }

    //商场列表
    public function malls()
    {
        //根据经纬度获取距离最近的商场
        $lng = $this->request->param('lng', '', 'trim');
        $lat = $this->request->param('lat', '', 'trim');
        $malls = (new ShoppingMallService())->getAllMalls();
        $nearby = (new ShoppingMallService())->getNearByMalls($lng, $lat);
        return api_output(0, ['nearby' => $nearby, 'malls' => $malls], 'success');
    }

    //楼层导航
    public function floorGuide()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $catId = $this->request->param('cat_id', 0, 'intval');
        $building = $this->request->param('building', '', 'trim');
        $rs = (new ShoppingMallService())->floorGuide($mallId, $catId, $building);
        return api_output(0, $rs, 'success');
    }

    //搜索店铺
    public function searchStore()
    {
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $rs = (new ShoppingMallService())->searchStore($mallId, $keyword);
        return api_output(0, $rs, 'success');
    }

    //个人中心
    public function personalCenter()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        try {
            $rs = (new ShoppingMallService())->personalCenter($this->request->log_uid);
            return api_output(0, $rs, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //楼层2D图
    public function floorInfo()
    {
        $floorId = $this->request->param('floor_id', '', 'trim');
        if (empty($floorId)) {
            return api_output_error(1003, '楼层ID不能为空');
        }

        $floor = (new ShoppingMallFloor())->alias('f')
            ->field('f.id,f.name as layer_name,f.floor_number as layer_number,f.bg_img,f.coor_list as coorList,m.is_self')
            ->join('shopping_mall m', 'f.mall_id = m.id')
            ->where(['f.id' => $floorId])->findOrEmpty()->toArray();
        if (!empty($floor['is_self'])) {
            $floor['two_dimensional'] = [
                'bg_img' => $floor['bg_img'],
                'coorList' => $floor['coorList'],
            ];
            unset($floor['bg_img']);
            unset($floor['coorList']);
            $res['layerInfo'] = $floor;
            return api_output(0, $res, 'success');
        } else {
            $url = sprintf('/village/get/layer/info?floor_id=%s&appkey=%s&org_id=%s', $floorId, cfg('building_appkey'), cfg('building_org_id'));
            $res = building_request_get($url);
            $res = json_decode($res, true);
            if ($res['code'] == 1000) {
                return api_output(0, $res['data'], 'success');
            } else {
                return api_output_error(1003, $res['msg']);
            }
        }
    }

    //根据房间号获取店铺
    public function getStoreByRoom()
    {
        $floorId = $this->request->param('floor_id', '', 'trim');
        $room = $this->request->param('room', '', 'trim');
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $store_id = $this->request->param('store_id', '', 'trim,intval');
        $rs = (new ShoppingMallService())->getStore($floorId, $room, $mallId, $store_id);
        return api_output(0, $rs, 'success');
    }

    //获取拍照积分规则
    public function getTicketPointRules()
    {
        try {
            $where = [
                ['name', 'IN', ['open_ticket_point', 'ticket_point_type', 'ticket_point_score_number', 'ticket_point_rule']]
            ];
            $result = (new \app\common\model\db\ConfigData())
                ->where($where)
                ->column('value', 'name');
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function submitTicketPoint()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $openTicketPoint = (new \app\common\model\db\ConfigData())->where('name', 'open_ticket_point')->value('value');
        if ($openTicketPoint != 1) {
            return api_output_error(1003, '平台已关闭上传小票获得积分，如有疑问请联系平台客服');
        }
        $param['uid'] = $this->request->log_uid;
        $param['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $param['business'] = $this->request->param('business', 'store', 'trim');
        $param['real_orderid'] = $this->request->param('real_orderid', '', 'trim');
        $param['money'] = $this->request->param('money', 0, 'floatval');
        $param['ticket_pic'] = $this->request->param('ticket_pic', '', 'trim');
        $param['pay_pic'] = $this->request->param('pay_pic', '', 'trim');
        try {
            $result = (new ShoppingMallService())->submitTicketPoint($param);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //获取小票积分提交记录
    public function ticketPointApplyList()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 10, 'intval');
        $uid = $this->request->log_uid;
        $status = $this->request->param('status', 0, 'intval');
        $result = (new ShoppingMallService())->ticketPointApplyList($uid, $status, $page, $pageSize);
        return api_output(0, $result, 'success');
    }

    //根据商场获取wifi列表
    public function getWifiList()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $result = (new ShoppingMallService())->getWifiList($mallId);
        return api_output(0, $result, 'success');
    }

    //会员权益
    public function levelBenefits()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $level = $this->request->param('level', 0, 'intval');
        if (empty($level) || empty($mallId)) {
            return api_output(0, [], 'success');
        }

        $result = (new ParkService())->getLevelBenefits($level, $mallId);
        return api_output(0, $result, 'success');
    }
}
