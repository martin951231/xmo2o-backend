<?php
/**
 * 街道网格员和工作人员
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/2/25 19:59
 */
namespace app\community\controller\manage_api\v1;

use app\common\model\service\UploadFileService;
use app\community\controller\manage_api\BaseController;

use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageGridService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\AreaStreetWorkersService;
use app\community\model\service\SmartQrCodeService;
use app\community\model\service\StreetWorksOrderService;
use app\community\model\service\AreaStreetEventCategoryService;
use app\community\model\service\TaskReleaseService;
use customization\customization;
class GridStreetController extends BaseController{
    use customization;
    /**
     * Notes: 网格员和事件处理人员首页信息
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/27 14:00
     */
    public function index() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $login_arr=$arr;
        $app_type = $this->request->param('app_type','');
        $area_id  = $arr['user']['area_id'];
        if (empty($area_id)) {
            $area_id = $this->request->param('area_street_id','0','intval');
        }
        $service_area_street = new AreaStreetService();
        $service_street_worker = new AreaStreetWorkersService();
        $where = [
            'area_id' => $area_id
        ];
        $area_info = $service_area_street->getAreaStreet($where);
        if ($this->login_info['login_name']) {
            $login_name = '，'.$this->login_info['login_name'];
        } else {
            $login_name = '';
        }
        $is_wisdom_qrcode=false;
        $area_name = isset($area_info['area_name']) && $area_info['area_name'] ? $area_info['area_name'] : '';
        $service_login = new ManageAppLoginService();
        $welcome_tip = $service_login->time_tip(time(),$login_name,$area_name);
        $worker_id = $this->_uid;
        $arr = $service_street_worker->getWorkIndexNum($worker_id);
        $arr['welcome_tip'] = $welcome_tip;
        $http_base = cfg('site_url').'/v20/public/static/community/images/statistics_column/';
        $column_list = [];
        if ($arr && isset($arr['is_show']) && $arr['is_show']) {
            $is_wisdom_qrcode=true;
            $column_list[] = ['ioc'=>$http_base.'wangluo.png','title'=>'网格事件管理','url'=>cfg('site_url').'/packapp/community/pages/Community/grid/eventList?current=0&type=todo','type'=>'grid_event'];
            $column_list[] = ['ioc'=>$http_base.'liuyan.png','title'=>'留言建议','url'=>cfg('site_url').'/packapp/community/pages/Community/networkManage/messageList','type'=>'massage'];
            if($this->hasSuiChuan()) {
                $column_list[] = ['ioc' => $http_base . 'erweima.png', 'title' => '智慧二维码', 'url' => cfg('site_url') . '/packapp/community/pages/Community/smartQRcode/taskList', 'type' => 'qrcode'];
            }
            $column_list[] = ['ioc'=>$http_base.'guanliwangluo.png','title'=>'管理网络','url'=>cfg('site_url').'/packapp/community/pages/Community/networkManage/networkIndex','type'=>'grid_range'];
            $column_list[] = ['ioc'=>$http_base.'renwu.png','title'=>'任务下达','url'=>cfg('site_url').'/packapp/community/pages/Community/networkManage/taskList','type'=>'grid_street_task'];

        }
        $column_list[] = ['ioc'=>$http_base.'shezhi.png','title'=>'设置','url'=>cfg('site_url').'/packapp/plat_dev/pages/Community/index/setup','type'=>'setting'];
       /* if($app_type == 'ios' || $app_type == 'android'){
            $column_list[] = ['ioc'=>$http_base.'setting.png','title'=>'设置','url'=>'','type'=>'setting'];
        }*/

        if (!cfg('ComingSoonHide')) {
            $column_list[] = ['ioc'=>$http_base.'other.png','title'=>'敬请期待','url'=>'','type'=>'other' ];
        }
        $arr['column_list'] = $column_list;
        $village_id=0;
        $wid=0;
        if($is_wisdom_qrcode){
            $phone = $login_arr['user']['work_phone'];
            $worker_village=(new HouseVillageService())->getHouseWorker(['account'=>$phone],'village_id,wid');
            if($worker_village){
                $village_id=$worker_village['village_id'];
                $wid=$worker_village['wid'];
            }else{
                $is_wisdom_qrcode=false;
            }
        }
        $arr['qrcode_position']=(new SmartQrCodeService())->getWisdomQrcodePersonTask($village_id,$wid,$is_wisdom_qrcode);
        return api_output(0,$arr);
    }

    // 状态返回
    public $status_desc = [
        ['status' => 'todo', 'name' => '待处理'],
        ['status' => 'processing', 'name' => '处理中'],
        ['status' => 'processed', 'name' => '已处理'],
        ['status' => 'all', 'name' => '全部'],
    ];

    /**
     * Notes: 网格员或者事件处理人员获取工单列表
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/1 13:57
     */
    public function workersOrderList() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
       //  fdump_api([$this->login_info],'login_info_0507',1);
        $status = $this->request->post('status');
        if(!$status) {
            // 默认待处理
            $status = 'todo';
        }
        $page = $this->request->post('page');
        $search = $this->request->post('search');
        $worker_id = $this->_uid;
        $street_works_order_service = new StreetWorksOrderService();
        $arr = $street_works_order_service->workGetWorksOrderLists($worker_id,$status,$search,$page,10);
        $arr['status_desc'] = $this->status_desc;
        return api_output(0,$arr);
    }

    /**
     * Notes: 工单内容内容提交页信息
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/1 17:02
     */
    public function streetEventAddInfo() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $area_id  = $this->login_info['area_id'];
        if (empty($area_id)) {
            $area_id = $this->request->param('area_street_id','0','intval');
        }
        if(!$area_id) {
            return api_output_error(1003,'缺少必传参数');
        }
        $worker_id = $this->_uid;
        // 查询对应街道下所有网格信息
        $service_street_worker = new AreaStreetWorkersService();
        try{
            $arr = $service_street_worker->workGetStreetGridOrder($area_id,$worker_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * Notes: 单独获取全部分类
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/2 13:27
     */
    public function streetAllEventCategory() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $area_id  = $this->login_info['area_id'];
        if (empty($area_id)) {
            $area_id = $this->request->param('area_street_id','0','intval');
        }
        if(!$area_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $type = $this->request->post('type');
        $cat_fid = $this->request->post('cat_fid');
        $arr = [];
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $where[] = ['status', '=', 1];
        $where[] = ['area_id', '=', $area_id];
        if ($cat_fid) {
            $where[] = ['cat_fid', '=', $cat_fid];
        } elseif ($type && $type='single') {
            $where[] = ['cat_fid', '=', 0];
        } elseif (!$type) {
            $type = 'all';
        }
        $field = true;
        $list = $service_area_street_event_category->userGetCategoryList($where,$field,'sort DESC, cat_id DESC',$type);
        if (empty($list)) {
            return api_output_error(1003,'暂无分类');
        }
        $arr['list'] = $list;
        return api_output(0,$arr);
    }

    /**
     * Notes: 图片上传
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/23 19:30
     */
    public function  streetEventImg() {
        $file = $this->request->file('imgFile');
        $service_area_street = new AreaStreetService();
        try{
            $imgurl = $service_area_street->uploads($file,'gridWorker');
            $arr = [];
            $arr['url'] = thumb_img($imgurl,200, 200, 'fill');
            $arr['imageUrl_path'] = $imgurl;
            $arr['imageUrl'] = replace_file_domain($imgurl);
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 网格员身份添加工单
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/2 14:11
     */
    public function streetOrderAdd() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id) {
            return api_output_error(1001,'请选择分类');
        }
        $area_id  = $this->login_info['area_id'];
        if (empty($area_id)) {
            $area_id = $this->request->param('area_street_id','0','intval');
        }
        if(!$area_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $grid_range_id = $this->request->post('grid_range_id',0);
        if(!$grid_range_id) {
            return api_output_error(1001,'请选择对应网格');
        }
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $order_content = htmlspecialchars(trim($order_content));
        if (empty($order_imgs) && !$order_content) {
            return api_output_error(1001,'缺少必传参数');
        }
        $order_address = $this->request->post('order_address');

        $street_works_order_service = new StreetWorksOrderService();
        $worker_id = $this->_uid;
        $data = [
            'cat_id' => $cat_id,
            'street_id' => $area_id,
            'grid_range_id' => $grid_range_id,
            'worker_id' => $worker_id,
            'order_address' => $order_address?$order_address:'',
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
            'now_role' => 2,
            'order_type' => 1,
            'order_status' => 3,
            'event_status' => 1,
            'order_go_by_center' => 1
        ];
        try{
            $info = $street_works_order_service->addStreetWorksOrder($data);
            $arr = [];
            $arr['info'] = $info;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes:获取工单详情信息
     * @return \json
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/3/3 10:45
     */
    public function streetOrderDetail() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $worker_id = $this->_uid;
        $street_works_order_service = new StreetWorksOrderService();
        $arr = [];
        try{
            $order_detail = $street_works_order_service->workGetOrder($order_id,$worker_id,true);
            $arr['order_detail'] = $order_detail;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $arr);
    }

    /**
     * Notes: 更改工单状态
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/3 16:56
     */
    public function streetOrderChange() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $log_name = $this->request->post('log_name');
        $order_id = $this->request->post('order_id',0);
        if(!$order_id || !$log_name) {
            return api_output_error(1001,'缺少必传参数');
        }
        $worker_id = $this->_uid;
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $order_content = htmlspecialchars(trim($order_content));
        $street_works_order_service = new StreetWorksOrderService();
        $data = [
            'worker_id' => $worker_id,
            'order_id' => $order_id,
            'log_name' => $log_name,
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
        ];
        try{
            $log_arr = $street_works_order_service->filterAddWorkOrderLog(1,$data);
            $arr = [];
            $arr['log'] = $log_arr;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    /**
     * Notes: 获取工单处理记录
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/25 16:05
     */
    public function streetGetOrderLog() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $worker_id = $this->_uid;
        try{
            $arr = [];
            $street_works_order_service = new StreetWorksOrderService();
            $log_list = $street_works_order_service->workGetOrderLog($order_id, $worker_id);
            $arr['log'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * Notes: 留言建议获取列表数据
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * 'area_id' => 4,
     * 'area_type' => 0,
     * @date_time: 2021/3/1 13:57
     */
    public function getMassageList() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $resultArr=(new AreaStreetService())->checkAreaStreetIdentity($this->login_info,$arr['user']);
        $area_id  = $resultArr['area_id'];
        $area_type  = $resultArr['area_type'];

       /* $area_id  = 4;
        $area_type  =0;*/
        $where = [];
        if (0==$area_type) {
            $where[] = ['street_id','=',$area_id];
        } elseif(1==$area_type) {
            $where[] = ['community_id','=',$area_id];
        }
        // 对应查询状态
        $status = $this->request->param('status','','intval');
        if ($status) {
            $where[] = ['status','=',$status];
        }
        // 页数
        $page = $this->request->param('page',0,'intval');

        $service_area_street = new AreaStreetService();
        $out = $service_area_street->getCommunitySuggestsList($where, $page);
        return api_output(0,$out);
    }


    /**
     * 获取留言建议详情
     * @param 传参
     * array (
     *  'suggestions_id'=> '建议id',
     *  'ticket' => '', 登录标识 必传
     * )
     * 'area_id' => 4,
     * 'area_type' => 0,
     * @author: wanziyang
     * @date_time: 2020/5/27 17:51
     * @return \json
     */
    public function getMassageDetail() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }

//        $area_id  = $this->login_info['area_id'];
//        $area_type  = $this->login_info['area_type'];
        $resultArr=(new AreaStreetService())->checkAreaStreetIdentity($this->login_info,$arr['user']);
        $area_id  = $resultArr['area_id'];
        $area_type  = $resultArr['area_type'];

        $service_area_street = new AreaStreetService();
        $where = [];
        if (0==$area_type) {
            $where[] = ['street_id','=',$area_id];
        } elseif(1==$area_type) {
            $where[] = ['community_id','=',$area_id];
        }
        // 街道社区留言建议id
        $suggestions_id = $this->request->param('suggestions_id','','intval');
        if(empty($suggestions_id)){
            return api_output_error(1001,'请上传id！');
        }
        $where[] = ['suggestions_id','=',$suggestions_id];
        $out = $service_area_street->getSuggestsDetail($where);
        // 如果是社区这边用户提的，街道只能看 不能回复
        $out['info']['is_edit'] = true;
        if (0==$area_type && $out['info']['community_id']>0) {
            $out['info']['is_edit'] = false;
        }
        return api_output(0,$out);
    }

    /**
     * 添加留言建议回复
     * @param 传参
     * array (
     *  'suggestions_id'=> '建议id',
     *  'reply_content'=> '回复内容',
     *  'ticket' => '', 登录标识 必传
     * )
     * 'area_id' => 4,
     * 'area_type' => 0,
     * @author: wanziyang
     * @date_time: 2020/5/27 17:51
     * @return \json
     */
    public function saveMessageSuggestionsReplyInfo() {
        $service_area_street = new AreaStreetService();
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
//        $area_id  = $this->login_info['area_id'];
//        $area_type  = $this->login_info['area_type'];

        $resultArr=(new AreaStreetService())->checkAreaStreetIdentity($this->login_info,$arr['user']);
        $area_id  = $resultArr['area_id'];
        $area_type  = $resultArr['area_type'];
        $where = [];
        if (0==$area_type) {
            $where[] = ['street_id','=',$area_id];
        } elseif(1==$area_type) {
            $where[] = ['community_id','=',$area_id];
        }
        // 街道社区留言建议id
        $suggestions_id = $this->request->param('suggestions_id','','intval');
        if(empty($suggestions_id)){
            return api_output_error(1001,'请上传id！');
        }
        $where[] = ['suggestions_id','=',$suggestions_id];
        $out = $service_area_street->getSuggestsDetail($where);
        if (!$out['info']) {
            return api_output_error(1002,'对应留言建议不存在！');
        }
        $reply_content = $this->request->param('reply_content');
        if(empty($reply_content)){
            return api_output_error(1001,'请填写回复！');
        }
        $data = [
            'suggestions_id' => $suggestions_id,
            'reply_uid' => $area_id,
            'reply_role' => $area_type,
            'reply_content' => $reply_content,
            'add_time' => time(),
        ];
        $reply_id = $service_area_street->addSuggestsReplyOne($data);
        if(!$reply_id){
            return api_output_error(1002,'回复失败！');
        } else {
            if (2!=$out['info']['status']) {
                $service_area_street->saveSuggestsOne($where,['status'=>2]);
            }
            return api_output(0,['reply_id'=>$reply_id]);
        }
    }

    /**
     * 查询工作人员的任务列表
     * @author:zhubaodi
     * @date_time: 2022/5/9 17:39
     */
    public function getTaskWorkerList(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $data['area_id']  = $this->login_info['area_id'];
        $data['area_type']  = $this->login_info['area_type'];
        $data['wid']  = $this->login_info['worker_id'];
       /* $data['area_id']  = 4;
        $data['area_type']  =0;
        $data['wid']=77;*/
        $data['page'] = $this->request->param('page',1);
        $data['limit'] = 10;
        try{
            $arr = [];
            $task_release_service = new TaskReleaseService();
            $log_list = $task_release_service->getTaskWorkerList($data);
            //$arr['list'] = $log_list;
            return api_output(0, $log_list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 查询工作人员的任务详情
     * @author:zhubaodi
     * @date_time: 2022/5/9 17:39
     */
    public function getTaskWorkerDetail(){
       $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $data['area_id']  = $this->login_info['area_id'];
        $data['area_type']  = $this->login_info['area_type'];
        $data['wid']  = $this->login_info['worker_id'];
        $data['task_id'] = $this->request->param('task_id',0);
        if (!$data['task_id']) {
            return api_output_error(1002,'请先选择任务！');
        }
        try{
            $arr = [];
            $task_release_service = new TaskReleaseService();
            $log_list = $task_release_service->getTaskWorkerDetail($data);
            $arr['info'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 工作人员添加任务回复
     * @author:zhubaodi
     * @date_time: 2022/5/9 17:39
     */
    public function addTaskReleaseRecord(){
       $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $data['area_id']  = $this->login_info['area_id'];
        $data['area_type']  = $this->login_info['area_type'];
        $data['wid']  = $this->login_info['worker_id'];

        $data['task_id'] = $this->request->param('task_id',0);
        $data['complete_num_u'] = $this->request->param('complete_num_u',0);
        $data['content'] = $this->request->param('content','');
        $data['img'] = $this->request->param('img','');
        if (empty($data['complete_num_u'])) {
            return api_output_error(1002,'请先填写任务数量！');
        }
        if (empty($data['content'])) {
            return api_output_error(1002,'请先填写上报内容！');
        }
        $data['complete_num_u']=(int)$data['complete_num_u'];
        if ($data['complete_num_u']<0) {
            return api_output_error(1002,'请正确填写任务数量！');
        }
        try{
            $arr = [];
            $task_release_service = new TaskReleaseService();
            $log_list = $task_release_service->addTaskReleaseRecord($data);
            $arr['info'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 网络管理
     * @author:zhubaodi
     * @date_time: 2022/5/10 9:50
     */
    public function getGridRange()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $data['area_id']  = $this->login_info['area_id'];
        $data['area_type']  = $this->login_info['area_type'];
        $data['wid']  = $this->login_info['worker_id'];

        $service_grid = new HouseVillageGridService();
        try{
            $log_list = $service_grid->getGridRangeList($data,1);
            return api_output(0, $log_list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 上传任务回复图片
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function uplodeTaskImg(){
        $service = new UploadFileService();
        $file = $this->request->file('file');
        $upload_dir='task/img';
        try {
            $savenum =  $savepath = $service->uploadPictures($file, $upload_dir);;
            return api_output(0, ['url'=>$savenum], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
}