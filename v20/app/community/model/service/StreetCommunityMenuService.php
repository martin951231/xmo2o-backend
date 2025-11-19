<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/21 14:55
 */

namespace app\community\model\service;


use app\common\model\service\config\ConfigCustomizationService;

class StreetCommunityMenuService
{


    /**
     * 返回前端需要的菜单格式
     * @author: wanziyang
     * @date_time: 2020/5/21 15:09
     * @param array $menuList
     * @param array $adminUser
     * @return array
     */
    public function formartMenuList($menuList = [],$adminUser=[]) {
        $returnArr = [];
        if($adminUser['area_type'] == 1){
            $title = '社区';
        }else{
            $title = '街道';
        }
        // 首页
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_index';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_1';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '首页',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/street_index';
        $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewStreet&a=index_new&iframe=true');

        $returnArr[] = $tmpMenu;

        // 街道设置
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_set';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_2';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => $title.'管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/config/index";

        // 街道设置-基本信息
        $returnArr[] = $tmpMenu;
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_info';
        $tmpMenu['parentId'] = 'street_2';
        $tmpMenu['id'] = 'street_2_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '基本信息',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'ConfigStreetComunityIndex';
        $tmpMenu['path'] = '/community/streetCommunity/config/index';
        // 街道设置-可视化页面
        $returnArr[] = $tmpMenu;
        $configCustomizationService=new ConfigCustomizationService();
        $cityCountyManageFuncJudge=$configCustomizationService->getCityCountyManageFuncJudge();
        if($cityCountyManageFuncJudge>0){
            $tmpMenu = [];
            $tmpMenu['name'] = 'street_comunity_list';
            $tmpMenu['parentId'] = 'street_2';
            $tmpMenu['id'] = 'street_2_10';
            $tmpMenu['mlevel'] = 2;
            $tmpMenu['is_dir'] = 0;
            $tmpMenu['meta'] = [
                'icon' => 'tasks',
                'title' => '数据中心',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'streetComunityList';
            $tmpMenu['path'] = '/community/streetCommunity/contentTable/comunityList';
            if($adminUser['area_type'] == 1){
                $tmpMenu['component'] = 'streetComunityHouseList';
                $tmpMenu['path'] = '/community/streetCommunity/contentTable/comunityHouseList';
            }
            // 街道设置-可视化页面
            $returnArr[] = $tmpMenu;
            
        }
        $tmpMenu = [];
        $tmpMenu['name'] = 'VisualizationIndex';
        $tmpMenu['parentId'] = 'street_2';
        $tmpMenu['id'] = 'street_2_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '可视化页面',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'VisualizationIndex';
        $tmpMenu['path'] = '/community/streetCommunity/visualization/VisualizationIndex';
        $returnArr[] = $tmpMenu;

        if($adminUser['area_type'] == 0){
            // 街道设置-组织架构
            $tmpMenu = [];
            $tmpMenu['name'] = 'OrganizationList';
            $tmpMenu['parentId'] = 'street_2';
            $tmpMenu['id'] = 'street_2_3';
            $tmpMenu['mlevel'] = 2;
            $tmpMenu['is_dir'] = 0;
            $tmpMenu['meta'] = [
                'icon' => 'tasks',
                'title' => '组织架构',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'OrganizationList';
            $tmpMenu['path'] = '/community/streetCommunity/Organization/OrganizationList';
            $returnArr[] = $tmpMenu;
        }


        // 街道设置-固定资产管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'FixedAssetsList';
        $tmpMenu['parentId'] = 'street_2';
        $tmpMenu['id'] = 'street_2_4';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '固定资产管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'FixedAssetsList';
        $tmpMenu['path'] = '/community/streetCommunity/fixedAssets/FixedAssetsList';
        $returnArr[] = $tmpMenu;
        $is_func_yougejianet_special=$configCustomizationService->getfuncYougejianetSpecial();

        if($adminUser['area_type'] == 1 && $is_func_yougejianet_special){
            // 街道设置-服务管理 定制
            $tmpMenu = [];
            $tmpMenu['name'] = 'serviceSpecialNavList';
            $tmpMenu['parentId'] = 'street_2';
            $tmpMenu['id'] = 'street_2_5';
            $tmpMenu['mlevel'] = 2;
            $tmpMenu['is_dir'] = 0;
            $tmpMenu['meta'] = [
                'icon' => 'tasks',
                'title' => '服务管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'serviceSpecialNavList';
            $tmpMenu['path'] = '/community/streetCommunity/functPage/serviceSpecialNavList';
            $returnArr[] = $tmpMenu;
        }
        
        // 民生管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_people_livelihood';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_4';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '民生管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/street_community.iframe/house_index";
        $returnArr[] = $tmpMenu;

        // 民生管理-业主管理
//        $tmpMenu = [];
//        $tmpMenu['name'] = 'house_index';
//        $tmpMenu['parentId'] = 'street_4';
//        $tmpMenu['id'] = 'street_5';
//        $tmpMenu['meta'] = [
//            'icon' => 'tasks',
//            'title' => '业主管理',
//            'show' => true,
//            'permission' => 'system',
//        ];
//        $tmpMenu['component'] = 'System';
//        $tmpMenu['path'] = '/community/street_community.iframe/house_index';
//        if ($adminUser && 0==$adminUser['area_type']) {
//            $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewStreet&a=community_index&iframe=true';
//            $street_idss = $adminUser['area_id'];
//            $src_url .= '&street_idss='.$street_idss;
//        } elseif ($adminUser && 1==$adminUser['area_type']) {
//            $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewStreet&a=house_index&iframe=true';
//            $community_idss = $adminUser['area_pid'];
//            $src_url .= '&community_idss='.$community_idss;
//        }
//        $tmpMenu['src'] = urlencode($src_url);
//        $returnArr[] = $tmpMenu;

        //todo 新版业主管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'house_index';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '业主管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PeopleLivelihoodUserList';
        $tmpMenu['path'] = '/community/streetCommunity/peopleLivelihood/userList';
        $returnArr[] = $tmpMenu;



        //网格化点击编辑跳转
        $tmpMenu = [];
        $tmpMenu['name'] = 'user_index';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '业主管理',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/house_index';
        $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewStreet&a=owner_edit&pigcms_id=3451&usernum=1-8888&iframe=true';
        $tmpMenu['src'] = urlencode($src_url);
        $returnArr[] = $tmpMenu;

        // 民生管理-留言建议
        $tmpMenu = [];
        $tmpMenu['name'] = 'messageSuggestionsList';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_3';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '留言建议',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PeopleLivelihoodMessageSuggestionsList';
        $tmpMenu['path'] = '/community/streetCommunity/peopleLivelihood/messageSuggestionsList';
        $returnArr[] = $tmpMenu;

        // 民生管理-投票管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_vote_index';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_4';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '投票管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/vote_index';
        $src_url = cfg('site_url') . '/packapp/vote/index.html#/vote_list';
        if ($adminUser && 0==$adminUser['area_type']) {
            $src_url .= '?from=street';
        } elseif ($adminUser && 1==$adminUser['area_type']) {
            $src_url .= '?from=community';
        }
        $tmpMenu['src'] = urlencode($src_url);
        $returnArr[] = $tmpMenu;

        // 民生管理-志愿者活动管理
        $xtitle=cfg('area_street_active_alias');
        if(empty($xtitle)){
            $xtitle='志愿者活动管理';
        }
        $tmpMenu = [];
        $tmpMenu['name'] = 'volunteerActivitiesList';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_5';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => $xtitle,
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PeopleLivelihoodVolunteerActivitiesList';
        $tmpMenu['path'] = '/community/streetCommunity/peopleLivelihood/volunteerActivitiesList';
        $returnArr[] = $tmpMenu;
        // 民生管理-志愿者活动管理-添加编辑
        $tmpMenu = [];
        $tmpMenu['name'] = 'addVolunteerActivitiesInfo';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_6';
        $tmpMenu['mlevel'] = 3;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '志愿者活动添加/编辑',
            'show' => false,
            'permission' => 'system',
            'keepAlive' => true
        ];
        $tmpMenu['component'] = 'PeopleLivelihoodAddVolunteerActivitiesInfo';
        $tmpMenu['path'] = '/community/streetCommunity/peopleLivelihood/addVolunteerActivitiesInfo';
        $returnArr[] = $tmpMenu;
        // 民生管理-志愿者活动管理-报名志愿者活动管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'signVolunteerActivitiesList';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_7';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '预约活动列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PeopleLivelihoodSignVolunteerActivitiesList';
        $tmpMenu['path'] = '/community/streetCommunity/peopleLivelihood/signVolunteerActivitiesList';
        $returnArr[] = $tmpMenu;

        // 民生管理-新闻管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_news_management';
        $tmpMenu['parentId'] = 'street_4';
        $tmpMenu['id'] = 'street_4_8';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '新闻管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/street_community.iframe/news_street_cate";
        // 民生管理-新闻管理-新闻分类
        $returnArr[] = $tmpMenu;
        $tmpMenu = [];
        $tmpMenu['name'] = 'news_street_cate';
        $tmpMenu['parentId'] = 'street_4_8';
        $tmpMenu['id'] = 'street_4_8_1';
        $tmpMenu['mlevel'] = 3;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '新闻分类',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/news_street_cate';
        $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewsSreet&a=cate&iframe=true';
        $tmpMenu['src'] = urlencode($src_url);
        $returnArr[] = $tmpMenu;
        // 民生管理-新闻管理-新闻列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'news_street_index';
        $tmpMenu['parentId'] = 'street_4_8';
        $tmpMenu['id'] = 'street_4_8_2';
        $tmpMenu['mlevel'] = 3;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '新闻列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/news_street_index';
        $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewsSreet&a=index&iframe=true';
        $tmpMenu['src'] = urlencode($src_url);
        $returnArr[] = $tmpMenu;
        // 民生管理-新闻管理-新闻评论列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'news_street_reply';
        $tmpMenu['parentId'] = 'street_4_8';
        $tmpMenu['id'] = 'street_4_8_3';
        $tmpMenu['mlevel'] = 3;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '新闻评论列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/community/street_community.iframe/news_street_reply';
        $src_url = cfg('site_url') . '/shequ.php?g=House&c=NewsSreet&a=reply&iframe=true';
        $tmpMenu['src'] = urlencode($src_url);
        $returnArr[] = $tmpMenu;
        $configCustomizationService=new ConfigCustomizationService();
        $isHangLanShequCustom=$configCustomizationService->getHangLanShequCustom();
        $grid_management_title='网格化管理';
        if ($isHangLanShequCustom==1){
            $grid_management_title='应急指挥';
        }
        // 网格化管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'street_grid_management';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_6';
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => $grid_management_title,
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['path'] = '/community/street_community.iframe/street_grid_custom';
        $tmpMenu['redirect'] = "/community/config/index";

        // 网格化管理-网格化管理
        $returnArr[] = $tmpMenu;
        $tmpMenu = [];
        $tmpMenu['name'] = 'GridManage';
        $tmpMenu['parentId'] = 'street_6';
        $tmpMenu['id'] = 'street_6_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '网格化管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'GridManage';
        $tmpMenu['path'] = '/community/streetCommunity/gridCustom/GridManage';
        $returnArr[] = $tmpMenu;
        // 网格员管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'GridCustomList';
        $tmpMenu['parentId'] = 'street_6';
        $tmpMenu['id'] = 'street_6_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '网格员管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'GridCustomList';
        $tmpMenu['path'] = "/community/streetCommunity/gridCustom/GridCustomList";
        $returnArr[] = $tmpMenu;

        // 事件分类管理
        if($adminUser['area_type'] != 1){
            $tmpMenu = [];
            $tmpMenu['name'] = 'GridEvent';
            $tmpMenu['parentId'] = 'street_6';
            $tmpMenu['id'] = 'street_6_3';
            $tmpMenu['mlevel'] = 2;
            $tmpMenu['is_dir'] = 0;
            $tmpMenu['meta'] = [
                'icon' => 'tasks',
                'title' => '事件分类管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'GridEvent';
            $tmpMenu['path'] = "/community/streetCommunity/gridCustom/GridEvent";
            $returnArr[] = $tmpMenu;
        }

        // 事件子分类管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'GridChildEvent';
        $tmpMenu['parentId'] = 'street_6';
        $tmpMenu['id'] = 'street_6_4';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '事件子分类管理',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'GridChildEvent';
        $tmpMenu['path'] = "/community/streetCommunity/gridCustom/GridChildEvent";
        $returnArr[] = $tmpMenu;

        // 网格事件中心
        $tmpMenu = [];
        $tmpMenu['name'] = 'GridEventCenter';
        $tmpMenu['parentId'] = 'street_6';
        $tmpMenu['id'] = 'street_6_5';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '网格事件中心',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'GridEventCenter';
        $tmpMenu['path'] = "/community/streetCommunity/gridCustom/GridEventCenter";
        $returnArr[] = $tmpMenu;

        // 智慧党建
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyWork';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_17';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '智慧党建',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/streetCommunity/partyWork";
        $returnArr[] = $tmpMenu;

        // 党务管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyWork';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党务管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/streetCommunity/partyWork";
        $returnArr[] = $tmpMenu;
        // 党务管理-党支部列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyWorkList';
        $tmpMenu['parentId'] = 'street_17_1';
        $tmpMenu['id'] = 'street_17_1_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党支部列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PartyWorkList';
        $tmpMenu['path'] = '/community/streetCommunity/partyWork/PartyWorkList';
        $returnArr[] = $tmpMenu;
        // 党员管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyMemberList';
        $tmpMenu['parentId'] = 'street_17_1';
        $tmpMenu['id'] = 'street_17_1_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党员管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PartyMemberList';
        $tmpMenu['path'] = '/community/streetCommunity/partyMember/partyMemberList';
        $returnArr[] = $tmpMenu;

        //智慧党建-三会一课
        $tmpMenu = [];
        $tmpMenu['name'] = 'ThreeLessonsList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '三会一课',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'ThreeLessonsList';
        $tmpMenu['path'] = '/community/streetCommunity/ThreeLessons/ThreeLessonsList';
        $returnArr[] = $tmpMenu;
        $tmpMenu = [];
        $tmpMenu['name'] = 'MeetingList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_3';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '会议列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'MeetingList';
        $tmpMenu['path'] = '/community/streetCommunity/ThreeLessons/MeetingList';
        $returnArr[] = $tmpMenu;
        //会议评论列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'CommentsList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_4';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '会议评论列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'CommentsList';
        $tmpMenu['path'] = '/community/streetCommunity/ThreeLessons/CommentsList';
        $returnArr[] = $tmpMenu;
        // 党内资讯
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyBuildCategoryLists';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_5';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党内资讯',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PartyBuildCategoryLists';
        $tmpMenu['path'] = "/community/streetCommunity/PartyBuild/CategoryList";
        $returnArr[] = $tmpMenu;

        $tmpMenu = [];
        $tmpMenu['name'] = 'NewsList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_5';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党内资讯',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'NewsList';
        $tmpMenu['path'] = "/community/streetCommunity/PartyBuild/NewsList";
        $returnArr[] = $tmpMenu;

        $tmpMenu = [];
        $tmpMenu['name'] = 'ReplyList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_6';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '评论列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'ReplyList';
        $tmpMenu['path'] = "/community/streetCommunity/PartyBuild/CommentsList";
        $returnArr[] = $tmpMenu;
        //党内活动管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'PartyActivityList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_7';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '党内活动管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'PartyActivityList';
        $tmpMenu['path'] = '/community/streetCommunity/PartyActivity/PartyActivityList';
        $returnArr[] = $tmpMenu;
        //党内活动报名列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'ActivityApplyList';
        $tmpMenu['parentId'] = 'street_17';
        $tmpMenu['id'] = 'street_17_8';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '活动报名列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'ActivityApplyList';
        $tmpMenu['path'] = '/community/streetCommunity/partyActivity/ActivityApplyList';
        $returnArr[] = $tmpMenu;
        // 网上政务
        $tmpMenu = [];
        $tmpMenu['name'] = 'PoliticsMatter';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_19';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '网上政务',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/streetCommunity/PoliticsMatter";
        $returnArr[] = $tmpMenu;
        // 事项材料分类管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'MatterClassifyList';
        $tmpMenu['parentId'] = 'street_19';
        $tmpMenu['id'] = 'street_19_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '事项材料管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'MatterClassifyList';
        $tmpMenu['path'] = "/community/streetCommunity/PoliticsMatter/MatterClassifyList";
        $returnArr[] = $tmpMenu;
        // 事项材料列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'MatterList';
        $tmpMenu['parentId'] = 'street_19';
        $tmpMenu['id'] = 'street_19_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '事项列表',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'MatterList';
        $tmpMenu['path'] = "/community/streetCommunity/PoliticsMatter/MatterList";
        $returnArr[] = $tmpMenu;
        // 首页
//        $indeArr['id'] = '999999';
//        $indeArr['fid'] = 0;
//        $indeArr['icon'] = '';
//        $indeArr['name'] = '首页';
//        $indeArr['module'] = 'Index';
//        $indeArr['action'] = 'main';
//        $returnArr[] = $this->getMenuField($indeArr,'Index');
//
//        foreach ($menuList as $key => $value) {
//            // 一级菜单
//            $tmpMenu = $this->getMenuField($value,'RouteView');
//
//            if (isset($value['menu_list']) && $value['menu_list']) {
//                // 二级菜单
//                $redirect = '';
//                $childArr = [];
//                foreach ($value['menu_list'] as $key => $_child) {
//                    $tmpChildMenu = $this->getMenuField($_child,'System');
//                    $childArr[] = $tmpChildMenu;
//                    if ($key == 0) {
//                        $redirect = $tmpChildMenu['path'];
//                    }
//
//                    // 三级菜单
//                    if (isset($_child['menu_list']) && $_child['menu_list']) {
//                        $childArr2 = [];
//                        foreach ($_child['menu_list'] as $key => $_child2) {
//                            $tmpChildMenu2 = $this->getMenuField($_child2,'System');
//                            $childArr2[] = $tmpChildMenu2;
//                        }
//                        $returnArr = array_merge($returnArr,$childArr2);
//                    }
//                }
//                $tmpMenu['redirect'] = $redirect; // 父级访问第一个子级
//                $returnArr[] = $tmpMenu;
//
//                $returnArr = array_merge($returnArr,$childArr);
//            }else{
//                $returnArr[] = $tmpMenu;
//            }
//        }
        $special_group_title='综治管理';
        if ($isHangLanShequCustom==1){
            $special_group_title='安防综合数据服务';
        }
        // 综治管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'SpecialGroup';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_18';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => $special_group_title,
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = "/community/streetCommunity/specialGroup";
        $returnArr[] = $tmpMenu;
        // 弱势困难群体管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'userVulnerableGroupsLists';
        $tmpMenu['parentId'] = 'street_18';
        $tmpMenu['id'] = 'street_18_1';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '关怀群体',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'userVulnerableGroupsLists';
        $tmpMenu['path'] = "/community/streetCommunity/specialGroup/userVulnerableGroupsLists";
        $returnArr[] = $tmpMenu;
        // 重要群体管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'importantGroupLists';
        $tmpMenu['parentId'] = 'street_18';
        $tmpMenu['id'] = 'street_18_2';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '重点人群管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'importantGroupLists';
        $tmpMenu['path'] = "/community/streetCommunity/specialGroup/importantGroupLists";
        $returnArr[] = $tmpMenu;
        // 特殊群体管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'specialGroupLists';
        $tmpMenu['parentId'] = 'street_18';
        $tmpMenu['id'] = 'street_18_3';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '特殊人群管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'specialGroupLists';
        $tmpMenu['path'] = "/community/streetCommunity/specialGroup/specialGroupLists";
        $returnArr[] = $tmpMenu;
        // 智慧养老
        $tmpMenu = [];
        $tmpMenu['name'] = 'smartPension';
        $tmpMenu['parentId'] = 'street_18';
        $tmpMenu['id'] = 'street_18_4';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '智慧养老',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'smartPension';
        $tmpMenu['path'] = "/community/streetCommunity/specialGroup/smartPension";
        $returnArr[] = $tmpMenu;
        // 跟踪记录
        $tmpMenu = [];
        $tmpMenu['name'] = 'groupRecord';
        $tmpMenu['parentId'] = 'street_18';
        $tmpMenu['id'] = 'street_18_5';
        $tmpMenu['mlevel'] = 2;
        $tmpMenu['is_dir'] = 0;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '跟踪记录',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'groupRecord';
        $tmpMenu['path'] = "/community/streetCommunity/specialGroup/groupRecord";
        $returnArr[] = $tmpMenu;


        // 任务下达
        $tmpMenu = [];
        $tmpMenu['name'] = 'taskRelease';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_20';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '任务下达',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'taskRelease';
        $tmpMenu['path'] = "/community/streetCommunity/taskRelease/list";
        $returnArr[] = $tmpMenu;

        // 疫情防控
        /*** 
        $tmpMenu = [];
        $tmpMenu['name'] = 'epidemicPrevent';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_21';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '疫情防控',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'epidemicPrevent';
        $tmpMenu['path'] = "/community/streetCommunity/epidemicPrevent/list";
        $returnArr[] = $tmpMenu;
        **/
        // 社区关怀
        $tmpMenu = [];
        $tmpMenu['name'] = 'communityCare';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'street_22';
        $tmpMenu['mlevel'] = 1;
        $tmpMenu['is_dir'] = 1;
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '社区关怀',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'communityCare';
        $tmpMenu['path'] = "/community/streetCommunity/communityCare/list";
        $returnArr[] = $tmpMenu;

        if($is_func_yougejianet_special){
            // 街道设置-服务管理 定制
            $tmpMenu = [];
            $tmpMenu['name'] = 'serviceCentreSpecialNavList';
            $tmpMenu['parentId'] = '0';
            $tmpMenu['id'] = 'street_23';
            $tmpMenu['mlevel'] = 1;
            $tmpMenu['is_dir'] = 1;
            $tmpMenu['meta'] = [
                'icon' => 'tasks',
                'title' => '服务中心',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'serviceCentreSpecialNavList';
            $tmpMenu['path'] = '/community/streetCommunity/functPage/serviceCentreSpecialNavList';
            $returnArr[] = $tmpMenu;
        }
        
        return $returnArr;
    }

    public function getStreetRolePermissionData($adminUser=[]){
        $newMenu=array();
        $streetMenu=$this->formartMenuList([],$adminUser);
        foreach($streetMenu as $sm){
            if($sm['name']==='street_index'){
                continue;
            }
            unset($sm['component'],$sm['path']);
            if($sm['meta']['show']){
                $sm['name']=$sm['meta']['title'];
                unset($sm['meta']);
                $newMenu[]=$sm;
            }
        }
        return $newMenu;
    }

    public function getTrees($array,$role_menus=[]){
        //第一步 构造数据
        $items = array();
        foreach($array as $value){
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if($value['parentId'] && isset($items[$value['parentId']])){
                $items[$value['parentId']]['child'][] = &$items[$key];
                if (isset($items[$value['parentId']]['count'])) {
                    $items[$value['parentId']]['count']++;
                }else{
                    $items[$value['parentId']]['count'] = 1;
                }
            } else{
                $tree[] = &$items[$key];
            }
        }
        $ckeyArr=array();
        foreach ($tree as $kk => $vv) {
            $tree[$kk]['ckey'] = 'key_0_' . $vv['id'];
            $tmpCv=false;
            if(in_array($vv['id'],$role_menus)){
                $tmpCv=true;
            }
            $ckeyArr[]=array('ckey'=>'key_0_' . $vv['id'],'cv'=>$tmpCv,'name'=>$vv['name'],'id'=>$vv['id']);
            if (isset($vv['child']) && !empty($vv['child'])) {
                foreach ($vv['child'] as $kk1 => $vv1) {
                    $tree[$kk]['child'][$kk1]['ckey'] = 'key_1_' . $vv1['id'];
                    $tmpCv=false;
                    if(in_array($vv1['id'],$role_menus)){
                        $tmpCv=true;
                    }
                    $ckeyArr[]=array('ckey'=>'key_1_' . $vv1['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'name'=>$vv1['name'],'id'=>$vv1['id']);
                    if (isset($vv1['child']) && !empty($vv1['child'])) {
                        foreach ($vv1['child'] as $kk2 => $vv2) {
                            $tree[$kk]['child'][$kk1]['child'][$kk2]['ckey'] = 'key_2_' . $vv2['id'];
                            $tmpCv=false;
                            if(in_array($vv2['id'],$role_menus)){
                                $tmpCv=true;
                            }
                            $ckeyArr[]=array('ckey'=>'key_2_' . $vv2['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'name'=>$vv2['name'],'id'=>$vv2['id']);
                            if (isset($vv2['child']) && !empty($vv2['child'])) {
                                foreach ($vv2['child'] as $kk3 => $vv3) {
                                    $tree[$kk]['child'][$kk1]['child'][$kk2]['child'][$kk3]['ckey'] = 'key_3_' . $vv3['id'];
                                    $tmpCv=false;
                                    if(in_array($vv3['id'],$role_menus)){
                                        $tmpCv=true;
                                    }
                                    $ckeyArr[]=array('ckey'=>'key_3_' . $vv3['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'ckey2'=>'key_2_' . $vv2['id'],'name'=>$vv3['name'],'id'=>$vv3['id']);
                                    if (isset($vv3['child']) && !empty($vv3['child'])) {
                                        foreach ($vv3['child'] as $kk4 => $vv4) {
                                            $tree[$kk]['child'][$kk1]['child'][$kk2]['child'][$kk3]['child'][$kk4]['ckey'] = 'key_4_' . $vv4['id'];
                                            $tmpCv=false;
                                            if(in_array($vv4['id'],$role_menus)){
                                                $tmpCv=true;
                                            }
                                            $ckeyArr[]=array('ckey'=>'key_4_' . $vv4['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'ckey2'=>'key_2_' . $vv2['id'],'ckey3'=>'key_3_' . $vv3['id'],'name'=>$vv4['name'],'id'=>$vv4['id']);
                                        }
                                    }

                                }

                            }

                        }

                    }
                }
            }
        }
        return array('tree'=>$tree,'ckeyArr'=>$ckeyArr);
    }
}