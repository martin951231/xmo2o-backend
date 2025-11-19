<?php

/*
 * 小区O2O功能
 *
 */

use app\community\model\db\PackageOrder;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingPersonMer;
class HouseAction extends BaseAction
{
    use customization;
    private $open_door_status = array(0 => '开门成功', 1 => '扫描失败', 2 => '连接失败', 3 => '重连失败', 4 => '获取不到蓝牙关键字');
    private $lm_sex = array(0 => '不限', 1 => '男性', 2 => '女性');
    protected $jsdhwy_new_wap_index = 0;
    protected $guess_you_like_goods = 0;
    public $is_visualize=0;
    public function __construct(){
        parent::__construct();
        $action_name=strtolower(ACTION_NAME);
        /**
         * todo 社区可视化页面开关状态
         */
        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        if(in_array($action_name,['house_adver_list','house_nav_list','house_middle_advert_list','house_open_advert_list'])){
            if(!isset($_GET['source'])){
                if (intval($switch) > 0){
                    die('小区广告管理关闭');
                }
            }
        }
        $this->is_visualize=$switch;

        // 营山定制开关
        $this->huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$this->huizhisq);
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        $this->jsdhwy_new_wap_index=$jsdhwy_new_wap_index;
        $this->assign('jsdhwy_new_wap_index',$jsdhwy_new_wap_index);
        $this->guess_you_like_goods=D('Config_customization')->getGuessYouLikeGoodsCustomized();
        $this->assign('guess_you_like_goods',$this->guess_you_like_goods);
    }

    public function config()
    {
        redirect(U('Config/index', array('galias' => 'house', 'header' => 'House/header')));
    }

    public function village()
    {
        $order_string = '`village_sort` DESC,`t_sort` ASC, `village_id` DESC';

        if ($_GET['sort']) {
            switch ($_GET['sort']) {

                case 'lastTime':
                    $order_string =$order_string. ',`last_time` DESC';
                    $order_string = $order_string . ',`last_time` DESC';
                    break;

            }
        }
        if ($_GET['sort'] == 'lastTime') {
            $style = "border: solid blue;border-width: 0 3px 3px 0;display: inline-block;padding: 3px;transform: rotate(45deg);color:blue";
            $this->assign('style', $style);
        }
        //搜索
        $whereHouseProperty = [];
        $whereHouseAdmin = [];
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['a.village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'village_name') {
                $condition_house_village['a.village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_name') {
                $whereHouseProperty['property_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_phone') {
                $whereHouseProperty['property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'account') {
                $whereHouseAdmin['account'] = $_GET['keyword'];
            }
        }
        if (!empty($whereHouseProperty)) {
            $propertyIds = M('House_property')->where($whereHouseProperty)->getField('id', true);
            $condition_house_village['a.property_id'] = ['in', $propertyIds];
        }
        if (!empty($whereHouseAdmin)) {
            $villageIds = M('House_admin')->where($whereHouseAdmin)->getField('village_id', true);
            $condition_house_village['a.village_id'] = ['in', $villageIds];
        }

        if ($_GET['property_id']) {
            $condition_house_village['a.property_id'] = $_GET['property_id'];
        }
        /*
        if ($condition_house_village) {
            $count = 10000;
        } else {
            $count = 30;
        }
        */
        $count = 30;
        $condition_house_village['a.status'] = array('neq', 5);

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }

        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['a.city_id'] = $where['city_id'] = $_GET['city_idss'];
        } else {
            if ($this->config['many_city'] && $_GET['province_idss']) {
                $condition_house_village['a.province_id'] = $where['province_id'] = $_GET['province_idss'];
            }
        }
        if ($_GET['area_id']) {
            $condition_house_village['a.area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['a.street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['a.community_id'] = $where['community_id'] = $_GET['community_idss'];
        }

        $database_house_village = D('House_village');
        $count_village = $database_house_village
            ->join(" as a   left JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")
            ->where($condition_house_village)->count();

        import('@.ORG.system_page');
        $p = new Page($count_village, $count);

        //$field = "*,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        //todo:添加小区排序
        $field = "a.*,b.village_sort,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $village_list = $database_house_village->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->field($field)->where($condition_house_village)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

        $is_demo_domain = $this->config['is_demo_domain'] ? $this->config['is_demo_domain'] : 0;
        $is_demo_domain = intval($is_demo_domain);
        if (!empty($village_list)) {
            $villageIds = array_unique(array_column($village_list,'village_id'));
            $db_house_community_about = D('House_community_about');
            $where = [
                'village_id' => ['in', $villageIds],
                'is_virtual' => 1,
            ];
            $aboutInfoArr = $db_house_community_about->where($where)->getField('village_id, facilities_type, from');
            
            $db_house_property = M('House_property');
            $house_admin = M('House_admin');
            //$propertyIds = array_values(array_unique(array_column($village_list, 'property_id')));
           // $whereProperty = array('id'=>array('in', $propertyIds));
           // $propertyInfoArr = $db_house_property->where($whereProperty)->getField('id,property_name, is_saas_user', true);
            foreach ($village_list as &$val) {
                $property_id = isset($val['property_id']) ? intval($val['property_id']) : 0;
                $propertyInfo=array();
                if($property_id>0){
                    $propertyInfo=$db_house_property->where(array('id'=>$property_id))->field('*')->find();
                }
                $property_name = $propertyInfo && !empty($propertyInfo['property_name']) ? trim($propertyInfo['property_name']) : '';
                if ($is_demo_domain == 1) {
                    $is_saas_user = $propertyInfo && isset($propertyInfo['is_saas_user']) ? intval($propertyInfo['is_saas_user']) : 0;
                } else {
                    $is_saas_user = 0;
                }
                if ($property_name) {
                    $val['property_name'] = $property_name;
                } else {
                    $val['property_name'] = '';
                }
                $val['is_saas_user'] = $is_saas_user;
                if ($val['village_id']) {
                    $last_time = $house_admin->where(['village_id'=>$val['village_id']])->order('last_time DESC')->getField('last_time');
                    if (intval($last_time)>intval($val['last_time'])) {
                        $val['last_time'] = $last_time;
                    }
                }
                $val['is_del'] = 1;
                if (isset($aboutInfoArr[$val['village_id']]) && $aboutInfoArr[$val['village_id']]) {
                    $facilities_type = $aboutInfoArr[$val['village_id']]['facilities_type'];
                    $from = $aboutInfoArr[$val['village_id']]['from'];
                    $val['facilities_type_text'] = $db_house_community_about->getFacilitiesTypeText($facilities_type);
                    if ($from == 'system') {
                        $val['is_del'] = 0;
                    }
                }
            }
        }

        $this->assign('village_list', $village_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $app_key = $this->config['hik_info_vision_i_community_app_key'];
        $app_secret = $this->config['hik_info_vision_i_community_app_secret'];
        $is_get_hik_info_vision_i_community=0;
        if(!empty($app_key) && !empty($app_secret)){
            $is_get_hik_info_vision_i_community=1;
        }
        $this->assign('is_get_hik_info_vision_i_community', $is_get_hik_info_vision_i_community);
        $this->display();
    }
    public function get_hik_info_community(){
        set_time_limit(0);
        $retTmp=invoke_v20_service('app\community\model\service\Device\HikInfoVisionICommunityService/getICommunityRegions');
        if($retTmp && isset($retTmp['data']['error_no']) && $retTmp['data']['error_no']==0){
            $this->ajaxReturn(array('err_code'=>0,'msg'=>'数据拉取完成！','data'=>$retTmp));
        }else{
            $this->ajaxReturn(array('err_code'=>1,'msg'=>'数据拉取出错了，请检查对接配置数据是否完整并正确！'));
        }
    }
    //小区短信批量添加
    public function sms_add_all()
    {
        if (IS_POST) {
            $add_a = M('House_village')->where(array())->setInc('now_sms_number', intval($_POST['sms_number']));
            if ($add_a) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $this->display();
        }
    }

    //todo 平台新闻
    public function platform_news()
    {
        $news_list = D('House_village_news')->getlist([], 0);
        $this->assign('news_list', $news_list['news_list']);
        $this->assign('pagebar', $news_list['pagebar']);
        $this->display();
    }

    //todo 平台添加新闻
    public function platform_news_add()
    {
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['title']) || empty($post['title'])) {
                $this->error('请输入标题');
            }
            if (!isset($post['description']) || empty($post['description'])) {
                $this->error('请输入发布内容');
            }
            $param = array(
                'type' => 0,
                'title' => trim($post['title']),
                'content' => $post['description'],
                'is_hot' => $post['is_hot'],
                'is_important_notice' => $post['is_important_notice'],
                'status' => $post['status'],
                'add_time' => time()
            );
            $result = D('House_village_news')->data($param)->add();
            if (!$result) {
                $this->error('添加失败');
            } else {
                $village_news_notice = D('House_village_news_notice')->where(['uid' => 0, 'news_id' => 0, 'type' => 0])->find();
                if ($village_news_notice) {
                    D('House_village_news_notice')->where(['uid' => 0, 'news_id' => 0, 'type' => 0])->data(['news_id' => $result])->save();
                    //todo 存在部分转发对象
                    D('House_village_news')->where(['news_id' => $result])->data(['is_whole' => 1])->save();
                }
                $this->success('添加成功！', U('House/platform_news'));
            }
        } else {
            $this->display();
        }
    }

    //todo 平台编辑新闻
    public function platform_news_edit()
    {
        $db_House_village_news = D('House_village_news');
        $db_House_village_news_notice = D('House_village_news_notice');
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['news_id']) || empty($post['news_id'])) {
                $this->error('缺少必要参数');
            }
            if (!isset($post['title']) || empty($post['title'])) {
                $this->error('请输入标题');
            }
            if (!isset($post['description']) || empty($post['description'])) {
                $this->error('请输入发布内容');
            }
            $news_info = $db_House_village_news->where(['news_id' => $post['news_id'], 'type' => 0])->find();
            if (!$news_info) {
                $this->error('新闻不存在');
            }
            $param = array(
                'title' => trim($post['title']),
                'content' => $post['description'],
                'is_hot' => $post['is_hot'],
                'is_important_notice' => $post['is_important_notice'],
                'status' => $post['status']
            );
            $village_news_notice = $db_House_village_news_notice->where(['news_id' => $news_info['news_id'], 'type' => 0])->find();
            if ($village_news_notice) {
                $param['is_whole'] = 1;
            } else {
                $param['is_whole'] = 0;
            }
            $db_House_village_news->where(['news_id' => $post['news_id']])->save($param);
            $this->success('编辑成功！', U('House/platform_news'));
        } else {
            $condition_village['news_id'] = $_GET['news_id'];
            $news_info = $db_House_village_news->where($condition_village)->find();
            if (empty($news_info)) {
                $this->error('暂无此新闻');
            }
            $this->assign('news_info', $news_info);
            $this->display();
        }
    }

    //todo 平台新闻删除
    public function platform_news_del()
    {
        if (IS_POST) {
            $post = $_POST;
            $news_id = $post['news_id'] + 0;
            if (!$news_id) {
                $this->error('传递参数有误！~~~');
            }
            $database_house_village_news = D('House_village_news');
            $news_where['type'] = 0;
            $news_where['news_id'] = $news_id;
            $insert_id = $database_house_village_news->where($news_where)->delete();
            if ($insert_id) {
                //todo 同步删除绑定关系
                D('House_village_news_notice')->where(['type' => 0, 'news_id' => $news_id])->delete();
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }
    }

    //todo 平台新闻消息发送
    public function platform_news_send()
    {
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['news_id']) || empty($post['news_id'])) {
                $this->error('缺少必要参数');
            }
            $rel = D('House_village_news_notice')->news_notice_send($post['news_id']);
            if (!$rel['error']) {
                $this->error($rel['msg']);
            } else {
                $this->success($rel['msg']);
            }
        }
    }

    //todo 平台选择小区
    public function choice_village()
    {
        $condition_house_village = [];
        //存在文章id
        if (isset($_GET['news_id']) || !empty($_GET['news_id'])) {
            $news_id = $_GET['news_id'];
        } else {
            $news_id = 0;
        }
        if ($_GET['property_id']) {
            $condition_house_village['property_id'] = $_GET['property_id'];
        }
        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
        } else {
            if ($this->config['many_city'] && $_GET['province_idss']) {
                $condition_house_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
            }
        }
        if ($_GET['area_id']) {
            $condition_house_village['area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['community_id'] = $where['community_id'] = $_GET['community_idss'];
        }
        if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
            $keyword = trim($_GET['keyword']);
            $condition_house_village['village_name'] = array('like', '%' . $keyword . '%');
        }
        if(isset($this->system_session['area_type']) && $this->system_session['area_type']==3){
            $area=D('Area')->get_parents_area_by_areaid($this->system_session['area_id']);
            if(isset($this->system_session['street_id']) && ($this->system_session['street_id']>0)){
                $condition_house_village['street_id']=$this->system_session['street_id'];
            }elseif(!empty($area)){
                if(isset($area['1']) && ($area['1']>0)){
                    $condition_house_village['province_id'] = $area['1'];
                }
                if(isset($area['2']) && ($area['2']>0)){
                    $condition_house_village['city_id'] = $area['2'];
                }
                if(isset($area['3']) && ($area['3']>0)){
                    $condition_house_village['area_id'] = $area['3'];
                }
            }
        }
        $rr = D('House_village_news_notice')->news_notice_bind($condition_house_village, $news_id);
        $this->assign('list', $rr['list']);
        $this->assign('pagebar', $rr['page']);
        $this->assign('news_id', $news_id);
        $this->display();
    }

    //todo 绑定小区
    public function bind_village()
    {
        $dbHouse_village_news_notice = D('House_village_news_notice');
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['mark_id']) || empty($post['mark_id'])) {
                $this->error('请选择小区');
            }
            $rel = D('House_village_news_notice')->news_notice_add($post);
            if (!$rel['error']) {
                $this->error($rel['msg']);
            } else {
                $this->success($rel['msg']);
            }
        } else {
            $dbhouse_village = D('House_village');
            $condition_house_village['status']=1;
            //存在文章id
            if (isset($_GET['news_id']) && !empty($_GET['news_id'])) {
                $village_news_notice_where=[
                    'type' => 0,
                    'news_id' => $_GET['news_id']
                ];
            }else{
                $village_news_notice_where=[
                    'type' => 0,
                    'news_id' =>0
                ];
            }
            $village_not_id = $dbHouse_village_news_notice->where($village_news_notice_where)->getField('village_id', true);
            if ($village_not_id) {
                $village_not_id = array_values(array_unique($village_not_id));
                $condition_house_village['village_id'] = ['not in',$village_not_id];
            }
            if ($_GET['property_id']) {
                $condition_house_village['property_id'] = $_GET['property_id'];
            }
            if ($_GET['city_idss'] && $this->config['many_city']) {
                $condition_house_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
            } else {
                if ($this->config['many_city'] && $_GET['province_idss']) {
                    $condition_house_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
                }
            }
            if ($_GET['area_id']) {
                $condition_house_village['area_id'] = $where['area_id'] = $_GET['area_id'];
            }
            if ($_GET['street_idss']) {
                $condition_house_village['street_id'] = $where['street_id'] = $_GET['street_idss'];
            }
            if ($_GET['community_idss']) {
                $condition_house_village['community_id'] = $where['community_id'] = $_GET['community_idss'];
            }
            if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
                $condition_house_village['village_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if(isset($this->system_session['area_type']) && $this->system_session['area_type']==3){
                $area=D('Area')->get_parents_area_by_areaid($this->system_session['area_id']);
                if(isset($this->system_session['street_id']) && ($this->system_session['street_id']>0)){
                    $condition_house_village['street_id']=$this->system_session['street_id'];
                }elseif(!empty($area)){
                    if(isset($area['1']) && ($area['1']>0)){
                        $condition_house_village['province_id'] = $area['1'];
                    }
                    if(isset($area['2']) && ($area['2']>0)){
                        $condition_house_village['city_id'] = $area['2'];
                    }
                    if(isset($area['3']) && ($area['3']>0)){
                        $condition_house_village['area_id'] = $area['3'];
                    }
                }
            }
            $count_village = $dbhouse_village->where($condition_house_village)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_village, 20, 'page');
            $field = "village_id,village_name,village_address";
            $village_list = $dbhouse_village->field($field)->where($condition_house_village)->order('village_id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
            $pagebar = $p->show();
            $this->assign('list', $village_list);
            $this->assign('pagebar', $pagebar);
            $this->display();
        }
    }

    //todo 删除绑定小区关系
    public function remove_bind_village()
    {
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['notice_id']) || empty($post['notice_id'])) {
                $this->error('缺少必要参数');
            }
            D('House_village_news_notice')->news_notice_unbind($post['notice_id']);
            $this->success(L_("移除成功！"));
        }
    }

    //todo 平台新闻评论列表
    public function platform_news_reply()
    {
        $news = D('House_village_news_reply')->get_platform_list(20);
        $is_check = D('config')->where(['name' => 'house_news_comment_open'])->getField('value');
        $this->assign('news_list', $news['list']);
        $this->assign('pagebar', $news['page']);
        $this->assign('is_check', $is_check);
        $this->display();
    }

    //todo 平台新闻评论审核
    public function platform_news_reply_check()
    {
        if (IS_POST) {
            $status = $_POST['type'] == 'open' ? 1 : 0;
            D('config')->where(['name' => 'house_news_comment_open'])->save(['value' => $status]);
            exit('1');
        }
    }

    //todo 评论显示隐藏
    public function platform_news_reply_status()
    {
        if (IS_POST) {
            $status = $_POST['type'] == 'open' ? '1' : '2';
            if (D('House_village_news_reply')->where(array('pigcms_id' => $_POST['id']))->save(array('status' => $status))) {
                exit('1');
            } else {
                exit;
            }
        }
    }

    //todo 删除评论
    public function platform_news_reply_del()
    {
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['pigcms_id']) || empty($post['pigcms_id'])) {
                $this->error('缺少必要参数');
            }
            $condition['pigcms_id'] = $post['pigcms_id'];
            D('House_village_news_reply')->where($condition)->delete();
            $this->success(L_("删除成功！"));
        }
    }

    //todo 评论已读
    public function platform_news_reply_read()
    {
        if (IS_POST) {
            $post = $_POST;
            if (!isset($post['cmsid']) || empty($post['cmsid'])) {
                $this->error('缺少必要参数');
            }
            $condition['is_read'] = 0;
            $condition['pigcms_id'] = $post['cmsid'];
            $data['is_read'] = 1;
            $result = D('House_village_news_reply')->where($condition)->data($data)->save();
            if ($result) {
                $this->success('已读');
            }
            $this->error('失败');
        }
    }


    //todo 运维人员列表
    public function maintenan_list(){
        $list = D('House_maintenan')->getlist();
        $this->assign('list', $list['list']);
        $this->assign('pagebar', $list['pagebar']);
        $this->display();
    }

    //todo 添加运维人员
    public function maintenan_add(){
        if(IS_POST){
            $data=array(
                'status'=>1,
                'add_time'=>time()
            );
            if (!isset($_POST['name']) || empty($_POST['name'])) {
                $this->error('请输入姓名');
            }
            if (!isset($_POST['phone']) || empty($_POST['phone'])) {
                $this->error('请输入手机号');
            }
            $data['name'] = trim($_POST['name']);
            $data['phone'] = trim($_POST['phone']);
            $rel=D('House_maintenan')->maintenan_add($data);
            if($rel){
                $this->frame_submit_tips(1, '添加成功');
            }
            else{
                $this->error('添加失败');
            }
        }
        else {
            $this->display();
        }
    }

    //todo 编辑运维人员
    public function maintenan_edit(){
        if(IS_POST){
            $data=array(
                'update_time'=>time()
            );
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                $this->error('缺少必要参数');
            }
            if (!isset($_POST['name']) || empty($_POST['name'])) {
                $this->error('请输入姓名');
            }
            if (!isset($_POST['phone']) || empty($_POST['phone'])) {
                $this->error('请输入手机号');
            }
            if (isset($_POST['status']) && in_array($_POST['status'],[0,1])) {
                $data['status']=$_POST['status'];
            }
            $data['name'] = trim($_POST['name']);
            $data['phone'] = trim($_POST['phone']);
            $rel=D('House_maintenan')->maintenan_edit(['id'=>intval($_POST['id']),'status'=>['in', [0,1]]],$data);
            if($rel){
                $this->frame_submit_tips(1, '编辑成功');
            }
            else{
                $this->error('编辑失败');
            }
        }
        else {
            $where['id']=intval($_GET['id']);
            $where['status']=['in', [0,1]];
            $list = D('House_maintenan')->get_one($where);
            $this->assign('res', $list);
            $this->display();
        }
    }

    //todo 删除运维人员
    public function maintenan_del(){
        if(IS_POST){
            $data=array(
                'status'=>4,
                'update_time'=>time()
            );
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                $this->error('缺少必要参数');
            }
            $rel=D('House_maintenan')->maintenan_edit(['id'=>intval($_POST['id']),'status'=>['in', [0,1]]],$data);
            if($rel){
                $this->success('删除成功');
            }
            else{
                $this->error('删除失败');
            }
        }
    }

    //todo 查看报警信息
    public function maintenan_log(){
        $id=$_GET['id'];
        $list = D('House_maintenan')->getlistlog($id);
        $this->assign('list', $list['list']);
        $this->assign('pagebar', $list['pagebar']);
        $this->display();
    }

    //todo 校验运维人员微信绑定
    public function check_maintenan_bind()
    {
        $where['id']=intval($_GET['id']);
        $where['status']=['in', [0,1]];
        $list = D('House_maintenan')->get_one($where);
        if (empty($list)) {
            exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
        }
        if (!empty($list['openid'])) {
            exit(json_encode(array('error_code' => 0, 'msg' => 'ok', 'nickname' => $list['nickname'], 'avatar' => $list['avatar'])));
        } else {
            exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
        }
    }

    //todo 取消绑定
    public function cancel_maintenan_bind()
    {
        $data=array('openid' => '', 'avatar' => '', 'nickname' => '');
        $rel=D('House_maintenan')->maintenan_edit(['id'=>intval($_GET['id']),'status'=>['in', [0,1]]],$data);
        if ($rel) {
            exit(json_encode(array('error_code' => 0, 'msg' => 'ok')));
        } else {
            exit(json_encode(array('error_code' => 1, 'msg' => '取消失败')));
        }
    }

    //小区添加
    public function village_add()
    {
        if (IS_POST) {
            // 添加默认允许游客访问
            if (!$_POST['tourist']) {
                $_POST['tourist'] = 1;
            }
//			$_POST['pwd'] = md5($_POST['pwd']);
            $database_house_village = D('House_village');
            $database_house_village_config = D('House_village_config');
            $_POST['add_time'] = time();
            if ($_POST['expiration_time']) { // 到期时间
                $_POST['expiration_time'] = strtotime($_POST['expiration_time']);
            }
            if (isset($_POST['province_idss'])) {
                $_POST['province_id'] = $_POST['province_idss'];
                unset($_POST['province_idss']);
            }
            if (isset($_POST['city_idss'])) {
                $_POST['city_id'] = $_POST['city_idss'];
                unset($_POST['city_idss']);
            }

            if (isset($_POST['street_idss'])) {
                $_POST['street_id'] = $_POST['street_idss'];
                unset($_POST['street_idss']);
            }
            if (isset($_POST['community_idss'])) {
                $_POST['community_id'] = $_POST['community_idss'];
                unset($_POST['community_idss']);
            }
            $data = [];
            if (isset($_POST['village_single_support_digit'])) {
                $village_single_support_digit = $_POST['village_single_support_digit'];
                if (!in_array($village_single_support_digit, [2,3])) {
                    $village_single_support_digit = 2;
                }
                $data['village_single_support_digit'] = $village_single_support_digit;
                $data['single_support_digit_set_time'] = time();
                unset($_POST['village_single_support_digit']);
            }
            $supply_bind_village_id = 0;
            if (isset($_POST['supply_bind_village_id'])) {
                $supply_bind_village_id = intval($_POST['supply_bind_village_id']);
                unset($_POST['supply_bind_village_id']);
            }
            $developer_bind_village_id = 0;
            if (isset($_POST['developer_bind_village_id'])) {
                $developer_bind_village_id = intval($_POST['developer_bind_village_id']);
                unset($_POST['developer_bind_village_id']);
            }

			$add_a = $database_house_village->data($_POST)->add();

            if ($add_a) {
                $data['village_id'] = $add_a;
                $data['village_sort'] = $_POST['village_sort'];
                $database_house_village_config->add($data);
                D('House_village')->update_user_info($add_a);
                $arr = array();
                $arr['param'] = serialize(array('village_id' =>$add_a,'type'=>'village'));
                $arr['plan_time'] = time()+30;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_village_'.$add_a;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();

                $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
                $db_house_community_about = D('House_community_about');
                $from_user = ['type' => 'system_admin', 'admin_id' => $this->system_session['id'], 'account' => $this->system_session['account']];
                if ($this->system_session['realname']) {
                    $from_user['name'] = $this->system_session['realname'];
                } elseif ($this->system_session['nickname']) {
                    $from_user['name'] = $this->system_session['nickname'];
                }
                if ($this->system_session['phone']) {
                    $from_user['phone'] = $this->system_session['phone'];
                }
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                if ($property_id) {
                    $house_community_about_info = $db_house_community_about->where(['property_id' => $property_id, 'village_id' => 0])->find();
                    if (isset($house_community_about_info['is_virtual']) && intval($house_community_about_info['is_virtual']) == 1) {
                        $facilities_type = $house_community_about_info['facilities_type'];
                        $data = [
                            'property_id' => $property_id, 'village_id' => $add_a, 'facilities_type' => $facilities_type,
                            'from' => 'admin', 'from_user' => $from_user, 'is_virtual' => 1, 'add_time' => time(),
                        ];
                        $db_house_community_about->add($data);
                    }
                }
                if ($supply_bind_village_id) {
                    // 绑定供暖站
                    $data = [
                        'from' => 'admin', 'from_user' => $from_user, 'property_id' => $property_id,
                    ];
                    $db_house_community_about->villageBindFacilities($add_a, $supply_bind_village_id, $data, 'supply_station');
                }
                if ($developer_bind_village_id) {
                    // 绑定开发商
                    $data = [
                        'from' => 'admin', 'from_user' => $from_user, 'property_id' => $property_id,
                    ];
                    $db_house_community_about->villageBindFacilities($add_a, $developer_bind_village_id, $data, 'developer');
                }
                $this->success('添加成功！', U('House/village_add', array('property_id' => $_POST['property_id'])));
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $jz_wsxdsj_net_customized_open  = D('Config_customization')->getWSXCustomizedOpen();
            if ($jz_wsxdsj_net_customized_open) {
                $property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
                $db_house_community_about = D('House_community_about');
                $house_community_about_info = $db_house_community_about->where(['property_id' => $property_id, 'village_id' => 0])->find();
                if (empty($house_community_about_info) || $house_community_about_info['is_virtual'] != 1) {
                    $supplyStationArr = $db_house_community_about->getAllFacilitiesTypeList('supply_station');
                    $developerArr = $db_house_community_about->getAllFacilitiesTypeList('developer');
                    $this->assign('supplyStationArr', $supplyStationArr);
                    $this->assign('developerArr', $developerArr);
                }
            }
            $this->display();
        }
    }

    //小区添加
    public function village_edit()
    {

        if ($this->system_session['level'] != 2 && !in_array(247, $this->system_session['menus'])) {
            $can_recharge = 0;
        } else {
            $can_recharge = 1;
        }
        $this->assign('can_recharge', $can_recharge);
        $database_house_village = D('House_village');
        $database_house_village_config = D('House_village_config');
        $tencent_voice_asr_open = C('config.tencent_voice_asr_open');
        $judge_voice_recognition_asr_open  = D('Config_customization')->getVoiceRecognitionOpenJudge();
        if(!$judge_voice_recognition_asr_open){
            $tencent_voice_asr_open=0;
        }
        $app_key = $this->config['hik_info_vision_i_community_app_key'];
        $app_secret = $this->config['hik_info_vision_i_community_app_secret'];
        if (IS_POST) {

            $now_village_info = $database_house_village->where(array('village_id' => $_POST['village_id']))->field('now_sms_number,community_id,property_id')->find();
            $now_sms_number = $now_village_info['now_sms_number'];
            $community_id = $now_village_info['community_id'];

            if (!empty($_POST['set_sms_number'])) {

                $sms_buy_order_data['orderid'] = 'sq' . date("ymdHis") . rand(10, 99) . sprintf("%06d", $_POST['village_id']);
                $sms_buy_order_data['type_id'] = $_POST['village_id'];
                $sms_buy_order_data['type'] = 1;
                $sms_buy_order_data['payment_money'] = 0.00;
                $sms_buy_order_data['sms_number'] = $_POST['set_sms_number'];
                $sms_buy_order_data['add_time'] = time();
                $sms_buy_order_data['pay_time'] = time();
                $sms_buy_order_data['pay_type'] = 'system';
                $sms_buy_order_data['paid'] = 3;
                $sms_buy_order_data['operation_type'] = 1;
                $sms_buy_order_data['operation'] = $this->system_session['realname'];


                if ($_POST['set_sms_type'] == 1) {
                    $_POST['now_sms_number'] = $now_sms_number + $_POST['set_sms_number'];
                    $sms_buy_order_data['set_type'] = 0;
                } else {
                    $_POST['now_sms_number'] = $now_sms_number - $_POST['set_sms_number'];
                    $sms_buy_order_data['set_type'] = 1;
                }

                $sms_buy_order_data['current_number'] = $_POST['now_sms_number'];

                if ($_POST['now_sms_number'] < 0) {
                    $this->error('修改后，短信条数不能小于0');
                }
            }
            $hikCommunityIndexCode = isset($_POST['hikCommunityIndexCode']) && trim($_POST['hikCommunityIndexCode']) ? trim($_POST['hikCommunityIndexCode']) : '';
            if ($app_key && $app_secret) {
                $otherWhere = ['app_key' => $app_key, 'from' => 'house', 'village_id' => $_POST['village_id']];
                $hikCommunityInfo = M('Hik_info_vision_community_regions')->where($otherWhere)->find();
                if ($hikCommunityInfo) {
                    $hikSaveData = ['village_id' => 0, 'update_time' => time()];
                    M('Hik_info_vision_community_regions')->where($otherWhere)->save($hikSaveData);
                }
                if ($hikCommunityIndexCode) {
                    $where = ['app_key' => $app_key,'from' => 'house','index_code' => $hikCommunityIndexCode];
                    $hikCommunityInfo = M('Hik_info_vision_community_regions')->where($where)->find();
                    if (! $hikCommunityInfo['village_id'] || $hikCommunityInfo['village_id'] == $_POST['village_id']) {
                        $hikSaveData = ['village_id' => $_POST['village_id'], 'update_time' => time()];
                        M('Hik_info_vision_community_regions')->where($where)->save($hikSaveData);
                        invoke_v20_service('app\community\model\service\Device\HikInfoVisionICommunityService/laterQueueGetRegionsHouses',['village_id'=>$_POST['village_id']]);
                    }
                }
            }

//			if($_POST['pwd']){
//				$_POST['pwd'] = md5($_POST['pwd']);
//			}else{
//				unset($_POST['pwd']);
//			}

            // 绑定商家 一个商家只能绑定小区
            if ($_POST['mer_id']) {
                $mer_village = $database_house_village->where(array('village_id' => array('neq', $_POST['village_id']), 'mer_id' => $_POST['mer_id']))->find();
                if ($mer_village) {
                    $this->error('该商家已绑定过别的小区，请换个商家ID或者先解绑');
                }
            }
            
            if(isset($_POST['scrcu_mer_no'])){
                $_POST['scrcu_mer_no']=$_POST['scrcu_mer_no'] ? trim($_POST['scrcu_mer_no']):'';
                $app_village = M('House_village_pay_config')->where(array('village_id'=>$_POST['village_id']))->find();
                if($app_village){
                    $saveArr=array('scrcu_mer_no'=>$_POST['scrcu_mer_no']);
                    if(isset($_POST['scrcu_mer_no_2'])){
                        $saveArr['scrcu_mer_no_2']=$_POST['scrcu_mer_no_2'] ? trim($_POST['scrcu_mer_no_2']):'';
                    }
                    M('House_village_pay_config')->where(array('village_id'=>$_POST['village_id']))->data($saveArr)->save();
                }else{
                    if($_POST['scrcu_mer_no']){
                        $addArr=array( 'scrcu_mer_no'=>$_POST['scrcu_mer_no'], 'village_id'=>$_POST['village_id']);
                        if(isset($_POST['scrcu_mer_no_2'])){
                            $addArr['scrcu_mer_no_2']=$_POST['scrcu_mer_no_2'] ? trim($_POST['scrcu_mer_no_2']):'';
                        }
                        M('House_village_pay_config')->data($addArr)->add();
                    }
                }
            }
            if(isset($_POST['mid'])){
                $app_village = M('House_village_pay_config')->where(array('village_id'=>$_POST['village_id']))->find();
                if($app_village){
                    M('House_village_pay_config')->where(array('village_id'=>$_POST['village_id']))->data([
                        'mid'=>trim($_POST['mid']),
                        'chinaums_zg_mid'=>trim($_POST['chinaums_zg_mid'])
                    ])->save();
                }else{
                    if($_POST['mid']){
                        M('House_village_pay_config')->data([
                            'mid'=>$_POST['mid'],
                            'village_id'=>$_POST['village_id'],
                            'chinaums_zg_mid'=>trim($_POST['chinaums_zg_mid'])
                        ])->add();
                    }
                }
            }

            $data['open_score_get_percent'] = $_POST['open_score_get_percent'];
            if ($data['open_score_get_percent'] == 1) {
                if (intval($_POST['score_get_percent']) >= 0 && intval($_POST['score_get_percent']) <= 100) {
                    $data['score_get_percent'] = $_POST['score_get_percent'];
                } else {
                    $this->error('积分获取百分比请填写0-100的数值');
                }
            } else {
                $data['user_score_get'] = $_POST['user_score_get'];
            }

            $data['village_pay_integral'] = $_POST['village_pay_integral'];
            $data['village_owe_pay_integral'] = $_POST['village_owe_pay_integral'];
            $data['village_pay_use_integral'] = $_POST['village_pay_use_integral'];
            $data['village_pay_owe_use_integral'] = $_POST['village_pay_owe_use_integral'];
            $data['use_max_integral_num'] = $_POST['use_max_integral_num'];
            $data['use_max_integral_percentage'] = $_POST['use_max_integral_percentage'];
            $data['village_id'] = $_POST['village_id'];
            $data['isverify'] = $_POST['isverify'];
            $data['village_sort'] = $_POST['village_sort'];
            $data['open_village_balance'] = $_POST['open_village_balance'];//允许使用小区住户余额
            if ($_POST['expiration_time']) { // 到期时间
                $_POST['expiration_time'] = strtotime($_POST['expiration_time']);
            }
            if (isset($_POST['province_idss'])) {
                $_POST['province_id'] = $_POST['province_idss'];
                unset($_POST['province_idss']);
            }
            if (isset($_POST['city_idss'])) {
                $_POST['city_id'] = $_POST['city_idss'];
                unset($_POST['city_idss']);
            }
            if (isset($_POST['street_idss'])) {
                $_POST['street_id'] = $_POST['street_idss'];
                unset($_POST['street_idss']);
            }
            if (isset($_POST['community_idss'])) {
                $_POST['community_id'] = $_POST['community_idss'];
                unset($_POST['community_idss']);
            }
            $supply_bind_village_id = 0;
            if (isset($_POST['supply_bind_village_id'])) {
                $supply_bind_village_id = intval($_POST['supply_bind_village_id']);
                unset($_POST['supply_bind_village_id']);
            }
            $developer_bind_village_id = 0;
            if (isset($_POST['developer_bind_village_id'])) {
                $developer_bind_village_id = intval($_POST['developer_bind_village_id']);
                unset($_POST['developer_bind_village_id']);
            }
            //2020/9/2 禁止物业则禁止小区，开区小区需要物业先开启 start
            if ($_POST['status'] == 1 || $_POST['status'] == 0) {
                $property_status = D('House_property')->where(['id' => $now_village_info['property_id']])->getField('status');
                if ($property_status == 2) {
                    $this->error('请先开启物业');
                }
                //以下信息，其中有一个没填 则视为信息未完善
//                if(!$now_village_info['property_phone'] || !$now_village_info['property_address'] || !$now_village_info['long'] || !$now_village_info['lat'] || !$now_village_info['province_id'] ||!$now_village_info['city_id'] ||!$now_village_info['area_id'] ){
//                    $_POST['status'] = 0;
//                }
            }

            //2020/9/2 禁止物业则禁止小区，开区小区需要物业先开启 end
            $edit_a = $database_house_village->where(array('village_id' => $_POST['village_id']))->data($_POST)->save();
            $village_info_arr=array();
            if(isset($_POST['sms_type']) && ($_POST['sms_type']==1 || $_POST['sms_type']==2)){
                $village_info_arr['sms_type']=$_POST['sms_type'];
            }
            if(isset($_POST['voice_recognition_open'])){
                $village_info_arr['voice_recognition_open']= intval($_POST['voice_recognition_open']);
            }
            if ($_POST['is_default'] == 1) {
                $village_info = M('House_village_info')->where(['is_default' => 1])->find();
                if (empty($village_info)) {
                    $village_info_arr['is_default']=1;
                    M('House_village_info')->where(['village_id' => $_POST['village_id']])->data($village_info_arr)->save();
                } elseif ($village_info['village_id'] != $_POST['village_id']) {
                    $village_info_arr['is_default']=1;
                    M('House_village_info')->where(['village_id' => $village_info['village_id']])->data(['is_default' => 0])->save();
                    M('House_village_info')->where(['village_id' => $_POST['village_id']])->data($village_info_arr)->save();
                }else{
                    M('House_village_info')->where(['village_id' => $_POST['village_id']])->data($village_info_arr)->save();
                }
            } else {
                $village_info_arr['is_default']=0;
                M('House_village_info')->where(['village_id' => $_POST['village_id']])->data($village_info_arr)->save();
            }

            $find = $database_house_village_config->where(array('village_id' => $_POST['village_id']))->find();
            if ($find) {
                $edit_b = $database_house_village_config->where(array('village_id' => $_POST['village_id']))->save($data);
            } else {
                $edit_b = $database_house_village_config->add($data);
            }

            if ($edit_a === false && $edit_b === false) {
                $this->error('编辑失败！请重试~');
            } else {
                if (!empty($_POST['set_sms_number'])) {
                    D('Sms_buy_order')->data($sms_buy_order_data)->add();
                }
                if ($community_id && $_POST['community_id'] && $community_id != $_POST['community_id']) {
                    $where_bind = array(
                        'type' => 2,
                        'bind_id' => $_POST['village_id'],
                        'fid' => $community_id
                    );
                    // 修改了关联
                    $set_data = array(
                        'fid' => $_POST['community_id']
                    );
                    D('House_village_custom_polygon')->change_village_custom($where_bind, $set_data);
                }
                $arr = array();
                $arr['param'] = serialize(array('village_id' =>$_POST['village_id'],'type'=>'village'));
                $arr['plan_time'] = time()+30;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_village_'.$_POST['village_id'];
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();

                $property_id = isset($now_village_info['property_id']) ? intval($now_village_info['property_id']) : 0;
                $village_id = isset($_POST['village_id']) ? intval($_POST['village_id']) : 0;
                $db_house_community_about = D('House_community_about');
                $from_user = ['type' => 'system_admin', 'admin_id' => $this->system_session['id'], 'account' => $this->system_session['account']];
                if ($this->system_session['realname']) {
                    $from_user['name'] = $this->system_session['realname'];
                } elseif ($this->system_session['nickname']) {
                    $from_user['name'] = $this->system_session['nickname'];
                }
                if ($this->system_session['phone']) {
                    $from_user['phone'] = $this->system_session['phone'];
                }
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                $is_contact_available = isset($_POST['is_contact_available']) ? intval($_POST['is_contact_available']) : 0;
                if ($property_id) {
                    $village_about_info = $db_house_community_about->where(['village_id' => $village_id])->find();
                    if (empty($village_about_info)) {
                        $property_about_info = $db_house_community_about->where(['property_id' => $property_id, 'village_id' => 0])->find();
                        if (isset($property_about_info['is_virtual']) && intval($property_about_info['is_virtual']) == 1) {
                            $facilities_type = $property_about_info['facilities_type'];
                            $data = [
                                'property_id' => $property_id, 'village_id' => $village_id, 'facilities_type' => $facilities_type,
                                'from' => 'admin', 'from_user' => $from_user, 'is_virtual' => 1, 'add_time' => time(),
                                'is_contact_available' => $is_contact_available
                            ];
                            $db_house_community_about->add($data);
                        }
                    } else {
                        $db_house_community_about->where(['village_id' => $village_id])->setField('is_contact_available', $is_contact_available);
                    }
                }
                if ($supply_bind_village_id) {
                    // 绑定供暖站
                    $data = [
                        'from' => 'admin', 'from_user' => $from_user, 'property_id' => $property_id,
                    ];
                    $db_house_community_about->villageBindFacilities($village_id, $supply_bind_village_id, $data, 'supply_station');
                }
                if ($developer_bind_village_id) {
                    // 绑定开发商
                    $data = [
                        'from' => 'admin', 'from_user' => $from_user, 'property_id' => $property_id,
                    ];
                    $db_house_community_about->villageBindFacilities($village_id, $developer_bind_village_id, $data, 'developer');
                }
                $this->success('编辑成功！');
            }
        } else {

            $now_village = $database_house_village->field(true)->where(array('village_id' => $_GET['village_id']))->find();
            if (empty($now_village)) {
                $this->error('当前' . C('config.house_name') . '不存在');
            } else {
                $now_village['expiration_time'] = $now_village['expiration_time'] ? date('Y-m-d H:i', $now_village['expiration_time']) : '';
                $info = $database_house_village_config->field(true)->where(array('village_id' => $now_village['village_id']))->find();
                if ($info) {
                    $now_village['open_score_get_percent'] = $info['open_score_get_percent'];
                    $now_village['user_score_get'] = $info['user_score_get'];
                    $now_village['score_get_percent'] = $info['score_get_percent'];
                    $now_village['village_sort'] = $info['village_sort'];
                    $now_village['open_village_balance'] = $info['open_village_balance'];//允许使用小区住户余额

                    $now_village['village_pay_integral'] = $info['village_pay_integral'];
					$now_village['village_owe_pay_integral'] = $info['village_owe_pay_integral'];
					$now_village['village_pay_use_integral'] = $info['village_pay_use_integral'];
					$now_village['village_pay_owe_use_integral'] = $info['village_pay_owe_use_integral'];
					$now_village['use_max_integral_num'] = $info['use_max_integral_num'];
					$now_village['use_max_integral_percentage'] = $info['use_max_integral_percentage'];
				}
                $pay_info = M('House_village_pay_config')->field(true)->where(array('village_id'=>$now_village['village_id']))->find();
                $now_village['scrcu_mer_no_2'] = '';
                if($pay_info){
                    $now_village['scrcu_mer_no'] = $pay_info['scrcu_mer_no'];
                    $now_village['mid'] = $pay_info['mid'];
                    $now_village['chinaums_zg_mid'] = $pay_info['chinaums_zg_mid'];
                    if(isset($pay_info['scrcu_mer_no_2'])){
                        $now_village['scrcu_mer_no_2'] = $pay_info['scrcu_mer_no_2'];
                    }
                }else{
                    $now_village['scrcu_mer_no'] = '';
                    $now_village['mid'] = '';
                    $now_village['chinaums_zg_mid'] = '';
                }
                $village_info = M('House_village_info')->where(['village_id' => $_GET['village_id']])->find();
                if (empty($village_info)) {
                    M('House_village_info')->data([
                        'village_id' => $_GET['village_id'],
                        'property_id' => $now_village['property_id'],
                        'add_time' => time()
                    ])->add();
                    $now_village['is_default'] = 0;
                    $now_village['sms_type']=1;
                    $now_village['voice_recognition_open']=0;
                } else {
                    $now_village['is_default'] = $village_info['is_default'];
                    $now_village['sms_type']=$village_info['sms_type'];
                    $now_village['voice_recognition_open']= isset($village_info['voice_recognition_open']) ? $village_info['voice_recognition_open']:0;
                }
            }
            $this->assign('tencent_voice_asr_open', $tencent_voice_asr_open);
            $this->assign('now_village', $now_village);
            if(strpos($_SERVER['SERVER_NAME'],'hz.huizhisq.com') !== false){
                $this->assign('is_scuru', 1);
            }else{
                $this->assign('is_scuru', 0);
            }
            $jz_wsxdsj_net_customized_open  = D('Config_customization')->getWSXCustomizedOpen();
            if ($jz_wsxdsj_net_customized_open) {
                $db_house_community_about = D('House_community_about');
                $supplyStationBindInfo = $db_house_community_about->getVillageBindFacilitiesInfo($now_village['village_id'], 'supply_station');
                unset($supplyStationBindInfo['from_user']);
                $developerBindInfo = $db_house_community_about->getVillageBindFacilitiesInfo($now_village['village_id'], 'developer');
                unset($developerBindInfo['from_user']);
                $this->assign('supplyStation', $supplyStationBindInfo);
                $this->assign('developer', $developerBindInfo);
                $property_id = isset($now_village['property_id']) ? intval($now_village['property_id']) : 0;
                $house_community_about_info = $db_house_community_about->where(['property_id' => $property_id, 'village_id' => 0])->find();
                if (empty($house_community_about_info) || $house_community_about_info['is_virtual'] != 1) {
                    $supplyStationArr = $db_house_community_about->getAllFacilitiesTypeList('supply_station');
                    $developerArr = $db_house_community_about->getAllFacilitiesTypeList('developer');
                    $this->assign('supplyStationArr', $supplyStationArr);
                    $this->assign('developerArr', $developerArr);
                }
                $where = [
                    'village_id' => $now_village['village_id'],
                    'is_virtual' => 1,
                ];
                $communityAboutInfo = $db_house_community_about->getCommunityAboutInfo($where);
                if (isset($communityAboutInfo['facilities_type']) && $communityAboutInfo['facilities_type']) {
                    $communityAboutInfo['facilities_type_text'] = $db_house_community_about->getFacilitiesTypeText($communityAboutInfo['facilities_type']);
                }
                $this->assign('communityAboutInfo', $communityAboutInfo);
            }
            if ($app_key && $app_secret) {
                $where = [
                    'app_key' => $app_key,
                    'from' => 'house',
                    'index_code' => ['neq', ''],
                    'village_id' => ['in', [0, $now_village['village_id']]],
                ];
                $hikCommunity = M('Hik_info_vision_community_regions')->where($where)->field('id, index_code, name, village_id')->order('sort desc')->select();
                $this->assign('hikCommunity', $hikCommunity);
            }
        }
        $this->display();
    }


    public function sms_order()
    {
        $condition_where['type_id'] = $_GET['village_id'];
        $condition_where['type'] = 1;

        if ($_GET['paid'] != '') {
            $condition_where['paid'] = array('eq', $_GET['paid']);
        }

        $condition_where['pay_type'] = 'system';
        // $count = D("Sms_buy_order")->where($condition_where)->count();
        // import('@.ORG.merchant_page');
        // $p = new Page($count, 10);
        $orderList = M('Sms_buy_order')->where($condition_where)->order('order_id DESC')->select();
        // $this->assign('pagebar',$p->show());
        $this->assign('orderList', $orderList);

        $this->display();
    }

    //小区导入
    public function village_import()
    {
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/village');

                $upload_dir = './upload/excel/village/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);    //删除文件
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $database_house_village = D('House_village');
                        $last_village_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null && $vv['L'] === null) continue;
                            if (empty($vv['A'])) {
                                $err_msg = '请填写' . C('config.house_name') . '名称！';
                                continue;
                            }
                            if (empty($vv['B'])) {
                                $err_msg = '请填写' . C('config.house_name') . '地址！';
                                continue;
                            }
                            if (empty($vv['C'])) {
                                $err_msg = '请填写物业公司名称！';
                                continue;
                            }
                            if (empty($vv['D'])) {
                                $err_msg = '请填写物业联系地址！';
                                continue;
                            }
                            if (empty($vv['E'])) {
                                $err_msg = '请填写物业联系电话！';
                                continue;
                            }
                            if (empty($vv['F'])) {
                                $err_msg = '请填写管理帐号！';
                                continue;
                            }
                            if (empty($vv['G'])) {
                                $err_msg = '请填写管理密码！';
                                continue;
                            }

                            $tmpdata = array();
                            $tmpdata['village_name'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            //检测小区是否已存在
                            if ($database_house_village->field('`village_id`')->where(array('village_name' => $tmpdata['village_name']))->find()) {
                                $err_msg = $vv['A'] . ' 已存在！';
                                continue;
                            }

                            $tmpdata['village_address'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $tmpdata['property_name'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['property_address'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['property_phone'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['account'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            $tmpdata['pwd'] = md5(htmlspecialchars(trim($vv['G']), ENT_QUOTES));
                            !empty($vv['H']) && $tmpdata['property_price'] = htmlspecialchars(trim($vv['H']));
                            !empty($vv['I']) && $tmpdata['water_price'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            !empty($vv['J']) && $tmpdata['electric_price'] = htmlspecialchars(trim($vv['J']));
                            !empty($vv['K']) && $tmpdata['gas_price'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                            !empty($vv['L']) && $tmpdata['park_price'] = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                            $tmpdata['status'] = 0;
                            $tmpdata['add_time'] = time();
                            $last_village_id = $database_house_village->data($tmpdata)->add();
                            if (!$last_village_id) {
                                $err_msg = $vv['A'] . ' 导入失败！';
                            }
                        }
                        if (!empty($last_village_id)) {
                            $this->frame_submit_tips(1, '导入成功');
                            // $this->success('导入成功');
                        } else {
                            // $this->error('导入失败');
                            $this->error( '导入失败！原因：' . $err_msg);
                        }
                    }
                } else {
                    // $this->error($upload->getErrorMsg());
                    $this->error( $upload->getErrorMsg());
                }
            }
            // $this->error('文件上传失败');
            $this->error( '文件上传失败');
        } else {
            $this->display();
        }
    }

    public function edit()
    {
        $this->assign('bg_color', '#F3F3F3');

        $database_user = D('User');
        $condition_user['uid'] = intval($_GET['uid']);
        $now_user = $database_user->field(true)->where($condition_user)->find();
        if (empty($now_user)) {
            $this->error('没有找到该用户信息！');
        }

        $levelDb = M(_view('User_level'));
        $tmparr = $levelDb->field(true)->order('id ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $levelarr[$vv['level']] = $vv;
            }
        }

        $this->assign('levelarr', $levelarr);
        $this->assign('now_user', $now_user);

        $this->display();
    }

    public function amend()
    {
        if (IS_POST) {
            $database_user = D('User');
            $condition_user['uid'] = intval($_POST['uid']);
            $now_user = $database_user->field(true)->where($condition_user)->find();
            if (empty($now_user)) {
                $this->error('没有找到该用户信息！');
            }
            $condition_user['uid'] = $now_user['uid'];
            $data_user['nickname'] = $_POST['nickname'];
            $data_user['phone'] = $_POST['phone'];
            if ($_POST['pwd']) {
                $data_user['pwd'] = md5($_POST['pwd']);
            }
            $data_user['sex'] = $_POST['sex'];
            $data_user['province'] = $_POST['province'];
            $data_user['city'] = $_POST['city'];
            $data_user['qq'] = $_POST['qq'];
            $data_user['status'] = $_POST['status'];
            $data_user['youaddress'] = trim($_POST['youaddress']);
            $data_user['truename'] = trim($_POST['truename']);

            $_POST['set_money'] = floatval($_POST['set_money']);
            if (!empty($_POST['set_money'])) {
                if ($_POST['set_money_type'] == 1) {
                    $data_user['now_money'] = $now_user['now_money'] + $_POST['set_money'];
                } else {
                    $data_user['now_money'] = $now_user['now_money'] - $_POST['set_money'];
                }
                if ($data_user['now_money'] < 0) {
                    $this->error('修改后，余额不能小于0');
                }
            }

            $_POST['set_score'] = intval($_POST['set_score']);
            if (!empty($_POST['set_score'])) {
                if ($_POST['set_score_type'] == 1) {
                    $data_user['score_count'] = $now_user['score_count'] + $_POST['set_score'];
                } else {
                    $data_user['score_count'] = $now_user['score_count'] - $_POST['set_score'];
                }
                if ($data_user['score_count'] < 0) {
                    $this->error('修改后，' . $this->config['score_name'] . '不能小于0');
                }
            }

            $data_user['level'] = intval($_POST['level']);

            if ($database_user->where($condition_user)->data($data_user)->save()) {
                if (!empty($_POST['set_money'])) {
                    D('User_money_list')->add_row($now_user['uid'], $_POST['set_money_type'], $_POST['set_money'], '管理员后台操作', false);
                }
                if (!empty($_POST['set_score'])) {
                    D('User_score_list')->add_row($now_user['uid'], $_POST['set_score_type'], $_POST['set_score'], '管理员后台操作', false);
                }
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $this->error('非法访问！');
        }
    }

    //平台提现
    public function companypay()
    {
        if (IS_POST) {
            if (!$village_info = D('House_village')->field('village_name,property_phone')->where('village_id=' . (int)$_POST['village_id'])->select()) {
                $this->error(C('config.house_name') . '不存在！');
            }
            sort($_POST['orderid']);
            $orderids = implode(',', $_POST['orderid']);
            $data['pay_type'] = 'house';
            $data['pay_id'] = $_POST['village_id'];
            $data['phone'] = $village_info[0]['property_phone'];
            $data['money'] = $_POST['money'];
            $data['desc'] = C('config.house_name') . $village_info[0]['village_name'] . '订单对账|订单号(' . $orderids . ')' . '|转账' . (float)($_POST['money'] / 100) . C('config.Currency_txt') . '';
            $data['status'] = 0;
            $data['add_time'] = time();

            $model = new Model();
            $where['order_id'] = array('in', $orderids);
            if ($model->table(C('DB_PREFIX') . 'companypay')->add($data) && $model->table(C('DB_PREFIX') . 'house_village_pay_order')->where($where)->setField('is_pay_bill', 1)) {
                $this->success("提现申请成功！");
            } else {
                $this->error("提现失败！请联系管理员！");
            }
        } else {
            $this->error('您提交的数据不正确');
        }
    }

    public function money_list()
    {
        $this->assign('bg_color', '#F3F3F3');


        $database_user_money_list = D('User_money_list');
        $condition_user_money_list['uid'] = intval($_GET['uid']);

        $count = $database_user_money_list->where($condition_user_money_list)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);

        $money_list = $database_user_money_list->field(true)->where($condition_user_money_list)->order('`time` DESC')->select();

        $this->assign('pagebar', $p->show());
        $this->assign('money_list', $money_list);
        $this->display();
    }

    public function score_list()
    {
        $this->assign('bg_color', '#F3F3F3');


        $database_user_score_list = D('User_score_list');
        $condition_user_score_list['uid'] = intval($_GET['uid']);

        $count = $database_user_score_list->where($condition_user_score_list)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);

        $score_list = $database_user_score_list->field(true)->where($condition_user_score_list)->order('`time` DESC')->select();

        $this->assign('pagebar', $p->show());
        $this->assign('score_list', $score_list);
        $this->display();
    }

    /*     * *导入客户页**** */

    public function import()
    {

        $this->display();
    }

    /*     * *导入客户页**** */

    public function execimport()
    {
        if ($_FILES['file']['error'] != 4) {

            import('ORG.Util.Dir');
            Dir::delDirnotself('./upload/excel/user');

            $getupload_dir = "/upload/excel/user/" . date('Ymd') . '/';
            $upload_dir = "." . $getupload_dir;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 10 * 1024 * 1024;
            $upload->allowExts = array('xls', 'xlsx');
            $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
            $upload->savePath = $upload_dir;
            $upload->thumb = false;
            $upload->thumbType = 0;
            $upload->imageClassPath = '';
            $upload->thumbPrefix = '';
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                //$reader = PHPExcel_IOFactory::createReader('Excel5');
                $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                $objReader = PHPExcel_IOFactory::createReader($fileType);
                $excelObj = $objReader->load($path);
                $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                unlink($path);    //删除文件
                if (!empty($result) && is_array($result)) {
                    unset($result[1]);
                    $user_importDb = D('User_import');
                    foreach ($result as $kk => $vv) {
                        if (empty($vv['A']) || empty($vv['B']) || empty($vv['C']))
                            continue;
                        $tmpdata = array();
                        $tmpdata['ppname'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                        $tmpdata['telphone'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                        $tmpdata['address'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                        !empty($vv['D']) && $tmpdata['village_id'] = intval(trim($vv['D']));
                        !empty($vv['E']) && $tmpdata['memberid'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                        !empty($vv['F']) && $tmpdata['level'] = intval(trim($vv['F']));
                        !empty($vv['G']) && $tmpdata['qq'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                        !empty($vv['H']) && $tmpdata['email'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                        !empty($vv['I']) && $tmpdata['money'] = intval(trim($vv['I']));
                        !empty($vv['J']) && $tmpdata['integral'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                        !empty($vv['K']) && $tmpdata['useraccount'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                        if (!empty($vv['L'])) {
                            $tmpdata['pwdmw'] = trim($vv['L']);
                            $tmpdata['pwd'] = md5($tmpdata['pwdmw']);
                        }
                        $tmpdata['isuse'] = 0;
                        $tmpdata['addtime'] = time();
                        $user_importDb->add($tmpdata);
                    }
                    if (!empty($tmpdata)) {
                        $this->dexit(array('error' => 0));
                    } else {
                        $this->dexit(array('error' => 1, 'msg' => '导入失败！'));
                    }
                }
            } else {
                $this->dexit(array('error' => 1, 'msg' => $upload->getErrorMsg()));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => '文件上传失败！'));
    }

    /*     * *导入客户的列表页**** */

    public function importlist()
    {
        $user_importDb = D('User_import');
        $count_userimportDb = $user_importDb->where('22=22')->count();
        import('@.ORG.system_page');
        $p = new Page($count_userimportDb, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = $user_importDb->where('22=22')->order('id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('userimport', $tmpdatas);
        $this->display();
    }

    /*     * *导入客户的列表页**** */

    public function levellist()
    {
        $user_levelDb = D('User_level');
        $count_userlevelDb = $user_levelDb->count();
        import('@.ORG.system_page');
        $p = new Page($count_userlevelDb, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = $user_levelDb->where('22=22')->order('id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('userlevel', $tmpdatas);
        $this->display();
    }

    /*     * *添加等级**** */

    public function addlevel()
    {
        $levelDb = M('User_level');
        $tmparr = M(_view('User_level'))->where('22=22')->order('level DESC')->find();
        $level = 0;
        if (!empty($tmparr)) {
            $level = $tmparr['level'];
        }
        $level = $level + 1;
        if (IS_POST) {
            $lid = intval($_POST['lid']);
            if (!($lid > 0)) {
                $newdata = array('level' => $level);
            }
            $lname = trim($_POST['lname']);
            if (empty($lname))
                $this->error('等级名称没有填写！');
            $newdata['lname'] = $lname;

            $integral = intval($_POST['integral']);
            if (!($integral > 0))
                $this->error('等级' . $this->config['score_name'] . '没有填写！');
            $newdata['integral'] = $integral;

            $newdata['icon'] = trim($_POST['icon']);
            $newdata['type'] = trim($_POST['fltype']);
            $newdata['boon'] = trim($_POST['boon']);
            $newdata['description'] = trim($_POST['description']);

            if ($lid > 0) {
                $inser_id = $levelDb->where(array('id' => $lid))->save($newdata);
            } else {
                $inser_id = $levelDb->add($newdata);
            }
            if ($inser_id) {
                $this->success('保存成功！');
            } else {
                $this->error('保存失败！');
            }
        } else {
            $lid = intval($_GET['lid']);
            $tmpdata = $levelDb->where(array('id' => $lid))->find();
            if (empty($tmpdata)) {
                $tmpdata = array('id' => 0, 'level' => $level, 'lname' => '', 'integral' => '', 'icon' => '', 'boon' => '', 'type' => 0, 'description' => '');
            }
            $this->assign('leveldata', $tmpdata);
            $this->display();
        }
    }

    public function pay_order()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_name') {
                $condition['order_name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            }
        }


        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['pay_time_str'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        if (isset($_GET['searchstatus'])) {
            $condition['is_pay_bill'] = $_GET['searchstatus'];
        }


        $village_id = $_GET['village_id'];
        if ($village_id) {
            $condition['village_id'] = $village_id;
            $condition['paid'] = 1;
            $condition['pay_type'] = 1;
            $result = D('House_village_pay_order')->get_limit_list_page($condition, 20, true);

            $finshtotal = $total = 0;
            if ($result) {
                foreach ($result['order_list'] as $v) {
                    $total += $v['money'];                                //本页的总额
                    $v['is_pay_bill'] && $finshtotal += $v['money'];    //本页已对账的总额
                }
            }
            $this->assign('finshtotal', $finshtotal);
            $this->assign('total', $total);

            $this->assign('order_list', $result);
        }
        $this->assign('village_id', $village_id);
        $this->display();
    }

    public function change()
    {
        $village_id = $_POST['village_id'];
        $strids = isset($_POST['strids']) ? htmlspecialchars($_POST['strids']) : '';
        if ($strids && $village_id) {
            $array = explode(',', $strids);
            $array && D('House_village_pay_order')->where(array('village_id' => $village_id, 'order_id' => array('in', $array)))->save(array('is_pay_bill' => 1));
        }
        exit(json_encode(array('error_code' => 0)));
    }

    /*     * **删除一条导入的记录**** */

    function delimportuser()
    {
        $idx = (int)trim($_POST['id']);
        $user_importDb = D('User_import');
        if ($user_importDb->where(array('id' => $idx))->delete()) {
            $this->dexit(array('error' => 0));
        } else {
            $this->dexit(array('error' => 1));
        }
    }

    /*     * json 格式封装函数* */

    private function dexit($data = '')
    {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }

    public function v20_village_login()
    {
        $database_village = D('House_village');
        $condition_village['village_id'] = trim($_GET['village_id']);
        $now_village = $database_village->field(true)->where($condition_village)->find();
        if (empty($now_village) || $now_village['status'] == 2) {
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该' . C('config.house_name') . '的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }
        // 是否为平台登录
        $now_village['is_system'] = 1;
        //查询权限 超级管理员赋予所有权限
        $admin_menus = D('House_menu_new')->field(true)->where(array('status' => 1))->select();
        $menus = array();
        foreach ($admin_menus as $value) {
            $menus[] = $value['id'];
        }
        invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => trim($_GET['village_id'])]);
        $now_village['menus'] = $menus;
        session('house', $now_village);
        import("@.ORG.Token");
        // 给予小区管理员身份
        $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_VILLAGE;
        $admin_id = intval($this->system_session['id']);
        $members = trim($_GET['village_id']) . '_' . $admin_id;
        $ticket = Token::createToken($members, $role);
        setcookie('village_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');
        redirect($this->config['site_url'] . '/admin.php?g=System&c=House&a=village_login&village_id=' . $_GET['village_id']);
    }

    public function v20AreaStreetLogin() {
        $database_area_street = D('Area_street');
        $condition_area_street['area_id'] = $_GET['area_id'];
        $nowAreaStreet = $database_area_street->field(true)->where($condition_area_street)->find();
        if (empty($nowAreaStreet) || $nowAreaStreet['is_open'] == 0) {
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该' . C('config.house_name') . '的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }
        // 是否为平台登录
        $nowAreaStreet['is_system'] = 1;
        session('areaStreet', $nowAreaStreet);
        import("@.ORG.Token");
        // 给予小区管理员身份
        if (isset($nowAreaStreet['area_type'])&&$nowAreaStreet['area_type']==1) {
            $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_AREA_COMMUNITY;
        } else {
            $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_AREA_STREET;
        }
        $admin_id = intval($this->system_session['id']);
        $members = trim($_GET['area_id']) . '_' . $admin_id;
        $ticket = Token::createToken($members, $role);
        setcookie('community_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');
        redirect($this->config['site_url'] . '/v20/public/platform/#/community/street_community.iframe/street_index');
    }

    public function village_login()
    {
        $database_village = D('House_village');
        $condition_village['village_id'] = $_GET['village_id'];
        $now_village = $database_village->field(true)->where($condition_village)->find();
        if (empty($now_village) || $now_village['status'] == 2) {
            exit('<html><head><script>window.top.toggleMenu(0);window.top.msg(0,"该' . C('config.house_name') . '的状态不存在！请查阅。",true,5);window.history.back();</script></head></html>');
        }

        // 是否为平台登录
        $now_village['is_system'] = 1;
        //查询权限 超级管理员赋予所有权限
        $admin_menus = D('House_menu_new')->field(true)->where(array('status' => 1))->select();
        $menus = array();
        foreach ($admin_menus as $value) {
            $menus[] = $value['id'];
        }
        invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => trim($_GET['village_id'])]);
        $now_village['menus'] = $menus;
        session('house', $now_village);
        import("@.ORG.Token");
        // 给予小区管理员身份
        $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_VILLAGE;
        $admin_id = intval($this->system_session['id']);
        $members = trim($_GET['village_id']) . '_' . $admin_id;
        $ticket = Token::createToken($members, $role);
        setcookie('village_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');
        $url = $this->config['site_url'] . '/v20/public/platform/#/village/village.iframe/house_index_index';
        redirect($url);
    }

    public function property_login()
    {
        $database_property = D('House_property');
        $condition_village['id'] = $_GET['property_id'];
        $now_property = $database_property->field(true)->where($condition_village)->find();
        $now_property['login_identify'] = 1;
        session('property', $now_property);

        // 给予物业管理员身份
        $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_PROPERTY;
        $admin_id = intval($this->system_session['id']);
        $members = trim($_GET['property_id']) . '_' . $admin_id;
        $ticket = Token::createToken($members, $role);
        setcookie('property_access_token', $ticket, $_SERVER['REQUEST_TIME'] + 10000000, '/');

        $database_property->where($condition_village)->data(array('last_time' => time()))->save();
        $url = $this->config['site_url'] . '/v20/public/platform/#/property/property.iframe/property_index';
        redirect($url);
//        redirect($this->config['site_url'].'/shequ.php?g=House&c=NewProperty&a=index_new');

    }

    public function property_login_v20()
    {
        $database_property = D('House_property');
        $condition_village['id'] = $_GET['property_id'];
        $now_property = $database_property->field(true)->where($condition_village)->find();
        $now_property['login_identify'] = 1;
        session('property', $now_property);

        // 给予物业管理员身份
        $role = D('CommunityAdmin')::SYSTEM_ADMIN_TO_PROPERTY;
        $admin_id = intval($this->system_session['id']);
        $members = trim($_GET['property_id']) . '_' . $admin_id;
        $ticket = Token::createToken($members, $role);
        setcookie('property_access_token', $ticket, -1, '/');

        $database_property->where($condition_village)->data(array('last_time' => time()))->save();

        $returnArr = [
            'status' => 1
        ];
        $returnArr['url'] = $this->config['site_url'] . '/v20/public/platform/#/property/property.iframe/property_index';
        $returnArr['ticket'] = $ticket;
        exit(json_encode(['status' => 0, 'data' => $returnArr]));
    }


    public function market_order()
    {
        $village_id = isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $where = "village_id='$village_id' AND (status=2 OR status=3)";//array('village_id' => 0, 'status' => array('in', array(2,3)));

//		$begin_time = isset($_POST['begin_time']) ? $_POST['begin_time'] : 0;
//		$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : 0;
        $begin_time = isset($_POST['begin_time']) ? $_POST['begin_time'] : date('Y-m-d', time());
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : date('Y-m-d', time());
        $this->assign(array('village_id' => $village_id, 'begin_time' => $begin_time, 'end_time' => $end_time));

        if ($begin_time && $end_time) {
            $where .= ' AND pay_time>' . strtotime($begin_time) . ' AND pay_time<' . strtotime($end_time . '23:59:59');
        }
        $result = D("Shop_order")->get_order_list($where, "order_id DESC", 3, false);
        $this->assign($result);
        $this->display();
    }

    //不同业务对账返点
    public function merchant_order()
    {
        $type = isset($_GET['type']) && $_GET['type'] ? trim($_GET['type']):'group';
        $village_id = isset($_GET['village_id']) && $_GET['village_id'] ? intval($_GET['village_id']):0;
        $time_condition = '';
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            //$condition['_string']=$time_condition;
            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }


        //$village = D('House_village')->where(array('village_id' => $village_id))->find();
        // 小区业务返点
        $village_info = D('House_village')->where(['village_id' => $village_id])->field('property_id,rebate_percent')->find();
        $property_info=D('House_property')->where(['id' => $village_info['property_id']])->field('rebate_percent')->find();
        $cfrom='平台返点';
        if ($village_info['rebate_percent'] > 0) {
            $cfrom='小区返点';
            $rebate_percent = $village_info['rebate_percent'];
        } elseif ($property_info && $property_info['rebate_percent']>0) {
            $cfrom='物业返点';
            $rebate_percent = $property_info['rebate_percent'];
        } else {
            $rebate_percent = C('config.house_money_percent');
        }
        $rebate_percent=$rebate_percent>0 ? $rebate_percent:0;
        $this->assign('rebate_percent', $rebate_percent);
        $order_list = D('House_village_group')->get_order_list($type, $village_id, $time_condition, 1);
        $total_fact_commission=0;
        if($rebate_percent>0 && isset($order_list['total_fact']) && ($order_list['total_fact']>0)){
            $total_fact_commission=($rebate_percent*$order_list['total_fact'])/100;
            $total_fact_commission=round($total_fact_commission,2);
        }
        $total_fact_commission=$total_fact_commission>0 ? $total_fact_commission:0;
        $this->assign('total_fact_commission',$total_fact_commission);
        $this->assign('cfrom',$cfrom);
        $this->assign($order_list);
        $this->display();
    }

    protected function get_alias_c_name()
    {
        $c_name = array(
            'all' => '所有分类',
            'sqrecharge' => '充值',
            'withdraw' => '提现',
            'village_pay' => '小区缴费',
            'express' => '快递代送',
        );

        return $c_name;
    }

    public function village_money_export()
    {
        $villge_id = $_GET['village_id'];
        //$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';

        $type = 'income';
        $title = '';

        switch ($type) {

            case 'income':
                $title = '收入明细';
                break;


        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);


        $objExcel->getActiveSheet()->setTitle($type);
        $objActSheet = $objExcel->getActiveSheet();
        $cell_income = array('type' => '类型', 'order_id' => '订单编号', 'num' => '数量', 'money' => '金额', 'use_time' => '对账时间', 'system_take' => '平台佣金', 'percent' => '佣金百分比', 'now_village_money' => '当前小区余额', 'desc' => '描述');
        // 开始填充头部
        $cell_name = 'cell_' . $type;
        $cell_count = count($$cell_name);
        $cell_start = 1;
        for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
            if ($cell_start > $cell_count) {
                break;
            }
            $col_char[] = $f;
        }
        $col_k = 0;
        foreach ($$cell_name as $key => $v) {
            $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
            $objActSheet->setCellValue($col_char[$col_k] . '1', $v);
            $col_k++;
        }
        $i = 2;
        //if($type=='income'){
        $where['village_id'] = $villge_id;
        if ($_GET['order_type'] && $_GET['order_type'] != 'all') {
            $where['type'] = $_GET['order_type'];
        }
        if ($_GET['order_id']) {
            $where['order_id'] = $_GET['order_id'];
        }


        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));

            $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $where['_string'] = $time_condition;
        }
        $result = M('Village_money_list')->field('type,order_id,num,pow(-1,income+1)*money as money,use_time,desc,system_take,percent,now_village_money')->where($where)->order('use_time DESC')->select();

        //}
        $alias_name = $this->get_alias_c_name();
        foreach ($result as $row) {
            $col_k = 0;
            foreach ($$cell_name as $k => $vv) {
                switch ($k) {
                    case 'type':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $alias_name[$row[$k]] . ' ');
                        break;
                    case 'order_id':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                        break;
                    case 'real_orderid':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                        break;
                    case 'orderid':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                        break;
                    case 'pay_time':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] ? date('Y-m-d H:i:s', $row[$k]) : '');
                        break;
                    case 'use_time':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] ? date('Y-m-d H:i:s', $row[$k]) : '');
                        break;
                    case 'desc':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                        break;
                    default:
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
                        break;
                }
                $col_k++;
            }
            if ($type != 'income') {
                $objActSheet->setCellValue($col_char[$cell_count - 1] . $i, $row['balance_pay'] + $row['coupon_price'] + $row['score_deducte'] + $row['payment_money']);
            }
            $i++;
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    public function export()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '小区账单';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_name') {
                $condition['order_name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            }
        }


        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['pay_time_str'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        if (isset($_GET['searchstatus'])) {
            $condition['is_pay_bill'] = $_GET['searchstatus'];
        }

        $village_id = $_GET['village_id'] + 0;
        if ($village_id) {
            $now_village = D('House_village')->get_one($village_id);
            $condition['village_id'] = $village_id;
            $condition['paid'] = 1;
            $count = D('House_village_pay_order')->where($condition)->count();
            $length = ceil($count / 1000);


            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objActSheet->setCellValue('A1', '缴费项');
            $objActSheet->setCellValue('B1', '已缴金额');
            $objActSheet->setCellValue('C1', '支付时间');
            $objActSheet->setCellValue('D1', '业主名');
            $objActSheet->setCellValue('E1', '联系方式');
            $objActSheet->setCellValue('F1', '住址');
            $objActSheet->setCellValue('G1', '编号');
            $objActSheet->setCellValue('H1', '物业服务周期');
            $objActSheet->setCellValue('I1', '自定义内容/赠送物业服务时间');
            $objActSheet->setCellValue('J1', '服务时间');
            $objActSheet->setCellValue('K1', '对账状态');

            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千订单');
                $result = D('House_village_pay_order')->get_limit_list_page($condition, ($i + 1) * 1000, true);
                if (!empty($result['order_list'])) {
                    $index = 2;
                    foreach ($result['order_list'] as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_name']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['money']);
                        $objActSheet->setCellValueExplicit('C' . $index, date('Y-m-d H:i:s', $value['time']));
                        $objActSheet->setCellValueExplicit('D' . $index, $value['username']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['phone']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['address']);
                        $objActSheet->setCellValueExplicit('G' . $index, $value['usernum']);
                        if ($value['property_month_num']) {
                            $objActSheet->setCellValueExplicit('H' . $index, $value['property_month_num'] . '个月');
                        } else {
                            $objActSheet->setCellValueExplicit('H' . $index, '暂无');
                        }

                        if (!empty($value["presented_property_month_num"]) and ($value["diy_type"] == 0)) {
                            $objActSheet->setCellValueExplicit('I' . $index, $value['presented_property_month_num'] . '个月');
                        } elseif ($value["diy_type"] == 1) {
                            $objActSheet->setCellValueExplicit('I' . $index, $value['diy_content']);
                        } else {
                            $objActSheet->setCellValueExplicit('I' . $index, '暂无');
                        }

                        if ($value['property_time_str']) {
                            $objActSheet->setCellValueExplicit('J' . $index, $value['property_time_str']);
                        } else {
                            $objActSheet->setCellValueExplicit('J' . $index, '暂无');
                        }

                        if ($value['is_pay_bill'] == 0) {
                            $objActSheet->setCellValueExplicit('K' . $index, '未对账');
                        } else {
                            $objActSheet->setCellValueExplicit('K' . $index, '已对账');
                        }
                        $index++;
                    }
                }
                sleep(2);
            }
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $now_village['village_name'] . '_' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    public function market_export()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '社区超市账单';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $village_id = isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $where = "village_id='$village_id' AND (status=2 OR status=3)";

        $begin_time = isset($_GET['begin_time']) ? $_GET['begin_time'] : 0;
        $end_time = isset($_GET['end_time']) ? $_GET['end_time'] : 0;

        if ($begin_time && $end_time) {
            $where .= ' AND pay_time>' . strtotime($begin_time) . ' AND pay_time<' . strtotime($end_time . '23:59:59');
        }

        if ($village_id) {
            $now_village = D('House_village')->get_one($village_id);

            $count = D('Shop_order')->where($where)->count();
            $length = ceil($count / 1000);

            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '订单编号');
            $objActSheet->setCellValue('B1', '下单人');
            $objActSheet->setCellValue('C1', '电话');
            $objActSheet->setCellValue('D1', '支付时间');
            $objActSheet->setCellValue('E1', '总价');
            $objActSheet->setCellValue('F1', '订单状态');
            $objActSheet->setCellValue('G1', '支付情况');

            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千订单');

                $objActSheet->getColumnDimension('A')->setWidth(50);
                $objActSheet->getColumnDimension('B')->setWidth(50);
                $objActSheet->getColumnDimension('C')->setWidth(50);
                $objActSheet->getColumnDimension('D')->setWidth(50);
                $objActSheet->getColumnDimension('E')->setWidth(50);
                $objActSheet->getColumnDimension('F')->setWidth(50);
                $objActSheet->getColumnDimension('G')->setWidth(50);

                $result = D("Shop_order")->get_order_list($where, "order_id DESC", 3, false);
                if (!empty($result['order_list'])) {
                    $index = 2;
                    foreach ($result['order_list'] as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['username']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['userphone']);
                        $objActSheet->setCellValueExplicit('D' . $index, date('Y-m-d H:i:s', $value['pay_time']));
                        $objActSheet->setCellValueExplicit('E' . $index, $value['price']);
                        $objActSheet->setCellValueExplicit('F' . $index, strip_tags($value['status_str']));
                        $objActSheet->setCellValueExplicit('G' . $index, $value['pay_type_str']);
                        $index++;
                    }
                }
                sleep(2);
            }
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $now_village['village_name'] . '_' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    //小区余额  余额列表
    public function village_withdraw()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_village['village_id'] = $where['village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_village['account'] = $where['accounts'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_village['village_name'] = $where['village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_village['property_phone'] = $where['property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        $searchstatus=0;
        if (isset($_GET['withdraw_status'])) {
            $searchstatus = intval($_GET['withdraw_status']);
            /*
            switch ($searchstatus) {
                case 1:
                    $condition_village['w.status'] = array('in', '0,4'); //待体现
                    break;
            }
            */
        }
        //$condition_village['province_id'] = $_GET['province_idss'];
        if ($_GET['province_idss'] && $this->config['many_city']) {
            $condition_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
        }
        if ($_GET['city_idsss'] && $this->config['many_city']) {
            $condition_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $condition_village['area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $condition_village[$area_index] = $this->system_session['area_id'];
        }
        $village_withdraw_list = D('Village_money_list');
        $where['status']=array('neq',5);
        $all_money = $village_withdraw_list->get_all_village_money($where);

        $this->assign('all_money', $all_money ? $all_money : 0);
        
        if ($_GET['export']) {
            $this->village_withdraw_export($condition_village);
            exit;
        }
        $condition_village['m.status']=array('neq',5);
        $village_withdraw_list = $village_withdraw_list->get_village_withdraw_list($condition_village,15,$searchstatus);

        $this->assign($village_withdraw_list);
        $this->display();
    }

    public function village_withdraw_export($condition_village)
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '' . C('config.house_name') . '余额记录';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $where=array();
        if ($condition_village['province_id']) {
            $province_id = $condition_village['province_id'];
            $where['a.area_pid'] = $condition_village['province_id'];
            unset($condition_village['province_id']);
            $where = array_merge($where, $condition_village);
            $count_merchant = M("Merchant")->join("as m left join " . C('DB_PREFIX') . 'area as a ON m.city_id = a.area_id')->where($where)->count();
        } else {
            $count_merchant = M("Merchant as m")->where($condition_village)->count();
        }


        $length = ceil($count_merchant[0]['tp_count'] / 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个订单信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);

            $objActSheet->setCellValue('A1', '编号');
            $objActSheet->setCellValue('B1', C('config.house_name') . '名称');
            $objActSheet->setCellValue('C1', '联系电话');
            $objActSheet->setCellValue('D1', '最近待兑现');
            $objActSheet->setCellValue('E1', C('config.house_name') . '余额');
            $objActSheet->setCellValue('F1', '最近提现时间');


            if ($province_id) {
                $where['status']=array('neq',5);
                $mer_withdraw_list = M('House_village')->join('as m left join ' . '(SELECT  village_id,SUM(money) AS  withdraw_money,withdraw_time,status as withdraw_status FROM pigcms_village_withdraw WHERE status in (0,4)  GROUP BY village_id) w ON m.village_id = w.village_id left join ' . C('DB_PREFIX') . 'area as a on a.area_id = m.city_id')
                    ->field('m.village_id,m.property_phone as phone,m.village_name as name,m.money,w.withdraw_time,w.withdraw_money ')
                    ->where($where)
                    ->order('m.money DESC')
                    ->group('m.village_id')
                    ->limit($i * 1000, 1000)
                    ->select();
            } else {
                $condition_village['m.status']=array('neq',5);
                $mer_withdraw_list = M('House_village')->join('as m left join ' . '(SELECT  village_id,SUM(money) AS  withdraw_money,withdraw_time,status as withdraw_status FROM pigcms_village_withdraw WHERE status in (0,4)  GROUP BY village_id) w ON m.village_id = w.village_id ')
                    ->field('m.village_id,m.property_phone as phone,m.village_name as name,m.money,w.withdraw_time,w.withdraw_money ')
                    ->where($condition_village)
                    ->order('m.money DESC')
                    ->group('m.village_id')
                    ->limit($i * 1000, 1000)
                    ->select();
            }

            if (!empty($mer_withdraw_list)) {
                $index = 1;
                foreach ($mer_withdraw_list as $value) {

                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['village_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['withdraw_money'] ? $value['withdraw_money'] / 100 : '');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['withdraw_time'] ? date('Y-m-d H:i:s', $value['withdraw_time']) : '');
                }
            }
            sleep(2);

        }


        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    //抽成列表
    public function village_percentage()
    {

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_village['village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_village['account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_village['village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_village['property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

//            if ($this->system_session['area_id']) {
//                $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
//                $condition_village[$area_index] = $this->system_session['area_id'];
//            }
        if ($_GET['area_id']) {
            $condition_village['area_id'] = $_GET['area_id'];
        }
        if ($_GET['city_id']) {
            $condition_village['city_id'] = $_GET['city_id'];
        }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_village['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }
        $village_withdraw_list = D('Village_money_list');
        $all_money = $village_withdraw_list->get_all_percent_money($condition_village);
        $all_score = $village_withdraw_list->get_all_score($condition_village);
        $this->assign('all_money', $all_money);
        $this->assign('all_score', $all_score);
        $village_percentage_list = $village_withdraw_list->get_village_percentage_list($condition_village);

        $this->assign('result', $village_percentage_list);
        $this->display();
    }

    public function village_withdraw_list()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_village['w.village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_village['m.account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_village['m.village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_village['m.property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }


        $condition_village['w.status'] = 1;
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (w.withdraw_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_village['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        import('@.ORG.system_page');
        $count = M('Village_withdraw')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'house_village m ON m.village_id = w.village_id ')->where($condition_village)->count();

        $p = new Page($count, 20);
        $withdraw_list = M('Village_withdraw')->field('w.id,w.village_id,w.withdraw_time,w.money,m.village_name as name,m.account,m.property_phone as phone')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'house_village m ON m.village_id = w.village_id ')->where($condition_village)->order('w.withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();

        $pagebar = $p->show();

        $all_money = M('Village_withdraw')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'house_village m ON m.village_id = w.village_id ')->where($condition_village)->sum('w.money');
        $this->assign('all_money', floatval($all_money / 100));
        $this->assign('withdraw_list', $withdraw_list);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function withdraw_info()
    {

        if (isset($_POST['begin_time']) && isset($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (withdraw_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            // $condition['_string']=$time_condition;
            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }
        $village_id = I('village_id');
        $status = I('status');
        $village = D('House_village')->field(true)->where(array('village_id' => $village_id))->find();
        $withdraw_list = D('Village_money_list')->get_withdraw_list($village_id, 1, $status, $time_condition);

        foreach ($withdraw_list['withdraw_list'] as &$v) {
            //  if($v['status']==4){
            $tmp = M('Withdraw_list')->where(array('type' => 'village', 'withdraw_id' => $v['id']))->find();

            $v['account'] = $tmp['account'];
            $v['account_detail'] = $tmp['remark'];
            //  }
        }
        $this->assign('now_village', $village);
        $this->assign('village_id', $village_id);
        $this->assign('status', $status);
        $this->assign('un_withdraw_list', $withdraw_list['withdraw_list']);
        $this->assign('pagebar', $withdraw_list['pagebar']);
        $this->display();
    }

    public function withdraw_order_info()
    {
        $withdraw = M('Village_withdraw')->where(array('id' => $_GET['id']))->find();
        $now_village = M('Village')->where(array('village_id' => $withdraw['village_id']))->find();
        $this->assign('withdraw', $withdraw);
        $this->assign('now_village', $now_village);
        $this->display();
    }

    public function village_money_list()
    {
        if (!empty($_POST['order_id'])) {
            if (empty($_POST['order_type'])) {
                $this->error_tips("没有选分类");
            }
            if ($_POST['order_type'] == 'all') {
                $this->error("请先选择业务分类");
            } else if ($_POST['order_type'] == 'withdraw') {
                $condition['id'] = $_POST['order_id'];
            } else {
                $condition['order_id'] = $_POST['order_id'];
            }
        }


        $this->assign('order_id', $_POST['order_id']);
        $this->assign('order_type', $_POST['order_type']);
        $_POST['order_type'] != '' && $condition['type'] = $_POST['order_type'];
        if($_POST['order_type']=='village_pay'){
            $condition['type']=array('in',array('village_pay','village_new_pay'));
        }
        if ($_POST['order_type'] == 'all') {
            unset($condition['type']);
        }
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['_string'] = $time_condition;
            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }
        $village_id = I('village_id');
        if (!$_GET['page']) {
            $_SESSION['condition'] = $condition;
            $_SESSION['condition_villageid'] = $village_id;
        } else {
            $village_id = $_SESSION['condition_villageid'];
        }

        $res = D('Village_money_list')->get_income_list($village_id, 1, $condition);
        $village = D('House_village')->field(true)->where(array('village_id' => $village_id))->find();

        $this->assign('now_village', $village);
        $this->assign('village_id', $village_id);
        $this->assign('income_list', $res['income_list']);
        $alias_name = D('Village_money_list')->get_alias_c_name();
        unset($alias_name['all']);
        $this->assign('alias_name', $alias_name);
        $this->assign('pagebar', $res['pagebar']);
        $this->display();
    }

    public function agree_withdraw()
    {
        if (D('Village_money_list')->agree($_GET['village_id'], $_GET['id'])) {
            $this->success('保存成功！');
        } else {
            $this->error_tips('保存失败！');
        }
    }

    public function reject_withdraw()
    {
        $res = D('Village_money_list')->reject($_GET['village_id'], $_GET['id']);
        if (!$res['error_code']) {
            $this->success('保存成功！');
        } else {
            $this->error_tips($res['msg']);
        }
    }

    public function edit_reason()
    {
        if (IS_POST) {
            if (empty($_POST['reason'])) {
                $this->error('理由不能为空！');
            }
            $res = D('Village_money_list')->reject($_POST['village_id'], $_POST['id'], $_POST['reason']);

            if (!$res['error_code']) {
                $this->success('保存成功！');
            } else {
                $this->error($res['msg']);
            }
        } else {
            $this->assign('id', $_GET['id']);
            $this->assign('village_id', $_GET['village_id']);
            $this->display();
        }

    }

    public function edit_withdraw()
    {
        $this->assign('id', I('id'));
        $this->assign('village_id', I('village_id'));
        $now_withdraw = M('Village_withdraw')->where(array('id' => I('id'), 'village_id' => I('village_id')))->find();
        $this->assign('now_withdraw', $now_withdraw);
        if (IS_POST) {
            if (empty($_POST['remark'])) {
                $this->error('理由不能为空！');
            }
            $is_online=isset($_POST['is_online']) ? intval($_POST['is_online']):0;
            if ($_POST['money'] > $now_withdraw['money']) {
                $this->error('修改的金额不能大于用户提现的金额！');
            }
            if($now_withdraw['withdraw_type']=='wx'){
                $is_online=1;
                $date_village = [];
                $param=array();
                $param['money'] = $now_withdraw['money'];
                $param['openid'] = $now_withdraw['account'];
                $param['name'] = trim($now_withdraw['name']);
                $param['withdraw_name'] = "{$now_withdraw['name']}提现";
                $param['partner_trade_no'] = date('YmdHis').'000'.$now_withdraw['village_id'];
                //开启了V3支付并且设置了V3秘钥
                if(C('config.withdraw_open_apiv3') == 1 && !empty(C('config.withdraw_weixin_apiv3'))){
                    import('@.ORG.withdraw.WeixinPayV3');
                    $weixinObj = new WeixinPayV3();
                    $ret = $weixinObj->withdraw($param);
                    fdump_api(['msg' => 'V3提现结果','ret' => $ret,'param' => $param],'withdraw/edit_withdraw',1);
                    $result=$ret['result'];
                    if($result && is_array($result) && isset($result['batch_id']) && !empty($result['batch_id'])){
                        $date_village['third_id'] = $result['batch_id'];
                    }elseif($result && is_array($result) && isset($result['transfer_bill_no']) && !empty($result['transfer_bill_no'])){
                        $date_village['third_id'] = $result['transfer_bill_no'];
                    }else {
                        $msg='提现失败！错误码：'.$result['code'];
                        if(isset($result['message']) && !empty($result['message'])){
                            $msg=$result['message'];
                        }else if(isset($result['err_code_des']) && !empty($result['err_code_des'])){
                            $msg=$result['err_code_des'];
                        }
                        $this->error($msg);
                        exit();
                    }
                }else{
                    import('@.ORG.withdraw.Weixin');
                    $weixinObj = new Weixin();
                    $ret = $weixinObj->withdraw($param);
                    fdump_api(['msg' => '提现结果','ret' => $ret,'param' => $param],'withdraw/edit_withdraw',1);
                    $result=$ret['result'];
                    if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
                        $date_village['third_id'] = $result['payment_no'];
                    }else {
                        $msg='提现失败！错误码：'.$result['err_code'];
                        if(isset($result['err_code_des']) && !empty($result['err_code_des'])){
                            $msg=$result['err_code_des'];
                        }else if(isset($result['return_msg']) && !empty($result['return_msg'])){
                            $msg=$result['return_msg'];
                        }
                        $this->error($msg);
                        exit();
                    }
                }
                $extra_data=array();
                $extra_data['openid']=$now_withdraw['account'];
                $extra_data['withdraw_type']='wx';
                $extra_data['param']=$param;
                $extra_data['result']=$result;
                // 修改状态
                $date_village['online']=1;
                $date_village['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                
                M('Village_withdraw')->where(['id'=>$now_withdraw['id']])->save($date_village);
            }
            $res = D('Village_money_list')->agree($_POST['village_id'], $_POST['money'], $_POST['id'], $_POST['remark'],$is_online);
            if ($_POST['is_online']) {
                $res = D('House_village')->where(array('village_id' => $_POST['village_id']))->find();
                $data['pay_type'] = 'village';
                $data['pay_id'] = $_POST['village_id'];
                $data['phone'] = $res['property_phone'];
                $data['money'] = $_POST['money'];
                $data['desc'] = '小区(' . $res['village_name'] . ')申请提现|转账 ' . (float)($_POST['money'] / 100) . C('config.Currency_txt') . '';
                $data['status'] = 0;
                $data['add_time'] = time();
                M('Companypay')->add($data);
            }
            if (!$res['error_code']) {
                $this->success('保存成功！');
            } else {
                $this->error($res['msg']);
            }
        } else {
            $this->display();
        }

    }

    protected function get_alias_name()
    {
        $c_name = array(
            'all' => '选择分类',
            'withdraw' => '提现',
            'sqrecharge' => '充值',
            'village_pay' => '小区缴费',
            'express' => '快递代送',
            'village_pay_cashier' => '小区收银台缴费',
        );

        return $c_name;
    }

    //提现导出excel
    public function export_withdraw()
    {

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_village['w.village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $condition_village['m.account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'name') {
                $condition_village['w.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_village['m.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        $condition_village['w.status'] = 1;
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (w.withdraw_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_village['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        $count = M('Village_withdraw')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'house_village m ON m.village_id = w.village_id ')->where($condition_village)->order('w.withdraw_time DESC')->count();
        $withdraw_list = M('Village_withdraw')->field('w.id,w.village_id,w.withdraw_time,w.money,m.village_name as name,m.account,m.property_phone as phone')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'house_village m ON m.village_id = w.village_id ')->where($condition_village)->order('w.withdraw_time DESC')->select();
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '导出提现数据';
        if ($_GET['begin_time'] && $_GET['end_time']) {
            $title .= '(' . $_GET['begin_time'] . '至' . $_GET['end_time'] . ')';
        }
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个提现信息');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '小区编号');
            $objActSheet->setCellValue('B1', '小区名称');
            $objActSheet->setCellValue('C1', '联系电话');
            $objActSheet->setCellValue('D1', '提现金额');
            $objActSheet->setCellValue('E1', '提现时间');

            if (!empty($withdraw_list)) {
                $index = 2;
                foreach ($withdraw_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['village_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['phone'] . ' ');
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($value['money'] / 100) . ' ');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['withdraw_time'] ? date('Y-m-d H:i:s', $value['withdraw_time']) : '');
                    $index++;
                }
            }
            sleep(2);
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Ymd h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    /*
	 * 开房门广告
	 * */

    public function open_door_adver()
    {
        $where = array();
        if (isset($_GET['intelligent_hardware_type']) && $_GET['intelligent_hardware_type'] != -1) {
            $where['intelligent_hardware_type'] = intval($_GET['intelligent_hardware_type']);
        }
        if (isset($_GET['advert_type']) && $_GET['advert_type'] != -1) {
            $where['advert_type'] = intval($_GET['advert_type']);
        }
        $d_house_open_door_adver = D('House_open_door_adver');
        $intelligent_hardware_type_arr = $d_house_open_door_adver->intelligent_hardware_type_arr;
        $advert_type_arr = $d_house_open_door_adver->advert_type_arr;
        $equipment_type_arr=$d_house_open_door_adver->equipmentTypeArr();
        import('@.ORG.system_page');
        $count = M('House_open_door_adver')->where($where)->count();
        $p = new Page($count, 20);
        $adver_list = M('House_open_door_adver')->where($where)->order('add_time DESC')->limit($p->firstRow, $p->listRows)->select();
        if ($adver_list) {
            foreach ($adver_list as &$val) {
                $where_record = array(
                    'adver_id' => $val['id'],
                );
                $record_total = M('House_open_door_adver_statistics')->where($where_record)->field('SUM(exposure_number) as total_exposure_number,SUM(click_num) as total_click_num')->find();
                if ($record_total && $record_total['total_exposure_number']) {
                    $val['total_exposure_number'] = $record_total['total_exposure_number'];
                } else {
                    $val['total_exposure_number'] = 0;
                }
                if ($record_total && $record_total['total_click_num']) {
                    $val['total_click_num'] = $record_total['total_click_num'];
                } else {
                    $val['total_click_num'] = 0;
                }
                if($val['equipment_type'] == '61'){
                    $a185_url=unserialize($val['a185_url']);
                    $val['android_pic']=$a185_url['android_pic'] ? $a185_url['android_pic'] : '';
                }
                //todo 查询对应设备类型
                $type_arr= $equipment_type_arr[$val['intelligent_hardware_type']];
                $device_type='';
                if($type_arr){
                    foreach ($type_arr as $v2){
                        if($v2['value'] == $val['equipment_type']){
                            $device_type=$v2['name'];
                            break;
                        }
                    }
                }
                $val['device_type']=$device_type;
            }
        }
        $pagebar = $count > 0 ? $p->show() : '';
        $this->assign('adver_list', $adver_list);
        $this->assign('pagebar', $pagebar);

        $this->assign('intelligent_hardware_type_arr', $intelligent_hardware_type_arr);
        $this->assign('advert_type_arr', $advert_type_arr);
        $this->display();
    }

    public function door_adver_statistics()
    {
        $adver_id = I('adver_id', 0, 'intval');
        $where = array();
        $where['adver_id'] = $adver_id;
        import('@.ORG.system_page');
        $count = M('House_open_door_adver_statistics')->where($where)->count();
        $p = new Page($count, 20);
        $list = M('House_open_door_adver_statistics')->where($where)->order('add_time DESC, log_id DESC')->limit($p->firstRow, $p->listRows)->select();
        $pagebar = $count > 0 ? $p->show() : '';
        fdump($list, '$list');
        $this->assign('list', $list);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }



    public function open_door_adver_add(){
        if(IS_POST){
            $radio_185='';
            if(isset($_POST['radio_185'])){
                $radio_185=$_POST['radio_185'];
                unset($_POST['radio_185']);
            }
            if($_POST['equipment_type'] == '61'){
                if(empty($_POST['android_pic']) && in_array($_POST['advert_type'],[0,2])){
                    $this->frame_error_tips('请上传图片');
                }
                if(empty($_POST['android_video']) && in_array($_POST['advert_type'],[1,2])){
                    $this->frame_error_tips('请上传视频');
                }

                switch ($_POST['advert_type']) {
                    case 0:
                        $_POST['a185_url']=serialize(['android_type'=>false,'android_pic'=>$_POST['android_pic']]);
                        break;
                    case 1:
                        $_POST['a185_url']=serialize(['android_type'=>true,'android_video'=>$_POST['android_video']]);
                        break;
                    case 2:
                        $a185_url=array(
                            'android_pic'=>$_POST['android_pic'],
                            'android_video'=>$_POST['android_video']
                        );
                        unset($a185_url[$_POST['radio_185_sort']]);
                        $a185_url[$_POST['radio_185_sort']]= $_POST[$_POST['radio_185_sort']];
                        $a185_url['android_type']=($_POST['radio_185_sort'] == 'android_pic') ? false: true;
                        $_POST['a185_url']=serialize(array_reverse($a185_url));
                        break;
                }

                unset($_POST['android_pic'],$_POST['android_video'],$_POST['radio_185_sort']);
            }else{
                if(empty($_POST['android_pic'])){
                    $this->frame_error_tips('请上传图片/视频');
                }
            }
            //if(empty($_POST['name']) || empty($_POST['ios_pic_s']) || empty($_POST['ios_pic_b']) || empty($_POST['android_pic']) || empty($_POST['url'])|| empty($_POST['begin_time'])|| empty($_POST['end_time'])|| empty($_POST['play_time'])){
            if (empty($_POST['name'])) {
//                $this->frame_submit_tips(0,'请填写广告名称');
                $this->error('请填写广告名称');
            }
            if ($_POST['play_time'] < 0) {
//                $this->frame_submit_tips(0,"播放时间请设置大于0的值");
                $this->error('播放时间请设置大于0的值');
            }
            $_POST['begin_time'] = strtotime($_POST['begin_time'] . " 00:00:00");
            $_POST['end_time'] = strtotime($_POST['end_time'] . " 23:59:59");
            if ($_POST['begin_time'] > $_POST['end_time']) {
//                $this->frame_submit_tips(0,"结束时间应大于开始时间");
                $this->error('结束时间应大于开始时间');
            }
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            if ($id = M('House_open_door_adver')->add($_POST)) {
                // 编辑成功处理下对应是否需要重新同步对应设备
                D('House_open_door_adver')->synchronous_hardware_advert($id);
                $this->frame_submit_tips(1, '添加成功');
            } else {
//                $this->frame_submit_tips(0,'添加失败');
                $this->error('添加失败');
            }
        } else {
            $d_house_open_door_adver = D('House_open_door_adver');
            $intelligent_hardware_type_arr = $d_house_open_door_adver->intelligent_hardware_type_arr;
            $equipment_type_arr = $d_house_open_door_adver->equipmentTypeArr();
            $advert_type_arr = $d_house_open_door_adver->advert_type_arr;
            $advert_file_tip = $d_house_open_door_adver->advert_file_tip;
            $this->assign('intelligent_hardware_type_arr', $intelligent_hardware_type_arr);
            $this->assign('equipment_type_arr', $equipment_type_arr);
            $this->assign('advert_type_arr', $advert_type_arr);
            $this->assign('advert_file_tip', $advert_file_tip);

            $this->display();
        }
    }


    //todo 视频分片
    public function upload_video(){
        import('@.ORG.UploadVideo');
        $uploader =  new UploadVideo();
        $rel=$uploader->video();
        $this->ajaxReturn(['status'=>$rel['error'],'data'=>$rel['data']],'JSON');
    }

        //视频文件上传
	public function vupload(){
        set_time_limit (0);
        //关闭缓存
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $filepath = './upload/video/' . date('Y') . '/' . date('m');
        if (!file_exists($filepath)) {
            mkdir($filepath, 0777, true);
        }
        $ip_path = $filepath;
        $save_path = trim($filepath, './');
        import('@.ORG.Vupload');
        $uploader = new Vupload();
        $uploader->set('path', $ip_path);
        //用于断点续传，验证指定分块是否已经存在，避免重复上传
        if (isset($_POST['status'])) {
            if ($_POST['status'] == 'chunkCheck') {
                $target = $ip_path . '/' . $_POST['name'] . '/' . $_POST['chunkIndex'];
                if (file_exists($target) && filesize($target) == $_POST['size']) {
                    die('{"ifExist":1}');
                }
                die('{"ifExist":0}');

            } elseif ($_POST['status'] == 'md5Check') {

                //todo 模拟持久层查询
                $dataArr = array(
                    'b0201e4d41b2eeefc7d3d355a44c6f5a' => 'kazaff2.jpg'
                );

                if (isset($dataArr[$_POST['md5']])) {
                    die('{"ifExist":1, "path":"/' . $dataArr[$_POST['md5']] . '"}');
                }
                die('{"ifExist":0}');
            } elseif ($_POST['status'] == 'chunksMerge') {

                if ($path = $uploader->chunksMerge($_POST['name'], $_POST['chunks'], $_POST['ext'])) {
                    //todo 把md5签名存入持久层，供未来的秒传验证
                    session('video_path', $save_path.'/'.$path);
                    $file_handle = new file_handle();
                    $a=$file_handle->upload('./'.$save_path.'/'.$path);
                    $img=replace_file_domain('/'.$save_path.'/'.$path);
                    die('{"status":1, "path": "'.$img.'"}');
                }
                die('{"status":0}');
            }
        }
        if (($path = $uploader->upload('file', $_POST)) !== false) {
            if (!session('video_path')) {
                session('video_path', $save_path . '/' . $path);
            }
            $file_handle = new file_handle();
            $a=$file_handle->upload('./'.$save_path.'/'.$path);
            $img=replace_file_domain('/'.$save_path.'/'.$path);
            die('{"status":1, "path": "'.$img.'"}');
        }
        die('{"status":0}');
    }

    //删除上传的视频文件
    public function del_video_cache()
    {
        $uniqueFileName = I('uniqueFileName', '', 'trim');
        if (empty($uniqueFileName)) {
            $this->error('网络出错，请重试！');
        }
        $ip_path = './upload/video';
        $filepath = $ip_path . '/' . $uniqueFileName;
        if (is_dir($filepath) || !is_file($filepath . '.tmp')) {
            $this->deleteDir($filepath);
            unlink($filepath . '.tmp');
        }
        $this->success('删除成功');
    }

    //删除文件夹
    private function deleteDir($dir)
    {
        if (!$handle = @opendir($dir)) {
            return false;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    deleteDir($file);
                } else {
                    @unlink($file);
                }
            }
        }
        @rmdir($dir);
    }

    public function open_door_adver_edit()
    {
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error( '请填写广告名称');
            }
            if($_POST['equipment_type'] == '61'){
                if(empty($_POST['android_pic']) && in_array($_POST['advert_type'],[0,2])){
                    $this->frame_error_tips('请上传图片');
                }
                if(empty($_POST['android_video']) && in_array($_POST['advert_type'],[1,2])){
                    $this->frame_error_tips('请上传视频');
                }

                switch ($_POST['advert_type']) {
                    case 0:
                        $_POST['a185_url']=serialize(['android_type'=>false,'android_pic'=>$_POST['android_pic']]);
                        break;
                    case 1:
                        $_POST['a185_url']=serialize(['android_type'=>true,'android_video'=>$_POST['android_video']]);
                        break;
                    case 2:
                        $a185_url=array(
                            'android_pic'=>$_POST['android_pic'],
                            'android_video'=>$_POST['android_video']
                        );
                        unset($a185_url[$_POST['radio_185_sort']]);
                        $a185_url[$_POST['radio_185_sort']]= $_POST[$_POST['radio_185_sort']];
                        $a185_url['android_type']=($_POST['radio_185_sort'] == 'android_pic') ? false: true;
                        $_POST['a185_url']=serialize(array_reverse($a185_url));
                        break;
                }

                unset($_POST['android_pic'],$_POST['android_video'],$_POST['radio_185_sort']);
            }
            else{
                if(empty($_POST['android_pic'])){
                    $this->frame_error_tips('请上传图片');
                }
            }
            if($_POST['play_time']<0){
                $this->frame_submit_tips(0,"播放时间请设置大于0的值");
            }
            $_POST['begin_time'] = strtotime($_POST['begin_time'] . " 00:00:00");
            $_POST['end_time'] = strtotime($_POST['end_time'] . " 23:59:59");
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            if (M('House_open_door_adver')->where(array('id' => $_POST['id']))->save($_POST)) {
                if($_POST['equipment_type'] == '61'){
                    //todo a185触发下发配置命令
                    D('Face_a185_request_record')->a185_sync_command(false,true);
                }
                // 编辑成功处理下对应是否需要重新同步对应设备
                D('House_open_door_adver')->synchronous_hardware_advert($_POST['id']);
                $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
                if ($judgeFaceXiHanDoorSwitch) {
                    D('Face_door_xihan_service')->xiHanStandbyPicture(0, $_POST['id']);
                }
                $this->success('编辑成功');
            } else {
                $this->error( '编辑失败');
            }
        } else {
            $now_adver = M('House_open_door_adver')->where(array('id' => $_GET['id']))->find();

            if($now_adver['a185_url']){
                $now_adver['a185_url']=unserialize($now_adver['a185_url']);
            }

            $condition_house_village['village_id'] = $now_adver['village_id'];
            $village_info = M('House_village')->where($condition_house_village)->find();

            $this->assign('village_info',$village_info);
            $this->assign('now_adver',$now_adver);

            $d_house_open_door_adver = D('House_open_door_adver');
            $intelligent_hardware_type_arr = $d_house_open_door_adver->intelligent_hardware_type_arr;
            $equipment_type_arr = $d_house_open_door_adver->equipmentTypeArr();
            $advert_type_arr = $d_house_open_door_adver->advert_type_arr;
            $advert_file_tip = $d_house_open_door_adver->advert_file_tip;
            $device_type='';
            if($rr=$equipment_type_arr[$now_adver['intelligent_hardware_type']]){
                foreach ($rr as $v){
                    if($v['value'] == $now_adver['equipment_type']){
                        $device_type=$v['name'];
                    }
                }
            }
            $this->assign('device_type',$device_type);
            $this->assign('intelligent_hardware_type_arr',$intelligent_hardware_type_arr);
            $this->assign('equipment_type_arr',$equipment_type_arr);
            $this->assign('advert_type_arr',$advert_type_arr);
            $this->assign('advert_file_tip',$advert_file_tip);
            $this->assign('equipment_type_val',$d_house_open_door_adver->equipment_type_val);
            $this->display();
        }
    }

    public function open_door_adver_del()
    {
        if (IS_POST) {
            $database_adver_category = D('House_open_door_adver');
            $condition_adver_category['id'] = $_POST['id'];
            $now_adver = M('House_open_door_adver')->where($condition_adver_category)->find();
            if(!$now_adver){
                $this->error('数据不存在');
            }
            if ($database_adver_category->where($condition_adver_category)->delete()) {
                //todo 删除同步触发命令
                if($now_adver['equipment_type'] == '61'){
                    //todo a185触发下发配置命令
                    D('Face_a185_request_record')->a185_sync_command(false,true);
                }
                $this->success('删除成功');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function open_door_adv_village()
    {
        $adver_id = $where['adver_id'] = $_GET['adver_id'];
        $where['status'] = 1;
        $adver_list = M('Adver_village')->where($where)->getField('village_id,status');
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'village_name') {
                $condition_house_village['village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_name') {
                $condition_house_village['property_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_phone') {
                $condition_house_village['property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'account') {
                $condition_house_village['account'] = $_GET['keyword'];
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }

        $database_house_village = D('House_village');
        $count_village = $database_house_village->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $village_list = $database_house_village->field(true)->where($condition_house_village)->order('`village_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('village_list', $village_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        //todo 参数传递
        $this->assign('adver_id', $adver_id);
        $this->assign('adver_list',$adver_list);
        $this->display();
    }

    public function ajax_change_adver_village()
    {
        $where['adver_id'] = $data['adver_id'] = $_POST['adver_id'];
        $where['village_id'] = $data['village_id'] = $_POST['village_id'];
        $status = $data['status'] = $_POST['status'];
        $database_house_open_door_adver=M('House_open_door_adver')->where(array('id' => $_POST['adver_id']))->find();
        if(!$database_house_open_door_adver){
            $this->error( '数据不存在');
        }
        if (M('Adver_village')->where($where)->find()) {
            M('Adver_village')->where($where)->setField('status', $status);
        } else {
            M('Adver_village')->add($data);
        }
        if($database_house_open_door_adver['equipment_type'] == 61){
            //todo a185触发下发配置命令
            D('Face_a185_request_record')->a185_sync_command(false,true);
        }
        // 编辑成功处理下对应是否需要重新同步对应设备
        D('House_open_door_adver')->synchronous_hardware_advert($_POST['adver_id']);
        $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
        if ($judgeFaceXiHanDoorSwitch) {
            D('Face_door_xihan_service')->xiHanStandbyPicture($_POST['village_id']);
        }
    }

    public function ajax_change_adver_village_all()
    {

        $where['adver_id'] = $data['adver_id'] = $_POST['adver_id'];
        $status = $data['status'] = $_POST['status'];
        $_POST['village_id'] && $condition['village_id'] = array('in', $_POST['village_id']);
        $database_house_village = D('House_village');
        $database_house_open_door_adver=M('House_open_door_adver')->where(array('id' => $_POST['adver_id']))->find();
        if(!$database_house_open_door_adver){
            $this->error( '数据不存在');
        }
        $village_list = $database_house_village->where($condition)->field(true)->select();
        foreach ($village_list as $item) {
            $where['village_id'] = $item['village_id'];
            if (M('Adver_village')->where($where)->find()) {
                M('Adver_village')->where($where)->setField('status', $status);
            } else {
                $data['village_id'] = $item['village_id'];
                M('Adver_village')->add($data);
            }
        }
        if($database_house_open_door_adver['equipment_type'] == 61){
            //todo a185触发下发配置命令
            D('Face_a185_request_record')->a185_sync_command(false,true);
        }
        // 编辑成功处理下对应是否需要重新同步对应设备
        D('House_open_door_adver')->synchronous_hardware_advert($_POST['adver_id']);
    }

    /**
     * 短信管理
     */
    public function sms_operate()
    {
        $all_sms_sum = M('Sms_buy_order')->where(['type'=>1,'paid'=>1])->sum('sms_number');
        $all_sms_send = M('Sms_record')->where(['village_id'=>array('gt',0)])->count();
        $all_sms_remain = M('House_village')->where(['now_sms_number'=>array('gt',-2)])->sum('now_sms_number');
        $all_sms_remain =$all_sms_remain>=0 ? $all_sms_remain:0;
        $village_name = $_GET['village_name'];
        $property_name = $_GET['property_name'];
        $where = [];
        if($village_name){
            $where['h.village_name'] = $village_name;
        }
        if($property_name){
            $where['p.property_name'] = $property_name;
        }
        $where['h.status'] = array('neq',5);
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'h.area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'h.city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'h.province_id';
            }
            $where[$area_index] = $this->system_session['area_id'];
        }
        $join = 'left join ' . C('DB_PREFIX') . 'house_property as `p` on p.id=h.property_id';
        $count = M('House_village')->alias('h')->join($join)->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $field='h.village_id,h.village_name,p.property_name,now_sms_number';
        $village_list= M('House_village')->alias('h')->join($join)->field($field)->where($where)->order('h.property_id DESC')->limit($p->firstRow,$p->listRows)->select();
        $res = [];
        $sms_sum = 0;
        $sms_send = 0;
        $sms_remain = 0;
        $list= M('House_village')->alias('h')->join($join)->field($field)->where($where)->order('h.property_id DESC')->select();
        foreach ($list as $value){
            $sms_sum_v = M('Sms_buy_order')->where(['type'=>1,'type_id'=>$value['village_id'],'paid'=>1])->sum('sms_number');
            $sms_send_v = M('Sms_record')->where(['village_id'=>$value['village_id']])->count();
            $sms_sum+=$sms_sum_v;
            $sms_send+=$sms_send_v;
            $sms_remain+=($value['now_sms_number']>-2 ? $value['now_sms_number']:0);
        }
        $sms_remain=$sms_remain>=0 ? $sms_remain:0;
        if($village_list){
            foreach ($village_list as $k=>&$v){
                $res[$k]['property_name'] = $v['property_name'];
                $res[$k]['village_name'] = $v['village_name'];
                $res[$k]['village_id'] = $v['village_id'];
                $sms_sum_v = M('Sms_buy_order')->where(['type'=>1,'type_id'=>$v['village_id'],'paid'=>1])->sum('sms_number');
                $sms_send_v = M('Sms_record')->where(['village_id'=>$v['village_id']])->count();
                $res[$k]['sms_sum'] = $sms_sum_v?$sms_sum_v:0;
                $res[$k]['sms_send'] = $sms_send_v;
                $now_store = D('House_village')->get_one($v['village_id']);
                $res[$k]['sms_remain'] = $now_store['now_sms_number'];
            }
        }
        $this->assign('pagebar',$p->show());
        $this->assign('all_sms_sum',$all_sms_sum?$all_sms_sum:0);
        $this->assign('all_sms_send',$all_sms_send);
        $this->assign('all_sms_remain',$all_sms_remain);
        $this->assign('village_list',$res);
        $this->assign('sms_sum',$sms_sum);
        $this->assign('sms_send',$sms_send);
        $this->assign('sms_remain',$sms_remain);
        $this->display();
    }

    public function sms_note(){
        // 我的短信-查看 权限
        $village_id = $_GET['id'];
        $now_store = D('House_village')->get_one($village_id);
        $this->assign('now_store',$now_store);
        $this->assign('village_id',$village_id);
        if($_GET['type'] == 'buy'){
            $condition_where['type_id']=$village_id;
            $condition_where['type']=1;
            if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
                $condition_where['add_time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
            }elseif($_GET['start_time']){
                $condition_where['add_time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
            }elseif($_GET['end_time']){
                $condition_where['add_time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
            }
            if($_GET['paid'] != ''){
                $condition_where['paid'] = array('eq',$_GET['paid']);
            }else{
                $condition_where['paid'] = array('neq',0);
            }
            $count = D("Sms_buy_order")->where($condition_where)->count();
            import('@.ORG.system_page');
            $p = new Page($count, 10);
            $orderList= M('Sms_buy_order')->where($condition_where)->order('order_id DESC')->limit($p->firstRow,$p->listRows)->select();
            $this->assign('pagebar',$p->show());
            $this->assign('orderList',$orderList);
        }else{
            $condition_where['village_id']=$village_id;
            //
            if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
                $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
            }elseif($_GET['start_time']){
                $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
            }elseif($_GET['end_time']){
                $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
            }
            if($_GET['status'] != ''){
                $condition_where['status'] = array('eq',$_GET['status']);
            }

            if($_GET['type'] == 'village_express'){
                $condition_where['type']= 'village_express';
            }elseif ($_GET['type'] == 'village_vistor') {
                $condition_where['type']= 'village_vistor';
            }

            $count = D("Sms_record")->where($condition_where)->count();
            import('@.ORG.system_page');
            $p = new Page($count, 10);
            $record_list= M('Sms_record')->where($condition_where)->order('pigcms_id DESC')->limit($p->firstRow,$p->listRows)->select();
            $this->assign('pagebar',$p->show());
            $this->assign('record_list',$record_list);
            $status = array('0' =>'发送成功', '-1' => '验证失败未购买', '-2' => '短信不足', '-3' => '操作失败', '-4' => '非法字符', '-5' => '内容过多', '-6' => '号码过多', '-7' => '频率过快', '-8' => '号码内容空', '-9' => '账号冻结', '-10' => '禁止频繁单条发送', '-11' => '系统暂定发送', '-12' => '有错误号码', '-13' => '定时时间不对', '-14' => '账号被锁，10分钟后登录', '-15' => '连接失败', '-16' => '禁止接口发送', '-17' => '绑定IP不正确', '-18' => '系统升级', '-19' => '域名不对', '-20' => 'key不匹配', '-21' => '用户不存在', '-22' => '余额不足', '-100' => '发送的token不合法','-999'=>'频繁发送');
            $this->assign('status', $status);

        }
        $this->display();
    }

    // 短信发送记录 导出
    public function village_sms_send_export(){
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        if($_GET['status'] != ''){
            $condition_where['status'] = array('eq',$_GET['status']);
        }

        $condition_where['village_id'] = $_GET['id'];

        $record_list= M('Sms_record')->where($condition_where)->order('pigcms_id DESC')->select();
        $status = array('0' =>'发送成功', '-1' => '验证失败未购买', '-2' => '短信不足', '-3' => '操作失败', '-4' => '非法字符', '-5' => '内容过多', '-6' => '号码过多', '-7' => '频率过快', '-8' => '号码内容空', '-9' => '账号冻结', '-10' => '禁止频繁单条发送', '-11' => '系统暂定发送', '-12' => '有错误号码', '-13' => '定时时间不对', '-14' => '账号被锁，10分钟后登录', '-15' => '连接失败', '-16' => '禁止接口发送', '-17' => '绑定IP不正确', '-18' => '系统升级', '-19' => '域名不对', '-20' => 'key不匹配', '-21' => '用户不存在', '-22' => '余额不足', '-100' => '发送的token不合法','-999'=>'频繁发送');


        if(count($record_list) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->village['village_name'] . '小区-短信发送记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($record_list)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($record_list) . '条记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '编号');
            $objActSheet->setCellValue('B1', '发送到手机');
            $objActSheet->setCellValue('C1', '发送类型');
            $objActSheet->setCellValue('D1', '发送时间');
            $objActSheet->setCellValue('E1', '发送内容');
            $objActSheet->setCellValue('F1', '类型');
            $objActSheet->setCellValue('G1', '状态');

            if (!empty($record_list)) {
                $index = 2;

                $cell_list = range('A','H');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($record_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value['pigcms_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['phone']);
                    if($value['sendto'] == 'user'){
                        $objActSheet->setCellValueExplicit('C' . $index, '顾客');
                    }else{
                        $objActSheet->setCellValueExplicit('C' . $index, '商家');
                    }
                    $objActSheet->setCellValueExplicit('D' . $index, date('Y-m-d H:i:s', $value['time']));
                    $objActSheet->setCellValueExplicit('E' . $index, $value['text']);

                    if($value['type'] == 'food'){
                        $objActSheet->setCellValueExplicit('F' . $index, '订餐');
                    }else if($value['type'] == 'takeout'){
                        $objActSheet->setCellValueExplicit('F' . $index, '外卖');
                    }else if($value['type'] == 'group'){
                        $objActSheet->setCellValueExplicit('F' . $index, '团购');
                    }else if($value['type'] == 'shop'){
                        $objActSheet->setCellValueExplicit('F' . $index, '快店');
                    }else if($value['type'] == 'village_express'){
                        $objActSheet->setCellValueExplicit('F' . $index, '小区快递');
                    }else if($value['type'] == 'meal'){
                        $objActSheet->setCellValueExplicit('F' . $index, '顺风车');
                    }else if($value['type'] == 'fee_notice'){
                        $objActSheet->setCellValueExplicit('F' . $index, '收费催缴');
                    }


                    $objActSheet->setCellValueExplicit('G' . $index, $status[$value['status']]);

                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

    // 小区-短信购买记录 导出
    public function village_sms_buy_export(){
        $village_id = $_GET['id'];
        $condition_where['type_id']=$village_id;
        $condition_where['type']=1;
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['add_time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['add_time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['add_time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        if($_GET['paid'] != ''){
            $condition_where['paid'] = array('eq',$_GET['paid']);
        }else{
            $condition_where['paid'] = array('neq',0);
        }
        if($_GET['status'] != ''){
            $condition_where['status'] = array('eq',$_GET['status']);
        }
        $orderList= M('Sms_buy_order')->where($condition_where)->order('order_id DESC')->select();
        if(count($orderList) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->village['village_name'] . '小区-短信购买记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($orderList)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($orderList) . '条记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '编号');
            $objActSheet->setCellValue('B1', '订单号');
            $objActSheet->setCellValue('C1', '支付金额');
            $objActSheet->setCellValue('D1', '购买条数');
            $objActSheet->setCellValue('E1', '添加时间');
            $objActSheet->setCellValue('F1', '支付时间');
            $objActSheet->setCellValue('G1', '支付方式');
            $objActSheet->setCellValue('H1', '状态');

            if (!empty($orderList)) {
                $index = 2;

                $cell_list = range('A','H');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($orderList as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['orderid']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['payment_money']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['sms_number']);
                    $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['add_time']));

                    if($value['pay_time']){
                        $objActSheet->setCellValueExplicit('F' . $index, $value['pay_time']);
                    }else{
                        $objActSheet->setCellValueExplicit('F' . $index, ' ');
                    }

                    if($value['pay_type'] == 'yue'){
                        $objActSheet->setCellValueExplicit('G' . $index, '余额');
                    }else if($value['pay_type'] == 'weixin'){
                        $objActSheet->setCellValueExplicit('G' . $index, '微信');
                    }else if($value['pay_type'] == 'system'){
                        $objActSheet->setCellValueExplicit('G' . $index, '管理员操作');
                    }else{
                        $objActSheet->setCellValueExplicit('G' . $index, $value['pay_type']);
                    }

                    if($value['paid'] == '1'){
                        $objActSheet->setCellValueExplicit('H' . $index, '支付成功');
                    }else if($value['paid'] == '0'){
                        $objActSheet->setCellValueExplicit('H' . $index, '未支付');
                    }else{
                        $objActSheet->setCellValueExplicit('H' . $index, '管理员操作');
                    }
                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    public function sms_buy_order()
    {
        import('@.ORG.system_page');
        if ($this->system_session['open_admin_area'] == 1) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }

            $count = D('')->table(array(C('DB_PREFIX') . 'house_village' => 'hv', C('DB_PREFIX') . 'sms_buy_order' => 'sbo'))->where("hv." . $area_index . "= '" . $this->system_session['area_id'] . "' AND sbo.type = 1 AND sbo.type_id = hv.village_id AND sbo.paid >= 1")->field('sbo.*,hv.village_name,hv.village_id')->order('sbo.order_id desc')->count();
            $p = new Page($count, 20);
            $sms_buy_orde_list = D('')->table(array(C('DB_PREFIX') . 'house_village' => 'hv', C('DB_PREFIX') . 'sms_buy_order' => 'sbo'))->where("hv." . $area_index . "= '" . $this->system_session['area_id'] . "' AND sbo.type = 1 AND sbo.type_id = hv.village_id AND sbo.paid >= 1")->field('sbo.*,hv.village_name,hv.village_id')->order('sbo.order_id desc')->select();
        } else {

            $condition_where['paid'] = array('egt', 1);
            $count = D('Sms_buy_order')->where($condition_where)->count();
            $p = new Page($count, 20);

            $sms_buy_orde_list = D('')->table(array(C('DB_PREFIX') . 'house_village' => 'hv', C('DB_PREFIX') . 'sms_buy_order' => 'sbo'))->where("sbo.type = 1 AND sbo.type_id = hv.village_id AND sbo.paid >= 1")->field('sbo.*,hv.village_name,hv.village_id')->order('sbo.order_id desc')->select();
        }
        $this->assign('sms_buy_orde_list', $sms_buy_orde_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //开门监控 基于百度地图
    public function door_notice_map()
    {
        $database_house_village = D('House_village');
        $condition['status'] = 1;
        $village_list = $database_house_village->field('village_id,village_name,village_address,property_name,long,lat,property_phone,wx_image')->where($condition)->order('`village_id` DESC')->select();
//        $open_history  = M('House_village_open_door')->join('h LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = h.uid')->field('h.* ,u.nickname,u.avatar,u.phone')->where(array('open_status'=>array('gt',0)))->limit(30)->order('add_time DESC')->select();
        $door_list = M('House_village_door')->getField('door_device_id,door_id,door_name,village_id');

        $this->assign('village_list_json', json_encode($village_list));
        $this->assign('village_list', $village_list);
//        $this->assign('open_history',$open_history);
        $this->assign('open_status', $this->open_door_status);
        $this->assign('door_list', $door_list);

        $this->display('desk');
    }

    public function search_village()
    {
        if (!empty($_POST['keyword'])) {
            $condition_house_village['village_name'] = array('like', '%' . $_POST['keyword'] . '%');
        }
        $condition_house_village['status'] = 1;
        $database_house_village = D('House_village');
        $village_list = $database_house_village->field(true)->where($condition_house_village)->order('`village_id` DESC')->select();

        echo json_encode(array('village_list' => $village_list));
    }


    public function get_village_name()
    {
        $village_name = $_POST['village_name'];
        $condition_house_village['village_name'] = $village_name;
        $village_info = M('House_village')->where($condition_house_village)->find();
        echo json_encode(array('village' => $village_info));
    }

    //查询开门情况
    public function get_house_open_status()
    {
        $village_list = M('House_village')->where(array('status' => 1))->field(true)->select();
        $start_time = strtotime(date('Y-m-d', $_SERVER['REQUEST_TIME']));
        $end_time = $start_time + 86400;
        $where['_string'] = "add_time>{$start_time} AND add_time";
        foreach ($village_list as $item) {
            $where['village_id'] = $item['village_id'];
            $res = M('House_village_open_door')->where($where)->field('open_status,COUNT(open_status) as counts')->group('open_status')->select();
            $all_count = 0;
            $success_count = 0;
            $tmp = [];
            foreach ($res as $re) {
                $re['status_txt'] = $this->open_door_status[$re['open_status']];

                $all_count += $re['counts'];
                $tmp[$re['open_status']] = $re;
            }

            $success_count = $tmp[0]['counts'];

            $arr[] = array(
                'village_id' => $item['village_id'],
                'status' => $tmp,
                'all_count' => intval($all_count),
                'success_count' => intval($success_count),
                'fail_count' => $all_count - $success_count,
                'now_lng' => $item['long'],
                'now_lat' => $item['lat'],
                'village_name' => $item['village_name'],
                'village_phone' => $item['property_phone'],
            );
        }

        echo json_encode(array('errcode' => 0, 'data' => $arr));
    }

    public function notice_village_manager()
    {
        echo json_encode(array('errcode' => 0));
    }

    public function check_fail_door()
    {
        $last_id = $_POST['new_id'];
        if ($last_id) {
            $where = array('h.id' => array('gt', $last_id));
        }
        $fail_door = M('House_village_open_door')->join('h LEFT JOIN ' . C('DB_PREFIX') . 'user u ON u.uid = h.uid')->field('h.* ,u.nickname,u.avatar,u.phone')->where($where)->limit(10)->order('add_time DESC')->select();
        $door_list = M('House_village_door')->getField('door_device_id,door_id,door_name');
        $condition['status'] = 1;
        $village_list = M('House_village')->where($condition)->getField('village_id,village_name,village_address,property_name,long,lat,property_phone');
        foreach ($fail_door as &$item) {
            $item['add_time'] = date('Y-m-d H:m:s');
            $item['status_txt'] = $this->open_door_status[$item['open_status']];
            $item['door_name'] = $village_list[$item['village_id']]['village_name'] . '(' . ($door_list[$item['door_id']] ? $door_list[$item['door_id']] : '未知') . ')';
            $item['nickname'] = ($item['nickname'] ? $item['nickname'] . ' ' . $item['phone'] : $item['phone']);
        }
        echo json_encode(array('fail_door' => $fail_door));

    }

    //查看用户开门情况
    public function get_user_open_status()
    {
        $uid = $_POST['uid'] = 1616;
        $id = $_POST['id'] = 6319;
        $open_status = M('House_village_open_door')->where(array('id' => $id))->find();
        $now_village = M('House_village')->field('village_id,village_name')->where(array('village_id' => $open_status['village_id']))->find();
        $door_list = D('House_village_door')->get_user_door($uid, $now_village['village_id']);
        $arr['now_village'] = $now_village;

        $arr['success_count'] = M('House_village_open_door')->where(array('open_status' => 0, 'uid' => $uid, 'village_id' => $now_village['village_id']))->count();
        $arr['fail_count'] = M('House_village_open_door')->where(array('open_status' => array('gt', 0), 'uid' => $uid, 'village_id' => $now_village['village_id']))->count();
        $arr['all_count'] = $arr['success_count'] + $arr['fail_count'];
        $arr['success_percent'] = $arr['all_count'] == 0 ? 0 : round($arr['all_count'] / $arr['all_count'] * 100, 2);
        foreach ($door_list as &$item) {
            $item['success_count'] = M('House_village_open_door')->where(array('open_status' => 0, 'uid' => $uid, 'door_device_id' => $item['door_device_id']))->count();
            $item['fail_count'] = M('House_village_open_door')->where(array('open_status' => array('gt', 0), 'uid' => $uid, 'door_device_id' => $item['door_device_id']))->count();
        }
        $arr['door_list'] = $door_list;
        echo json_encode($arr);
    }

    public function property()
    {
        $order_string = '`t_sort` ASC, `id` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {

                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;

            }
        }
        if ($_GET['sort'] == 'lastTime') {
            $style = '<img style="width: 16px;" src="./tpl/System/Static/images/property_xia.png">';
            $this->assign('style', $style);

        }
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'village_name') {
                $condition_house_village['village_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_name') {
                $condition_house_village['property_name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_phone') {
                $condition_house_village['property_phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'property_address') {
                $condition_house_village['property_address'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'account') {
                $condition_house_village['account'] = $_GET['keyword'];
            }
        }


        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }

        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
        } else {
            if ($this->config['many_city'] && $_GET['province_idss']) {
                $condition_house_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
            }
        }
        if ($_GET['area_id']) {
            $condition_house_village['area_id'] = $where['area_id'] = $_GET['area_id'];
        }

        $database_house_property = D('House_property');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $field = "*,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 WHEN (`status` = 2) then 4 ELSE 2 END) as `t_sort`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

        if ($property_list) {
            $propertyIds = array_unique(array_column($property_list,'id'));
            $db_house_community_about = D('House_community_about');
            $where = ['property_id' => ['in', $propertyIds], 'village_id' => 0, 'is_virtual' => 1];
            $aboutInfoArr = $db_house_community_about->where($where)->getField('property_id, facilities_type');
            $is_demo_domain = $this->config['is_demo_domain'] ? $this->config['is_demo_domain'] : 0;
            $is_demo_domain = intval($is_demo_domain);
            foreach ($property_list as &$val) {
                $val['package_order'] = $this->package_order_detail($val['id']);
                if (isset($aboutInfoArr[$val['id']]) && $aboutInfoArr[$val['id']]) {
                    $val['facilities_type_text'] = $db_house_community_about->getFacilitiesTypeText($aboutInfoArr[$val['id']]);
                }
                if ($is_demo_domain == 1 && isset($val['is_saas_user']) && intval($val['is_saas_user']) == 1) {
                    $val['saasText'] = 'Saas';
                }
            }
        }
        fdump_api($property_list, '$property_list');

        $this->assign('property_list', $property_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
//        $this->village_sync_property(); //小区表同步到物业表信息
        $this->display();
    }

    private function package_order_detail($property_id = 0, $package_order = '')
    {
        if (!$package_order) {
            $d_package_order = M('Package_order');
            $where['property_id'] = $property_id;
            $where['status'] = 1;
//            $where['package_end_time'] = ['gt',time()];
            $package_order = $d_package_order->where($where)->order('order_id DESC')->find();
            if (!$package_order) {
                $package_order = '';
                $map['property_id'] = $property_id;
                $map['status'] = 0;
                $map['package_try_end_time'] = ['gt', time()];
                $package_order = $d_package_order->where($map)->order('order_id DESC')->find();
            }
        }
        if ($package_order) {
            $package_order['is_expired']=0;
            $now_time = time();
            if ($package_order['status'] == 0 && $package_order['package_try_end_time'] > 0) {
                if (intval($now_time) > intval($package_order['package_try_end_time'])) {
                    $package_order['tip'] = '已到期(试用结束)';
                    $package_order['font_color'] = 'red';
                    $package_order['is_expired']=1;
                } else {
                    $package_order['tip'] = '试用中';
                    $package_order['font_color'] = 'green';
                }
            } else {
                if (intval($package_order['package_end_time']) <= 0 && floatval($package_order['order_money']) <= 0) {
                    // 如果没有到期时间且没有结束时间 且支付金额为零 计算出到期时间
                    if ($package_order['pay_time']) {
                        $time = $package_order['pay_time'];
                    } elseif ($package_order['create_time']) {
                        $time = $package_order['create_time'];
                    }
                    if ($time) {
                        $set = [
                            'package_end_time' => (1 * 366) * 86400 + intval($time) + 86400
                        ];
                        $where_set = [
                            'order_id' => $package_order['order_id']
                        ];
                        $set = $d_package_order->where($where_set)->data($set)->save();
                        if ($set) {
                            $package_order['package_end_time'] = $set['package_end_time'];
                        }
                    }
                }
                if (intval($now_time) > intval($package_order['package_end_time'])) {
                    $package_order['tip'] = '已到期(套餐结束)';
                    $package_order['font_color'] = 'red';
                    $package_order['is_expired']=1;
                } else {
                    $package_order['tip'] = date('Y-m-d', $package_order['package_end_time']);
                    $package_order['font_color'] = 'green';
                }
            }
        } else {
            $package_order = [];
        }
        return $package_order;
    }

    // 物业套餐详情
    public function package_order()
    {
        $order_id = intval($_GET['order_id']);
        $condition_user['order_id'] = $order_id;
        $d_package_order = M('Package_order');
        $package_order = $d_package_order->where(['order_id' => $order_id])->find();
        if ($package_order) {
            $now_time = time();
            $use_status = 0;
            if ($package_order['status'] == 0 && $package_order['package_try_end_time'] > 0) {
                if (intval($now_time) > intval($package_order['package_try_end_time'])) {
                    $package_order['tip'] = '已到期(试用结束)';
                    $package_order['package_try_end_time_txt'] = date('Y-m-d', $package_order['package_try_end_time']);
                    $package_order['font_color'] = 'red';
                    $use_status = 0;
                } else {
                    $package_order['tip'] = '试用中';
                    $package_order['package_try_end_time_txt'] = date('Y-m-d', $package_order['package_try_end_time']);
                    $package_order['font_color'] = 'green';
                }
            } else {
                if (intval($now_time) > intval($package_order['package_end_time'])) {
                    $package_order['tip'] = '已到期(套餐结束)';
                    $package_order['font_color'] = 'red';
                    $use_status = 1;
                } else {
                    $package_order['tip'] = '使用中';
                    $package_order['package_end_time_txt'] = date('Y-m-d', $package_order['package_end_time']);
                    $package_order['font_color'] = 'green';
                    $use_status = 1;
                }
            }
            // 使用状态 0 试用中  1 使用中
            $package_order['use_status'] = $use_status;
            // 查询关联房间套餐
            $condition_user['order_id'] = $order_id;
            $d_package_room_order = M('Package_room_order');
            $package_room_total = $d_package_room_order->where(['package_order_id' => $order_id, 'status' => 1])->sum('room_num*num');
            $package_room_order = $d_package_room_order->where(['package_order_id' => $order_id, 'status' => 1])->select();
            if (!$package_room_total) {
                $package_room_total = 0;
            }
            if (!$package_room_order) {
                $package_room_order = [];
            }
            $package_room_total += $package_order['room_num'];
            $this->assign('package_room_total', $package_room_total);
            $this->assign('package_room_order', $package_room_order);
        }
        $this->assign('package_order', $package_order);
        $this->display();
    }


    //物业添加
    public function property_add()
    {
        if (IS_POST) {
            $package_id = $_POST['package_id'];
            if (!$package_id) {
                $this->error('请选择套餐');
            }
            $pay_year = $_POST['pay_year'];
            $package = D('Privilege_package')->where(['package_id' => $package_id])->field('package_title,package_price,room_num,package_limit_num')->find();
            if ($package['package_limit_num'] && !is_numeric($pay_year) || strpos($pay_year, ".") !== false) {
                $this->error('套餐周期必须是整数');
            }
            if ($package['package_limit_num'] && $pay_year > $package['package_limit_num']) {
                $this->error('该套餐最后可购买' . $package['package_limit_num'] . '年');
            }
            fdump_api([$_POST, $this->system_session], '$_POST');
            $database_house_village = D('House_property');
            $_POST['account'] = trim($_POST['account']);
            $now_house = M('House_property')->field(true)->where(array('account' => $_POST['account']))->find();
            if ($now_house) {
                $this->error('物业账号已存在！请重试~');
            }
            $is_virtual_switch = 0;
            if (isset($_POST['is_virtual_switch'])) {
                $is_virtual_switch = intval($_POST['is_virtual_switch']);
                unset($_POST['is_virtual_switch']);
            }
            if (isset($_POST['dosubmit'])) {
                unset($_POST['dosubmit']);
            }
            $facilities_type = '';
            if (isset($_POST['facilities_type'])) {
                $facilities_type = trim($_POST['facilities_type']);
                unset($_POST['facilities_type']);
            }
            $_POST['password'] = md5($_POST['password']);
            $_POST['status'] = 1; //调整成不需要审核
            $_POST['create_time'] = time();
            unset($_POST['package_id']);
            unset($_POST['pay_year']);
            if (isset($_POST['login_phone'])) {
                $login_phone = trim($_POST['login_phone']);
                unset($_POST['login_phone']);
            }
            $property_id = $database_house_village->data($_POST)->add();
            if ($property_id) {
                D('House_property')->addNewCharge($property_id);
                $package_count = D('Privilege_package_bind')->where(['package_id' => $package_id])->count();

                $content_id_arr = D('Privilege_package_bind')->where(['package_id' => $package_id])->getField('content_id', true);
                $content_id = implode(',', $content_id_arr);

                $order_no = date('YmdHis') . rand(100, 999);
                //生成功能套餐订单
                if (!$package['package_limit_num']) {
                    // 无购买期限的 默认 使用截止时间
                    if ($package['package_try_days'] > 0) {
                        $package_try_end_time = strtotime(date('Y-m-d', time())) + intval($package['package_try_days']) * 24 * 3600 + 24 * 3600;
                    } else {
                        $package_try_end_time = strtotime(date('Y-m-d', time())) + 24 * 3600;
                    }
                    $package_end_time = $package_try_end_time;
                } else {
                    $package_try_end_time = 0;
                    $package_end_time = strtotime(date('Y-m-d', time())) + 366 * $pay_year * 24 * 3600 + 24 * 3600;
                }
                $data = [
                    'order_no' => $order_no,
                    'package_id' => $package_id,
                    'property_id' => $property_id,
                    'property_name' => $_POST['property_name'],
                    'property_tel' => $_POST['property_phone'],
                    'order_money' => ($pay_year * $package['package_price']),
                    'pay_money' => ($pay_year * $package['package_price']),
                    'pay_type' => 3,//3表示平台添加
                    'create_time' => time(),
                    'pay_time' => time(),
                    'package_period' => $pay_year,
                    'package_end_time' => $package_end_time,
                    'package_try_end_time' => $package_try_end_time,
                    'num' => $pay_year,
                    'status' => 1,
                    'transaction_no' => $order_no,
                    'details_info' => json_encode(['package_title' => $package['package_title'], 'num' => $package_count, 'price' => $package['package_price'], 'room_num' => $package['room_num']], JSON_UNESCAPED_UNICODE),
                    'package_title' => $package['package_title'],
                    'room_num' => $package['room_num'],
                    'package_price' => $package['package_price'],
                    'order_type' => 0,
                    'content_id' => $content_id,
                ];
                $order_ids = D('Package_order')->data($data)->add();
                $log_msg = '平台给' . $_POST['property_name'] . '物业购买' . $package['package_title'] . '套餐';
                $this->create_log($log_msg, $package_id, $property_id, $order_no, 1, 1, 1);
            }
            
            if ($property_id && $is_virtual_switch==1 && $facilities_type) {
                $db_house_community_about = M('House_community_about');
                $from_user = ['type' => 'system_admin', 'admin_id' => $this->system_session['id'], 'account' => $this->system_session['account']];
                if ($this->system_session['realname']) {
                    $from_user['name'] = $this->system_session['realname'];
                } elseif ($this->system_session['nickname']) {
                    $from_user['name'] = $this->system_session['nickname'];
                }
                if ($this->system_session['phone']) {
                    $from_user['phone'] = $this->system_session['phone'];
                }
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                $data = [
                    'property_id' => $property_id, 'village_id' => 0, 'facilities_type' => $facilities_type,
                    'from' => 'admin', 'from_user' => $from_user, 'is_virtual' => 1, 'add_time' => time(),
                ];
                $id = $db_house_community_about->add($data);
                if ($id) {
                    $dataVillage = [
                        'account' => trim($_POST['account']),
                        'password' => $_POST['password'],
                        'status' => 0,
                        'add_time' => time(),
                    ];
                    if (isset($_POST['property_name']) && $_POST['property_name']) {
                        $dataVillage['property_name'] = trim($_POST['property_name']);
                    }
                    if (isset($_POST['property_address']) && $_POST['property_address']) {
                        $dataVillage['property_address'] = trim($_POST['property_address']);
                    }
                    if (isset($_POST['property_phone']) && $_POST['property_phone']) {
                        $dataVillage['property_phone'] = trim($_POST['property_phone']);
                    }
                    if (isset($_POST['province_id']) && $_POST['province_id']) {
                        $dataVillage['province_id'] = intval($_POST['province_id']);
                    }
                    if (isset($_POST['city_id']) && $_POST['city_id']) {
                        $dataVillage['city_id'] = intval($_POST['city_id']);
                    }
                    if (isset($_POST['area_id']) && $_POST['area_id']) {
                        $dataVillage['area_id'] = intval($_POST['area_id']);
                    }
                    $dataVillage['village_name'] = trim($_POST['property_name']);
                    $dataVillage['village_address'] = trim($_POST['property_address']);
                    $dataVillage['property_id'] = $property_id;
                    $dataVillage['tourist'] = 1;
                    $village_id = M('House_village')->add($dataVillage);
                    if ($village_id) {
                        $data = [
                            'property_id' => $property_id, 'village_id' => $village_id, 'facilities_type' => $facilities_type,
                            'from' => 'system', 'from_user' => $from_user, 'is_virtual' => 1, 'add_time' => time(),
                        ];
                        $db_house_community_about->add($data);
                    }
                }
            }
            if ($property_id) {
                D('House_village')->setSoftwareNewEffectCharge($property_id);
                // 处理添加登录凭依手机号
                if (isset($login_phone) && $login_phone) {
                    $modelHouseProperty = D('House_property');
                    $dataParam = [
                        'type'        => $modelHouseProperty::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                        'from_table'  => $modelHouseProperty::WORK_WEI_XIN_PHONE_FROM_PROPERTY,
                        'from_id'     => $property_id,
                        'phone'       => $login_phone,
                        'add_time'    => time(),
                    ];
                    $db_work_weixin_phone = M('Work_weixin_phone');
                    $db_work_weixin_phone->data($dataParam)->add();
                }
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $package = D('Privilege_package')->field('package_id,package_title')->where(['status' => 1, 'type' => 0])->select();
            $isSuiChuan = $this->hasSuiChuan();
            $havePropertyVirtual  = D('Config_customization')->getHavePropertyVirtual();
            if($havePropertyVirtual>0){
                $isSuiChuan=1;
            }
            $jz_wsxdsj_net_customized_open  = D('Config_customization')->getWSXCustomizedOpen();
            $isVirtualSwitch = 0;
            if ($jz_wsxdsj_net_customized_open) {
                $isSuiChuan = 0;
                $isVirtualSwitch = 1;
            }
            $this->assign('isSuiChuan', $isSuiChuan);
            $this->assign('isVirtualSwitch', $isVirtualSwitch);

            if ($jz_wsxdsj_net_customized_open) {
                $facilities_type_switch = D('House_community_about')->getFacilitiesTypeSwitch();
                $this->assign('facilities_type_switch', $facilities_type_switch);
            }
            
            $this->assign('package', $package);
            $this->display();
        }
    }

    public function getPackageY()
    {
        $package_id = $_POST['package_id'];

        $package = D('Privilege_package')->where(['package_id' => $package_id])->getField('package_limit_num');
        if ($package) {
            $property_id = intval($_POST['id']);
            $sum_where['status'] = 1;
            $sum_where['property_id'] = $property_id;
            $sum_where['package_id'] = $package_id;
            $sum_package_period = D('Package_order')->where($sum_where)->field('sum(package_period) as package_period')->find();
            $sum_package_period = $sum_package_period['package_period'] ? $sum_package_period['package_period'] : 0;
            $package = intval($package) - $sum_package_period;
            $package = intval($package);
            exit(json_encode(['code' => 1, 'package_limit_num' => $package]));
        } else {
            exit(json_encode(['code' => 2, 'package_limit_num' => '']));
        }

    }

    //物业修改
    public function property_edit()
    {

        $isSuiChuan = $this->hasSuiChuan();
        $havePropertyVirtual  = D('Config_customization')->getHavePropertyVirtual();
        if($havePropertyVirtual>0){
            $isSuiChuan=1;
        }
        $jz_wsxdsj_net_customized_open  = D('Config_customization')->getWSXCustomizedOpen();
        $isVirtualSwitch = 0;
        if ($jz_wsxdsj_net_customized_open) {
            $isSuiChuan = 0;
            $isVirtualSwitch = 1;
        }
        $property_id = I('id', '', 'intval');
        $database_property = D('House_property');
        $db_house_community_about = D('House_community_about');
        $whereWorkWeiXinPhone = [
            'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
            'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_PROPERTY,
            'from_id'     => $property_id,
        ];
        $db_work_weixin_phone = M('Work_weixin_phone');
        $workWeiXinPhoneInfo = $db_work_weixin_phone->where($whereWorkWeiXinPhone)->find();
        if (IS_POST) {
            $condition_user['id'] = $property_id;
            $now_user = $database_property->field(true)->where($condition_user)->find();
            if (empty($now_user)) {
                $this->error('没有找到该物业信息！');
            }
            $data_user['sms_sign']            = $_POST['sms_sign'];
            $data_user['property_name']       = $_POST['property_name'];
            $data_user['rebate_percent']      = $_POST['rebate_percent'];
            $data_user['property_address']    = $_POST['property_address'];
            $data_user['property_phone']      = $_POST['property_phone'];
            $data_user['province_id']         = $_POST['province_id'];
            $data_user['city_id']             = $_POST['city_id'];
            $data_user['appid']               = $_POST['appid'];
            $data_user['app_secret']          = $_POST['app_secret'];
            $data_user['area_id']             = $_POST['area_id'];
            //$data_user['village_num']       = $_POST['village_num']; //2020/8/14 修改
            $data_user['status']              = $_POST['status'];
            $data_user['is_config_show']      = $_POST['is_config_show'];
            $data_user['merchant_id']         = empty($_POST['merchant_id']) ? '' : $_POST['merchant_id'];
            $data_user['identity']            = empty($_POST['identity']) ? '' : $_POST['identity'];
            $data_user['house_e_invoice_url'] = empty($_POST['house_e_invoice_url']) ? '' : $_POST['house_e_invoice_url'];
            $data_user['house_e_pdf_url']     = empty($_POST['house_e_pdf_url']) ? '' : $_POST['house_e_pdf_url'];
//            $data_user['room_limit_num']    = empty($_POST['room_limit_num']) ? 0 : $_POST['room_limit_num'];
            $data_user['property_short_name'] = empty(trim($_POST['property_short_name'])) ? '' : trim($_POST['property_short_name']);
            $data_user['is_virtual'] = intval($_POST['is_virtual']);
            if(!$isSuiChuan){
                $data_user['is_virtual'] = 0;
            }
            if (!empty($_POST['password'])) {
                $data_user['password'] = md5($_POST['password']);
            }
            //禁止物业则禁止物业下所有小区
            if ($_POST['status'] == 2) {
                $result = $this->closeCommunity($property_id);
                if (!$result) {
                    $this->error('禁止小区失败！请重试~');
                }
            }
            $pay_year   = $_POST['pay_year'];//升级才有 周期
            $order_id   = $_POST['order_id'];
            $pay_years  = $_POST['pay_years'];//续费才有  周期
            $package_id = $_POST['package_id'];
            if ($pay_year) {
                if (!is_numeric($pay_year) || strpos($pay_year, ".") !== false) {
                    $this->error('套餐周期必须是整数');
                }
            }
            if ($pay_years) {
                if (!is_numeric($pay_years) || strpos($pay_years, ".") !== false) {
                    $this->error('套餐周期必须是整数');
                }
            }
            $package_order_info = D('Package_order')->where(['order_id' => $order_id])->find();
            if ($_POST['upgrade_type'] == 2) {
                $package_id = $package_order_info['package_id'];
            } elseif ($_POST['upgrade_type'] == 1 && $package_id && !$pay_year) {
                $this->error('请输入套餐购买周期');
            }
            //求和功能套餐已购买周期
            $sum_where['status'] = 1;
            $sum_where['property_id'] = $property_id;
            $sum_where['package_id'] = $package_id;
            $sum_package_period = D('Package_order')->where($sum_where)->field('sum(package_period) as package_period')->find();
            $sum_package_period = $sum_package_period['package_period'] ? $sum_package_period['package_period'] : 0;
            //订单详情
            //修改的情况生成订单  没修改不生成订单
            if ($pay_years || ($pay_year && $package_id)) {
                //剩余时间
                $surplus_time = $package_order_info['package_end_time'] - time();

                $package = D('Privilege_package')->where(['package_id' => $package_id])->field('package_title,package_price,room_num,package_limit_num')->find();
                $order_no = date('YmdHis') . rand(100, 999);

                //升级
                if ($_POST['upgrade_type'] == 1) {
                    $content_id_arr = D('Privilege_package_bind')->where(['package_id' => $package_id])->getField('content_id', true);
                    $content_id = implode(',', $content_id_arr);

                    $surplus_money = 0;//初始剩余金额
                    //判断是否还有剩余时间
                    if ($surplus_time > 0) {
                        $surplus_day = ceil($surplus_time / 86400);//剩余天数
                        //支付金额 - （单价/366 * 剩余天数）= 剩余的钱
                        $surplus_money = round($package_order_info['pay_money'] - (($package_order_info['package_price'] / ($package_order_info['package_period'] * 366)) * $surplus_day), 2);
                        if ($surplus_money < 0) {
                            $surplus_money = 0;
                        }
                    }
                    if (($_POST['pay_year'] + $sum_package_period) > $package['package_limit_num']) {
                        $surplus_num = $package['package_limit_num'] - $sum_package_period;
                        $this->error('该套餐最后可购买' . intval($surplus_num) . '年');
                    }
                    $package_count = D('Privilege_package_bind')->where(['package_id' => $package_id])->count();

                    $money = $pay_year * $package['package_price'];
                    //生成功能套餐订单
                    $data = [
                        'order_no' => $order_no,
                        'package_id' => $package_id,
                        'property_id' => $property_id,
                        'property_name' => $_POST['property_name'],
                        'property_tel' => $_POST['property_phone'],
                        'order_money' => $money, //应付
                        'pay_money' => $money,   //实付
                        'pay_type' => 3,//3表示平台操作
                        'create_time' => time(),
                        'pay_time' => time(),
                        'package_period' => $pay_year,
                        'package_end_time' => strtotime(date('Y-m-d', time())) + 366 * $pay_year * 24 * 3600 + 24 * 3600,
                        'package_try_end_time' => 0,
                        'num' => $pay_year,
                        'status' => 1,
                        'transaction_no' => $order_no,
                        'details_info' => json_encode(['package_title' => $package['package_title'], 'num' => $package_count, 'price' => $package['package_price'], 'room_num' => $package['room_num']], JSON_UNESCAPED_UNICODE),
                        'package_title' => $package['package_title'],
                        'room_num' => $package['room_num'],
                        'package_price' => $package['package_price'],
                        'package_limit_num' => $package['package_limit_num'],
                        'order_type' => (int)$_POST['upgrade_type'],
                        'content_id' => $content_id
                    ];
                    $res = D('Package_order')->data($data)->add();
                    $order_ids = D('Package_order')->getLastInsID();
                    if ($res) {
                        if (!empty($property_id)){
                            $person_info = D('New_marketing_person_mer')->where(['mer_id'=>$property_id,'type'=>1])->find();
                            if (!empty($person_info)){
                                $data_person=[
                                    'order_type'=>1,
                                    'order_id'=>$res,
                                    'team_id'=>$person_info['team_id'],
                                ];
                                D('New_marketing_order_type')->data($data_person)->add();
                            }
                        }
                        D('Package_order')->where(['order_id' => $order_id])->save(['order_type' => 3]);//修改为已过期
                        $log_msg = '平台给' . $_POST['property_name'] . '物业添加了' . $package['package_title'] . '套餐';
                        $this->create_log($log_msg, $package_id, $property_id, $order_no, 1, 1, 2);
                    }
                }
                //续费
                if ($_POST['upgrade_type'] == 2) {
                    if ($package_order_info['package_end_time'] < time() && $package_order_info['package_try_end_time'] < time()) {
                        $content_id_arr = D('Privilege_package_bind')->where(['package_id' => $package_order_info['package_id']])->getField('content_id', true);
                        $content_id = implode(',', $content_id_arr);
                    } else {
                        $content_id = $package_order_info['content_id'];
                    }
                    //计算房间订单周期天数
                    $room_z_day = $pay_years * 366;
                    if ($room_z_day <= 0) {
                        $this->error('房间套餐结束时间异常');
                    }
                    //订单金额
                    $order_money = $pay_years * $package['package_price'];
                    $money = $order_money;
                    //试用期情况
                    if ($package_order_info['pay_type'] == 4) {
                        //剩余时间
                        $surplus_time = $package_order_info['package_try_end_time'] - time();
                    } else {//不是试用的情况
                        //剩余时间
                        $surplus_time = $package_order_info['package_end_time'] - time();
                    }
                    $surplus_time = $surplus_time < 0 ? 0 : $surplus_time;
                    $package_end_time = strtotime(date('Y-m-d', time())) + 366 * $pay_years * 86400 + 86400 + $surplus_time;
                    $has_been_used_period = $sum_package_period + $pay_years;//物业所有功能套餐周期和+续费购买周期
                    //进一法取总和周期值 如果大于允许周期 return
                    if (ceil($has_been_used_period) > $package['package_limit_num']) {
                        $this->error('套餐最大允许周期已使用完毕');
                    }

                    //生成功能套餐订单
                    $data = [
                        'order_no' => $order_no,
                        'package_id' => $package_order_info['package_id'],
                        'property_id' => $property_id,
                        'property_name' => $_POST['property_name'],
                        'property_tel' => $_POST['property_phone'],
                        'order_money' => $order_money, //应付
                        'pay_money' => $order_money,   //实付
                        'pay_type' => 3,//3表示平台操作
                        'create_time' => time(),
                        'pay_time' => time(),
                        'package_period' => (int)$pay_years,
                        'package_end_time' => $package_end_time,
                        'package_try_end_time' => 0,
                        'num' => (int)$pay_years,
                        'status' => 1,
                        'transaction_no' => $order_no,
                        'details_info' => $package_order_info['details_info'],
                        'package_title' => $package_order_info['package_title'],
                        'room_num' => $package_order_info['room_num'],
                        'package_price' => $package_order_info['package_price'],
                        'package_limit_num' => $package['package_limit_num'],
                        'order_type' => 2,
                        'content_id' => $content_id
                    ];
                    $res = D('Package_order')->data($data)->add();
                    if ($res) {
                        if (!empty($property_id)){
                            $person_info = D('New_marketing_person_mer')->where(['mer_id'=>$property_id,'type'=>1])->find();
                            if (!empty($person_info)){
                                $data_person=[
                                    'order_type'=>1,
                                    'order_id'=>$res,
                                    'team_id'=>$person_info['team_id'],
                                ];
                                D('New_marketing_order_type')->data($data_person)->add();
                            }
                        }
                        $order_ids = D('Package_order')->getLastInsID();
                        D('Package_order')->where(['order_id' => $order_id])->save(['order_type' => 3]);//修改为已过期
                        $log_msg = '平台给' . $_POST['property_name'] . '物业续费' . $package['package_title'] . '套餐';
                        $this->create_log($log_msg, $package_id, $property_id, $order_no, 1, 1, 3);
                    }
                }
            }
            if(isset($_POST['scrcu_mer_no'])){
                $_POST['scrcu_mer_no']=$_POST['scrcu_mer_no'] ? trim($_POST['scrcu_mer_no']):'';
                $app_property = M('House_village_pay_config')->where(array('property_id'=>$_POST['id']))->find();
                if($app_property){
                    $saveArr=array('scrcu_mer_no'=>$_POST['scrcu_mer_no']);
                    if(isset($_POST['scrcu_mer_no_2'])){
                        $saveArr['scrcu_mer_no_2']=$_POST['scrcu_mer_no_2'] ? trim($_POST['scrcu_mer_no_2']):'';
                    }
                    M('House_village_pay_config')->where(array('property_id'=>$_POST['id']))->data($saveArr)->save();
                }else{
                    if($_POST['scrcu_mer_no']){
                        $addArr=array( 'scrcu_mer_no'=>$_POST['scrcu_mer_no'], 'property_id'=>$_POST['id']);
                        if(isset($_POST['scrcu_mer_no_2'])){
                            $addArr['scrcu_mer_no_2']=$_POST['scrcu_mer_no_2'] ? trim($_POST['scrcu_mer_no_2']):'';
                        }
                        M('House_village_pay_config')->data($addArr)->add();
                    }
                }
            }
            
            if (isset($_POST['login_phone'])) {
                $login_phone = trim($_POST['login_phone']);
                unset($_POST['login_phone']);
            }
            $res = M('House_property')->where($condition_user)->data($data_user)->save();
            if ($res !== false) {
                D('House_village')->setSoftwareNewEffectCharge($property_id);
                // 处理添加登录凭依手机号
                if (isset($login_phone) && $login_phone && !$workWeiXinPhoneInfo) {
                    $dataParam = [
                        'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                        'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_PROPERTY,
                        'from_id'     => $property_id,
                        'phone'       => $login_phone,
                        'add_time'    => time(),
                    ];
                    $db_work_weixin_phone->data($dataParam)->add();
                } elseif (isset($login_phone) && $workWeiXinPhoneInfo) {
                    $dataParam = [
                        'phone'          => $login_phone,
                        'update_time'    => time(),
                    ];
                    $db_work_weixin_phone->where($whereWorkWeiXinPhone)->save($dataParam);
                }
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！请重试~');
            }
        } else {
            if ($property_id) {
                $res = $database_property->where(array('id' => $property_id))->find();
                if (trim($res['app_secret']) != '') {
                    import('ORG.Net.Http');
                    $http = new Http();
                    try {
                        $curl_get = $http->curlGet($this->config['site_url'] . '/v20/public/index.php/pay/pay/getTianqueUrl?mno=' . trim($res['app_secret']));
                        $curl_res = json_decode($curl_get, true);
                        if (isset($curl_res['url']) && $curl_res['url'] != '') {
                            $res['tianque_mno_url'] = $this->config['site_url'] . '/v20/public/index.php/pay/index/getTianqueUrl?mno=' . trim($res['app_secret']);
                        } elseif (isset($curl_res['already'])) {
                            $res['tianque_mno_status'] = 1;
                        } else {
                            $res['tianque_mno_status'] = 2;
                        }
                    } catch (Exeception $e) {

                    }
                }
                $property_info = M('House_village_pay_config')->where(array('property_id'=>$property_id))->find();
                $res['scrcu_mer_no_2']='';
                if($property_info){
                    $res['scrcu_mer_no'] = $property_info['scrcu_mer_no'];
                    if(isset($property_info['scrcu_mer_no_2'])){
                        $res['scrcu_mer_no_2'] = $property_info['scrcu_mer_no_2'];
                    }
                }else{
                    $res['scrcu_mer_no'] = '';
                }
                $this->assign('now_village', $res);
                $limit_info = $database_property->get_room_limit_num($property_id);
                $this->assign('limit_info', $limit_info);
                $map['property_id'] = $property_id;
//                $map['order_type'] = ['neq',3];
                $map['status'] = 1;
//                $map['package_try_end_time|package_end_time'] = ['gt',time()];
                //$package_order_info =  D('package_order')->where($map)->order('order_id desc')->field('order_id,package_id,sum(package_period) as package_period,package_title,package_end_time,package_price')->find();
                $package_order_info = M('package_order')->where($map)->field('order_id,package_id,package_title,package_end_time,package_price')->order('order_id desc')->find();
                if (!$package_order_info) {
                    $map = [];
                    $map['property_id'] = $property_id;
                    $map['pay_type'] = 4;
                    $map['status'] = 0;
                    $map['package_try_end_time'] = ['gt', time()];
                    $package_order_info = M('package_order')->where($map)->field('order_id,package_id,package_title,package_end_time,package_price,package_try_end_time')->order('order_id desc')->find();
                    $package_order_info['package_end_time'] = $package_order_info['package_try_end_time'];
                    // 没有购买记录 限制周期当前已购买为0
                    $package_order_info['package_period'] = 0;
                } elseif ($package_order_info['package_id']) {
                    $map['package_id'] = $package_order_info['package_id'];
                    // 有购买记录 限制周期当前已购买总期数
                    $package_order_info['package_period'] = M('package_order')->where($map)->sum('package_period');
                }
                $max_renewal = 0;
                if ($package_order_info) {
                    $package_limit_num = D('Privilege_package')->where(['package_id' => $package_order_info['package_id']])->getField('package_limit_num');
                    $max_renewal = $package_limit_num - $package_order_info['package_period'];
                    $max_renewal = $max_renewal > 0 ? $max_renewal : 0;
                } else {
                    $package_order_info['package_price'] = 0;
                    $package_order_info['package_id'] = 0;
                }
                $this->assign('max_renewal', $max_renewal);
                //$details_info = json_decode($package_order_info['details_info'],true);
//                $where['package_price'] = ['gt',$package_order_info['package_price']];

                $where['package_limit_num'] = ['gt', 0];
                if ($package_order_info['package_id']) {
                    $where['package_id'] = ['neq', $package_order_info['package_id']];
                }
                $where['status'] = 1;
                $where['type'] = 0;
                $package_info = D('Privilege_package')->where($where)->select();

                $this->assign('package_order_info', $package_order_info);
                $this->assign('package_info', $package_info);
                if(strpos($_SERVER['SERVER_NAME'],'hz.huizhisq.com') !== false){
                    $this->assign('is_scuru', 1);
                }else{
                    $this->assign('is_scuru', 0);
                }

                if ($jz_wsxdsj_net_customized_open) {
                    $house_community_about_info = $db_house_community_about->where(['property_id' => $property_id, 'village_id' => 0])->find();
                    if (isset($house_community_about_info['from_user'])) {
                        unset($house_community_about_info['from_user']);
                    }
                    $house_community_about_info['facilities_type_text'] = $db_house_community_about->getFacilitiesTypeText($house_community_about_info['facilities_type']);
                    $this->assign('about_info', $house_community_about_info);
                }
            }

            $this->assign('isSuiChuan', $isSuiChuan);
            $this->assign('isVirtualSwitch', $isVirtualSwitch);

            if (!$workWeiXinPhoneInfo) {
                $workWeiXinPhoneInfo = [];
            }
            $this->assign('workWeiXinPhoneInfo', $workWeiXinPhoneInfo);

            $this->display();
        }
    }

    //关闭小区
    public function closeCommunity($property_id)
    {
        $where['property_id'] = $property_id;
        $where['status'] = ['in', '0,1'];//正常可使用的房间
        $village = D('House_village')->where($where)->getField('village_id', true);
        if ($village) {
            $village_id = implode(',', $village);
            $map['village_id'] = ['in', $village_id];
            $res = D('House_village')->where($map)->data(['status' => 2])->save();
            return $res;
        } else {
            return 1;
        }
    }

    /**
     * Notes: 添加套餐生成日志
     * @param $package_id
     * @param $property_id
     * @param $order_ids
     * @param $uid
     * @param $bind_type
     * @param $operate_type
     * @param int $log_type
     * @datetime: 2020/8/14 20:22
     */
    public function create_log($log_msg, $package_id, $property_id, $order_no, $uid, $bind_type, $operate_type, $log_type = 1)
    {
        $log_data = [
            'bind_type' => $bind_type,
            'bind_id' => $package_id,
            'operate_type' => $operate_type,
            'uid' => $uid,
            'property_id' => $property_id,
            'order_no' => $order_no,
            'log_type' => $log_type,
            'describe' => $log_msg,
            'create_time' => time(),
        ];
        D('package_log')->data($log_data)->add();
    }

    public function del_property()
    {
        $pigcms_id = intval($_POST['id']);
        if (!$pigcms_id) {
            $this->error('参数传输错误');
        }
        $del = D('House_property')->where(array('id' => $pigcms_id))->delete();
        if ($del) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //小区表物业账号同步物业表
    public function village_sync_property()
    {
        $dataList = D('House_village')->select();
        foreach ($dataList as $k => $v) {
            if ($v['account']) {
                $res = M('House_property')->where(array('account' => $v['account']))->find();
                if ($res) {
                    D('House_village')->where(array('account' => $v['account']))->data(array('property_id' => $res['id']))->save();
                } else {
                    $data['account'] = $v['account'];//账号
                    $data['password'] = $v['pwd'];
                    $data['property_name'] = $v['property_name'];//物业名称
                    $data['status'] = $v['status'];//状态
                    $data['property_phone'] = $v['property_phone'];//物业电话
                    $data['property_address'] = $v['property_address'];//物业地址
                    $data['province_id'] = $v['province_id'];//省
                    $data['city_id'] = $v['city_id'];//省
                    $data['area_id'] = $v['area_id'];//省
                    $data['village_num'] = 1;//小区数量
                    $data['create_time'] = $v['add_time'];
                    $property_id = M('House_property')->data($data)->add();
                    D('House_village')->where(array('account' => $v['account']))->data(array('property_id' => $property_id))->save();

                }
            }
        }
    }


    //小区导入
    public function property_import()
    {
        if (IS_POST) {
            $database_house_village = D('House_village');
            if ($_FILES['file']['error'] != 4) {

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/property');

                $upload_dir = './upload/excel/property/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);    //删除文件
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $database_house_property = D('House_property');
                        $database_house_village_config = D('House_village_config');
                        $last_village_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null) continue;
                            $keyValA = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            if (empty($keyValA)) {
                                $err_msg = '请填写物业公司名称！';
                                continue;
                            }
                            $keyValB = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (empty($keyValB)) {
                                $err_msg = '请填写物业联系地址！';
                                continue;
                            }
                            $keyValC = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            if (empty($keyValC)) {
                                $err_msg = '请填写物业联系电话！';
                                continue;
                            }
                            $keyValD = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            if (empty($keyValD)) {
                                $err_msg = '请填写管理帐号！';
                                continue;
                            }
                            $keyValE = trim($vv['E']);
                            if (empty($keyValE)) {
                                $err_msg = '请填写管理密码！';
                                continue;
                            }
                            $keyValF = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            if (empty($keyValF)) {
                                $err_msg = '请填写小区名称！';
                                continue;
                            }
                            $keyValG = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            if (empty($keyValG)) {
                                $err_msg = '请填写小区地址！';
                                continue;
                            }
                            $dataList = $database_house_property->where(array('account' => trim($vv['D'])))->select();
                            if ($dataList) {
                                $err_msg = '物业登录账号已存在,请重新编辑表格！';
                                continue;
                            }
                            $keyValH = trim($vv['H']);
                            $tmpdata = array();
                            $tmpdata['property_name']    = $keyValA;
                            $tmpdata['property_address'] = $keyValB;
                            $tmpdata['property_phone']   = $keyValC;
                            $tmpdata['account']          = $keyValD;
                            $tmpdata['password']         = md5($keyValE);
                            $tmpdata['status'] = 0;
                            $tmpdata['create_time'] = time();
                            $last_village_id = $database_house_property->data($tmpdata)->add();
                            if (!$last_village_id) {
                                $err_msg = $keyValA . ' 导入失败！';
                            } else {
                                D('House_property')->addNewCharge($last_village_id);
                                $model['property_id']     = $last_village_id;
                                $model['village_name']    = $keyValF;
                                $model['village_address'] = $keyValG;

                                $model['status'] = 0;
                                $model['add_time'] = time();
                                $last_village_id = $database_house_village->data($model)->add();

                                $data = [];
                                if ($keyValH == '是') {
                                    $village_single_support_digit = 3;
                                    $data['village_single_support_digit']  = $village_single_support_digit;
                                    $data['single_support_digit_set_time'] = time();
                                } elseif ($keyValH) {
                                    $village_single_support_digit = 2;
                                    $data['village_single_support_digit']  = $village_single_support_digit;
                                    $data['single_support_digit_set_time'] = time();
                                }
                                
                                $data['village_id'] = $last_village_id;
                                $database_house_village_config->add($data);
                                D('House_village')->update_user_info($last_village_id);
                            }
                        }
                        if ($last_village_id) {

                            //  $this->frame_submit_tips(1, '导入成功');
                            $this->frame_main_ok_tips('导入成功');
                        } else {
                            // $this->error('导入失败');
                            $this->frame_error_tips('导入失败！原因：' . $err_msg);
                        }
                    }
                } else {
                    // $this->error($upload->getErrorMsg());
                    $this->error( $upload->getErrorMsg());
                }
            }
            // $this->error('文件上传失败');
            $this->error('文件上传失败');
        } else {
            $this->display();
        }
    }


    public function house_adver_list()
    {
        $this->assign('bg_color', 'white');
        $is_platform=1;
        if ($_GET['cat_key']) {
            $database_adver_category = D('House_adver_category');
            $condition_adver_category['cat_key'] = $_GET['cat_key'];
            $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
        } else {
            $now_category = M('House_adver_category')->where(['cat_key' => 'house_app_index_top'])->find();
            if (!$now_category) {
                $this->error('获取失败！请联系管理员添加【社区:APP/小程序首页顶部轮播图】广告类别~');
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
        $condition_adver['a.status'] = array('neq',4);
//        $condition_adver['a.village_id'] = 0;
        if($this->is_visualize > 0){
            $condition_adver['_string'] = '( a.village_id in (0,-1))';
        }else{
            $condition_adver['_string'] = '(a.add_type=1 OR  a.village_id in (0,-1))';
        }

        if ($this->system_session['area_id']) {
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($temp['area_type'] == 1) {
                    $city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'], true);
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
                $condition_adver['a.city_id'] = array('in', $area_id);
            } else {
                $condition_adver['a.city_id'] = $area_id;
            }
        }
        $jz_open = D('Config_customization')->getWSXCustomizedOpen();
        $systemAdminMenus = isset($this->system_session['menus']) ? $this->system_session['menus'] : [];
        $systemAdminId = isset($this->system_session['id']) ? intval($this->system_session['id']) : 0;
        $show_nav = true;
        $checkAdvertIds = [];
        if ($jz_open) {
            // 定制 且 无权限访问此目录 查询当前身份审核数据
            $dbHouseVillageCheckauthDetail = M('House_village_checkauth_detail');
            $whereCheckAuthDetail = ['property_id' => 0, 'village_id' => 0, 'wid' => $systemAdminId, 'status' => 0];
            $applyIds = $dbHouseVillageCheckauthDetail->where($whereCheckAuthDetail)->getField('apply_id', true);
            $whereApply = ['id' => ['in', $applyIds]];
            $whereApply['xtype'] = 'house_advert';
            $checkAdvertIds = M('House_village_checkauth_apply')->where($whereApply)->getField('order_id', true);
            if (!empty($systemAdminMenus) && !in_array(431, $systemAdminMenus)) {
                $condition_adver = [];
                $condition_adver['a.cat_id'] = $now_category['cat_id'];
                $condition_adver['a.status'] = array('neq',4);
                $condition_adver['a.id'] = ['in', $checkAdvertIds];
                $show_nav = false;
            }
        }
        
        $field = "a.*,b.look_num,b.click_num,(CASE WHEN (`a`.`status`='0') then 1 WHEN (`a`.`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $adver_list = $database_adver->field($field)
            ->join(" as a LEFT JOIN " . C('DB_PREFIX') . "house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->order('`t_sort` ASC,`a`.`id` DESC')
            ->select();
        if ($adver_list) {
            // 	广告状态 0-关闭 1-正常 2-待审核 3审核拒绝 4-删除
            $statusArr = [0 => '关闭', 1 => '正常', 2 => '待审核', 3 => '审核拒绝', 4=>'已删除'];
            $statusColorArr = [0 => 'red', 1 => 'green', 2 => '#2681f3', 3 => 'red', 4=>'red'];
            foreach ($adver_list as &$v) {
                if($many_city == 1 ){
                    $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                    if (empty($city)) {
                        $v['city_id'] = '通用';
                    } else {
                        $v['city_id'] = $city['area_name'];
                    }
                }
                $status = isset($v['status']) ? intval($v['status']) : 0;
                $v['status_text'] = isset($statusArr[$status]) ? trim($statusArr[$status]) : '';
                $v['status_color'] = isset($statusColorArr[$status]) ? trim($statusColorArr[$status]) : '';
                $isEdit = true;
                $isDelete = true;
                $isStatistic = true;
                $isCheck = false;
                $lookCheck = false;
                $advert_id = isset($v['id']) ? intval($v['id']) : 0;
                if ($status == 2) {
                    // 待审核不允许编辑 不允许删除
                    $isEdit = false;
                    $isDelete = false;
                    $isStatistic = false;
                    if (in_array($advert_id, $checkAdvertIds)) {
                        $isCheck = true;
                    } else {
                        $lookCheck = true;
                    }
                }
                $v['isEdit'] = $isEdit;
                $v['isDelete'] = $isDelete;
                $v['isStatistic'] = $isStatistic;
                $v['isCheck'] = $isCheck;
                $v['lookCheck'] = $lookCheck;
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif ($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/' . $v['pic']);
                }
            }
        }
        if ($jz_open) {
            $jz_open = $jz_open ? 1 : 0;
            $this->assign('jz_open', $jz_open);
            if (!empty($this->system_session['menus']) && !in_array(907, $this->system_session['menus'])) {
                $checkSetUrl = '';
            } else {
                $checkSetUrl = $this->config['site_url'] . '/v20/public/platform/#/system/platform.villageAdvert/checkSet';
            }
            $this->assign('checkSetUrl', $checkSetUrl);
        }
        $this->assign('is_platform', $is_platform);
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->assign('show_nav', $show_nav);
        $this->display();
    }

    public function house_adver_add()
    {
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        $this->display();
    }

    public function adver_modify()
    {
        $cat_id = M('House_adver_category')->where(['cat_key' => 'house_app_index_top'])->getField('cat_id');
        if (!$cat_id) {
            $this->error('添加失败！请联系管理员添加【社区:APP/小程序首页顶部轮播图】广告类别~');
        }
        $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
        if (!$image['error']) {
            $_POST = array_merge($_POST, $image['url']);
        } else {
            $this->error($image['msg'] ? $image['msg'] : $image['message']);
        }
        $data = [];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error(0, '请填写广告名称~');
        }
        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'];
        }
        //todo 记录下小区功能库返回的参数
        $data['source_type']=D('House_link')->get_source_type($_POST['url']);
        $data['cat_id'] = $cat_id;
        if($this->is_visualize > 1){
            if(!isset($_POST['is_platform']) || empty($_POST['is_platform'])){
                $data['is_platform'] =$_POST['is_platform'];
            }
        }
        $database_adver = D('House_adver');
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error(0, '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error(0, '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error('播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error('播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error('投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error('投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        if(isset($_POST['wxapp_id'])){
            $data['wxapp_id']=trim($_POST['wxapp_id']);
            $data['wxapp_page']=trim($_POST['wxapp_page']);
        }
        if ($id = $database_adver->data($data)->add()) {
            D('Image')->update_table_id($data['pic'], $id, 'adver');

            D('Config')->clearCache();

            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $database_adver->data(['village_id'=>-1])->where(['id'=>$id])->save();
                $village_arr = explode(',', $_POST['village_arr']);
                if (count($village_arr) == 1) {
                    $data_count = [];
                    $data_count['village_id'] = $village_arr;
                    $data_count['adver_id'] = $id;
                    $data_count['add_time'] = time();
                    $data_count['last_time'] = time();
                    D('House_adver_village_count')->data($data_count)->add();
                } else {
                    foreach ($village_arr as $value) {
                        if (!empty($value)) {
                            $value = trim($value);
                            $data_count = [];
                            $data_count['village_id'] = $value;
                            $data_count['adver_id'] = $id;
                            $data_count['add_time'] = time();
                            $data_count['last_time'] = time();
                            D('House_adver_village_count')->data($data_count)->add();
                        }
                    }

                }

            }
            $jz_open = D('Config_customization')->getWSXCustomizedOpen();
            if ($jz_open) {
                // 检查下是否需要走审核逻辑
                $from_user = ['type' => 'system_admin', 'admin_id' => $this->system_session['id'], 'account' => $this->system_session['account']];
                if (isset($this->system_session['realname']) && $this->system_session['realname']) {
                    $from_user['name'] = $this->system_session['realname'];
                } elseif (isset($this->system_session['nickname']) && $this->system_session['nickname']) {
                    $from_user['name'] = $this->system_session['nickname'];
                }
                if (isset($this->system_session['phone']) && $this->system_session['phone']) {
                    $from_user['phone'] = $this->system_session['phone'];
                }
                $name = isset($from_user['name']) ? trim($from_user['name']) : '';
                $phone = isset($from_user['phone']) ? trim($from_user['phone']) : '';
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                $params = [
                    'id' => $id, 'village_id' => 0, 'from_user' => $from_user, 'name' => $name, 'phone' => $phone, 'admin_id' => $this->system_session['id'],
                ];
                $result = D('CommunityCheckHandleService')->tryCheckHouseAdvertBox($params);
                fdump_api($result, '$result');
            }

            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"添加成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11, '添加成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"添加成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //$this->frame_submit_tips(1, '添加成功！');
            }
        } else {
            exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error( '添加失败！请重试~');
        }


    }

    public function house_adver_edit()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');

        $database_adver = D('House_adver');
        $condition_adver['id'] = $_GET['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();

        if (empty($now_adver)) {
            $this->error( '该广告不存在！');
        }
        if (strpos($now_adver['pic'], '/upload') !== false) {
            $now_adver['pic'] = replace_file_domain($now_adver['pic']);
        } elseif ($now_adver['pic']) {
            $now_adver['pic'] = replace_file_domain('/upload/adver/' . $now_adver['pic']);
        }

        if ($now_adver['play_start_time']) {
            $play_start_time_txt = mb_substr($now_adver['play_start_time'], 0, 2) . ':' . mb_substr($now_adver['play_start_time'], -2, 2);
            $now_adver['play_start_time_txt'] = $play_start_time_txt;
        }
        if ($now_adver['play_end_time']) {
            $play_end_time_txt = mb_substr($now_adver['play_end_time'], 0, 2) . ':' . mb_substr($now_adver['play_end_time'], -2, 2);
            $now_adver['play_end_time_txt'] = $play_end_time_txt;
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_start_time'] = '';
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_end_time'] = '';
        }
        $village_arr = D('House_adver_village_count')->field(true)->where(['adver_id' => $now_adver['id']])->getField('village_id', true);
        if (!empty($village_arr)) {
            $village_arr = array_unique($village_arr);
            $village_arr = implode(',', $village_arr);
            $now_adver['village_arr'] = $village_arr;
        }
        $this->assign('now_adver', $now_adver);
        $now_category = $this->frame_check_get_category(1);
        $this->assign('now_category', $now_category);

        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);

        $this->display();
    }


    public function adver_amend()
    {
        $cat_key = trim($_GET['cat_key']) ? trim($_GET['cat_key']) : 'house_app_index_top';
        $cat_id = M('House_adver_category')->where(['cat_key' => $cat_key])->getField('cat_id');
        if (!$cat_id) {
            $this->error( '添加失败！请联系管理员添加【社区:APP/小程序首页顶部轮播图】广告类别~');
        }
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if (!$now_adver) {
            $this->error( '编辑的对象不存在~');
        }

        if ($_FILES['pic']['error'] != 4) {
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            } else {
                $this->error( $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $data = [];
        $data['id'] = $now_adver['id'];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error( '请填写广告名称~');
        }
        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'] ? $_POST['bg_color'] : '';
        }
        //todo 记录下小区功能库返回的参数
        $data['source_type']=D('House_link')->get_source_type($_POST['url']);
        $data['cat_id'] = $cat_id;
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error( '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error( '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error( '播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error( '播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error('投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error( '投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        if(isset($_POST['wxapp_id'])){
            $data['wxapp_id']=trim($_POST['wxapp_id']);
            $data['wxapp_page']=trim($_POST['wxapp_page']);
        }
        $set = $database_adver->data($data)->save();
        if ($set) {
            D('Image')->update_table_id($_POST['pic'], $_POST['id'], 'adver');
            D('Config')->clearCache();
            $village_count=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->select();
            if (!empty($village_count)){
                $delete=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->delete();
                fdump_api(['删除'.__LINE__,D('House_adver_village_count')->_sql()],'village_count',true);
            }
            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $village_arr = explode(',', $_POST['village_arr']);
                $database_adver->data(['village_id'=>-1])->where(['id'=>$now_adver['id']])->save();
              //  D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->delete();
                foreach ($village_arr as $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $data_count = [];
                        $data_count['village_id'] = $value;
                        $data_count['adver_id'] = $now_adver['id'];
                        $data_count['last_time'] = time();
                        $data_count['add_time'] = time();
                        D('House_adver_village_count')->data($data_count)->add();
                    }
                }
            }
            if ($_POST['pic']) {
                if (strpos($now_adver['pic'], '/upload') !== false) {
                    //unlink('.' . $now_adver['pic']);
                } elseif ($now_adver['pic']) {
                    //unlink('./upload/adver/' . $now_adver['pic']);
                }
            }
            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"编辑成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11, '编辑成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"编辑成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
               // $this->frame_submit_tips(1, '编辑成功！');
            }

        } else {
            exit('<html><head><script>window.top.msg(0,"编辑失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error( '编辑失败！请重试~');
        }


    }


    public function house_adver_check()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');

        $database_adver = D('House_adver');
        $condition_adver['id'] = $_GET['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();

        if (empty($now_adver)) {
            $this->error( '该广告不存在！');
        }
        if (strpos($now_adver['pic'], '/upload') !== false) {
            $now_adver['pic'] = replace_file_domain($now_adver['pic']);
        } elseif ($now_adver['pic']) {
            $now_adver['pic'] = replace_file_domain('/upload/adver/' . $now_adver['pic']);
        }

        if ($now_adver['play_start_time']) {
            $play_start_time_txt = mb_substr($now_adver['play_start_time'], 0, 2) . ':' . mb_substr($now_adver['play_start_time'], -2, 2);
            $now_adver['play_start_time_txt'] = $play_start_time_txt;
        }
        if ($now_adver['play_end_time']) {
            $play_end_time_txt = mb_substr($now_adver['play_end_time'], 0, 2) . ':' . mb_substr($now_adver['play_end_time'], -2, 2);
            $now_adver['play_end_time_txt'] = $play_end_time_txt;
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_start_time'] = '';
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_end_time'] = '';
        }
        $village_arr = D('House_adver_village_count')->field(true)->where(['adver_id' => $now_adver['id']])->getField('village_id', true);
        if (!empty($village_arr)) {
            $village_arr = array_unique($village_arr);
            $villageNameArr = D('House_village')->where(['village_id' => ['in', $village_arr]])->getField('village_name', true);
            $villageNames = implode(' ； ', $villageNameArr);
            $now_adver['villageNames'] = $villageNames;
        } else {
            $now_adver['villageNames'] = '无';
        }
        $statusArr = [0 => '关闭', 1 => '正常', 2 => '待审核', 3 => '审核拒绝', 4=>'已删除'];
        $statusColorArr = [0 => 'red', 1 => 'green', 2 => '#2681f3', 3 => 'red', 4=>'red'];
        $status = isset($now_adver['status']) ? intval($now_adver['status']) : 0;
        $now_adver['status_text'] = isset($statusArr[$status]) ? trim($statusArr[$status]) : '';
        $now_adver['status_color'] = isset($statusColorArr[$status]) ? trim($statusColorArr[$status]) : '';
        $this->assign('now_adver', $now_adver);
        $now_category = $this->frame_check_get_category(1);
        $this->assign('now_category', $now_category);

        //小程序列表
        if (isset($now_adver['wxapp_id']) && trim($now_adver['wxapp_id'])) {
            $wxappName = M('Wxapp_other')->where(['appid' => trim($now_adver['wxapp_id'])])->getField('name');
        }
        !$wxappName && $wxappName = '无';
        $this->assign('wxappName', $wxappName);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);


        $jz_open = D('Config_customization')->getWSXCustomizedOpen();
        if ($jz_open) {
            $params = [
                'id' => $_GET['id'], 'village_id' => 0,  'admin_id' => $this->system_session['id'],
            ];
            $checkLog = D('CommunityCheckHandleService')->tryGetCheckLogBox($params);
            $this->assign('checkLog', $checkLog);
        }
        
        $this->display();
    }
    
    public function advert_check() {
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if (!$now_adver) {
            $this->error( '审核的对象不存在~');
        }
        $check_status = isset($_POST['check_status']) ? intval($_POST['check_status']) : 0;
        if (!in_array($check_status, [2,3])) {
            $this->error( '请选择正确审核状态~');
        }
        $check_reason = isset($_POST['check_reason']) ? trim($_POST['check_reason']) : '';
        $check_reason = htmlspecialchars_decode($check_reason);
        if ($check_status == 3 && !$check_reason) {
            $this->error( '审核拒绝请填写审核理由~');
        }
        $from_user = ['type' => 'system_admin', 'admin_id' => $this->system_session['id'], 'account' => $this->system_session['account']];
        if (isset($this->system_session['realname']) && $this->system_session['realname']) {
            $from_user['name'] = $this->system_session['realname'];
        } elseif (isset($this->system_session['nickname']) && $this->system_session['nickname']) {
            $from_user['name'] = $this->system_session['nickname'];
        }
        if (isset($this->system_session['phone']) && $this->system_session['phone']) {
            $from_user['phone'] = $this->system_session['phone'];
        }
        $name = isset($from_user['name']) ? trim($from_user['name']) : '';
        $phone = isset($from_user['phone']) ? trim($from_user['phone']) : '';
        $params = [
            'id' => $_POST['id'], 'check_status' => $check_status, 'check_reason' => $check_reason, 'village_id' => 0, 'from_user' => $from_user, 'name' => $name, 'phone' => $phone, 'admin_id' => $this->system_session['id'],
        ];
        $result = D('CommunityCheckHandleService')->tryHouseAdvertCheckBox($params);
        if (isset($result['err']) && intval($result['err']) >= 1) {
            $this->error( $result['msg']);
        }
        if(isset($_POST['source']) && $_POST['source'] ==1 ){
            D('House_link')->frame_submit_tips(11, '操作成功！');
        }else{
            $this->frame_submit_tips(1, '操作成功！');
        }
    }
    
    public function house_adver_del()
    {
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        // $condition_adver_where['add_flag']=$now_adver['add_flag'];
        if ($database_adver->where($condition_adver)->delete()) {
            if (strpos($now_adver['pic'], '/upload') !== false) {
                //unlink('.' . $now_adver['pic']);
            } elseif ($now_adver['pic']) {
                //unlink('./upload/adver/' . $now_adver['pic']);
            }
            D('Config')->clearCache();
            $this->success('删除成功');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    // 中间轮播广告

    /**
     * Notes: 中间轮播广告
     * @author: wanzy
     * @date_time: 2021/1/14 9:25
     */
    public function house_middle_advert_list()
    {
        $is_platform=2;
        $now_category = M('House_adver_category')->where(['cat_key' => 'house_app_index_center'])->find();
        if (!$now_category) {
            // 如果没有直接添加
            $advert_cat = [
                'cat_id' => 2,
                'cat_key' => 'house_app_index_center',
                'cat_name' => '社区:APP/小程序中间轮播图',
                'cat_type' => 0,
                'is_system' => 1,
                'size_info' => '640*206',
            ];
            $cat_id = M('House_adver_category')->data($advert_cat)->add();
            if ($cat_id) {
                $now_category = $advert_cat;
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
        $condition_adver['a.status'] = array('neq',4);
//        $condition_adver['a.village_id'] = 0;
        if($this->is_visualize > 0){
            $condition_adver['_string'] = '( a.village_id in (0,-1))';
        }else{
            $condition_adver['_string'] = '(a.add_type=1 OR  a.village_id in (0,-1))';
        }

        if ($this->system_session['area_id']) {
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($temp['area_type'] == 1) {
                    $city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'], true);
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
                $condition_adver['a.city_id'] = array('in', $area_id);
            } else {
                $condition_adver['a.city_id'] = $area_id;
            }
        }
        $field = "a.*,b.look_num,b.click_num,(CASE WHEN (`a`.`status`='0') then 1 WHEN (`a`.`status` = 1) then 0 ELSE 2 END) as `t_sort`";

        // $adver_list = $database_adver->field('a.*,b.look_num,b.click_num')
        $adver_list = $database_adver->field($field)
            ->join(" as a LEFT JOIN " . C('DB_PREFIX') . "house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->order('t_sort ASC,`a`.`id` DESC')
            ->select();
//        print_r($database_adver->_sql());die;
//        fdump(['line:'.__LINE__,$database_adver->_sql(),$adver_list],'$adver_list');
        if ($adver_list) {
            foreach ($adver_list as &$v) {
                if($many_city == 1){
                    $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                    if (empty($city)) {
                        $v['city_id'] = '通用';
                    } else {
                        $v['city_id'] = $city['area_name'];
                    }
                }
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif ($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/' . $v['pic']);
                }
            }
        }
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->assign('is_platform', $is_platform);
        $this->display();
    }

    public function house_middle_advert_add()
    {
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        $this->display();
    }

    public function middle_adver_modify()
    {
        $cat_id = M('House_adver_category')->where(['cat_key' => 'house_app_index_center'])->getField('cat_id');
        if (!$cat_id) {
            $this->error( '添加失败！请联系管理员添加【社区:APP/小程序中间轮播图】广告类别~');
        }
        $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
        if (!$image['error']) {
            $_POST = array_merge($_POST, $image['url']);
        } else {
            $this->error($image['msg'] ? $image['msg'] : $image['message']);
        }
        $data = [];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error( '请填写广告名称~');
        }
        /*        if (isset($_POST['province_idss'])) {
                    $data['province_id'] = $_POST['province_idss'];
                }
                if (isset($_POST['city_idss'])) {
                    $data['city_id'] = $_POST['city_idss'];
                }
                if (isset($_POST['area_id'])) {
                    $data['area_id'] = $_POST['area_id'];
                }
                if (isset($_POST['street_idss'])) {
                    $data['street_id'] = $_POST['street_idss'];
                }
                if (isset($_POST['community_idss'])) {
                    $data['community_id'] = $_POST['community_idss'];
                }
                if (isset($_POST['village_idss']) && $_POST['village_idss']) {
                    $data['village_id'] = intval($_POST['village_idss']);
                } else {
                    $data['village_id'] = 0;
                }*/
        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'] ? $_POST['bg_color'] : '';
        }
        $data['last_time'] = time();
        $data['cat_id'] = $cat_id;
        $database_adver = D('House_adver');
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error( '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error( '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error( '播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error( '播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error('投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error( '投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        //todo 记录下小区功能库返回的参数
        $data['source_type']=D('House_link')->get_source_type($_POST['url']);
        if($this->is_visualize > 1){
            if(!isset($_POST['is_platform']) || empty($_POST['is_platform'])){
                $data['is_platform'] =$_POST['is_platform'];
            }
        }
        if(isset($_POST['source']) && ($_POST['source'] == 1)){
            $data['is_platform']=2;
        }
        if(isset($_POST['wxapp_id'])){
            $data['wxapp_id']=trim($_POST['wxapp_id']);
            $data['wxapp_page']=trim($_POST['wxapp_page']);
        }
        if ($id = $database_adver->data($data)->add()) {
            D('Image')->update_table_id($data['pic'], $id, 'adver');
            D('Config')->clearCache();
            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $database_adver->data(['village_id'=>-1])->where(['id'=>$id])->save();
                $village_arr = explode(',', $_POST['village_arr']);
                if (count($village_arr) == 1) {
                    $data_count = [];
                    $data_count['village_id'] = $village_arr;
                    $data_count['adver_id'] = $id;
                    $data_count['add_time'] = time();
                    $data_count['last_time'] = time();
                    D('House_adver_village_count')->data($data_count)->add();
                } else {
                    foreach ($village_arr as $value) {
                        if (!empty($value)) {
                            $value = trim($value);
                            $data_count = [];
                            $data_count['village_id'] = $value;
                            $data_count['adver_id'] = $id;
                            $data_count['add_time'] = time();
                            $data_count['last_time'] = time();
                            D('House_adver_village_count')->data($data_count)->add();
                        }
                    }

                }

            }

            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"添加成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11, '添加成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"添加成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //$this->frame_submit_tips(1, '添加成功！');
            }
        } else {
            exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error( '添加失败！请重试~');
        }
    }

    public function house_middle_adver_edit()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');

        $database_adver = D('House_adver');
        $condition_adver['id'] = $_GET['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();

        if (empty($now_adver)) {
            $this->error('该广告不存在！');
        }
        if (strpos($now_adver['pic'], '/upload') !== false) {
            $now_adver['pic'] = replace_file_domain($now_adver['pic']);
        } elseif ($now_adver['pic']) {
            $now_adver['pic'] = replace_file_domain('/upload/adver/' . $now_adver['pic']);
        }

        if ($now_adver['play_start_time']) {
            $play_start_time_txt = mb_substr($now_adver['play_start_time'], 0, 2) . ':' . mb_substr($now_adver['play_start_time'], -2, 2);
            $now_adver['play_start_time_txt'] = $play_start_time_txt;
        }
        if ($now_adver['play_end_time']) {
            $play_end_time_txt = mb_substr($now_adver['play_end_time'], 0, 2) . ':' . mb_substr($now_adver['play_end_time'], -2, 2);
            $now_adver['play_end_time_txt'] = $play_end_time_txt;
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_start_time'] = '';
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_end_time'] = '';
        }
        $village_arr = D('House_adver_village_count')->field(true)->where(['adver_id' => $now_adver['id']])->getField('village_id', true);
        if (!empty($village_arr)) {
            $village_arr = array_unique($village_arr);
            $village_arr = implode(',', $village_arr);
            $now_adver['village_arr'] = $village_arr;
        }
        $this->assign('now_adver', $now_adver);

        $now_category = $this->frame_check_get_category(1);
        $this->assign('now_category', $now_category);

        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);

        $this->display();
    }

    public function middle_adver_amend()
    {
        $cat_key = trim($_GET['cat_key']) ? trim($_GET['cat_key']) : 'house_app_index_center';
        $cat_id = M('House_adver_category')->where(['cat_key' => $cat_key])->getField('cat_id');
        if (!$cat_id) {
            $this->error('编辑失败！请联系管理员添加【社区:APP/小程序中间轮播图】广告类别~');
        }
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if (!$now_adver) {
            $this->error( '编辑的对象不存在~');
        }

        if ($_FILES['pic']['error'] != 4) {
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            } else {
                $this->error( $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $data = [];
        $data['id'] = $now_adver['id'];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error( '请填写广告名称~');
        }
        /* if (isset($_POST['province_idss'])) {
             $data['province_id'] = $_POST['province_idss'];
         }
         if (isset($_POST['city_idss'])) {
             $data['city_id'] = $_POST['city_idss'];
         }
         if (isset($_POST['area_id'])) {
             $data['area_id'] = $_POST['area_id'];
         }
         if (isset($_POST['street_idss'])) {
             $data['street_id'] = $_POST['street_idss'];
         }
         if (isset($_POST['community_idss'])) {
             $data['community_id'] = $_POST['community_idss'];
         }*/

        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'] ? $_POST['bg_color'] : '';
        }
        $data['last_time'] = time();
        $data['cat_id'] = $cat_id;
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error( '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error( '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error( '播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error('播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error( '投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error('投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        if(isset($_POST['wxapp_id'])){
            $data['wxapp_id']=trim($_POST['wxapp_id']);
            $data['wxapp_page']=trim($_POST['wxapp_page']);
        }
        //todo 记录下小区功能库返回的参数
        $data['source_type']=D('House_link')->get_source_type($_POST['url']);
        $set = $database_adver->data($data)->save();
        if ($set) {
            D('Image')->update_table_id($_POST['pic'], $_POST['id'], 'adver');
            D('Config')->clearCache();
            $village_count=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->select();
            if (!empty($village_count)){
                $delete=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->delete();
                fdump_api(['删除'.__LINE__,D('House_adver_village_count')->_sql()],'village_count',true);
            }
            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $village_arr = explode(',', $_POST['village_arr']);
                $database_adver->data(['village_id'=>-1])->where(['id'=>$now_adver['id']])->save();
             //   D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->delete();
                foreach ($village_arr as $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $data_count = [];
                        $data_count['village_id'] = $value;
                        $data_count['adver_id'] = $now_adver['id'];
                        $data_count['last_time'] = time();
                        $data_count['add_time'] = time();
                        D('House_adver_village_count')->data($data_count)->add();
                    }
                }
            }
            if ($_POST['pic']) {
                if (strpos($now_adver['pic'], '/upload') !== false) {
                    //unlink('.' . $now_adver['pic']);
                } elseif ($now_adver['pic']) {
                   // unlink('./upload/adver/' . $now_adver['pic']);
                }
            }
            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"编辑成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
               // D('House_link')->frame_submit_tips(11, '编辑成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"编辑成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //$this->frame_submit_tips(1, '编辑成功！');
            }
        } else {
            exit('<html><head><script>window.top.msg(0,"编辑失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error( '编辑失败！请重试~');
        }

    }

    // 一键开门广告管理
    public function house_open_advert_list()
    {
        $now_category = M('House_adver_category')->where(['cat_key' => 'house_open_advert'])->find();
        if (!$now_category) {
            // 如果没有直接添加
            $advert_cat = [
                'cat_id' => 9,
                'cat_key' => 'house_open_advert',
                'cat_name' => '一键开门广告',
                'cat_type' => 0,
                'is_system' => 1,
                'size_info' => '750*988',
            ];
            $cat_id = M('House_adver_category')->data($advert_cat)->add();
            if ($cat_id) {
                $now_category = $advert_cat;
            }
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver = [];
        $condition_adver['a.cat_id'] = $now_category['cat_id'];
//        $condition_adver['a.village_id'] = 0;
        $condition_adver['_string'] = '(a.add_type=1 OR  a.village_id=0)';

        if ($this->system_session['area_id']) {
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($temp['area_type'] == 1) {
                    $city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'], true);
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
                $condition_adver['a.city_id'] = array('in', $area_id);
            } else {
                $condition_adver['a.city_id'] = $area_id;
            }
        }
        $field = "a.*,b.look_num,b.click_num,(CASE WHEN (`a`.`status`='0') then 1 WHEN (`a`.`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        // $adver_list = $database_adver->field('a.*,b.look_num,b.click_num')
        $adver_list = $database_adver->field($field)
            ->join(" as a LEFT JOIN " . C('DB_PREFIX') . "house_adver_record as b ON (b.adver_id=a.id AND b.type=1 AND b.business_type='house_adver')")
            ->where($condition_adver)
            ->order('`t_sort` ASC,`a`.`id` DESC')
            ->select();
        if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
                if (strpos($v['pic'], '/upload') !== false) {
                    $v['pic'] = replace_file_domain($v['pic']);
                } elseif ($v['pic']) {
                    $v['pic'] = replace_file_domain('/upload/adver/' . $v['pic']);
                }
            }
        }
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->display();
    }

    public function house_open_advert_add()
    {
        $this->display();
    }

    public function open_adver_modify()
    {
        $cat_id = M('House_adver_category')->where(['cat_key' => 'house_open_advert'])->getField('cat_id');
        if (!$cat_id) {
            $this->error( '添加失败！请联系管理员添加【一键开门广告】广告类别~');
        }
        $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
        if (!$image['error']) {
            $_POST = array_merge($_POST, $image['url']);
        } else {
            $this->error( $image['msg'] ? $image['msg'] : $image['message']);
        }
        $data = [];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error('请填写广告名称~');
        }
        /* if (isset($_POST['province_idss'])) {
             $data['province_id'] = $_POST['province_idss'];
         }
         if (isset($_POST['city_idss'])) {
             $data['city_id'] = $_POST['city_idss'];
         }
         if (isset($_POST['area_id'])) {
             $data['area_id'] = $_POST['area_id'];
         }
         if (isset($_POST['street_idss'])) {
             $data['street_id'] = $_POST['street_idss'];
         }
         if (isset($_POST['community_idss'])) {
             $data['community_id'] = $_POST['community_idss'];
         }
         if (isset($_POST['village_idss']) && $_POST['village_idss']) {
             $data['village_id'] = intval($_POST['village_idss']);
         } else {
             $data['village_id'] = 0;
         }*/
        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'] ? $_POST['bg_color'] : '';
        }
        $data['last_time'] = time();
        $data['cat_id'] = $cat_id;
        $database_adver = D('House_adver');
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error( '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error( '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error( '播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error( '播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error( '投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error( '投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        if ($id = $database_adver->data($data)->add()) {
            D('Image')->update_table_id($data['pic'], $id, 'adver');
            D('Config')->clearCache();
            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $database_adver->data(['village_id'=>-1])->where(['id'=>$id])->save();
                $village_arr = explode(',', $_POST['village_arr']);
                if (count($village_arr) == 1) {
                    $data_count = [];
                    $data_count['village_id'] = $village_arr;
                    $data_count['adver_id'] = $id;
                    $data_count['add_time'] = time();
                    $data_count['last_time'] = time();
                    D('House_adver_village_count')->data($data_count)->add();
                } else {
                    foreach ($village_arr as $value) {
                        if (!empty($value)) {
                            $value = trim($value);
                            $data_count = [];
                            $data_count['village_id'] = $value;
                            $data_count['adver_id'] = $id;
                            $data_count['add_time'] = time();
                            $data_count['last_time'] = time();
                            D('House_adver_village_count')->data($data_count)->add();
                        }
                    }

                }

            }
            $this->frame_submit_tips(1, '添加成功！');
        } else {
            $this->error('添加失败！请重试~');
        }
    }

    public function house_open_adver_edit()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');

        $database_adver = D('House_adver');
        $condition_adver['id'] = $_GET['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();

        if (empty($now_adver)) {
            $this->error('该广告不存在！');
        }
        if (strpos($now_adver['pic'], '/upload') !== false) {
            $now_adver['pic'] = replace_file_domain($now_adver['pic']);
        } elseif ($now_adver['pic']) {
            $now_adver['pic'] = replace_file_domain('/upload/adver/' . $now_adver['pic']);
        }

        if ($now_adver['play_start_time']) {
            $play_start_time_txt = mb_substr($now_adver['play_start_time'], 0, 2) . ':' . mb_substr($now_adver['play_start_time'], -2, 2);
            $now_adver['play_start_time_txt'] = $play_start_time_txt;
        }
        if ($now_adver['play_end_time']) {
            $play_end_time_txt = mb_substr($now_adver['play_end_time'], 0, 2) . ':' . mb_substr($now_adver['play_end_time'], -2, 2);
            $now_adver['play_end_time_txt'] = $play_end_time_txt;
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_start_time'] = '';
        }
        if (!$now_adver['effective_start_time']) {
            $now_adver['effective_end_time'] = '';
        }
        $village_arr = D('House_adver_village_count')->field(true)->where(['adver_id' => $now_adver['id']])->getField('village_id', true);
        if (!empty($village_arr)) {
            $village_arr = array_unique($village_arr);
            $village_arr = implode(',', $village_arr);
            $now_adver['village_arr'] = $village_arr;
        }

        $this->assign('now_adver', $now_adver);

        $now_category = $this->frame_check_get_category(1);
        $this->assign('now_category', $now_category);

        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);

        $this->display();
    }

    public function open_adver_amend()
    {
        $cat_key = trim($_GET['cat_key']) ? trim($_GET['cat_key']) : 'house_open_advert';
        $cat_id = M('House_adver_category')->where(['cat_key' => $cat_key])->getField('cat_id');
        if (!$cat_id) {
            $this->error( '添加失败！请联系管理员添加【一键开门广告】广告类别~');
        }
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if (!$now_adver) {
            $this->error( '编辑的对象不存在~');
        }

        if ($_FILES['pic']['error'] != 4) {
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            } else {
                $this->error( $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $data = [];
        $data['id'] = $now_adver['id'];
        if (isset($_POST['name'])) {
            $data['name'] = $_POST['name'];
        } else {
            $this->error( '请填写广告名称~');
        }
        /*        if (isset($_POST['province_idss'])) {
                    $data['province_id'] = $_POST['province_idss'];
                }
                if (isset($_POST['city_idss'])) {
                    $data['city_id'] = $_POST['city_idss'];
                }
                if (isset($_POST['area_id'])) {
                    $data['area_id'] = $_POST['area_id'];
                }
                if (isset($_POST['street_idss'])) {
                    $data['street_id'] = $_POST['street_idss'];
                }
                if (isset($_POST['community_idss'])) {
                    $data['community_id'] = $_POST['community_idss'];
                }
                if (isset($_POST['village_idss']) && $_POST['village_idss']) {
                    $data['village_id'] = intval($_POST['village_idss']);
                } else {
                    $data['village_id'] = 0;
                }*/
        $data['add_type'] = 1;
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($_POST['url']) {
            $data['url'] = $_POST['url'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if ($_POST['pic']) {
            $data['pic'] = $_POST['pic'];
        }
        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['sort'])) {
            $data['sort'] = $_POST['sort'];
        }
        if (isset($_POST['bg_color'])) {
            $data['bg_color'] = $_POST['bg_color'] ? $_POST['bg_color'] : '';
        }
        $data['last_time'] = time();
        $data['cat_id'] = $cat_id;
        if ($this->config['house_advert_targeted_delivery'] == 1) {
            if ($_POST['effective_start_time'] && $_POST['effective_end_time']) {
                $effective_start_time = strtotime($_POST['effective_start_time']);
                $effective_end_time = strtotime($_POST['effective_end_time']);
                if ($effective_start_time && $effective_end_time && $effective_end_time <= $effective_start_time) {
                    $this->error( '广告有效期 结束时间需大于起始时间~');
                } elseif ($effective_start_time && $effective_end_time) {
                    $data['effective_start_time'] = $effective_start_time;
                    $data['effective_end_time'] = $effective_end_time;
                }
            } elseif ($_POST['effective_start_time'] || $_POST['effective_end_time']) {
                $this->error( '广告有效期填写了需要对应都填写~');
            }
            if ($_POST['play_start_time'] && $_POST['play_end_time']) {
                $play_start_time = str_replace(':', '', $_POST['play_start_time']);
                $play_end_time = str_replace(':', '', $_POST['play_end_time']);
                if ($play_start_time && $play_end_time && intval($play_end_time) <= intval($play_start_time)) {
                    $this->error( '播放时间段 结束时段需大于起始时段~');
                } elseif ($play_start_time && $play_end_time) {
                    $data['play_start_time'] = $play_start_time;
                    $data['play_end_time'] = $play_end_time;
                }
            } elseif ($_POST['play_start_time'] || $_POST['play_end_time']) {
                $this->error( '播放时间段填写了需要对应都填写~');
            }
            if ($_POST['lm_age_min'] && $_POST['lm_age_max']) {
                $lm_age_min = intval($_POST['lm_age_min']);
                $lm_age_max = intval($_POST['lm_age_max']);
                if ($lm_age_min && $lm_age_max && intval($lm_age_max) <= intval($lm_age_min)) {
                    $this->error('投放年龄范围 区间后的数字需大于区间前的数字~');
                } elseif ($lm_age_min && $lm_age_max) {
                    $data['lm_age_min'] = $lm_age_min;
                    $data['lm_age_max'] = $lm_age_max;
                }
            } elseif ($_POST['lm_age_min'] || $_POST['lm_age_max']) {
                $this->error('投放年龄范围填写了需要对应都填写~');
            }
            if (isset($_POST['lm_sex'])) {
                $data['lm_sex'] = $_POST['lm_sex'] ? intval($_POST['lm_sex']) : 0;
            }
            if (isset($_POST['virtual_look_num'])) {
                $data['virtual_look_num'] = $_POST['virtual_look_num'] ? intval($_POST['virtual_look_num']) : 0;
            }
            if (isset($_POST['virtual_click_num'])) {
                $data['virtual_click_num'] = $_POST['virtual_click_num'] ? intval($_POST['virtual_click_num']) : 0;
            }
            if (isset($_POST['lm_day_look_max'])) {
                $data['lm_day_look_max'] = $_POST['lm_day_look_max'] ? intval($_POST['lm_day_look_max']) : 0;
            }
            if (isset($_POST['lm_day_click_max'])) {
                $data['lm_day_click_max'] = $_POST['lm_day_click_max'] ? intval($_POST['lm_day_click_max']) : 0;
            }
        }
        $data['last_time'] = time();
        $data['village_id'] = 0;
        $set = $database_adver->data($data)->save();
        if ($set) {
            D('Image')->update_table_id($_POST['pic'], $_POST['id'], 'adver');
            D('Config')->clearCache();
            $village_count=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->select();
            if (!empty($village_count)){
                $delete=D('House_adver_village_count')->where(['adver_id' => $now_adver['id']])->delete();
                fdump_api(['删除'.__LINE__,D('House_adver_village_count')->_sql()],'village_count',true);
            }

            if (isset($_POST['village_arr']) && !empty($_POST['village_arr']) && trim($_POST['village_arr'])) {
                $village_arr = explode(',', $_POST['village_arr']);
                $database_adver->data(['village_id'=>-1])->where(['id'=>$now_adver['id']])->save();
                foreach ($village_arr as $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $data_count = [];
                        $data_count['village_id'] = $value;
                        $data_count['adver_id'] = $now_adver['id'];
                        $data_count['last_time'] = time();
                        $data_count['add_time'] = time();
                        D('House_adver_village_count')->data($data_count)->add();
                    }
                }
            }
            if ($_POST['pic']) {
                if (strpos($now_adver['pic'], '/upload') !== false) {
                    //unlink('.' . $now_adver['pic']);
                } elseif ($now_adver['pic']) {
                    //unlink('./upload/adver/' . $now_adver['pic']);
                }
            }
            $this->frame_submit_tips(1, '编辑成功！');
        } else {
            $this->error( '编辑失败！请重试~');
        }
    }

    protected function get_category($cat_id)
    {
        $database_adver_category = D('House_adver_category');
        $condition_adver_category['cat_id'] = $cat_id;
        $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
        return $now_category;
    }

    protected function frame_check_get_category($cat_id)
    {
        $now_category = $this->get_category($cat_id);
        if (empty($now_category)) {
            $this->error('分类不存在！');
        } else {
            return $now_category;
        }
    }

    protected function check_get_category($cat_id)
    {
        $now_category = $this->get_category($cat_id);
        if (empty($now_category)) {
            $this->error_tips('分类不存在！');
        } else {
            return $now_category;
        }
    }


    public function house_nav_list()
    {
        $row_type = isset($_GET['row_type']) ? remove_xss($_GET['row_type']) : '';
        $database_house_village_nav = D('House_village_nav');
          $where['village_id'] = array('in',[0,-1]);
        $where['status'] = array('neq',4);
        $where['type'] = 0;
        $view_num = 7;
        if($row_type == 'single'){
            $where['type'] = 1;
            // 查询分配权限的小区
            $village_arr = $this->get_visualize_assign(11);
            $where_info = [];
            $where_info['village_id'] = array('not in',$village_arr);
            $viewNum = D('House_village_info')->where($where_info)->field('view_num')->find();
            $view_num = $viewNum['view_num'];
        }else if($row_type == 'middle'){
            $where['type'] = 2;
        }
        $this->assign('view_num', $view_num);

          //$where = '1=1';
        $field = "*,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $result = $database_house_village_nav->house_village_nav_page_list($where, $field, 't_sort ASC, sort desc', 20,'',1);

        if (!$result) {
            $this->error('数据处理有误！');
        }

        $this->assign('result', $result['result']);
        $this->display();
    }
    public function index_nav_pop(){

        $village_id = 0;
        $database_house_village_nav = D('House_village_nav');
        
        $where=array('type'=>20,'block_type_id'=>'house_wap_index_pop','is_community_platform'=>1,'village_id'=>'0');
        $key_name=isset($_GET['key_name']) ? trim($_GET['key_name']):'';
        if($key_name){
            $where['name']=array('like','%'.$key_name.'%');
        }
        $status=isset($_GET['status']) ? intval($_GET['status']):0;
        $status=$status-1;
        if($status>=0){
            $where['status'] = array('eq',$status);
        }else{
            $where['status'] = array('neq',4);
        }
        $start_time=isset($_GET['start_time']) ? trim($_GET['start_time']):'';
        $start_time=$start_time ? strtotime($start_time):0;
        if($start_time>0){
            $where['start_time']=array('egt',$start_time);
        }
        $end_time=isset($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time=$end_time ? strtotime($end_time):0;
        if($end_time>0){
            $where['end_time']=array('elt',$end_time);
        }
        
        $result = $database_house_village_nav->house_village_nav_page_list($where , true , 'status DESC,id desc');
        if(!$result){
            $this->error('数据处理有误！');
        }
        $this->assign('bg_color', '#fff');
        $this->assign('result',$result['result']);
        $this->display();
    }
    public function nav_add_pop(){
        $idd=isset($_GET['id']) ? intval($_GET['id']):0;
        $database_house_village_nav = D('House_village_nav');
        $village_id = 0;
        $detail=array();
        if($idd>0){
            $whereArr=array('id'=>$idd,'village_id'=>$village_id,'type'=>20,'block_type_id'=>'house_wap_index_pop');
            $editData=$database_house_village_nav->house_village_nav_detail($whereArr,'*');
            if(empty($editData['detail'])){
                $this->error('编辑的数据不存在！');
            }
            $detail=$editData['detail'];
            if(substr($detail['img'],1,6) == 'static'){
                $detail['img'] = C('config.site_url').$detail['img'];
            }elseif(substr($detail['img'],'0','4') == '000/'){
                $detail['img']= file_domain().'/upload/adver/' . $detail['img'];
            }else{
                $detail['img']= file_domain().'/upload/service/'.$detail['img'];
            }
            $detail['start_time']=$detail['start_time']>100 ? date('Y-m-d',$detail['start_time']):'';
            $detail['end_time']=$detail['end_time']>100 ? date('Y-m-d',$detail['end_time']):'';
        }
        $this->assign('detail',$detail);
        $this->assign('idd',$idd);
        $this->display();
    }
    public function nav_del_pop(){
        $idd=isset($_POST['id']) ? intval($_POST['id']):0;
        $village_id = 0;
        if($idd>0){
            $database_house_village_nav = M('House_village_nav');
            $whereArr=array('id'=>$idd,'village_id'=>$village_id,'type'=>20,'block_type_id'=>'house_wap_index_pop');
            $delArr=array('status'=>4,'del_time'=>time());
            $database_house_village_nav->where($whereArr)->data($delArr)->save();
            $this->success('删除成功！');

        }
        $this->error('删除失败！请重试~！');
    }
    public function nav_amend_pop(){
        $_POST['block_type_id']='house_wap_index_pop';
        $_POST['type']=20;  //定制的 看 block_type_id字段来区分
        $_POST['is_community_platform']=1;
        if( $_FILES['img']['error'] != 4){
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $village_id = 0;
        $database_house_village_nav = D('House_village_nav');
        $idd = isset($_POST['id'])  ? $_POST['id'] :0;
        $start_time=isset($_POST['start_time']) ? strtotime($_POST['start_time']):0;
        $_POST['start_time']=$start_time;
        $end_time=isset($_POST['end_time']) ? strtotime($_POST['end_time']):0;
        $_POST['end_time']=$end_time>0 ? ($end_time+86399):0;
        unset($_POST['id']);
        $where=array();
        if($idd>0){
            $where['id']=$idd;
            $where['village_id']=$village_id;
            $where['block_type_id']='house_wap_index_pop';
            $where['type']=20;
        }
        $result  = $database_house_village_nav->house_village_nav_save($_POST,0,$where);
        if(!$result){
            $this->frame_error_tips('数据处理有误！');
        }
        if($result['status']){
            exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
        }else{
            exit('<html><head><script>window.top.msg(0,"保存失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
        }
    }
    public function village_express_delivery(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 1;
        $is_community_platform=1;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $village_id=0;
        $whereArr=array('village_id'=>$village_id,'block_type_id'=>$block_type_id,'type'=>50);
        $whereArr['is_community_platform']=1;
        
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');

        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        $whereArr['is_community_platform']=$is_community_platform;
     
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        if($block_type_id=='business_express_delivery'){
            $whereArr['block_type_id']='business_small_shop';
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){
            /*
            if(isset($nav_set['data_extra']) && $nav_set['data_extra']){
                $data_extra=json_decode($nav_set['data_extra'],true);
                if($data_extra && isset($data_extra['product_info'])){
                    //$nav_set['product_info']=$data_extra['product_info'];
                    //$nav_set['product_num']=count($nav_set['product_info']);
                }
            }
            */
        }else{
            $nav_set=array();
        }
        if($block_type_id=='business_express_delivery'){
            if(empty($nav_set)){
                $nav_set['fillet_radius']=0;
                $nav_set['xsort']=0;
            }
            $whereTmpArr=array('parent_block_type_id'=>'business_express_delivery','village_id'=>$village_id);
            $whereTmpArr['is_community_platform']=$is_community_platform;
            
            $whereTmpArr['block_top_color']=array('neq','');
            $navTmpSet=$house_village_nav_moduleDb->getOneInfo($whereTmpArr);
            if($navTmpSet){
                $nav_set['fillet_radius']=$navTmpSet['fillet_radius'];
                $nav_set['block_top_color']=$navTmpSet['block_top_color'];
                $nav_set['block_bottom_color']=$navTmpSet['block_bottom_color'];
                
            }
            $list = D('Common')->businessProductList('shop');
            $nav_set['product_info']=!empty($list) && $list['list']? $list['list']:array();
        }
        if($block_type_id=='village_street_community'){
            $database_house_village_nav = D('House_village_nav');
            $whereArr=array('block_type_id'=>'village_street_community','type'=>50,'village_id'=>$village_id,'is_community_platform'=>$is_community_platform);
            $nav_info_village=$database_house_village_nav->where($whereArr)->find();
            $nav_set['village_nav']=$nav_info_village;
        }
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);

        $this->assign('result',$result['result']);
        $this->display();
    }

    public function  get_business_nav_set_info(){
        $sub_block_type_id = isset($_POST['sub_block_type_id']) ? remove_xss($_POST['sub_block_type_id']) : '';
        $is_community_platform=1;
        $parent_block_type_id = isset($_POST['parent_block_type_id']) ? remove_xss($_POST['parent_block_type_id']) : '';
        if($is_community_platform>0){
            $village_id=0;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        
        $whereArr=array('block_type_id'=>$sub_block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }

        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){
            /*
            if(isset($nav_set['data_extra']) && $nav_set['data_extra']){
                $data_extra=json_decode($nav_set['data_extra'],true);
                if($data_extra && isset($data_extra['product_info'])){
                    $nav_set['product_info']=$data_extra['product_info'];
                    $nav_set['product_num']=count($nav_set['product_info']);
                }
            }
            */
            unset($nav_set['data_extra']);
        }else{
            $nav_set=array('block_type_id'=>$sub_block_type_id,'village_id'=>$village_id,'is_show'=>0,'title'=>'','xsort'=>0,'distance'=>0);
        }
        $business_type='shop';
        switch ($sub_block_type_id){
            case 'business_small_shop':
                $business_type='shop';
                break;
            case 'business_new_mall':
                $business_type='mall';
                break;
            case 'business_community_group':
                $business_type='group';
                break;
            case 'business_service_reservation':
                $business_type='appoint';
                break;
            default:
                $business_type='shop';
                break;
        }
        $list = D('Common')->businessProductList($business_type);
        $nav_set['product_info']=!empty($list) && $list['list']? $list['list']:array();

        $jsonArr=array('err_code'=>0,'data'=>$nav_set,'msg'=>'');
        echo json_encode($jsonArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }
    public function business_product_list(){
        $xtype=isset($_GET['xtype']) ? trim($_GET['xtype']) : ''; //要获取的商品类型
        /**business_small_shop 快店***business_new_mall 新版商城**business_community_group 社区团购***business_service_reservation 预约服务*******/
        $business_type='shop';
        switch ($xtype){
            case 'business_small_shop':
                $business_type='shop';
                break;
            case 'business_new_mall':
                $business_type='mall';
                break;
            case 'business_community_group':
                $business_type='group';
                break;
            case 'business_service_reservation':
                $business_type='appoint';
                break;
            default:
                $business_type='shop';
                break;
        }
        $list = D('Common')->businessProductList($business_type);
        
        $datalist=$list['list'];
        $page=$list['pagebar'];
        $this->assign('list',$datalist);
        $this->assign('page', $page);
        $this->display();
    }
    public function  nav_add_guess_love(){

        $block_type_id='';
        $is_community_platform=0;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        if(isset($_GET['is_community_platform'])){
            $is_community_platform=intval($_GET['is_community_platform']);
        }
        $classify_category_list=array();
        if($block_type_id=='business_classify_info'){
            $database_classify_category = D('Classify_category');
            $condition=array();
            $condition['cid'] = array('gt',0);
            $condition['fcid'] = 0;
            $condition['cat_status']=1;
            $classify_category_list = $database_classify_category->field('cid,cat_name,cat_url')->where($condition)->order('`cat_sort` DESC,`cid` ASC')->select();
        }
        if($block_type_id=='business_store_info'){
            $database_shop_category = D('Shop_category');
            $condition=array();
            $condition['cat_id'] = array('gt',0);
            $condition['cat_fid'] = 0;
            $condition['cat_status']=1;
            $classify_category_list = $database_shop_category->field('cat_id as cid,cat_name,cat_url')->where($condition)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            $all_shop_category=array('cid'=>0,'cat_name'=>'全部分类','cat_url'=>'all');
            if($classify_category_list){
                array_unshift($classify_category_list, $all_shop_category);
            }else{
                $classify_category_list=$all_shop_category;
            }
        }

        $detail=array();
        if(isset($_GET['id']) && $_GET['id']){
            $idd=intval($_GET['id']);
            $database_house_village_nav = D('House_village_nav');
            $where= array('id'=>$idd);
            $result = $database_house_village_nav->house_village_nav_detail($where);
            if(!$result){
                $this->frame_error_tips('数据处理有误！');
            }
            if($result['detail']['img']){

                if(substr($result['detail']['img'],1,6) == 'static'){
                    $result['detail']['img'] = C('config.site_url').$result['detail']['img'];
                }elseif(substr($result['detail']['img'],'0','4') == '000/'){
                    $result['detail']['img']= file_domain().'/upload/adver/' . $result['detail']['img'];
                }else{
                    $result['detail']['img']= file_domain().'/upload/service/'.$result['detail']['img'];
                }
            }
            $detail=$result['detail'];
            unset($result);
        }
        if($block_type_id=='business_store_info' && empty($detail)){
            $detail['business_num']=10;
        }
        $this->assign('classify_category_list',$classify_category_list);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('detail',$detail);
        $this->display();
    }
    public function house_nav_guess_love(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $block_type_id=trim($block_type_id);
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $block_type_arr=$house_village_nav_moduleDb->block_type_arr;
        $block_type_str='';
        if($block_type_id && isset($block_type_arr[$block_type_id])){
            $block_type_str=$block_type_arr[$block_type_id];
        }

        $whereArr=array('village_id'=>$village_id,'block_type_id'=>$block_type_id,'type'=>50);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=1;
        }
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');
        if($block_type_id=='business_classify_info' && $result['result'] && $result['result']['list']){
            $database_classify_category = D('Classify_category');
            foreach ($result['result']['list'] as $kk=>$vv){
                $result['result']['list'][$kk]['classify_name']='';
                $condition=array();
                $condition['cid'] = $vv['business_cat_id'];
                $classify_category = $database_classify_category->field('cid,cat_name,cat_url')->where($condition)->find();
                if($classify_category){
                    $result['result']['list'][$kk]['classify_name']=$classify_category['cat_name'];
                }
            }
        }else if($block_type_id=='business_store_info' && $result['result'] && $result['result']['list']){
            $database_shop_category = D('Shop_category');
            foreach ($result['result']['list'] as $kk=>$vv){
                $result['result']['list'][$kk]['classify_name']='';
                $condition=array();
                $condition['cat_id'] = $vv['business_cat_id'];
                $classify_category = $database_shop_category->field('cat_id as cid,cat_name,cat_url')->where($condition)->find();
                if($classify_category){
                    $result['result']['list'][$kk]['classify_name']=$classify_category['cat_name'];
                }
            }
        }
        $house_visualize_switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $house_visualize_switch=$house_visualize_switch ? intval($house_visualize_switch):0;
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $nav_set=$nav_set ? $nav_set:array();
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('block_type_str',$block_type_str);
        $this->assign('result',$result['result']);
        $this->assign('house_visualize_switch',$house_visualize_switch);
        $getparam=$_GET;
        $this->assign('getparam',$getparam);
        $this->display();
    }
    public function house_nav_2list(){

        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 1;
        $is_community_platform=1;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $block_type_id=trim($block_type_id);
        $village_id=0;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $block_type_arr=$house_village_nav_moduleDb->block_type_arr;
        $block_type_str='';
        if($block_type_id && isset($block_type_arr[$block_type_id])){
            $block_type_str=$block_type_arr[$block_type_id];
        }
        $whereArr=array('village_id'=>$village_id,'block_type_id'=>$block_type_id,'is_community_platform'=>$is_community_platform,'type'=>50);
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');
        $house_visualize_switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $house_visualize_switch=$house_visualize_switch ? intval($house_visualize_switch):0;
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id,'is_community_platform'=>$is_community_platform);
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){

        }else{
            $nav_set=array();
        }

        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('block_type_str',$block_type_str);
        $this->assign('result',$result['result']);
        $this->assign('house_visualize_switch',$house_visualize_switch);
        $getparam=$_GET;
        $this->assign('getparam',$getparam);
        $this->display();
    }
    /**
     * 直播设置
     **/
    public function  house_nav_live(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=1;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $block_type_id=trim($block_type_id);
        $village_id=0;
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        $whereArr['is_community_platform']=$is_community_platform;
  
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $nav_set=$nav_set ? $nav_set:array();
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->display();
    }
    public function save_village_nav_set(){
        $block_type_id = isset($_POST['block_type_id']) ? remove_xss($_POST['block_type_id']) : '';
        $sub_block_type_id = isset($_POST['sub_block_type_id']) ? remove_xss($_POST['sub_block_type_id']) : '';
        $parent_block_type_id = isset($_POST['parent_block_type_id']) ? remove_xss($_POST['parent_block_type_id']) : '';
        $title = isset($_POST['title']) ? remove_xss($_POST['title']) : '';
        $sub_title = isset($_POST['sub_title']) ? remove_xss($_POST['sub_title']) : '';
        $label_title = isset($_POST['label_title']) ? remove_xss($_POST['label_title']) : '';
        $label_bg_color = isset($_POST['label_bg_color']) ? trim($_POST['label_bg_color']) : '';
       // $show_community_platform = isset($_POST['show_community_platform']) ? remove_xss($_POST['show_community_platform']) : 0;
        $is_community_platform = isset($_POST['is_community_platform']) ? intval($_POST['is_community_platform']) : 1;
        $is_community_platform=1;

        $is_show = isset($_POST['is_show']) ? intval($_POST['is_show']) : 1;  //1显示 0不显示

        $fillet_radius = isset($_POST['fillet_radius']) ? intval($_POST['fillet_radius']) : 0;  //圆角值
        $block_bg_color = isset($_POST['block_bg_color']) ? trim($_POST['block_bg_color']) : '';  //矩形背景颜色
        $block_border_color = isset($_POST['block_border_color']) ? trim($_POST['block_border_color']) : '';  //矩形边框颜色
        $label_img = isset($_POST['label_img']) ? trim($_POST['label_img']) : '';  //角标图片上传

        $block_top_color = isset($_POST['block_top_color']) ? trim($_POST['block_top_color']) : '';  //顶部颜色
        $block_bottom_color = isset($_POST['block_bottom_color']) ? trim($_POST['block_bottom_color']) : '';  //底部颜色
        $xsort = isset($_POST['xsort']) ? trim($_POST['xsort']) : 0;  //排序
        $product_info = isset($_POST['product_info']) ? $_POST['product_info'] :'';  //product_info
        $img=isset($_POST['img']) ? trim($_POST['img']) : '';  //图标
        $url=isset($_POST['url']) ? trim($_POST['url']) : '';  //跳转链接
        $wxapp_id=isset($_POST['wxapp_id']) ? trim($_POST['wxapp_id']) : '';  //小程序id
        $wxapp_page=isset($_POST['wxapp_page']) ? trim($_POST['wxapp_page']) : '';  //小程序链接
        $status_show=isset($_POST['status_show']) ? trim($_POST['status_show']) : '';  //1开启 0关闭
        $distance=isset($_POST['distance']) ? intval($_POST['distance']) : 0;  //显示商品的距离
        if(empty($block_type_id) && !empty($sub_block_type_id)){
            $block_type_id=$sub_block_type_id;
        }
        if(empty($block_type_id)){
            $this->error('类型参数错误，请重试！');
        }
        if($block_type_id!='business_live_broadcast' && $block_type_id!='business_store_info' && empty($title)){
            $this->error('请填写标题！');
        }
        $village_id=0;
        
        $saveArr=array('title'=>$title,'sub_title'=>$sub_title);
        $saveArr['is_community_platform']=$is_community_platform;
        
        $saveArr['village_id']=$village_id;
        $saveArr['label_title']=$label_title;
        $saveArr['label_bg_color']=$label_bg_color;
       // $saveArr['show_community_platform']=$show_community_platform;
        $haveShowBlock=array('business_live_broadcast','business_store_info','business_classify_info','left_bottom_rowgrid','right_bottom_rowgrid');
        if(in_array($block_type_id,$haveShowBlock)){
            $saveArr['is_show']=$is_show;
        }
        if($block_type_id=='village_street_community'){
            $saveArr['is_show']=$status_show;
            $saveArr['xsort']=$xsort;
        }
        $saveArr['fillet_radius']=$fillet_radius;
        if($block_type_id=='left_bottom_rowgrid' || $block_type_id=='right_bottom_rowgrid'){
            $saveArr['block_bg_color']=$block_bg_color;
            $saveArr['block_border_color']=$block_border_color;
            $saveArr['label_img']=$label_img;
        }
        if($parent_block_type_id && $parent_block_type_id=='business_express_delivery'){
            $saveArr['block_top_color']=$block_top_color;
            $saveArr['block_bottom_color']=$block_bottom_color;
            $saveArr['is_show']=$is_show;
            $saveArr['xsort']=$xsort;
            $saveArr['distance']=$distance;
            if(!empty($product_info)){
                $data_extra=array();
                $data_extra['product_info']=$product_info;
                $saveArr['data_extra']=json_encode($data_extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }else{
                $saveArr['data_extra']='';
            }

        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        $whereArr['is_community_platform']=$is_community_platform;
        
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if(!empty($nav_set)){
            $house_village_nav_moduleDb->saveData($whereArr,$saveArr);
        }else{
            $saveArr['block_type_id']=$block_type_id;
            $saveArr['parent_block_type_id']=$parent_block_type_id;
            $saveArr['add_time']=time();
            $house_village_nav_moduleDb->addData($saveArr);
        }
        if($parent_block_type_id && $parent_block_type_id=='business_express_delivery'){
            $whereTmpArr=array('parent_block_type_id'=>$parent_block_type_id,'village_id'=>$village_id);
            $whereTmpArr['is_community_platform']=$is_community_platform;
            
            $saveTmpArr=array('block_top_color'=>$block_top_color,'block_bottom_color'=>$block_bottom_color);
            $saveTmpArr['fillet_radius']=$fillet_radius;
            $house_village_nav_moduleDb->saveData($whereTmpArr,$saveTmpArr);
        }
        if($block_type_id=='village_street_community'){
            $village_nav_id=isset($_POST['village_nav_id']) ? trim($_POST['village_nav_id']) : 0;
            $village_nav_info=array('name'=>$title,'village_id'=>$village_id);
            $village_nav_info['img']=$img;
            $village_nav_info['url']=$url ? htmlspecialchars_decode($url):'';
            $village_nav_info['status']=$status_show;
            $village_nav_info['sort']=$xsort;
            $village_nav_info['type']=50;
            $village_nav_info['wxapp_id']=$wxapp_id;
            $village_nav_info['wxapp_page']=$wxapp_page;
            $village_nav_info['block_type_id']='village_street_community';
            $village_nav_info['is_community_platform']=$is_community_platform;
            $database_house_village_nav = D('House_village_nav');
            $whereArr=array('block_type_id'=>'village_street_community','type'=>50,'village_id'=>$village_id,'is_community_platform'=>$is_community_platform);
            $nav_info_village=$database_house_village_nav->where($whereArr)->find();
            if(!empty($nav_info_village)){
                $where=array('id'=>$nav_info_village['id']);
                $result  = $database_house_village_nav->house_village_nav_edit($where,$village_nav_info,$village_id);
            }else{
                $village_nav_info['add_time'] = time();
                $result = $database_house_village_nav->house_village_nav_add($village_nav_info,$block_type_id);
            }
        }
        $this->success('保存成功');
        //exit('<html><head><script>window.top.msg(1,"保存成功",true,2);window.parent.closeiframe();</script></head></html>');

    }
    public function guess_you_like_goods(){
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>'0','is_community_platform'=>'1');
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if(empty($nav_set)){
            $nav_set=array('is_show'=>0,'set_num'=>4);
        }
        $this->assign('nav_set',$nav_set);
        $this->display();
    }
    public function save_guess_you_like_goods(){
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>'0','is_community_platform'=>'1');
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $is_show= isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
        $set_num= isset($_POST['set_num']) ? intval($_POST['set_num']) : 4;
        if(!empty($nav_set)){
            $saveArr=array('is_show'=>$is_show,'set_num'=>$set_num);
            $house_village_nav_moduleDb->saveData(array('id'=>$nav_set['id']),$saveArr);
        }else{
            $saveArr=array('is_show'=>$is_show,'set_num'=>$set_num,'village_id'=>'0','is_community_platform'=>'1');
            $saveArr['block_type_id']='guess_you_like_goods';
            $saveArr['title']='猜你喜欢商品';
            $saveArr['add_time']=time();
            $house_village_nav_moduleDb->addData($saveArr);
        }
        $this->success('保存成功');
    }
    public function house_nav_count()
    {
        $count = 20;
        if (empty($_GET['id'])) {
            $this->error('广告id不能为空！');
        }
        $condition_house_village['a.adver_id'] = $_GET['id'];
        $condition_adver['id'] = $_GET['id'];
        $now_adver = D('House_adver')->field(true)->where($condition_adver)->find();

        if (empty($now_adver)) {
            $this->error('广告信息不存在！');
        }
        $condition_house_village['a.village_id'] =array('neq', 0);
        //搜索
        if (!empty($_GET['village_name'])) {
            $condition_house_village['b.village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }

        if ($_GET['province_idss']) {
            $condition_house_village['b.province_id'] = $where['province_id'] = $_GET['province_idss'];
        }
        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['b.city_id'] = $where['city_id'] = $_GET['city_idss'];
        } else {
            if ($this->config['many_city'] && $_GET['province_idss']) {
                $condition_house_village['b.province_id'] = $where['province_id'] = $_GET['province_idss'];
            }
        }
        if ($_GET['area_id']) {
            $condition_house_village['b.area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['b.street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['b.community_id'] = $where['community_id'] = $_GET['community_idss'];
        }

        $database_adver = D('house_adver_village_count');
        $count_village = $database_adver->join(" as a left JOIN " . C('DB_PREFIX') . "house_village as b ON (b.village_id=a.village_id)")
            ->where($condition_house_village)->count();

        //   ->join(" left JOIN " . C('DB_PREFIX') . "house_adver_record as c ON (c.adver_id=a.id)")           ->join("  left JOIN " . C('DB_PREFIX') . "house_village_user_bind as d ON (a.village_id=d.village_id )")

        import('@.ORG.system_page');
        $p = new Page($count_village, $count);
        $field = "a.*,b.village_name";
        $adver_list = $database_adver->field($field)
            ->join(" as a left JOIN " . C('DB_PREFIX') . "house_village as b ON (b.village_id=a.village_id)")
            ->where($condition_house_village)
            ->order('`a`.`id` DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();

        //   ->join(" left JOIN " . C('DB_PREFIX') . "house_adver_record as c ON (c.adver_id=a.id)")
        //  print_r($database_adver->_sql());exit;


        /*   $database_adver = D('house_adver_record');
           $count_village = $database_adver->join(" as a left JOIN " . C('DB_PREFIX') . "house_village as b ON (b.village_id=a.village_id)")
               ->where($condition_house_village)->count();
           //   ->join(" left JOIN " . C('DB_PREFIX') . "house_adver as c ON (a.village_id=c.village_id and a.adver_id=c.id)")
           //            ->join("  left JOIN " . C('DB_PREFIX') . "house_village_user_bind as d ON (a.village_id=d.village_id )")

           import('@.ORG.system_page');
           $p = new Page($count_village, $count);
           $field = "a.*,b.village_name";
           $adver_list = $database_adver->field($field)
               ->join(" as a left JOIN " . C('DB_PREFIX') . "house_village as b ON (b.village_id=a.village_id)")
               ->where($condition_house_village)
               ->order('`a`.`adver_id` DESC')
               ->limit($p->firstRow . ',' . $p->listRows)
               ->select();*/
        if (!empty($adver_list)) {
            foreach ($adver_list as &$value) {
                $value['lm_sex'] = $this->lm_sex[$now_adver['lm_sex']];
                if (empty($now_adver['lm_sex'])){
                    $value['lm_sex'] = '不限';
                }
                if (empty($now_adver['lm_age_max'])) {
                    $value['lm_age'] = '不限';
                } else {
                    $value['lm_age'] = $value['lm_age_min'] . '-' . $value['lm_age_max'];
                }
                $where_uid = [];
                $where_uid['village_id'] = $value['village_id'];
                $where_uid['type'] = array('in', [0, 1, 2, 3]);
                $where_uid['status'] = 1;
                $uid_arr = D('house_village_user_bind')->where($where_uid)->getField('uid', true);
                $uid_arr1 = array_keys(array_flip($uid_arr));
                $where_user = [];
                $where_user['uid'] = array('in', $uid_arr1);
                $user_list = D('user')->where($where_user)->field('uid,sex,birthday')->select();
                $value['lm_sex_num'] = 0;
                $value['lm_age_num'] = 0;
                if (!empty($user_list)) {
                    foreach ($user_list as $val) {
                        if ($now_adver['lm_sex'] == 1 && $val['sex'] == 1) {
                            $value['lm_sex_num'] = $value['lm_sex_num'] + 1;
                        } elseif ($now_adver['lm_sex'] == 2 && $val['sex'] == 2) {
                            $value['lm_sex_num'] = $value['lm_sex_num'] + 1;
                        }
                        $val['age'] = date('Y') - substr($val['birthday'], 0, 4);
                        if ($now_adver['lm_age_min'] <= $val['age'] && $val['age'] <= $value['lm_age_max']) {
                            $value['lm_age_num'] = $value['lm_age_num'] + 1;
                        }
                    }
                }
                if ($now_adver['lm_sex'] == 0) {
                    $value['lm_sex_num'] = count($user_list);
                }
                if ($now_adver['lm_age_max'] == 0) {
                    $value['lm_age_num'] = count($user_list);
                }
                $value['age'] = $value['lm_age'] . ':' . $value['lm_age_num'];
                $value['sex'] = $value['lm_sex'] . ':' . $value['lm_sex_num'];
            }
        }
        //   print_r($adver_list);exit;
        $this->assign('adver_list', $adver_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }


    public function house_nav_add()
    {
        $block_type_id='';
        $is_community_platform=1;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        if(isset($_GET['is_community_platform'])){
            $is_community_platform=intval($_GET['is_community_platform']);
        }
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->display();
    }


    public function house_nav_village_list()
    {
        $order_string = '`village_id` DESC';
        $village_id = $_GET['village_id'];
        //搜索
        if (!empty($_GET['village_name'])) {
            $condition_house_village['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }
        if ($condition_house_village) {
            $count = 10000;
        } else {
            $count = 20;
        }

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }
        $condition_house_village['status'] =1;
        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
        }

        if ($this->config['many_city'] && $_GET['province_idss']) {
            $condition_house_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
        }

        if ($_GET['area_id']) {
            $condition_house_village['area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['community_id'] = $where['community_id'] = $_GET['community_idss'];
        }

        $database_house_village = D('House_village');
        $count_village = $database_house_village->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, $count);
        $field = "village_id,village_name,village_address,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $village_list = $database_house_village->field($field)->where($condition_house_village)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('village_list', $village_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function nav_modify_new()
    {
        $row_type = isset($_POST['row_type']) ? remove_xss($_POST['row_type']) : '';
        unset($_POST['row_type']);
        if($row_type == 'single'){
            $_POST['type'] = 1;
        }else if($row_type == 'middle'){
            $_POST['type'] = 2;
        }
        $block_type_id='';
        $is_community_platform=1;
        if(isset($_POST['block_type_id']) && !empty($_POST['block_type_id'])){
            $block_type_id=trim($_POST['block_type_id']);
            $_POST['block_type_id']=$block_type_id;
            $_POST['type']=50;  //定制的 看 block_type_id字段来区分
            $_POST['is_community_platform']=$is_community_platform;
        }
        if($block_type_id!='business_classify_info') {
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->error($image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        $_POST['add_time'] = time();
        //  $_POST['add_flag'] = time() . $this->system_session['id'];
        $_POST['village_id'] = 0;
        if (empty($_POST['name'])) {
            $this->error('名称不能为空！');
        }

        if ($block_type_id!='business_classify_info' && $block_type_id!='business_store_info'  && empty($_POST['url'])) {
            $this->error('url不能为空！');
        }

        if ($block_type_id!='business_classify_info' && empty($_POST['img'])) {
            $this->error('图片不能为空！');
        }


        // RGB 转16进制
        if(isset($_POST['mark_bg']) && !empty($_POST['mark_bg'])){
            $_POST['mark_bg'] = RGBToHex($_POST['mark_bg']);
        }

        $database_house_village_nav = D('House_village_nav');
        $village_arr = $_POST['village_arr'];
        unset($_POST['village_arr']);
        //todo 记录下小区功能库返回的参数
        $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);
        $result = $database_house_village_nav->add($_POST);
        if ($result) {
            if (isset($village_arr) && !empty($village_arr) && trim($village_arr)) {
                $village_arrs = explode(',', $village_arr);
                D('House_village_nav')->data(['village_id'=>-1])->where(['id'=>$result])->save();
                foreach ($village_arrs as $val) {
                    $village_id = trim($val);
                    if (!empty($village_id)) {
                        $data_count=[];
                        $data_count['nav_id']=$result;
                        $data_count['village_id']=$village_id;
                        $data_count['add_time'] = time();
                        $data_count['last_time'] = time();
                        D('House_adver_village_count')->data($data_count)->add();
                    }
                }
            }

            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11, '添加成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //$this->frame_submit_tips(1, '添加成功');
            }
        } else {
            exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error('添加失败');
        }
    }

    public function house_nav_edit()
    {
        $this->assign('bg_color', '#F3F3F3');
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_GET['id'] + 0;
        $result = $database_house_village_nav->house_village_nav_detail($where);
        $village_arr = '';
        $village_arr = D('House_adver_village_count')->field(true)->where(['nav_id' => $_GET['id']])->getField('village_id', true);
        if (!empty($village_arr)) {
            $village_arr = array_unique($village_arr);
            $village_arr = implode(',', $village_arr);
        }
        if (!$result) {
            $this->error('数据处理有误！');
        }
        if ($result['detail']) {
            if (substr($result['detail']['img'], '1', '6') == 'static') {
                $result['detail']['img'] = C('config.site_url') . $result['detail']['img'];
            } elseif (substr($result['detail']['img'], '0', '4') == '000/') {
                $result['detail']['img'] = file_domain() . '/upload/adver/' . $result['detail']['img'];
            } else {
                $result['detail']['img'] = file_domain() . '/upload/service/' . $result['detail']['img'];

            }
        }
        $block_type_id='';
        $is_community_platform=1;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('detail', $result['detail']);
        $this->assign('village_arr', $village_arr);
        $this->display();
    }


    public function nav_amend_new()
    {

        $block_type_id='';
        if(isset($_POST['block_type_id']) && !empty($_POST['block_type_id'])){
            $block_type_id=trim($_POST['block_type_id']);
            $_POST['block_type_id']=$block_type_id;
            $_POST['type']=50;  //定制的 看 block_type_id字段来区分
        }
        
        if ($block_type_id!='business_classify_info' && $_FILES['img']['error'] != 4) {
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->error($image['msg'] ? $image['msg'] : $image['message']);
            }
        }

        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_POST['id'] + 0;
        $_POST['add_time']=time();
        $village_arr = $_POST['village_arr'];
        unset($_POST['village_arr']);
        //todo 记录下小区功能库返回的参数
        $_POST['url']=htmlspecialchars_decode($_POST['url']);
        $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);

        if(isset($_POST['mark_bg']) && !empty($_POST['mark_bg']) && strpos($_POST['mark_bg'],'rgb') !== false){
            $_POST['mark_bg'] = RGBToHex($_POST['mark_bg']);
        }

        $result = $database_house_village_nav->house_village_nav_edit($where, $_POST);
        if (!$result) {
            $this->error('数据处理有误！');
        }
        if ($result['status']) {
            if (isset($village_arr) && !empty($village_arr) && trim($village_arr)) {
                $database_house_village_nav->data(['village_id'=>-1])->where(['id'=>$_POST['id']])->save();
                D('House_adver_village_count')->where(['nav_id' => $_POST['id']])->delete();
                $village_arrs = explode(',', $village_arr);
                foreach ($village_arrs as $val) {
                    $village_id = trim($val);
                    if (!empty($village_id)) {
                        $data_count=[];
                        $data_count['nav_id']=$_POST['id'];
                        $data_count['village_id']=$village_id;
                        $data_count['add_time'] = time();
                        $data_count['last_time'] = time();
                        D('House_adver_village_count')->data($data_count)->add();
                        fdump_api(['广告添加小区'.__LINE__,D('House_adver_village_count')->_sql()],'adver_village',true);
                    }
                }
            }

            if(isset($_POST['source']) && $_POST['source'] ==1 ){
                exit('<html><head><script>window.top.msg(1,"修改成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11, '修改成功！');
            }else{
                exit('<html><head><script>window.top.msg(1,"修改成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //$this->frame_submit_tips(1, '修改成功！');
            }

        } else {
            exit('<html><head><script>window.top.msg(0,"修改失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            //$this->error('修改失败！请重试~');
        }
    }

    public function house_nav_del()
    {
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_POST['id'] + 0;
     //   $nav_info = $database_house_village_nav->house_village_nav_detail($where);
      //  $where_nav['add_flag'] = $nav_info['detail']['add_flag'];
        $result = $database_house_village_nav->house_village_nav_del($where);
        if ($result) {
            $result = $database_house_village_nav->house_village_nav_detail($where);
            if (substr($result['detail']['img'], '0', '4') == '000/') {
               // unlink('./upload/adver/' . $result['detail']['img']);
                D('Config')->clearCache();
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    /**
     * 设置单行功能导航一行数量
     * User: zhanghan
     * Date: 2022/2/22
     * Time: 11:56
     */
    public function set_village_nav(){
        $view_num = remove_xss($_POST['view_num']);

        $village_info = D('House_village_info');
        // 查询分配权限的小区
        $village_arr = $this->get_visualize_assign(11);

        $data = [
            'view_num' => $view_num,
            'add_time' => time(),
        ];

        $where = [];
        $where['village_id'] = array('not in',$village_arr);
        $res = $village_info->where($where)->data($data)->save();
        if($res){
            exit(json_encode(array('status'=>0,'msg'=>'设置成功。')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'设置失败！请重试。')));
        }
    }

    //物业公告
    public function notice()
    {
        $count = 30;
        $database_house_notice = D('House_notice');
        $count_notice = $database_house_notice->where(['status' => ['neq', '-1']])->count();
        import('@.ORG.system_page');
        $p = new Page($count_notice, $count);
        $noticeList = $database_house_notice->where(['status' => ['neq', '-1']])->limit($p->firstRow . ',' . $p->listRows)->order('`is_top` DESC,`add_time` DESC')->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('noticeList', $noticeList);
        $this->display();
    }

    //添加物业公告
    public function notice_add()
    {
        if (IS_POST) {
            $database_house_notice = D('House_notice');
            $_POST['content'] = fulltext_filter($_POST['content']);
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            if ($database_house_notice->data($_POST)->add()) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->display();
        }
    }

    //编辑物业公告
    public function notice_edit()
    {
        if (IS_POST) {
            $database_house_notice = D('House_notice');
            $_POST['content'] = fulltext_filter($_POST['content']);
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            if ($database_house_notice->data($_POST)->save()) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        } else {
            $database_house_notice = D('House_notice');
            $where['id'] = $_GET['id'];
            $info = $database_house_notice->field(true)->where($where)->find();
            if (empty($info)) {
                $this->error('数据库中没有查询到该条公告！', 5);
            }
            $this->assign('info', $info);

            $this->assign('bg_color', '#F3F3F3');
            $this->display();
        }
    }

    //删除物业公告
    public function notice_del()
    {
        if (IS_POST) {
            $database_house_notice = D('House_notice');
            $where['id'] = $_POST['id'];
            $data['status'] = '-1';
            if ($database_house_notice->where($where)->data($data)->save()) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function house_village_del(){

        // 房间数量
        $room_count =  D('House_village_user_vacancy')->where(array('is_del'=>'0','village_id'=>$_POST['village_id']))->count();
        // 业主数量
        $where_user = [
            'type'=>array('in',[0,3]),
            'vacancy_id'=>['gt',0],
            'village_id'=>$_POST['village_id'],
            'status'=>1
        ];
        $where_user['_string'] = "(uid not in (0,'') OR phone not in (0,'') OR name not in (0,''))";
        $user_count =  D('House_village_user_bind')->where($where_user)->count();
        // 车辆数量
        $car_count =  D('House_village_parking_car')->where(array('village_id'=>$_POST['village_id']))->count();
        // 车位数量
        $position_count =  D('House_village_parking_position')->where(array('village_id'=>$_POST['village_id']))->count();

        $db_house_community_about = D('House_community_about');
        $house_community_about_info = $db_house_community_about->where(['village_id' => $_POST['village_id']])->find();
        if (isset($house_community_about_info['from']) && $house_community_about_info['from'] == 'system') {
            $facilities_type_text = $db_house_community_about->getFacilitiesTypeText($house_community_about_info['facilities_type']);
            $this->error("系统自动创建的[{$facilities_type_text}]不允许删除~");
        }
        
        $database_village = D('House_village');
        $condition_village['village_id'] = $_POST['village_id'];
        $now_village = $database_village->field('village_name')->where($condition_village)->find();
        if (empty($room_count)&&empty($user_count)&&empty($car_count)&&empty($position_count)){
          //  $now_village = $database_village->field(true)->where($condition_village)->find();
            $db_house_community_about->deleteHouseCommunityAbout($_POST['village_id']);
            if ($database_village->where($condition_village)->data(['status'=>5])->save()) {
                // todo 同步删除工作人员和管理员账号
                $db_house_admin = D('House_admin');
                $whereAdmin = ['village_id' => $_POST['village_id']];
                $setAdmin = [
                    'status' => 4, 'account' => array('exp',"concat(`account`,'_del')"), 'phone' => array('exp',"concat(`phone`,'_del')")
                ];
                $db_house_admin->where($whereAdmin)->data($setAdmin)->save();
                $db_house_worker = D('House_worker');
                $whereWork = ['village_id' => $_POST['village_id']];
                $setWork = [
                    'status' => 4, 'is_del' => 1, 'account' => array('exp',"concat(`account`,'_del')"), 'phone' => array('exp',"concat(`phone`,'_del')")
                ];
                $db_house_worker->where($whereWork)->data($setWork)->save();
                D('Config')->clearCache();
                $this->success('删除成功');
            } else {
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('请先到['.$now_village['village_name'].']小区后台删除 房间、业主、车辆、车位的数据');
        }

    }

    public function get_visualize_assign($type){
        // 查询分配权限的小区
        $village_arr = [0];
        $assign_type = D('House_visualize_assign')->where(array('type' => $type))->field('village_id')->select();
        if(!empty($assign_type)){
            $village_arr = array_merge(array_column($assign_type,'village_id'),$village_arr);
        }
        return $village_arr;
    }

    public function qitianscreen(){
        $skycockpit_info=M('Skycockpit_info')->where(array('id'=>1))->field('*')->find();
        $province_id='0';
        $city_id='0';
        if($skycockpit_info){
            $skycockpit_info['baseinfo']=!empty($skycockpit_info['baseinfo']) ? json_decode($skycockpit_info['baseinfo'],1):'';
            if($skycockpit_info['baseinfo'] && isset($skycockpit_info['baseinfo']['province_id'])){
                $province_id=$skycockpit_info['baseinfo']['province_id'];
                $city_id=$skycockpit_info['baseinfo']['city_id'];
            }
            $skycockpit_info['info1data']=!empty($skycockpit_info['info1data']) ? json_decode($skycockpit_info['info1data'],1):'';
            $skycockpit_info['info2data']=!empty($skycockpit_info['info2data']) ? json_decode($skycockpit_info['info2data'],1):'';
            $skycockpit_info['info3data']=!empty($skycockpit_info['info3data']) ? json_decode($skycockpit_info['info3data'],1):'';
            $skycockpit_info['info4data']=!empty($skycockpit_info['info4data']) ? json_decode($skycockpit_info['info4data'],1):'';
            $skycockpit_info['info5data']=!empty($skycockpit_info['info5data']) ? json_decode($skycockpit_info['info5data'],1):'';
            $skycockpit_info['info6data']=!empty($skycockpit_info['info6data']) ? json_decode($skycockpit_info['info6data'],1):'';
            $skycockpit_info['info7data']=!empty($skycockpit_info['info7data']) ? json_decode($skycockpit_info['info7data'],1):'';
            $skycockpit_info['info8data']=!empty($skycockpit_info['info8data']) ? json_decode($skycockpit_info['info8data'],1):'';
            if($skycockpit_info['info8data'] && $skycockpit_info['info8data']['navinfo'] && is_array($skycockpit_info['info8data']['navinfo'])){
                foreach ($skycockpit_info['info8data']['navinfo'] as $k8k=>$v8v){
                    if($v8v['is_img'] == 1 && !empty($v8v['camera_sn'])){
                        $whereArr=array('camera_status'=>array('neq',4),'camera_sn'=>$v8v['camera_sn'],'look_url'=>array('neq',''),'lookUrlType'=>array('in',array('flv')));
                        $camera_device=M('House_camera_device')->where($whereArr)->order('camera_id desc')->find();
                        if($camera_device){
                            $skycockpit_info['info8data']['navinfo'][$k8k]['xv2']=$camera_device['camera_name'];
                        }
                    }

                }
            }
        }

        $this->assign('province_id', $province_id);
        $this->assign('city_id', $city_id);
        $this->assign('skycockpit', $skycockpit_info);
        $this->display();
    }
    public function qitianscreen_v(){
        $idstr = isset($_GET['idstr']) ? trim($_GET['idstr']):'';
        $count = 10;
        $whereArr=array('village_id'=>array('gt',0),'camera_status'=>array('neq',4),'camera_sn'=>array('neq',''),'look_url'=>array('neq',''),'lookUrlType'=>array('in',array('flv')));
        $xcount = M('House_camera_device')->where($whereArr)->count();
        import('@.ORG.system_page');
        $p = new Page($xcount, $count);
        $fieldStr='camera_id,camera_name,camera_sn,village_id,look_url,lookUrlType';
        $house_villageDb=D('House_village');
        $camera_device_list = M('House_camera_device')->where($whereArr)->field($fieldStr)->limit($p->firstRow . ',' . $p->listRows)->order('`camera_id` DESC')->select();
  
        if(!empty($camera_device_list)){
            foreach ($camera_device_list as $kk=>$item){
                $camera_device_list[$kk]['village_name']='';
                $house_village=$house_villageDb->where(array('village_id'=>$item['village_id']))->field('village_id,village_name')->find();
                if(!empty($house_village)){
                    $camera_device_list[$kk]['village_name']=$house_village['village_name'];
                }
                if(!empty($item['look_url']) && empty($item['lookUrlType'])){
                    if(strpos($item['look_url'],'ws:')!==false){
                        $camera_device_list[$kk]['lookUrlType']='ws';
                    }elseif(strpos($item['look_url'],'wss:')!==false){
                        $camera_device_list[$kk]['lookUrlType']='wss';
                    }elseif(strpos($item['look_url'],'rtmp:')!==false){
                        $camera_device_list[$kk]['lookUrlType']='rtmp';
                    }elseif(strpos($item['look_url'],'rtsp:')!==false){
                        $camera_device_list[$kk]['lookUrlType']='rtsp';
                    }elseif((strpos($item['look_url'],'http:')!==false||strpos($item['look_url'],'https:')!==false) && strpos($item['look_url'],'.m3u8')!==false){
                        $camera_device_list[$kk]['lookUrlType']='hls';
                    }elseif((strpos($item['look_url'],'http:')!==false||strpos($item['look_url'],'https:')!==false) && strpos($item['look_url'],'.flv')!==false){
                        $camera_device_list[$kk]['lookUrlType']='flv';
                    }

                }
            }
        }
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('idstr', $idstr);
        $this->assign('camera_device_list', $camera_device_list);
        $this->display();
    }
    //保存数据
    public function saveqitianscreen(){
            $xtype = isset($_POST['xtype']) ? intval($_POST['xtype']):0;
            $infodata=array();
            $saveArr=array();
            switch ($xtype){
                case 0:
                    $infodata=array('xtype'=>0);
                    $screentitle=trim($_POST['screentitle']);
                    $screentitle=!empty($screentitle) ? strip_tags($screentitle):'';
                    $infodata['screentitle']=htmlspecialchars($screentitle,ENT_QUOTES);
                    $bg_img=trim($_POST['bg_img']);
                    $bg_img=!empty($bg_img) ? strip_tags($bg_img):'';
                    $infodata['bg_img']=$bg_img;
                    $province_id=trim($_POST['province_id']);
                    $infodata['province_id']=$province_id;
                    $city_id=trim($_POST['city_id']);
                    $infodata['city_id']=$city_id;
                    $saveArr['baseinfo']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 1:
                    $infodata=array('xtype'=>1);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $saveArr['info1data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 2:
                    $infodata=array('xtype'=>2);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $tjtitle=trim($_POST['tjtitle']);
                    $tjtitle=!empty($tjtitle) ? strip_tags($tjtitle):'';
                    $infodata['tjtitle']=htmlspecialchars($tjtitle,ENT_QUOTES);
                    $tjinfo=$_POST['tjinfo'];
                    $infodata['tjinfo']=$tjinfo;
                    $saveArr['info2data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 3:
                    $infodata=array('xtype'=>3);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $tjtitle=trim($_POST['tjtitle']);
                    $tjtitle=!empty($tjtitle) ? strip_tags($tjtitle):'';
                    $infodata['tjtitle']=htmlspecialchars($tjtitle,ENT_QUOTES);
                    $tjinfo=$_POST['tjinfo'];
                    $infodata['tjinfo']=$tjinfo;
                    $xmonth=$_POST['xmonth'];
                    $infodata['xmonth']=$xmonth;
                    $saveArr['info3data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 4:
                    $infodata=array('xtype'=>4);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $tjtitle=trim($_POST['tjtitle']);
                    $tjtitle=!empty($tjtitle) ? strip_tags($tjtitle):'';
                    $infodata['tjtitle']=htmlspecialchars($tjtitle,ENT_QUOTES);
                    $tjinfo=$_POST['tjinfo'];
                    $infodata['tjinfo']=$tjinfo;
                    $tjattr=$_POST['tjattr'];
                    $infodata['tjattr']=$tjattr;
                    $saveArr['info4data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 5:
                    $infodata=array('xtype'=>5);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $tjtitle=trim($_POST['tjtitle']);
                    $tjtitle=!empty($tjtitle) ? strip_tags($tjtitle):'';
                    $infodata['tjtitle']=htmlspecialchars($tjtitle,ENT_QUOTES);
                    $tjinfo=$_POST['tjinfo'];
                    $infodata['tjinfo']=$tjinfo;
                    $saveArr['info5data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 6:
                    $infodata=array('xtype'=>6);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $navleft=$_POST['navleft'];
                    $infodata['navleft']=$navleft;
                    $saveArr['info6data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 7:
                    $infodata=array('xtype'=>7);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $saveArr['info7data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
                case 8:
                    $infodata=array('xtype'=>8);
                    $navtitle=trim($_POST['navtitle']);
                    $navtitle=!empty($navtitle) ? strip_tags($navtitle):'';
                    $infodata['navtitle']=htmlspecialchars($navtitle,ENT_QUOTES);
                    $navinfo=$_POST['navinfo'];
                    $infodata['navinfo']=$navinfo;
                    $faceimg=$_POST['faceimg'];
                    $infodata['faceimg']=$faceimg;
                    $saveArr['info8data']=json_encode($infodata,JSON_UNESCAPED_UNICODE);
                    break;
            }
            $updatetime=time();
            $saveArr['updatetime']=$updatetime;
            $skycockpit_info=M('Skycockpit_info')->where(array('id'=>1))->field('*')->find();
            if(!empty($skycockpit_info)){
                M('Skycockpit_info')->where(array('id'=>1))->data($saveArr)->save();
            }else{
                M('Skycockpit_info')->data($saveArr)->add();
            }

            $this->success('保存成功');
    }

    public function ajax_upload_pic()
    {
        if ($_FILES['imgFile']['error'] != 4) {
            $upload_dir = './upload/house/village/' . date('Ymd') . '/' . date('H') . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/webp', 'application/octet-stream');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $uploadList[0]['savename'];
                $url = $upload_dir . $title;
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => replace_file_domain($url))));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }
    public function  robot_look_video(){
        $robot_device_id = isset($_GET['robot_device_id']) ? trim($_GET['robot_device_id']):0;
        $robot_code = isset($_GET['robot_code']) ? trim($_GET['robot_code']):'';
        $whereArr=array();
        if($robot_device_id>0){
            $whereArr['id']=$robot_device_id;
        }
        if(!empty($robot_code)){
            $whereArr['robot_code']=$robot_code;
        }
        $robot_login_lnfo=array();
        if($whereArr){
            $robot_device=M('Third_robot_device')->where($whereArr)->find();
            if($robot_device && $robot_device['robot_login_lnfo']){
                $robot_login_lnfo=json_decode($robot_device['robot_login_lnfo'],true);
                $robot_login_lnfo['tenantCode']=$robot_device['tenant_code'];
                $robot_login_lnfo['tenantId']=$robot_device['tenant_id'];
                $robot_login_lnfo['branchId']=$robot_device['branch_id'];
            }
        }
        $this->assign('robot_login_lnfo', $robot_login_lnfo);
        $this->display();
    }
    public function shop_goods_seckill_set(){
        $village_id = 0;
        $database_house_village_nav = D('House_village_nav');
        $shop_goods_type=isset($_GET['shop_goods_type']) ? trim($_GET['shop_goods_type']):'';
        $key_name=isset($_GET['key_name']) ? trim($_GET['key_name']):'';
        $goods_name=isset($_GET['goods_name']) ? trim($_GET['goods_name']):'';
        $where=array('type'=>21,'block_type_id'=>'shop_goods_seckill','is_community_platform'=>1,'village_id'=>'0');
        if($key_name){
            $where['name']=array('like','%'.$key_name.'%');
        }
        if($goods_name){
            $where['_string']="(shop_mer_name LIKE '%{$goods_name}%' OR shop_store_name LIKE '%{$goods_name}%')";
        }
        if($shop_goods_type){
            $where['shop_goods_type']=$shop_goods_type;
        }
        $where['status'] = array('neq',4);
        $result = $database_house_village_nav->house_village_nav_page_list($where , '*' , 'sort DESC,status DESC,id desc');
        if(!$result){
            $this->error('数据处理有误！');
        }
        $resultList=$result['result'];
        $newGoodsData=array();
        if(isset($result['shopGoodsList']) && $result['shopGoodsList']){
            $shopGoodsData=D('Admin')->getSeckillGoods($result['shopGoodsList'], false);
            if($shopGoodsData){
                foreach ($shopGoodsData as $gvv){
                    $newGoodsData[$gvv['nav_idd']]=$gvv;
                }
            }
        }
        $delArrId=array();
        foreach ($resultList['list'] as $kk=>$vv){
            if(isset($newGoodsData[$vv['id']]) && $newGoodsData[$vv['id']]['goods_name']){
                $gitrm=$newGoodsData[$vv['id']];
                $saveArr=array();
                $saveArr['name']=$gitrm['goods_name'];
                $saveArr['img']=isset($gitrm['img']) ? $gitrm['img']:'';
                $saveArr['url']=isset($gitrm['url']) ? $gitrm['url']:'';
                $saveArr['status']=isset($gitrm['goods_status']) ? $gitrm['goods_status']:0;  //0未上架 1上架 2过期
                $saveArr['url']=isset($gitrm['url']) ? $gitrm['url']:'';
                $saveArr['business_num']=isset($gitrm['stock_num']) ? $gitrm['stock_num']:0;  //库存
                $start_time=isset($gitrm['active_start_time']) ? trim($gitrm['active_start_time']):'';
                $saveArr['start_time']=!empty($start_time) ? strtotime($start_time):0;
                $end_time=isset($gitrm['active_end_time']) ? trim($gitrm['active_end_time']):'';
                $saveArr['end_time']=!empty($end_time) ? strtotime($end_time):0;
                $saveArr['shop_mer_name']=$gitrm['merchant_name'];
                $saveArr['shop_store_name']=$gitrm['store_name'];
                unset($gitrm['is_spec'],$gitrm['spec_value'],$gitrm['nav_idd']);
                $saveArr['data_extra']=json_encode($gitrm,JSON_UNESCAPED_UNICODE);
                $database_house_village_nav->where(array('id'=>$vv['id']))->data($saveArr)->save();
            }else{
                $delArrId[]=$vv['id'];
            }
        }
        $nowtime=time();
        if($delArrId){
            $database_house_village_nav->where(array('id'=>array('in',$delArrId)))->data(array('status'=>4,'del_time'=>$nowtime))->save();
        }
        $result = $database_house_village_nav->house_village_nav_page_list($where , '*' , 'sort DESC,status DESC,id desc');
        $resultList=$result['result'];
        $this->assign('bg_color', '#fff');
        $this->assign('result',$resultList);
        $this->assign('shop_goods_type',$shop_goods_type);
        $this->assign('village_id',$village_id);
        $this->display();
    }

    public function shop_goods_seckill_pop(){
        $goods_type=isset($_GET['goods_type']) ? trim($_GET['goods_type']) :'shop';
        $goods_name=isset($_GET['goods_name']) ? trim($_GET['goods_name']) :'';
        $goods_list=array();
        $pagebar='';
        $params=array('goods_name'=>$goods_name);
        $shopData=D('Admin')->getSeckillGoodsList($goods_type, $params);
        if($shopData && isset($shopData['list'])){
            $goods_list=$shopData['list'];
            $pagebar=$shopData['page_bar'];
        }
        $this->assign('bg_color', '#fff');
        $this->assign('goods_list',$goods_list);
        $this->assign('pagebar',$pagebar);
        $this->assign('goods_type',$goods_type);
        $this->display();
    }
    public function shop_goods_seckill_save(){

        $goods_type=isset($_POST['goods_type']) ? trim($_POST['goods_type']):"shop";
        $goods_arr=isset($_POST['goods_arr']) ? $_POST['goods_arr']:"";
        $village_id = 0;
        $database_house_village_nav = D('House_village_nav');
        if(empty($goods_arr)){
            exit(json_encode(array('err_code'=>1,'msg'=>'请至少选择一个商品！')));
        }

        $nowtime=time();
        foreach ($goods_arr as $gvv){
            if(isset($gvv['goods_id']) && $gvv['goods_id']){
                $saveArr=array();
                $saveArr['block_type_id']='shop_goods_seckill';
                $saveArr['type']=21;  //定制的 看 block_type_id字段来区分
                $saveArr['village_id']=$village_id;
                $saveArr['is_community_platform']=1;
                $tmpWhere=array('village_id'=>$village_id,'type'=>21,'is_community_platform'=>1,'shop_goods_type'=>$goods_type,'goods_id'=>$gvv['goods_id']);
                $navinfo=$database_house_village_nav->where($tmpWhere)->find();
                $saveArr['name']=$gvv['goods_name'];
                $saveArr['img']=isset($gvv['img']) ? $gvv['img']:'';
                $saveArr['url']=isset($gvv['url']) ? $gvv['url']:'';
                $saveArr['status']=isset($gvv['goods_status']) ? $gvv['goods_status']:0;  //0未上架 1上架 2过期
                $saveArr['url']=isset($gvv['url']) ? $gvv['url']:'';
                $saveArr['sort']=isset($gvv['sort']) ? $gvv['sort']:0;
                $saveArr['business_num']=isset($gvv['stock_num']) ? $gvv['stock_num']:0;  //库存
                $start_time=isset($gvv['active_start_time']) ? trim($gvv['active_start_time']):'';
                $saveArr['start_time']=!empty($start_time) ? strtotime($start_time):0;
                $end_time=isset($gvv['active_end_time']) ? trim($gvv['active_end_time']):'';
                $saveArr['end_time']=!empty($end_time) ? strtotime($end_time):0;
                $saveArr['shop_goods_type']=$goods_type;
                $saveArr['shop_mer_name']=$gvv['merchant_name'];
                $saveArr['shop_store_name']=$gvv['store_name'];
                $saveArr['goods_id']=$gvv['goods_id'];
                $saveArr['del_time']=0;
                $saveArr['data_extra']=json_encode($gvv,JSON_UNESCAPED_UNICODE);
                if($navinfo){
                    $database_house_village_nav->where(array('id'=>$navinfo['id']))->data($saveArr)->save();
                }else{
                    $saveArr['add_time']=$nowtime;
                    $database_house_village_nav->data($saveArr)->add();
                }
            }
        }
        exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
    }

    public function edit_shop_goods_seckill(){

        $idd=isset($_POST['idd']) ? intval($_POST['idd']):0;
        $goods_id=isset($_POST['goods_id']) ? intval($_POST['goods_id']):0;
        $sort=isset($_POST['xsort']) ? intval($_POST['xsort']):0;
        $database_house_village_nav = D('House_village_nav');
        $village_id = 0;
        $tmpWhere=array('village_id'=>$village_id,'is_community_platform'=>1,'type'=>21,'id'=>$idd,'goods_id'=>$goods_id);
        $database_house_village_nav->where($tmpWhere)->data(array('sort'=>$sort))->save();
        exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
    }
    //删除
    public function del_shop_goods_seckill(){
        $idd_arr=isset($_POST['idd_arr']) ? $_POST['idd_arr']:"";
        $village_id = 0;
        $idd_arr=$idd_arr ? array_unique($idd_arr):array();
        if(empty($idd_arr) || !is_array($idd_arr)){
            exit(json_encode(array('err_code'=>1,'msg'=>'没有可删除数据！')));
        }
        $nowtime=time();
        $database_house_village_nav = D('House_village_nav');
        $tmpWhere=array('village_id'=>$village_id,'is_community_platform'=>1,'type'=>21,'id'=>array('in',$idd_arr));
        $database_house_village_nav->where($tmpWhere)->data(array('del_time'=>$nowtime,'status'=>4))->save();
        exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
    }
    public function check_village_money_list(){
        $village_id=129;

        //$whereStr='village_id='.$village_id.' AND id>='.$id;
        $whereStr='village_id='.$village_id.' AND use_time >= 1702656000';

        $money_list=M('Village_money_list')->where($whereStr)->field('*')->order('id asc')->select();

        $findArr=array();
        $monecout=0;
        $fistArr=$money_list['0'];
        $now_village_money=$fistArr['now_village_money'];
        foreach ($money_list as $kk=>$mvv){
            if($kk==0){
                continue;
            }
            $newK=$kk+1;
            $monecout +=$mvv['money'];
            $tmpMoy=$mvv['now_village_money']-$mvv['money'];
            if(isset($money_list[$newK])){
                $tmpM=$money_list[$newK]['money']+$mvv['now_village_money'];
                if($tmpM<$money_list[$newK]['now_village_money']){
                    $findArr[]=$mvv;
                }
            }
            $newvillage_money=$monecout+$now_village_money;
            if($newvillage_money<$mvv['now_village_money']){
                //$findArr[]=$mvv;
            }
        }
        print_r($mvv);
        echo '<br/><br/>';
        echo $monecout.'+'.$now_village_money.'='.($monecout+$now_village_money);
        echo '<br/><br/>';
        print_r($findArr);
    }

    public function jg_index()
    {
        $where = array(
            'del_time' => array('lt',10)
        );
        $count = D("House_app_jpush_mass")->where($where)
            ->count();
        import('@.ORG.system_page');
        $p = new Page($count,15);
        $list = D("House_app_jpush_mass")
            ->order('id DESC')
            ->where($where)
            ->limit($p->firstRow, $p->listRows)
            ->select();
        if (empty($list)) {
            $list = array();
        }else{
            $send_status_arr=array('待发送','发送中','已发送');
            $house_villageDb= M('House_village');
            $house_village_singleDb = M('House_village_single');
            $house_village_floorDb= D('House_village_floor');
            foreach ($list as $kk=>$vv){
                $list[$kk]['send_status_str']=$send_status_arr[$vv['send_status']];
                $list[$kk]['send_time_str']=$vv['send_time']>10 ? date('Y-m-d H:i:s',$vv['send_time']):'';
                $list[$kk]['add_time_str']=$vv['add_time']>10 ? date('Y-m-d H:i:s',$vv['add_time']):'';
                $list[$kk]['village_detail']='';
                $house_village=$house_villageDb->where(array('village_id'=>$vv['village_id']))->field('village_id,village_name,village_address')->find();
                if($house_village){
                    $list[$kk]['village_detail']=$house_village['village_name'];
                    if($vv['single_id']>0){
                        $village_single=$house_village_singleDb->where(array('village_id'=>$vv['village_id'],'id'=>$vv['single_id']))->field('single_name')->find();
                        if($village_single){
                            $single_name=$village_single['single_name'];
                            if(is_numeric($single_name)){
                                $single_name=$single_name.'(栋)';
                            }
                            $list[$kk]['village_detail'].=' '.$single_name;
                            if($vv['floor_id']>0){
                                $village_floor=$house_village_floorDb->where(array('village_id'=>$vv['village_id'],'floor_id'=>$vv['floor_id']))->field('floor_name')->find();
                                if($village_floor){
                                    $floor_name=$village_floor['floor_name'];
                                    if(is_numeric($floor_name)){
                                        $floor_name=$floor_name.'(单元)';
                                    }
                                    $list[$kk]['village_detail'].=' '.$floor_name;
                                }
                            }
                        }
                    }
                }
            }
        }
        $weixin_push_jpush_appkey=C('config.weixin_push_jpush_appkey');
        $weixin_push_jpush_secret=C('config.weixin_push_jpush_secret');
        $this->assign('weixin_push_jpush_appkey', $weixin_push_jpush_appkey);
        $this->assign('weixin_push_jpush_secret', $weixin_push_jpush_secret);
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
    public function jg_send_record(){
        $send_id = isset($_GET['send_id']) ? intval($_GET['send_id']):0;
        $where=array('mass_id'=>$send_id);
        $count = M("House_app_jpush_user_log")->where($where)
            ->count();
        import('@.ORG.system_page');
        $p = new Page($count,15);
        $field='ul.*,ub.name,ub.phone,ub.type,ub.floor_id,ub.vacancy_id,ub.relatives_type,ub.single_id,ub.layer_id';
        $list = M("House_app_jpush_user_log") ->join(" as ul left JOIN " . C('DB_PREFIX') . "house_village_user_bind as ub ON ul.bind_id=ub.pigcms_id")
            ->order('ul.send_status ASC,ul.id DESC')->field($field)
            ->where($where)
            ->limit($p->firstRow, $p->listRows)
            ->select();
        if (empty($list)) {
            $list = array();
        }else{
            $house_villageDb=D('House_village');
            foreach ($list as $kk=>$item){
                $send_status_str='发送成功';
                if($item['send_status']==2){
                    $send_status_str='发送失败';
                    if(empty($item['registration_id'])){
                        $send_status_str='此业主没有极光身份，发送失败';
                    }
                }
                $list[$kk]['send_status_str']=$send_status_str;
                $list[$kk]['send_time_str']=date('Y-m-d H:i:s',$item['send_time']);
                $room_address=$house_villageDb->get_single_floor_room($item['single_id'],$item['floor_id'],$item['layer_id'],$item['vacancy_id'],$item['village_id']);
                $list[$kk]['room_address']=$room_address;
            }
        }
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
    public function jg_add()
    {
        if (IS_POST) {
            $jg_title = isset($_POST['jg_title']) ? trim($_POST['jg_title']):'';
            if (!$jg_title) {
                $this->error('请填写标题！');exit;
            }
            $village_id = isset($_POST['village_id']) ? intval($_POST['village_id']):0;
            if ($village_id<1) {
                $this->error('请选择一个小区！');exit;
            }
            $single_id = isset($_POST['single_id']) ? intval($_POST['single_id']):0;
            $floor_id = isset($_POST['floor_id']) ? intval($_POST['floor_id']):0;
            $send_content = isset($_POST['send_content']) ? trim($_POST['send_content']):'';
            if(empty($send_content)){
                $this->error('请填写内容！');exit;
            }
            $url= isset($_POST['url']) ? trim($_POST['url']):'';
            $addData=array('jg_title'=>$jg_title,'village_id'=>$village_id);
            $addData['single_id']=$single_id;
            $addData['floor_id']=$floor_id;
            $addData['send_content']=$send_content;
            $addData['url']=$url;
            $addData['send_status']=0;
            $addData['add_time']=time();
            $insert_id=M('House_app_jpush_mass')->add($addData);
            if($insert_id){
                $models = '\app\community\model\service\HouseAppJpushInfoService/insertToRedisQueue';
                $param=['idd'=>$insert_id,'opt_type'=>'jpush_house_village_user_bind','village_id'=>$village_id];
                $result = invoke_v20_service($models, $param);
                $retval = $result['data']['retval'];
                
                $this->success('保存成功！');exit;
               // exit('<html><head><script>window.top.msg(1,"保存成功！",true,3);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
            }else{
                $this->error('保存失败请重试！');exit;
            }
        }else{
            $this->display();
        }
        
    }
}
