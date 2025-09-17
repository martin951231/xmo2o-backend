<?php
/**
 * 景区首页装修service
 */

namespace app\life_tools\model\service;

use app\common\model\db\GroupCategory;
use app\common\model\service\AreaService;
use app\common\model\service\ConfigDataService;
use app\group\model\db\Group;
use app\life_tools\model\db\LifeScenicActivityDetail;
use app\common\model\service\plan\file\LifeToolsScenicIndexRecService;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCompetition;
use app\life_tools\model\db\LifeToolsInformation;
use app\life_tools\model\db\LifeToolsRecommendCompetition;
use app\life_tools\model\db\LifeToolsRecommendCourse;
use app\life_tools\model\db\LifeToolsRecommendInfo;
use app\life_tools\model\db\LifeToolsRecommendScenic;
use app\life_tools\model\db\LifeToolsRecommendTools;
use app\life_tools\model\db\LifeToolsScenicHotRecommend;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\mall\model\service\AppOtherService;
use app\mall\model\service\WxappOtherService;
use app\life_tools\model\db\MerchantCategory;
use app\life_tools\model\db\LifeToolsAdver;
use app\life_tools\model\db\LifeToolsAdverCategory;
use app\life_tools\model\db\LifeToolsAppoint;
use app\life_tools\model\db\LifeToolsAppointJoinOrder;
use app\life_tools\model\db\LifeToolsRecommendCate;
use app\life_tools\model\db\LifeToolsSportsHotRecommend;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\service\appoint\LifeToolsAppointService;
use app\mall\model\db\MallGoods;
use app\mall\model\service\MallGoodsService;
use think\facade\Db;
use think\Model;

class HomeDecorateService
{
    public function __construct()
    {
        $this->areaService                   = new AreaService();
        $this->wxappOtherService             = new WxappOtherService();
        $this->appOtherService               = new AppOtherService();
        $this->LifeToolsAdver                = new LifeToolsAdver();
        $this->LifeToolsAdverCategory        = new LifeToolsAdverCategory();
        $this->LifeToolsRecommendCate        = new LifeToolsRecommendCate();
        $this->LifeToolsRecommendInfo        = new LifeToolsRecommendInfo();
        $this->LifeToolsRecommendCourse      = new LifeToolsRecommendCourse();
        $this->LifeToolsRecommendScenic      = new LifeToolsRecommendScenic();
        $this->LifeToolsRecommendTools       = new LifeToolsRecommendTools();
        $this->LifeToolsRecommendCompetition = new LifeToolsRecommendCompetition();
        $this->LifeToolsInformation          = new LifeToolsInformation();
        $this->MerchantCategory              = new MerchantCategory();
        $this->LifeTools                     = new LifeTools();
        $this->LifeToolsCompetition          = new LifeToolsCompetition();
        $this->LifeToolsRecommendScenic      = new LifeToolsRecommendScenic();
        $this->LifeToolsSportsHotRecommend   = new LifeToolsSportsHotRecommend();
        $this->LifeToolsScenicHotRecommend   = new LifeToolsScenicHotRecommend();
        $this->LifeToolsAppoint      = new LifeToolsAppoint();
        $this->LifeToolsTicket      = new LifeToolsTicket();
        $this->LifeToolsAppoint      = new LifeToolsAppoint();
        $this->LifeToolsAppointJoinOrder      = new LifeToolsAppointJoinOrder();
    }

    /**
     * 获取列表
     * @param $cat_key
     * @param $systemUser
     * @return array
     * @throws \think\Exception
     */
    public function getList($cat_key, $systemUser)
    {
        if (empty($cat_key)) {
            throw new \think\Exception('缺少cat_key参数');
        }
        $where_cat = ['cat_key' => $cat_key];
        $now_category = $this->LifeToolsAdverCategory->getById(true, $where_cat);
        $arr['now_category'] = $now_category;
        $many_city = cfg('many_city');
        $where = [['cat_id', '=', $now_category['cat_id']]];
        if ($systemUser['area_id']) {
            $area_id = $systemUser['area_id'];
            if ($systemUser['level'] == 1) {
                $temp = $this->areaService->getOne(['area_id' => $systemUser['area_id']]);
                if ($temp['area_type'] == 1) {
                    $city_list = $this->areaService->getAreaListByCondition(['area_pid' => $temp['area_id']]);
                    $area_id = array();
                    foreach ($city_list as $value) {
                        $area_id[] = $value['area_id'];
                    }
                } else if ($temp['area_type'] == 2) {
                    $area_id = $temp['area_id'];
                } else {
                    $area_id = $temp['area_pid'];
                }
            }
            if (is_array($area_id)) {
                array_push($where, ['city_id', 'in', $area_id]);
            } else {
                array_push($where, ['city_id', '=', $area_id]);
            }
        }
        $order = ['sort' => 'DESC', 'id' => 'DESC'];
        $adver_list = $this->LifeToolsAdver->getByCondition(true, $where, $order);
        if (!empty($adver_list)) {
            if ($many_city == 1 && !empty($adver_list)) {
                foreach ($adver_list as $key => $v) {
                    $city = $this->areaService->getOne(['area_id' => $v['city_id']]);
                    if (empty($city)) {
                        $adver_list[$key]['area_name'] = '通用';
                    } else {
                        $adver_list[$key]['area_name'] = $city['area_name'];
                    }
                }
            }
            //处理图片
            foreach ($adver_list as $key => $v) {
                $adver_list[$key]['pic'] = $v['pic'] ? replace_file_domain($v['pic']) : '';
                $adver_list[$key]['last_time'] = date('Y-m-d H:i:s', $adver_list[$key]['last_time']);
            }
            $arr['adver_list'] = $adver_list;
            $arr['many_city'] = $many_city;
        }
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 编辑或新增
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addOrEdit($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($param['url'])) {
            // throw new \think\Exception('缺少url参数');
        }
        if (empty($param['cat_key'])) {
            throw new \think\Exception('缺少cat_key参数');
        }

        if($param['cat_key'] == 'wap_life_tools_ticket_slider'){
            $param['currency'] = 1;
        }
        
        if ($param['currency'] == 1) {
            $param['province_id'] = 0;
            $param['city_id'] = 0;
        } else {
            if (!empty($param['areaList'])) {
                $param['province_id'] = $param['areaList'][0];
                $param['city_id'] = $param['areaList'][1];
            } else {
                $param['currency'] == 1;
                $param['province_id'] = 0;
                $param['city_id'] = 0;
            }
        }
        //没图片使用默认图片地址
        if (empty($param['pic'])) {
            $param['pic'] = '/v20/public/static/mall/mall_platform_default_decorate.png';
        }
        unset($param['areaList']);
        $where_cat = ['cat_key' => $param['cat_key']];
        $now_category = $this->LifeToolsAdverCategory->getById(true, $where_cat);
        $param['cat_id'] = $now_category['cat_id'];
        if (empty($param['cat_id'])) {
            throw new \think\Exception('缺少cat_id参数');
        }
        unset($param['cat_key']);
        // app打开其他小程序需要获取原始id
        if ($param['app_wxapp_id']) {
            $wxapp = $this->wxappOtherService->getById(true, ['appid' => $param['app_wxapp_id']]);
            $param['app_wxapp_username'] = $wxapp['username'];
        }
        $param['last_time'] = time();
        $param['url'] = htmlspecialchars_decode($param['url']);
        if (empty($param['id'])) {
            //添加
            $res = $this->LifeToolsAdver->addOne($param);
        } else {
            //编辑
            if (stripos($param['pic'], 'http') !== false) {
                $param['pic'] = '/upload/' . explode('/upload/', $param['pic'])[1];
            }
            $res = $this->LifeToolsAdver->editOne(['id' => $param['id']], $param);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 编辑或参看时的一些参数
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getEdit($id)
    {
        if (!empty($id)) {
            $where = ['id' => $id];
            $now_adver = $this->LifeToolsAdver->getById(true, $where);
            if (!empty($now_adver)) {
                $now_adver['pic'] = $now_adver['pic'] ? replace_file_domain($now_adver['pic']) : '';
            }
            $arr['now_adver'] = $now_adver;
            if (empty($now_adver)) {
                throw new \think\Exception('该广告不存在');
            }
            $where_cat = ['cat_id' => $now_adver['cat_id']];
            $now_category = $this->LifeToolsAdverCategory->getById(true, $where_cat);
            $arr['now_category'] = $now_category;
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        //小程序列表
        $wxapp_list = $this->wxappOtherService->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = $this->appOtherService->getAll();
        $arr['app_list'] = $app_list;
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 添加时的一些参数
     * @param $cat_id
     * @return array
     * @throws \think\Exception
     */
    public function getAdd($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('缺少cat_id参数');
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        $where_cat = ['cat_id' => $cat_id];
        $now_category = $this->LifeToolsAdverCategory->getById(true, $where_cat);
        $arr['now_category'] = $now_category;
        //小程序列表
        $wxapp_list = $this->wxappOtherService->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = $this->appOtherService->getAll();
        $arr['app_list'] = $app_list;
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     *删除
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function getDel($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $res   = $this->LifeToolsAdver->getDel($where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取活动商品
     */
    public function getCateList($param)
    {
        $cateIds = $this->LifeToolsRecommendCate->column('cat_id');
        $limit   = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['cat_id', 'not in', $cateIds],
            ['cat_status', '=', 1]
        ];
        if (!empty($param['title'])) {
            $where[] = ['cat_name', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->MerchantCategory->where($where)->order('cat_sort desc')->paginate($limit)->toArray();
        if ($res['data']) {
            foreach ($res['data'] as $k => $v) {
                $res['data'][$k]['cat_info'] = strlen($v['cat_info']) > 20 ? substr($v['cat_info'], 0, 20) . '...' : $v['cat_info'];
            }
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取活动商品
     */
    public function getInfoList($param)
    {
        $infoIds = $this->LifeToolsRecommendInfo->column('information_id');
        $limit   = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        if (empty($param['type']) || $param['type'] != 'scenic') {
            $param['type'] = 'sports';
        }
        $where = [
            ['pigcms_id', 'not in', $infoIds],
            ['type', '=', $param['type']],
            ['is_del', '=', 0]
        ];
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeToolsInformation->where($where)->order('add_time desc')->paginate($limit)->toArray();
        if (!empty($res['data'])) {
            foreach ($res['data'] as $k => $v) {
                $res['data'][$k]['show_type']  = $v['show_type'] == 1 ? '永久' : '时间段';
                $res['data'][$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d H:i:s', $v['start_time']) : '无';
                $res['data'][$k]['end_time']   = !empty($v['end_time']) ? date('Y-m-d H:i:s', $v['end_time']) : '无';
            }
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取活动商品
     */
    public function getCourseList($param)
    {
        $courseIds = $this->LifeToolsRecommendCourse->column('tools_id');
        $limit     = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['tools_id', 'not in', $courseIds],
            ['type', '=', 'course'],
            ['status', '=', 1]
        ];
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeTools->where($where)->order('sort desc')->paginate($limit)->toArray();
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取景区
     */
    public function getScenicList($param)
    {
        $scenicIds = $this->LifeToolsRecommendScenic->column('tools_id');
        $limit     = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['tools_id', 'not in', $scenicIds],
            ['type', '=', 'scenic'],
            ['status', '=', 1]
        ];
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeTools->where($where)->order('sort desc')->paginate($limit)->toArray();
        return $res;
    }
    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取景区
     */
    public function getToolsList($param)
    {
        $toolsIds = $this->LifeToolsRecommendTools->column('tools_id');
        $limit     = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['tools_id', 'not in', $toolsIds],
//            ['type', '<>', 'course'],
            ['status', '=', 1]
        ];
        if (!empty($param['type'])) {
            $where[] = ['type', '=', $param['type']];
        }
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeTools->where($where)->order('sort desc')->paginate($limit)->toArray();
        if ($res['data']) {
            $LifeToolsService = new LifeToolsService();
            foreach ($res['data'] as $k => $v) {
                $res['data'][$k]['type_val'] = $LifeToolsService->getTypeName($v['type']);
            }
        }
        return $res;
    }
    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取预约
     */
    public function getAppointList($param)
    {
        $appointIds = $this->LifeToolsRecommendTools->where('type', 'appoint')->column('tools_id');
        $limit     = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['appoint_id', 'not in', $appointIds],
            ['status', '=', 1]
        ];
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeToolsAppoint->where($where)->order('add_time desc')->paginate($limit)->toArray();
        if ($res['data']) {
            foreach ($res['data'] as $k => $v) {
                $res['data'][$k]['type_val'] = '通用预约';
                $res['data'][$k]['tools_id'] = $v['appoint_id'];
            }
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取活动商品
     */
    public function getCompetitionList($param)
    {
        $competitionIds = $this->LifeToolsRecommendCompetition->column('competition_id');
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['competition_id', 'not in', $competitionIds],
            ['is_del', '=', 0],
            ['status', '=', 1]
        ];
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        $res = $this->LifeToolsCompetition->where($where)->order('add_time desc')->paginate($limit)->toArray();
        if (!empty($res['data'])) {
            foreach ($res['data'] as $k => $v) {
                $res['data'][$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d H:i:s', $v['start_time']) : '无';
                $res['data'][$k]['end_time']   = !empty($v['end_time']) ? date('Y-m-d H:i:s', $v['end_time']) : '无';
            }
        }
        return $res;
    }

    /**
     * 添加关联商品
     */
    public function addRelatedCate($cat_id)
    {
        if (!empty($cat_id)) {
            foreach ($cat_id as $val) {
                $res_add = $this->LifeToolsRecommendCate->add([
                    'cat_id' => $val,
                    'create_time' => time()
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
                (new LifeToolsScenicIndexRecService())->runTask(); //添加推荐分类执行计划任务
            }
        }
        return true;
    }

    /**
     * 添加关联公告
     */
    public function addRelatedInfo($information_id)
    {
        if (!empty($information_id)) {
            foreach ($information_id as $val) {
                $res_add = $this->LifeToolsRecommendInfo->add([
                    'information_id' => $val,
                    'create_time' => time()
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
        }
        return true;
    }

    /**
     * 添加关联课程
     */
    public function addRelatedCourse($tools_id)
    {
        if (!empty($tools_id)) {
            foreach ($tools_id as $val) {
                $res_add = $this->LifeToolsRecommendCourse->add([
                    'tools_id' => $val,
                    'create_time' => time()
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
        }
        return true;
    }

    /**
     * 添加关联景区
     */
    public function addRelatedScenic($tools_id)
    {
        if (!empty($tools_id)) {
                $res_add = $this->LifeToolsRecommendScenic->add([
                    'tools_id' => $tools_id,
                    'create_time' => time()
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
        }
        return true;
    }

    /**
     * 添加关联门票
     */
    public function addRelatedTools($tools_id, $type)
    {
        if (!empty($tools_id)) {
            $time = time();
            foreach ($tools_id as $val) {
                $res_add = $this->LifeToolsRecommendTools->add([
                    'tools_id' => $val,
                    'type' => $type,
                    'create_time' => $time
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
        }
        return true;
    }

    /**
     * 添加关联活动
     */
    public function addRelatedCompetition($competition_id)
    {
        if (!empty($competition_id)) {
            foreach ($competition_id as $val) {
                $res_add = $this->LifeToolsRecommendCompetition->add([
                    'competition_id' => $val,
                    'create_time' => time()
                ]);
                if ($res_add === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
        }
        return true;
    }

    /**
     * 获取装修渲染的信息（景区）
     */
    public function getUrlAndRecSwitch()
    {
        $url = get_base_url('pages/lifeTools/scenic/index');
        return ['is_display' => 1, 'url' => $url];
    }

    /**
     * 获取装修渲染的信息（体育健身）
     */
    public function getUrlAndRecSwitchSport()
    {
        $url = get_base_url('pages/lifeTools/sports/index');
        return ['is_display' => 1, 'url' => $url];
    }

    /**
     * 获取装修渲染的信息（门票预约）
     */
    public function getUrlAndRecSwitchTicket()
    {
        $url = get_base_url('pages/lifeTools/scenic/ticketBook');
        return ['is_display' => 1, 'url' => $url];
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联商品列表
     */
    public function getRelatedList($page, $pageSize)
    {
        $where = ['g.cat_status' => 1];
        $res   = $this->LifeToolsRecommendCate->getDetail($where, $page, $pageSize, 'r.sort DESC');
        if (!empty($res['data'])) {
            foreach ($res['data'] as &$val) {
                $val['cat_pic'] = replace_file_domain($val['cat_pic']);
            }
        }
        $list['list']  = $res['data'];
        $list['total'] = $res['total'];
        return $list;
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联商品列表
     */
    public function getRelatedInfoList($page, $pageSize, $type = 'sports')
    {
        if ($type != 'scenic') {
            $type = 'sports';
        }
        $where = [
            'g.type' => $type,
            'g.is_del' => 0
        ];
        $res = $this->LifeToolsRecommendInfo->getDetail($where, $page, $pageSize, 'r.sort DESC');
        $list['list']  = $res['data'];
        $list['total'] = $res['total'];
        return $list;
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联商品列表
     */
    public function getRelatedCourseList($page, $pageSize)
    {
        $where = [
            'g.type'   => 'course',
            'g.status' => 1,
            'g.is_del' => 0
        ];
        $res = $this->LifeToolsRecommendCourse->getDetail($where, $page, $pageSize, 'r.sort DESC');
        $list['list']  = $res['data'];
        $list['total'] = $res['total'];
        return $list;
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联景区列表
     */
    public function getRelatedScenicList($page, $pageSize)
    {
        $where = [
            'g.type'   => 'scenic',
            'g.status' => 1,
            'g.is_del' => 0
        ];
        $res = $this->LifeToolsRecommendScenic->getDetail($where, $page, $pageSize, 'r.sort DESC');
        $list['list']  = $res['data'];
        $list['total'] = $res['total'];
        return $list;
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联商品列表
     */
    public function getRelatedToolsList($page, $pageSize)
    {
        $where = [
            ['r.tools_id', '>', 0],
            // ['g.status', '=', 1],
            // ['g.is_del', '=', 0]
        ];
        $res = $this->LifeToolsRecommendTools->getDetail($where, $page, $pageSize, 'r.sort DESC');
        $list['list'] = $res['data'];
        if ($list['list']) {
            $LifeToolsService = new LifeToolsService();
            foreach ($list['list'] as $k => $v) {
                if($v['type'] == 'appoint'){
                    $list['list'][$k]['type_val'] = '通用预约';
                    $str = '';
                    $v['a_status'] == 0 && $str = '已关闭';
                    $v['a_is_del'] == 1 && $str = '已删除';
                }else{
                    $list['list'][$k]['type_val'] = $LifeToolsService->getTypeName($v['tools_type']);
                    $str = '';
                    $v['t_status'] == 0 && $str = '已关闭';
                    $v['t_is_del'] == 1 && $str = '已删除';
                }
                $list['list'][$k]['title'] = $v['title'] . ($str ?  "($str)"  : '');
            }
        }
        $list['total'] = $res['total'];
        $data = $this->LifeToolsRecommendTools->getOne(['tools_id' => 0]);
        if (empty($data)) {
            $data = [
                'name'     => '推荐',
                'status'   => 1,
                'show'     => 1,
                'tools_id' => 0,
                'create_time' => time()
            ];
            $this->LifeToolsRecommendTools->add($data);
        } else {
            $data = $data->toArray();
        }
        $list['name'] = $data['name'];
        $list['show'] = $data['show'];
        return $list;
    }

    /**
     * @param $page
     * @param $pageSize
     * 获取关联商品列表
     */
    public function getRelatedCompetitionList($page, $pageSize)
    {
        $where = [
            'g.is_del' => 0,
            'g.status' => 1
        ];
        $res = $this->LifeToolsRecommendCompetition->getDetail($where, $page, $pageSize, 'r.sort DESC');
        $list['list']  = $res['data'];
        $list['total'] = $res['total'];
        return $list;
    }

    /**
     * @param $cat_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedSort($cat_id, $sort)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['cat_id' => $cat_id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendCate->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $pigcms_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedInfoSort($pigcms_id, $sort)
    {
        if (empty($pigcms_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['information_id' => $pigcms_id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendInfo->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedCourseSort($tools_id, $sort)
    {
        if (empty($tools_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['tools_id' => $tools_id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendCourse->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @param $sort
     * 保存景区排序
     */
    public function saveRelatedScenicSort($tools_id, $sort)
    {
        if (empty($tools_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['tools_id' => $tools_id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendScenic->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedToolsSort($id, $sort)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendTools->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $competition_id
     * @param $sort
     * 保存排序
     */
    public function saveRelatedCompetitionSort($competition_id, $sort)
    {
        if (empty($competition_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['competition_id' => $competition_id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsRecommendCompetition->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $cat_id
     * @throws \think\Exception
     * 删除关联商品
     */
    public function delOne($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['cat_id' => $cat_id];
        $res   = $this->LifeToolsRecommendCate->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $pigcms_id
     * @throws \think\Exception
     * 删除关联公告
     */
    public function delInfo($pigcms_id)
    {
        if (empty($pigcms_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['information_id' => $pigcms_id];
        $res   = $this->LifeToolsRecommendInfo->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @throws \think\Exception
     * 删除关联公告
     */
    public function delCourse($tools_id)
    {
        if (empty($tools_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['tools_id' => $tools_id];
        $res   = $this->LifeToolsRecommendCourse->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @throws \think\Exception
     * 删除关联景区
     */
    public function delScenic($tools_id)
    {
        if (empty($tools_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['tools_id' => $tools_id];
        $res   = $this->LifeToolsRecommendScenic->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $tools_id
     * @throws \think\Exception
     * 删除
     */
    public function delTools($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        $res   = $this->LifeToolsRecommendTools->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $competition_id
     * @throws \think\Exception
     * 删除关联公告
     */
    public function delCompetition($competition_id)
    {
        if (empty($competition_id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['competition_id' => $competition_id];
        $res   = $this->LifeToolsRecommendCompetition->where($where)->delete();
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * 用户端首页各种广告数据返回
     * @param $catKey
     * @param int $limit
     * @param bool $needFormart
     * @return array|string|string[]
     */
    public function getAdverByCatKey($catKey, $limit = 3, $needFormart = false)
    {
        // 当前城市
        $nowCity = cfg('now_city');
        $adverList = [];
        if (!empty($adverList)) {
            $adverList = replace_domain($adverList);
            return $adverList;
        }
        // 广告分类信息
        $nowAdverCategory = $this->LifeToolsAdverCategory->getAdverCategoryByCatKey($catKey);
        if (!$nowAdverCategory) {
            return [];
        }
        // 搜索条件
        $where = [['cat_id', '=', $nowAdverCategory['cat_id']], ['status', '=', 1]];
        // 开启多城市
        if (cfg('many_city')) {
            array_push($where, ['city_id', 'exp', Db::raw('=' . $nowCity . ' or currency = 1')]);
        }
        // 排序
        $order = ['complete' => 'DESC', 'sort' => 'DESC', 'id' => 'DESC',];
        // 广告列表
        $adverList = $this->LifeToolsAdver->getAdverListByCondition($where, $order, $limit);
        $imgCount = count($adverList);
        // 替换图片路径
        foreach ($adverList as $key => $value) {
            if ($value['pic'] == '/v20/public/static/mall/mall_platform_default_decorate.png') {
                $adverList[$key]['pic'] = cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            } else {
                $adverList[$key]['pic'] = $value['pic'] ? replace_file_domain($value['pic']) : cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            }
            $adverList[$key]['img_count'] = $imgCount;
        }
        if (!empty($adverList) && $needFormart) {
            $adverList = $this->formatAdver($adverList);
        }
        $adverList = replace_domain($adverList);
        return $adverList;
    }

    /**
     * 去掉不要的字段
     * @param $array
     * @return array
     */
    public function formatAdver($array)
    {
        foreach ($array as &$adver_value) {
            unset($adver_value['id']);
            unset($adver_value['bg_color']);
            unset($adver_value['cat_id']);
            unset($adver_value['status']);
            unset($adver_value['last_time']);
            unset($adver_value['sort']);
            unset($adver_value['province_id']);
            unset($adver_value['city_id']);
            unset($adver_value['complete']);
            unset($adver_value['img_count']);
        }
        return $array;
    }

    /**
     * 首页滚动公告
     * $type 1=滚动公告 2=推荐课程 3=热门活动 4=门票预约推荐 5=景区排名
     * $param
     * @return array
     */
    public function getIndexRecommend($type = 1, $param = [])
    {
        $data = [];
        switch ($type) {
            case 1:
                $data = $this->LifeToolsRecommendInfo->getList([['r.status', '=', 1], ['g.type', '=', 'sports'], ['g.is_del', '=', 0], ['g.show_type', 'exp', Db::raw('= 1 OR (start_time <= '. time() .' AND end_time >= ' . time() . ')')]], 'g.title,r.information_id', 'r.sort desc', 5);
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['url'] = get_base_url() . 'pages/lifeTools/information/detail?id=' . $v['information_id'];
                        $data[$k]['act_type'] ='normal';
                    }
                }
                break;
            case 2:
                $data = $this->LifeToolsRecommendCourse->getList(['r.status' => 1, 'g.status' => 1, 'g.is_del' => 0], 'g.title,g.cover_image as image,g.money,r.tools_id', 'r.sort desc', 3);
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['url']   = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $v['tools_id'];
                        $data[$k]['image'] = replace_file_domain($v['image']);
                        $data[$k]['act_type'] ='normal';
                    }
                }
                break;
            case 3:
                $data = $this->LifeToolsRecommendCompetition->getList(['r.status' => 1, 'g.status' => 1, 'g.is_del' => 0], 'g.title,g.image_small as image,g.price,g.address,g.start_time,g.end_time,r.competition_id', 'r.sort desc', 3);
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['url']         = get_base_url() . 'pages/lifeTools/match/detail?id=' . $v['competition_id'];
                        $data[$k]['compet_time'] = date('Y-m-d', $v['start_time']) . '开始';
                        $data[$k]['image']       = replace_file_domain($v['image']);
                        $data[$k]['act_type'] ='normal';
                    }
                }
                break;
            case 4:
                $condition = [];
                $condition[] = ['r.status', '=', 1];
                $condition[] = ['r.tools_id', '<>', 0];
                $data = $this->LifeToolsRecommendTools->getRecommendList($param['pageSize'] ?? 10);
                $LifeToolsService =  new LifeToolsService();
                foreach ($data['data'] as $k => $v) {
                    if($v['r_type'] == 'appoint'){
                        $data['data'][$k]['url'] = get_base_url() . 'pages/lifeTools/appointment/detail?id=' . $v['tools_id'];
                        if (!empty($param['long']) && !empty($param['lat'])) { //计算距离
                            $data['data'][$k]['distance'] = $LifeToolsService->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                        }
                        $data['data'][$k]['sale_count'] = $this->LifeToolsAppointJoinOrder->where([
                            ['appoint_id', '=', $v['tools_id']],
                            ['status', 'in', [1, 3, 4]]
                        ])->count();
                        $data['data'][$k]['image'] = $v['image'];
                        $data['data'][$k]['label'] = ['通用预约'];
                        $data['data'][$k]['url'] = get_base_url() . 'pages/lifeTools/appointment/detail?id=' . $v['tools_id'];
                    }else{
                        $data['data'][$k]['url']   = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $v['tools_id'];
                            $data['data'][$k]['image']    = thumb_img(replace_file_domain($v['image']),200,200) ;
                            $data['data'][$k]['label'] = $this->LifeTools->getLabelArrAttr('', $v);
                            $data['data'][$k]['distance'] = 0;
                            $data['data'][$k]['act_type'] ='normal';
                            if (!empty($param['long']) && !empty($param['lat'])) { //计算距离
                                $data['data'][$k]['distance'] = $LifeToolsService->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                            }
                            if($v['type']=='scenic'){
                                $where_act=[['lt.tools_id','=',$v['tools_id']],['lt.status','=',1],['lt.is_del','=',0],
                                    ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                                $ret=(new LifeScenicActivityDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,lt.tools_id');
                               $data['data'][$k]['act_type'] =$ret['act_type'];
                            }
                            if($v['type']=='stadium' || $v['type']=='course'){
                                $where_act=[['lt.tools_id','=',$v['tools_id']],['lt.status','=',1],['lt.is_del','=',0],
                                    ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                                $ret=(new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,l.tools_id');
                                if(!empty($ret)){
                                    $data['data'][$k]['act_stock_num'] =$ret['act_stock_num'];
                                    $data['data'][$k]['limited_status'] =$ret['limited_status'];
                                    $data['data'][$k]['act_type'] =$ret['act_type'];
                                }
                            }
                            $data['data'][$k]['is_close_name'] = $v['is_close']==1?'暂停营业':'正常营业';
                            $data['data'][$k]['is_close_body'] = $v['is_close_body']?:'';
                    }
                }
         
                break;
            case 5:
                $data = $this->LifeToolsRecommendScenic->getList(['r.status' => 1, 'g.status' => 1, 'g.is_del' => 0]);
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['url']   = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $v['tools_id'];
                        $data[$k]['image'] = replace_file_domain($v['cover_image']);
                        $data[$k]['label'] = $this->LifeTools->getLabelArrAttr('', $v);
                        $data[$k]['act_type'] ='normal';
                    }
                }
                break;
            case 6:
                $data = $this->LifeToolsRecommendInfo->getList([['r.status', '=', 1], ['g.type', '=', 'scenic'], ['g.is_del', '=', 0], ['g.show_type', 'exp', Db::raw('= 1 OR (start_time <= '. time() .' AND end_time >= ' . time() . ')')]], 'g.title,r.information_id', 'r.sort desc', 5);
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['url'] = get_base_url() . 'pages/lifeTools/information/detail?id=' . $v['information_id'];
                        $data[$k]['act_type'] ='normal';
                    }
                }
                break;
        }
        return $data;
    }

    /**
     * 设置门票预约推荐主标题
     */
    public function setToolsName($param)
    {
        $data = $this->LifeToolsRecommendTools->getOne(['tools_id' => 0]);
        if (empty($data)) {
            $res = $this->LifeToolsRecommendTools->add($param);
        } else {
            $res = $this->LifeToolsRecommendTools->updateThis(['tools_id' => 0], $param);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }
    

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取体育热门用户端展示的推荐商品数据
     */
    public function getSportsHotRecommendByUser()
    {
        $type = $param['type'] ?? 'stadium';// 商品类型：stadium-场馆，course-课程，appoint-预约活动，mall-商城

        $configData = (new ConfigDataService())->getConfigData();

        $tabArr = [];
        $sortArr = [];
        if($configData['life_tools_sports_index_stadium_status']){
            $tabArr[] = [
                'name'=>$configData['life_tools_sports_index_stadium_desc'] ?: '场馆',
                'type'=>'stadium',
                'sort'=>$configData['life_tools_sports_index_stadium_sort'] ?: 0,
                'url'=>$configData['life_tools_sports_index_stadium_url'] ?: '',
                'column' => 3,
                'list' => []
            ];
            $sortArr[] = $configData['life_tools_sports_index_stadium_sort'];
        }
        if($configData['life_tools_sports_index_course_status']){
            $tabArr[] = [
                'name'=>$configData['life_tools_sports_index_course_desc'] ?: '课程',
                'type'=>'course',
                'sort'=>$configData['life_tools_sports_index_course_sort'] ?: 0,
                'url'=>$configData['life_tools_sports_index_course_url'] ?: '',
                'column' => 3,
                'list' => []
            ];
            $sortArr[] = $configData['life_tools_sports_index_course_sort'];
        }
        if($configData['life_tools_sports_index_competition_status']){
            $tabArr[] = [
                'name'=>$configData['life_tools_sports_index_competition_desc'] ?: '活动',
                'type'=>'competition',
                'sort'=>$configData['life_tools_sports_index_competition_sort'] ?: 0,
                'url'=>$configData['life_tools_sports_index_competition_url'] ?: '',
                'column' => 3,
                'list' => []
            ];
            $sortArr[] = $configData['life_tools_sports_index_competition_sort'];
        }
        if($configData['life_tools_sports_index_mall_status']){
            $tabArr[] = [
                'name'=>$configData['life_tools_sports_index_mall_desc'] ?: '商城',
                'type'=>'mall',
                'sort'=>$configData['life_tools_sports_index_mall_sort'] ?: 0,
                'url'=>$configData['life_tools_sports_index_mall_url'] ?: '',
                'column' => 2,
                'list' => []
            ];
            $sortArr[] = $configData['life_tools_sports_index_mall_sort'];
        }
        
        array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$tabArr);

        foreach($tabArr as $key => &$tab){
            $type = $tab['type'];// 商品类型：stadium-场馆，course-课程，appoint-预约活动，mall-商城
            switch($type){
                case 'stadium':
                case 'course':
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                        't.is_del' => 0,
                        't.status' => 1,
                    ];
                    $res = $this->LifeToolsSportsHotRecommend->alias('r')
                    ->field('t.title,t.cover_image as image,m.name as merchant_name,r.id,r.sort,r.recommend_id,t.money')
                    ->where($where)
                    ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'life_tools t','t.tools_id = r.recommend_id')
                    ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.sort DESC,t.add_time DESC')
                    ->select()->toArray();

                    foreach($res as $key => $value){
                        $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 224, 144);
                        $res[$key]['url']   = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $value['recommend_id'];
                    }
                    break;
                case 'competition':// 预约活动
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                        't.is_del' => 0,
                    ];
                    $res = $this->LifeToolsSportsHotRecommend->alias('r')
                    ->field('t.title,t.image_small as image,t.price as money,t.start_time,t.address,r.id,r.sort,r.recommend_id')
                    ->where($where)
                    ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'life_tools_competition t','t.competition_id = r.recommend_id')
                    ->order('r.sort DESC,t.add_time DESC')
                    ->select()->toArray();

                    foreach($res as $key => $value){
                        $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 200, 200);
                        $res[$key]['start_time'] = $value['start_time'] ? date('Y-m-d', $value['start_time']) : '';
                        $res[$key]['url']   = get_base_url() . 'pages/lifeTools/match/detail?id=' . $value['recommend_id'];
                    }
                    break;
                case 'mall':// 商城
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                    ];
                    $res = $this->LifeToolsSportsHotRecommend->alias('r')
                    ->field('t.name as title,t.image,t.min_price as money,t.goods_type,m.name as merchant_name,r.id,r.sort,r.recommend_id')
                    ->where($where)
                    ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'mall_goods t','t.goods_id = r.recommend_id')
                    ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.create_time DESC')
                    ->select()->toArray();

                    foreach($res as $key => $value){
                        $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 200, 200);
                        $res[$key]['url']   = get_base_url() . 'pages/shopmall_third/commodity_details?goods_id=' . $value['recommend_id'];
                    }

                    break;
            }
            $tab['list'] = $res;

            if(empty($tab['list'])){
                unset($tabArr[$key]);
            }
        }
        
        return array_values($tabArr);
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取体育热门推荐商品
     */
    public function getSportsHotRecommendList($param)
    {
        $type = $param['type'] ?? 'stadium';// 商品类型：stadium-场馆，course-课程，appoint-预约活动，mall-商城

        // 查找已经添加的商品id
        $recommendIds = $this->LifeToolsSportsHotRecommend->where(['type'=>$type,'is_del'=>0])->column('recommend_id');

        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];

        switch($type){
            case 'stadium':
            case 'course':
                $param['tools_ids_not'] = $recommendIds;
                $param['status'] = 1;
                $param['page_size'] = $param['pageSize'];
                $res = (new LifeToolsService())->getLifeToolsList($param)->toArray();
                if(isset($res['data']) && $res['data']){
                    foreach($res['data'] as $key => $value){
                        $res['data'][$key]['recommend_id'] = $value['tools_id'];
                    }
                }
                break;
            case 'competition':// 体育赛事活动
                $param['competition_ids_not'] = $recommendIds;
                $param['status'] = 1;
                $res = (new LifeToolsCompetitionService())->getLimitList($param);
                if(isset($res['data']) && $res['data']){
                    foreach($res['data'] as $key => $value){
                        $res['data'][$key]['recommend_id'] = $value['competition_id'];
                    }
                }
                break;
            case 'mall':// 商城                
                $where = [
                    ['t.is_del', '=', 0],
                    ['t.status', '=', 1]
                ];
                if($recommendIds){
                    $where[] = ['t.goods_id' ,'not in', $recommendIds];  
                }
                if($param['title']){
                    $where[] = ['t.name' ,'like', '%'.$param['title'].'%'];  
                }
                $res = (new MallGoods())->alias('t')
                ->field('t.name as title,m.name as merchant_name,t.goods_id as recommend_id')
                ->where($where)
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                ->order('t.create_time DESC')
                ->paginate($param['pageSize']);
                break;
        }
        return $res;
    }
    
    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取体育热门推荐商品
     */
    public function getSportsHotRecommendSelectedList($param)
    {
        $type = $param['type'] ?? 'stadium';// 商品类型：stadium-场馆，course-课程，appoint-预约活动，mall-商城
        switch($type){
            case 'stadium':
            case 'course':
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                    't.is_del' => 0,
                ];
                $res = $this->LifeToolsSportsHotRecommend->alias('r')
                ->field('t.title,t.money,m.name as merchant_name,r.id,r.sort')
                ->where($where)
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'life_tools t','t.tools_id = r.recommend_id')
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                ->order('r.sort DESC,t.sort DESC,t.add_time DESC')
                ->paginate($param['pageSize']);
                break;
            case 'competition':// 体育赛事活动
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                    't.is_del' => 0,
                ];
                $res = $this->LifeToolsSportsHotRecommend->alias('r')
                ->field('t.title,t.price as money,r.id,r.sort')
                ->where($where)
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'life_tools_competition t','t.competition_id = r.recommend_id')
                ->order('r.sort DESC,t.add_time DESC')
                ->paginate($param['pageSize']);
                break;
            case 'mall':// 商城
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                ];
                $res = $this->LifeToolsSportsHotRecommend->alias('r')
                ->field('t.name as title,t.min_price as money,t.goods_type,m.name as merchant_name,r.id,r.sort')
                ->where($where)
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'mall_goods t','t.goods_id = r.recommend_id')
                ->join($this->LifeToolsSportsHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                ->order('r.sort DESC,t.create_time DESC')
                ->paginate($param['pageSize']);
                break;
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 添加体育热门推荐商品
     */
    public function addSportsHotRecommendList($param)
    {
        $type = $param['type'] ?? '';// 商品类型：stadium-场馆，course-课程，appoint-预约活动，mall-商城
        $recommendIds = $param['recommend_id'] ?? [];

        if(empty($type) || empty($recommendIds)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        
        $addData = [];
        foreach ($recommendIds as $k => $v) {
            $addData[] = [
                'type' => $type,
                'recommend_id' => $v,
                'add_time' => time()
            ];
        }
        $res = $this->LifeToolsSportsHotRecommend->addAll($addData);
        if($res === false){
            throw new \think\Exception(L_('添加失败'), 1003);
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 删除体育热门推荐商品
     */
    public function delSportsHotRecommendList($param)
    {
        $id = $param['id'] ?? [];

        if(empty($id) ){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = [
            ['id', 'in', $id]
        ];

        $res = $this->LifeToolsSportsHotRecommend->updateThis($where, ['is_del'=>1]);

        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }
        return $res;
    }

    /**
     * @param $id
     * @param $sort
     * 保存排序
     */
    public function saveSportsHotRecommendSort($id, $sort)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsSportsHotRecommend->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取景区热门用户端展示的推荐商品数据
     */
    public function getScenicHotRecommendByUser()
    {
        $type = $param['type'] ?? 'scenic';// 商品类型：scenic-文旅，hotel-酒店，appoint-预约活动，mall-商城
        $configData = (new ConfigDataService())->getConfigData();
        $tabArr  = [];
        $sortArr = [];
        if ($configData['life_tools_scenic_index_scenic_status']) {
            $tabArr[] = [
                'name' => $configData['life_tools_scenic_index_scenic_desc'] ?? '文旅',
                'type' => 'scenic',
                'sort' => $configData['life_tools_scenic_index_scenic_sort'] ?? 0,
                'url'  => $configData['life_tools_scenic_index_scenic_url'] ??'',
                'column' => 3,
                'list'   => []
            ];
            $sortArr[] = $configData['life_tools_scenic_index_scenic_sort'];
        }
        if ($configData['life_tools_scenic_index_hotel_status']) {
            $tabArr[] = [
                'name' => $configData['life_tools_scenic_index_hotel_desc'] ?? '酒店',
                'type' => 'hotel',
                'sort' => $configData['life_tools_scenic_index_hotel_sort'] ?? 0,
                'url'  => $configData['life_tools_scenic_index_hotel_url'] ?? '',
                'column' => 3,
                'list'   => []
            ];
            $sortArr[] = $configData['life_tools_scenic_index_hotel_sort'];
        }
        if ($configData['life_tools_scenic_index_appoint_status']) {
            $tabArr[] = [
                'name' => $configData['life_tools_scenic_index_appoint_desc'] ?? '预约',
                'type' => 'appoint',
                'sort' => $configData['life_tools_scenic_index_appoint_sort'] ?? 0,
                'url'  => $configData['life_tools_scenic_index_appoint_url'] ?? '',
                'column' => 3,
                'list'   => []
            ];
            $sortArr[] = $configData['life_tools_scenic_index_appoint_sort'];
        }
        if ($configData['life_tools_scenic_index_mall_status']) {
            $tabArr[] = [
                'name' => $configData['life_tools_scenic_index_mall_desc'] ?? '商城',
                'type' => 'mall',
                'sort' => $configData['life_tools_scenic_index_mall_sort'] ?? 0,
                'url'  => $configData['life_tools_scenic_index_mall_url'] ?? '',
                'column' => 2,
                'list'   => []
            ];
            $sortArr[] = $configData['life_tools_scenic_index_mall_sort'];
        }
        array_multisort($sortArr,SORT_DESC,SORT_NUMERIC,$tabArr);
        foreach ($tabArr as $k => &$tab) {
            $type = $tab['type'];// 商品类型：scenic-文旅，hotel-酒店，appoint-预约活动，mall-商城
            switch($type){
                case 'scenic':
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                        't.is_del' => 0,
                        't.status' => 1,
                    ];
                    $res = $this->LifeToolsScenicHotRecommend->alias('r')
                        ->field('t.money,t.title,t.cover_image as image,t.label,m.name as merchant_name,r.id,r.sort,r.recommend_id')
                        ->where($where)
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'life_tools t','t.tools_id = r.recommend_id')
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                        ->order('r.sort DESC,t.sort DESC,t.add_time DESC')
                        ->select();
                    if (!empty($res)) {
                        $res = $res->toArray();
                        foreach($res as $key => $value){
                            $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 224, 144);
                            $res[$key]['label'] = !empty($value['label']) ? explode(' ', $value['label']) : [];
                            $res[$key]['url']   = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $value['recommend_id'];
                        }
                    } else {
                        $res = [];
                    }
                    break;
                case 'hotel':// 酒店
                    $where = [
                        ['r.type'   ,'=', $type],
                        ['r.is_del' ,'=', 0],
                        ['t.status' ,'=', 1],
                        ['t.type'   ,'=', 1],
                        ['t.end_time'   ,'>', time()]
                    ];
                    $res = $this->LifeToolsScenicHotRecommend->alias('r')
                        ->field('t.name as title,t.pic as image,t.price as money, m.name as merchant_name,r.id,r.sort,r.recommend_id')
                        ->where($where)
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'group t','t.group_id = r.recommend_id')
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                        ->order('r.sort DESC,t.add_time DESC')
                        ->select();
                    if (!empty($res)) {
                        $res = $res->toArray();
                        foreach ($res as $key => $value) {
                            $res[$key]['image'] = !empty($value['image']) ? thumb_img(replace_file_domain(explode(';', $value['image'])[0]), 600, 600) : '';
                            $res[$key]['url']   = get_base_url('', 1) . 'pages/group/v1/groupDetail/index?group_id=' . $value['recommend_id'];
                        }
                    } else {
                        $res = [];
                    }
                    break;
                case 'appoint':// 预约活动
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                        't.is_del' => 0,
                    ];
                    $res = $this->LifeToolsScenicHotRecommend->alias('r')
                        ->field('t.title,t.image_small as image,t.price as money,t.start_time,t.address,m.name as merchant_name,r.id,r.sort,r.recommend_id')
                        ->where($where)
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'life_tools_appoint t','t.appoint_id = r.recommend_id')
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                        ->order('r.sort DESC,t.add_time DESC')
                        ->select();
                    if (!empty($res)) {
                        $res = $res->toArray();
                        foreach($res as $key => $value){
                            $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 600, 600);
                            $res[$key]['start_time'] = $value['start_time'] ? date('Y-m-d', $value['start_time']) : '';
                            $res[$key]['url']   = get_base_url() . 'pages/lifeTools/appointment/detail?id=' . $value['recommend_id'];
                        }
                    } else {
                        $res = [];
                    }
                    break;
                case 'mall':// 商城
                    $where = [
                        'r.type' => $type,
                        'r.is_del' => 0,
                        't.status' => 1,
                        't.is_del' => 0
                    ];
                    $res = $this->LifeToolsScenicHotRecommend->alias('r')
                        ->field('t.name as title,t.image,t.min_price as money,t.goods_type,m.name as merchant_name,r.id,r.sort,r.recommend_id')
                        ->where($where)
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'mall_goods t','t.goods_id = r.recommend_id')
                        ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                        ->order('r.sort DESC,t.create_time DESC')
                        ->select();
                    if (!empty($res)) {
                        $res = $res->toArray();
                        foreach($res as $key => $value){
                            $res[$key]['image'] = thumb_img(replace_file_domain($value['image']), 600, 600);
                            $res[$key]['url']   = get_base_url() . 'pages/shopmall_third/commodity_details?goods_id=' . $value['recommend_id'];
                        }
                    } else {
                        $res = [];
                    }
                    break;
            }
            if (empty($res)) {
                unset($tabArr[$k]);
                continue;
            }
            $tab['list'] = $res;
        }
        $tabArr = array_values($tabArr);
        return $tabArr;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取体育热门推荐商品
     */
    public function getScenicHotRecommendList($param)
    {
        $type = $param['type'] ?? 'scenic';// 商品类型：scenic-文旅，hotel-酒店，appoint-预约活动，mall-商城

        // 查找已经添加的商品id
        $recommendIds = $this->LifeToolsScenicHotRecommend->where(['type'=>$type,'is_del'=>0])->column('recommend_id');

        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];

        switch($type){
            case 'scenic':
                $param['tools_ids_not'] = $recommendIds;
                $param['tools_type'] = 'scenic';
                $param['status'] = 1;
                $param['page_size'] = $param['pageSize'];
                $res = (new LifeToolsService())->getLifeToolsList($param)->toArray();
                if(isset($res['data']) && $res['data']){
                    foreach($res['data'] as $key => $value){
                        $res['data'][$key]['recommend_id'] = $value['tools_id'];
                    }
                }
                break;
            case 'hotel':// 酒店
                $catIds = (new GroupCategory())->where(['is_hotel' => 1, 'cat_status' => 1])->column('cat_id');
                if ($catIds) {
                    $where = [
                        ['g.status', '=', 1],
                        ['g.type', '=', 1],
                        ['m.status', '=', 1],
                        ['g.end_time', '>', time()],
                        ['ms.have_group', '=', 1],
                        ['g.cat_id|g.cat_fid', 'in', $catIds],
                        ['g.group_id', 'not in', $recommendIds]
                    ];
                    $res = (new Group())->getHotelList($where, $limit);
                }
                break;
            case 'appoint':// 预约活动
                $param['appoint_ids_not'] = $recommendIds;
                $param['status'] = 1;
                $param['page_size'] = $param['pageSize'];
                $res = (new LifeToolsAppointService())->getList($param);
                if(isset($res['list']) && $res['list']){
                    foreach($res['list'] as $key => $value){
                        $res['list'][$key]['recommend_id'] = $value['appoint_id'];
                        $res['list'][$key]['merchant_name'] = $value['mer_name'];
                    }
                }
                $res['data'] = $res['list'] ?? [];
                break;
            case 'mall':// 商城
                $where = [
                    ['t.is_del', '=', 0],
                    ['t.status', '=', 1]
                ];
                if($recommendIds){
                    $where[] = ['t.goods_id' ,'not in', $recommendIds];
                }
                if($param['title']){
                    $where[] = ['t.name' ,'like', '%'.$param['title'].'%'];
                }
                $res = (new MallGoods())->alias('t')
                    ->field('t.name as title,m.name as merchant_name,t.goods_id as recommend_id')
                    ->where($where)
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('t.create_time DESC')
                    ->paginate($param['pageSize']);
                break;
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 获取体育热门推荐商品
     */
    public function getScenicHotRecommendSelectedList($param)
    {
        $type = $param['type'] ?? 'scenic';// 商品类型：scenic-文旅，hotel-酒店，appoint-预约活动，mall-商城
        switch($type){
            case 'scenic':
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                    't.is_del' => 0,
                ];
                $res = $this->LifeToolsScenicHotRecommend->alias('r')
                    ->field('t.title,t.money,m.name as merchant_name,r.id,r.sort')
                    ->where($where)
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'life_tools t','t.tools_id = r.recommend_id')
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.sort DESC,t.add_time DESC')
                    ->paginate($param['pageSize']);
                break;
            case 'hotel':// 酒店
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                    't.status' => 1,
                    't.type' => 1,
                ];
                $res = $this->LifeToolsScenicHotRecommend->alias('r')
                    ->field('t.name as title,t.price as money, m.name as merchant_name,r.id,r.sort')
                    ->where($where)
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'group t','t.group_id = r.recommend_id')
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.add_time DESC')
                    ->paginate($param['pageSize']);
                break;
            case 'appoint':// 预约活动
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                    't.is_del' => 0,
                ];
                $res = $this->LifeToolsScenicHotRecommend->alias('r')
                    ->field('t.title,t.price as money, m.name as merchant_name,r.id,r.sort')
                    ->where($where)
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'life_tools_appoint t','t.appoint_id = r.recommend_id')
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.add_time DESC')
                    ->paginate($param['pageSize']);
                break;
            case 'mall':// 商城
                $where = [
                    'r.type' => $type,
                    'r.is_del' => 0,
                ];
                $res = $this->LifeToolsScenicHotRecommend->alias('r')
                    ->field('t.name as title,t.min_price as money,t.goods_type,m.name as merchant_name,r.id,r.sort')
                    ->where($where)
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'mall_goods t','t.goods_id = r.recommend_id')
                    ->join($this->LifeToolsScenicHotRecommend->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                    ->order('r.sort DESC,t.create_time DESC')
                    ->paginate($param['pageSize']);
                break;
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 添加体育热门推荐商品
     */
    public function addScenicHotRecommendList($param)
    {
        $type = $param['type'] ?? 'scenic';// 商品类型：scenic-文旅，hotel-酒店，appoint-预约活动，mall-商城
        $recommendIds = $param['recommend_id'] ?? [];

        if(empty($type) || empty($recommendIds)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $addData = [];
        foreach ($recommendIds as $k => $v) {
            $addData[] = [
                'type' => $type,
                'recommend_id' => $v,
                'add_time' => time()
            ];
        }
        $res = $this->LifeToolsScenicHotRecommend->addAll($addData);
        if($res === false){
            throw new \think\Exception(L_('添加失败'), 1003);
        }
        return $res;
    }

    /**
     * @param $param
     * @return array|mixed|void
     * @throws \think\Exception
     * 删除体育热门推荐商品
     */
    public function delScenicHotRecommendList($param)
    {
        $id = $param['id'] ?? [];

        if(empty($id) ){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = [
            ['id', 'in', $id]
        ];

        $res = $this->LifeToolsScenicHotRecommend->updateThis($where, ['is_del'=>1]);

        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }
        return $res;
    }

    /**
     * @param $id
     * @param $sort
     * 保存排序
     */
    public function saveScenicHotRecommendSort($id, $sort)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少参数');
        }
        $where = ['id' => $id];
        $data  = ['sort' => $sort];
        $res   = $this->LifeToolsScenicHotRecommend->update($data, $where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $res;
    }

}