<?php


namespace app\community\controller\street_api;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseAdverCategoryService;
use app\community\model\service\AreaStreetNewsService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseAdverService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PartyMemberService;
use app\community\model\service\StreetNavService;
use app\community\model\service\HouseVillageUserLabelService;
use app\community\model\service\PartyBranchService;
use app\community\model\service\VisualizationService;
class HomePageController extends CommunityBaseController
{

    /**
     * 接到首页
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/09/10
     */
    public function index()
    {
        $street_id = $this->request->post('area_street_id', 0);
//        $community_id = $this->request->post('area_community_id', 0); // 社区id
//        if (!$street_id && $community_id) {
//            $street_id = $community_id;
//        }
        if (!$street_id) {
            return api_output_error(1001, '缺少必传参数');
        }
        $site_url = cfg('site_url');
        $base_url = '/packapp/street/pages/street';
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $tmp_street_id = $street_id;
        $street_info = $service_area_street->getAreaStreet($where_street);
        if ($street_info && $street_info['area_type'] == 1) {
            $data['title'] = '社区';
            $bind_type = 1;
            $tmp_street_id = $street_info['area_pid'];
        } elseif ($street_info) {
            $data['title'] = '街道';
            $bind_type = 0;
        } else {
            return api_output_error(1001, '对应街道/社区不存在');
        }
        if (isset($street_info['long']) && $street_info['long']) {
            $data['long'] = $street_info['long'];
        }
        if (isset($street_info['lat']) && $street_info['lat']) {
            $data['lat'] = $street_info['lat'];
        }
        if (isset($street_info['logo']) && $street_info['logo'])
            $data['logo'] = dispose_url($street_info['logo']);
        else
            $data['logo'] = '';
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 20);
        //-----------------------------------首页Nav----------------------------------------
        $street_nav_service = new StreetNavService();
        $nav_lists = $street_nav_service->getList($street_id, 0);
        if ($nav_lists['list'] && $nav_lists['count'] > 0) {
            if (count($nav_lists['list']) > 8) {
                $nav = [];
                $list = [];
                foreach ($nav_lists['list'] as $k => &$v) {
                    $v['img'] = dispose_url($v['img']);
                    if (count($nav) == 8) {
                        $list[] = $nav;
                        $nav = [];
                    }
                    $nav[] = $v;

                }
                $list[] = $nav;
                $data['nav'] = isset($list) ? $list : [];
            } else {
                foreach ($nav_lists['list'] as $k => &$v) {
                    $v['img'] = dispose_url($v['img']);
                }
                $list = $nav_lists['list'];
                $data['nav'][] = isset($list) ? $list : [];
            }
        } else {
            $data['nav'] = [];
        }
        $partyBranchService = new PartyBranchService();
        $partyBranch = $partyBranchService->getPartyBranchAll($tmp_street_id);
        $party_ids = array();
        if ($partyBranch && !$partyBranch->isEmpty()) {
            foreach ($partyBranch as $pvv) {
                $party_ids[] = $pvv['id'];
            }
        }
        //-----------------------------------街道统计----------------------------------------
        $service_area_street = new AreaStreetService();
        $house_village_user_bind_count = $service_area_street->getStreetVillageUserNum([['a.area_id', '=', $street_id], ['c.status', 'in', [0, 1]], ['c.type', 'in', [0, 1, 3]]], $bind_type);//街道下居民数
        $area_community_count = $service_area_street->getStreetCommunityNum(['area_pid' => $street_id]);//街道下社区数量
        $house_village_count = $service_area_street->getStreetVillageNum(['a.area_id' => $street_id, 'b.status' => 1], $bind_type);//街道下小区数量
        $service_house_village_user_label = new HouseVillageUserLabelService();
        if ($bind_type) {
            $whereArr = [['v.community_id', '=', $street_id], ['l.user_political_affiliation', '=', 1], ['b.status', '<>', 4]];
            if ($party_ids) {
                $whereArr[] = ['pbu.party_id', 'in', $party_ids];
            }
            $house_party_member_count = $service_house_village_user_label->getCount($whereArr);//街道党员数量
        } else {
            $whereArr = [['v.street_id', '=', $street_id], ['l.user_political_affiliation', '=', 1], ['b.status', '<>', 4]];
            if ($party_ids) {
                $whereArr[] = ['pbu.party_id', 'in', $party_ids];
            }
            $house_party_member_count = $service_house_village_user_label->getCount($whereArr);//街道党员数量
        }
        $partyMemberService= new PartyMemberService();

        $countWorker=$partyMemberService->getWorkerMemberListCount($tmp_street_id);
        $house_party_member_count=$house_party_member_count+$countWorker;
        $datalist = [];
        if ($street_info && $street_info['area_type'] != 1) {
            $datalist[] = ['title' => '社区数', 'num' => $area_community_count, 'url' => get_base_url('pages/street/index/communityList?area_street_id=' . $street_id)];
        }
        $datalist[] = ['title' => ((new HouseVillageService())->getHouseAlias('house_name') ?: '小区') . '数', 'num' => $house_village_count, 'url' => get_base_url('pages/street/index/villageList?area_street_id=' . $street_id)];
        $datalist[] = ['title' => '居民数', 'num' => $house_village_user_bind_count];
        $datalist[] = ['title' => '党员数', 'num' => $house_party_member_count, 'url' => get_base_url('pages/street/index/partyList?area_street_id=' . $street_id . '&tab=0')];
        $data['street_Statistics']['datalist'] = $datalist;

        //-----------------------------------新闻----------------------------------------
        $area_street_news_service = new AreaStreetNewsService();
        $field = 'cat_id,cat_name';
        $news_category_lists = $area_street_news_service->getNewsCategoryLists(['area_id' => $street_id, 'cat_status' => 1], $field, 'cat_sort DESC')->toArray();
        if ($news_category_lists) {
            array_unshift($news_category_lists, ['cat_id' => 0, 'cat_name' => '全部']);
            $data['news_list']['news_tab'] = $news_category_lists;
        } else {
            $data['news_list']['news_tab'] = [];
        }
        /*$cat_id = $this->request->post('cat_id',0);
        $area_street_news_service = new AreaStreetNewsService();
        if($cat_id)
            $where['cat_id'] = $cat_id;
        else
            $where = array();
        $where['status'] = 1;
        $field = 'title,add_time,title_img,news_id';
        $news_list = $area_street_news_service->getNewsLists($where,$field,$page,$limit,'is_hot DESC,news_id DESC');
        $data['news_list']['news_lists'] = $news_list;*/

        //-----------------------------------街道要闻----------------------------------------
        $im_news = $area_street_news_service->getOne(['area_id' => $street_id, 'is_important_notice' => 1, 'status' => 1], 'news_id,title');
        if (!$im_news) {
            $im_news = (object)[];
        }
        $data['im_news'] = $im_news;

        //-----------------------------------街道社区列表----------------------------------------
        $area_community_lists = $service_area_street->getStreetLists(['area_pid' => $street_id], 'area_name,area_id,logo', $page, $limit);
        $data['street_info'] = $area_community_lists;

        //-----------------------------------优秀党员---------------------------------------------
        $where = [];

        if ($street_info && $street_info['area_type'] == 1) {
            $where[] = ['v.community_id', '=', $street_id];
        } else {
            $where[] = ['v.street_id', '=', $street_id];
        }
        if ($party_ids) {
            $where[] = ['pbu.party_id', 'in', $party_ids];
        }
        $where[] = ['l.user_political_affiliation', '=', 1];
        $where[] = ['l.is_excellent', '=', 1];
        $where[] = ['b.status', '<>', 4];
        $part_member_lists = $service_house_village_user_label->getPartyMemberLists($where, 'l.logo,b.name,l.part_time,p.name as party_branch_name', 1, 2, 'l.id DESC');
        $data['partyMember'] = $part_member_lists;

        //-----------------------------------首页中间轮播----------------------------------------
        $service_house_adver = new HouseAdverService();
        $service_house_adver_category = new HouseAdverCategoryService();
        $res = $service_house_adver_category->getOne(['cat_key' => 'street_app_index_center'], 'cat_id');
        if (empty($res)) {
            $adver_lists = array();
        } else {
            $where_adver = [];
            $where_adver[] = ['status', '=', 1];
            $where_adver[] = ['cat_id', '=', $res['cat_id']];
            if ($street_info && $street_info['area_type'] == 1) {
                $where_adver[] = ['community_id', '=', $street_id];
                $adver_lists = $service_house_adver->getAdverLists($where_adver, 'pic,url,bg_color,name', 'sort DESC');
            } else {
                $where_adver[] = ['street_id', '=', $street_id];
                $adver_lists = $service_house_adver->getAdverLists($where_adver, 'pic,url,bg_color,name', 'sort DESC');
            }
            if ($adver_lists) {
                foreach ($adver_lists as $k => $v) {
                    if ($v['pic']) {
                        $adver_lists[$k]['pic'] = dispose_url($v['pic']);
                    }
                }
            }
        }
        $data['index_center_img'] = $adver_lists;

        //-----------------------------------四图广告----------------------------------------
        $res = $service_house_adver_category->getOne(['cat_key' => 'street_four_adver'], 'cat_id');
        if (empty($res)) {
            $adver_lists = array();
        } else {
            if ($street_info && $street_info['area_type'] == 1) {
                $adver_lists = $service_house_adver->getAdverLists([['community_id', '=', $street_id], ['status', '=', 1], ['cat_id', '=', $res['cat_id']]], 'pic,url,bg_color,name', 'sort DESC');
            } else {
                $adver_lists = $service_house_adver->getAdverLists([['street_id', '=', $street_id], ['status', '=', 1], ['cat_id', '=', $res['cat_id']]], 'pic,url,bg_color,name', 'sort DESC');
            }
            if ($adver_lists) {
                foreach ($adver_lists as $k => $v) {
                    if ($v['pic']) {
                        $adver_lists[$k]['pic'] = dispose_url($v['pic']);
                    }
                }
            }
        }
        $data['four_img'] = $adver_lists;

        //-----------------------------------首页顶部轮播----------------------------------------
        $res = $service_house_adver_category->getOne(['cat_key' => 'street_app_index_top'], 'cat_id');
        if (empty($res)) {
            $lists = array();
        } else {
            if ($street_info && $street_info['area_type'] == 1) {
                $lists = $service_house_adver->getAdverLists([['community_id', '=', $street_id], ['status', '=', 1], ['cat_id', '=', $res['cat_id']], ['pic', '<>', '']], 'pic,url,bg_color,name', 'sort DESC');
            } else {
                $lists = $service_house_adver->getAdverLists([['street_id', '=', $street_id], ['status', '=', 1], ['cat_id', '=', $res['cat_id']], ['pic', '<>', '']], 'pic,url,bg_color,name', 'sort DESC');
            }
            if ($lists) {
                foreach ($lists as $k => $v) {
                    if ($v['pic']) {
                        $lists[$k]['pic'] = dispose_url($v['pic']);
                        $lists[$k]['pic'] = str_replace("\\", '/', $lists[$k]['pic']);
                    }
                }
            }
        }
        $data['is_have_bulletin']=0;
        $data['bulletin_info']=array('xtitle'=>'','id'=>0 ,'add_time'=>'');
        $configCustomizationService=new ConfigCustomizationService();
        $is_func_yougejianet_special= $configCustomizationService->getfuncYougejianetSpecial();
        if($is_func_yougejianet_special){
            $data['is_have_bulletin']=1;
            $visualizationService =new VisualizationService();
            $whereArr=array(['xtype','=','bulletin_info'],['status','=',1]);
            if($street_info && $street_info['area_type']==1){
                $whereArr[]=array('community_id','=',$street_id);
                $whereArr[]=array('street_type','=',1);   //社区
            }else if($street_info){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('street_type','=',0);   //街道
            }
            $bulletin_info=$visualizationService->getOneStreetInfoContent($whereArr,'*','xsort desc,id desc');
            if($bulletin_info){
                $data['bulletin_info']['xtitle']=$bulletin_info['xtitle'];
                $data['bulletin_info']['id']=$bulletin_info['id'];
                $data['bulletin_info']['add_time']=date('m-d',$bulletin_info['add_time']);
            }
        }

        $data['top_swiper'] = $lists;
        $data['top_title'] = $street_info['area_name'];
        $data['share_info'] = [
            'share_switch' => intval(cfg('share_switch')),
            'share_img' => cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx' => cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName' => cfg('pay_wxapp_username'),
            'title' => '欢迎来到【' . $data['top_title'] . '】',
            'info' => '小区演示推出最新智慧社区平台，邀你一起来体验。'
        ];
        return api_output(0, $data);
    }

    /**
     * 街道首页nav
     * @return \json
     * @author lijie
     * @date_time 2020/09/10
     */
    public function getStreetNav()
    {
        $street_id = $this->request->post('area_street_id', 0);
        if (!$street_id)
            return api_output_error(1001, '缺少必传参数');
        $street_nav_service = new StreetNavService();
        $data = $street_nav_service->getList($street_id);
        return api_output(0, $data);
    }

    public function getStreetFooterNav()
    {
        $street_id = $this->request->post('area_street_id', 0);
        if (!$street_id) {
            // return api_output_error(1001,'缺少必传参数');
        }
        $street_nav_service = new StreetNavService();
        $data = $street_nav_service->getStreetFooterNavs($street_id);
        return api_output(0, $data);
    }

    /**
     * 街道新闻分类
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/09/09
     */
    public function getNewsCategoryLists()
    {
        $street_id = $this->request->post('area_street_id', 0);
        if (!$street_id)
            return api_output_error(1001, '缺少必传参数');
        $area_street_news_service = new AreaStreetNewsService();
        $where['area_id'] = $street_id;
        $where['cat_status'] = 1;
        $field = 'cat_id,cat_name';
        $data = $area_street_news_service->getNewsCategoryLists($where, $field, 'cat_sort DESC');
        return api_output(0, $data);
    }

    /**
     * 新闻列表
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/09/09
     */
    public function getNewsLists()
    {
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 20);
        $cat_id = $this->request->post('cat_id', 0);
        $street_id = $this->request->post('area_street_id', 0);
        $area_street_news_service = new AreaStreetNewsService();
        if ($cat_id)
            $where['cat_id'] = $cat_id;
        if ($street_id)
            $where['area_id'] = $street_id;
        $where['status'] = 1;
        $field = 'title,add_time,cat_id,title_img,news_id,read_sum';
        $data = $area_street_news_service->getNewsLists($where, $field, $page, $limit, 'is_hot DESC,news_id DESC');
        return api_output(0, $data);
    }

    /**
     *新闻评论
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/09/09
     */
    public function getNewsReply()
    {
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 20);
        $news_id = $this->request->post('news_id', 0);
        if (!$news_id)
            return api_output_error(1001, '缺少必传参数');
        $area_street_news_service = new AreaStreetNewsService();
        $where['r.news_id'] = $news_id;
        $field = 'r.content,r.add_time,u.nickname,u.avatar';
        $data = $area_street_news_service->getNewsReplyLists($where, $field, $page, $limit, 'r.pigcms_id DESC');
        return api_output(0, $data);
    }

    public function getStreetsList()
    {
        $street_id = $this->request->post('area_street_id', 0, 'intval');
        $street_name = $this->request->post('street_name', '', 'trim');
        try {
            $areaStreetService = new AreaStreetService();
            $whereArr = array(['area_id', '>', 0], ['area_type', '=', 1], ['is_open', '=', 1]);
            if ($street_name) {
                $whereArr[] = array(['street_name', 'like', '%' . $street_name . '%']);
            }
            $streetList = $areaStreetService->getStreetLists($whereArr, 'area_id,area_pid,area_name,`long`,`lat`,address,detail_address,logo', 1, 20);
            $data = array('list' => array(), 'current_street' => array('area_street_id' => $street_id));
            if ($streetList) {
                $data['list'] = $streetList;
            }
            if ($street_id > 0) {
                $whereArr = array(['area_id', '=', $street_id], ['area_type', '=', 1], ['is_open', '=', 1]);
                $current_street = $areaStreetService->getAreaStreet($whereArr, 'area_id,area_pid,area_name,`long`,`lat`,address,detail_address,logo');
                $data['current_street'] = $current_street;
                $data['current_street']['area_street_id'] = $street_id;
            }
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getStreetServiceNavCatList()
    {
        $street_id = $this->request->post('area_street_id', 0, 'intval');
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $page = $this->request->post('page',1,'intval');
        $cat_name = $this->request->post('cat_name','','trim');
        $page=$page>0 ? $page:1;
        $limit = 10;
        $pigcms_id = $this->request->post('pigcms_id', 0, 'intval');  //住户身份id
        try {
            $serviceStreetNav = new StreetNavService();
            $areaStreetService = new AreaStreetService();
            $whereArr = array(['area_id', '=', $street_id], ['is_open', '=', 1]);
            $current_street = $areaStreetService->getAreaStreet($whereArr, 'area_id,area_pid,area_type,area_name,`long`,`lat`,address,detail_address,logo');
            if (empty($current_street)) {
                return api_output_error(1001, '街道社区信息不存在！');
            }
            $whereArr=array();
            if($current_street['area_type']==1){
                $whereArr[]=array('community_id','=',$current_street['area_id']);
                $whereArr[]=array('cat_type','=',1);   //社区
            }else if($current_street['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('cat_type','=',0);   //街道
            }
            $whereArr[]=array('del_time','<',10);
            if($cat_name){
                $whereArr[]=array('cat_name','like','%'.$cat_name.'%');
            }
            $listData = $serviceStreetNav->getStreetServiceNavCatList($whereArr,'*','xsort desc,id DESC',$page,$limit,$pigcms_id);
            return api_output(0, $listData);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getStreetServiceNavList()
    {
        $street_id = $this->request->post('area_street_id', 0, 'intval');
        $page = $this->request->post('page', 1, 'intval');
        $nav_name = $this->request->post('nav_name', '', 'trim');
        $pigcms_id = $this->request->post('pigcms_id', 0, 'intval');  //住户身份id
        $cat_id = $this->request->post('cat_id',0,'intval');
        $page = $page > 0 ? $page : 1;
        $limit = 10;
        if ($street_id < 1) {
            return api_output_error(1001, '缺少街道ID参数！');
        }
        try {
            $serviceStreetNav = new StreetNavService();
            $areaStreetService = new AreaStreetService();
            $whereArr = array(['area_id', '=', $street_id], ['is_open', '=', 1]);
            $current_street = $areaStreetService->getAreaStreet($whereArr, 'area_id,area_pid,area_type,area_name,`long`,`lat`,address,detail_address,logo');
            if (empty($current_street)) {
                return api_output_error(1001, '社区信息不存在！');
            }

            $whereArr = array();
            if ($current_street['area_type'] == 1) {
                $whereArr[] = array('community_id', '=', $current_street['area_id']);
                $whereArr[] = array('nav_type', '=', 1);   //社区
            } else if ($current_street['area_type'] == 0) {
                $whereArr[] = array('street_id', '=', $current_street['area_id']);
                $whereArr[] = array('community_id', '=', 0);
                $whereArr[] = array('nav_type', '=', 0);   //街道
            }
            $whereArr[] = array('xstatus', '=', 1);
            if ($nav_name) {
                $whereArr[] = array('nav_name', 'like', '%' . $nav_name . '%');
            }
            $catInfo=array();
            $cat_name='';
            if($cat_id>0){
                $whereCatArr=array(['id','=',$cat_id]);
                if($current_street['area_type']==1){
                    $whereCatArr[]=array('community_id','=',$current_street['area_id']);
                    $whereCatArr[]=array('cat_type','=',1);   //社区
                }else if($current_street['area_type']==0){
                    $whereCatArr[]=array('street_id','=',$street_id);
                    $whereCatArr[]=array('community_id','=',0);
                    $whereCatArr[]=array('cat_type','=',0);   //街道
                }
                $whereCatArr[]=array('del_time','<',10);
                $field='id,cat_type,cat_name,level_show,xstatus,xsort';
                $catInfo = $serviceStreetNav->getOneStreetServiceNavCat($whereCatArr,$field,'id DESC');
                if(empty($catInfo)){
                    return api_output_error(1001, '分类数据不存在！');
                }
                if($catInfo['xstatus']<1){
                    return api_output_error(1001, '分类已关闭访问！');
                }
                $cat_name=$catInfo['cat_name'];
                $whereArr[]=array('cat_id','=',$cat_id);
                $whereArr[]=array('xtype','=',1);
            }else{
                $whereArr[]=array('cat_id','=',0);
                $whereArr[]=array('xtype','=',0);
            }
            $fieldStr = 'id,street_id,community_id,nav_type,nav_name,nav_url,img_url,level_show,xsort,add_time,cat_id,xtype';
            $listData = $serviceStreetNav->getStreetServiceNavList($whereArr, $fieldStr, 'xsort desc,id DESC', $page, $limit, $pigcms_id);
            if(!empty($catInfo) && $catInfo['level_show']==1 && $listData['is_party_member_g']!=1){
                return api_output_error(1001, '此功能仅对党员用户开放，请联系平台！');
            }
            $listData['cat_id']=$cat_id;
            $listData['cat_name']=$cat_name;
            return api_output(0, $listData);
        } catch (\Exception  $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getStreetServiceNavInfo(){
        $street_id = $this->request->post('area_street_id', 0, 'intval');
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        $pigcms_id = $this->request->post('pigcms_id', 0, 'intval');  //住户身份id
        $cat_id = $this->request->post('cat_id',0,'intval');
        $serviceStreetNav = new StreetNavService();
        try {
            $areaStreetService = new AreaStreetService();
            $whereArr = array(['area_id', '=', $street_id], ['is_open', '=', 1]);
            $current_street = $areaStreetService->getAreaStreet($whereArr, 'area_id,area_pid,area_type,area_name,`long`,`lat`,address,detail_address,logo');
            if (empty($current_street)) {
                return api_output_error(1001, '社区信息不存在！');
            }
            $whereArr=array(['id','=',$idd]);
            if($current_street['area_type']==1){
                $whereArr[]=array('community_id','=',$current_street['area_id']);
                $whereArr[]=array('nav_type','=',1);   //社区
            }else if($current_street['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('nav_type','=',0);   //街道
            }
            if($cat_id>0){
                $whereArr[]=array('cat_id','=',$cat_id);
                $whereArr[]=array('xtype','=',1);
            }else{
                $whereArr[]=array('cat_id','=',0);
                $whereArr[]=array('xtype','=',0);
            }
            $whereArr[]=array('nav_url','=',"");
            $dataInfo = $serviceStreetNav->getOneStreetServiceNav($whereArr,'*','id DESC');
            if(empty($dataInfo)){
                return api_output_error(1001,'记录数据不存在！');
            }
            $returnArr=array('detail'=>$dataInfo);
            return api_output(0, $returnArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getStreetBulletinList()
    {
        $street_id = $this->request->post('area_street_id', 0);
        if(!$street_id){
            return api_output_error(1001,'街道社区参数错误！');
        }
        $page = $this->request->post('page',1,'intval');
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if(empty($street_info)){
            return api_output_error(1001,'街道社区信息不存在！');
        }
        $page=$page>0 ? $page:1;
        $limit = 10;
        $visualizationServiceNav = new VisualizationService();
        try {
            $whereArr=array(['xtype','=','bulletin_info']);
            if($street_info['area_type']==1){
                $whereArr[]=array('community_id','=',$street_info['area_id']);
                $whereArr[]=array('street_type','=',1);   //社区
            }else if($street_info['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('street_type','=',0);   //街道
            }
            
            $whereArr[]=array('status','=',1);
            
            $field='id,street_id,community_id,street_type,xtype,xtitle,status,xsort,view_num,cover_img,add_time,update_time';
            $listData = $visualizationServiceNav->getStreetInfoContentList($whereArr,$field,'xsort desc,id DESC',$page,$limit,true);
            return api_output(0, $listData);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getStreetBulletinDetail(){
        $street_id = $this->request->post('area_street_id', 0);
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if(empty($street_info)){
            return api_output_error(1001,'街道社区信息不存在！');
        }
        $visualizationServiceNav = new VisualizationService();
        try {
            $whereArr=array(['id','=',$idd],['xtype','=','bulletin_info']);
            if($street_info['area_type']==1){
                $whereArr[]=array('community_id','=',$street_info['area_id']);
                $whereArr[]=array('street_type','=',1);   //社区
            }else if($street_info['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('street_type','=',0);   //街道
            }
            $whereArr[]=array('status','=',1);
            $dataInfo = $visualizationServiceNav->getOneStreetInfoContent($whereArr,'*','id DESC');
            if(empty($dataInfo)){
                return api_output_error(1001,'详情数据不存在！');
            }
            $dataInfo['add_time_str']=date('m-d H:i',$dataInfo['add_time']);
            $returnArr=array('detail'=>$dataInfo);
            return api_output(0, $returnArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}