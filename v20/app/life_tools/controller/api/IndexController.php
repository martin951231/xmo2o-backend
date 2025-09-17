<?php
/**
 * 体育健身公共接口控制器
 */

namespace app\life_tools\controller\api;

use app\common\model\service\plan\file\LifeToolsDistributionOrderToStatementService;
use app\common\model\service\plan\file\LifeToolsOrderAutoCancelService;
use app\life_tools\model\db\LifeToolsRecommendTools;
use app\life_tools\model\service\appoint\LifeToolsAppointService;
use app\life_tools\model\service\HomeDecorateService;
use app\life_tools\model\service\LifeToolsCategoryService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\LifeToolsSportsActivityService;

class IndexController extends ApiBaseController
{

    /**
     * 体育健身首页接口
     */
    public function index()
    {
        $Homeservice = new HomeDecorateService();
        $return = [];//返回的数据集合
        //顶部轮播图
        $return['rotation']    = $Homeservice->getAdverByCatKey('wap_life_tools_sports_index_top', 10);
        //导航栏导航列表
        $return['slider']      = $Homeservice->getAdverByCatKey('wap_life_tools_sports_slider', 20);
        //中间广告图
        $return['advert']      = $Homeservice->getAdverByCatKey('wap_life_tools_sports_ad', 3);
        //滚动公告
        $return['information'] = $Homeservice->getIndexRecommend(1);
        //推荐课程
        // $return['course']      = $Homeservice->getIndexRecommend(2);
        //热门活动
        // $return['competition'] = $Homeservice->getIndexRecommend(3);

        
        //热门推荐
        $return['hot_recommend'] = $Homeservice->getSportsHotRecommendByUser();


        return api_output(0, $return);
    }

    /**
     * 门票预约首页接口
     */
    public function ticketIndex()
    {
        $Homeservice = new HomeDecorateService();
        $return      = [];//返回的数据集合
        $return['rotation'] = $Homeservice->getAdverByCatKey('wap_life_tools_ticket_index_top', 10);//顶部轮播图
        $return['slider']   = $Homeservice->getAdverByCatKey('wap_life_tools_ticket_slider', 20);//导航栏导航列表
        if ($return['slider']) {
            $typeArr = [
                10 => 'scenic',
                11 => 'stadium',
                12 => 'course',
                13 => 'village'
            ];
            foreach ($return['slider'] as $k => $v) {
                if($v['name'] == '活动预约'){
                    $return['slider'][$k]['type'] = 'appoint';
                }else{
                    $return['slider'][$k]['type'] = $typeArr[$v['id']];
                }
            }
        }
        $data = (new LifeToolsRecommendTools())->getOne(['tools_id' => 0]);
        if ($data && $data->show == 1) {
            $arr = [[
               'id'   => 0,
               'name' => $data->name,
               'type' => 'all'
            ]];
            $return['slider'] = array_merge($arr, $return['slider']);
        }
        return api_output(0, $return);
    }

    /**
     * 门票预约首页景区列表
     */
    public function ticketTools()
    {
        $param['type']     = $this->request->param('type', '', 'trim');
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['keywords'] = $this->request->param('keywords', '', 'trim');
        $param['order']    = 'sort desc, distance ASC'; //排序
        try {
            if ($param['type'] == 'all') {
                $arr = (new HomeDecorateService())->getIndexRecommend(4, $param);
            } else {
                switch($param['type']){
                    case 'village' :
                        $arr = [];
                        break;
                    case 'appoint' :
                        $condition = [
                            'page' => $param['page'],
                            'pageSize' => $param['pageSize'],
                            'long' => $param['long'],
                            'lat' => $param['lat'],
                        ];
                        $arr = (new LifeToolsAppointService())->appointList($condition, 'ticketBook',$this->_uid);
                        break;
                    case 'sports':
                        $arr = (new LifeToolsService())->getToolsList($param, $param['type']);
                        break;
                    default:
                        $arr = (new LifeToolsService())->getToolsList($param, $param['type']);
                        break;
                }
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 体育健身课程/场馆/景区-详情
     */
    public function toolsDetail()
    {
        $tools_id = $this->request->param('tools_id', 0, 'intval');
        $invite_id = $this->request->param('invite_id', 0, 'intval');
        if (empty($tools_id)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsService())->getToolsDetail($tools_id, $this->_uid, $invite_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 体育健身课程/场馆详情页推荐列表
     */
    public function recommendList()
    {
        $type = $this->request->param('type', 1, 'intval');
        $type = $type == 2 ? 'stadium' : 'course';
        try {
            $arr = (new LifeToolsService())->getToolsList([], $type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区详情页推荐列表
     */
    public function scenicRecommendList()
    {
        $param['type']     = $this->request->param('type', '', 'trim');
        $param['tools_id'] = $this->request->param('tools_id', 1, 'intval');
        if (empty($param['type']) || empty($param['tools_id'])) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsService())->scenicRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 体育健身课程/场馆/活动/咨讯-分类列表
     */
    public function cateList()
    {
        $type = $this->request->param('type', 1, 'intval'); //1=体育课程/2=体育资讯/3=赛事活动/4=体育咨讯
        try {
            $arr = (new LifeToolsCategoryService())->getCateList($type, $this->_uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 服务订单详情
     */
    public function orderDetail()
    {
        $this->checkLogin();
        $param['order_id']      = $this->request->param('order_id', 0, 'intval');
        $param['group_tourist_search']      = $this->request->post('group_tourist_search', '', 'trim');
        $param['uid']      = $this->_uid;
        try {
            $arr = (new LifeToolsOrderService())->getUserDetail($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 课程/场馆-提交订单页数据
     */
    public function confirm()
    {
        $this->checkLogin();
        $param['ticket_id']      = $this->request->param('ticket_id', 0, 'intval');
        $param['select_id']      = $this->request->param('select_id', 0, 'intval');
        $param['sys_hadpull_id'] = $this->request->param('sys_hadpull_id', 0, 'intval'); //-1=不使用，0=默认选中第一个
        $param['mer_hadpull_id'] = $this->request->param('mer_hadpull_id', 0, 'intval'); //-1=不使用，0=默认选中第一个
        $param['member_id']      = $this->request->param('member_id', 0, 'intval');
        $param['num']            = $this->request->param('num', 1, 'intval');
        $param['activity_id']    = $this->request->param('activity_id', 0, 'intval');
        $param['people_num']     = $this->request->param('people_num', 0, 'intval');
        $param['group_type']     = $this->request->param('group_type', 1, 'intval');
        $param['sku_id']     = $this->request->param('sku_id',0, 'intval');
        $param['activity_type']     = $this->request->param('activity_type', '', 'trim');// 活动类型：group-团体票

        $param['sku_ids']     = $this->request->post('sku_ids', []);//场馆座位分布
        try {
            $arr = (new LifeToolsOrderService())->confirm($param, $this->_uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 课程/场馆-提交订单
     */
    public function saveOrder()
    {
        $this->checkLogin();
        $param['ticket_id']      = $this->request->param('ticket_id', 0, 'intval');
        $param['select_id']      = $this->request->param('select_id', 0, 'intval');
        $param['sys_hadpull_id'] = $this->request->param('sys_hadpull_id', 0, 'intval'); //-1=不使用，0=默认选中第一个
        $param['mer_hadpull_id'] = $this->request->param('mer_hadpull_id', 0, 'intval'); //-1=不使用，0=默认选中第一个
        $param['member_id']      = $this->request->param('member_id', 0, 'intval');
        $param['num']            = $this->request->param('num', 1, 'intval');
        $param['activity_id']    = $this->request->param('activity_id', 0, 'intval');
        $param['activity_title'] = $this->request->param('activity_title', '', 'trim');
        $param['people_num']     = $this->request->param('people_num', 0, 'intval');
        $param['group_type']     = $this->request->param('group_type', 1, 'intval');
        $param['is_public']      = $this->request->param('is_public', 1, 'intval');
        $pay_price               = $this->request->param('pay_price', '0', 'trim');
        $param['custom_form']               = $this->request->param('custom_form', '', 'trim');// 自定义表单
        $param['sku_id']     = $this->request->param('sku_id',0, 'intval');
        $param['activity_type']     = $this->request->param('activity_type', '', 'trim');// 活动类型：group-团体票
        $param['tour_guide_custom_form']               = $this->request->param('tour_guide_custom_form', '', 'trim');// 导游自定义信息
        
        $param['sku_ids']     = $this->request->post('sku_ids', []);//场馆座位分布
        try {
            $confirm = (new LifeToolsOrderService())->confirm($param, $this->_uid);
            if ($param['activity_type'] != 'group' && $confirm['base_info']['type'] != 'course' && (empty($confirm['base_info']['select_id']) || empty($confirm['base_info']['member_id'])) && $confirm['base_info']['scenic_ticket_type']) {
                return api_output_error(1003, '参数有误');
            }
            if ($pay_price != $confirm['base_info']['pay_price']) {
                throw new \think\Exception('支付价格有误');
            }
            if ($confirm['base_info']['limit_num'] < $param['num']) {
                throw new \think\Exception('库存不足');
            }
            if ($confirm['base_info']['type'] == 'course' && $confirm['base_info']['course_end_time'] <= time()) {
                throw new \think\Exception('此门票购买活动已结束');
            }
            $confirm['base_info']['activity_id']    = $param['activity_id'];
            $confirm['base_info']['activity_title'] = $param['activity_title'];
            $confirm['base_info']['people_num']     = $param['people_num'];
            $confirm['base_info']['group_type']     = $param['group_type'];
            $confirm['base_info']['is_public']      = $param['is_public'];
            $confirm['custom_form']     = $param['custom_form'];
            $confirm['tour_guide_custom_form']     = $param['tour_guide_custom_form'];
            $confirm['activity_type']     = $param['activity_type'];
            $res = (new LifeToolsOrderService())->saveOrder($confirm, $this->userInfo);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 体育健身课程/场馆-申请退款
     */
    public function supplyRefund()
    {
        $this->checkLogin();
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $param['reason']   = $this->request->param('reason', '', 'trim');
        $param['detail_ids'] = $this->request->post('detail_ids', []);
        try {
            (new LifeToolsOrderService())->supplyRefund($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 体育健身课程/场馆-撤销申请
     */
    public function revokeRefund()
    {
        $this->checkLogin();
        $order_id = $this->request->param('order_id', 0, 'intval');
        try {
            (new LifeToolsOrderService())->revokeRefund($order_id);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //限时优惠去提醒
    public function addLimitedNotice()
    {
        $data['ticket_id'] = $this->request->param("ticket_id", "", "intval");//商品id
        $data['uid'] = $this->_uid;
        $data['act_id'] = $this->request->param('act_id', '', 'intval');
        $data['start_time'] = $this->request->param('start_time', '', 'trim');
        if(empty($data['uid'])){
            return api_output_error(1002, '当前接口需要登录');
        }
        try {
            $return['status'] = (new LifeToolsService())->addLimitedNotice($data);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }
    /**
     * 课程/场馆-提交评价
     */
    public function reply()
    {
        $this->checkLogin();
        $params = [];
        $params['order_id'] = $this->request->post('order_id', 0, 'trim,intval');
        $params['ticket_id'] = $this->request->post('ticket_id', 0, 'trim,intval');
        $params['content'] = $this->request->post('content', '', 'trim');
        $params['images'] = $this->request->post('images', '', 'trim');
        $params['video_url'] = $this->request->post('video_url', '', 'trim');
        $params['video_image'] = $this->request->post('video_image', '', 'trim');
        $params['score'] = $this->request->post('score', 0, 'trim,intval');
        $params['uid'] = $this->_uid;
 
        try {
            $data = (new LifeToolsService)->reply($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 评价列表
     */
    public function replyList()
    {
        $params = [];
        // 类型:0=全部，1=晒图，2=低分，3=最新
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval');
        $params['type'] = $this->request->post('type', null, 'trim,intval');
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');
        try {
            $data = (new LifeToolsService)->replyList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 我的消息
     */
    public function myMsg()
    {
        $this->checkLogin();
        $params = [];
        // 类型：scenic-景区，sports-体育
        $params['type'] = $this->request->post('type', '', 'trim');
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval'); 
        $params['uid'] = $this->_uid; 
        try {
            $data = (new LifeToolsService)->myMsg($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取发起约战提交页面数据
     */
    public function getSportsActivityDetail()
    {
        $param['activity_id'] = $this->request->param("activity_id", 0, "intval");
        $param['price']       = $this->request->param("price", "0", "trim");
        $param['group_type']  = $this->request->param("group_type", 1, "intval");
        $param['people_num']  = $this->request->param("people_num", 1, "intval");
        try {
            $arr = (new LifeToolsSportsActivityService())->getSportsActivityDetail($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 约战列表
     */
    public function sportsActivityList()
    {
        $this->checkLogin();
        $param['keyword']  = $this->request->param("keyword", "", "trim");
        $param['status']   = $this->request->param("status", 1, "intval");
        $param['is_my']    = $this->request->param('is_my', 0, 'intval');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['uid']      = $this->_uid;
        try {
            $arr = (new LifeToolsSportsActivityService())->sportsActivityList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 约战详情
     */
    public function sportsActivityDetail()
    {
        $this->checkLogin();
        $param['order_id'] = $this->request->param("order_id", 0, "intval");
        $param['uid']      = $this->_uid;
        try {
            $arr = (new LifeToolsSportsActivityService())->sportsActivityDetail($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 加入约战
     */
    public function addSportsActivityOrder()
    {
        $this->checkLogin();
        $leader_order_id = $this->request->param("leader_order_id", 0, "intval");
        try {
            $arr = (new LifeToolsSportsActivityService())->addSportsActivityOrder($leader_order_id, $this->userInfo);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 退出约战
     */
    public function backSportsActivityOrder()
    {
        $this->checkLogin();
        $leader_order_id = $this->request->param("leader_order_id", 0, "intval");
        try {
            $arr = (new LifeToolsSportsActivityService())->backSportsActivityOrder($leader_order_id, $this->userInfo);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

      // 开门语音获取
      private function voice_baidu()
      {
        static $return;

        if (empty($return)) {
            $voicBaidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            $return = \net\Http::curlGet($voicBaidu);
        }
        return $return;
      }

      public function cancelOrder()
      {
            $this->checkLogin();
            $order_id = $this->request->post('order_id', 0, 'intval');
            try {
                $data = (new LifeToolsService)->cancelOrder($order_id, $this->_uid);
            } catch (\Exception $e) {
                return api_output_error(1001, $e->getMessage());
            }
            return api_output(0, $data);
      }

      public function test()
      {
        $voice_return = json_decode($this->voice_baidu(), true);
        $voice_access_token = $voice_return['access_token'];
        $voice_mp3 = 'https://tsn.baidu.com/text2audio?tex=哈哈哈&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
        // return $voice_mp3;
        return api_output(0, $voice_return, 'success');
      }

    public function testPlan() {
        (new LifeToolsDistributionOrderToStatementService())->runTask();
        return api_output(0, 1, 'success');
    }

}
